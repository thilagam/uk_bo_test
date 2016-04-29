<?php
require_once 'Zend/Controller/Action.php';
class AoController extends Ep_Controller_Action
{
	public function init() 
	{
        parent::init();
        $this->_view->lang = $this->_lang;
        $this->adminLogin  = Zend_Registry::get('adminLogin');
        $this->sid         = session_id();
        $this->configval=$this->getConfiguredval(); 		
        
        $categories_array  = $this->_arrayDb->loadArrayv2("EP_ARTICLE_CATEGORY", $this->_lang);
        $sign_type_array   = $this->_arrayDb->loadArrayv2("EP_ARTICLE_SIGN_TYPE", $this->_lang);
        $type_array = $this->_arrayDb->loadArrayv2("EP_ARTICLE_TYPE", $this->_lang);
        $ep_lang_array = $this->_arrayDb->loadArrayv2("EP_LANGUAGES", $this->_lang);
        $categories_array  = $this->_arrayDb->loadArrayv2("EP_ARTICLE_CATEGORY", $this->_lang);
        $prof = $this->_arrayDb->loadArrayv2("CONTRIB_PROFESSION", $this->_lang);
    }
    public function createAction()
	{
		$this->_redirect("/ao/ao-create1?submenuId=ML2-SL3");
	}
	/*** AO creation Step1 ***/
	/**** Delivery level fields are filled in this step***/
    public function aoCreate1Action()
    { 
		//multiple upload
		/*require_once "/home/sites/site14/web/BO/theme/gebo/lib/phpuploader/include_phpuploader.php";
		$uploader=new PhpUploader();
		$uploader->MultipleFilesUpload=true;
		$uploader->InsertText="Spec file upload";
		$uploader->MaxSizeKB=1024000;	
		$uploader->AllowedFileExtensions="doc,docx,xls,xlsx,zip";
		$this->_view->uploadtext = $uploader->Render(); */
		
		$this->AO_creation = Zend_Registry::get('AO_creation');
		
		
		//Clear sessions on click of 'Annuler champs'
		if($_GET['delsession']==1)
		{
			unset($this->AO_creation->ao_step1);
			unset($this->AO_creation->ao_step2);
			unset($this->AO_creation->ao_step3);
		}	
		
		//Navigation on top right
		if(isset($this->AO_creation->ao_step1['title']))
			$this->_view->nav_1=1;
		if(isset($this->AO_creation->ao_step2))
			$this->_view->nav_2=1;
		if(isset($this->AO_creation->ao_step3))
			$this->_view->nav_3=1;
			
		$ao_obj=new Ep_Delivery_Options();
		$del_obj = new Ep_Delivery_Delivery();
		$contrib_obj = new Ep_User_Contributor();
		$client_obj = new Ep_User_Client();
		
		//Save company name from 'UPDATER PROFIL CLIENT'
		if($_REQUEST['profilesave']!="")
		{
			$client_obj->updateCompany($_REQUEST['company_name'],$_REQUEST['client']);
			$this->_view->def_user=$_REQUEST['client'];
		}
	
        //Premium options for Mission premiums
        $AllOptions=$ao_obj->getParentOptionsAO();
        for($o=0;$o<count($AllOptions);$o++)
        {
            $AllOptions[$o]['description1']=$AllOptions[$o]['description']."<br><br><p align=center><b>Price premium option:&nbsp;".$AllOptions[$o]['option_price_bo']."&euro; per article selected</b></p>";
            $child=$ao_obj->getChildOptionsAO($AllOptions[$o]['id']);
            if(count($child)>0)
			{
                for($op=0;$op<count($child);$op++)
                {
                    $child[$op]['description']=$child[$op]['description']."<br><br><p align=center><b>Price premium option:&nbsp;".$child[$op]['option_price_bo']."&euro; per article selected</b></p>";
                }
                $AllOptions[$o]['childlist']=$child;
            }
        }
        $this->_view->options=$AllOptions;
        $this->_view->optioncount=count($AllOptions);
    	$this->_view->prem_ser=array();
       
		//Private contributors
		$ep_lang_array = $this->_arrayDb->loadArrayv2("EP_LANGUAGES", $this->_lang);
        $categories_array  = $this->_arrayDb->loadArrayv2("EP_ARTICLE_CATEGORY", $this->_lang);
		$contrib_lang_array=$ep_lang_array; 
		$this->_view->Contrib_langs = $contrib_lang_array;
		$contrib_cat_array=array_merge(array("all"=>"All"),$categories_array); 
		$this->_view->Contrib_cats = $contrib_cat_array;
		//$contrib_lang_array=array_merge(array("all"=>"All"),$ep_lang_array); 
		//$this->_view->Contrib_langs = $contrib_lang_array;
	
		//Private contribs
		$contriblistall=$del_obj->getAllContribAO(0);
		$this->_view->contrib_array=array();
		$contriblistall1=array();
			for ($i=0;$i<count($contriblistall);$i++)
			{
				$contriblistall1[]=$contriblistall[$i];
				$name=$contriblistall1[$i]['email'];
				$nameArr=array($contriblistall1[$i]['first_name'],$contriblistall1[$i]['last_name']);
				$nameArr=array_filter($nameArr);
				if(count($nameArr)>0)
					$name.=" (".implode(", ",$nameArr).")";
				$contriblistall1[$i]['name']=strtoupper($name);
			}
		$this->_view->contriblistall1=$contriblistall1;
	
		//Private Correctors for Correction block
		$correcterall=$del_obj->getAllCorrectors(0);
		$correcterall1=array();
			for ($i=0;$i<count($correcterall);$i++)
			{
				$correcterall1[]=$correcterall[$i];
				$name=$correcterall1[$i]['email'];
				$nameArr=array($correcterall1[$i]['first_name'],$correcterall1[$i]['last_name']);
				$nameArr=array_filter($nameArr);
				if(count($nameArr)>0)
					$name.=" (".implode(", ",$nameArr).")";
				$correcterall1[$i]['name']=strtoupper($name);
			}
		$this->_view->correctorlist=$correcterall1;
		$this->_view->corrector_array=array();
		if($_GET['clnt']!="")
			$this->_view->def_user = $_GET['clnt'];
			
		/** If, when step1 sessions are set while coming back from other steps to step1 or in case of duplicate missions or loading templates
		Otherwise Else **/	
		if($this->_view->nav_1==1)
		{
			$this->_view->missiontest=$this->AO_creation->ao_step1['missiontest'];
			$this->_view->title=htmlentities($this->AO_creation->ao_step1['title']);
			$this->_view->def_user=$this->AO_creation->ao_step1['client_list'];
			$this->_view->deli_anonymous=$this->AO_creation->ao_step1['deli_anonymous'];
			$this->_view->language=$this->AO_creation->ao_step1['language'];
			$this->_view->type=$this->AO_creation->ao_step1['type'];
			$this->_view->category=$this->AO_creation->ao_step1['category'];
			$this->_view->signtype=$this->AO_creation->ao_step1['signtype'];
			$this->_view->min_sign=$this->AO_creation->ao_step1['min_sign'];
			$this->_view->max_sign=$this->AO_creation->ao_step1['max_sign'];
			
			$this->_view->urlsexcluded=$this->AO_creation->ao_step1['urlsexcluded'];
			$this->_view->mission_type=$this->AO_creation->ao_step1['mission_type'];
			$this->_view->premium_option=$this->AO_creation->ao_step1['premium_option'];
			if($this->AO_creation->ao_step1['mission_type']=="liberte")
				$this->_view->TotPrem='0';
			else
				$this->_view->TotPrem=$this->AO_creation->ao_step1['TotPrem'];
			$this->_view->price_min=$this->AO_creation->ao_step1['price_min'];
			$this->_view->price_max=$this->AO_creation->ao_step1['price_max'];
			$this->_view->price_min_total=$this->AO_creation->ao_step1['price_min_total'];
			$this->_view->price_max_total=$this->AO_creation->ao_step1['price_max_total'];
			$this->_view->currency=$this->AO_creation->ao_step1['currency'];
			$this->_view->pricedisplay=$this->AO_creation->ao_step1['pricedisplay'];
			
			$this->_view->total_article=$this->AO_creation->ao_step1['total_article'];
			$this->_view->prem_ser=$this->AO_creation->ao_step1['premium_service'];
			$this->_view->AOtype=$this->AO_creation->ao_step1['AOtype'];
			$this->_view->writer_notify=$this->AO_creation->ao_step1['writer_notify'];
			
			$this->_view->contrib_percentage=$this->AO_creation->ao_step1['contrib_percentage'];
			$this->_view->junior_time=$this->AO_creation->ao_step1['junior_time'];
			$this->_view->senior_time=$this->AO_creation->ao_step1['senior_time'];
			$this->_view->subjunior_time=$this->AO_creation->ao_step1['subjunior_time'];
			$this->_view->submit_option=$this->AO_creation->ao_step1['submit_option'];
			$this->_view->jc_resubmission=$this->AO_creation->ao_step1['jc_resubmission'];
			$this->_view->sc_resubmission=$this->AO_creation->ao_step1['sc_resubmission'];
			$this->_view->jc0_resubmission=$this->AO_creation->ao_step1['jc0_resubmission'];
			$this->_view->resubmit_option=$this->AO_creation->ao_step1['resubmit_option'];
			$this->_view->participation_time=$this->AO_creation->ao_step1['participation_time'];
			//Poll related
			$this->_view->linkpoll=$this->AO_creation->ao_step1['linkpoll'];
			$this->_view->priority_hours=$this->AO_creation->ao_step1['priority_hours'];
			$this->_view->pollao=$this->AO_creation->ao_step1['pollao'];
			$this->_view->priorcontrib=$this->AO_creation->ao_step1['priorcontrib'];
			$this->_view->contribtype=$this->AO_creation->ao_step1['contribtype'];
			$this->_view->correction=$this->AO_creation->ao_step1['correction'];
			$this->_view->plagiarism_check=$this->AO_creation->ao_step1['plagiarism_check'];
			$this->_view->white_list=$this->AO_creation->ao_step1['white_list'];
			$this->_view->black_list=$this->AO_creation->ao_step1['black_list'];
			$this->_view->blwl_check=$this->AO_creation->ao_step1['blwl_check'];
			/*$this->_view->CPC=$this->AO_creation->ao_step1['CPC'];
			$this->_view->cpc_maxvisit=$this->AO_creation->ao_step1['cpc_maxvisit'];
			$this->_view->cpc_minvisit=$this->AO_creation->ao_step1['cpc_minvisit'];
			$this->_view->cpc_budget=$this->AO_creation->ao_step1['cpc_budget'];
			$this->_view->cpc_endate=$this->AO_creation->ao_step1['cpc_endate'];*/
			$this->_view->link_quiz=$this->AO_creation->ao_step1['link_quiz'];
			$this->_view->quiz=$this->AO_creation->ao_step1['quiz'];
			$this->_view->quiz_marks=$this->AO_creation->ao_step1['quiz_marks'];
			$this->_view->quiz_duration=$this->AO_creation->ao_step1['quiz_duration'];
			$this->_view->quiz_cat=$this->AO_creation->ao_step1['quiz_cat'];
			if($this->AO_creation->ao_step1['spec_file_name']!="")
			{
				$this->_view->sp_file=$this->AO_creation->ao_step1['spec_file_name'];
				$this->_view->sp_array=explode(",",$this->AO_creation->ao_step1['spec_file_name']);
			}
			if($this->_view->AOtype=='private')
			{
				$this->_view->contrib_array=$this->AO_creation->ao_step1['favcontribcheck'];
				//$this->_view->contrib_lang=$this->AO_creation->ao_step1['contrib_lang'];
				$this->_view->contrib_langarray=$this->AO_creation->ao_step1['contrib_lang'];
				if(count($this->AO_creation->ao_step1['contrib_lang'])==0)
					$this->_view->contrib_langarray=array_keys($contrib_lang_array);
				$this->_view->contrib_cat=$this->AO_creation->ao_step1['contrib_cat'];	
			}
			else
			{
				$this->_view->contrib_langarray=array_keys($contrib_lang_array);
				$this->_view->publish_langarray=$this->AO_creation->ao_step1['publish_language'];
			}
			//Correction
			if($this->AO_creation->ao_step1['correction']=="on")
			{
				if($this->AO_creation->ao_step1['correction_spec_file']!="")
					$this->_view->correction_spec_file=$this->AO_creation->ao_step1['correction_spec_file'];
				
				$this->_view->correction_participation=$this->AO_creation->ao_step1['correction_participation'];
				$this->_view->correction_jc_submission=$this->AO_creation->ao_step1['correction_jc_submission'];
				$this->_view->correction_sc_submission=$this->AO_creation->ao_step1['correction_sc_submission'];
				$this->_view->correction_jc_resubmission=$this->AO_creation->ao_step1['correction_jc_resubmission'];
				$this->_view->correction_sc_resubmission=$this->AO_creation->ao_step1['correction_sc_resubmission'];
				$this->_view->correction_submit_option=$this->AO_creation->ao_step1['correction_submit_option'];
				$this->_view->correction_resubmit_option=$this->AO_creation->ao_step1['correction_resubmit_option'];
				$this->_view->correction_type=$this->AO_creation->ao_step1['correction_type'];
			
				if($this->AO_creation->ao_step1['correction_type']=="private")
				{
					$this->_view->corrector_array=$this->AO_creation->ao_step1['correctorcheck'];
					$this->_view->corrector_langarray=$this->AO_creation->ao_step1['corrector_lang'];
					if(count($this->AO_creation->ao_step1['corrector_lang'])==0)
						$this->_view->corrector_langarray=array_keys($contrib_lang_array);
				}
				else
					$this->AO_creation->ao_step1['correctorcheck']=array();
			
				$this->_view->corrector_mail=$this->AO_creation->ao_step1['corrector_mail'];	
				$this->_view->correction_pricemin=$this->AO_creation->ao_step1['correction_pricemin'];
				$this->_view->correction_pricemax=$this->AO_creation->ao_step1['correction_pricemax'];
				$this->_view->corrector=$this->AO_creation->ao_step1['corrector'];
				$this->_view->corrector_option=$this->AO_creation->ao_step1['corrector_option'];
				$this->_view->writer=$this->AO_creation->ao_step1['writer'];
				$this->_view->writer_option=$this->AO_creation->ao_step1['writer_option'];
				$this->_view->corrector_notify=$this->AO_creation->ao_step1['corrector_notify'];
				
				if($this->AO_creation->ao_step1['missiontest']=="on")
				{
					$this->_view->min_mark=$this->AO_creation->ao_step1['min_mark'];
					$this->_view->invoice=$this->AO_creation->ao_step1['invoice'];
					$this->_view->writerinvoice=$this->AO_creation->ao_step1['writerinvoice'];
				}
			}
			$this->_view->articletype=$this->AO_creation->ao_step1['articletype'];
			$this->_view->sub_article=$this->AO_creation->ao_step1['sub_article'];	
			
			$clientdata=$client_obj->getClientRecord($this->AO_creation->ao_step1['client_list']);
			if($clientdata[0]['contrib_percentage']!="")
				$contribper=$clientdata[0]['contrib_percentage'];
			else
				$contribper=$this->configval["nopremium_contribpercent"];
			$this->_view->eppercent=100-$contribper;
			$this->_view->column_xls=$this->AO_creation->ao_step1['column_xls'];
			$this->_view->testrequired=$this->AO_creation->ao_step1['testrequired'];
			$this->_view->testmarks=$this->AO_creation->ao_step1['testmarks'];
			$this->_view->nomoderation=$this->AO_creation->ao_step1["nomoderation"];
			
			//Refusal reasons
			$this->_view->product=$this->AO_creation->ao_step1["product"];
			if($this->AO_creation->ao_step1["product"]=="redaction")
				$this->_view->redactionrefusal=$this->AO_creation->ao_step1["redactionrefusal"]; 
			else
				$this->_view->translationrefusal=$this->AO_creation->ao_step1["translationrefusal"];
		}	
		else
		{
			//Default values for a fresh or unset page
			$this->_view->mission_type='';
			$this->_view->premium_option='13_1';
			$this->_view->deli_anonymous='on';
			$this->_view->TotPrem='1';
			$this->_view->participation_time=$this->configval["participation_time"];
			$this->_view->contrib_percentage='100';
			$this->_view->currency=$this->configval["currency"];
			$this->_view->correction_participation=$this->configval['correction_participation']/60;
			$this->_view->correction_type='public';
			$this->_view->correction_pricemin=0;
			$this->_view->correction_pricemax=0;
			$this->_view->corrector_mail='on';
			$this->_view->contrib_langarray=array();
			$this->_view->corrector_langarray=array();
			$this->_view->contribtype=array("0"=>"senior","1"=>"junior","2"=>"sub-junior"); 
			$this->_view->min_mark=1;
			$this->_view->eppercent=100-$this->configval["nopremium_contribpercent"];
			$this->_view->pricedisplay='yes';
			
			$this->_view->publish_langarray=array();
		}
		if($this->_view->prem_ser=="")
			$this->_view->prem_ser=array();
			
		//Writers count based on profile type for View To
		$this->_view->sc_count=$contrib_obj->getContributorcount('senior');
		$this->_view->jc_count=$contrib_obj->getContributorcount('junior');
		$this->_view->jc0_count=$contrib_obj->getContributorcount('sub-junior');
		$this->_view->refusal_reasons_max=$this->configval['refusal_reasons_max'];
		$this->_view->usertype = $this->adminLogin->type;
        $this->_view->render("ao_aocreate1");
    }
	
	 /*** AO creation Step2 ***/
	 /**** Article level fields are filled in this step***/
    public function aoCreate2Action()
    {
		$this->AO_creation = Zend_Registry::get('AO_creation');
		
		$del_obj = new Ep_Delivery_Delivery();
		
		//Storing step1 values in session array
		$params1=$this->_request->getParams();
		if($params1['title']!="")
			$this->AO_creation->ao_step1=$params1;
		
		//Navigation on right top
		if(isset($this->AO_creation->ao_step1['title']))
			$this->_view->nav_1=1;	
		if(isset($this->AO_creation->ao_step2))
			$this->_view->nav_2=1;
		
		$lang_array=  $this->_arrayDb->loadArrayv2("EP_LANGUAGES", $this->_lang);
		$art_cat_type_array=  $this->_arrayDb->loadArrayv2("EP_ARTICLE_CATEGORY", $this->_lang);
		$contrib_cat_array=array_merge(array("all"=>"All"),$art_cat_type_array); 
		$this->_view->Contrib_cats = $contrib_cat_array;
		$contrib_lang_array=$lang_array; 
		$this->_view->Contrib_langs = $contrib_lang_array;
		
		//Private contribs
		$contriblistall=$del_obj->getAllContribAO(0);
		$this->_view->contrib_array=array();
		$contriblistall1=array();
			for ($i=0;$i<count($contriblistall);$i++)
				{
					$contriblistall1[]=$contriblistall[$i];
				    $name=$contriblistall1[$i]['email'];
					$nameArr=array($contriblistall1[$i]['first_name'],$contriblistall1[$i]['last_name']);
					$nameArr=array_filter($nameArr);
					if(count($nameArr)>0)
						$name.=" (".implode(", ",$nameArr).")";
					$contriblistall1[$i]['name']=strtoupper($name);
				}
		$this->_view->contriblistall1=$contriblistall1;
		
		/** If, when step2 session is set already while coming back from other steps to step2 or in case of duplicate missions or loading templates
		Otherwise Else **/	
		if($this->_view->nav_2==1)
		{
			$tot=$this->AO_creation->ao_step1['total_article'];
			$totart=count($this->AO_creation->ao_step2['art_title']);
			
			if($tot>$totart)
			{
				$difftot=$tot-$totart;
			}
			for($a=0;$a<$totart;$a++)
			{
				$art_title[]=htmlentities($this->AO_creation->ao_step2['art_title'][$a]);
				$min_sign[]=$this->AO_creation->ao_step2['num_min'][$a];
				$max_sign[]=$this->AO_creation->ao_step2['num_max'][$a];
				$price_min[]=$this->AO_creation->ao_step2['price_min'][$a];
				$price_max[]=$this->AO_creation->ao_step2['price_max'][$a];
				$language[]=$this->AO_creation->ao_step2['language'][$a];
				$type[]=$this->AO_creation->ao_step2['type'][$a];
				$category[]=$this->AO_creation->ao_step2['category'][$a];
				$signtype[]=$this->AO_creation->ao_step2['signtype'][$a];
				$contrib_percentage[]=$this->AO_creation->ao_step2['contrib_percentage'][$a];
				
				$subjunior_time[]=$this->AO_creation->ao_step2['subjunior_time'][$a];
				$junior_time[]=$this->AO_creation->ao_step2['junior_time'][$a];
				$senior_time[]=$this->AO_creation->ao_step2['senior_time'][$a];
				$submit_option[]=$this->AO_creation->ao_step2['submit_option'][$a];
				
				$jc0_resubmission[]=$this->AO_creation->ao_step2['jc0_resubmission'][$a];
				$jc_resubmission[]=$this->AO_creation->ao_step2['jc_resubmission'][$a];
				$sc_resubmission[]=$this->AO_creation->ao_step2['sc_resubmission'][$a];
				$resubmit_option[]=$this->AO_creation->ao_step2['resubmit_option'][$a];
				
				if($this->AO_creation->ao_step1['AOtype']=='private')
				{
					$contrib_lang[]=$this->AO_creation->ao_step2['contrib_lang'][$a];
					$contrib_cat[]=$this->AO_creation->ao_step2['contrib_cat'][$a];
					$contrib_array[]=$this->AO_creation->ao_step2['favcontribcheck'][$a];
				}
				if($this->AO_creation->ao_step1['white_list'] == 'on')
				{
					$wl_kws[] = $this->AO_creation->ao_step2['white_list_'.($a+1)] ;
					$wl_kw_density_min[] = $this->AO_creation->ao_step2['white_list_density_min_'.($a+1)] ;
					$wl_kw_density_max[] = $this->AO_creation->ao_step2['white_list_density_max_'.($a+1)] ;
				}
				if($this->AO_creation->ao_step1['black_list'] == 'on')
					$bl_kws[] = $this->AO_creation->ao_step2['black_list_'.($a+1)] ;
				//correction code
				if($this->AO_creation->ao_step1['correction']=='on')
				{
					$correction_pricemin[]=$this->AO_creation->ao_step2['correction_pricemin'][$a];
					$correction_pricemax[]=$this->AO_creation->ao_step2['correction_pricemax'][$a];
				}
				
				//Lots sub titles
				if($this->AO_creation->ao_step1['articletype']=='lot')
				{
					$sub_article[]=count($this->AO_creation->ao_step2['subtitle'][$a]);
					$subtitle[]=$this->AO_creation->ao_step2['subtitle'][$a];
				}
				$column_xls[]=$this->AO_creation->ao_step2['column_xls'][$a];
			}
			
			/** Only when there is differnce in total article value filled in step1 and 
			article session array count from step2 **/
			if($difftot>0)
			{
				$inx=$totart;
				for($d=0;$d<$difftot;$d++)
				{
					$min_sign[$inx+$d]=$this->AO_creation->ao_step1['min_sign'];
					$max_sign[$inx+$d]=$this->AO_creation->ao_step1['max_sign'];
					$price_min[$inx+$d]=$this->AO_creation->ao_step1['price_min'];
					$price_max[$inx+$d]=$this->AO_creation->ao_step1['price_max'];
					$language[$inx+$d]=$this->AO_creation->ao_step1['language'];
					$type[$inx+$d]=$this->AO_creation->ao_step1['type'];
					$category[$inx+$d]=$this->AO_creation->ao_step1['category'];
					$signtype[$inx+$d]=$this->AO_creation->ao_step1['signtype'];
					$contrib_percentage[$inx+$d]=$this->AO_creation->ao_step1['contrib_percentage'];
					
					$subjunior_time[$inx+$d]=$this->AO_creation->ao_step1['subjunior_time'];
					$junior_time[$inx+$d]=$this->AO_creation->ao_step1['junior_time'];
					$senior_time[$inx+$d]=$this->AO_creation->ao_step1['senior_time'];
					$submit_option[$inx+$d]=$this->AO_creation->ao_step1['submit_option'];
					
					$jc0_resubmission[$inx+$d]=$this->AO_creation->ao_step1['jc0_resubmission'];
					$jc_resubmission[$inx+$d]=$this->AO_creation->ao_step1['jc_resubmission'];
					$sc_resubmission[$inx+$d]=$this->AO_creation->ao_step1['sc_resubmission'];
					$resubmit_option[$inx+$d]=$this->AO_creation->ao_step1['resubmit_option'];
					
					if($this->AO_creation->ao_step1['AOtype']=='private')
					{
						$contrib_lang[]=$this->AO_creation->ao_step1['contrib_lang'];
						$contrib_cat[]=$this->AO_creation->ao_step1['contrib_cat'];
						$contrib_array[]=$this->AO_creation->ao_step1['favcontribcheck'];
					}
					
					if($this->AO_creation->ao_step1['correction']=='on')
					{
						$correction_pricemin[]=$this->AO_creation->ao_step1['correction_pricemin'];
						$correction_pricemax[]=$this->AO_creation->ao_step1['correction_pricemax'];
					}
				}
			}
			$this->_view->art_title=$art_title;
			
           
			
		}
		else
		{
			/** setting default values whcn step2 session is not set **/
			
			//if a mission test, total article is considered to be number of private writers selected in step1
			if($this->AO_creation->ao_step1['missiontest']=="on")
				$tot=count($this->AO_creation->ao_step1['favcontribcheck']);
			else
				$tot=$this->AO_creation->ao_step1['total_article'];
			
			if($this->AO_creation->ao_step1['templateid']!="")
			{
				$temp_obj=new Ep_Delivery_MissionTemplate();
				$articlevalues=$temp_obj->loadArticlebytemplate($this->AO_creation->ao_step1['templateid']);
				for($t=0;$t<count($articlevalues);$t++)
					$art_title[]=htmlentities($articlevalues[$t]['title']);
				$this->_view->art_title=$art_title;	
			}
			
			for($a=0;$a<$tot;$a++)
			{
			  $min_sign[]=$this->AO_creation->ao_step1['min_sign'];
			  $max_sign[]=$this->AO_creation->ao_step1['max_sign'];
			  $price_min[]=$this->AO_creation->ao_step1['price_min'];
			  $price_max[]=$this->AO_creation->ao_step1['price_max'];
			  $language[]=$this->AO_creation->ao_step1['language'];
			  $type[]=$this->AO_creation->ao_step1['type'];
			  $category[]=$this->AO_creation->ao_step1['category'];
			  $signtype[]=$this->AO_creation->ao_step1['signtype'];
			  $contrib_percentage[]=$this->AO_creation->ao_step1['contrib_percentage'];
			  
			  $subjunior_time[]=$this->AO_creation->ao_step1['subjunior_time'];
			  $junior_time[]=$this->AO_creation->ao_step1['junior_time'];
			  $senior_time[]=$this->AO_creation->ao_step1['senior_time'];
			  $submit_option[]=$this->AO_creation->ao_step1['submit_option'];
			
			  $jc0_resubmission[]=$this->AO_creation->ao_step1['jc0_resubmission'];
			  $jc_resubmission[]=$this->AO_creation->ao_step1['jc_resubmission'];
			  $sc_resubmission[]=$this->AO_creation->ao_step1['sc_resubmission'];
			  $resubmit_option[]=$this->AO_creation->ao_step1['resubmit_option'];
			  
			  if($this->AO_creation->ao_step1['AOtype']=='private')
			  {
				$contrib_lang[]=$this->AO_creation->ao_step1['contrib_lang'];
				$contrib_cat[]=$this->AO_creation->ao_step1['contrib_cat'];
				$contrib_array[]=$this->AO_creation->ao_step1['favcontribcheck'];
			  }	
              
                if( $this->AO_creation->ao_step1['white_list'] == 'on') :
                    $wl_kws[] = $params2['white_list_'.($a+1)] ;
                    $wl_kw_density_min[] =  $params2['white_list_density_min_'.($a+1)] ;
                    $wl_kw_density_max[] =  $params2['white_list_density_max_'.($a+1)] ;
                endif ;
                if( $this->AO_creation->ao_step1['black_list'] == 'on') :
                    $bl_kws[] = $params2['black_list_'.($a+1)] ;
                endif ;
			
				if($this->AO_creation->ao_step1['correction']=='on')
				{
					$correction_pricemin[]=$this->AO_creation->ao_step1['correction_pricemin'];
					$correction_pricemax[]=$this->AO_creation->ao_step1['correction_pricemax'];
				}
				
				if($this->AO_creation->ao_step1['articletype']=='lot')
				{
					$sub_article[]=$this->AO_creation->ao_step1['sub_article'];
				}
				$column_xls[]=$this->AO_creation->ao_step1['column_xls'];
			}
        }	
			if($this->AO_creation->ao_step1['white_list'] == 'on')
			{
                $this->_view->wl_kw_count = $this->AO_creation->ao_step2['white_list_kw_count'] ;
                $this->_view->wl_kws = $wl_kws ;
                $this->_view->wl_kw_density_min = $wl_kw_density_min ;
                $this->_view->wl_kw_density_max = $wl_kw_density_max ;
                $_SESSION['white']['kw_count'] = $this->AO_creation->ao_step2['white_list_kw_count'] ;
                $_SESSION['white']['kws'] = $wl_kws ;
                $_SESSION['white']['kw_density_min'] = $wl_kw_density_min ;
                $_SESSION['white']['kw_density_max'] = $wl_kw_density_max ;
            }
            if($this->AO_creation->ao_step1['black_list'] == 'on')
			{
                $this->_view->bl_kw_count = $this->AO_creation->ao_step2['black_list_kw_count'] ;
                $this->_view->bl_kws = $bl_kws ;
                $_SESSION['black']['kw_count'] = $this->AO_creation->ao_step2['black_list_kw_count'] ;
                $_SESSION['black']['kws'] = $bl_kws ;
            }
            
			
		$this->_view->totalart=$tot; 
		$this->_view->aotype=$this->AO_creation->ao_step1['AOtype'];
		$this->_view->correction=$this->AO_creation->ao_step1['correction'];
		$this->_view->mission_type=$this->AO_creation->ao_step1['mission_type'];
		$this->_view->whitelist=$this->AO_creation->ao_step1['white_list'];
		$this->_view->blacklist=$this->AO_creation->ao_step1['black_list'];
		$this->_view->blwlcheck=$this->AO_creation->ao_step1['blwl_check'];
		$this->_view->num_min=$min_sign;
		$this->_view->num_max=$max_sign;
		$this->_view->price_min=$price_min;
		$this->_view->price_max=$price_max;
		$this->_view->language=$language;
		$this->_view->type=$type;    
		$this->_view->category=$category;   
		$this->_view->sign_type=$signtype;
		$this->_view->contrib_percentage=$contrib_percentage;
		$this->_view->subjunior_time=$subjunior_time;
		$this->_view->junior_time=$junior_time;
		$this->_view->senior_time=$senior_time;
		$this->_view->submit_option=$submit_option;
		
		$this->_view->jc0_resubmission=$jc0_resubmission;
		$this->_view->jc_resubmission=$jc_resubmission;
		$this->_view->sc_resubmission=$sc_resubmission;
		$this->_view->resubmit_option=$resubmit_option;
		
		$this->_view->contrib_array=$contrib_array;
		$this->_view->contrib_cat=$contrib_cat;
		$this->_view->contrib_lang=$contrib_lang;
		$this->_view->correction_pricemin=$correction_pricemin;
		$this->_view->correction_pricemax=$correction_pricemax;
		$this->_view->articletype=$this->AO_creation->ao_step1['articletype'];
		$this->_view->sub_article=$sub_article;
		$this->_view->subtitle=$subtitle;
		$this->_view->column_xls=$column_xls;
		
		$this->_view->page_title="Edit-place Admin : Cr&eacute;er AO";
		$this->_view->render("ao_aocreate2"); 
	}
	
