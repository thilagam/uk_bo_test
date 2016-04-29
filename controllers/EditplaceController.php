<?php
/**
 * DocoboController
 * 
 * @author shiva
 * @version 1.0
 */

class EditplaceController extends Ep_Controller_Action
{
	private $controller = "editplace";
	private $my_obj;
	private $filesname;
    private $entryLang;
    private $cntry;
    private $totalLang;
    private $my_view;
    private $text_admin;
    
	public function init()
	{
		parent::init();
		//$this->_lang = "en";
		$this->_view->lang = $this->_lang;
		$this->cntry = Zend_Registry::get('_country');
		$this->_view->cntry = $this->cntry;
		$this->totalLang = Zend_Registry::get('_totalLang');
		$this->_view->allLang = $this->totalLang;
		Zend_Loader::loadClass('Ep_Db_Category2');
		$this->cate_obj = new Ep_Db_Category2();
		Zend_Loader::loadClass('Ep_Db_Contract2');
		$this->cont_obj = new Ep_Db_Contract2();
		$this->xml_path = "/home/sites/site4/users/xmldb_editplace/translation/";
		$this->my_obj=new Ep_Db_ArrayDb2($this->xml_path."main_meta_data.xml","root");
		$this->adminLogin	= Zend_Registry::get ( 'adminLogin' );
		$this->_view->loginName = $this->adminLogin->loginName;
		$this->_view->permission = $this->adminLogin->permission;
		//$this->text_admin =  $this->my_obj->loadArrayv2("text_admin", $this->_lang);
		$this->_view->ADMINISTRATION_INTERFACE = $this->text_admin['ADMINISTRATION_INTERFACE'];
		$this->_view->CORRECTOR = $this->text_admin['CORRECTOR'];
		$this->_view->Disconnect = $this->text_admin['Disconnect'];
		$this->_view->Login = $this->text_admin['Login'];
		$this->_view->Password = $this->text_admin['Password'];
	    $this->_view->Country = $this->text_admin['Country'];
	    
	    $this->_view->controller = $this->_request->getParam('controller');
	    $this->_view->siteName = $_SERVER['HTTP_HOST'];
		ini_set("memory_limit","50M");
	}
	
	public function listcategoriesAction()
	{
		$this->listcategories	= Zend_Registry::get ( 'listcategories' );
		$type = $this->listcategories->listcategory;
		if($type != '')
		{
			$listcategories =  $this->cate_obj->loadArrayv2($type , $this->_lang);
			$entredCode = $this->_request->getParam('entredCode');
			if(array_key_exists($entredCode, $listcategories))
				echo 'Code already exists, please enter another code';
			else
				return false;
		}
	}
	
	public function listparentidAction()
	{
		$listparentid =  $this->cate_obj->loadArrayv2("displaySubject" , $this->_lang);
		$extract = array();
		$entredCode = $this->_request->getParam('entredCode');
		foreach ($listparentid as $key => $val )
		{
			if($val == '1')
			$extract[] = $key;
		}
			if(in_array($entredCode, $extract))
				return false;
			else
				echo 'Entered Parent doesnt exists, please enter valid parent';
	}	

