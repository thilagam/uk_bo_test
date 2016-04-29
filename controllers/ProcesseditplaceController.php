<?php
/**
 * ProcessdacoController - The default controller class
 *
 * @author farid
 * @version 1.0
 */

class ProcesseditplaceController extends Ep_Controller_Action
{
	/**
	 * User session
	 *
	 * @var Zend_Session_Namespace
	 */
	protected $session = null;
	private $text_admin;

	//local variables for translation actions
	private $my_obj;
	private $filesname;
    private $entryLang;
    private $cntry;
    private $totalLang;
    private $my_view;
	//end
	private $controller = "processeditplace";

	/**
	 * Overriding the init method to also load the session from the registry
	 *
	 */
	public function init()
	{
		parent::init();
		$this->session = Zend_Registry::get('session');
		$this->_view->lang = $this->_lang;
		$this->adminLogin	= Zend_Registry::get ( 'adminLogin' );
		$this->_view->loginName = $this->adminLogin->loginName;

		$this->text_admin =  $this->_arrayDb->loadArrayv2("text_admin", $this->_lang);
		$this->_view->ADMINISTRATION_INTERFACE = $this->text_admin['ADMINISTRATION_INTERFACE'];
		$this->_view->CORRECTOR = $this->text_admin['CORRECTOR'];
		$this->_view->Disconnect = $this->text_admin['Disconnect'];
		$this->_view->Login = $this->text_admin['Login'];
		$this->_view->Password = $this->text_admin['Password'];
	    $this->_view->Country = $this->text_admin['Country'];

	    $this->cntry = Zend_Registry::get('_country');
		$this->_view->cntry = $this->cntry;
		$this->totalLang = Zend_Registry::get('_totalLang');
		$this->_view->allLang = $this->totalLang;
		$this->my_obj = new Ep_Db_ArrayDb2(DATA_PATH."main_meta_data.xml", "root");
		$this->adminLogin	= Zend_Registry::get ( 'adminLogin' );
		$this->_view->loginName = $this->adminLogin->loginName;
		$this->_view->permission = $this->adminLogin->permission;
		$this->_view->siteName = $_SERVER['HTTP_HOST'];
		$this->_view->controller = $this->_request->getParam('controller');
		$this->versionFile = DATA_PATH."version.xml";
		 ///////////////////////////////////////////////////////////////////////
                if($this->mainMenu->menuId == "")
                {
                    $this->mainMenu = Zend_Registry::get('mainMenu');
                    $this->mainMenu->menuId = $this->_request->getParam('menuId');
				    $this->_view->menuId = $this->mainMenu->menuId;
                }
                $this->mainMenu->submenuId = $this->_request->getParam('submenu');
	            $this->_view->submenuId = $this->mainMenu->submenuId;

		//////////////////////////////////////////////////////////////////////
		$this->patternFile = DATA_PATH.'pattern.xml';
		$this->moduleFile = DATA_PATH.'module.xml';
	}

	public function checkpageexistenceAction()
	{
		$p = new Ep_Controller_Page($this->pageFile);
		$pageName = $this->_request->getParam('pageName');
		if ($p->checkpageexistence($pageName))
		echo 'This page name already exists, please enter another name';
		return false;
	}

	public function checkpatternexistenceAction()
	{
		$p = new Ep_Controller_Pattern($this->patternFile);
		$patternName = $this->_request->getParam('patternName');
		if ($p->checkpatternexistence($patternName))
		echo 'This pattern name already exists, please enter another name';
		return false;
	}