	 /*** AO creation Step2 Lots***/
	 /** Article level fields are filled in, if it is lot **/
    public function aoCreate2lotAction()
    {
		
		$this->AO_creation = Zend_Registry::get('AO_creation');
		
		$del_obj = new Ep_Delivery_Delivery();
		//Storing step1 post array into session
		$params1=$this->_request->getParams();
		if($params1['title']!="")
			$this->AO_creation->ao_step1=$params1;
		//Navigation at right top
		if(isset($this->AO_creation->ao_step1['title']))
			$this->_view->nav_1=1;	
		if(isset($this->AO_creation->ao_step2))
			$this->_view->nav_2=1;
			
		
		$lang_array=  $this->_arrayDb->loadArrayv2("EP_LANGUAGES", $this->_lang);
		$art_cat_type_array=  $this->_arrayDb->loadArrayv2("EP_ARTICLE_CATEGORY", $this->_lang);
		$contrib_cat_array=array_merge(array("all"=>"All"),$art_cat_type_array); 
		$this->_view->Contrib_cats = $contrib_cat_array;
		$contrib_lang_array=$lang_array; 
		$this->_view->Contrib_langs = $contrib_lang_array; 
		$type_array = $this->_arrayDb->loadArrayv2("EP_ARTICLE_TYPE", $this->_lang); 
		$this->_view->type_array = $type_array;
		
		//Private contribs
		$contriblistall=$del_obj->getAllContribAO(0);
		$this->_view->contrib_array=array();
		$contriblistall1=array();
			for ($i=0;$i<count($contriblistall);$i++)
			{
				$contriblistall1[]=$contriblistall[$i];
				$name=$contriblistall1[$i]['email'];
				$nameArr=array($contriblistall1[$i]['first_name'],$contriblistall1[$i]['last_name']);
				$nameArr=array_filter($nameArr);
				if(count($nameArr)>0)
					$name.=" (".implode(", ",$nameArr).")";
				$contriblistall1[$i]['name']=strtoupper($name);
			}
		$this->_view->contriblistall1=$contriblistall1;
		/** If, when step2 session is set already while coming back from other steps to step2 or in case of duplicate missions or loading templates
		Otherwise Else **/	
		if($this->_view->nav_2==1)
		{
			//Total articles value filled in step1
			$tot=$this->AO_creation->ao_step1['total_article'];
			//Count of articles sets filled in step2
			$totart=count($this->AO_creation->ao_step2['art_title']);
			
			//difference of above 2 cases
			if($tot>$totart)
			{
				$difftot=$tot-$totart;
			}
			//Loading article array values 
			for($a=0;$a<$totart;$a++)
			{
				$wtype[]=$this->AO_creation->ao_step2['wtype'][$a];
				$art_title[]=htmlentities($this->AO_creation->ao_step2['art_title'][$a]);
				$min_sign[]=$this->AO_creation->ao_step2['num_min'][$a];
				$max_sign[]=$this->AO_creation->ao_step2['num_max'][$a];
				$price_min[]=$this->AO_creation->ao_step2['price_min'][$a];
				$price_max[]=$this->AO_creation->ao_step2['price_max'][$a];
				$language[]=$this->AO_creation->ao_step2['language'][$a];
				$type[]=$this->AO_creation->ao_step2['type'][$a];
				$category[]=$this->AO_creation->ao_step2['category'][$a];
				$signtype[]=$this->AO_creation->ao_step2['signtype'][$a];
				$contrib_percentage[]=$this->AO_creation->ao_step2['contrib_percentage'][$a];
				
				$subjunior_time[]=$this->AO_creation->ao_step2['subjunior_time'][$a];
				$junior_time[]=$this->AO_creation->ao_step2['junior_time'][$a];
				$senior_time[]=$this->AO_creation->ao_step2['senior_time'][$a];
				$submit_option[]=$this->AO_creation->ao_step2['submit_option'][$a];
				
				$jc0_resubmission[]=$this->AO_creation->ao_step2['jc0_resubmission'][$a];
				$jc_resubmission[]=$this->AO_creation->ao_step2['jc_resubmission'][$a];
				$sc_resubmission[]=$this->AO_creation->ao_step2['sc_resubmission'][$a];
				$resubmit_option[]=$this->AO_creation->ao_step2['resubmit_option'][$a];
				
				if($this->AO_creation->ao_step1['AOtype']=='private')
				{
					$contrib_lang[]=$this->AO_creation->ao_step2['contrib_lang'][$a];
					$contrib_cat[]=$this->AO_creation->ao_step2['contrib_cat'][$a];
					$contrib_array[]=$this->AO_creation->ao_step2['favcontribcheck'][$a];
				}
				if($this->AO_creation->ao_step1['white_list'] == 'on')
				{
					$wl_kws[] = $this->AO_creation->ao_step2['white_list_'.($a+1)] ;
					$wl_kw_density_min[] = $this->AO_creation->ao_step2['white_list_density_min_'.($a+1)] ;
					$wl_kw_density_max[] = $this->AO_creation->ao_step2['white_list_density_max_'.($a+1)] ;
				}
				if($this->AO_creation->ao_step1['black_list'] == 'on')
					$bl_kws[] = $this->AO_creation->ao_step2['black_list_'.($a+1)] ;
								
				//correction code
				if($this->AO_creation->ao_step1['correction']=='on')
				{
					$correction_pricemin[]=$this->AO_creation->ao_step2['correction_pricemin'][$a];
					$correction_pricemax[]=$this->AO_creation->ao_step2['correction_pricemax'][$a];
				}
				
				//Lots sub titles
				if($this->AO_creation->ao_step1['articletype']=='lot')
				{
					$sub_article[]=count($this->AO_creation->ao_step2['subtitle'][$a]);
					$subtitle[]=$this->AO_creation->ao_step2['subtitle'][$a];
				}
				$column_xls[]=$this->AO_creation->ao_step2['column_xls'][$a];
			}
			/** Only when there is differnce in total article value filled in step1 and 
			article session array count from step2 **/
			if($difftot>0)
			{
				$inx=$totart;
				for($d=0;$d<$difftot;$d++)
				{
					$wtype[]="R&eacute;daction";
					$min_sign[$inx+$d]=$this->AO_creation->ao_step1['min_sign'];
					$max_sign[$inx+$d]=$this->AO_creation->ao_step1['max_sign'];
					$price_min[$inx+$d]=$this->AO_creation->ao_step1['price_min'];
					$price_max[$inx+$d]=$this->AO_creation->ao_step1['price_max'];
					$language[$inx+$d]=$this->AO_creation->ao_step1['language'];
					$type[$inx+$d]=$this->AO_creation->ao_step1['type'];
					$category[$inx+$d]=$this->AO_creation->ao_step1['category'];
					$signtype[$inx+$d]=$this->AO_creation->ao_step1['signtype'];
					$contrib_percentage[$inx+$d]=$this->AO_creation->ao_step1['contrib_percentage'];
					
					$subjunior_time[$inx+$d]=$this->AO_creation->ao_step1['subjunior_time'];
					$junior_time[$inx+$d]=$this->AO_creation->ao_step1['junior_time'];
					$senior_time[$inx+$d]=$this->AO_creation->ao_step1['senior_time'];
					$submit_option[$inx+$d]=$this->AO_creation->ao_step1['submit_option'];
					
					$jc0_resubmission[$inx+$d]=$this->AO_creation->ao_step1['jc0_resubmission'];
					$jc_resubmission[$inx+$d]=$this->AO_creation->ao_step1['jc_resubmission'];
					$sc_resubmission[$inx+$d]=$this->AO_creation->ao_step1['sc_resubmission'];
					$resubmit_option[$inx+$d]=$this->AO_creation->ao_step1['resubmit_option'];
					
					if($this->AO_creation->ao_step1['AOtype']=='private')
					{
						$contrib_lang[]=$this->AO_creation->ao_step1['contrib_lang'];
						
						$contrib_cat[]=$this->AO_creation->ao_step1['contrib_cat'];
						$contrib_array[]=$this->AO_creation->ao_step1['favcontribcheck'];
					}
					
					if($this->AO_creation->ao_step1['correction']=='on')
					{
						$correction_pricemin[]=$this->AO_creation->ao_step1['correction_pricemin'];
						$correction_pricemax[]=$this->AO_creation->ao_step1['correction_pricemax'];
					}
				}
			}
			$this->_view->art_title=$art_title;
		}
		else
		{
			if($this->AO_creation->ao_step1['missiontest']=="on")
				$tot=count($this->AO_creation->ao_step1['favcontribcheck']);
			else
				$tot=$this->AO_creation->ao_step1['total_article'];
			
			if($this->AO_creation->ao_step1['templateid']!="")
			{
				$temp_obj=new Ep_Delivery_MissionTemplate();
				$articlevalues=$temp_obj->loadArticlebytemplate($this->AO_creation->ao_step1['templateid']);
				for($t=0;$t<count($articlevalues);$t++)
					$art_title[]=htmlentities($articlevalues[$t]['title']);
				$this->_view->art_title=$art_title;	
			}
			
			for($a=0;$a<$tot;$a++)
			{
			  
			  $wtype[]="R&eacute;daction";
			  $min_sign[]=$this->AO_creation->ao_step1['min_sign'];
			  $max_sign[]=$this->AO_creation->ao_step1['max_sign'];
			  $price_min[]=$this->AO_creation->ao_step1['price_min'];
			  $price_max[]=$this->AO_creation->ao_step1['price_max'];
			  $language[]=$this->AO_creation->ao_step1['language'];
			  $type[]=$this->AO_creation->ao_step1['type'];
			  $category[]=$this->AO_creation->ao_step1['category'];
			  $signtype[]=$this->AO_creation->ao_step1['signtype'];
			  $contrib_percentage[]=$this->AO_creation->ao_step1['contrib_percentage'];
			  
			  $subjunior_time[]=$this->AO_creation->ao_step1['subjunior_time'];
			  $junior_time[]=$this->AO_creation->ao_step1['junior_time'];
			  $senior_time[]=$this->AO_creation->ao_step1['senior_time'];
			  $submit_option[]=$this->AO_creation->ao_step1['submit_option'];
			
			  $jc0_resubmission[]=$this->AO_creation->ao_step1['jc0_resubmission'];
			  $jc_resubmission[]=$this->AO_creation->ao_step1['jc_resubmission'];
			  $sc_resubmission[]=$this->AO_creation->ao_step1['sc_resubmission'];
			  $resubmit_option[]=$this->AO_creation->ao_step1['resubmit_option'];
			  
			  if($this->AO_creation->ao_step1['AOtype']=='private')
			  {
				$contrib_lang[]=$this->AO_creation->ao_step1['contrib_lang'];
				$contrib_cat[]=$this->AO_creation->ao_step1['contrib_cat'];
				$contrib_array[]=$this->AO_creation->ao_step1['favcontribcheck'];
			  }	
              
                if( $this->AO_creation->ao_step1['white_list'] == 'on') :
                    $wl_kws[] = $params2['white_list_'.($a+1)] ;
                    $wl_kw_density_min[] =  $params2['white_list_density_min_'.($a+1)] ;
                    $wl_kw_density_max[] =  $params2['white_list_density_max_'.($a+1)] ;
                endif ;
                if( $this->AO_creation->ao_step1['black_list'] == 'on') :
                    $bl_kws[] = $params2['black_list_'.($a+1)] ;
                endif ;
			
				if($this->AO_creation->ao_step1['correction']=='on')
				{
					$correction_pricemin[]=$this->AO_creation->ao_step1['correction_pricemin'];
					$correction_pricemax[]=$this->AO_creation->ao_step1['correction_pricemax'];
				}
				
				if($this->AO_creation->ao_step1['articletype']=='lot')
				{
					$sub_article[]=$this->AO_creation->ao_step1['sub_article'];
				}
				$column_xls[]=$this->AO_creation->ao_step1['column_xls'];
			}
        }	
			if($this->AO_creation->ao_step1['white_list'] == 'on')
			{
                $this->_view->wl_kw_count = $this->AO_creation->ao_step2['white_list_kw_count'] ;
                $this->_view->wl_kws = $wl_kws ;
                $this->_view->wl_kw_density_min = $wl_kw_density_min ;
                $this->_view->wl_kw_density_max = $wl_kw_density_max ;
                $_SESSION['white']['kw_count'] = $this->AO_creation->ao_step2['white_list_kw_count'] ;
                $_SESSION['white']['kws'] = $wl_kws ;
                $_SESSION['white']['kw_density_min'] = $wl_kw_density_min ;
                $_SESSION['white']['kw_density_max'] = $wl_kw_density_max ;
            }
            if($this->AO_creation->ao_step1['black_list'] == 'on')
			{
                $this->_view->bl_kw_count = $this->AO_creation->ao_step2['black_list_kw_count'] ;
                $this->_view->bl_kws = $bl_kws ;
                $_SESSION['black']['kw_count'] = $this->AO_creation->ao_step2['black_list_kw_count'] ;
                $_SESSION['black']['kws'] = $bl_kws ;
            }
            
		$this->_view->totalart=$tot; 
		$this->_view->aotype=$this->AO_creation->ao_step1['AOtype'];
		$this->_view->correction=$this->AO_creation->ao_step1['correction'];
		$this->_view->mission_type=$this->AO_creation->ao_step1['mission_type'];
		$this->_view->whitelist=$this->AO_creation->ao_step1['white_list'];
		$this->_view->blacklist=$this->AO_creation->ao_step1['black_list'];
		$this->_view->blwlcheck=$this->AO_creation->ao_step1['blwl_check'];
		$this->_view->num_min=$min_sign;
		$this->_view->num_max=$max_sign;
		$this->_view->price_min=$price_min;
		$this->_view->price_max=$price_max;
		$this->_view->language=$language;
		$this->_view->type=$type;    
		$this->_view->category=$category;   
		$this->_view->sign_type=$signtype;
		$this->_view->contrib_percentage=$contrib_percentage;
		$this->_view->subjunior_time=$subjunior_time;
		$this->_view->junior_time=$junior_time;
		$this->_view->senior_time=$senior_time;
		$this->_view->submit_option=$submit_option;
		
		$this->_view->jc0_resubmission=$jc0_resubmission;
		$this->_view->jc_resubmission=$jc_resubmission;
		$this->_view->sc_resubmission=$sc_resubmission;
		$this->_view->resubmit_option=$resubmit_option;
		
		$this->_view->contrib_array=$contrib_array;
		$this->_view->contrib_cat=$contrib_cat;
		$this->_view->contrib_lang=$contrib_lang;
		$this->_view->correction_pricemin=$correction_pricemin;
		$this->_view->correction_pricemax=$correction_pricemax;
		$this->_view->articletype=$this->AO_creation->ao_step1['articletype'];
		$this->_view->sub_article=$sub_article;
		$this->_view->subtitle=$subtitle;
		$this->_view->wtype=$wtype;
		
		$user_obj=new Ep_User_Client();
		$detailsC=$user_obj->getClientName($this->AO_creation->ao_step1['client_list']);
		$this->_view->clientname=$detailsC[0]['company_name'];
		$this->_view->column_xls=$column_xls;
		
		$this->_view->frnow=strftime("%B %Y");
		
		$this->_view->render("ao_aocreate2lot"); 
	}
	
	/*** AO creation Step2 test mission***/
	/**** Article level fields are filled in this step, if it is mission test***/
    public function aoCreate2testAction()
    {
		$this->AO_creation = Zend_Registry::get('AO_creation');
		
		$del_obj = new Ep_Delivery_Delivery();
		//Storing step1 values in session array
		$params1=$this->_request->getParams();
		if($params1['title']!="")
			$this->AO_creation->ao_step1=$params1;
		//Navigation on right top	
		if(isset($this->AO_creation->ao_step1['title']))
			$this->_view->nav_1=1;	
		if(isset($this->AO_creation->ao_step2))
			$this->_view->nav_2=1;
		$lang_array=  $this->_arrayDb->loadArrayv2("EP_LANGUAGES", $this->_lang);
		$art_cat_type_array=  $this->_arrayDb->loadArrayv2("EP_ARTICLE_CATEGORY", $this->_lang);
		$contrib_cat_array=array_merge(array("all"=>"All"),$art_cat_type_array); 
		$this->_view->Contrib_cats = $contrib_cat_array;
		$contrib_lang_array=$lang_array; 
		$this->_view->Contrib_langs = $contrib_lang_array;
		
		//Private contribs
		$contriblistall=$del_obj->getAllContribAO(0);
		$this->_view->contrib_array=array();
		$contribliststep1=array();
			for ($i=0;$i<count($contriblistall);$i++)
			{
				if(in_array($contriblistall[$i]['identifier'],$this->AO_creation->ao_step1['favcontribcheck']))
				{
					$contribliststep1[$i]['identifier']=$contriblistall[$i]['identifier'];
					$name=$contriblistall[$i]['email'];
					$nameArr=array($contribliststep1[$i]['first_name'],$contriblistall[$i]['last_name']);
					$nameArr=array_filter($nameArr);
					if(count($nameArr)>0)
						$name.=" (".implode(", ",$nameArr).")";
					$contribliststep1[$i]['name']=strtoupper($name);
				}
			}
		$this->_view->contribliststep1=$contribliststep1;
		
		//Correctors for Correction block
		$correcterall=$del_obj->getAllCorrectors(0);
		$correcterall1=array();
			for ($i=0;$i<count($correcterall);$i++)
			{
				$correcterall1[]=$correcterall[$i];
				$name=$correcterall1[$i]['email'];
				$nameArr=array($correcterall1[$i]['first_name'],$correcterall1[$i]['last_name']);
				$nameArr=array_filter($nameArr);
				if(count($nameArr)>0)
					$name.=" (".implode(", ",$nameArr).")";
				$correcterall1[$i]['name']=strtoupper($name);
			}
		$this->_view->correctorliststep1=$correcterall1;
		
		/** If, when step2 session is set already while coming back from other steps to step2 or in case of duplicate missions or loading templates
		Otherwise Else **/	
		if($this->_view->nav_2==1)
		{
			//Total articles value filled in step1
			$tot=count($this->AO_creation->ao_step1['favcontribcheck']);
			//Count of articles sets filled in step2
			$totart=count($this->AO_creation->ao_step1['favcontribcheck']);
			
			//Loading article array values 
			for($a=0;$a<$totart;$a++)
			{
				$art_title[]=htmlentities($this->AO_creation->ao_step2['art_title'][$a]);
				$min_sign[]=$this->AO_creation->ao_step2['num_min'][$a];
				$max_sign[]=$this->AO_creation->ao_step2['num_max'][$a];
				$price_min[]=$this->AO_creation->ao_step2['price_min'][$a];
				$price_max[]=$this->AO_creation->ao_step2['price_max'][$a];
				$language[]=$this->AO_creation->ao_step2['language'][$a];
				$type[]=$this->AO_creation->ao_step2['type'][$a];
				$category[]=$this->AO_creation->ao_step2['category'][$a];
				$signtype[]=$this->AO_creation->ao_step2['signtype'][$a];
				$contrib_percentage[]=$this->AO_creation->ao_step2['contrib_percentage'][$a];
				//$contrib_lang[]=$this->AO_creation->ao_step2['contrib_lang'][$a];
				//$contrib_cat[]=$this->AO_creation->ao_step2['contrib_cat'][$a];
				//$contrib_array[]=$this->AO_creation->ao_step2['favcontribcheck'][$a];
				$subjunior_time[]=$this->AO_creation->ao_step2['subjunior_time'][$a];
				$junior_time[]=$this->AO_creation->ao_step2['junior_time'][$a];
				$senior_time[]=$this->AO_creation->ao_step2['senior_time'][$a];
				$submit_option[]=$this->AO_creation->ao_step2['submit_option'][$a];
				
				$jc0_resubmission[]=$this->AO_creation->ao_step2['jc0_resubmission'][$a];
				$jc_resubmission[]=$this->AO_creation->ao_step2['jc_resubmission'][$a];
				$sc_resubmission[]=$this->AO_creation->ao_step2['sc_resubmission'][$a];
				$resubmit_option[]=$this->AO_creation->ao_step2['resubmit_option'][$a];
				
				if($this->AO_creation->ao_step1['white_list'] == 'on')
				{
					$wl_kws[] = $this->AO_creation->ao_step2['white_list_'.($a+1)] ;
					$wl_kw_density_min[] = $this->AO_creation->ao_step2['white_list_density_min_'.($a+1)] ;
					$wl_kw_density_max[] = $this->AO_creation->ao_step2['white_list_density_max_'.($a+1)] ;
				}
				if($this->AO_creation->ao_step1['black_list'] == 'on')
					$bl_kws[] = $this->AO_creation->ao_step2['black_list_'.($a+1)] ;
								
				if($this->AO_creation->ao_step1['correction']=='on')
				{
					$correction_pricemin[]=$this->AO_creation->ao_step2['correction_pricemin'][$a];
					$correction_pricemax[]=$this->AO_creation->ao_step2['correction_pricemax'][$a];
					$correction_jc_submission[]=$this->AO_creation->ao_step2['correction_jc_submission'][$a];
					$correction_sc_submission[]=$this->AO_creation->ao_step2['correction_sc_submission'][$a];
					$correction_submit_option[]=$this->AO_creation->ao_step2['correction_submit_option'][$a];
					$corrector_array[]=$this->AO_creation->ao_step2['correctorcheck'][$a];
				}
				$column_xls[]=$this->AO_creation->ao_step2['column_xls'][$a];
			}
			$this->_view->art_title=$art_title;
		}
		else
		{
			$tot=count($this->AO_creation->ao_step1['favcontribcheck']);
			
			for($a=0;$a<$tot;$a++)
			{
			  $min_sign[]=$this->AO_creation->ao_step1['min_sign'];
			  $max_sign[]=$this->AO_creation->ao_step1['max_sign'];
			  $price_min[]=$this->AO_creation->ao_step1['price_min'];
			  $price_max[]=$this->AO_creation->ao_step1['price_max'];
			  $language[]=$this->AO_creation->ao_step1['language'];
			  $type[]=$this->AO_creation->ao_step1['type'];
			  $category[]=$this->AO_creation->ao_step1['category'];
			  $signtype[]=$this->AO_creation->ao_step1['signtype'];
			  $contrib_percentage[]=$this->AO_creation->ao_step1['contrib_percentage'];
			  $subjunior_time[]=$this->AO_creation->ao_step1['subjunior_time'];
			  $junior_time[]=$this->AO_creation->ao_step1['junior_time'];
			  $senior_time[]=$this->AO_creation->ao_step1['senior_time'];
			  $submit_option[]=$this->AO_creation->ao_step1['submit_option'];
			  
			  $jc0_resubmission[]=$this->AO_creation->ao_step1['jc0_resubmission'];
			  $jc_resubmission[]=$this->AO_creation->ao_step1['jc_resubmission'];
			  $sc_resubmission[]=$this->AO_creation->ao_step1['sc_resubmission'];
			  $resubmit_option[]=$this->AO_creation->ao_step1['resubmit_option'];
			  
				//$contrib_lang[]=$this->AO_creation->ao_step1['contrib_lang'];
				//$contrib_cat[]=$this->AO_creation->ao_step1['contrib_cat'];
				//$contrib_array[]=$this->AO_creation->ao_step1['favcontribcheck'];
			  	
                if( $this->AO_creation->ao_step1['white_list'] == 'on') :
                    $wl_kws[] = $params2['white_list_'.($a+1)] ;
                    $wl_kw_density_min[] =  $params2['white_list_density_min_'.($a+1)] ;
                    $wl_kw_density_max[] =  $params2['white_list_density_max_'.($a+1)] ;
                endif ;
                if( $this->AO_creation->ao_step1['black_list'] == 'on') :
                    $bl_kws[] = $params2['black_list_'.($a+1)] ;
                endif ;
			
					$correction_pricemin[]=$this->AO_creation->ao_step1['correction_pricemin'];
					$correction_pricemax[]=$this->AO_creation->ao_step1['correction_pricemax'];
					$correction_jc_submission[]=$this->AO_creation->ao_step1['correction_jc_submission'];
					$correction_sc_submission[]=$this->AO_creation->ao_step1['correction_sc_submission'];
					$correction_submit_option[]=$this->AO_creation->ao_step1['correction_submit_option'];
					$corrector_array[]=$this->AO_creation->ao_step1['correctorcheck'];
				
				$column_xls[]=$this->AO_creation->ao_step1['column_xls'];
			}
        }	
			if($this->AO_creation->ao_step1['white_list'] == 'on')
			{
                $this->_view->wl_kw_count = $this->AO_creation->ao_step2['white_list_kw_count'] ;
                $this->_view->wl_kws = $wl_kws ;
                $this->_view->wl_kw_density_min = $wl_kw_density_min ;
                $this->_view->wl_kw_density_max = $wl_kw_density_max ;
                $_SESSION['white']['kw_count'] = $this->AO_creation->ao_step2['white_list_kw_count'] ;
                $_SESSION['white']['kws'] = $wl_kws ;
                $_SESSION['white']['kw_density_min'] = $wl_kw_density_min ;
                $_SESSION['white']['kw_density_max'] = $wl_kw_density_max ;
            }
            if($this->AO_creation->ao_step1['black_list'] == 'on')
			{
                $this->_view->bl_kw_count = $this->AO_creation->ao_step2['black_list_kw_count'] ;
                $this->_view->bl_kws = $bl_kws ;
                $_SESSION['black']['kw_count'] = $this->AO_creation->ao_step2['black_list_kw_count'] ;
                $_SESSION['black']['kws'] = $bl_kws ;
            }
            
		$this->_view->totalart=$tot; 
		$this->_view->aotype=$this->AO_creation->ao_step1['AOtype'];
		$this->_view->correction=$this->AO_creation->ao_step1['correction'];
		$this->_view->mission_type=$this->AO_creation->ao_step1['mission_type'];
		$this->_view->whitelist=$this->AO_creation->ao_step1['white_list'];
		$this->_view->blacklist=$this->AO_creation->ao_step1['black_list'];
		$this->_view->blwlcheck=$this->AO_creation->ao_step1['blwl_check'];
		$this->_view->num_min=$min_sign;
		$this->_view->num_max=$max_sign;
		$this->_view->price_min=$price_min;
		$this->_view->price_max=$price_max;
		$this->_view->language=$language;
		$this->_view->type=$type;    
		$this->_view->category=$category;   
		$this->_view->sign_type=$signtype;
		$this->_view->contrib_percentage=$contrib_percentage;
		
		$this->_view->subjunior_time=$subjunior_time;
		$this->_view->junior_time=$junior_time;
		$this->_view->senior_time=$senior_time;
		$this->_view->submit_option=$submit_option;
		
		$this->_view->jc0_resubmission=$jc0_resubmission;
		$this->_view->jc_resubmission=$jc_resubmission;
		$this->_view->sc_resubmission=$sc_resubmission;
		$this->_view->resubmit_option=$resubmit_option;
		
		$this->_view->contrib_array=$this->AO_creation->ao_step1['favcontribcheck'];
		//$this->_view->contrib_cat=$contrib_cat;
		//$this->_view->contrib_lang=$contrib_lang;
		$this->_view->correction_pricemin=$correction_pricemin;
		$this->_view->correction_pricemax=$correction_pricemax;
		$this->_view->correction_jc_submission=$correction_jc_submission;
		$this->_view->correction_sc_submission=$correction_sc_submission;
		$this->_view->correction_submit_option=$correction_submit_option;
		$this->_view->corrector_array=$corrector_array;
		$this->_view->column_xls=$column_xls;
		
		$this->_view->render("ao_aocreate2test"); 
	}
	
	/* Ajax call to load keyword list*/ 
	public function kwlistAction() 
	{
        $kwParams=$this->_request->getParams() ;
        $kwListSession =  $_SESSION[$kwParams['list']] ;
        
        $htm = '' ;
        for($i=0;$i<$kwParams['count'];$i++)
        {
            if( $kwListSession['kw_count'][$kwParams['idx'] - 1] > $i ) :
                $kw  =   $kwListSession['kws'][$kwParams['idx'] - 1][$i] ;
                $kwmin  =   $kwListSession['kw_density_min'][$kwParams['idx'] - 1][$i] ;
                $kwmax  =   $kwListSession['kw_density_max'][$kwParams['idx'] - 1][$i] ;
            else :
                $kw  =   '' ;   $kwmin  =   '' ;    $kwmax  =   '' ;
            endif ;
            
            $htm .=   '<input type="text" value="' . $kw . '" id="'.$kwParams['list'].'_list_'.$kwParams['idx'].'[]" name="'.$kwParams['list'].'_list_'.$kwParams['idx'].'[]" placeholder="Keyword">&nbsp;' ;
            
            if($kwParams['list'] != 'black') :
                $htm .=   '<input type="text" style="width:40px;" value="' . $kwmin . '" id="'.$kwParams['list'].'_list_density_min_'.$kwParams['idx'].'[]" name="'.$kwParams['list'].'_list_density_min_'.$kwParams['idx'].'[]" placeholder="Min">&nbsp;' ;
                $htm .=   '<input type="text" style="width:40px;" value="' . $kwmax . '" id="'.$kwParams['list'].'_list_density_max_'.$kwParams['idx'].'[]" name="'.$kwParams['list'].'_list_density_max_'.$kwParams['idx'].'[]" placeholder="Max">' ;
            endif ;
            
            $htm .=   '<br>' ;
        }
        exit($htm) ;
    }
	