	public function filelistingAction()
	{
		$this->_view->charset = "utf8";
		$arraylangTemp = array();
		$this->_view->arraylangTemp = $arraylangTemp;
		$this->adminLogin	= Zend_Registry::get ( 'adminLogin' );
		//default check mark
		$this->_view->dfr = "checked";
		$this->_view->actiontodo = "filelisting";
		$this->my_obj=new Ep_Db_ArrayDb2($this->xml_path."main_meta_data.xml","root");
		if(!$this->_request->isPost())
		{
			$file = str_replace(" ","_",$this->_request->getParam("file"));
			$data = $this->my_obj->getAllNodes();
			if ($this->_request->getParam("action1") == "update")
			{
				if(is_numeric(substr($file,0,1)) == "true")
				$file = "_".$file;
				$this->_view->file = $file;
				$this->_view->desc = $this->_request->getParam("descr");
				$result = $data->xpath('/root/' . $file);
				$edit = $result[0]['editable'];
				if($edit == 'Yes')
				$this->_view->editableYes = 'checked="checked"'; 
				else
				$this->_view->editableNo = 'checked="unchecked"';
				$languag1 = $result[0]['language'];
				$arrayLang = split('\|', $languag1);
				foreach($arrayLang as $value1)
				{
					if ($value1 == "fr")
						$this->_view->fr = "checked";
					if ($value1 == "pt")
						$this->_view->pt = 'checked="checked"';
					if ($value1 == "en")
						$this->_view->en = 'checked="checked"';
					if ($value1 == "in")
						$this->_view->in = 'checked="checked"';
				}
				if ($this->_view->fr != "checked")
				$this->_view->dfr = "unchecked";
			}
			
			if ($this->_request->getParam("action1") == "delete")
			{
				$result = $data->xpath('/root/' . $file);
				$language1 = $result[0]['language'];
				$this->my_obj->deleteNode($file);
				//$this->my_obj->deleteFile($file, $language1);
				echo '<META HTTP-EQUIV=Refresh CONTENT="0; URL=filelisting">';
			}
			$this->_view->arrayv = $data;
			echo $this->render($this->controller."_filelisting");
		}
		
		//if post back
		if ($this->_request->isPost())
		{
			if ($this->_request->getParam("action1") == "back")
			{
				echo '<META HTTP-EQUIV=Refresh CONTENT="0; URL=filelisting">';
				exit();
			}
			if ($this->_request->getParam("action1") == "synchronize")
			{
				//echo 'synchronize';
				if (!copy(DATA_PATH."dacodoc.xml", DATA_PATH."oboulo.xml")) 
				{
	    			echo "failed to copy testoboulo.xml...\n";
				}
			}			
			
			$this->_view->fr = "";
			$this->_view->pt = "";
			$this->_view->en = "";
			$this->_view->in = "";
			
			$data = $this->my_obj->getAllNodes();
			$data1 = array("filename" => str_replace(" ","_",$this->_request->getParam("Filename")), "description" => $this->_request->getParam("Description"));
			
			$languag1 = $this->_request->getParam("CountryLanguage1");
			$languag2 = $this->_request->getParam("CountryLanguage2");
			$languag3 = $this->_request->getParam("CountryLanguage3");
			$languag4 = $this->_request->getParam("CountryLanguage4");
			
			if ($languag1 == 'fr')
				$language = $languag1 . "|";
			if ($languag2 == 'pt')
				$language .= $languag2 . "|";
			if ($languag3 == 'en')
				$language .= $languag3 . "|";
			if ($languag4 == 'in')
				$language .= $languag4;
			
			$editAble = $this->_request->getParam("Editable");
			$attributes = array("desc" => $data1['description'],"language" => $language,"editable" => $editAble);
			$temp = 0;
			$path = "";
			if ($this->_request->getParam("action1") == "findarray")
			{
				$arrayname = $this->_request->getParam("find");
				$result = $data->xpath('/root/*/*/' . $arrayname);
				$path = "Not Found Array";
				
				if(empty($result))
				{
					$path = "Not Found Array";
				}
				else 
				{
					$result = $data->xpath('/root/*');
					$totalfile = array("texte.php","blackList.php","country.php","data.php","error.php","meta_values.php","price.php","sending.php","subject.php","process.php");
					foreach ($totalfile as $file)
					{
						$result1 = $data->xpath('/root/'.$file.'/*/' . $arrayname);
						if(!empty($result1))
						{
							$path = 'Found at '.$file;
							break;
						}
					}
				}
			}
			$this->_view->arrayfind = $path;			
			
			if ($this->_request->getParam("Submit1") == "Submit")
			{
				foreach ($data->children() as $child)
				{
					if ($child->getName() == $data1['filename'])
					{
						$temp = 1;
						$this->my_obj->updateFileNode($data1['filename'],$attributes);
						echo '<META HTTP-EQUIV=Refresh CONTENT="0; URL=filelisting">';
					}
				}
				if($temp == 0)
				{
					$status = $this->my_obj->insertNode($data1['filename'], $attributes, $this->totalLang);
					$language = split('\|', $language);
				}
				if($temp == 1)
				{
					$this->my_obj->insertNode($data1['filename'], $attributes, $this->totalLang);
				}
			}
			
			$data = $this->my_obj->getAllNodes();
			$this->_view->arrayv = $data;
			echo $this->render($this->controller."_filelisting");
		}
	}
	