	/**
	 * Page action controller
	 *
	 */
	public function pageAction()
	{
		$sectionList = $this->_arrayDb->loadArrayv2("sectionList2", $this->_lang);
        $sectionList[] = "none";
		krsort($sectionList);
		//echo "path ".$this->patternFile;
        $this->_view->controllerName = $this->_request->getParam("controller");
        $this->_view->domainSel = $this->_request->getParam("domainSel");

		$this->_view->sectionList = $sectionList;
		$this->_view->lastsection = count($sectionList);
		$do = $this->_request->getParam("do");
		$page = trim($this->_request->getParam("page"));
		$page = str_replace(" ", "_", $page);
		$segment = $this->_request->getParam("segment");
		$update_check = $this->_request->getParam("update_check");
		$update = false;
		$p = new Ep_Controller_Page($this->pageFile);
		if ($do == "update")
		{
			$p->getNodeMap($page);
			$this->_view->update_check = 1;
		}

		if ($do == "delete")
			$p->deleteNode($page);

		if($do == "add")
		{
			$Patterns = $this->_request->getParam("selectPattern");
			$selectedPattns = '';
			foreach($Patterns as $patt)
			$selectedPattns .= $patt ."|";
			$patternList = substr($selectedPattns,0,-1);
			$patten = $patternList;

			$p->setName($page);
			$p->getNodeMap($page);
			$p->setDescription($this->_request->getParam("description"));
			$p->setPattern($patten);
			$p->setSegment($this->_request->getParam("segment"));
			$p->setmetaDescription($this->_request->getParam("metaDescription"));
			$p->setmetaKeywords($this->_request->getParam("metaKeywords"));
			$p->setmetaTitle($this->_request->getParam("metaTitle"));
			$p->setaccessCode($this->_request->getParam("accessCode"));
			$section = $this->_request->getParam("section");
			$processCode = $this->_request->getParam("processCode");
			//echo 'Section '.$section.'-'.$section.'-'.$processCode;
			if($section == (count($sectionList)))
			$p->setsection("");
			else
			$p->setsection($section);

			$p->setprocessCode($processCode);

			$ptest = new Ep_Controller_Page($this->pageFile);
			if(!$ptest->getNodeMap($page))
			{
				$p->insert();
				$addtrue = 1;
				$this->_view->fail_update = "The page - $page added";
			}
			else
			{
				if($update_check == 1)
				echo $p->update();
				else
				{
					if($addtrue != 1)
					$this->_view->fail_update = "The page - $page already exists";
				}
			}
			$update = true;
		}
		//meta tag
		$this->_view->title = "page details";
		$this->_view->keywords = "page details";
		$this->_view->description = "page details";

		//selected page
		$this->_view->selectedPage = $p;

		//pattern list
		$pa = new Ep_Controller_Pattern($this->patternFile);
		$this->_view->patternList = $pa->getAllPattern();

		//page list
		$p = new Ep_Controller_Page($this->pageFile);
		if (! $segment)
			$segment = "0";
		$pageList = $p->selectAllPagesBySegment($segment);
        $this->_view->pageList = $pageList;

		//segment list
		$this->_view->segmentArrayName =  "editplace_segmentList";
		$this->_view->segmentList =  $this->_arrayDb->loadArrayv2("editplace_segmentList", $this->_lang);
		$this->_view->segment = $segment;
		if($update == true)
			echo '<META HTTP-EQUIV=Refresh CONTENT="0; URL=page?segment=' . $segment . '">';
		$this->render($this->controller . "_page");
	}