	/*** AO creation Step3 ***/
	/* In this step Mails, comments & publishtimes are set */
    public function aoCreate3Action()
    {	
		$this->AO_creation = Zend_Registry::get('AO_creation');
		$params2=$this->_request->getParams();
		//storing step2 data in session array
		if($params2['art_title'][0]!="")
			$this->AO_creation->ao_step2=$params2;
		//Navigation on right top
		if(isset($this->AO_creation->ao_step1['title']))
			$this->_view->nav_1=1;
		if(isset($this->AO_creation->ao_step2))
			$this->_view->nav_2=1;
		if(isset($this->AO_creation->ao_step3))
			$this->_view->nav_3=1;
		
		if($this->AO_creation->ao_step1['mission_type']!="liberte")
			$this->_view->price_per_art=array_sum($this->AO_creation->ao_step2['price_max']);		
		$del_obj= new Ep_Delivery_Delivery();
		$contrib_lang_array = $this->_arrayDb->loadArrayv2("EP_LANGUAGES", $this->_lang);
        $this->_view->Contrib_langs = $contrib_lang_array;
		
		//Private contribs //not using now
		$contriblistall=$del_obj->getMailContribs($this->AO_creation->ao_step1['contribtype']);
		$this->_view->contrib_array=array();
		$mailcontriblist=array();
			for ($i=0;$i<count($contriblistall);$i++)
			{
				$mailcontriblist[]=$contriblistall[$i];
				$name=$mailcontriblist[$i]['email'];
				$nameArr=array($mailcontriblist[$i]['first_name'],$mailcontriblist[$i]['last_name']);
				$nameArr=array_filter($nameArr);
				if(count($nameArr)>0)
					$name.=" (".implode(", ",$nameArr).")";
				$mailcontriblist[$i]['name']=strtoupper($name);
			}
		$this->_view->mailcontriblist=$mailcontriblist;
		
			/* If only for duplicate & loading template cases*/
			if($this->_view->nav_3==1)
			{ 
				$this->_view->missioncomment=$this->AO_creation->ao_step3['missioncomment'];
				$this->_view->fbcomment=$this->AO_creation->ao_step3['fbcomment'];
				$this->_view->nltitle=$this->AO_creation->ao_step3['nltitle'];
				
				if($this->AO_creation->ao_step3['publishnow']=="yes" && $this->AO_creation->ao_step3['AOtype']!="private" && (in_array('senior',$this->AO_creation->ao_step1['contribtype']) && count($this->AO_creation->ao_step1['contribtype'])==1))
					$this->_view->contrib_langarray=$this->AO_creation->ao_step3['mailcontrib_lang'];
				else
					$this->_view->contrib_langarray=array_keys($contrib_lang_array);
			}
			else
				$this->_view->contrib_langarray=array_keys($contrib_lang_array);
		if(in_array('senior',$this->AO_creation->ao_step1['contribtype']) && count($this->AO_creation->ao_step1['contribtype'])==1)
			$this->_view->sconly='yes';
		else
			$this->_view->sconly='no';
		
		$ttotal=$this->AO_creation->ao_step1['TotPrem']*$this->AO_creation->ao_step1['total_article'];
		$ttotal+=$this->_view->price_per_art;
        $this->_view->total_price=$ttotal;
        $this->_view->ao_type=$this->AO_creation->ao_step1['AOtype'];
        $this->_view->contribtype=$this->AO_creation->ao_step1['contribtype'];
        $current_timestamp = strtotime('+1 day', time());
		$date=date("d/m/Y",$current_timestamp);
		$this->_view->tommorow=$date.' 10:00';
		$this->_view->usertype = $this->adminLogin->type;
		$this->_view->paypercent = 0;
		$this->_view->premium_option=$this->AO_creation->ao_step1['mission_type'];
		$this->_view->missiontest=$this->AO_creation->ao_step1['missiontest'];
		$this->_view->articletype=$this->AO_creation->ao_step1['articletype'];
		$this->_view->correction=$this->AO_creation->ao_step1['correction'];
			
		$this->_view->render("ao_aocreate3");
	}
	
	/*** AO creation Step4 ***/
	/* FInal step of AO creation where all related db insertions are done */
    public function aoCreate4Action()
    {
		$this->AO_creation = Zend_Registry::get('AO_creation');
		//If step1 seesion is not set, redirecting to step1
		if($this->AO_creation->ao_step1['title']=="")
			$this->_redirect("/ao/create?submenuId=ML2-SL3");
		
		//Storing step3 data to session array
		$params3=$this->_request->getParams();
		$this->AO_creation->ao_step3=$params3;
		
		//If duplicate mission checked in step3
		//print_r($this->AO_creation->ao_step2);
		//print_r($this->AO_creation->ao_step3);
		$this->_view->duplicate=$this->AO_creation->ao_step3['duplicateao'];
		
		$ao_obj = new Ep_Delivery_Delivery();
		$art_obj = new EP_Delivery_Article();
		$opt_obj = new Ep_Delivery_DeliveryOptions();
		$pay_obj = new Ep_Payment_Payment();
		$payart_obj = new Ep_Payment_PaymentArticle();
		
		$type_array = $this->_arrayDb->loadArrayv2("EP_ARTICLE_TYPE", $this->_lang); 
		
		/******************************************** db Insertion ********************************************/
		$this->AO_creation->ao_step1['created_user']=$this->adminLogin->userId;
		if($this->AO_creation->ao_step3['publishnow']!="yes" && $this->AO_creation->ao_step3['publishtime']!="")
		{	
			$time=explode(" ",$this->AO_creation->ao_step3['publishtime']);
			$dat=explode("/",$time[0]);
			$dat1=$dat[2]."-".$dat[1]."-".$dat[0]." ".$time[1].":00";
			$this->AO_creation->ao_step3['publishtimestamp'] = strtotime($dat1);
		}
		
		//Delivery insertion
		$darray = array();
		$darray["user_id"] = $this->AO_creation->ao_step1['client_list'];
		$darray['title'] = $this->AO_creation->ao_step1['title'];
		if($this->AO_creation->ao_step1['deli_anonymous']=="on")$darray["deli_anonymous"] = "yes";else{$darray["deli_anonymous"] = "no";}
		$darray["total_article"] = $this->AO_creation->ao_step1['total_article'];
		$darray["language"] = $this->AO_creation->ao_step1['language'];
		$darray["type"] = $this->AO_creation->ao_step1['type'];
		$darray["category"] = $this->AO_creation->ao_step1['category'];
		$darray["signtype"] = $this->AO_creation->ao_step1['signtype'];
		$darray["min_sign"]=str_replace(",",".",$this->AO_creation->ao_step1['min_sign']);
		$darray['max_sign']=str_replace(",",".",$this->AO_creation->ao_step1['max_sign']);
		$darray['price_min']=str_replace(",",".",$this->AO_creation->ao_step1['price_min']);
		$darray['price_max']=str_replace(",",".",$this->AO_creation->ao_step1['price_max']);
		
		$darray['currency']=$this->AO_creation->ao_step1['currency'];
			$darray_view_to = implode(',', $this->AO_creation->ao_step1['contribtype']) ;
			$short = array("jc0","jc","sc");
			$full = array("sub-junior","junior","senior");
		$darray["view_to"] = str_replace($full, $short, $darray_view_to);
        //Premium
		if($this->AO_creation->ao_step1['mission_type']=="liberte")
			$darray["premium_option"] = "0";
	    else
			{
				$Option = explode("_",$this->AO_creation->ao_step1['premium_option']);
				$darray["premium_option"] = $Option[0];
			}
		$darray["premium_total"] = $this->AO_creation->ao_step1['TotPrem'];
		//Submit time
	    if($this->AO_creation->ao_step1['submit_option']=='min')
			$sub_multiple = 1;
		elseif($this->AO_creation->ao_step1['submit_option']=='hour')
			$sub_multiple = 60;
		elseif($this->AO_creation->ao_step1['submit_option']=='day')
			$sub_multiple = 60*24;
		$darray["submit_option"] = $this->AO_creation->ao_step1['submit_option'];
		$darray["junior_time"] = $this->AO_creation->ao_step1['junior_time']*$sub_multiple;
		$darray["senior_time"] = $this->AO_creation->ao_step1['senior_time']*$sub_multiple;
		$darray["subjunior_time"] = $this->AO_creation->ao_step1['subjunior_time']*$sub_multiple;
		
		//Resubmit time
		if($this->AO_creation->ao_step1['resubmit_option']=='min')
			$resub_multiple = 1;
		elseif($this->AO_creation->ao_step1['resubmit_option']=='hour')
			$resub_multiple = 60;
		elseif($this->AO_creation->ao_step1['resubmit_option']=='day')
			$resub_multiple = 60*24;
		$darray["resubmit_option"] = $this->AO_creation->ao_step1['resubmit_option'];
		$darray["jc_resubmission"] = $this->AO_creation->ao_step1['jc_resubmission']*$resub_multiple;
		$darray["sc_resubmission"] = $this->AO_creation->ao_step1['sc_resubmission']*$resub_multiple;
		$darray["jc0_resubmission"] = $this->AO_creation->ao_step1['jc0_resubmission']*$resub_multiple;
		$darray["participation_time"] = $this->AO_creation->ao_step1['participation_time'];
		if($this->AO_creation->ao_step1['blwl_check']=="on")
            $darray["blwl_check"] = 'yes';
		$darray["file_name"] = $this->AO_creation->ao_step1['spec_file_name'];
		if($this->AO_creation->ao_step1['spec_file_name']!="")
		{
			$specarray = explode(",",$this->AO_creation->ao_step1['spec_file_name']);
			$filepathstr = array();
			for($s=0;$s<count($specarray);$s++)
				$filepathstr[] = "/".$this->AO_creation->ao_step1['client_list']."/".$specarray[$s];
			$darray["filepath"] = implode("|",$filepathstr);	 
		}
		$darray["created_by"]='BO';
		$darray["created_user"]=$this->AO_creation->ao_step1['created_user'];
		$darray["status_bo"] = "active";
		$darray["updated_at"] = date('Y-m-d');
		$darray["published_at"] = time();
		if($this->AO_creation->ao_step1['AOtype']=="private")$darray["AOtype"] = "private";else{$darray["AOtype"] = "public";}
		if($this->AO_creation->ao_step1["AOtype"] =='private')
			 $darray["contribs_list"]=implode(",",$this->AO_creation->ao_step1["favcontribcheck"]);
		if($darray["premium_option"]=="0")
			$darray["plagiarism_check"]="no";
		else
			$darray["plagiarism_check"]="yes";
		if($this->AO_creation->ao_step1['writer_notify']=="yes"){$darray["writer_notify"]="yes";}else{$darray["writer_notify"]="no";}	
			
		//Link Poll
		if($this->AO_creation->ao_step1['linkpoll']=="on")
		{
			$darray["poll_id"] = $this->AO_creation->ao_step1['pollao'];
			$darray["priority_hours"] = $this->AO_creation->ao_step1['priority_hours'];
		}
		//Correction
		if($this->AO_creation->ao_step1['correction']=="on")
		{
			$darray["correction_pricemin"]=$this->AO_creation->ao_step1['correction_pricemin'];
			$darray["correction_pricemax"]=$this->AO_creation->ao_step1['correction_pricemax'];
			if($this->AO_creation->ao_step1['correction_spec_file']!="")
				$darray["correction_file"]="/".$this->AO_creation->ao_step1['client_list']."/".$this->AO_creation->ao_step1['correction_spec_file'];
			$darray["correction_participation"]=$this->AO_creation->ao_step1['correction_participation'];
			//Corrector submit time
		    $darray["correction_submit_option"]=$this->AO_creation->ao_step1['correction_submit_option'];
			if($this->AO_creation->ao_step1['correction_submit_option']=='min')
				$corrsub_multiple = 1;
			elseif($this->AO_creation->ao_step1['correction_submit_option']=='hour')
				$corrsub_multiple = 60;
			elseif($this->AO_creation->ao_step1['correction_submit_option']=='day')
				$corrsub_multiple = 60*24;
			$darray["correction_jc_submission"] = $this->AO_creation->ao_step1['correction_jc_submission']*$corrsub_multiple;
			$darray["correction_sc_submission"] = $this->AO_creation->ao_step1['correction_sc_submission']*$corrsub_multiple;
			//Corrector resubmit time
			$darray["correction_resubmit_option"]=$this->AO_creation->ao_step1['correction_resubmit_option'];
			if($this->AO_creation->ao_step1['correction_resubmit_option']=='min')
				$corrresub_multiple = 1;
			elseif($this->AO_creation->ao_step1['correction_resubmit_option']=='hour')
				$corrresub_multiple = 60;
			elseif($this->AO_creation->ao_step1['correction_resubmit_option']=='day')
				$corrresub_multiple = 60*24;
			$darray["correction_jc_resubmission"] = $this->AO_creation->ao_step1['correction_jc_resubmission']*$corrresub_multiple;
			$darray["correction_sc_resubmission"] = $this->AO_creation->ao_step1['correction_sc_resubmission']*$corrresub_multiple;
			if($this->AO_creation->ao_step1['corrector_mail']=="on"){$darray["corrector_mail"]="yes";}else{$darray["corrector_mail"]="no";}
			if($this->AO_creation->ao_step1['correction_type']=="private"){$darray["correction_type"]="private";}else{$darray["correction_type"]="public";}
			/*if($this->AO_creation->ao_step1['corrector']!="")
				$corrector[]=$this->AO_creation->ao_step1['corrector_option'];
			if($this->AO_creation->ao_step1['writer']!="")
				$corrector[]=$this->AO_creation->ao_step1['writer_option'];
			$darray['corrector_list']=implode(",",$corrector);*/
			$darray['corrector_list']='CB';
			if($this->AO_creation->ao_step1['corrector_notify']=="yes"){$darray["corrector_notify"]="yes";}else{$darray["corrector_notify"]="no";}
			//Mail
			$darray["correctorsendfrom"]=$this->AO_creation->ao_step3['correctorsendfrom'];
			$darray["correctormailsubject"]=$this->AO_creation->ao_step3['correctormailsubject'];
			$darray["correctormailcontent"]=$this->AO_creation->ao_step3['correctormailcontent'];
		}
		//Quiz
		if($this->AO_creation->ao_step1['link_quiz']=="on"){$darray["link_quiz"]="yes";}else{$darray["link_quiz"]="no";};
		if($this->AO_creation->ao_step1['link_quiz']=="on")
		{
			$darray["quiz"]=$this->AO_creation->ao_step1['quiz'];
			$darray["quiz_marks"]=$this->AO_creation->ao_step1['quiz_marks'];
			$darray["quiz_duration"]=$this->AO_creation->ao_step1['quiz_duration'];
		}
		//publishtime
		if($this->AO_creation->ao_step3['publishnow']!="yes" && $this->AO_creation->ao_step3['publishtime']!="")
			$darray["publishtime"]=$this->AO_creation->ao_step3['publishtimestamp'];
		$darray["mailsubject"]=$this->AO_creation->ao_step3['mailsubject'];
		$darray["mailcontent"]=$this->AO_creation->ao_step3['mailcontrib'];
		$darray["missioncomment"]=$this->AO_creation->ao_step3['missioncomment'];
		$darray["fbcomment"]=$this->AO_creation->ao_step3['fbcomment'];
		$darray["nltitle"]=$this->AO_creation->ao_step3['nltitle'];
		
		if($this->AO_creation->ao_step3['publishnow']=="yes" && $darray["AOtype"]=="public")
			$darray["mailnow"]="yes";
		if($this->AO_creation->ao_step3['mail_send']=="on"){$darray["mail_send"]="yes";}else{$darray["mail_send"]="no";};
		/*if($this->AO_creation->ao_step3['publishnow']=="yes" && $darray["AOtype"]=="public" && (in_array('senior',$this->AO_creation->ao_step1['contribtype']) && count($this->AO_creation->ao_step1['contribtype'])==1))
		{
			$darray["publish_language"]=implode(",",$this->AO_creation->ao_step3['mailcontrib_lang']);
		}*/
		if($darray["AOtype"]=="public" && (count($this->AO_creation->ao_step1['publish_language'])>0))
			$darray["publish_language"]=implode(",",$this->AO_creation->ao_step1['publish_language']);
			
		//Mission test
		if($this->AO_creation->ao_step1['missiontest']=="on")
		{
			$darray["missiontest"]="yes";
			$darray["total_article"]=count($this->AO_creation->ao_step1["favcontribcheck"]);
			$darray["min_mark"]=$this->AO_creation->ao_step1["min_mark"]; 
			$darray["premium_total"]=0; 
			$darray["premium_option"]=13;
		}
		else
		{
			$darray["missiontest"]="no";
		}
		
		//Lots
		if($this->AO_creation->ao_step1['articletype']=="lot")
			$darray["lot"]="yes";
		
		//public mail filter
		//if(count($this->AO_creation->ao_step3["mailcontribcheck"])>0)
			 //$darray["publicmailcontrib"]=implode(",",$this->AO_creation->ao_step3["mailcontribcheck"]);
		
		if($this->AO_creation->ao_step1['pricedisplay']=="yes")
			$darray["pricedisplay"]="yes"; 
		else
			$darray["pricedisplay"]="no";
		$darray["column_xls"]=$this->AO_creation->ao_step1['column_xls'];
		$darray["mail_sender"]=$this->AO_creation->ao_step3['sendfrom'];	
		$darray["urlsexcluded"]=$this->AO_creation->ao_step1['urlsexcluded'];
		
		$darray["product"]=$this->AO_creation->ao_step1['product'];
		if($this->AO_creation->ao_step1['product']=="redaction")
		{
			if(count($this->AO_creation->ao_step1['redactionrefusal'])>0)
				$darray["refusalreasons"]=implode("|",$this->AO_creation->ao_step1['redactionrefusal']);
		}
		else
		{
			if(count($this->AO_creation->ao_step1['translationrefusal'])>0)
				$darray["refusalreasons"]=implode("|",$this->AO_creation->ao_step1['translationrefusal']);
		}
				
				
		$delivery=$ao_obj->insertDelivery($darray);
	
		//If delivery insertion is done properly
			if($delivery!="false")
			{
				//Premium option insertion 
				if(count($this->AO_creation->ao_step1['premium_service'])>0)
					$opt_obj->insertOptions($delivery,$this->AO_creation->ao_step1['premium_service']);
				
				//Article insertion
				$this->AO_creation->ao_step2['currency'] = $this->AO_creation->ao_step1['currency'];
				
				$this->AO_creation->ao_step2['priorcontrib'] = $this->AO_creation->ao_step1['priorcontrib'];
				$this->AO_creation->ao_step2['correction'] = $this->AO_creation->ao_step1['correction'];
				$this->AO_creation->ao_step2['AOtype'] = $this->AO_creation->ao_step1['AOtype'];
				$this->AO_creation->ao_step2['view_to'] = $darray["view_to"];
				
				if($this->AO_creation->ao_step1['AOtype']!="private" && count($this->AO_creation->ao_step1['publish_language'])>0)
					$this->AO_creation->ao_step2['publish_language'] = $this->AO_creation->ao_step1['publish_language'];
					
				$this->AO_creation->ao_step2['linkpoll'] = $this->AO_creation->ao_step1['linkpoll'];
				if($this->AO_creation->ao_step3['publishnow']!="yes" && $this->AO_creation->ao_step3['publishtime']!="")
					$expires=$this->AO_creation->ao_step3['publishtimestamp']+(60*$this->AO_creation->ao_step1['participation_time']);
				else
					$expires=time()+(60*$this->AO_creation->ao_step1['participation_time']);
				$this->AO_creation->ao_step2['participation_expires'] = $expires;
				
				$count=count($this->AO_creation->ao_step2['art_title']);
				$user_obj=new Ep_User_Client();
				$detailsC=$user_obj->getClientName($this->AO_creation->ao_step1['client_list']);
				for($s=0;$s<$count;$s++)
				{
					if($this->AO_creation->ao_step1['white_list']=="on")
						$this->AO_creation->ao_step2["wl_kws"][$s] = ( $_SESSION['white']['kws'][$s][0] ? (serialize(array($_SESSION['white']['kws'][$s], $_SESSION['white']['kw_density_min'][$s], $_SESSION['white']['kw_density_max'][$s]))) : '' ) ;
					
					if($this->AO_creation->ao_step1['black_list']=="on")
						$this->AO_creation->ao_step2["bl_kws"][$s] = ( $_SESSION['black']['kws'][$s][0] ? (serialize(array($_SESSION['black']['kws'][$s]))) : '' ) ;
					
					//Title
					if($this->AO_creation->ao_step1['articletype']=="lot")
						$this->AO_creation->ao_step2["art_title"][$s]=$detailsC[0]['company_name'].' - '.strtoupper($this->AO_creation->ao_step2["language"][$s]).' - '.$this->AO_creation->ao_step2["wtype"][$s].' - '.$count.' article(s) - '.$type_array[$this->AO_creation->ao_step2["type"][$s]].' - LOT '.($s+1).' - '.strftime("%B %Y"); 
				}
				$this->AO_creation->ao_step2['missiontest']=$this->AO_creation->ao_step1['missiontest'];
				if($this->AO_creation->ao_step1['missiontest']!="on")
				{
					$this->AO_creation->ao_step2['corrector_privatelist'] = $this->AO_creation->ao_step1['correctorcheck'];
					//$this->AO_creation->ao_step2["submit_option"] = $darray["submit_option"];
					//$this->AO_creation->ao_step2["junior_time"] = $darray["junior_time"];
					//$this->AO_creation->ao_step2["senior_time"] = $darray["senior_time"];
					//$this->AO_creation->ao_step2["subjunior_time"] = $darray["subjunior_time"];
					$this->AO_creation->ao_step2["correction_jc_submission"] = $darray["correction_jc_submission"];
					$this->AO_creation->ao_step2["correction_sc_submission"] = $darray["correction_sc_submission"];
					$this->AO_creation->ao_step2["correction_submit_option"] = $darray["correction_submit_option"];
				}
				
				//Participation and submit times
				$this->AO_creation->ao_step2["participation_time"] = $darray["participation_time"];
				//$this->AO_creation->ao_step2["resubmit_option"] = $darray["resubmit_option"];
				//$this->AO_creation->ao_step2["jc_resubmission"] = $darray["jc_resubmission"];
				//$this->AO_creation->ao_step2["sc_resubmission"] = $darray["sc_resubmission"];
				//$this->AO_creation->ao_step2["jc0_resubmission"] = $darray["jc0_resubmission"]; 
				//Correction times
				$this->AO_creation->ao_step2["correction_participation"] = $darray["correction_participation"];
				$this->AO_creation->ao_step2["correction_jc_resubmission"] = $darray["correction_jc_resubmission"];
				$this->AO_creation->ao_step2["correction_sc_resubmission"] = $darray["correction_sc_resubmission"];
				$this->AO_creation->ao_step2["correction_resubmit_option"] = $darray["correction_resubmit_option"];
				if($this->AO_creation->ao_step1["nomoderation"]=="yes")
					$this->AO_creation->ao_step2["nomoderation"]="yes";
				else
					$this->AO_creation->ao_step2["nomoderation"]="no";
					
				$this->AO_creation->ao_step2['articletype']=$this->AO_creation->ao_step1['articletype'];
				if($this->AO_creation->ao_step1['testrequired']=="yes")
					{
						$this->AO_creation->ao_step2['testrequired']="yes";
						$this->AO_creation->ao_step2['testmarks']=$this->AO_creation->ao_step1['testmarks'];
					}
				else
					{
						$this->AO_creation->ao_step2['testrequired']="no";
					}
				$this->AO_creation->ao_step2['product']=$this->AO_creation->ao_step1['product'];
				if($this->AO_creation->ao_step1['product']=="redaction")
				{
					if(count($this->AO_creation->ao_step1['redactionrefusal'])>0)
						$this->AO_creation->ao_step2["refusalreasons"]=implode("|",$this->AO_creation->ao_step1['redactionrefusal']);
				}
				else
				{
					if(count($this->AO_creation->ao_step1['translationrefusal'])>0)
						$this->AO_creation->ao_step2["refusalreasons"]=implode("|",$this->AO_creation->ao_step1['translationrefusal']);
				}	
				
				//correction participation expires
				if($this->AO_creation->ao_step1['correction']=="on")
				{
					if($this->AO_creation->ao_step3['publishnow']!="yes" && $this->AO_creation->ao_step3['publishtime']!="")
						$correxpires=$this->AO_creation->ao_step3['publishtimestamp']+(60*$this->AO_creation->ao_step1['correction_participation']);
					else
						$correxpires=time()+(60*$this->AO_creation->ao_step1['correction_participation']);	
						
					$this->AO_creation->ao_step2['correction_participationexpires'] = $correxpires;
				}
				$art_obj->insertArticle($delivery,$this->AO_creation->ao_step2);
				
				//Payment table
			
				$Pyarray = array();
				$Pyarray['delivery_id']=$delivery;
				if($this->AO_creation->ao_step3['paypercent']=="0")
					$Pyarray['amount_paid']=$this->AO_creation->ao_step3['totcost'];
				else
					$Pyarray['amount_paid']="0";
				$Pyarray['status']='Paid';
				$pay_obj->insertPayment($Pyarray);
				if(($this->AO_creation->ao_step3['paypercent']=="0"))
                {
					$data = array();
					$data['amount'] = $Pyarray['amount_paid'];
					$pay_amount = $Pyarray['amount_paid']*1.196;
					$pay_amount = number_format($pay_amount,2);
					$data['user_id']=$this->AO_creation->ao_step1['client_list'];
					$data['amount_paid']=$pay_amount;
					$data['type']='instant';
					$data['pay_type']='BO';
					$invoiceId=$payart_obj->insertPayment_article($data);
                    $art_obj->updatePaidarticle($delivery,$invoiceId);
                }
				
				// Mission Template insertion
				if($this->AO_creation->ao_step3['saveao']=="yes" && $this->AO_creation->ao_step3['templatename']!="")
                {
					$temp_obj=new Ep_Delivery_MissionTemplate();
					$missarray=array("client_id"=>$this->AO_creation->ao_step1['client_list'],"title"=>$this->AO_creation->ao_step3['templatename'],"delivery_id"=>$delivery);
					$temp_obj->inserttemplate($missarray);
				}
				//ArticleHistory Insertion
				$hist_obj = new Ep_Delivery_ArticleHistory();
				$action_obj = new EP_Delivery_ArticleActions();
				$history1=array();
				$history1['user_id']=$this->adminLogin->userId;
				$history1['article_id']=$delivery;
					$sentence1=$action_obj->getActionSentence(1);
					/*if($darray["AOtype"]=="public")
						$AO_type='<b>Public, '.$darray["view_to"].'</b>';
					else
						$AO_type='<b>Private avec '.count($this->AO_creation->ao_step1["favcontribcheck"]).' contributeurs</b>';*/
						
					if($darray["AOtype"]=="public")
						$AO_type='<b>Public</b>';
					else
						$AO_type='<b>Private</b>';	
					
					//$AO_type='<b>'.$darray["AOtype"].'</b>';
					$AO_name='<a href="/ongoing/ao-details?client_id='.$this->AO_creation->ao_step1['client_list'].'&ao_id='.$delivery.'&submenuId=ML2-SL4" target="_blank"><b>'.$this->AO_creation->ao_step1['title'].'</b></a>';
						$user_obj=new Ep_User_Client();
						$detailsC=$user_obj->getClientName($this->AO_creation->ao_step1['client_list']);
					$client_name='<b>'.$detailsC[0]['company_name'].'</b>';
					$project_manager_name='<b>'.ucfirst($this->adminLogin->loginName).'</b>';
					$actionmessage=strip_tags($sentence1[0]['Message']);
					eval("\$actionmessage= \"$actionmessage\";");
				$history1['stage']='creation';
				$history1['action_sentence']=$actionmessage;
				$hist_obj->insertHistory($history1);
				/** Sending mail to client **/
                $deldetails = $ao_obj->getDeliveryDetails($delivery);
				$aoDetails = $ao_obj->getPrAoDetails($delivery);
                
				if($deldetails[0]['mail_send']=='yes' && $aoDetails[0]['premium_option']=='0')
                {
					$clientparameters['AO_title']=$aoDetails[0]['title'];
					$clientparameters['submitdate_bo']=date('d/m/Y H:i', $expires);
					$clientparameters['clientartname_link'] = "/client/quotes?id=".$aoDetails[0]['articleid'];
					$this->messageToEPMail($aoDetails[0]['user_id'],5,$clientparameters);
                }
				
				/** Sending mail to contributors **/
				    //Priority contributors mail
                    if($aoDetails[0]['priority_contributors']!="")
                    {
                        /*$prior_contribs=explode(",",$aoDetails[0]['priority_contributors']);
                        $prior_parameters['poll_link']='<a href="/contrib/aosearch">Cliquant-ici</a>';
                        $prior_parameters['hours']=$aoDetails[0]['priority_hours'];
                        foreach($prior_contribs as $pcontrib)
                        {
                            $contrib_poll=$ao_obj->getPollcontribDetails($aoDetails[0]['poll_id'],$pcontrib);
                            $prior_parameters['poll']=$contrib_poll[0]['title'];
                            $prior_parameters['date']=$contrib_poll[0]['poll_date'];
                            $prior_parameters['price']=$contrib_poll[0]['price_user'];
                            $this->messageToEPMail($pcontrib,15,$prior_parameters);//
                        }*/
                    }
                    
					//quiz linked Aos
					$quizfaliedparticipants=array();
					if($this->AO_creation->ao_step1['link_quiz']=="on")
					{
						$quiz_obj=new Ep_Delivery_quizz();
						$failedlist=$quiz_obj->faliedcontribs($this->AO_creation->ao_step1['quiz']);
						
							for($f=0;$f<count($failedlist);$f++)
								$quizfaliedparticipants[]=$failedlist[$f]['user_id'];
					}
					
					//Normal Aos not linked to Poll
                    if($aoDetails[0]['poll_id']=="")
                    { 
                        $parameters['editobject']=$deldetails[0]['mailsubject'];
                        $parameters['editmessage']=$deldetails[0]['mailcontent'];
						$parameters['sender']=$deldetails[0]['mail_sender'];
						
						//Mail sent only if it selected Now
						if($this->AO_creation->ao_step3['publishnow']=="yes")
						{
							if($aoDetails[0]['AOtype']=='private')
							{
								$contributors=array_unique(explode(",",$aoDetails[0]['article_contribs']));
								if(is_array($contributors) && count($contributors)>0)
								{
									if($aoDetails[0]['premium_option']=='0')
										$automailid=88;
									else
										{
											$totalarticle=$this->AO_creation->ao_step1['total_article'];
											$single='yes';
											for($t=0;$t<$totalarticle;$t++)
											{
												
												if(count($this->AO_creation->ao_step2['favcontribcheck'][$t])>1)
													$single='no';
											}
											if($single=='yes')
												$automailid=128;	
											else
												$automailid=87;
										}
										
									foreach($contributors as $contributor)
									{
										if(!in_array($contributor,$quizfaliedparticipants))
										{
											if($this->AO_creation->ao_step1['testrequired']!="yes")
												$this->messageToEPMail($contributor,$automailid,$parameters);
											else
											{
												$userConb=new Ep_User_User();
												$contrib_details=$userConb->getContributordetails($contributor);
												if($contrib_details[0]['contributortest']=="yes" && ($this->AO_creation->ao_step1['testmarks']==NULL ||  $contrib_details[0]['contributortestmarks']>=$this->AO_creation->ao_step1['testmarks']))
													$this->messageToEPMail($contributor,$automailid,$parameters);
											}
										}
									}
								}
							}
							elseif($aoDetails[0]['AOtype']=='public')
							{
								if($this->AO_creation->ao_step3['publishnow']=="yes" && $darray["AOtype"]=="public" )
								{		
									//$filtercontributors=$this->AO_creation->ao_step3['mailcontribcheck'];	
									$del_obj = new Ep_Delivery_Delivery();
									$filtercontributors = $del_obj->getContribsByLang($this->AO_creation->ao_step1['publish_language'],$this->AO_creation->ao_step1['contribtype']);
									//print_r($filtercontributors);exit;
									if(is_array($filtercontributors) && count($filtercontributors)>0)
									{
										if($aoDetails[0]['premium_option']=='0')
											$mailId=86;//
										else
											$mailId=85;//
												
										foreach($filtercontributors as $contributor)
										{
											if(!in_array($contributor['identifier'],$quizfaliedparticipants))
											{
												if($this->AO_creation->ao_step1['testrequired']!="yes")
													$this->messageToEPMail($contributor['identifier'],$mailId,$parameters);
												else
												{
													if($contributor['contributortest']=="yes" && ($this->AO_creation->ao_step1['testmarks']==NULL ||  $contributor['contributortestmarks']>=$this->AO_creation->ao_step1['testmarks']))
														$this->messageToEPMail($contributor['identifier'],$mailId,$parameters);
												}
											}
										}
									}
								
								}
								else
								{
									$contributors=$ao_obj->getContributorsOfAllCategories('public',$aoDetails[0]['view_to']);	
										
									if(is_array($contributors) && count($contributors)>0)
									{
										if($aoDetails[0]['premium_option']=='0')
											$mailId=86;//
										else
											$mailId=85;//
												
										foreach($contributors as $contributor)
										{
											if(!in_array($contributor['identifier'],$quizfaliedparticipants))
											{
												if($this->AO_creation->ao_step1['testrequired']!="yes")
													$this->messageToEPMail($contributor['identifier'],$mailId,$parameters);
												else
												{
													$userConb=new Ep_User_User();
													$contrib_details=$userConb->getContributordetails($contributor['identifier']);
													if($contrib_details[0]['contributortest']=="yes" && ($this->AO_creation->ao_step1['testmarks']==NULL ||  $contrib_details[0]['contributortestmarks']>=$this->AO_creation->ao_step1['testmarks']))
														$this->messageToEPMail($contributor['identifier'],$mailId,$parameters);
												}
											}
										}
									}
								}
							}
							
							//FB & TWT posting
							/*if($this->AO_creation->ao_step3['fbcomment']!="")
							{
								require_once '/home/sites/site7/web/FO/postfb/facebook.php';
								require_once "/home/sites/site7/web/FO/tmhOAuth/tmhOAuth.php";
								
								//FB details
								$appId = $this->configval['fb_app_id'];
								$secret = $this->configval['fb_secret'];
								//$returnurl = 'http://localhost:8086/posttofb/';
								$permissions = 'publish_stream,offline_access';
								
								$fb = new Facebook(array("appId" => $appId, "secret" => $secret,'scope' => $permissions));
								
								//TWT details
								$tmhOAuth = new tmhOAuth(array(
								  'consumer_key' => $this->configval['twt_consumer_key'],
								  'consumer_secret' => $this->configval['twt_consumer_secret'],
								  'token' => $this->configval['twt_token'],
								  'secret' => $this->configval['twt_secret'],
								));
								
								$Isposted=$ao_obj->checkfbpost($this->AO_creation->ao_step1['client_list']);
								
								if($Isposted!="yes")
								{
									if($this->AO_creation->ao_step3['fbcomment']!="")
									{
										
										//FB posting
										$message = array(
													//'access_token'=>'CAACxflqXW94BAJ98HE6bGmtwoClekKGxSpYkqypF8LcQcJKWkPPZCUZAwkO0QkXYo67ceYrHjrn4ScrZA48KMyvBm2bHQIF3KVYCZB5SwR8nEjhwgEg2UZCDIpYHthaTuX9XKAv9w54j0PZA8jUGJUOgcb7x6pGi3AAxgt3MZAaK9zZC2ELMbs3Q',
													'access_token'=>$this->configval['fb_access_token'],
													'message'=>utf8_encode(stripslashes($this->AO_creation->ao_step3['fbcomment']))
													);
										
										$url='/237274402982745/feed';
										$result = $fb->api($url, 'POST', $message);
										
										//TWT posting
										$response = $tmhOAuth->request('POST', $tmhOAuth->url('1.1/statuses/update'), array(
										  'status' => utf8_encode(stripslashes($this->AO_creation->ao_step3['fbcomment'])
										)));
										
										$mail_text='<b>AO ID</b> : '.$delivery.'<br><br>
													<b>Title</b> : '.$this->AO_creation->ao_step1['title'].'<br><br>
													<b>Comment</b> : '.$this->AO_creation->ao_step3['fbcomment'];
										$mail = new Zend_Mail();
										$mail->addHeader('Reply-To','support@edit-place.com');
										$mail->setBodyHtml($mail_text)
											 ->setFrom('support@edit-place.com','Support Edit-place')
											 ->addTo('mailpearls@gmail.com')
											 //->addCc('kavithashree.r@gmail.com')
											 ->setSubject('FB & TWT posting Test Site');
										$mail->send();
										
									}
								}
								// update fbpost status
								$array['fbpost']='yes';
								$array['postoftheday']='yes';
								$where=" id='".$delivery."'";
								$ao_obj->updateDelivery($array,$where);
							}*/
						}
						//else
						//{
						//Mission comment insertion to Adcomments
							if($this->AO_creation->ao_step3['missioncomment']!="")
							{
								$comm_obj=new Ep_User_AdComments();
								$artids=$art_obj->getArticles($delivery);
								for($a=0;$a<count($artids);$a++)
								{
									$commentarray=array();
									$commentarray['user_id']=$this->adminLogin->userId;;
									$commentarray['type']="article";
									$commentarray['type_identifier']=$artids[$a]['id'];
									$commentarray['comments']=$this->AO_creation->ao_step3['missioncomment'];
									$comm_obj->InsertComment($commentarray);
								}
							}
						//}
					}
					/** Sending mail to correctors **/
					if($this->AO_creation->ao_step1['correction']=="on")
					{
						$parameterscorr['editobject']=$deldetails[0]['correctormailsubject'];
						$parameterscorr['editmessage']=$deldetails[0]['correctormailcontent'];
						$parameterscorr['sender']=$deldetails[0]['correctorsendfrom'];
							
						//Mail sent only if it selected Now
						if($this->AO_creation->ao_step3['publishnow']=="yes")
						{
							if($deldetails[0]['correctiontype']=="private")
							{
								$correctors=array_unique(explode(",",$aoDetails[0]['article_correctors']));
								if(is_array($correctors) && count($correctors)>0)
								{
									foreach($correctors as $corrector)
										$this->messageToEPMail($corrector,178,$parameterscorr);
								}
							}
							else
							{
								$correctorlist=$ao_obj->getCorrectorsByLang($aoDetails[0]['article_language']);
								foreach($correctorlist as $corr)
									$this->messageToEPMail($corr['identifier'],178,$parameterscorr);
							}
						}
					}
            }
			/* Unsetting all step session variables, only if duplicate mission is not checked*/
			if($this->AO_creation->ao_step3['duplicateao']!='yes')
			{
				unset($this->AO_creation->ao_step1);
				unset($this->AO_creation->ao_step2);
				unset($this->AO_creation->ao_step3);
			}
				
		$this->_view->render("ao_aocreate4");
	}
	