	public function arraymangamentAction()
	{
		$this->_view->charset = "utf8";
		$this->_view->dfr = "checked";
		$this->adminLogin	= Zend_Registry::get ( 'adminLogin' );
		$this->my_obj=new Ep_Db_ArrayDb2($this->xml_path."main_meta_data.xml","root");
		
		if (! $this->_request->isPost())
		{
			$data = $this->my_obj->getAllNodes();
			//var_dump($data);
			$this->_view->fr = "";
			$this->_view->pt = "";
			$this->_view->en = "";
			$this->_view->in = "";
			
			$this->_view->editableYes = "";
			$this->_view->editableNo = "";
			$this->_view->typeArray = "";
			$this->_view->typeSimple = "";
			$this->_view->indexYes = "";
			$this->_view->indexNo = "";
			$this->_view->arrayName = "";
			$this->_view->comment = "";
			
			$action1 = $this->_request->getParam("action1");
			$fileName = $this->_request->getParam("fileName");
			$arrayname = $this->_request->getParam("arrayname");
			
			//update section
			if ($action1 == "update")
			{
				$result = $data->xpath('/root/' . $fileName . '/'.$this->_lang.'/' . $arrayname);
				$editable = $result[0]['editable'];
				$type = $result[0]['type'];
				$index = $result[0]['index'];
				$languag1 = $result[0]['language'];
				$comment = $result[0]['desc'];
				
				// assigning to view variables
				$this->_view->arrayName = $arrayname;
				$this->_view->comment = $comment;
				$arrayLang = split('\|', $languag1);
				foreach($arrayLang as $languag1)
				{
					if ($languag1 == "fr")
						$this->_view->fr = "checked";
					if ($languag1 == "pt")
						$this->_view->pt = 'checked="checked"';
					if ($languag1 == "en")
						$this->_view->en = 'checked="checked"';
					if ($languag1 == "in")
						$this->_view->in = 'checked="checked"';
				}
				if ($this->_view->fr != "checked")
					$this->_view->dfr = "unchecked";
				if ($type == "Array")
					$this->_view->typeArray = 'checked="checked"'; else
					$this->_view->typeArray = "";
				if ($type == "Simple")
					$this->_view->typeSimple = 'checked="checked"'; else
					$this->_view->typeSimple = "";
				if ($editable == "Yes")
					$this->_view->editableYes = 'checked="checked"'; else
					$this->_view->editableYes = "";
				if ($editable == "No")
					$this->_view->editableNo = 'checked="checked"'; else
					$this->_view->editableNo = "";
				if ($index == "Yes")
					$this->_view->indexYes = 'checked="checked"'; else
					$this->_view->indexYes = "";
				if ($index == "No")
					$this->_view->indexNo = 'checked="checked"'; else
					$this->_view->indexNo = "";
			}
			if ($action1 == "delete")
			{
				$result = $data->xpath('/root/' . $fileName . '/'.$this->_lang.'/' . $arrayname);
				$languag1 = $result[0]['language'];
				$this->_view->arrayName = $arrayname;
				$this->_view->filename = $fileName;
				$this->my_obj->deleteArrayNode($fileName, $arrayname, $languag1);
				echo '<META HTTP-EQUIV=Refresh CONTENT="0; URL=arraymangament?fileName=' . $fileName . '">';
			}
			$data = $this->my_obj->getAllArrayDetails($fileName, $this->_lang);
			$this->_view->arrayv = $data;
			$this->_view->filename = $fileName;
			echo $this->_view->render($this->controller."_arraymangament");
		}
		if ($this->_request->isPost())
		{
			$fileName = $this->_request->getParam("fileName");
			$this->_view->filename = $fileName;
			$action1 = $this->_request->getParam("action1");
			if ($this->_request->getParam("Submit1") == 'Submit')
			{
				$this->_view->fr = "";
				$this->_view->pt = "";
				$this->_view->en = "";
				$this->_view->in = "";
				
				$arrayname = $this->_request->getParam("Arrayname");
				$arrayname = str_replace(" ","_",$arrayname);
				$comment = $this->_request->getParam("Comment");
				$languag1 = $this->_request->getParam("CountryLanguage1");
				$languag2 = $this->_request->getParam("CountryLanguage2");
				$languag3 = $this->_request->getParam("CountryLanguage3");
				$languag4 = $this->_request->getParam("CountryLanguage4");
				$type = $this->_request->getParam("Type");
				$index = $this->_request->getParam("Index");
				$editable = $this->_request->getParam("Editable");
				
				if ($languag1 == 'fr')
					$language = $languag1 . "|";
				if ($languag2 == 'pt')
					$language .= $languag2 . "|";
				if ($languag3 == 'en')
					$language .= $languag3 . "|";
				if ($languag4 == 'in')
					$language .= $languag4;
				
				$attributes = array("desc" => $comment, "language" => $language, "editable" => $editable, "type" => $type, "index" => $index);
				$status = $this->my_obj->checkChildNodeExistence($fileName, $this->_lang, $arrayname);
				
				if($status == true)
				{
					$data2 = $this->my_obj->getAllNodes();
					$strTemp = '/root/' . $fileName .'/'.$this->_lang.'/'.$arrayname;
					$result = $data2->xpath($strTemp);
					$prevLangList = $result[0]['language'];
					
					$pLang = split('\|', $prevLangList);
					$cLang = split('\|', $language);
					$dif = array_diff($pLang,$cLang);
					foreach ($dif as $l)
					$del .= $l. "|";
					if($del != '')
					{
						$this->my_obj->deleteArrayNode($fileName, $arrayname, $del);	
					}
					$this->my_obj->updateArrayNode($fileName, $arrayname, $language, $attributes);
					echo '<META HTTP-EQUIV=Refresh CONTENT="0; URL=arraymangament?fileName=' . $fileName . '">';
				}
				else
				{
					if($type == 'Array')
					{
						$newarray = fopen("$this->xml_path$arrayname.xml","x");
						chmod("$this->xml_path$arrayname.xml", 0777);
						//echo "$this->xml_path$arrayname.xml";
						$contents = '<?xml version="1.0" encoding="UTF-8"?><root>';
						$contents .= '<'.$fileName.' desc="'.$comment.'" language="'.$language.'" editable="'.$editable.'">';
						$subcontents = '<'.$arrayname.' desc="'.$comment.'" language="'.$language.'" editable="'.$editable.'" type="'.$type.'" index="'.$index.'">';
						$subcontents .= '</'.$arrayname.'>';
						if ($languag1 == 'fr')
						$contents .= '<fr>'.$subcontents.'</fr>';
						if ($languag2 == 'pt')
						$contents .= '<pt>'.$subcontents.'</pt>';
						if ($languag3 == 'en')
						$contents .= '<en>'.$subcontents.'</en>';
						if ($languag4 == 'in')
						$contents .= '<in>'.$subcontents.'</in>';
						$contents .= '</'.$fileName.'>';
						$contents .= '</root>';
						@fwrite($newarray, $contents);
						@fclose($newarray);
					}
					$this->my_obj->addArrayNodesDetails($fileName, $arrayname, $attributes,$language);
				}
			}
			$data = $this->my_obj->getAllArrayDetails($fileName, $this->_lang);
			$this->_view->arrayv = $data;
			echo $this->_view->render($this->controller."_arraymangament");
		}
	}
 	