	/**
	 * Module action controller
	 *
	 */
	public function moduleAction()
	{
		try
		{
			//country to be passed
			$Date = new Date("", $this->_lang);
			$today = $Date->getDateArray();
			$presentDate = $Date->getDateFormatedforDiff();
			$presentDate .= ' ' . $Date->getTimeFormatedEN();

			//default check mark
			$this->_view->dfr = "selected";

			$p = new Ep_Controller_Page($this->pageFile);
			$ac = $p->getPageAccesscode("loginAdmin");

			$mod = trim($this->_request->getParam("mod"));
			$p->getNodeMap($mod);
			$newMod = trim($this->_request->getParam("moduleName"));

			//$arrayPosition = array(0 => "main", 1 => "header", 2 => "footer", 3 => "right");
			$arrayPosition = $this->_arrayDb->loadArrayv2("modulePosition", $this->_lang);
			$countryList = array("fr", "en", "pt", "in");

			//$accessList = array("public", "private", "Admin");
			$accessList = $this->_arrayDb->loadArrayv2("accessList", $this->_lang);
			if ($mod == "")
				throw new Exception();

			//meta tag
			$this->_view->title = "module details";
			$this->_view->keywords = "module details";
			$this->_view->description = "module details";

			$this->_view->newENTRY = "";
			$this->_view->addDisplay = "display: none;";
			$this->_view->searchDisplay = "display: none;";
			$this->_view->enteredModule = $newMod;

			$done2 = false;
			$do = $this->_request->getParam("do");
			$page = trim($this->_request->getParam("page"));
			$this->_view->message = "";
			$m = new Ep_Controller_Module($this->moduleFile);

			if ($do == "update")
			{
				$this->_view->addDisplay = "display: block;";
				$this->_view->newENTRY = "No";
				$m->getNodeMap($page);
			}

			// to add module to a page $pageName
			if ($do == "AddToPage")
			{
				$p2 = new Ep_Controller_Page($this->pageFile);
				$enteredModule = trim($this->_request->getParam("enteredModule"));
				$pageName = $p2->getPageNameByModule($enteredModule);
				$p2->getNodeMap($mod);
				$exists = $p2->checkModuleExistence($enteredModule);
				if ($exists == true)
					$this->_view->message = "This module exists already in this page"; else
				{
					$p2->addModule($enteredModule);
					echo '<META HTTP-EQUIV=Refresh CONTENT="0; URL=module?mod=' . $mod . '">';
					$this->_view->message = "you have added this module to page....check above";
				}
			}

			if ($do == "check")
			{
				$check = $m->getNode($newMod);
				if ($check == null)
				{
					$this->_view->searchDisplay = "display: none;";
					$this->_view->addDisplay = "display: block;";
					$this->_view->message = "Module Name ' " . $newMod . " ' does not Exists ...So please enter below details to create";
				}
				else
				{
					$pp = new Ep_Controller_Page($this->pageFile);
					$mm = new Ep_Controller_Module($this->moduleFile);
					$mArray = $mm->getAllNodesMap();
					$pageName = $pp->getAllPageNameByModule($newMod);
					$pp->getNodeMap($pageName[0]);
					$this->_view->spage = $pageName;

					if (empty($pageName))
					{
						foreach ($mArray as $mf)
						{
							$modName = $mf->getNodeName();
							if ($modName == $newMod)
							{
								$this->_view->sdescription = $mf->getNodeValue();
								$this->_view->sfile = $mf->getFile();
								$this->_view->srank = $mf->getRank();
								$this->_view->sonline = $mf->getOnline();
								$this->_view->slanguage = $mf->getLanguage();
								$position = $mf->getPosition();
								$this->_view->sposition = $arrayPosition[$position];
								$access = $mf->getAccess();
								$this->_view->saccess = $accessList[$access];
								$this->_view->sorder = $mf->getOrder();
							}
						}
						$this->_view->spage = array("null(no page)");
					}
					else
					{
						$gotDetails = $pp->getAllModule();
						foreach ($gotDetails as $mf)
						{
							$modName = $mf->getNodeName();
							if ($modName == $newMod)
							{
								$this->_view->sdescription = $mf->getNodeValue();
								$this->_view->sfile = $mf->getFile();
								$this->_view->srank = $mf->getRank();
								$this->_view->sonline = $mf->getOnline();
								$this->_view->slanguage = $mf->getLanguage();
								$position = $mf->getPosition();
								$this->_view->sposition = $arrayPosition[$position];
								$access = $mf->getAccess();
								$this->_view->saccess = $accessList[$access];
								$this->_view->sorder = $mf->getOrder();
							}
						}
					}
					$this->_view->message = "Module Name ' " . $newMod . " ' already Exists ...See below its details";
					$this->_view->addDisplay = "display: none;";
					$this->_view->searchDisplay = "display: block;";
				}
			}

			if($do == "delete")
			{
				$p->deleteModule($page);
			}

			if ($do == "add")
			{
				$desc = $this->_request->getParam("description");
				$file = $this->_request->getParam("file");
				$rank = $this->_request->getParam("rank");
				$online = $this->_request->getParam("online");
				$Langs = $this->_request->getParam("countryLang");
				$sites = $this->_request->getParam("site");

				$selectedSites = '';
				foreach($sites as $site)
				$selectedSites .= $site ."|";
				$sitesList = substr($selectedSites,0,-1);

				$selectedLangs = '';
				foreach($Langs as $lang)
				$selectedLangs .= $lang ."|";
				$countryList = substr($selectedLangs,0,-1);
				$country = $countryList;

				$pos = $this->_request->getParam("position");
				$acces = $this->_request->getParam("access");
				$order = $this->_request->getParam("order");

				$m->setNodeName($page);
				$m->setNodeValue($desc);
				$m->setFile($file);
				$m->setRank($rank);
				$m->setOnline($online);
				$m->setLanguage($country);
				$m->setPosition($pos);
				$m->setAccess($acces);
				$m->setOrder($order);
				$m->setmodDate($presentDate);
				$m->setSite($sitesList);

				$mtest = new Ep_Controller_Module($this->moduleFile);

				if (! $mtest->getNodeMap($page))
				{
					$p->addModule($m->getNodeName());
					$m->insert();
					$m1 = new Ep_Controller_Module($this->versionFile, "versions");
					$m1->searchActiveforVersion($page);
					$m1->setNodeName($page . 'vzn');
					$m1->setNodeValue($desc);
					$m1->setFile($file);
					$m1->setRank($rank);
					$m1->setOnline($online);
					$m1->setLanguage($country);
					$m1->setPosition($pos);
					$m1->setAccess($acces);
					$m1->setOrder($order);
					$m1->setmodDate($presentDate);
					$m1->setdaysBetDate('0');
					$m1->setactive('1');
					$m1->insert();
					$done2 = true;
				} else
				{
					echo $m->update();
					$hmodDate = $this->_request->getParam("hmodDate");
					//keep track of module versioning details
					$mVersion = new Ep_Controller_Module($this->versionFile, "versions");
					$i = 0;
					$page = $page . 'vzn';
					$temp = $page;
					$done = false;
					while ( $done == false )
					{
						if (! $mVersion->getNodeMap($temp))
						{
							$mVersion->searchActiveforVersion($temp);
							$mVersion->setNodeName($temp);
							$mVersion->setNodeValue($desc);
							$mVersion->setFile($file);
							$mVersion->setRank($rank);
							$mVersion->setOnline($online);
							$mVersion->setLanguage($country);
							$mVersion->setPosition($pos);
							$mVersion->setAccess($acces);
							$mVersion->setOrder($order);
							$mVersion->setmodDate($presentDate);
							$days = $mVersion->count_days(strtotime($hmodDate), strtotime($presentDate));
							$mVersion->setdaysBetDate($days);
							$mVersion->setactive('1');
							$mVersion->insert();
							$done = true;
							$done2 = true;
						} else
						{
							$i = $i + 1;
							$temp = $page . $i;
						}
					}
					unset($mVersion);
				}
			}

			if ($do == "deleteMod")
			{
				$p->deleteModule($page);
			}

			//selected module
			$this->_view->selectedModule = $m;
			$this->_view->mList = $p->getAllModule();
			$this->_view->pageName = $mod;

			//data
			$this->_view->arrayPosition = $arrayPosition;
			$this->_view->countryList = $countryList;
			$this->_view->accessList = $accessList;
			//$this->_view->siteList = $this->_arrayDb->loadArrayv2("daco_domainName", $this->_lang);

			if ($done2 == true)
				echo '<META HTTP-EQUIV=Refresh CONTENT="0; URL=module?mod=' . $mod . '">'; else
				$this->render($this->controller . '_module');
		}

		catch (Exception $e)
		{
			echo "<strong>You must give a correct module name</strong>";
		}
	}