	/* Uploading xls/xlsx for article titles in step2 */
	public function uploadartxlsAction()
	{
		$realfilename=$_FILES['excelfile']['name'];
		$ext=$this->findexts($realfilename);
		if($ext=="xls" || $ext=="xlsx")
		{
			$uploaddir = APP_PATH_ROOT.'client_excel/';
		}

		$this->AO_creation = Zend_Registry::get('AO_creation');
		$client_id=$this->AO_creation->ao_step1['client_list'];
		$newfilename=$client_id.".".$ext;

		if(!is_dir($uploaddir.$client_id))
		{
			mkdir($uploaddir.$client_id,0777);
		    chmod($uploaddir.$client_id,0777);
		}

		$uploaddir=$uploaddir.$client_id."/";
		$file = $uploaddir . basename($realfilename,".".$ext)."_".uniqid().".".$ext;

		if (move_uploaded_file($_FILES['excelfile']['tmp_name'], $file))
		{
			chmod($file,0777);
			//$exefile=$this->writecsv($file);
			//$char =$this->readcsv($file);
			//echo $char;
			//unlink($exefile);
			if($ext=="xlsx")
			{
				require_once APP_PATH_ROOT.'nlibrary/tools/PHPExcel.php';
				$objReader = PHPExcel_IOFactory::createReader('Excel2007');
				$objReader->setReadDataOnly(true);
				$objPHPExcel = $objReader->load($file);
				
				
				$sheetData=array();
				foreach ($objPHPExcel->getWorksheetIterator() as $objWorksheet) {
					foreach ($objWorksheet->getCellCollection(false) as $cellID) {
					$sheetData[]=$objPHPExcel->getActiveSheet()->getCell($cellID)->getvalue();
				 }
				}
			}
			else
			{
				include 'reader.php';
    
				// initialize reader object
				$excel = new Spreadsheet_Excel_Reader();
				$excel->setOutputEncoding('Windows-1252');//echo $file;exit;
				// read spreadsheet data
				$excel->read($file); 
				
				$x=2;
				$sheetData=array();
				
				while($x<=$excel->sheets[0]['numRows']) {
					
					$sheetData[] = isset($excel->sheets[0]['cells'][$x][1]) ? iconv("ISO-8859-1", "UTF-8", $excel->sheets[0]['cells'][$x][1]) : '';
					$x++;
				}
			}
			echo implode('@@', $sheetData);;
			exit;
		}
		else
		{
			echo "error";
		}
	}
	
	/* Function to Read csv file */
	//not in use
	public function readcsv($filename)
	{
		include 'reader.php';
    
		// initialize reader object
		$excel = new Spreadsheet_Excel_Reader();
		$excel->setOutputEncoding('Windows-1252');
		// read spreadsheet data
		$excel->read($filename); 
		
		$x=1;
		$sub_array=array();
		//return print_r($excel->sheets[0]['cells'][1][1]);exit;
		while($x<=$excel->sheets[0]['numRows']) {
		 	
			$sub_array[] = isset($excel->sheets[0]['cells'][$x][1]) ? iconv("ISO-8859-1", "UTF-8", $excel->sheets[0]['cells'][$x][1]) : '';
			$x++;
		}
		
		return implode('@@', $sub_array);;
	}

	/* Function to write csv file */
	//not in use
	public function writecsv($fname)
	{
		$fh = fopen($fname, 'r') or die("can't open file");

		//$fname2=basename($fname,".xls");
		//$downlpath=APP_PATH_ROOT.'client_excel/'.$client_id.'/'.$fname2.'_1.xls';
		$sub_array=array();
		
		//$fp = fopen($downlpath, 'w') or die("can't open file");
		 while (($buffer = fgets($fh, 4096)) !== false) {
			$sub_array[]=$buffer;
			//fwrite($fp, $stringData);
		}
		fclose($fh);
		//fclose($fp);

		//unlink($fname);
		return implode('@@', $sub_array);;
	}
	
	/* Ajax call to update quizz list in AO creation step1*/
	public function updatequizlistAction()
	{
		$this->AO_creation = Zend_Registry::get('AO_creation');
		
		$quiz_obj=new Ep_Delivery_quizz();
		$quiz_list=$quiz_obj->ListQuizzbyCategory($_REQUEST['category']);
		$quizlist.='<select name="quiz" id="quiz" onChange="loadquizvals();"><option value="">Select</option>';
			
			if($this->AO_creation->ao_step1['quiz']!="" && $_REQUEST['parameter']=="1")	
			{
				for($q1=0;$q1<count($quiz_list);$q1++)
				{
					if($quiz_list[$q1]['id']==$this->AO_creation->ao_step1['quiz'])
						$quizlist.='<option value="'.$quiz_list[$q1]['id'].'" selected>'.$quiz_list[$q1]['title'].'</option>';
					else
						$quizlist.='<option value="'.$quiz_list[$q1]['id'].'" >'.$quiz_list[$q1]['title'].'</option>';
				}
			}
			else
			{
				for($q2=0;$q2<count($quiz_list);$q2++)
				{
					$quizlist.='<option value="'.$quiz_list[$q2]['id'].'">'.$quiz_list[$q2]['title'].'</option>';
				}
			}
		$quizlist.='</select>';
			
		echo $quizlist;
	}
	
	/* Ajax call to update quizz values on change quizz list in AO creation step1 */
	public function getquizvalsAction()
	{
		$quiz_obj=new Ep_Delivery_quizz();
		$quizdetails=$quiz_obj->quizzdetails($_REQUEST['quizid']);
		$duration=explode("|",$quizdetails[0]['setuptime']);
		echo trim($quizdetails[0]['correct_ans_count']).'#'.$duration[0].'#'.$duration[1];
		exit;
	}
	
	/* Ajax call to update private contributors list in step1 onchange of language, category or contributor type*/
	public function updatecontriblistAction()
	{
		$this->AO_creation = Zend_Registry::get('AO_creation');
		
		$del_obj = new Ep_Delivery_Delivery();
		$contriblistall = $del_obj->getContribsByType($_REQUEST['type'],$_REQUEST['category'],$_REQUEST['language']);
		$contriblistall1=array();
			for ($i=0;$i<count($contriblistall);$i++)
				{
					$contriblistall1[]=$contriblistall[$i];
				    $name=$contriblistall1[$i]['email'];
					$nameArr=array($contriblistall1[$i]['first_name'],$contriblistall1[$i]['last_name']);
					$nameArr=array_filter($nameArr);
					if(count($nameArr)>0)
						$name.=" (".implode(", ",$nameArr).")";
					$contriblistall1[$i]['name']=strtoupper($name);
				}
		
		//$contriblist=':';
		
		$contriblist.='<select multiple="multiple" name="favcontribcheck[]" id="favcontribcheck">';
			
			if($this->AO_creation->ao_step1['favcontribcheck']!="" && $_REQUEST['parameter']=="1")	
			{
				foreach ($contriblistall1 as $contrib)
				{
					if(in_array($contrib['identifier'],$this->AO_creation->ao_step1['favcontribcheck']))
						$contriblist.='<option value="'.$contrib['identifier'].'" selected>'.utf8_encode($contrib['name']).'</option>';
					else
						$contriblist.='<option value="'.$contrib['identifier'].'" >'.utf8_encode($contrib['name']).'</option>';
				}
			}
			else
			{
				foreach ($contriblistall1 as $contrib)
				{
					$contriblist.='<option value="'.$contrib['identifier'].'" selected>'.utf8_encode($contrib['name']).'</option>';
				}
			}
		$contriblist.='</select>
			<div id="favcontrib_err" style="color:red;"></div>';
			
		echo $contriblist;
	}
	
	/* Ajax call to update private corrector list in step1 onchange of article language*/
	public function updatecorrectorlistAction()
	{
		$this->AO_creation = Zend_Registry::get('AO_creation');
		
		$del_obj = new Ep_Delivery_Delivery();
		$contriblistall = $del_obj->getCorrectorsByLang($_REQUEST['language']);
		$contriblistall1=array();
			for ($i=0;$i<count($contriblistall);$i++)
				{
					$contriblistall1[]=$contriblistall[$i];
				    $name=$contriblistall1[$i]['email'];
					$nameArr=array($contriblistall1[$i]['first_name'],$contriblistall1[$i]['last_name']);
					$nameArr=array_filter($nameArr);
					if(count($nameArr)>0)
						$name.=" (".implode(", ",$nameArr).")";
					$contriblistall1[$i]['name']=strtoupper($name);
				}
		
		//$contriblist=':';
		
		$contriblist.='<select multiple="multiple" name="correctorcheck[]" id="correctorcheck">';
			
			if($this->AO_creation->ao_step1['correctorcheck']!="" && $_REQUEST['parameter']=="1")	
			{
				foreach ($contriblistall1 as $contrib)
				{
					if(in_array($contrib['identifier'],$this->AO_creation->ao_step1['correctorcheck']))
						$contriblist.='<option value="'.$contrib['identifier'].'" selected>'.utf8_encode($contrib['name']).'</option>';
					else
						$contriblist.='<option value="'.$contrib['identifier'].'" >'.utf8_encode($contrib['name']).'</option>';
				}
			}
			else
			{
				foreach ($contriblistall1 as $contrib)
				{
					$contriblist.='<option value="'.$contrib['identifier'].'" selected>'.utf8_encode($contrib['name']).'</option>';
				}
			}
		$contriblist.='</select>
			<div id="corrcontrib_err" style="color:red;"></div>';
			
		echo $contriblist;
	}
	
	public function updatecontriblistarticleAction()
	{
		$this->AO_creation = Zend_Registry::get('AO_creation');
		
		$del_obj = new Ep_Delivery_Delivery();
		$type=implode("','",$this->AO_creation->ao_step1['contribtype']);
		
		//$contriblistall = $del_obj->getContribsByType($type,$_REQUEST['category'],$_REQUEST['language']);
		if($this->AO_creation->ao_step1['testrequired']=='yes')
			$contriblistall = $del_obj->getContribsByTypeTest($type,$_REQUEST['category'],$_REQUEST['language'],$this->AO_creation->ao_step1['testrequired'],$this->AO_creation->ao_step1['testmarks']);
		else
			$contriblistall = $del_obj->getContribsByType($type,$_REQUEST['category'],$_REQUEST['language']);
			
		$index=$_REQUEST['index']-1;
		$contriblistall1=array();
			for ($i=0;$i<count($contriblistall);$i++)
				{
					$contriblistall1[]=$contriblistall[$i];
				    $name=$contriblistall1[$i]['email'];
					$nameArr=array($contriblistall1[$i]['first_name'],$contriblistall1[$i]['last_name']);
					$nameArr=array_filter($nameArr);
					if(count($nameArr)>0)
						$name.=" (".implode(", ",$nameArr).")";
					$contriblistall1[$i]['name']=strtoupper($name);
				}
		
		//$contriblist=':';
		
		$contriblist.='<select multiple="multiple" name="favcontribcheck['.$index.'][]" id="favcontribcheck_'.$_REQUEST['index'].'" class="favcontribcheck">';
			
		if(isset($this->AO_creation->ao_step2) && $_REQUEST['param']==1)
		{	
			foreach ($contriblistall1 as $contrib)
			{
				if(in_array($contrib['identifier'],$this->AO_creation->ao_step2['favcontribcheck'][$index]))
					$contriblist.='<option value="'.$contrib['identifier'].'" selected>'.utf8_encode($contrib['name']).'</option>';
				else
					$contriblist.='<option value="'.$contrib['identifier'].'">'.utf8_encode($contrib['name']).'</option>';
			}
		}
		elseif($_REQUEST['param']==1)
		{	
			foreach ($contriblistall1 as $contrib)
			{
				if(in_array($contrib['identifier'],$this->AO_creation->ao_step1['favcontribcheck']))
					$contriblist.='<option value="'.$contrib['identifier'].'" selected>'.utf8_encode($contrib['name']).'</option>';
				else
					$contriblist.='<option value="'.$contrib['identifier'].'">'.utf8_encode($contrib['name']).'</option>';
			}
		}
		else
		{
			foreach ($contriblistall1 as $contrib)
			{
				$contriblist.='<option value="'.$contrib['identifier'].'" selected>'.utf8_encode($contrib['name']).'</option>';
			}
		}
		$contriblist.='</select>
			<div id="contriberr_'.$_REQUEST['index'].'" style="color:red;"></div>';
			
		echo $contriblist;
	}
	
	/* Ajax call to update private contributors list in step1 onchange of language & category, if mission test */
	public function updatecontriblisttestmAction() 
	{
		$this->AO_creation = Zend_Registry::get('AO_creation');
		
		$del_obj = new Ep_Delivery_Delivery();
		$type=implode("','",$this->AO_creation->ao_step1['contribtype']);
		$contriblistall = $del_obj->getContribsByType($type,$_REQUEST['category'],$_REQUEST['language']);
		$index=$_REQUEST['index']-1;
		$contriblistall1=array();
			for ($i=0;$i<count($contriblistall);$i++)
				{
					$contriblistall1[]=$contriblistall[$i];
				    $name=$contriblistall1[$i]['email'];
					$nameArr=array($contriblistall1[$i]['first_name'],$contriblistall1[$i]['last_name']);
					$nameArr=array_filter($nameArr);
					if(count($nameArr)>0)
						$name.=" (".implode(", ",$nameArr).")";
					$contriblistall1[$i]['name']=strtoupper($name);
				}
		//$contriblist=':';
		$contriblist.='<select name="favcontribcheck['.$index.'][]" id="favcontribcheck_'.$_REQUEST['index'].'" class="singleselect" style="width:auto;">';
			
			foreach ($contriblistall1 as $contrib)
			{
				$contriblist.='<option value="'.$contrib['identifier'].'" selected>'.utf8_encode($contrib['name']).'</option>';
			}
			
		$contriblist.='</select>
			<div id="contriberr_'.$_REQUEST['index'].'" style="color:red;"></div>';
			
		echo $contriblist;
	}
	
	/*loading Writers selection list in step3 for sending mails based on ao language & category */
	//not in use
	public function mailcontriblistAction()
	{
		$this->AO_creation = Zend_Registry::get('AO_creation');
		
		$del_obj = new Ep_Delivery_Delivery();
		$contriblistall = $del_obj->getContribsByLang($_REQUEST['language'],$this->AO_creation->ao_step1['contribtype']);
		$contriblistall1=array();
			for ($i=0;$i<count($contriblistall);$i++)
				{
					$contriblistall1[]=$contriblistall[$i];
				    $name=$contriblistall1[$i]['email'];
					$nameArr=array($contriblistall1[$i]['first_name'],$contriblistall1[$i]['last_name']);
					$nameArr=array_filter($nameArr);
					if(count($nameArr)>0)
						$name.=" (".implode(", ",$nameArr).")";
					$contriblistall1[$i]['name']=strtoupper($name);
				}
	
		$contriblist.='<select multiple="multiple" name="mailcontribcheck[]" id="mailcontribcheck">';
			if($this->AO_creation->ao_step1['mailcontribcheck']!="" && $_REQUEST['parameter']=="1")	
			{
				foreach ($contriblistall1 as $contrib)
				{
					if(in_array($contrib['identifier'],$this->AO_creation->ao_step1['mailcontribcheck']))
						$contriblist.='<option value="'.$contrib['identifier'].'" selected>'.utf8_encode($contrib['name']).'</option>';
					else
						$contriblist.='<option value="'.$contrib['identifier'].'" >'.utf8_encode($contrib['name']).'</option>';
				}
			}
			else
			{
				foreach ($contriblistall1 as $contrib)
				{
					$contriblist.='<option value="'.$contrib['identifier'].'" selected>'.utf8_encode($contrib['name']).'</option>';
				}
			}
		$contriblist.='</select><div id="mailcontrib_err" style="color:red;"></div>';
		echo $contriblist;
	}  
	
	/* Update contributors count in contributor type field, based on language*/
	public function updatecontribtypeAction() 
	{
		$this->AO_creation = Zend_Registry::get('AO_creation');
		$contrib_obj = new EP_User_Contributor();
		$seniorcount=$contrib_obj->getContributorcountwithLang('senior',$_REQUEST['language']);
		$juniorcount=$contrib_obj->getContributorcountwithLang('junior',$_REQUEST['language']);
		$sjcount=$contrib_obj->getContributorcountwithLang('sub-junior',$_REQUEST['language']);
			
		echo $seniorcount."#".$juniorcount."#".$sjcount;
	}
	
	/* To load poll list in ao creation step1, if it is opted to link a poll for the ao */
	public function aolinkpollAction()
	{
		$this->AO_creation = Zend_Registry::get('AO_creation');
		$poll_obj = new Ep_Delivery_Poll();
		$content='';
		if($_REQUEST['client']!="") //Loading Polls
		{
			$poll_list=$poll_obj->clientpolls($_REQUEST['client']);
			if(count($poll_list)>0)
			{
			$content.='<select name="pollao" id="pollao" onChange="loadpollcontent();" style="width:250px;">';
				$content.='<option value="">Select</option>';
					for($p=0;$p<count($poll_list);$p++)
					{
						if($poll_list[$p]['id']==$this->AO_creation->ao_step1['pollao'])
							$content.='<option value="'.$poll_list[$p]['id'].'" selected>'.utf8_encode($poll_list[$p]['title']).'</option>';
						else
							$content.='<option value="'.$poll_list[$p]['id'].'">'.utf8_encode($poll_list[$p]['title']).'</option>';
					}
			$content.='</select>';
			}
			else
				$content.='No Polls for this Client';
			echo $content;
		}
		elseif($_REQUEST['poll']!="") // Loading other data on selection of Poll
		{
			$poll_contrib=$poll_obj->ListPollPartcipation($_REQUEST['poll'],'');
			$poll_detail=$poll_obj->getPolldetails($_REQUEST['poll']);
			$content.='<div style="min-height:auto;max-height:200px;overflow:auto;margin-right:6px;overflow-x:hidden">';
				for($pc=0;$pc<count($poll_contrib);$pc++)
				{
					$file = "/home/sites/site7/web/FO/profiles/contrib/pictures/".$poll_contrib[$pc]['user_id']."/".$poll_contrib[$pc]['user_id']."_h.jpg";
						if(file_exists($file))
							$poll_contrib[$pc]['contrib_home_picture']="/FO/profiles/contrib/pictures/".$poll_contrib[$pc]['user_id']."/".$poll_contrib[$pc]['user_id']."_h.jpg";
                        else
                            $poll_contrib[$pc]['contrib_home_picture']="/FO/images/Contrib/profile-img-def.png";
					$contrib_name=utf8_encode($poll_contrib[$pc]['first_name']).'&nbsp;'.utf8_encode(substr($poll_contrib[$pc]['last_name'],0,1));
					
						$content.='<div><label class="uni-checkbox">';
									if(count($this->AO_creation->ao_step1['priorcontrib'])>0)
									{
										if(in_array($poll_contrib[$pc]['user_id'],$this->AO_creation->ao_step1['priorcontrib']))
											$content.='<input type="checkbox" name="priorcontrib[]" id="check_'.$poll_contrib[$pc]['user_id'].'" value="'.$poll_contrib[$pc]['user_id'].'" checked class="uni_style"/>'; 
										else
											$content.='<input type="checkbox" name="priorcontrib[]" id="check_'.$poll_contrib[$pc]['user_id'].'" value="'.$poll_contrib[$pc]['user_id'].'" class="uni_style"/>';
									}
									else
										$content.='<input type="checkbox" name="priorcontrib[]" id="check_'.$poll_contrib[$pc]['user_id'].'" value="'.$poll_contrib[$pc]['user_id'].'"  checked class="uni_style"/>';
					$content.='<b>'.$contrib_name.'('.$poll_contrib[$pc]['price_user'].')</b></div>
								</label></div>';
				}
			$content.='</div>';
			if(count($poll_contrib)==0)
				$content='Select Poll';
			if($this->AO_creation->ao_step1['tender_name'])
				$title=utf8_encode($this->AO_creation->ao_step1['tender_name']);
			else
				$title=utf8_encode($poll_detail[0]['title']);
			if($this->AO_creation->ao_step1['priority_hours'])
				$hours=$this->AO_creation->ao_step1['priority_hours'];
			else
				$hours=$poll_detail[0]['priority_hours'];
			
			if($this->AO_creation->ao_step1['contrib_value'])
				$percent=$this->AO_creation->ao_step1['contrib_value'];
			else
				$percent=$poll_detail[0]['contrib_percentage'];
				
			echo $content.'#'.$title.'#'.$hours.'#'.$percent;
		}
	}
	
	/* Function to upload spec file in ao creation step1*/
	public function uploadaospecAction()
	{
		$this->AO_creation = Zend_Registry::get('AO_creation');
		$realfilename=$_FILES['uploadfile']['name'];//echo $realfilename;exit;
		$ext=$this->findexts($realfilename);
		
		$uploaddir = '/home/sites/site7/web/FO/client_spec/';
		//echo $this->AO_creation->client_id;exit;
		$client_id=$this->AO_creation->client_id;
		$newfilename=$client_id.".".$ext;
		if(!is_dir($uploaddir.$client_id))
		{
			mkdir($uploaddir.$client_id,0777);
			chmod($uploaddir.$client_id,0777);
		}
		$uploaddir=$uploaddir.$client_id."/";
		$realfilename=trim($realfilename);
		$realfilename=str_replace(" ","_",$realfilename);
		
		$bname=basename($realfilename,".".$ext)."_".uniqid().".".$ext;
		$file = $uploaddir . $bname;
		if (move_uploaded_file($_FILES['uploadfile']['tmp_name'], $file))
		{
			chmod($file,0777);
			echo "success#".$bname;
		}
		else
		{
			echo "error";
		}
	}
	
	/* Function to upload correction spec file in ao creation step1 */
	public function uploadcorrspecAction()
	{
		$this->AO_creation = Zend_Registry::get('AO_creation');
		$realfilename=$_FILES['corrfile']['name'];
		$ext=$this->findexts($realfilename);
		
		//destination folder path to save spec file
		$uploaddir = '/home/sites/site7/web/FO/correction_spec/';
		
		$client_id=$this->AO_creation->client_id;
		$newfilename=$client_id.".".$ext;
		$client_corrector_spec_folder=$uploaddir.$client_id;
		if(!is_dir($client_corrector_spec_folder))
		{
			mkdir($client_corrector_spec_folder,0777);
		    chmod($client_corrector_spec_folder,0777);
		}
		
		$uploaddir=$uploaddir.$client_id."/";
		$realfilename=str_replace(" ","_",$realfilename);
		$bname=basename($realfilename,".".$ext)."_".uniqid().".".$ext;
		echo $file = $uploaddir.$bname;
		
		if (move_uploaded_file($_FILES['corrfile']['tmp_name'], $file))
		{
			$this->AO_creation->ao_step3['correction_spec_file']=$bname;
			chmod($file,0777);
			echo "success#".$bname;
		}
		else
		{
			echo "error";
		}
	}
	