	public function arrayindexAction()
	{
		$this->_view->charset = "utf8";
		$this->adminLogin	= Zend_Registry::get('adminLogin');
		$this->_view->lfr = '';
		$this->_view->lpt = '';
		$this->_view->len = '';
		$this->_view->lin = '';		
		$this->_view->invisibleS = '';
		$this->_view->invisibleU = 'invisible';
		
		$fileName = $this->_request->getParam("fileName");
		$arrayName = $this->_request->getParam("arrayName");
		$countryLang = $this->_request->getParam("countryLang");
		
		$this->my_obj = new Ep_Db_ArrayDb2($this->xml_path."$arrayName.xml", "root");
		//$this->my_obj=new Ep_Db_ArrayDb2($this->xml_path."main_meta_data.xml","root");
		$data = $this->my_obj->getAllNodes();
		$result = $data->xpath('/root/' . $fileName . '/'.$this->_lang.'/' . $arrayName);
		$this->_view->resultF = $result;
		
		$count = 0;
		$str = array();
		
		foreach($result[0]->children() as $grand_gen)
		{
			$str[] = str_replace("element", "", $grand_gen[0]->getName());
		}
		
		if(!empty($str))
		{
			$count = max($str);
			$count++;
		}
		$this->_view->count = $count;
		unset($str);		
		foreach($result[0]->children() as $grand_gen)
		{
			foreach($this->totalLang as $l)
			$resultChk[$l] = $data->xpath('/root/' . $fileName . '/' . $l . '/' . $arrayName . '/' . $grand_gen[0]->getName());
		}
		
		foreach($this->totalLang as $l)
		{
			$resultView[$l] = $data->xpath('/root/' . $fileName . '/' . $l . '/' . $arrayName);			
			$this->_view->eachlangfrView = $resultView[$l][0]['language'];
		}
		$this->_view->resultView = $resultView;
		$this->_view->typefrView = $resultView[$this->_lang][0]['type'];
		$this->_view->langfrView = $resultView[$this->_lang][0]['language'];
		
		$editable = $result[0]['editable'];
		$type = $result[0]['type'];
		$index = $result[0]['index'];
		$language1 = $result[0]['language'];
		$desc = $result[0]['desc'];
		$action1 = $this->_request->getParam("action1");
		$indexNo = $this->_request->getParam("indexNo");
		
		//assign values to view
		$this->_view->indexNo = '';
		$this->_view->filename = $fileName;
		$this->_view->vArrayName = $arrayName;
		$this->_view->indexStatus = $index;
		$this->_view->type = $type;
		$this->_view->editable = $editable;
		if($indexNo == 'indexNo')
		$this->_view->indexNo = 'invisible';
		$arrayLang = split('\|', $language1);
		
		//for view purpose - it will decide which language should be checkmarked 
		foreach($arrayLang as $languag1)
		{
			if ($languag1 == "fr")
				$this->_view->lfr = 'fr';
			if ($languag1 == "pt")
				$this->_view->lpt = 'pt';
			if ($languag1 == "en")
				$this->_view->len = 'en';
			if ($languag1 == "in")
				$this->_view->lin = 'in';
		}
		$indexFind = '';
		$this->_view->index = $indexFind;
		
		$searchword = $this->_request->getParam("searchword");		
		if (!$this->_request->isPost() && $searchword != "search")
		{
			if ($action1 == "delete")
			{
				$arrayIndex = $this->_request->getParam("indexName");
				if($index == 'Yes')
				$this->my_obj->deleteIndexNode($fileName, $arrayName, $arrayIndex, $arrayLang);
				else
				{
					$this->my_obj->deleteIndexNodeForIndexNo($fileName, $arrayName, $arrayIndex, $arrayLang);
				}
				echo '<META HTTP-EQUIV=Refresh CONTENT="0; URL=arrayindex?fileName=' . $fileName . '&arrayName=' . $arrayName . '">';
			}
			else
			{
				$this->_view->arrayv = $data;
				$this->_view->render($this->controller."_arrayindex");
			}
		}
		
		if($this->_request->isPost() || $searchword == "search")
		{
			$search1 = $this->_request->getParam("btnFind");
			$search2 = $this->_request->getParam("searchVar");
			$search3 = $this->_request->getParam("NewEntry"); 
			
			if($search1 == "Search" || $search2 == "search" || $search3 == "NewEntry")
			{
				$find = $this->_request->getParam("find");
				//echo 'Find '.$find;	
				$this->_view->find = $find;
				$this->_view->indexFind = true;
			}
			
			$j = $this->_request->getParam("j");
			$indx = $this->_request->getParam("indx");
			
			$singleUpdate = false;
			for($i=0;$i<15;$i++)
			{
				$chk = $this->_request->getParam("indexName".$i);
				if(isset($chk))
				{
					$nameIndx = $indx[$i];
					$singleUpdate = true;
				}
			}
			
			if($singleUpdate == true)
			{
				$fr = $this->_request->getParam("text_fr");
				$pt = $this->_request->getParam("text_pt");
				$en = $this->_request->getParam("text_en");
				$in = $this->_request->getParam("text_in");
				$indexAtrributes = array("fr" => $fr[$nameIndx], "pt" => $pt[$nameIndx], "en" => $en[$nameIndx], "in" => $in[$nameIndx]);
				foreach($arrayLang as $k => $val)
				$updateArray[$val] = $indexAtrributes[$val];
				$this->my_obj->updateIndexNode($fileName, $arrayName, $nameIndx, $updateArray);
			}
			
			if($this->_request->getParam("submit") == "Submit" && $singleUpdate == false)
			{
				//echo 'Hello';
				$arrayIndex = '';
				$simple = $this->_request->getParam("simple");
				if($simple == 'simple')
				{
					$fr = $this->_request->getParam("t_fr");
					$pt = $this->_request->getParam("t_pt");
					$en = $this->_request->getParam("t_en");
					$in = $this->_request->getParam("t_in");
					$indexAtrributes = array("fr" => $fr, "pt" => $pt, "en" => $en, "in" => $in);
					foreach($arrayLang as $k => $val)
					$updateArray[$val] = $indexAtrributes[$val];
					$this->my_obj->updateIndexNode2($fileName, $arrayName, $updateArray);
				}
				else
				{
					$traceIndexArr = $this->_request->getParam("traceIndexArr");
					$newEntry = $this->_request->getParam("NewEntry");
					$temp = $this->_request->getParam("trace");
					
					$fr = $this->_request->getParam("text_fr");
					$key_fr = $this->_request->getParam("key_fr");
					$pt = $this->_request->getParam("text_pt");
					$key_pt = $this->_request->getParam("key_pt");
					$en = $this->_request->getParam("text_en");
					$key_en = $this->_request->getParam("key_en");
					$in = $this->_request->getParam("text_in");
					$key_in = $this->_request->getParam("key_in");
					
					$this->my_obj->updateEachIndexNode($fileName, $arrayName, $fr, "fr");
					$this->my_obj->updateEachIndexNode($fileName, $arrayName, $pt, "pt");
					$this->my_obj->updateEachIndexNode($fileName, $arrayName, $en, "en");
					$this->my_obj->updateEachIndexNode($fileName, $arrayName, $in, "in");
				}
			}
			
			if($this->_request->getParam("validate") == "Insert" & $singleUpdate == false)
			{
				$index = str_replace(" ","_",$this->_request->getParam("neword"));
				$indexLang = $this->_request->getParam("countryLang");
				$this->_view->lfr = '';
				$this->_view->lpt = '';
				$this->_view->len = '';
				$this->_view->lin = '';
				foreach($indexLang as $languag1)
				{
					if ($languag1 == "fr")
						$this->_view->lfr = 'fr';
					if ($languag1 == "pt")
						$this->_view->lpt = 'pt';
					if ($languag1 == "en")
						$this->_view->len = 'en';
					if ($languag1 == "in")
						$this->_view->lin = 'in';
				}
				$indexAtrributes = array("fr" => "", "pt" => "", "en" => "", "in" => "");				
				$this->_view->index = $index;
				$this->_view->find = $index;
				if(is_numeric(substr($index,0,1)) == "true" & is_numeric($index) == "true")
				{
					$this->_view->index = $index;
					$this->_view->find = $index;
				}
				$grandChildExists = $this->my_obj->checkIndexNodeExistence($fileName, $arrayName, $index, $this->_lang);
				if($grandChildExists == true)
				{
					//echo 'Node Exsits ';
				}
				else 
				{
					$this->my_obj->addIndexNodes($fileName, $arrayName, $index, $indexLang);
				}
				$this->_view->indexFind = true;
			}
			
			$data = $this->my_obj->getAllNodes();
			$this->_view->arrayv = $data;
			$this->_view->render($this->controller."_arrayindex");
		}
	}
	