	/**
	 * Pattern action controller
	 *
	 */
	public function patternAction()
	{
		try
		{
			if ($this->_request->getParam("pat") == "")
				throw new Exception();
			$pat = trim($this->_request->getParam("pat"));
			$page = $this->_request->getParam("page");

			$p = new Ep_Controller_Pattern($this->patternFile);
			$p->getNodeMap($pat);

			$do = $this->_request->getParam("do");
			$page = $this->_request->getParam("page");

			$m = new Ep_Controller_Module($this->moduleFile);

			if ($do == "add")
			{
				$sites = $this->_request->getParam("site");
				$selectedSites = '';
				if(is_array($sites))
				foreach($sites as $site)
				$selectedSites .= $site ."|";
				$sitesList = substr($selectedSites,0,-1);

				$p->getNodeMap($page);
				$p->setNodeName($page);
				$p->setNodevalue($this->_request->getParam("description"));
				$p->setSkeleton($this->_request->getParam("skeleton"));
				$p->setModule(explode("|", $this->_request->getParam("modul")));
				$p->setCss(explode("|", $this->_request->getParam("css")));
				$p->setSite($sitesList);
				$p->setJavascript(explode("|", $this->_request->getParam("javascript")));

				$ptest = new Ep_Controller_Pattern($this->patternFile);
				if ($ptest->getNodeMap($page))
					$p->update(); else
					$p->insert();
			}
			//meta values
			$this->_view->meta_title = "pattern details";
			$this->_view->meta_keywords = "pattern details";
			$this->_view->meta_description = "pattern details";
			$this->_view->selectedPattern = $p;
			//$this->_view->siteList = $this->_arrayDb->loadArrayv2("daco_domainName", $this->_lang);
			$this->render($this->controller . '_pattern');

		} catch (Exception $e)
		{
			echo "<strong>You must give a correct module name</strong>";
		}
	}

    public function searchpageAction()
    {
        $formname = $this->_request->getParam('formname');
        $pagename = $this->_request->getParam('pagename');
        $segmentList =  $this->_arrayDb->loadArrayv2("editplace_segmentList", $this->_lang);
        //print_r($segmentList);
        $p = new Ep_Controller_Page();
        $pageVal = $p->searchpage($pagename);
        //echo $pageVal;
        echo $segmentList["$pageVal"];
    }
}