	/* Function to get mail content in ao creation step3 based on mission liberte/premium and mission public/private */
	public function getcontribmailcontentAction()
	{
		$this->AO_creation = Zend_Registry::get('AO_creation');
		$automail=new Ep_Message_AutoEmails();
		$user_obj=new Ep_User_Client();
		$detailsC=$user_obj->getClientName($this->AO_creation->ao_step1['client_list']);
		$mailid="";
		
			if($this->AO_creation->ao_step1['AOtype']=="private")
			{
				if($this->AO_creation->ao_step1['missiontest']=="on")
					$mailid=103;	
				else
				{
					//if the mission is liberte, else premium
					if($this->AO_creation->ao_step1['mission_type']=="liberte")
					{
						$mailid=88;	
						if($this->AO_creation->ao_step1['deli_anonymous']=="0")
							$client="<b>".$detailsC[0]['company_name']."</b>";
						else
							$client="<b>le client</b>";
					}
					else
					{
						$totalarticle=$this->AO_creation->ao_step1['total_article'];
						$single='yes';
						for($t=0;$t<$totalarticle;$t++)
						{
							
							if(count($this->AO_creation->ao_step2['favcontribcheck'][$t])>1)
								$single='no';
						}
						if($single=='yes')
							$mailid=128;	
						else
							$mailid=87;
					}
				}
			}
			else
			{
					if($this->AO_creation->ao_step1['mission_type']=="liberte")
					{
						if($_REQUEST['now']=='yes')
							$mailid=86;
						else
							$mailid=90;
							
						if($this->AO_creation->ao_step1['deli_anonymous']=="0")
							$client="<b>".$detailsC[0]['company_name']."</b>";
						else
							$client="<b>le client</b>";
					}
					else
					{
						if($_REQUEST['now']=='yes')
							$mailid=85;
						else
							$mailid=89;
					}	
			}
		
			
        $email=$automail->getAutoEmail($mailid);
		
		//If Delivery is published immediately 
		//$parameters['submitdate_bo']=date('d/m/Y H:i', $expires);
		if($_REQUEST['now']=='yes')
		{
			$expires=time()+(60*$this->AO_creation->ao_step1['participation_time']);
			$submitdate_bo="<b>".strftime("%d/%m/%Y %I:%M %p",$expires)."</b>";
		}
		else
		{
			$_REQUEST['time'] = str_replace("/","-",$_REQUEST['time']);
			$expires=strtotime($_REQUEST['time'])+(60*$this->AO_creation->ao_step1['participation_time']);
			//$expires+=60*60*24;
			$submitdate_bo="<b>".strftime("%d/%m/%Y  %I:%M %p",$expires)."</b>";
		}
		$aowithlink='<a href="http://ep-test.edit-place.co.uk/contrib/aosearch">'.stripslashes($this->AO_creation->ao_step1['title']).'</a>';
		$sub=$email[0]['Object'];
		$Message=$email[0]['Message'];
		eval("\$Message= \"$Message\";");
		
		$subcon=$sub."#".$Message;
		//echo utf8_encode($subcon);
		
		//Corrector email in simultaneous correction case
		if($this->AO_creation->ao_step1['correction']=="on")
		{
			if($this->AO_creation->ao_step1['missiontest']=="on")
				$correctoremail=$automail->getAutoEmail('104');	
			else
				$correctoremail=$automail->getAutoEmail('178');
			
			if($_REQUEST['now']=='yes')
			{
				$correxpires=time()+(60*$this->AO_creation->ao_step1['correction_participation']);
				$submit_hours="<b>".strftime("%d/%m/%Y &agrave; %H:%M",$correxpires)."</b>";
			}
			else
			{
				$_REQUEST['time'] = str_replace("/","-",$_REQUEST['time']);
				$correxpires=strtotime($_REQUEST['time'])+(60*$this->AO_creation->ao_step1['correction_participation']);
				$submit_hours="<b>".strftime("%d/%m/%Y &agrave; %H:%M",$correxpires)."</b>";
			}
			$article='<b>'.stripslashes(utf8_encode($this->AO_creation->ao_step1['title'])).'</b>';
			$corrector_ao_link='<a href="http://ep-test.edit-place.com/contrib/aosearch">Cliquant-ici</a>';
			
			//calculating max_reception_writer_file_date_hour
				$max=max($this->AO_creation->ao_step1['subjunior_time'], $this->AO_creation->ao_step1['junior_time'], $this->AO_creation->ao_step1['senior_time']);
				
				if($this->AO_creation->ao_step1['submit_option']=="day")
					$submittime=24 * 60 * 60 * $max;
				elseif($this->AO_creation->ao_step1['submit_option']=="hour") 
					$submittime=60 * 60 * $max;
				else
					$submittime=60 * $max;
			$max_reception_writer_file_date_hour="<b>".strftime("%d/%m/%Y &agrave; %H:%M",($expires+$submittime))."</b>"; ;
			
			$corrsub=stripslashes(utf8_encode($correctoremail[0]['Object']));
			$corrMessage=stripslashes(utf8_encode($correctoremail[0]['Message']));
			eval("\$corrMessage= \"$corrMessage\";");
			
			 $subcon.="#".$corrsub."#".$corrMessage;
		}
		
		echo $subcon;
		//echo $mailid;
		
	}
	
	/* Function to load validation template/refusal reason checkboxes in ao creation step1 */
	public function loadvalidationtemplatesAction()
	{
		$this->AO_creation = Zend_Registry::get('AO_creation');
		
		$template_obj=new Ep_Message_Template();
		$templatelist=$template_obj->getActiveValidationtemplates($_REQUEST['validationtype']);
		
		$blocktext='';
		if($templatelist!="NO")
		{
			$blocktext='<table cellpadding="5" cellspacing="5" width="100%">
				<tr><td colspan="2">Merci de s&eacute;lectionner entre 1 et '.$this->configval['refusal_reasons_max'].' crit&egrave;res de notation parmi les suivants : </td></tr>';
			
			if($_REQUEST['validationtype']=="redaction")
				$variable='redactionrefusal';
			else
				$variable='translationrefusal';
			
			if($_REQUEST['mode']==1)
				$refusalarray=$this->AO_creation->ao_step1[$variable]; 
			else
				$refusalarray=array();
			//print_r($refusalarray);exit;
			for($t=0;$t<count($templatelist);$t++)
			{
				$checked="";
				if($_REQUEST['mode']==1)
				{
					if(in_array($templatelist[$t]['identifier'],$refusalarray))
						$checked="checked";
				}
				else
				{
					if($templatelist[$t]['selected']=="yes")
						$checked="checked";
				}
				if($t%2==0)
					$blocktext.='<tr>';
				$blocktext.='<td width="50%"><input type="checkbox" name="'.$variable.'[]" value="'.$templatelist[$t]['identifier'].'" '.$checked.' class="'.$variable.'"/> '.utf8_encode($templatelist[$t]['title']).'</td>';
				if($t%2!=0)
					$blocktext.='</tr>';
			}
			$blocktext.='</table>';
		}
		echo $blocktext; 
	}
	
	/**********************************************Mail code ****************************************/
	public function messageToEPMail($receiverId,$mailid,$parameters)
    {
        $automail=new Ep_Message_AutoEmails();
        $sc_limit='<b>'.$parameters['sc_limit'].'</b>';
        $jc_limit='<b>'.$parameters['jc_limit'].'</b>';
        $AO_title="<b>".$parameters['AO_title']."</b>";
        $AO_end_date='<b>'.$parameters['AO_end_date'].'</b>';
        $submitdate_bo="<b>".$parameters['submitdate_bo']."</b>";
        $articlewithlink='<a href="'.$parameters['articlename_link'].'">'.stripslashes($parameters['article_title']).'</a>';
        $aowithlink='<a href="'.$parameters['aoname_link'].'">'.stripslashes($parameters['AO_title']).'</a>';
		$poll_link='<a href="http://ep-test.edit-place.co.uk/client/emaillogin?user='.$user.'&hash='.$password.'&type='.$type.'&poll='.$parameters['poll'].'">here</a>';
		$client_polllink='<a href="http://ep-test.edit-place.co.uk/client/devispremium?id='.$parameters['poll'].'">Click here</a>';
		$category=$parameters['category'];
		$poll_title='<b>'.$parameters['poll_title'].'</b>';
		$poll_enddate='<b>'.$parameters['poll_enddate'].'</b>';
        $clientcomment=$parameters['clientcomment'];
		$pollcategory='<b>'.$parameters['pollcategory'].'</b>';
        $poll_title='<b>'.$parameters['poll_title'].'</b>';
        $poll_enddate='<b>'.$parameters['poll_enddate'].'</b>';
        $articleclient_link  = '<a href="'.$parameters['clientartname_link'].'">'.stripslashes($parameters['AO_title']).'</a>';
        $client_link = '<a href="'.$parameters['clientartname_link'].'">Click here</a>';
		
        $email=$automail->getAutoEmail($mailid);
        
		if($parameters['editobject']!="")
			$Object=$parameters['editobject'];
		else
			$Object=$email[0]['Object'];
		
		$Object=strip_tags($Object);
        eval("\$Object= \"$Object\";");
        if($parameters['editmessage']!="")
		{
			$Message=$parameters['editmessage'];
		}
		else
		{
			$Message=$email[0]['Message'];
			eval("\$Message= \"$Message\";");
		}
		
		/**Inserting into EP mail Box**/
           $this->sendMailEpMailBox($receiverId,$Object,$Message,$parameters['sender']);
    }
	public function sendMailEpMailBox($receiverId,$object,$content,$senderfrom=NULL)
    {
        $sender=$this->adminLogin->userId;
        $sender='111201092609847';
        $ticket=new Ep_Message_Ticket();
         if($this->AO_creation->ao_step3['sendfrom']=='me')
			$ticket->sender_id=$this->adminLogin->userId;
		else
			$ticket->sender_id=$sender;
        $ticket->recipient_id=$receiverId;
        $ticket->title=$object;
        $ticket->status='0';
        $ticket->created_at=date("Y-m-d H:i:s", time());
        try
        {
            if($ticket->insert())
               {
                    $ticket_id=$ticket->getIdentifier();
                    $message=new Ep_Message_Message();
                    $message->ticket_id=$ticket_id;
                    $message->content=$content;
                    $message->type='0' ;
                    $message->status='0';
                    $message->created_at=$ticket->created_at;
                    $message->approved='yes';
                     if($this->AO_creation->ao_step3['sendfrom']=='me')
						$message->auto_mail='no';
					else
						$message->auto_mail='yes';
                    $message->insert();
					$messageId=$message->getIdentifier();
					$automail=new Ep_Message_AutoEmails();
					$UserDetails=$automail->getUserType($receiverId);
                    $email=$UserDetails[0]['email'];
                    $password=$UserDetails[0]['password'];
                    $type=$UserDetails[0]['type'];
					$this->mail_from= $this->configval["mail_from"];
					if(!$object)
                     $object="Vous avez re&ccedil;uu un email-Edit-place";
					$object=strip_tags($object);
                    if($UserDetails[0]['type']=='client')
					{
						$text_mail="<p>Dear client,<br><br>
										You have received an email from Edit-place&nbsp;!<br><br>
										Thank you <a href=\"http://ep-test.edit-place.co.uk/user/email-login?user=".MD5('ep_login_'.$email)."&hash=".MD5('ep_login_'.$password)."&type=".$type."&message=".$messageId."&ticket=".$ticket_id."\">click here</a> to Read.<br><br>
										Yours sincerely,<br>
										<br>
										The Edit-place team<br><br>
										You do not wish to receive notifications ? <a href=\"http://ep-test.edit-place.co.uk/user/alert-unsubscribe?uaction=unsubscribe&user=".MD5('ep_login_'.$email)."\">Click here</a>.</p>"
									;
					}
					else if($UserDetails[0]['type']=='contributor')
					{
						$text_mail="<p>Dear writer,<br><br>
										You have received an email from Edit-place&nbsp;!<br><br>
										Thank you <a href=\"http://ep-test.edit-place.co.uk/user/email-login?user=".MD5('ep_login_'.$email)."&hash=".MD5('ep_login_'.$password)."&type=".$type."&message=".$messageId."&ticket=".$ticket_id."\">click here</a> to Read.<br><br>
										Yours sincerely,<br>
										<br>
										The Edit-place team<br><br>
										You do not wish to receive notifications ? <a href=\"http://ep-test.edit-place.co.uk/user/alert-unsubscribe?uaction=unsubscribe&user=".MD5('ep_login_'.$email)."\">Click here</a>.</p>"
									;
					}
				
						//if($this->AO_creation->ao_step3['sendfrom']=='me')
					//{
					if($UserDetails[0]['alert_subscribe']=='yes')
					{
						
						if($senderfrom=='me')
						{
							$from=$this->adminLogin->loginEmail;
							$user_obj=new Ep_User_User();
							$todetail=$user_obj->getEmailUser($this->adminLogin->userId);
							if($todetail[0]['first_name']!="")
								$fromname=$todetail[0]['first_name'].' '.$todetail[0]['last_name'];
							else
								$fromname=$todetail[0]['login'];
							
								$fromURL="http://ep-test.edit-place.co.uk/contrib/aosearch";
								$toURL="http://ep-test.edit-place.co.uk/user/email-login?user=".MD5('ep_login_'.$email)."&hash=".MD5('ep_login_'.$password)."&type=".$type."&red_to=aosearch";
							$content=str_replace($fromURL,$toURL,$content);
							$content.="<br><br>You do not wish to receive notifications ? <a href=\"http://ep-test.edit-place.co.uk/user/alert-unsubscribe?uaction=unsubscribe&user=".MD5('ep_login_'.$email)."\">Click here</a>";
							$mailbody=$content;
						}
						elseif($this->AO_creation->ao_step3['sendfrom']=='editorial')
						{
							$from=$this->mail_from;
							$fromname='Support Edit-place';
							
								$fromURL="http://ep-test.edit-place.co.uk/contrib/aosearch";
								$toURL="http://ep-test.edit-place.co.uk/user/email-login?user=".MD5('ep_login_'.$email)."&hash=".MD5('ep_login_'.$password)."&type=".$type."&red_to=aosearch";
							$content=str_replace($fromURL,$toURL,$content);
							$content.="<br><br>
										You do not wish to receive notifications ? <a href=\"http://ep-test.edit-place.co.uk/user/alert-unsubscribe?uaction=unsubscribe&user=".MD5('ep_login_'.$email)."\">Click here</a>";
							$mailbody=$content;
						}
						else
						{
							$from=$this->mail_from;
							$fromname='Support Edit-place';
							$mailbody=$text_mail;
						}
						$mail = new Zend_Mail();
						//$mail->addHeader('Reply-To',$this->mail_from);
						$mail->setBodyHtml($mailbody)
							 ->setFrom($from, $fromname)
							 ->addTo($UserDetails[0]['email'])
							 ->setSubject($object);
						if($mail->send())
							return true;
					}		
               }
        }
        catch(Exception $e)
        {
                echo $e->getMessage();
        }
    }
	
	/* Function to calculate ep percentage from contributor percentage*/
	public function seteppercentAction()
	{
		$client_obj=new Ep_User_Client();
		$clientdata=$client_obj->getClientRecord($_REQUEST['client']);
		if($clientdata[0]['contrib_percentage']!="")
			$contribper=$clientdata[0]['contrib_percentage'];
		else
			$contribper=$this->configval["nopremium_contribpercent"];
		$eppercent=100-$contribper;
		echo $eppercent;
	}
	
	/*********************************** Template missions ******************************************/
	
	/* Loading Mission template slection list in AO creation step1, on selection of client*/
	public function loadtemplatesAction()
	{
		$temp_obj=new Ep_Delivery_MissionTemplate();
		//Getting mission templates of the client
		$templates=$temp_obj->listtemplates($_REQUEST['client']);
		$this->AO_creation = Zend_Registry::get('AO_creation');
		
		if($_REQUEST['client']!="")
			$this->AO_creation->client_id=$_REQUEST['client'];
		
		session_start();
		$_SESSION['client']=$_REQUEST['client'];
		if(count($templates)>0)
		{
			$tempvar="";
			$tempvar.='<select name="templateid" id="templateid" class="chzn_a span12" data-placeholder="Select Template" onChange="loadaovalues();"><option value="">Select</option>';
			
			for($t=0;$t<count($templates);$t++)
			{
				if($templates[$t]['id']==$this->AO_creation->ao_step1['templateid'])
					$tempvar.='<option value="'.$templates[$t]['id'].'" selected>'.utf8_encode($templates[$t]['title']).'</option>';
				else
					$tempvar.='<option value="'.$templates[$t]['id'].'">'.utf8_encode($templates[$t]['title']).'</option>';
			}
			$tempvar.='</select>';
			
			echo $tempvar;
		}
		else
			echo "NO";
		exit;
	}
	
	/* Fetching Delivery and Article details on selection of mission template */
	/* Here AO creation step session arrays are set */
	public function loadtemplateaoAction()
	{
		$temp_obj=new Ep_Delivery_MissionTemplate();
		//Fetching Delivery details based on MissionTemplate id
		$aovalues=$temp_obj->loadDeliverybytemplate($_REQUEST['templateid']);
		//print_r($aovalues);
		if(count($aovalues)>0)
		{
			//Fetching Delivery details based on MissionTemplate id
			$articlevalues=$temp_obj->loadArticlebytemplate($_REQUEST['templateid']);	
			
			$this->AO_creation = Zend_Registry::get('AO_creation');
			
			/********************************** STEP 1 *************************************/				
			$this->AO_creation->ao_step1['templateid']=$_REQUEST['templateid'];
			$this->AO_creation->ao_step1['title']=$aovalues[0]['title'];
			$this->AO_creation->ao_step1['client_list']=$_REQUEST['client_list'];
			if($aovalues[0]['deli_anonymous']=="yes")$this->AO_creation->ao_step1['deli_anonymous']="on";
			$this->AO_creation->ao_step1['language']=$aovalues[0]['language'];
			$this->AO_creation->ao_step1['type']=$aovalues[0]['type'];
			$this->AO_creation->ao_step1['category']=$aovalues[0]['category'];
			$this->AO_creation->ao_step1['signtype']=$aovalues[0]['signtype'];
			$this->AO_creation->ao_step1['min_sign']=$aovalues[0]['min_sign'];
			$this->AO_creation->ao_step1['max_sign']=$aovalues[0]['max_sign'];
			
			$this->AO_creation->ao_step1['TotPrem']=$aovalues[0]['premium_total']; 	
			$this->AO_creation->ao_step1['price_min']=$aovalues[0]['price_min']; 	
			$this->AO_creation->ao_step1['price_max']=$aovalues[0]['price_max']; 	
			$this->AO_creation->ao_step1['currency']=$aovalues[0]['currency']; 	
			
			$this->AO_creation->ao_step1['total_article']=$aovalues[0]['total_article'];
			$this->AO_creation->ao_step1['AOtype']=$aovalues[0]['AOtype'];
			$this->AO_creation->ao_step1['writer_notify']=$aovalues[0]['writer_notify'];
			
			$darray_view_to = explode(',', $aovalues[0]['view_to']) ;
        
			if(in_array( 'sc', $darray_view_to ))
				$contribtype[] = 'senior' ;
			if(in_array( 'jc', $darray_view_to ))
				$contribtype[] = 'junior' ;
			if(in_array('jc0', $darray_view_to ))
				$contribtype[] = 'sub-junior' ;
			
			$this->AO_creation->ao_step1['contribtype'] = $contribtype;
			
			if($aovalues[0]['premium_option']=='0')
				$this->AO_creation->ao_step1['mission_type']='liberte';
			else
			{
				$this->AO_creation->ao_step1['premium_option']=$aovalues[0]['premium_option'];
				$prem_ser=array();
				for($p=0;$p<count($aovalues);$p++)
					$prem_ser[]=$aovalues[$p]['option_id'];
					
				$this->AO_creation->ao_step1['premium_service']=$prem_ser;
			}
			$this->AO_creation->ao_step1['submit_option']=$aovalues[0]['submit_option'];
			if($aovalues[0]['submit_option']=="hour")
			{
				$this->AO_creation->ao_step1['junior_time']=$aovalues[0]['junior_time']/60;
				$this->AO_creation->ao_step1['senior_time']=$aovalues[0]['senior_time']/60;
				$this->AO_creation->ao_step1['subjunior_time']=$aovalues[0]['subjunior_time']/60;
			}
			elseif($aovalues[0]['submit_option']=="day")
			{
				$this->AO_creation->ao_step1['junior_time']=$aovalues[0]['junior_time']/(60*24);
				$this->AO_creation->ao_step1['senior_time']=$aovalues[0]['senior_time']/(60*24);
				$this->AO_creation->ao_step1['subjunior_time']=$aovalues[0]['subjunior_time']/(60*24);
			}
			else
			{
				$this->AO_creation->ao_step1['junior_time']=$aovalues[0]['junior_time'];
				$this->AO_creation->ao_step1['senior_time']=$aovalues[0]['senior_time'];
				$this->AO_creation->ao_step1['subjunior_time']=$aovalues[0]['subjunior_time'];
			}
			
			$this->AO_creation->ao_step1['resubmit_option']=$aovalues[0]['resubmit_option'];
			if($aovalues[0]['resubmit_option']=="hour")
			{
				$this->AO_creation->ao_step1['jc_resubmission']=$aovalues[0]['jc_resubmission']/60;
				$this->AO_creation->ao_step1['sc_resubmission']=$aovalues[0]['sc_resubmission']/60;
				$this->AO_creation->ao_step1['jc0_resubmission']=$aovalues[0]['jc0_resubmission']/60;
			}
			elseif($aovalues[0]['resubmit_option']=="day")
			{
				$this->AO_creation->ao_step1['jc_resubmission']=$aovalues[0]['jc_resubmission']/(60*24);
				$this->AO_creation->ao_step1['sc_resubmission']=$aovalues[0]['sc_resubmission']/(60*24);
				$this->AO_creation->ao_step1['jc0_resubmission']=$aovalues[0]['jc0_resubmission']/(60*24);
			}
			else
			{
				$this->AO_creation->ao_step1['jc_resubmission']=$aovalues[0]['jc_resubmission'];
				$this->AO_creation->ao_step1['sc_resubmission']=$aovalues[0]['sc_resubmission'];
				$this->AO_creation->ao_step1['jc0_resubmission']=$aovalues[0]['jc0_resubmission'];
			}
			
			$this->AO_creation->ao_step1['participation_time']=$aovalues[0]['participation_time'];
			//Poll Link 
			if($aovalues[0]['poll_id']!="")
			{
				$this->AO_creation->ao_step1['linkpoll']="on";
				$this->AO_creation->ao_step1['pollao']=$aovalues[0]['poll_id'];
				$this->AO_creation->ao_step1['priority_hours']=$aovalues[0]['priority_hours'];
				$this->AO_creation->ao_step1['priorcontrib']=explode(",",$articlevalues[0]['priority_contributors']);
			}
			
			//Quiz Link 
			if($aovalues[0]['link_quiz']=="yes")
			{
				$this->AO_creation->ao_step1['link_quiz']="on";
				$this->AO_creation->ao_step1['quiz']=$aovalues[0]['quiz'];
				$this->AO_creation->ao_step1['quiz_marks']=$aovalues[0]['quiz_marks'];
				$this->AO_creation->ao_step1['quiz_duration']=$aovalues[0]['quiz_duration'];
			}
			$this->AO_creation->ao_step1['spec_file_name']=$aovalues[0]['file_name'];
			if($aovalues[0]['AOtype']=='private')
				$this->AO_creation->ao_step1['favcontribcheck']=explode(",",$aovalues[0]['contribs_list']);
			else
				$this->AO_creation->ao_step1['publish_language']=explode(",",$aovalues[0]['publish_language']);
			
			//Correction
			if($articlevalues[0]['correction']=="yes")
			{
				$this->AO_creation->ao_step1['correction']="on";
				
				if($aovalues[0]['correction_file']!="")
				{
					$corr_file=explode("/",$aovalues[0]['correction_file']);
					$this->AO_creation->ao_step1['correction_spec_file']=$corr_file[2];
				}	
				$this->AO_creation->ao_step1['correction_participation']=$aovalues[0]['correction_participation'];
				
				$this->AO_creation->ao_step1['correction_submit_option']=$aovalues[0]['correction_submit_option'];
				if($aovalues[0]['correction_submit_option']=="hour")
				{
					$this->AO_creation->ao_step1['correction_jc_submission']=$aovalues[0]['correction_jc_submission']/60;
					$this->AO_creation->ao_step1['correction_sc_submission']=$aovalues[0]['correction_sc_submission']/60;
				}
				elseif($aovalues[0]['correction_submit_option']=="day")
				{
					$this->AO_creation->ao_step1['correction_jc_submission']=$aovalues[0]['correction_jc_submission']/(60*24);
					$this->AO_creation->ao_step1['correction_sc_submission']=$aovalues[0]['correction_sc_submission']/(60*24);
				}
				else
				{
					$this->AO_creation->ao_step1['correction_jc_submission']=$aovalues[0]['correction_jc_submission'];
					$this->AO_creation->ao_step1['correction_sc_submission']=$aovalues[0]['correction_sc_submission'];
				}
				
				$this->AO_creation->ao_step1['correction_resubmit_option']=$aovalues[0]['correction_resubmit_option'];
				if($aovalues[0]['correction_resubmit_option']=="hour")
				{
					$this->AO_creation->ao_step1['correction_jc_resubmission']=$aovalues[0]['correction_jc_resubmission']/60;
					$this->AO_creation->ao_step1['correction_sc_resubmission']=$aovalues[0]['correction_sc_resubmission']/60;
				}
				elseif($aovalues[0]['correction_resubmit_option']=="day")
				{
					$this->AO_creation->ao_step1['correction_jc_resubmission']=$aovalues[0]['correction_jc_resubmission']/(60*24);
					$this->AO_creation->ao_step1['correction_sc_resubmission']=$aovalues[0]['correction_sc_resubmission']/(60*24);
				}
				else
				{
					$this->AO_creation->ao_step1['correction_jc_resubmission']=$aovalues[0]['correction_jc_resubmission'];
					$this->AO_creation->ao_step1['correction_sc_resubmission']=$aovalues[0]['correction_sc_resubmission'];
				}
				
				$this->AO_creation->ao_step1['correction_pricemin']=$aovalues[0]['correction_pricemin'];
				$this->AO_creation->ao_step1['correction_pricemax']=$aovalues[0]['correction_pricemax'];
				if($aovalues[0]['corrector_mail']=="yes")$this->AO_creation->ao_step1['corrector_mail']="on";
				
				$corrector=explode(",",$aovalues[0]['corrector_list']);
						if(in_array('CJC',$corrector))
							$this->AO_creation->ao_step1['corrector_option']='CJC';
						elseif(in_array('CSC',$corrector))
							$this->AO_creation->ao_step1['corrector_option']='CSC';
						elseif(in_array('CB',$corrector))
							$this->AO_creation->ao_step1['corrector_option']='CB';	
							
						if(in_array('WJC',$corrector))
							$this->AO_creation->ao_step1['writer_option']='WJC';
						elseif(in_array('WSC',$corrector))
							$this->AO_creation->ao_step1['writer_option']='WSC';
						elseif(in_array('WB',$corrector))
							$this->AO_creation->ao_step1['writer_option']='WB';		
					
				if($this->AO_creation->ao_step1['corrector_option']!="")
					$this->AO_creation->ao_step1['corrector']="corrector";
				
				if($this->AO_creation->ao_step1['writer_option']!="")
					$this->AO_creation->ao_step1['writer']="writer";
					
				$this->AO_creation->ao_step1['correction_type']=$aovalues[0]['correction_type'];
				if($aovalues[0]['correction_type']=="private")
					$this->AO_creation->ao_step1['correctorcheck']=explode(",",$articlevalues[0]['corrector_privatelist']);	
				$this->AO_creation->ao_step1['corrector_notify']=$aovalues[0]['corrector_notify'];	
			}
			$this->AO_creation->ao_step1['product']=$aovalues[0]['product'];	
			if($aovalues[0]['product']=="redaction")
				$this->AO_creation->ao_step1['redactionrefusal']=explode(",",$aovalues[0]['refusalreasons']);	
			else
				$this->AO_creation->ao_step1['redactionrefusal']=explode(",",$aovalues[0]['refusalreasons']);	
			
				
			/********************************** STEP 2 *************************************/	
			
			if($articlevalues[0]['wl_kws']!="")
				$this->AO_creation->ao_step1['white_list']="on";
			if($articlevalues[0]['bl_kws']!="")	
				$this->AO_creation->ao_step1['black_list']="on";
			if($articlevalues[0]['blwl_check']!="")
                $this->AO_creation->ao_step1['blwl_check']="on";  
			//$this->AO_creation->ao_step1['CPC']=$articlevalues[0]['CPC'];
			$this->AO_creation->ao_step1['contrib_percentage']=$articlevalues[0]['contrib_percentage'];			
			
			/* Commented as per the requirement
			for($a=0;$a<count($articlevalues);$a++)
			{
				$this->AO_creation->ao_step2['art_title'][$a]=$articlevalues[$a]['title'];
				$this->AO_creation->ao_step2['num_min'][$a]=$articlevalues[$a]['num_min'];
				$this->AO_creation->ao_step2['num_max'][$a]=$articlevalues[$a]['num_max'];
				$this->AO_creation->ao_step2['price_min'][$a]=$articlevalues[$a]['price_min'];
				$this->AO_creation->ao_step2['price_max'][$a]=$articlevalues[$a]['price_max'];
				$this->AO_creation->ao_step2['language'][$a]=$articlevalues[$a]['language'];
				$this->AO_creation->ao_step2['type'][$a]=$articlevalues[$a]['type'];
				$this->AO_creation->ao_step2['category'][$a]=$articlevalues[$a]['category'];
				$this->AO_creation->ao_step2['signtype'][$a]=$articlevalues[$a]['sign_type'];
				$this->AO_creation->ao_step2['contrib_percentage'][$a]=$articlevalues[$a]['contrib_percentage'];
				
				if($this->AO_creation->ao_step1['AOtype']=='private')
					$this->AO_creation->ao_step2['favcontribcheck'][$a]=explode(",",$articlevalues[$a]['contribs_list']);
				
				
				if($this->AO_creation->ao_step1['white_list']=='on') 
				{
					$articlevalues[$a]['wl_kws']=str_replace("\\","",$articlevalues[$a]['wl_kws']);
					$wl_kws=unserialize(utf8_encode($articlevalues[$a]['wl_kws']));
					$this->AO_creation->ao_step2['white_list_kw_count'][$a]=count($wl_kws[0]);
					for($w=0;$w<count($wl_kws[0]);$w++)
					{
						$this->AO_creation->ao_step2['white_list_'.($a+1)][$w]=$wl_kws[0][$w] ;
						$this->AO_creation->ao_step2['white_list_density_min_'.($a+1)][$w]=$wl_kws[1][$w] ;
						$this->AO_creation->ao_step2['white_list_density_max_'.($a+1)][$w]=$wl_kws[2][$w] ;
					}
				}
				if( $this->AO_creation->ao_step1['black_list'] == 'on') 
				{
					$articlevalues[$a]['bl_kws']=str_replace("\\","",$articlevalues[$a]['bl_kws']);
					$bl_kws=unserialize(utf8_encode($articlevalues[$a]['bl_kws']));
					$this->AO_creation->ao_step2['black_list_kw_count'][$a]=count($bl_kws[0]);
					for($b=0;$b<count($bl_kws[0]);$b++)
						$this->AO_creation->ao_step2['black_list_'.($a+1)][$b]=$bl_kws[0][$b];
				}
				
				if($this->AO_creation->ao_step1['CPC']=='yes')
					$this->AO_creation->ao_step2['cpc_price'][]=$articlevalues[$a]['price_cpc'];
				
				if( $this->AO_creation->ao_step1['correction'] == 'on') 
				{
					$this->AO_creation->ao_step2['correction_pricemin'][$a]=$articlevalues[$a]['correction_pricemin'];
					$this->AO_creation->ao_step2['correction_pricemax'][$a]=$articlevalues[$a]['correction_pricemax'];
				}
			}
			*/
			/********************************** STEP 3 *************************************/
					
				if($aovalues[0]['mail_send']=="yes")$this->AO_creation->ao_step3['mail_send']="on";
				
				$this->AO_creation->ao_step3['missioncomment']=$aovalues[0]['missioncomment'];		
				$this->AO_creation->ao_step3['fbcomment']=$aovalues[0]['fbcomment'];		
				$this->AO_creation->ao_step3['nltitle']=$aovalues[0]['nltitle'];		
				$this->AO_creation->ao_step3['paypercent']='0';		 
					
		}
	}
	