	public function arrayindex3Action()
	{
		$this->_view->charset = "utf8";
		$this->adminLogin	= Zend_Registry::get ( 'adminLogin' );
		$this->_view->lfr = '';
		$this->_view->lpt = '';
		$this->_view->len = '';
		$this->_view->lin = '';		
		$this->_view->invisibleS = '';
		$this->_view->invisibleU = 'invisible';
		
		$fileName = $this->_request->getParam("fileName");
		$arrayName = $this->_request->getParam("arrayName");
		$countryLang = $this->_request->getParam("countryLang");
		
		$this->my_obj = new Ep_Db_ArrayDb2($this->xml_path."$arrayName.xml", "root");
		$data = $this->my_obj->getAllNodes();
		$result = $data->xpath('/root/' . $fileName . '/'.$this->_lang.'/' . $arrayName);
		$this->_view->resultF = $result;
		
		$count = 0;
		$str = array();
		foreach($result[0]->children() as $grand_gen)
		{
			$str[] = str_replace("element", "", $grand_gen[0]->getName());
		}
		
		if(!empty($str))
		{
			$count = max($str);
			$count++;
		}
		$this->_view->count = $count;
		unset($str);
		
		foreach($result[0]->children() as $grand_gen)
		{
			foreach($this->totalLang as $l)
			$resultChk[$l] = $data->xpath('/root/' . $fileName . '/' . $l . '/' . $arrayName . '/' . $grand_gen[0]->getName());
		}
		
		foreach($this->totalLang as $l)
		{
			$resultView[$l] = $data->xpath('/root/' . $fileName . '/' . $l . '/' . $arrayName);			
			$this->_view->eachlangfrView = $resultView[$l][0]['language'];
		}
		$this->_view->resultView = $resultView;
		$this->_view->typefrView = $resultView[$this->_lang][0]['type'];
		$this->_view->langfrView = $resultView[$this->_lang][0]['language'];
		
		$editable = $result[0]['editable'];
		$type = $result[0]['type'];
		$index = $result[0]['index'];
		$language1 = $result[0]['language'];
		$desc = $result[0]['desc'];
		$action1 = $this->_request->getParam("action1");
		$indexNo = $this->_request->getParam("indexNo");
		
		//assign values to view
		$this->_view->indexNo = '';
		$this->_view->filename = $fileName;
		$this->_view->vArrayName = $arrayName;
		$this->_view->indexStatus = $index;
		$this->_view->type = $type;
		$this->_view->editable = $editable;
		if($indexNo == 'indexNo')
		$this->_view->indexNo = 'invisible';
		$arrayLang = split('\|', $language1);
		
		//for view purpose - it will decide which language should be checkmarked 
		foreach ($arrayLang as $languag1)
		{
			if ($languag1 == "fr")
				$this->_view->lfr = 'fr';
			if ($languag1 == "pt")
				$this->_view->lpt = 'pt';
			if ($languag1 == "en")
				$this->_view->len = 'en';
			if ($languag1 == "in")
				$this->_view->lin = 'in';
		}
		$indexFind = '';
		$this->_view->index = $indexFind;
		if (!$this->_request->isPost())
		{
			if ($action1 == "delete")
			{
				$arrayIndex = $this->_request->getParam("indexName");
				if($index == 'Yes')
				$this->my_obj->deleteIndexNode($fileName, $arrayName, $arrayIndex, $arrayLang);
				else
				{
					//$this->my_obj->deleteIndexNode($fileName, $arrayName, $arrayIndex, $arrayLang);
					$this->my_obj->deleteIndexNodeForIndexNo($fileName, $arrayName, $arrayIndex, $arrayLang);
				}
				echo '<META HTTP-EQUIV=Refresh CONTENT="0; URL=arrayindex3?fileName=' . $fileName . '&arrayName=' . $arrayName . '">';
			}
			else 
			{
				$this->_view->arrayv = $data;
				echo $this->_view->render($this->controller."_arrayindex3");
			}
		}
		
		if($this->_request->isPost())
		{
			$search1 = $this->_request->getParam("btnFind");
			$search2 = $this->_request->getParam("searchVar");
			$search3 = $this->_request->getParam("NewEntry"); 
			
			if($search1 == "Search" || $search2 == "search" || $search3 == "NewEntry")
			{
				$find = $this->_request->getParam("find");
				//echo 'Find '.$find;	
				$this->_view->find = $find;
				$this->_view->indexFind = true;
			}
			
			$j = $this->_request->getParam("j");
			$indx = $this->_request->getParam("indx");
			
			$singleUpdate = false;
			for($i=0;$i<15;$i++)
			{
				$chk = $this->_request->getParam("indexName".$i);
				if(isset($chk))
				{
					$nameIndx = $indx[$i];
					$singleUpdate = true;
				}
			}
			
			if($singleUpdate == true)
			{
				$fr = $this->_request->getParam("text_fr");
				$pt = $this->_request->getParam("text_pt");
				$en = $this->_request->getParam("text_en");
				$in = $this->_request->getParam("text_in");
				$indexAtrributes = array("fr" => $fr[$nameIndx], "pt" => $pt[$nameIndx], "en" => $en[$nameIndx], "in" => $in[$nameIndx]);
				foreach($arrayLang as $k => $val)
				$updateArray[$val] = $indexAtrributes[$val];
				/*print_r($updateArray);
				echo '<br/> - '.$fileName.'-'.$arrayName.'-'.$nameIndx;*/
				$this->my_obj->updateIndexNode($fileName, $arrayName, $nameIndx, $updateArray);
			}
			
			
			if($this->_request->getParam("submit") == "Submit" & $singleUpdate == false)
			{
				$arrayIndex = '';
				$simple = $this->_request->getParam("simple");
				if($simple == 'simple')
				{
					$fr = $this->_request->getParam("t_fr");
					$pt = $this->_request->getParam("t_pt");
					$en = $this->_request->getParam("t_en");
					$in = $this->_request->getParam("t_in");
					$indexAtrributes = array("fr" => $fr, "pt" => $pt, "en" => $en, "in" => $in);
					foreach($arrayLang as $k => $val)
					$updateArray[$val] = $indexAtrributes[$val];
					$this->my_obj->updateIndexNode2($fileName, $arrayName, $updateArray);
				}
				else 
				{
					$traceIndexArr = $this->_request->getParam("traceIndexArr");
					$newEntry = $this->_request->getParam("NewEntry");
					$temp = $this->_request->getParam("trace");
					
					$fr = $this->_request->getParam("text_fr");
					$key_fr = $this->_request->getParam("key_fr");
					$pt = $this->_request->getParam("text_pt");
					$key_pt = $this->_request->getParam("key_pt");
					$en = $this->_request->getParam("text_en");
					$key_en = $this->_request->getParam("key_en");
					$in = $this->_request->getParam("text_in");
					$key_in = $this->_request->getParam("key_in");
					
					$this->my_obj->updateEachIndexNode($fileName, $arrayName, $fr, "fr");
					$this->my_obj->updateEachIndexNode($fileName, $arrayName, $pt, "pt");
					$this->my_obj->updateEachIndexNode($fileName, $arrayName, $en, "en");
					$this->my_obj->updateEachIndexNode($fileName, $arrayName, $in, "in");
				}
			}
			
			if($this->_request->getParam("validate") == "Insert" & $singleUpdate == false)
			{
				$index = str_replace(" ","_",$this->_request->getParam("neword"));
				$indexLang = $this->_request->getParam("countryLang");
				$this->_view->lfr = '';
				$this->_view->lpt = '';
				$this->_view->len = '';
				$this->_view->lin = '';
				foreach($indexLang as $languag1)
				{
					if ($languag1 == "fr")
						$this->_view->lfr = 'fr';
					if ($languag1 == "pt")
						$this->_view->lpt = 'pt';
					if ($languag1 == "en")
						$this->_view->len = 'en';
					if ($languag1 == "in")
						$this->_view->lin = 'in';
				}
				$indexAtrributes = array("fr" => "", "pt" => "", "en" => "", "in" => "");				
				$this->_view->index = $index;
				$this->_view->find = $index;
				if(is_numeric(substr($index,0,1)) == "true" & is_numeric($index) == "true")
				{
					$this->_view->index = $index;
					$this->_view->find = $index;
				}
				$grandChildExists = $this->my_obj->checkIndexNodeExistence($fileName, $arrayName, $index, $this->_lang);
				if($grandChildExists == true)
				{
					//echo 'Node Exsits ';
				}
				else 
				{
					$this->my_obj->addIndexNodes($fileName, $arrayName, $index, $indexLang);
				}
				$this->_view->indexFind = true;
			}
			$data = $this->my_obj->getAllNodes();
			$this->_view->arrayv = $data;
			echo $this->_view->render($this->controller."_arrayindex3");
		}
	}
	
	public function unlockAction()
	{
		$docId = $this->_request->getParam('docId');
		require_once ROOT_PATH1 . 'library/tools/doclock/Resource.php';
		require_once ROOT_PATH1 . 'library/tools/doclock/ObouloResource.php';
		require_once ROOT_PATH1 . 'library/tools/doclock/Lock.php';
		if ($this->_lang == 'fr')
			$this->dlock = new Lock($this->_config->path->web . "Admin/data/");
		if ($this->_lang != 'fr')
			$this->dlock = new Lock($this->_config->path->web . "/Admin/Lock/data/");
		$this->dlock->setdocId($this->_view->docid);
		
		if($docId != "")
		{
			$this->dlock->setdocId($docId);
			$this->dlock->delete();
		}
	}
	
	public function searchwordAction()
	{
		$this->_view->charset = "utf8";
		$this->render("docobo_searchword");
	}
	
	// master of all search - it will search anything in the website and gives the data to update or modify it. 
	public function searchAction()
	{	
		$this->_view->charset = "utf8";
		$find = stripslashes($this->_request->getParam("find"));
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
		$this->render("docobo_search");
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
}