	/* Ajax call to check whether client testrequired is yes*/
	public function loadtestrequiredAction()
	{
		$client_obj=new Ep_User_Client();
		$clientdetails=$client_obj->getClientRecord($_REQUEST['client']);
		
		if(count($clientdetails)>0)
			echo $clientdetails[0]['contributortestrequired']."#".$clientdetails[0]['urlsexcluded'];
		else
			echo "no";
	}
	
	/* Finding extensions of the filename passed as parameter*/
	public function findexts ($filename="")
	{
		$filename = strtolower($filename) ;
		$exts = split("[/\\.]", $filename) ;
		$n = count($exts)-1;
		$exts = $exts[$n];
		return $exts;
	}
	
	/* Creating new client from AO creation step1 or Poll creation step1 */
	public function adduserAction()
	{
		$user_det=$this->_request->getParams();
		$user_obj=new Ep_User_User();
		$clident=$user_obj->InsertnewUser($user_det);
		
		if($_REQUEST['type']=='poll')
			$this->_redirect("ao/createpoll?submenuId=ML2-SL15&clnt=".$clident);
		elseif($_REQUEST['type']=='ao')
			$this->_redirect("ao/ao-create1?submenuId=ML2-SL3&clnt=".$clident);
		elseif($_REQUEST['type']=='users')
		{
			if($user_det['contrib_type']=="client")
				$utype=1;
			else if($user_det['contrib_type']=="contributor")
				$utype=2;
				
			$this->_redirect("ao/users?submenuId=ML2-SL7&utype=".$utype."&user=".$clident);
		}	
	}
	
	/* Function to check whether given email already exists in db*/
	public function checknewuseremailAction()
	{
		$emailcheck_params_duplicate=$this->_request->getParams();
		$obj = new Ep_User_User();	
		$res= $obj->checkClientMailid($emailcheck_params_duplicate['newemail']);
		echo $res;
		exit;		
	}
	
	/* Function to check whether client basic profile (Company name & logo) are filled - used in AO creation step1*/
	public function clientprofilecheckAction(){
		$clientParams=$this->_request->getParams() ;
        $userobj=new Ep_User_User() ;
        $user_detail=$userobj->getUserdetails($clientParams['client']) ;
        if(file_exists('/home/sites/site7/web/FO/profiles/clients/logos/'.$clientParams['client'].'/'.$clientParams['client'].'_global.png') && ($user_detail[0]['company_name'] != ''))
           $send="yes";
        else
            $send="no";
			
		echo trim($send);	
    }
	
	/* Function to update client company name - used in AO creation step1*/
	public function clientprofileupdateAction()
	{
		//Client deatils
		$obj=new Ep_User_User();
		$clientdetail=$obj->getClientProfile($_REQUEST['client']);
		
		$this->_view->clientdetail=$clientdetail;
		$this->_view->render("ao_clientprofileupdate");
	}
	
	/* TO check whether client logo exists in server */
	function checkclientprofileAction()
	{
        $clientParams=$this->_request->getParams() ;
        
        if( file_exists('/home/sites/site7/web/FO/profiles/clients/logos/'.$clientParams['client'].'/'.$clientParams['client'].'_global.png')  )
            exit('yes') ;
        else
            exit('no') ;
    }
	
	/* Function to upload client logo in AO creation step1, when his profile is not complete*/
	public function uploadclientgloballogoAction()
	{
		$realfilename=$_FILES['uploadfile']['name'] ;
		$ext=substr(strrev($realfilename), 0, strpos($realfilename, '.')) ;
		$client_id=$_REQUEST['clientid'];
		$profiledir='/home/sites/site7/web/FO/profiles/clients/logos/'.$client_id.'/';
		$pic_path='/profiles/clients/logos/'.$client_id.'/';
		$uploaddir = '/home/sites/site7/web/FO/profiles/clients/logos/'.$client_id.'/'; 
		$newfilename=$client_id.".".$ext;
		$clntid=$this->_view->clientidentifier;
		if(!is_dir($uploaddir))
		{   
			mkdir($uploaddir,0777);
			chmod($uploaddir,0777);
		}
		$file = $uploaddir.$client_id.".png"; 
		$file_global1=$uploaddir.$client_id."_global.png";
		$file_global2=$uploaddir.$client_id."_global1.png";
		list($width, $height)  = getimagesize($_FILES['uploadfile']['tmp_name']);
		if($width>=90 || $height>=90)
		{
			if (move_uploaded_file($_FILES['uploadfile']['tmp_name'], $file))
			{
				//73
				$newimage_crop= new EP_User_Image();
				$newimage_crop->load($file);
				list($width, $height) = getimagesize($file);
				if($width>$height)
					$newimage_crop->resizeToWidth(73);
				elseif($height>$width)
					$newimage_crop->resizeToHeight(73);
				else
					$newimage_crop->resize(73,73);
				
				$newimage_crop->save($file_global1);
				chmod($file_global1,0777);
				
				//90
				$newimage_crop1= new EP_User_Image();
				$newimage_crop1->load($file);
				list($width, $height) = getimagesize($file);
				if($width>$height)
					$newimage_crop1->resizeToWidth(90);
				elseif($height>$width)
					$newimage_crop1->resizeToHeight(90);
				else
					$newimage_crop1->resize(90,90);
				
				$newimage_crop1->save($file_global2);
				chmod($file_global2,0777);
				
				$array=array("status"=>"success","identifier"=>$client_id,"path"=>$pic_path,"ext"=>"png");
				echo json_encode($array);
			}
			else
			{
				$array=array("status"=>"error"  );
				echo json_encode($array);
			}
		}
		else
		{
			$array=array("status"=>"smallfile"  );
			echo json_encode($array);
		}
	}
	
	public function listallaoAction()
	{
		$this->_redirect("/ongoing/list?submenuId=ML2-SL4");
	}
	
	/*************************************************** POLL ************************************************/
	/* Poll creation step1 - Here basic poll datas are filled in */
	public function createpollAction()
	{ 
		$this->Poll_creation = Zend_Registry::get('Poll_creation');
		
		//Setting Navigation in top right corner
		if(isset($this->Poll_creation->poll_step1['title']))
			$this->_view->nav_1=1;
		if(isset($this->Poll_creation->poll_step2))
			$this->_view->nav_2=1;
		if(isset($this->Poll_creation->poll_step3))
			$this->_view->nav_3=1;
		if($_GET['clnt']!="")
			$this->_view->def_user = $_GET['clnt'];
		
		/* If Poll creation step1 session is already set, while coming back from other steps*/
		if($this->_view->nav_1==1)
		{
			$this->_view->def_user=$this->Poll_creation->poll_step1['client_list'];
			$this->_view->title=$this->Poll_creation->poll_step1['title'];
			$this->_view->poll_date=$this->Poll_creation->poll_step1['poll_date'];
			$this->_view->poll_anonymous=$this->Poll_creation->poll_step1['poll_anonymous'];
			
			$this->_view->type=$this->Poll_creation->poll_step1['type'];
			$this->_view->language=$this->Poll_creation->poll_step1['language'];
			$this->_view->category=$this->Poll_creation->poll_step1['category'];
			$this->_view->signtype=$this->Poll_creation->poll_step1['signtype'];
			$this->_view->min_sign=$this->Poll_creation->poll_step1['min_sign'];
			$this->_view->max_sign=$this->Poll_creation->poll_step1['max_sign'];
			$this->_view->priority_hours=$this->Poll_creation->poll_step1['priority_hours'];
			$this->_view->poll_max=$this->Poll_creation->poll_step1['poll_max'];
			$this->_view->contrib_percentage=$this->Poll_creation->poll_step1['contrib_percentage'];
			$this->_view->publishnow=$this->Poll_creation->poll_step1['publishnow'];
			if($this->Poll_creation->poll_step1['publishnow']!="checked")
				$this->_view->publish_time=$this->Poll_creation->poll_step1['publish_time'];
			if($this->Poll_creation->poll_step1['poll_spec_name']!="")
				$this->_view->poll_file=$this->Poll_creation->poll_step1['poll_spec_name'];
			$this->_view->get_modify=1;
		}
		else
		{
			//Default values
			$this->_view->min_sign='Min.';
			$this->_view->max_sign='Max.';
			$this->_view->priority_hours=$this->configval["priority_hours"];
		    $this->_view->contrib_percentage=$this->configval["pollfo_contribpercent"];
		    $this->_view->currency=$this->configval["currency"];
			$this->_view->poll_file="";
			$this->_view->publishnow="checked";
		}
		$this->_view->render("ao_createpoll_step1");
	}
	/* Poll creation step2 - for adding poll questions */
	public function createpoll1Action()
	{
		$this->Poll_creation = Zend_Registry::get('Poll_creation');
		//Storing step1 data in session array
		$params2=$this->_request->getParams();
		if($params2['title']!="")
			$this->Poll_creation->poll_step1=$params2;
		if($this->Poll_creation->poll_step1['title']=="")
			 $this->_redirect("/ao/createpoll?submenuId=ML2-SL15");
		//Navigation
		if(isset($this->Poll_creation->poll_step1['title']))
			$this->_view->nav_1=1;
		if(isset($this->Poll_creation->poll_step2))
			$this->_view->nav_2=1;
		if(isset($this->Poll_creation->poll_step3))
			$this->_view->nav_3=1;
		$poll_obj=new Ep_Delivery_Poll();
		//Getting defined set of questions from Poll_Configuration
		$ques_obj=new Ep_Delivery_Pollconfiguration();
		$pquestionlist=$ques_obj->getPollquestions();
			for($p=0;$p<count($pquestionlist);$p++)
			{
				if($pquestionlist[$p]['type']=='radio' || $pquestionlist[$p]['type']=='checkbox')
				{
					if($pquestionlist[$p]['option'] != "")
						$pquestionlist[$p]['optionlist'] = str_replace("|"," ,",$pquestionlist[$p]['option']);
				}
			}
		$this->_view->pquestionlist=$pquestionlist;
		$selectarr=array();
	
		/* If Poll creation step2 session is already set, while coming back from other steps */
		if($this->_view->nav_2==1)
		{
			$selectarr=$this->Poll_creation->poll_step2['selques'];
			for($a=1;$a<=count($pquestionlist);$a++)
			{
				$name='title_'.$a;
				$pquestionlist[$a-1]['title']=$this->Poll_creation->poll_step2[$name];
				if($this->Poll_creation->poll_step2['type_'.$a]=='timing')
					$pquestionlist[$a-1]['option']=$this->Poll_creation->poll_step2['timingoption_'.$a];
				if($this->Poll_creation->poll_step2['type_'.$a]=='price' || $this->Poll_creation->poll_step2['type_'.$a]=='range_price' || $this->Poll_creation->poll_step2['type_'.$a]=='timing')	
					$pquestionlist[$a-1]['maximum']=$this->Poll_creation->poll_step2['maximum_'.$a];
				if($this->Poll_creation->poll_step2['type_'.$a]=='range_price' || $this->Poll_creation->poll_step2['type_'.$a]=='timing')
					$pquestionlist[$a-1]['minimum']=$this->Poll_creation->poll_step2['minimum_'.$a];
				$pquestionlist[$a-1]['order']=$this->Poll_creation->poll_step2['order_'.$a];
				$pquestionlist[$a-1]['linkedit']=$this->Poll_creation->poll_step2['link_'.$a];
			}
		}
		
		$this->_view->smicrate=$poll_obj->getSMICpoll($this->Poll_creation->poll_step1['language'],$this->Poll_creation->poll_step1['category']);
		$this->_view->pquestionlist=$pquestionlist;
		$this->_view->selectedq=$selectarr;
		$this->_view->render("ao_createpoll_step2");
	}
	/* Poll creation step3 - mailing related form */
	public function createpoll2Action()
	{
	
		$this->Poll_creation = Zend_Registry::get('Poll_creation');
		//storing step2 data to session array
		$params2=$this->_request->getParams();
		if($params2['quesid_1']!="")
			$this->Poll_creation->poll_step2=$params2;
		if($this->Poll_creation->poll_step1['title']=="")
			 $this->_redirect("/ao/createpoll?submenuId=ML2-SL15");
		//Navigation
		if(isset($this->Poll_creation->poll_step1['title']))
			$this->_view->nav_1=1;
		if(isset($this->Poll_creation->poll_step2))
			$this->_view->nav_2=1;
		if(isset($this->Poll_creation->poll_step3))
			$this->_view->nav_3=1;
		$this->_view->ao_title=$this->Poll_creation->poll_step1['title'];
		$this->_view->category=$this->Poll_creation->poll_step1['category'];
		$this->_view->render("ao_createpoll_step3");
	}
	/* Poll creation step4 - Insertion of Poll of all 3 steps data stored in session */
	public function createpoll3Action()
	{
		$this->Poll_creation = Zend_Registry::get('Poll_creation');
		$art_cat_type_array=$this->_arrayDb->loadArrayv2("EP_ARTICLE_CATEGORY", $this->_lang);
		if($this->Poll_creation->poll_step1['title']=="")
			 $this->_redirect("/ao/createpoll?submenuId=ML2-SL15");
		//storing step3 data to session array
		$params4=$this->_request->getParams();
		$this->Poll_creation->poll_step3=$params4;
			$obj2 = new Ep_Delivery_Poll();
			$this->Poll_creation->poll_step1['black_contrib']=$this->Poll_creation->poll_step3['black_contrib'];
			$this->Poll_creation->poll_step1['contrib']=$this->Poll_creation->poll_step3['contrib'];
			$this->Poll_creation->poll_step1['created_by']=$this->adminLogin->userId;
			$this->Poll_creation->poll_step1['send_mail']=$this->Poll_creation->poll_step3['send_mail'];
			//Insertion to Poll
			$poll_id=$obj2->insertPoll($this->Poll_creation->poll_step1);
			
			//Inserting Poll Questions
			if($poll_id!="NO")
			{
				$obj3 = new Ep_Delivery_Pollquestion();
				$aids=$obj3->insertpollQuestions($poll_id,$this->Poll_creation->poll_step2);
			}
			
			//Sending mail to the client
			$parameters1['pollcategory']=trim($art_cat_type_array[$this->Poll_creation->poll_step1['category']]);
			$parameters1['poll_title']=utf8_decode($this->Poll_creation->poll_step1['title']);
				$date=str_replace("/","-",$this->Poll_creation->poll_step1['poll_date']);
			$parameters1['poll_enddate']=date("d/m/Y H:i:s",strtotime($date));
			$parameters1['client_polllink']="<a href='http://ep-test.edit-place.co.uk/client/devispremium?id=".$poll_id."'>ici</a>";
			$parameters1['poll']=$poll_id;
			$this->messageToEPMail($this->Poll_creation->poll_step1['client_list'],11,$parameters1);
			
			//Unseting all Poll creation related sessions after db insertion
			unset($this->Poll_creation->poll_step1);
			unset($this->Poll_creation->poll_step2);
			unset($this->Poll_creation->poll_step3);
		$this->_view->render("ao_createpoll_step4");
	}
	
	/* Fetching writers count to send poll creation mails*/
	public function countpollmailsAction()
	{
		$obj = new Ep_Delivery_Poll();
		//echo $_REQUEST['users'].'-'.$_REQUEST['black'].'-'.$_REQUEST['category'];exit;
		$countm=$obj->getMailcount($_REQUEST['users'],$_REQUEST['black'],$_REQUEST['category']);
		echo $countm;
		//exit;
	}
	
	/* Fetching writer list to send poll creation mails*/
	public function pollusersAction()
	{
		$obj = new Ep_Delivery_Poll();
		$emaillist=$obj->getMailids($_REQUEST['userty'],$_REQUEST['black'],$_REQUEST['cat']);
		
		$emails='';
		
			echo '<span style="color:#E18E08;font-weight:bold;text-align:center;">List of emails</span><br><br>';
			
			for($e=0;$e<count($emaillist);$e++)
				echo '<b>'.$emaillist[$e]['email'].'</b><br><br>';
	}
	
	/* Ajax call to upload spec file in poll creation */
	public function uploadpollspecdocAction()
	{
		$this->Poll_creation = Zend_Registry::get('Poll_creation');
		$realfilename=$_FILES['uploadfile']['name'];
		$ext=$this->findexts($realfilename);
		$uploaddir = '/home/sites/site7/web/FO/poll_spec/';
		//$uploaddir=$uploaddir."/";
		$bname=basename($realfilename,".".$ext)."_".uniqid().".".$ext;
		$file = $uploaddir . $bname;
		if (move_uploaded_file($_FILES['uploadfile']['tmp_name'], $file))
		{
			chmod($file,0777);
			echo "success#".$bname;
		}
		else
		{
			echo "error";
		}
	}
	
	/* Page to list all the Polls*/
	public function pollAction()
	{
		$poll_obj = new Ep_Delivery_Poll();
		//Updating Poll details on Edit
		if($_REQUEST['submit_poll']!="")
		{//print_r($_REQUEST);exit;
			$poll_obj->editupdatePoll($_REQUEST);
		}
		
		//Closing poll on click of close button in the bottom
		if($_REQUEST['closepll'])
		{
			for($p=0;$p<count($_REQUEST['closecheck']);$p++)
			{
				$poll_obj->closepoll($_REQUEST['closecheck'][$p],'closed');
			}	
		}
		
		//Search parameters
		$searchvals = array();
		if($_REQUEST['search_submit']!="")
		{
			$searchvals['start_date'] = $_GET['start_date'];
			$searchvals['end_date'] = $_GET['end_date'];
			$searchvals['start_datepublish'] = $_GET['start_datepublish'];
			$searchvals['end_datepublish'] = $_GET['end_datepublish'];
			$searchvals['client'] = $_GET['client'];
			$searchvals['category'] = $_GET['category'];
			$searchvals['sorttype'] = $_GET['sorttype'];
			
			$this->_view->start_date = $searchvals['start_date'];
			$this->_view->end_date = $searchvals['end_date'];
			$this->_view->start_datepublish = $searchvals['start_datepublish'];
			$this->_view->end_datepublish = $searchvals['end_datepublish'];
			$this->_view->client = $searchvals['client'];
			$this->_view->category = $searchvals['category'];
			$this->_view->sorttype = $searchvals['sorttype'];
		}
		//Fetching poll list on search or by default
		$polllist=$poll_obj->ListPollsearchresult($searchvals);
		//Client list for search form
		$client_info_obj = new Ep_User_User();
		$client_info= $client_info_obj->GetclientList();
		$client_list=array();
		if($client_info!="NO")
		{
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
		}
		$this->_view->client_list=$client_list;
		 
		$art_cat_type_array=  $this->_arrayDb->loadArrayv2("EP_ARTICLE_CATEGORY", $this->_lang);
		$this->_view->ep_art_cat_type=$art_cat_type_array;
		$this->_view->now=strtotime("now");
		
		//Framing poll participation details
		for($p=0;$p<count($polllist);$p++)
		{
			//$polllist[$p]['expire']=strtotime($polllist[$p]['poll_date'])-strtotime("h",$polllist[$p]['poll_duration']);
			$polldetails=$poll_obj->ListPollresult($polllist[$p]['id']);
			$polllist[$p]['participation']=$polldetails[0]['participation'];
			$polllist[$p]['maxprice']=$polldetails[0]['maxprice'];
			$polllist[$p]['minprice']=$polldetails[0]['minprice'];
			$polllist[$p]['sumprice']=$polldetails[0]['sumprice'];
			
			$polllist[$p]['expire']=strtotime($polllist[$p]['poll_date']);
		}
		$this->_view->polllist=$polllist;
		$this->_view->pagelimit=$this->getConfiguredval('pagination_bo');
		$this->_view->render("ao_poll");
	}
	
	/* Detailed poll participation page*/
	public function pollmoderateusersAction()
	{
		$poll_obj = new Ep_Delivery_Poll();
		$pollques_obj = new Ep_Delivery_Pollquestion();
		$brief_obj=new Ep_Delivery_Pollbrief();
		
		//Activate all selected poll participations 
		if($_REQUEST['activate_all']!="")
		{
			for($a=0;$a<count($_REQUEST['contribtype']);$a++)
				$poll_obj->pollpartstatus($_REQUEST['contribtype'][$a],'inactive');
		}
		
		//Inactivate all selected poll participations
		if($_REQUEST['inactivate_all']!="")
		{
			for($a=0;$a<count($_REQUEST['contribtype']);$a++)
				$poll_obj->pollpartstatus($_REQUEST['contribtype'][$a],'active');
		}
		
		//Saving Brief2 details on submit of brief2 form
		if($_REQUEST['addbrief']==1)
		{
			$brief_obj->UpdatePollBrief2($_REQUEST);
			$this->_redirect("/ao/pollmoderateusers?submenuId=ML2-SL17&poll=".$_REQUEST['poll']);
		}
		
		//Poll participation detail list
		$PollParticipationDetails=$poll_obj->ListPollPartcipationmoderate($_REQUEST['poll'],$_REQUEST['smic']);
		$this->_view->PollParticipationDetails=$PollParticipationDetails;
		//Poll questions
		$pollstats=$pollques_obj->getQuestStats($_REQUEST['poll'],$_REQUEST['smic']);
		//print_r($pollstats);
		//Poll stats calculation for stats block
			for($s=0;$s<count($pollstats);$s++)
			{
				if($pollstats[$s]['type'] == 'timing')
				{
					if($pollstats[$s]['option'] == 'day')
					{
						$multiple = 60*24;
						$pollstats[$s]['optionname'] = 'Jour(s)';
					}
					elseif($pollstats[$s]['option'] == 'hour')
					{
						$multiple = 60; 
						$pollstats[$s]['optionname'] = 'Heure(s)';
					}
					else
					{
						$multiple = 1; 
						$pollstats[$s]['optionname'] = 'Minute(s)';
					}		
					$pollstats[$s]['min'] = $pollstats[$s]['min']/$multiple;
					$pollstats[$s]['max'] = $pollstats[$s]['max']/$multiple;
					$pollstats[$s]['avg'] = $pollstats[$s]['avg']/$multiple;
				}
				elseif($pollstats[$s]['type'] == 'radio' || $pollstats[$s]['type'] == 'checkbox')
				{
					$optioncount = explode("|",$pollstats[$s]['option']);
					$pollstats[$s]['optionlist'] = array();
					for($p=1;$p<=count($optioncount);$p++)
					{
						$label = 'option'.$p;
						$pollstats[$s]['optionlist'][$optioncount[$p-1]] = $pollques_obj->getRadioresponse($_REQUEST['poll'],$pollstats[$s]['id'],$label);
					}
				}
			}
		$this->_view->pollstats=$pollstats;
		
		if(count($pollstats)==0)
		{
			$pollquestions=$pollques_obj->getQuestions($_REQUEST['poll']);
			$this->_view->pollquestions=$pollquestions;
		}
		
		//checking whether brief2 filled
		$checkbrief = $brief_obj->getPollBrief2($_REQUEST['poll']);
		if(count($checkbrief)==0)
			$this->_view->brief = 'no';
		else
			$this->_view->brief = 'yes';
		$this->_view->usertype = $this->adminLogin->type;		
		$this->_view->render("ao_pollmoderateusers");
	}
	
	/* Ajax call to swtich poll participation status active <->inactive  and update stats accordingly*/
	public function pollparticipationstatusAction()
	{
		$poll_obj = new Ep_Delivery_Poll();
		$pollques_obj = new Ep_Delivery_Pollquestion();
		$ppstatus=$poll_obj->pollpartstatus($_REQUEST['partid'],$_REQUEST['status']);
		
		if($ppstatus=='active')
		{
			$actvar="active";
			$data['text']='<b><a href="javascript:void(0);" onClick="pollparticipationactive('.$_REQUEST['partid'].',\''.$actvar.'\');">Exclude</a></b>';
		}
		else
		{
			$actvar="inactive";
			$data['text']='<b><a href="javascript:void(0);" onClick="pollparticipationactive('.$_REQUEST['partid'].',\''.$actvar.'\');">Include</a></b>';
		}
		
		//Poll questions
		$smic="";
		if($_REQUEST['smic']=="on")
			$smic=1;
		$pollstats=$pollques_obj->getQuestStats($_REQUEST['poll'],$smic);
		
		$stats='';
		
		$stats.='<table width="55%" cellpadding="2" align="center" class="w-box-content">';
		if(count($pollstats)>0)
		{
			foreach($pollstats as $stat)
			{
					if($stat['type'] == 'timing')
					{
						if($stat['option'] == 'day')
						{
							$multiple = 60*24;
							$stat['optionname'] = 'Jour(s)';
						}
						elseif($stat['option'] == 'hour')
						{
							$multiple = 60;
							$stat['optionname'] = 'Heure(s)';							
						}
						else
						{
							$multiple = 1;
							$stat['optionname'] = 'Minute(s)';							
						}	
							
						$stat['min'] = $stat['min']/$multiple;
						$stat['max'] = $stat['max']/$multiple;
						$stat['avg'] = $stat['avg']/$multiple;
					}
					
				
					$stats.='<tr class="w-box-header"><th colspan="4">'.stripslashes(utf8_encode($stat['title'])).'</th></tr>';
					
					if($stat['type']!='radio' && $stat['type']!='checkbox' && $stat['type']!='calendar' && $stat['type']!='timing')
					{
						$stats.='<tr>
							<td width="30%">Number of reponses : </td>
							<td align="left">'.$stat['partcount'].'</td>
							<td align="right">Minimum : </td>
							<td>'.$this->zero_cut($stat['min'],2).' </td>
							
						</tr>
						<tr>
							<td colspan="2"></td>
							<td align="right">Maximum : </td>
							<td>'.$this->zero_cut($stat['max'],2).' </td>
						</tr>
						<tr>
							<td colspan="2"></td>
							<td style="font-weight:bold;" align="right">Average : </td>
							<td style="font-weight:bold;">'.$this->zero_cut($stat['avg'],2).' </td>
						</tr>';
					}
					elseif($stat['type']=='timing')
					{
						$stats.='<tr>
							<td width="30%">Number of reponses : </td>
							<td>'.$stat['partcount'].'</td>
							<td align="right">Minimum : </td>
							<td>'.$this->zero_cut($stat['min'],2).'&nbsp;'.$stat['optionname'].'</td>
							
						</tr>
						<tr>
							<td colspan="2"></td>
							<td align="right">Maximum : </td>
							<td>'.$this->zero_cut($stat['max'],2).'&nbsp;'.$stat['optionname'].'</td>
						</tr>
						<tr>
							<td colspan="2"></td>
							<td style="font-weight:bold;" align="right">Average : </td>
							<td style="font-weight:bold;">'.$this->zero_cut($stat['avg'],2).'&nbsp;'.$stat['optionname'].'</td>
						</tr>';
					}
					elseif($stat['type']=='calendar')
					{
						$stats.='<tr>
							<td align="right" width="30%">Number of reponses : </td>
							<td>'.$stat['partcount'].'</td>
							</tr>';
					}
					else
					{
						$optioncount = explode("|",$stat['option']);
						
						$stats.='<tr>
							<td align="right" width="30%" valign="top">Number of reponses : </td>
							<td valign="top">'.$stat['partcount'].'</td>
							<td align="right" width="30%" valign="top">Options : </td>
							<td nowrap>';
							for($p=1;$p<=count($optioncount);$p++)
							{
								$label = 'option'.$p;
								$stats.=$optioncount[$p-1].' - '.$pollques_obj->getRadioresponse($_REQUEST['poll'],$stat['id'],$label).'<br>';
							}
						$stats.='</td>';
					}
					$stats.='<tr><td>&nbsp;</td></tr>';
			}
		}
		else
		{
			//When there are no poll participation stats
			$pollquestions=$pollques_obj->getQuestions($_REQUEST['poll']);
			
			foreach($pollquestions as $ques)
			{
					$stats.='<tr class="w-box-header"><th colspan="4">'.stripslashes(utf8_encode($ques['title'])).'</th></tr>';
					$stats.='<tr><td>&nbsp;</td></tr>';
			}
		}		
		$stats.='</table>';
		
		
		$data['stats'] = $stats;
		echo json_encode($data);
	}
	
	//Getting category name of multiple cat ids sent comma  seperated
	public function getCategoryName($category_value)
    {
        $category_name='';
        $categories=explode(",",$category_value);
        $categories_array=$this->_arrayDb->loadArrayv2("EP_ARTICLE_CATEGORY", $this->_lang);
        $cnt=0;
        $totcnt=count($categories);
		foreach($categories as $category)
        {
            if($cnt==4)
                break;
            if($cnt!=0)
					$category_name.=", ";
					
			$category_name.=$categories_array[$category];
			$cnt++;
        }
        return $category_name;
    }
	
	/* XLS extraction of poll participation details */
	public function downloadpollxlsAction()
	{
		$poll_id=$_REQUEST['id'];
		$poll_obj = new Ep_Delivery_Poll();
		$poll_details=$poll_obj->pollclientdetails($poll_id);
		$poll_contribs=$poll_obj->PollPartcipationsAll($poll_id);
		
		$country_array=$this->_arrayDb->loadArrayv2("countryList", $this->_lang);
		$profession_array=$this->_arrayDb->loadArrayv2("CONTRIB_PROFESSION", $this->_lang);
        $language_array=$this->_arrayDb->loadArrayv2("EP_LANGUAGES", $this->_lang);
		
		$downlpath=APP_PATH_ROOT.'poll_xls/'.$poll_id.'/';
		
		$file = $downlpath.$poll_details[0]['title'].'.xls';
                 ob_start();
                 echo '<table border="1"> ';
					echo '<tr>
							<th>NAME</th>
							<th>EMAIL</th>
							<th>INITIAL</th>
							<th>STATUS</th>
							<th>DATE OF JOIN</th>
							<th>PROFILE TYPE</th>
							<th>BLACK STATUS</th>
							<th>ADDRESS</th>
							<th>CITY</th>
							<th>STATE</th>
							<th>COUNTRY</th>
							<th>PINCODE</th>
							<th>PHONE</th>
							<th>DOB</th>
							<th>UNIVERSITY</th>
							<th>PROFESSION</th>
							<th>LANGUAGE</th>
							<th>CATEGORY</th>
							<th>DESCRIPTION</th>
							<th>PRICE</th>
						</tr>';
                 
                 for($p=0;$p<count($poll_contribs);$p++)
                 {
                     $name=$poll_contribs[$p]['first_name'].' '.$poll_contribs[$p]['last_name'];
					 $poll_contribs[$p]['self_details']=str_replace("<br />","",$poll_contribs[$p]['self_details']);
					 
					 if($poll_contribs[$p]['initial']=='mr')
						$initial="M";
					else
						$initial="F";
					 
					//Job
					$jobdetails=$poll_obj->pollcontribjob($poll_contribs[$p]['user_id']);
					
					//Education
					$edudetails=$poll_obj->pollcontribeducation($poll_contribs[$p]['user_id']);
					$education="";	
						for($e=0;$e<count($edudetails);$e++)
						{
							if($e>0)
								$education.=" / ";
								
							$education.=$edudetails[$e]['title'].", ".$edudetails[$e]['institute'];
						}
						
					 echo '<tr>
								<td valign="top">'.$name.'</td>
								<td valign="top">'.$poll_contribs[$p]['email'].'</td>
								<td valign="top">'.$initial.'</td>
								<td valign="top">'.$poll_contribs[$p]['status'].'</td>
								<td valign="top">'.$poll_contribs[$p]['created_at'].'</td>
								<td valign="top">'.$poll_contribs[$p]['profile_type'].'</td>
								<td valign="top">'.$poll_contribs[$p]['blackstatus'].'</td>
								<td valign="top">'.$poll_contribs[$p]['address'].'</td>
								<td valign="top">'.$poll_contribs[$p]['city'].'</td>
								<td valign="top">'.$poll_contribs[$p]['state'].'</td>
								<td valign="top">'.$country_array[$poll_contribs[$p]['country']].'</td>
								<td valign="top">'.$poll_contribs[$p]['zipcode'].'</td>
								<td valign="top">'.$poll_contribs[$p]['phone_number'].'</td>
								<td valign="top">'.$poll_contribs[$p]['dob1'].'</td>
								<td valign="top">'.$education.'</td>
								<td valign="top">'.$jobdetails[0]['title'].'</td>
								<td valign="top">'.$language_array[$poll_contribs[$p]['language']].'</td>
								<td valign="top">'.$this->getCategoryName($poll_contribs[$p]['favourite_category']).'</td>
								<td valign="top">'.stripslashes($poll_contribs[$p]['self_details']).'</td>
								<td valign="top">'.$poll_contribs[$p]['price_user'].'</td>
							</tr>';
                     
                 }
                 echo '</table>';
                 
				 $poll_details[0]['title']=str_replace(" ","_",$poll_details[0]['title']);
				 $poll_details[0]['first_name']=str_replace(" ","_",$poll_details[0]['first_name']);
				 
                 $content = ob_get_contents();
                 ob_end_clean();
                 header("Expires: 0");
                 header("Cache-Control: post-check=0, pre-check=0", false);
                 header("Pragma: no-cache");  header("Content-type: application/vnd.ms-excel;charset:UTF-8");
                 header('Content-length: '.strlen($content));
                 header('Content-disposition: attachment; filename='.$poll_details[0]['title'].'-'.$poll_details[0]['first_name'].'.xls');
                 echo $content;
                 exit;
		
	}
	
	/* Poll edit pop up */
	public function polleditAction()
	{
		$poll_obj = new Ep_Delivery_Poll();
		$client_info_obj = new Ep_User_User();
		$client_info= $client_info_obj->GetclientList();
		$client_list=array();
		for($c=0;$c<count($client_info);$c++)
		{
			$identifier=$client_info[$c]['identifier'];
			$name=$client_info[$c]['email'];
			
			$client_list[$identifier]=strtoupper($name);
		}
		
		asort($client_list);
		$this->_view->client_list = $client_list;
		
		$art_cat_type_array=$this->_arrayDb->loadArrayv2("EP_ARTICLE_CATEGORY", $this->_lang);
		foreach ($art_cat_type_array as $id => $val)
			$art_cat_type_array[$id]=utf8_encode($art_cat_type_array[$id]);
		$this->_view->EP_ARTICLE_CATEGORY=$art_cat_type_array;
		$poll_detail=$poll_obj->ListPollresultedit($_REQUEST['poll']);
		
		if($poll_detail[0]['file_name']!="")
			$poll_detail[0]['file_name']=wordwrap($poll_detail[0]['file_name'], 8, "\n", true);
			
		$this->_view->poll_detail=$poll_detail;
		$this->_view->render("ao_polledit");
	}
	
	/* To swtich poll status  closed/notclosed */
	public function closepollAction()
	{
		$poll_obj = new Ep_Delivery_Poll();
		$poll_obj->closepoll($_REQUEST['poll'],$_REQUEST['para']); 
	}
	
	/* Poll or poll participation detail pop up */
	public function pollpopupAction()
	{
		$poll_obj = new Ep_Delivery_Poll();
		if($_REQUEST['tooltype']=='poll')
		{
			 //Participation details
			$PollParticipationDetails=$poll_obj->ListPollPartcipation($_REQUEST['poll'],$_REQUEST['contribtype']);
			//Poll details
			$this->_view->polllist=$poll_obj->ListPollresult($_REQUEST['poll']);
			 for($i=0; $i<count($PollParticipationDetails);$i++)
             {
                $file = "/home/sites/site7/web/FO/profiles/contrib/pictures/".$PollParticipationDetails[$i]['user_id']."/".$PollParticipationDetails[$i]['user_id']."_h.jpg";
                 if(file_exists($file))
                 {
                      $PollParticipationDetails[$i]['contrib_home_picture']="/FO/profiles/contrib/pictures/".$PollParticipationDetails[$i]['user_id']."/".$PollParticipationDetails[$i]['user_id']."_h.jpg";
                 }
                 else
                 {
                      $PollParticipationDetails[$i]['contrib_home_picture']="/FO/images/Contrib/profile-img-def.png";
                 }
             }
            $this->_view->tooltipuser = "yes";
			$this->_view->PollParticipationDetails=$PollParticipationDetails;
			$this->_view->render("ao_poll_popup");
		}
		elseif($_REQUEST['tooltype']=='user')
		{
			$PollCOntributorDetails=$poll_obj->PollContributorDetails($_REQUEST['user']);
				$file = "/home/sites/site7/web/FO/profiles/contrib/pictures/".$PollCOntributorDetails[0]['user_id']."/".$PollCOntributorDetails[0]['user_id']."_h.jpg";
                 if(file_exists($file))
                    $PollCOntributorDetails[0]['contrib_home_picture']="/FO/profiles/contrib/pictures/".$PollCOntributorDetails[0]['user_id']."/".$PollCOntributorDetails[0]['user_id']."_h.jpg";
                 else
                    $PollCOntributorDetails[0]['contrib_home_picture']="/FO/images/Contrib/profile-img-def.png";
				if($PollCOntributorDetails[0]['education']==1)
				  $PollCOntributorDetails[0]['education']='Bac+1';
				elseif($PollCOntributorDetails[0]['education']==2)
				  $PollCOntributorDetails[0]['education']='Bac+2';
				elseif($PollCOntributorDetails[0]['education']==3)
				  $PollCOntributorDetails[0]['education']='Bac+3';
				elseif($PollCOntributorDetails[0]['education']==4)
				  $PollCOntributorDetails[0]['education']='Bac+4';
				elseif($PollCOntributorDetails[0]['education']==5)
				  $PollCOntributorDetails[0]['education']='Bac+5';
				elseif($PollCOntributorDetails[0]['education']==6)
				  $PollCOntributorDetails[0]['education']='Greater than Bac+5 ';
						if($PollCOntributorDetails[0]['dob']!="")
							$PollCOntributorDetails[0]['dob']=date("d/m/y", strtotime($PollCOntributorDetails[0]['dob']));
						//Category
						$PollCOntributorDetails[0]['favourite_category']=$this->getCategoryName($PollCOntributorDetails[0]['favourite_category']);
             $contribprofile='';
			 $contribprofile.='<div style="min-height:auto;max-height:200px;overflow:auto;margin-right:8px;overflow-x:hidden; border-radius:5px; margin-left:8px;">
									<table border="0" id="contrib_list_quicktip" style="max-height:40px;overflow:auto;" >
										<tr>
											<td style="color: #841515;font-size: 14px;padding-bottom: 5px;" colspan="3" align="center" valign="top"><b>'.utf8_encode($PollCOntributorDetails[0]['first_name']).'&nbsp;'.utf8_encode($PollCOntributorDetails[0]['last_name']).'</b></td>
										</tr>
										<tr>
											<td>
												<table>
														<tr>
															<td rowspan="5" valign="top">
																<img src="'.$PollCOntributorDetails[0]['contrib_home_picture'].'" style="border-radius:10px; border:1px dashed #999999;" />
															</td>
														</tr>
														<tr>
															<td>Schools:</td>
															<td>: <b>'.$PollCOntributorDetails[0]['university'].'</b></td>
														</tr>
														<tr>
															<td>Education </td>
															<td>: <b>'.$PollCOntributorDetails[0]['education'].'</b></td>
														</tr>
														<tr>
															<td>Date of Birth</td>
															<td>: <b>'.$PollCOntributorDetails[0]['dob'].'</b></td>
														</tr>
														<tr>
															<td>Favourite Categories</td>
															<td>: <b>'.utf8_encode($PollCOntributorDetails[0]['favourite_category']).'</b></td>
														</tr>
														<tr><td>&nbsp;</td></tr>
														<tr>
															<td colspan="2" valign="top">Self details</td>
															<td colspan="2">: '.utf8_encode(stripslashes($PollCOntributorDetails[0]['self_details'])).'</td>
														</tr>
												</table>
											</td>
										</tr>
									</table>
								</div>';
			echo $contribprofile;
		}
	}
	
	/* Poll question and response detail of poll participants */
	public function questiondetailAction()
	{
		$poll_id=$_REQUEST['poll'];
		$user_id=$_REQUEST['contrib'];
		$poll_obj = new Ep_Delivery_Poll();
		$poll_qdetails=$poll_obj->pollquestiondetails($poll_id,$user_id);
		$smic = $poll_obj->getSMICvalue($poll_id);
		
                 ob_start();
                 echo '<table border="1"> ';
					echo '<tr>
							<th>POLL</th>
							<td colspan="2">'.$poll_qdetails[0]['title'].'</td>
						</tr>
						<tr>
							<th>USER</th>
							<td colspan="2">'.$poll_qdetails[0]['first_name'].'&nbsp;'.$poll_qdetails[0]['last_name'].'</td>
						</tr>
						<tr><td>&nbsp;</td></tr>
						<tr>	
							<th>Questions:</th>
							<td colspan="2"></td>
						</tr>
						';	
							
                 		 for($p=0;$p<count($poll_qdetails);$p++)
						 {
							echo '<tr>
										<td valign="top">'.$poll_qdetails[$p]['question'].'</td>
										<td valign="top">'.$poll_qdetails[$p]['response'].'</td>';
							
							if($poll_qdetails[$p]['type']=='price' || $poll_qdetails[$p]['type']=='bulk_price' || $poll_qdetails[$p]['type']=='range_price')
							{
								if($poll_qdetails[$p]['response']>$smic)
									echo '<td>SMIC OK</td>';
								else
									echo '<td>SMIC NOK</td>';
							}
							else
								echo '<td></td>';
							
							echo '</tr>';
							 
						 }
                 echo '</table>';
				 
		$content = ob_get_contents();//exit;
		ob_end_clean();
		
		$filename = str_replace(" ","_",$poll_qdetails[0]['title']);
		header("Expires: 0");
		header("Cache-Control: post-check=0, pre-check=0", false);
		header("Pragma: no-cache");  header("Content-type: application/vnd.ms-excel;charset:UTF-8");
		header('Content-length: '.strlen($content));
		header('Content-disposition: attachment; filename='.$filename.'_questiondetail.xls');
		echo $content;
		exit;		
	}
	
	/* Pop up screen to fill Brief2 data */
	public function pollbrief2Action()
	{
		$art_type_array = $this->_arrayDb->loadArrayv2("EP_ARTICLE_TYPE", $this->_lang);
		$this->_view->ep_art_type=$art_type_array;
		
		//Getting brief2 details if already filled in
		$brief_obj=new Ep_Delivery_Pollbrief();
		$this->_view->briefdetail=$brief_obj->getPollBrief2($_REQUEST['poll']);
		
		$this->_view->poll=$_REQUEST['poll'];
		$this->_view->render("ao_pollbrief2");
	}
	
	/* Function to extract brief2 data into an xls */
	public function downloadbrief2Action()
	{
		$poll_id = $_REQUEST['poll'];
		$poll_obj = new Ep_Delivery_Poll(); 
		$brief_obj = new Ep_Delivery_Pollbrief();
		$briefdetail=$brief_obj->getPollBrief2($_REQUEST['poll']);
		
		$art_type_array = $this->_arrayDb->loadArrayv2("EP_ARTICLE_TYPE", $this->_lang);
		
		//Brief2 xls file saving path
		$downlpath=APP_PATH_ROOT.'poll_brief2/'.$poll_id.'/';
		
		$file = $downlpath.$briefdetail[0]['id'].'.xls';
                 ob_start();
                 echo '<table border="1" style="text-align:left;"> ';
					
                 	  echo '<tr>
								<td valign="top" style="font-weight:bold;">Does anyone ever worked with the client?</td>
								<td style="text-align:left;">'.$briefdetail[0]['work'].'</td>
							</tr>';
						
					if($briefdetail[0]['work'] == 'yes')
					{	
						echo '<tr>							
								<td valign="top" >In which year?</td>
								<td style="text-align:left;">'.$briefdetail[0]['year'].'</td>
							</tr>
							<tr>							
								<td valign="top" >Volume of the old contract?</td>
								<td style="text-align:left;">'.$briefdetail[0]['volume'].'</td>
							</tr>
							<tr>							
								<td valign="top" >Type of articles of the old contract?</td>
								<td style="text-align:left;">'.$art_type_array[$briefdetail[0]['articletype']].'</td>
							</tr>
							<tr>							
								<td valign="top" >Average price per article ?</td>
								<td style="text-align:left;">'.$briefdetail[0]['price'].'</td>
							</tr>
							<tr>							
								<td valign="top" >What was the level of client requirement ?</td>
								<td style="text-align:left;">'.$briefdetail[0]['level'].'</td>
							</tr>';
                    }
					
					if($briefdetail[0]['clientype'] == 'seo')
						$clienttype = 'SEO';
					else
						$clienttype = 'Editorial';
						
					if($briefdetail[0]['missionmange'] == 'contract')
						$missionmange = 'Signing the contract';
					else
						$missionmange = 'House editorial team';
						
						echo '<tr>							
								<td valign="top"  style="font-weight:bold;">Potential volume of this cliebt over the year</td>
								<td style="text-align:left;">'.$briefdetail[0]['potential'].'</td>
							</tr>
							<tr>							
								<td valign="top"  style="font-weight:bold;">Will he annexe potential in this mission</td>
								<td style="text-align:left;">'.$briefdetail[0]['potentialannexe'].'</td>
							</tr>
							<tr>							
								<td valign="top"  style="font-weight:bold;">Type of clients ?</td>
								<td style="text-align:left;">'.$clienttype.'</td>
							</tr>
							<tr>							
								<td valign="top" style="font-weight:bold;">Who will manage the mission at the client ? </td>
								<td style="text-align:left;">'.$missionmange.'</td>
							</tr>
							<tr>							
								<td valign="top" style="font-weight:bold;">Desired start date</td>
								<td style="text-align:left;">'.date("d/m/Y H:i:s",strtotime($briefdetail[0]['start_date'])).'</td>
							</tr>
							<tr>							
								<td valign="top" style="font-weight:bold;">Time allotted for the mission (in days) </td>
								<td style="text-align:left;">'.$briefdetail[0]['daylimit'].'</td>
							</tr>
							<tr>							
								<td valign="top" style="font-weight:bold;">Free comment </td>
								<td style="text-align:left;">'.$briefdetail[0]['comment'].'</td>
							</tr>	
								';
                 echo '</table>';
                 
				 
                 $content = ob_get_contents();
                 ob_end_clean();//echo $content;exit;
				 
				 $polldetail = $poll_obj->getPolldetails($_REQUEST['poll']);
				 $filename = str_replace(" ","_",$polldetail[0]['title']);
                 header("Expires: 0");
                 header("Cache-Control: post-check=0, pre-check=0", false);
                 header("Pragma: no-cache");  header("Content-type: application/vnd.ms-excel;charset:UTF-8");
                 header('Content-length: '.strlen($content));
                 header('Content-disposition: attachment; filename='.$filename.'_Brief2.xls');
                 echo $content;
                 exit;
		
	}
	
	/* Decimal number formating */
	function zero_cut($str,$digits=0)
	{
        $value=sprintf("%.${digits}f",$str);
		$value=number_format($str,2,',','');
        if(0==$digits)
                return $value;
        list($left,$right)=explode (",",$value);
        $len=strlen($right);
        $k=0; 
		 for($i=$len-1;$i>=0;$i--)
		{
                if('0'==$right{$i})
                        $k++;
                else
                        break; 
        }
        $right=substr($right,0,$len-$k);
        if($right!="")
            $right=",$right";
        return $left.$right;
	}
	
	/* Poll Configuration page - to create set of prefined questions for poll creation (which loads in step2)*/
	public function pollconfigurationAction()
	{
		$ques_obj=new Ep_Delivery_Pollconfiguration();
		
		if($_POST['submit_config']!="")
		{//print_r(array_filter($_POST['option']));print_r($_POST);exit;
			$ques_obj->UpdatePollQuestion($_POST);
			$this->_redirect("/ao/pollconfiguration?submenuId=ML2-SL23");
		}
		
		$pquestionlist=$ques_obj->getPollquestions();
		
			for($q=0;$q<count($pquestionlist);$q++)
			{
				//Different type of questions
				if($pquestionlist[$q]['type']=='price')
					$pquestionlist[$q]['type']='Price';
				elseif($pquestionlist[$q]['type']=='timing')
					$pquestionlist[$q]['type']='Duration';
				elseif($pquestionlist[$q]['type']=='radio')
					$pquestionlist[$q]['type']='Radio buttons';
				elseif($pquestionlist[$q]['type']=='bulk_price')
					$pquestionlist[$q]['type']='Wholesale price';
				elseif($pquestionlist[$q]['type']=='range_price')
					$pquestionlist[$q]['type']='Price Range';
				elseif($pquestionlist[$q]['type']=='checkbox')
					$pquestionlist[$q]['type']='Checkboxes';
				elseif($pquestionlist[$q]['type']=='calendar')
					$pquestionlist[$q]['type']='Calendar';	
				
			} 
		$this->_view->blocknamearr=$blocknamearr;
		$this->_view->ImgBlockarr=$ImgBlockarr;
		$this->_view->pquestionlist=$pquestionlist;
		
		$this->_view->render("ao_pollconfiguration") ;
	}
	
	/* Pop up to edit poll question */
	public function questioneditAction()
	{
		$ques_obj = new Ep_Delivery_Pollconfiguration();
		$quesdetail=$ques_obj->getPollquestions($_REQUEST['quesid']);
		
		if($quesdetail[0]['type'] == 'radio' || $quesdetail[0]['type'] == 'checkbox')
		{
			if($quesdetail[0]['option'] != "")
				$quesdetail[0]['optionlist'] = explode("|", $quesdetail[0]['option']);
		}
		
		$this->_view->quesdetail=$quesdetail;
		$this->_view->render("ao_questionedit");
	}
	
	/* Call to delete poll question*/
	public function questiondeleteAction()
	{
		$ques_obj=new Ep_Delivery_Pollconfiguration();
		$ques_obj->DeletePollQuestion($_REQUEST['quesid']);
	}
	
	/* SMIC configure */
	public function smicconfigureAction() 
	{
		$smic_obj=new Ep_Delivery_LanguageSMIC();
		
		//Update LanguageSMIC
		if($_REQUEST['smic_submit']!="")
		{
			$smic_obj->updateSMIC($_POST);
		}
		
		$language_array=$this->_arrayDb->loadArrayv2("EP_LANGUAGES", $this->_lang);
		$this->_view->language_array = $language_array;
		
		$smiclist = $smic_obj->ListLanguageSMIC();
		$this->_view->smiclist = $smiclist;
		
		$this->_view->render("ao_smicconfigure");
	}
	
	/* Category configure - to set/edit difficulty percentage for each category*/
	public function categorydifficultyAction()
	{
		$cat_obj = new Ep_Delivery_CategoryDifficultyPercent();
		
		//Update CategoryDifficultyPercent
		if($_REQUEST['catdiff_submit']!="")
		{
			$cat_obj->updateCatDiff($_POST);
		}
		
		$category_array=$this->_arrayDb->loadArrayv2("EP_ARTICLE_CATEGORY", $this->_lang);
		
		$catdifflist = $cat_obj->ListCategoryDifficulty();
			for($c=0;$c<count($catdifflist);$c++)
				$catdifflist[$c]['title'] = $category_array[utf8_encode($catdifflist[$c]['id'])];	
			
		$this->_view->catdifflist = $catdifflist;
		
		$this->_view->render("ao_categorydifficulty");
	}
	
	/********************************************************* AO Configuration ****************************************************/
	public function configurationAction()
	{
		$config_obj=new Ep_Delivery_Configuration();
		$request=$this->_request->getParams();
		//Display formation of different blocks
		for($l=1;$l<=12;$l++)
		{
			$blockname='ConfigBlock'.$l;
			$this->_view->$blockname="display:none;";
			$img='ImgBlock'.$l;
			$this->_view->$img="/BO/theme/gebo/img/plus16.png";
		}
		// Saving Configuration datat into db
		if($_POST['submit_config'])
		{
			$blocknamevar='ConfigBlock'.$_POST['blocknum'];
			$this->_view->$blocknamevar="display:block;";
			$imgvar='ImgBlock'.$_POST['blocknum'];
			$this->_view->$imgvar="/BO/theme/gebo/img/minus16.png";
				
			//Conversion of time if block 2
			if($_POST['blocknum']==2)
			{
				$_POST['jc0_time']=$this->convertTimeOptionencode($_POST['jc0_time'],$_POST['jc0_time_option']);
				$_POST['jc_time']=$this->convertTimeOptionencode($_POST['jc_time'],$_POST['jc_time_option']);
				$_POST['sc_time']=$this->convertTimeOptionencode($_POST['sc_time'],$_POST['sc_time_option']);
				$_POST['sc_bonus']=$this->convertTimeOptionencode($_POST['sc_bonus'],$_POST['sc_bonus_option']);
				$_POST['jc0_resubmission']=$this->convertTimeOptionencode($_POST['jc0_resubmission'],$_POST['jc0_resubmission_option']);
				$_POST['jc_resubmission']=$this->convertTimeOptionencode($_POST['jc_resubmission'],$_POST['jc_resubmission_option']);
				$_POST['sc_resubmission']=$this->convertTimeOptionencode($_POST['sc_resubmission'],$_POST['sc_resubmission_option']);
				$_POST['correction_jc_submission']=$this->convertTimeOptionencode($_POST['correction_jc_submission'],$_POST['correction_jc_submission_option']);
				$_POST['correction_sc_submission']=$this->convertTimeOptionencode($_POST['correction_sc_submission'],$_POST['correction_sc_submission_option']);
				$_POST['correction_jc_resubmission']=$this->convertTimeOptionencode($_POST['correction_jc_resubmission'],$_POST['correction_jc_resubmission_option']);
				$_POST['correction_sc_resubmission']=$this->convertTimeOptionencode($_POST['correction_sc_resubmission'],$_POST['correction_sc_resubmission_option']);
			}
			
			$config_obj->UpdateCongiguration($_POST);
			
			//Sending mail if block 1 or 2, as it contains sensitive data of the site
			/*if($_POST['blocknum']==1 || $_POST['blocknum']==2)
			{
					$mail = new Zend_Mail();
					
					$body='';
					if($_POST['blocknum']==1)
					{
						$body='Hi,<br><br>
								This mail is to inform you that "Mode of paiement" block has been updated in Configuartion Tool by '.$this->adminLogin->loginName.'
								<br><br>
								Regards,<br>
								Edit-place Team';
					}
					elseif($_POST['blocknum']==2)
					{	
						$body='Hi,<br><br>
								This mail is to inform you that "Contributeurs Participation" block has been updated in Configuartion Tool by '.$this->adminLogin->loginName.'
								<br><br>
								Regards,<br>
								Edit-place Team';
					}
					
					$mail->setBodyHTML($body)
						->setFrom('contact@edit-place.com')
						->addTo('mailpearls@gmail.com')
					   //->addCc('mailpearls@gmail.com')
					   ->setSubject('Configuration Tool updation - Test');
					$mail->send();
			}*/
		}
		$list_config=$config_obj->ListConfiguration();
			$ConfigList=array();
			for($c=0;$c<count($list_config);$c++)
			{
				$ConfigList[$list_config[$c]['configure_name']]=$list_config[$c]['configure_value'];
			}
			$ConfigList['jc0_timevalue']=$this->convertTimeOptiondecode($ConfigList['jc0_time']);
				$ConfigList['jc_timevalue']=$this->convertTimeOptiondecode($ConfigList['jc_time']);
				$ConfigList['sc_timevalue']=$this->convertTimeOptiondecode($ConfigList['sc_time']);
				$ConfigList['sc_bonusvalue']=$this->convertTimeOptiondecode($ConfigList['sc_bonus']);
				$ConfigList['jc0_resubmissionvalue']=$this->convertTimeOptiondecode($ConfigList['jc0_resubmission']);
				$ConfigList['jc_resubmissionvalue']=$this->convertTimeOptiondecode($ConfigList['jc_resubmission']);
				$ConfigList['sc_resubmissionvalue']=$this->convertTimeOptiondecode($ConfigList['sc_resubmission']);
				$ConfigList['correction_jc_submissionvalue']=$this->convertTimeOptiondecode($ConfigList['correction_jc_submission']);
				$ConfigList['correction_sc_submissionvalue']=$this->convertTimeOptiondecode($ConfigList['correction_sc_submission']);
				$ConfigList['correction_jc_resubmissionvalue']=$this->convertTimeOptiondecode($ConfigList['correction_jc_resubmission']);
				$ConfigList['correction_sc_resubmissionvalue']=$this->convertTimeOptiondecode($ConfigList['correction_sc_resubmission']);
			
		$this->_view->ConfigList=$ConfigList;
		$this->_view->render("ao_configuration");
	}
	
	/* Convert time from mins to day/hour/min*/
	function convertTimeOptiondecode($time)
	{
		if($time<60)
			return $time;
		elseif($time>=60 && $time<1440)
			return $time/60;
		elseif($time>=1440)
			return $time/(60*24);
	}
	
	/* Convert time from day/hour/min to mins*/
	function convertTimeOptionencode($timevalue,$option)
	{
		if($option=="day")
			return $timevalue/(60*24);
		elseif($option=="hour")
			return $timevalue/60;
		else
			return $timevalue;
	}
	
	public function listallmissionsAction()
    {	
        $user_obj = new Ep_User_User();
        $ao_obj=new Ep_Delivery_Delivery();
		$artprocess_obj=new EP_Delivery_ArticleProcess();
		  
		$languages_array=$this->_arrayDb->loadArrayv2("EP_LANGUAGES", $this->_lang);
        $categories_array=$this->_arrayDb->loadArrayv2("EP_ARTICLE_CATEGORY", $this->_lang);
		$this->_view->ep_categories_list=$categories_array;
        $this->_view->ep_languages_list=$languages_array;
		
		$client_info= $user_obj->GetclientList();
        $client_list=array();
        foreach($client_info as $key=>$value)
        {
            if($value['company_name']!="")
                $client_list[$value['identifier']]  =   strtoupper($value['company_name']) ;
            else
                $client_list[$value['identifier']]=strtoupper($value['email']);
        }
        asort($client_list);
        array_unshift($client_list, "S&eacute;lectionner");
		$this->_view->client_list=$client_list;
		 
		$ao_info= $ao_obj->getDeliverylist();
		foreach($ao_info as $key=>$value)
        {
            $ao_list[$value['id']]=strtoupper($value['title']);
        }
       
        if(count($ao_list)>0)
        {
            asort($ao_list);
            //array_unshift($ao_list, "S&eacute;lectionner");
        }
        $this->_view->mission_list=$ao_list;
        
        $ongoing_params=$this->_request->getParams();
		
        if($ongoing_params)
        {
            $searchParameters['start_date']=$ongoing_params['start_date'];
            $searchParameters['end_date']=$ongoing_params['end_date'];
            $searchParameters['pay_status']=$ongoing_params['pay_status'];
            $searchParameters['client_list']=$ongoing_params['client_list'];
            $searchParameters['mission_list']=$ongoing_params['mission_list'];
            $searchParameters['ep_category']=$ongoing_params['ep_category'];
            $searchParameters['language']=$ongoing_params['language'];
            $searchParameters['sorttype']=$ongoing_params['sorttype'];
            $this->_view->submenuId='ML2-SL20';
            $this->_view->startDate=$searchParameters['start_date'];
            $this->_view->endDate=$searchParameters['end_date'];
            $this->_view->payStatus=$searchParameters['pay_status'];
            $this->_view->clientList=$searchParameters['client_list'];
            $this->_view->missionList=$searchParameters['mission_list'];
            $this->_view->epCategory=$searchParameters['ep_category'];
            $this->_view->language=$searchParameters['language'];
            $this->_view->sorttype=$searchParameters['sorttype'];
        }
        $AO_details=$ao_obj->listAllMissions($searchParameters);
		unset($searchParameters);
        if($_REQUEST['debug']){echo '<pre>';print_r($AO_details);exit;}
        $turnover=0;
		$margin=0;
        if($AO_details!='NO')
        {
            foreach($AO_details as $res_key => $res_value)
            {
                if($AO_details[$res_key]['partId'] !='')
                {
                    $partid=$artprocess_obj->partidInArticleProcess($AO_details[$res_key]['partId']);
                    if($partid != 'NO')
                    {
                        $articleP=$artprocess_obj->getRecentVersion($partid[0]['participate_id']);
                        $AO_details[$res_key]['article_path']  = $articleP[0]['article_path'];
                        $AO_details[$res_key]['article_name']  = $articleP[0]['article_name'];
                    }
                    $artId =  $AO_details[$res_key]['art_id'];
                    $status =  $AO_details[$res_key]['status'];
                    
                    if($AO_details[$res_key]['paid_status'] == 'notpaid') :
                        $artPaymentInfo =   $ao_obj->getartproposedprice($artId) ;
                        $AO_details[$res_key]['artpayment'] =   ( ($artPaymentInfo[0]['price_user'] > 0) ? ceil(($artPaymentInfo[0]['price_user'] * 100) / $AO_details[$res_key]['contrib_percentage']) : '' ) ;
                    elseif(!empty($AO_details[$res_key]['invoice_id'])) :
                        $artPaymentamount = $ao_obj->getartpaymentinfo($AO_details[$res_key]['invoice_id']) ;
                        $AO_details[$res_key]['artPaymentamount'] = $artPaymentamount[0]['amount_paid'] ;
                    endif ;
                    
                    $statusarrya = explode('@',$AO_details[$res_key]['status']);
                    if(in_array('bid_nonpremium',$statusarrya))  {
                        $part_details=$ao_obj->getpartdetialsonstatus($artId,'bid_nonpremium');
                        if($part_details != 'NO')
                        {
                            $AO_details[$res_key]['status']  = $part_details[0]['status'];
                            $AO_details[$res_key]['partId']  = $part_details[0]['id'];
                            $AO_details[$res_key]['userCount']  = $part_details[0]['userCount'];
                        }
                    }
                    elseif(in_array('bid',$statusarrya))
                    {
                        $part_details=$ao_obj->getpartdetialsonstatus($artId,'bid');//
                        if($part_details != 'NO')
                        {
                            $AO_details[$res_key]['status']  = $part_details[0]['status'];
                            $AO_details[$res_key]['partId']  = $part_details[0]['id'];
                            $AO_details[$res_key]['userCount']  = $part_details[0]['userCount'];
                        }
                    }
                    elseif(in_array('under_study',$statusarrya))
                    {
                        $part_details=$ao_obj->getpartdetialsonstatus($artId,'under_study');//
                        if($part_details != 'NO')
                        {
                            $AO_details[$res_key]['status']  = $part_details[0]['status'];
                            $AO_details[$res_key]['partId']  = $part_details[0]['id'];
                            $AO_details[$res_key]['userCount']  = $part_details[0]['userCount'];
                        }
                    }
                    elseif(in_array('disapproved',$statusarrya))
                    {
                        $part_details=$ao_obj->getpartdetialsonstatus($artId,'disapproved');//
                        if($part_details != 'NO')
                        {
                            $AO_details[$res_key]['status']  = $part_details[0]['status'];
                            $AO_details[$res_key]['partId']  = $part_details[0]['id'];
                            $AO_details[$res_key]['userCount']  = $part_details[0]['userCount'];
                        }
                    }
                    elseif(in_array('closed_client_temp',$statusarrya))
                    {
                        $part_details=$ao_obj->getpartdetialsonstatus($artId,'closed_client_temp');//
                        if($part_details != 'NO')
                        {
                            $AO_details[$res_key]['status']  = $part_details[0]['status'];
                            $AO_details[$res_key]['partId']  = $part_details[0]['id'];
                            $AO_details[$res_key]['userCount']  = $part_details[0]['userCount'];
                        }
                    }
                    elseif(in_array('closed_client',$statusarrya))
                    {
                        $part_details=$ao_obj->getpartdetialsonstatus($artId,'closed_client');//
                        if($part_details != 'NO')
                        {
                            $AO_details[$res_key]['status']  = $part_details[0]['status'];
                            $AO_details[$res_key]['partId']  = $part_details[0]['id'];
                            $AO_details[$res_key]['userCount']  = $part_details[0]['userCount'];
                        }
                    }
					elseif(in_array('published',$statusarrya))
                    {
                        $part_details=$ao_obj->getpartdetialsonstatus($artId,'published');//
                        if($part_details != 'NO')
                        {
                            $AO_details[$res_key]['status']  = $part_details[0]['status'];
                            $AO_details[$res_key]['partId']  = $part_details[0]['id'];
                            $AO_details[$res_key]['userCount']  = $part_details[0]['userCount'];
                        }
                    }
                    else
                    {
                        $AO_details[$res_key]['status']  = '';
                    }
					$turnover+=$AO_details[$res_key]['price_final'];
					$margin+=($AO_details[$res_key]['price_final']-$AO_details[$res_key]['margin']);
					$this->_view->turnover = $turnover;
					$this->_view->margin = $margin;
                }
            }
            $this->_view->paginator = $AO_details;
        }
		$this->_view->now=strtotime("now");
        $this->_view->render("ao_listallmissions");
    }
	
	public function listaoloadAction()
	{
		$ao_obj=new Ep_Delivery_Delivery();
		$ao_list=$ao_obj->getMissionlist($_GET['client'],0);
		$select_ao='';
		$select_ao.='<select name="mission_list" id="mission_list" data-placeholder="SELECT AN OPTION">';
        
		for($cl=0;$cl<count($ao_list);$cl++)
		{
			$ao_list[$cl]['title']=$this->modifychar($ao_list[$cl]['title']);
			$select_ao.='<option value="'.$ao_list[$cl]['id'].'" selected>'.stripslashes(utf8_encode($ao_list[$cl]['title'])).'</option>';
		}
		$select_ao.='</select>';
		echo $select_ao;
	}
	
	public function closemissionAction()
    {
        $article=new EP_Delivery_Article();
        $profilelist_params=$this->_request->getParams();
        $article->mission_closed="closed" ;
        $article->id=$profilelist_params["artid"] ;
        $data = array("mission_closed"=>$article->mission_closed);////////updatin
        print_r($data);
        $query = "id= '".$article->id."'";
        $article->updateArticle($data,$query);
    }
	
	/** List of all articles whose submission time is expired ***/
    public function overDueAction()
    {
        $articleParams=$this->_request->getParams();
        $participation_obj=new EP_Participation_Participation();
		$user_obj=new EP_User_User();
        if($articleParams['action_from']=='popup')
        {
            $overDueArticles=$participation_obj->getOverDueArticlespopup($articleParams['participationId']);
            $extend_hours= 0;
            $parameters['ongoinglink']="/contrib/ongoing";
            $parameters['extend_hours']=$extend_hours;
            $emailContent=$this->getMailComments(49,$parameters);
            $this->_view->mail_content= utf8_encode(stripslashes($emailContent));
            $this->_view->pagefrom= $articleParams['from'];
        }
        else    
		{
			$overDueArticles=$participation_obj->getOverDueArticles($this->adminLogin->userId,$this->adminLogin->type);
		
		}		
		
        if($overDueArticles != "NO")
        {
            $cnt=0;
			foreach($overDueArticles as $article)
            {
				$overDueArticles[$cnt]['submit_expires']=date("d/m/Y H:i:s",$article['article_submit_expires']);
				$overDueArticles[$cnt]['submit_expires_sort']=date("Y-m-d H:i:s",$article['article_submit_expires']);
				if($article['created_user']!="")
					$overDueArticles[$cnt]['bouser']=$user_obj->getBOuser($article['created_user']);
				else
					$overDueArticles[$cnt]['bouser']='-';
				$cnt++;
			}
			$this->_view->overDueArticles = $overDueArticles;
		}
		else
		{
			$this->_view->nores = "true";
		}
        $this->_view->date = date("Y/m/d H:i");
        if($articleParams['action_from']=='popup')
            $this->_view->render("overdue_popup");
        else
            $this->_view->render("overdue_articles");
    }
	
	 /**get the mail message from automails table for extend time**/
    public function getMailComments($mailid,$parameters)
    {
        $automail=new Ep_Message_AutoEmails();
        $link='<a href="http://ep-test.edit-place.co.uk'.$parameters['document_link'].'">Click here</a>';
        $contributor='<b>'.$parameters['contributor_name'].'</b>';
        $article='<b>'.stripslashes($parameters['article_title']).'</b>';
        $AO_title='<b>'.stripslashes($parameters['AO_title']).'</b>';
        $extend_hours="<b>".$parameters['extend_hours']."</b>";
		$extend_date="<b>".$parameters['extend_date']."</b>";
		$ongoinglink='<a href="http://ep-test.edit-place.co.uk'.$parameters['ongoinglink'].'">Click here</a>';
		
        $email=$automail->getAutoEmail($mailid);
        $Object=$email[0]['Object'];
        $Message=$email[0]['Message'];
        eval("\$Message= \"$Message\";");
        return $Message;
    }
	
	/* Function to get mail for extending submission time of writer */
	public function getextendmailAction()
    {
        $articleParams=$this->_request->getParams();
       
        if(!$articleParams['extend_time'])
        {
           $articleParams['extend_time']=0;
        }
		$extend_hours= intval($articleParams['extend_time']);		
		
		
        $user_type=$articleParams['utype'];
        if($user_type=='corrector')
        	$participation_obj=new EP_Ongoing_CorrectorParticipation();
        else	
        	$participation_obj=new EP_Ongoing_Participation();

        $participation_id=$articleParams['participation_id'];
        

        if($participation_id)
        {
            $overDueArticles=$participation_obj->getOverDueArticles($participation_id);
			if($user_type=='corrector')	
			{				
				$submit_expires=$overDueArticles[0]['corrector_submit_expires'];	
			}
			else
			{
				$submit_expires=$overDueArticles[0]['article_submit_expires'];				
			}
			if($submit_expires>time())
				$parameters['extend_date']=date("d/m/Y H:i:s",($submit_expires+($extend_hours*60*60)));
			else	
				$parameters['extend_date']=date("d/m/Y H:i:s",(time()+($extend_hours*60*60)));
		}	
		
		
		$parameters['ongoinglink']="/contrib/ongoing";
		$parameters['extend_hours']=$extend_hours;
		$emailContent=$this->getMailComments(49,$parameters);
		$emailComments = utf8_encode(stripslashes($emailContent));
		echo $emailComments;
		exit;
	}
	
	/* Function to extend submission time of writer */
	public function extendArticleSubmitAction()
    {
		if($this->_request-> isPost())
		{
			$articleParams=$this->_request->getParams();
			$participation_obj=new EP_Participation_Participation();
			$user_obj = new EP_User_User();
			if($articleParams['participationId'])
			{
				$details=$participation_obj->getParticipateDetails($articleParams['participationId']);
				$participationId=$articleParams['participationId'];
				$extendDate=$articleParams['extend_date'];
				$extendTimestamp= time()+($extendDate*60*60);
				$data=array("article_submit_expires"=>$extendTimestamp,"extend_count"=>new Zend_Db_Expr('extend_count+1'));
				$query=" id='".$participationId."'";
				$participation_obj->updateParticipation($data,$query);
				$this->_helper->FlashMessenger("<b>Time Extended</b>");
				//ArticleHistory insertion
				if($articleParams['pagefrom']=='overdue')
				{
					$hist_obj = new Ep_Delivery_ArticleHistory();
					$action_obj = new EP_Delivery_ArticleActions();
					$history6=array();
					$history6['user_id']=$this->adminLogin->userId;
					$history6['article_id']=$details[0]['article_id'];
						$sentence6=$action_obj->getActionSentence(6);
						$project_manager_name='<b>'.ucfirst($this->adminLogin->loginName).'</b>';
						$article_name='<a href="/ongoing/ao-details?client_id='.$details[0]['clientId'].'&ao_id='.$details[0]['deliveryId'].'&submenuId=ML2-SL4" target="_blank"><b>'.$details[0]['title'].'</b></a>';
								$cname=$user_obj->getUsername($details[0]['user_id']);
						$contributor_name='<a class="writer" href="/user/contributor-edit?submenuId=ML2-SL7&tab=viewcontrib&userId='.$quote_params['quote'].'" target=_blank""><b>'.$cname.'</b></a>';
						$actionmessage=strip_tags($sentence6[0]['Message']);
						eval("\$actionmessage= \"$actionmessage\";");
					$history6['stage']='ongoing';
					$history6['action']='submittime_extended';
					$history6['action_sentence']=$actionmessage;
					$hist_obj->insertHistory($history6);
				}
				$Message = utf8_decode(stripslashes($articleParams['extend_comment']));
				$automail=new Ep_Message_AutoEmails();
				$email=$automail->getAutoEmail(49);
				$Object=$email[0]['Object'];
				$receiverId = $details[0]['user_id'];
				$this->sendMailEpMailBox($receiverId,$Object,$Message);
				if($articleParams['pagefrom']=='overdue')
					$this->_redirect("/ao/over-due?submenuId=ML2-SL11");
				else
				{
					$client_id=$details[0]['clientId'];
					$ao_id=$details[0]['deliveryId'];
					$this->_redirect("/ongoing/ao-details?client_id=$client_id&ao_id=$ao_id&submenuId=ML2-SL4");
				}	
			}
			else
			{
				if($articleParams['pagefrom']=='overdue')
					$this->_redirect("/ao/over-due?submenuId=ML2-SL11");
				else
					$this->_redirect("/ongoing/list?submenuId=ML2-SL4");
			}
		}
    }
	
	/************************************************ Premium option ************************************************/
	/* Interface to add/update premium options/services */
	public function premiumoptionsAction()
    {
        $options_obj = new Ep_Delivery_Options();
		$premoptions= $options_obj->getOptionsGrid();
        if($premoptions!=NULL)
        {
            $this->_view->paginator = $premoptions;
	    }
            $this->render('ao_premiumoptions');
    }
	
	/* Edit form of Premium options*/
	public function editpremoptionAction()
	{
        $optionId = $this->_request->getParam('optionId');
        $options_obj = new Ep_Delivery_Options();
        $details= $options_obj->getOptionDetailsOnId($optionId);
        $parents= $options_obj->getParentOptions();
        if($details[0]['parent'] != 0)
        {
            $parentOption= $options_obj->getParentOfThisOption($details[0]['parent']);
        }
        $this->_view->parentOption = $parentOption[0]['id'];
        $this->_view->parentoptions_list = $parents;
        $children= $options_obj->getChildOptions();
        $this->_view->optiondetails=$details;
        if($this->_request-> isPost())
        {
            $option_params=$this->_request->getParams();
            try
            {
                $options_obj->option_name=$option_params["option_name"] ;
                $options_obj->option_price=$option_params["option_price"] ;
                $options_obj->option_price_bo=$option_params["option_price_bo"] ;
                $options_obj->belongs=$option_params["belongs_to"] ;
                $options_obj->status=$option_params["status"] ;
                $options_obj->parent=$option_params["parentoptions_list"] ;
                if($options_obj->parent == 0)
                    $options_obj->type='unique';
                else
                    $options_obj->type='additional';
                $options_obj->description=stripcslashes($option_params["option_desc"]) ;
                $data_option = array("option_name"=>$options_obj->option_name, "option_price"=>$options_obj->option_price,
                                     "option_price_bo"=>$options_obj->option_price_bo, "belongs"=>$options_obj->belongs, "description"=>$options_obj->description,
                                    "parent"=>$options_obj->parent, "status"=>$options_obj->status, "type"=>$options_obj->type,);
                $query_option = "id= '".$optionId."'";
                $options_obj->updateOptions($data_option,$query_option);
                $this->_helper->FlashMessenger('Option Updated Successfully.');
                $this->_redirect("/ao/premiumoptions?submenuId=ML2-SL8");
            }
            catch(Zend_Exception $e)
            {
                echo $e->getMessage();
                $this->_view->error_msg =$e->getMessage()." Sorry! Getting error.";
                $this->render('ao_premiumoptions');
            }
        }
		else
            $this->render('ao_editpremoption');
    }
	
	/* Adding new premium option */
	public function addpremoptionAction()
	{
        $options_obj = new Ep_Delivery_Options();
        $parents= $options_obj->getParentOptions();
        $pat = array(0=>'set as a parent');
        $parents = $pat+$parents;
        
        $this->_view->parentoptions_list = $parents;
        if($this->_request-> isPost())
        {
            $option_params=$this->_request->getParams();
            try
            {
                $options_obj->option_name=$option_params["option_name"] ;
                $options_obj->option_price=$option_params["option_price"] ;
                $options_obj->option_price_bo=$option_params["option_price_bo"] ;
                $options_obj->belongs=$option_params["belongs_to"] ;
                $options_obj->status=$option_params["status"] ;
                $options_obj->parent=$option_params["parentoptions_list"] ;
                 if($options_obj->parent == 0)
                    $options_obj->type='unique';
                else
                    $options_obj->type='additional';
                
                $options_obj->description=stripcslashes($option_params["option_desc"]) ;
                $options_obj->insert();
                $this->_helper->FlashMessenger('Option Added Successfully.');
                $this->_redirect("/ao/premiumoptions?submenuId=ML2-SL8");
            }
            catch(Zend_Exception $e)
            {
                echo $e->getMessage();
                $this->_view->error_msg =$e->getMessage()." Sorry! Getting error.";
                $this->render('ao_addpremoption');
            }
        }
		else
           $this->render('ao_addpremoption');
    }
    
	//Deleting premium option
    public function deletepremoptionAction()
	{
        $optionId = $this->_request->getParam('optionId');
        $options_obj = new Ep_Delivery_Options();
        $details= $options_obj->getOptionDetailsOnId($optionId);
        $this->_view->optiondetails=$details;
        if($optionId != NULL)
        {
            $data_option = array("status"=>'deleted');
            $query_option = "id= '".$optionId."'";
            $options_obj->updateOptions($data_option,$query_option);
            $this->_helper->FlashMessenger('Option Deleted Successfully.');
            $this->_redirect("/ao/premiumoptions?submenuId=ML2-SL8");
        }
    }
	
	/************************************************************* Category price *********************************************/
	/* Inteface to update price for each available category in the platform */
	public function categorypriceAction()
	{
		$price_obj=new EP_Delivery_Pricenbwords();
		if($_REQUEST['submit_price'])
		{
			$Pricearray=array();
			$Pricearray['charprice']=$_REQUEST['charprice'];
			$Pricearray['wordprice']=$_REQUEST['wordprice'];
			$Pricearray['sheetprice']=$_REQUEST['sheetprice'];
			$price_obj->UpdatePriceCategory($Pricearray,$_REQUEST['category_id']);
		}
		$list_price=$price_obj->ListCategoryPrice();
		$categories_array=$this->_arrayDb->loadArrayv2("EP_ARTICLE_CATEGORY", $this->_lang);
		$this->_view->categories_array=$categories_array;
		$this->_view->PriceList=$list_price;
		$this->_view->pagelimit=$this->getConfiguredval('pagination_bo');
		$this->_view->render("ao_category_price");
	}
	/* Category price edit screen */
	public function categorypricepopupAction()
	{
		$price_obj=new EP_Delivery_Pricenbwords();
		$list_price=$price_obj->ListCategoryPrice($_REQUEST['cat']);
		$categories_array=$this->_arrayDb->loadArrayv2("EP_ARTICLE_CATEGORY", $this->_lang);
		$this->_view->categories_array=$categories_array[$list_price[0]['category_id']];
		$this->_view->PriceList=$list_price;
		$this->_view->render("ao_categoryprice_popup");
	}
	
	/****************************************  Automatize SC & JC  **********************************/
	/* Interface to upgrade/downgrade writer profile type based on defined condition */
	public function automatizescjcAction()
	{
		$user_obj=new Ep_User_User();
		//Updating upgrade /downgrade of contributors
		if($_REQUEST['update_all']!="")
		{
			for($u=0;$u<count($_REQUEST['contribtype']);$u++)
			{
				$vars=explode("_",$_REQUEST['contribtype'][$u]);
				
				$user_obj->UpdateContributortype($vars[0],$vars[1]);
				
				$parameters[]="";
				if($vars[1]=="up")
					$mail=30;//
				else
					$mail=32;//
				
				//sending mail
				$this->messageToEPMail($vars[0],$mail,$parameters);
			}
		}		
		//Fetching contributors
		$contrib_obj=new EP_User_Contributor();
		$Contriblist=$contrib_obj->ListContributors();
		
			for($c=0;$c<count($Contriblist);$c++)
			{
				$Contriblist[$c]['category']=$this->getCategoryName($Contriblist[$c]['favourite_category']);
				//In checkUpgradeDowngrade model function defined conditions are checked to upgrade/downgrade
				$Contriblist[$c]['updown']=$contrib_obj->checkUpgradeDowngrade($Contriblist[$c]['identifier'],$Contriblist[$c]['profile_type']);
			}
		//print_r($Contriblist);
		$this->_view->contriblist=$Contriblist;
		
		$this->_view->render("ao_automatizescjc");
	}
	
	/* Upgrading/downgrading writer based on action performed in above function */
	public function updateprofiletypeAction()
	{
		$contrib_obj=new Ep_User_User();
		$contrib_obj->UpdateContributortype($_REQUEST['contrib'],$_REQUEST['action']);
		
		$parameters[]="";
		if($_REQUEST['action']=="up")
			$mail=30;//
		else
			$mail=32;//
		
		$this->messageToEPMail($_REQUEST['contrib'],$mail,$parameters);
	}
	
	public function updateprofiletypemarksAction()
	{
		$contrib_obj=new Ep_User_User();
		$contrib_obj->UpdateContributortypeMarks($_REQUEST['contrib'],$_REQUEST['action'],$_REQUEST['ptype'],$this->adminLogin->userId);
		
		/*$parameters[]="";
		if($_REQUEST['action']=="up")
			$mail=30;//
		else
			$mail=32;//
		
		$this->messageToEPMail($_REQUEST['contrib'],$mail,$parameters);*/
	}
	
	/* Function to list all mission test articles with details, and options to upgrade/downgrade writer based on marks and to validate an article */
	public function markstatAction()
	{
		$article_obj=new EP_Delivery_Article();
		$recruitment_id=$this->_request->getParam('recruitment_id');
		$testmissions=$article_obj->getTestMissionArticles($recruitment_id);
		$this->_view->testmissions=$testmissions;
		$this->_view->page_title="Edit-place Admin : Statistiques de missions test";
		$this->_view->render("ao_markstat");
	}
	
	/* Writer & correction participation detail of an mission test article with scored Marks */
	public function marksdetailAction()
	{
		$article_obj=new EP_Delivery_Article();
		
		//writer details
		$writerdetail=$article_obj->getWriterdetail($_REQUEST['participate']);
		$this->_view->writerdetail=$writerdetail;
		
		//corrector details
		$correctiondetail=$article_obj->getCorrectiondetail($_REQUEST['participate']);
			$markarray=array();
			foreach($correctiondetail as $cdetail)
			{
				$markarray[]=$cdetail['marks'];
			}
			$this->_view->sum=array_sum($markarray);
			$this->_view->corrcnt=count($correctiondetail);
			$this->_view->average=($this->_view->sum)/($this->_view->corrcnt);
			
		$this->_view->correctiondetail=$correctiondetail;
		
		$this->_view->render("ao_markstatdetail");
	}
	
	/* Downloading writer/corrector article from detail pop up*/
	public function downloadarticleAction()
	{
		$ArtP_obj=new EP_Delivery_ArticleProcess();
		$article=$ArtP_obj->getArticlebyApid($_GET['process_id']);
		$dwlfile= '/home/sites/site7/web/FO/articles/'.$article[0]['article_path']; 
		$ext=$this->findexts($article[0]['article_path']);
		header('Content-type: application/force-download; charset=utf-8'); 
		header('Content-disposition: attachment;filename='.$article[0]['article_name'].'.'.$ext);
		readfile($dwlfile);
	}
	
	// Validating an mission test article and adding royalties (if alreday not added) from Mission test stats
	public function addroyaltiesAction()
	{
		if($this->_request->isPost() && $_SERVER['HTTP_X_REQUESTED_WITH']=='XMLHttpRequest')
		{
			$params=$this->_request->getParams();
			$article_id=$params['article'];
		$participate_obj=new Ep_Participation_Participation();
		$corrparticipate_obj=new Ep_Participation_CorrectorParticipation();
		$royalty_obj=new Ep_Payment_Royalties();
			
			$delivery_obj=new Ep_Delivery_Delivery();
			$deliveryDetails=$delivery_obj->getArtDeliveryDetails($article_id);
			if($deliveryDetails!='NO')
			{
				$free_article=$deliveryDetails[0]['free_article'];
			}
			//echo $free_article;exit;
				$writerdetail=$participate_obj->getWriter($article_id);
				if($royalty_obj->checkRoyaltyExists($article_id)=='NO')
			{ 
			
				//Writer
					//if($writerdetail[0]['price_user']!='0')
					//{
						if($free_article=='yes')
							$price_writer=0;
						else	
							$price_writer=$writerdetail[0]['price_user'];
					$royalty_obj->participate_id=$writerdetail[0]['id'];
						$royalty_obj->article_id=$article_id;
					$royalty_obj->user_id=$writerdetail[0]['user_id'];
						$royalty_obj->price=$price_writer;
					$royalty_obj->insert();
					//}
				//Correctors
					$correctordetail=$corrparticipate_obj->getCorrector($article_id);
				
				for($c=0;$c<count($correctordetail);$c++)
				{
						//if($correctordetail[$c]['price_corrector']!='0')
						//{
						$royalty_obj1=new Ep_Payment_Royalties();
						$royalty_obj1->participate_id=$correctordetail[$c]['participate_id'];
							$royalty_obj1->article_id=$article_id;
						$royalty_obj1->user_id=$correctordetail[$c]['corrector_id'];
						$royalty_obj1->price=$correctordetail[$c]['price_corrector'];
						$royalty_obj1->crt_participate_id=$correctordetail[$c]['id'];
							$royalty_obj1->correction='recruitment';
						$royalty_obj1->insert();
						unset($royalty_obj1);
						//}
					//Update Participation
					$where_crrart=" id='".$correctordetail[$c]['id']."'";
					$crrarr_art=array();
					$crrarr_art['status']='published';
					$corrparticipate_obj->updateCrtParticipation($crrarr_art,$where_crrart);
				}
			}
				
			//Update Participation
			$where_art=" id='".$writerdetail[0]['id']."'";
			$arr_art=array();
			$arr_art['status']='published';
			$participate_obj->updateparticipation($arr_art,$where_art);
			
			/*
			$art_obj = new Ep_Ao_Article();
			$artdetails=$art_obj->getArticledetails($paricipationdetails[0]['article_id']);
			//Mail send contrib
			$parameters['article']='<b>'.$artdetails[0]['title'].'</b>';
			$parameters['royalty']='<b>'.$paricipationdetails[0]['price_user'].'</b>';
			$this->messageToEPMail($paricipationdetails[0]['user_id'],44,$parameters);
			*/
		exit;	
		}	
	}
	
	/**************************************************** Premium Quotes *************************************/
	/* Page to list all premium quotes created from FO by client, with an option to download quotes */
	public function premiumquotesAction()
	{
		$this->_view->category_array=$this->_arrayDb->loadArrayv2("EP_ARTICLE_CATEGORY", $this->_lang);
		
		$del_obj=new Ep_Delivery_Delivery();
		$quoteslist=$del_obj->getPremiumQuotes();
		$this->_view->quoteslist=$quoteslist;
		
		$this->render('ao_premiumquotes');
	}
	
	/**************************************************** History of Quotes *************************************/
	/* Interface to add/update quotes history */
	public function quoteshistoryAction()
	{
		$hist_obj=new Ep_Quote_QuotesHistory();
		
		if($_REQUEST['historysubmit']!="")
		{
			$Harray=array();
			$Harray['type']=$_REQUEST['type'];
			$Harray['language']=$_REQUEST['language'];
			$Harray['content_type']=$_REQUEST['content_type'];
			$Harray['volume']=$_REQUEST['volume'];
			$Harray['variation']=$_REQUEST['variation'];
			$Harray['prod_cost']=$_REQUEST['prod_cost'];
			$Harray['reference']=$_REQUEST['reference'];
			$Harray['margin']=$_REQUEST['margin'];
			if($_REQUEST['language_var']!="")
				$Harray['language_var']=$_REQUEST['language_var'];
			//print_r($Harray);exit;	
			if($_REQUEST['historyid']!="")
			{
				$hist_obj->updateHistory($Harray,$_REQUEST['historyid']);
			}
			else
				$hist_obj->insertHistory($Harray);
				
		}
		
		$this->_view->type_array=array("seo"=>"SEO article","desc"=>"Product description","blog"=>"Blog article","news"=>"News","guide"=>"Guide","other"=>"Others");
		
		$historylist=$hist_obj->getAllQuotesHistory();
		
		for($h=0;$h<count($historylist);$h++)
		{
			$historylist[$h]['languagestring']=$this->getLanguageString($historylist[$h]['language']);
		}
		$this->_view->historylist=$historylist;
		$this->_view->usertype = $this->adminLogin->type;
		$this->render('ao_quoteshistory');
	}
	
	/* Edit screen of hisotry of quotes*/
	public function edithistoryAction()
	{
		$hist_obj=new Ep_Quote_QuotesHistory();
		$historydetail=$hist_obj->getQuotesHistoryById($_REQUEST['qid']);
		$this->_view->history=$historydetail;
		$this->render('ao_editHistory');
	}
	
	/* Getting language names when multiple lang ids are passed as parameter*/
	public function getLanguageString($lang)
	{
		if($lang!="other")
		{
			$lang_array=$this->_arrayDb->loadArrayv2("EP_LANGUAGES", $this->_lang);
			$langArr=explode(",",$lang);
			
			$str=array();
			foreach($langArr as $lg)
				$str[]=$lang_array[$lg];
			
			return implode(",",$str);
		}
		else
			return "Other Languages";
	}
	
	/* Deleting quotes history data */
	public function deletehistoryAction()
	{
		$hist_obj=new Ep_Quote_QuotesHistory();
		$hist_obj->deleteHistory($_REQUEST['history']);
	}
}
