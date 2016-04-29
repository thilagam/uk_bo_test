<?php
/**
 * Ongoing Controller for ongoing Ao actions and Edit AO
 *
 * @author
 * @version
 */
class OngoingController extends Ep_Controller_Action
{
	
	public function init()
	{
		parent::init();
		$this->_view->lang = $this->_lang;
		$this->adminLogin = Zend_Registry::get('adminLogin');
        $this->_view->userId = $this->adminLogin->userId;        
        $this->sid = session_id();	
        ////if session expires/////
       /*  if($this->adminLogin->loginName == '') {
           $this->_redirect("/index/processtest");
        } */
        if($this->_helper->FlashMessenger->getMessages()) {
	            $this->_view->actionmessages=$this->_helper->FlashMessenger->getMessages();
	            //echo "<pre>";print_r($this->_view->actionmessages); 
	    }
    }   
    //list all AO 
	public function listAction()
	{
		$aoObject=new EP_Ongoing_Delivery();
		$user_obj=new Ep_User_User();
		$del_obj=new EP_Delivery_Article();
		$searchParams=$this->_request->getParams();
		
		if($searchParams['deleteao']!="")
			$aoObject->DeleteDelivery($searchParams['deleteao']);
			
		//list all PMs
		$bouserlist=$user_obj->ListallBousers();
		$projectm_array=array();
		foreach($bouserlist as $bou)
			$projectm_array[$bou['identifier']]=$bou['first_name'].' '.$bou['last_name'];
		$this->_view->projectm_array=$projectm_array;
		
		if(count($_GET)>1)
			$this->_view->manager_id=$_GET['manager_id'];
		else
		{
			$this->_view->manager_id=$this->adminLogin->userId;
			$searchParams['manager_id']=$this->_view->manager_id;
			$searchParams['sorttype']="allongoing";
		}	
		
		$ongoingAoList=$aoObject->getOngoingAOList($searchParams);
		
		if($_REQUEST['debug']){echo "<pre>";print_r($ongoingAoList);exit;}

		
		if($ongoingAoList)
		{
            foreach($ongoingAoList as $key=>$value)
            {
                $artpercent = 0;
                $artids = $del_obj->getAllArticleIds($ongoingAoList[$key]['id']);
                $articleCount = count($artids);
                for($i=0; $i<$articleCount; $i++)
                {
                    $artdetails = $del_obj->getArticleDetails($artids[$i]['artId']);
                    $artpercent+= $artdetails[0]['progressbar_percent'];
                } //echo $artpercent; exit;
                if($artdetails[0]['correction'] == 'no')
                    $array = array(0=>'#ff0000', 15=>'#ff7200', 30=>'#ffa200', 45=>'#ffd21d', 65=>'#f2f43c', 85=>'#cbf43c', 100=>'#3fe805');
                else
                    $array = array(0=>'#ff0000', 12=>'#ff7200', 15=>'#ff7200', 25=>'#ffa200', 30=>'#ffa200', 37=>'#ffc600', 45=>'#ffd21d',
                    50=>'#ffd21d', 62=>'#f2f43c', 65=>'#f2f43c', 85=>'#f2f43c', 97=>'#cbf43c', 100=>'#3fe805');
                $progresspercentage = round($artpercent/$articleCount);
                foreach ($array as $k=>$v) {
                    if ($k >= $progresspercentage)
                    {
                        $colorcode = $array[$k];
                        break;
                    }
                }
                $ongoingAoList[$key]['progressbar'] = $progresspercentage;
                $ongoingAoList[$key]['progresscolorcode'] = $colorcode;
                /*$details = $del_obj->getDeliveryDetails($ao_ongoingaos[$key]['id']);
                $ao_ongoingaos[$key]['paid_status'] = $details[0]['paid_status'];*/

            }
            $this->_view->ongoingAO=$ongoingAoList;
		}	

		$this->_view->render("ongoing_list");
	}
	//getting all AO of a Client
	public function getAoListOngoingAction()
    {
        $ao_obj=new Ep_Delivery_Delivery();
        $aoParams=$this->_request->getParams();
        $ao_list=$ao_obj->getAOlist($aoParams['client'],0);
        $select_ao='';
        if($aoParams['ptype']=='ao_details')
        	$select_ao.='<select name="ao_id" id="deliveries" onChange="this.form.submit();" data-placeholder="Deliveries">';
        else
        	$select_ao.='<select name="ao_id" id="deliveries" data-placeholder="Deliveries">';
        if($aoParams['client'])
        {
        	$select_ao.='<option value="" ></option>';
        	
        }
        else
        	$select_ao.='<option value="" ></option>';
        for($cl=0;$cl<count($ao_list);$cl++)
        {
            $ao_list[$cl]['title']=$this->modifychar($ao_list[$cl]['title']);
            if($ao_list[$cl]['id']==$aoParams['ao'])
                $select_ao.='<option value="'.$ao_list[$cl]['id'].'" selected>'.stripslashes(utf8_encode($ao_list[$cl]['title'])).'</option>';
            else
                $select_ao.='<option value="'.$ao_list[$cl]['id'].'" >'.stripslashes(utf8_encode($ao_list[$cl]['title'])).'</option>';
        }
        $select_ao.='</select>';
        echo $select_ao;
    }
    //ongoing  details of a Delivery
    public function aoDetailsAction()
    {
    	$aoParams=$this->_request->getParams();
    	$aoObject=new EP_Ongoing_Delivery();
    	$articleObject=new EP_Ongoing_Article();
        $artprocess_obj = new EP_Delivery_ArticleProcess();
		$comments_obj=new Ep_Delivery_Adcomments();		
    	$ao_id=$aoParams['ao_id'];
    	$client_id=$aoParams['client_id'];
    	$aoParams['sorttype']='all';
    	
		if($_POST['sendcontrib_mail']!="")
		{
			//print_r($_POST);
			for($c=0;$c<count($_POST['contributor_list']);$c++)
			{
				$this->sendMailEpMailBoxOngoing($_POST['contributor_list'][$c],$_POST['email_subject'],$_POST['email_content']);
			}
		}
		
		if($ao_id && $client_id)
    	{
	
			$aoDetails=$aoObject->getOngoingAODetails($aoParams,1);
			//echo "<pre>";print_r($aoDetails);exit;
			
			if($aoDetails)	
			{
				
				//getting All article details of AO
				$aoParams['missiontest']=$aoDetails[0]['missiontest'];
				$articleDetails=$articleObject->getOngoingArticleDetails($aoParams);
				//echo "<pre>";print_r($articleDetails);exit;
				//getting writer and corrector Bidding Details
				$bcnt=0;
				foreach($articleDetails as $article)
				{
					$participationObject=new EP_Ongoing_Participation();
    				$cParticipationObject=new EP_Ongoing_CorrectorParticipation();
    				if($article['writerParticipation'])
    				{
						$articleDetails[$bcnt]['writer_bid_details']=$participationObject->getBiddingDetails($article['writerParticipation']);
						$articleDetails[$bcnt]['writer_facturation_details']=$participationObject->getFacturationDetails($article['writerParticipation'],$article['id']);
						$articleDetails[$bcnt]['writer_artproc_details']=$artprocess_obj->getLatestWriterArticle($article['id']);

    				}
					if($article['correctorParticipation'])
					{
						$articleDetails[$bcnt]['corrector_bid_details']=$cParticipationObject->getBiddingDetails($article['correctorParticipation']);
						$articleDetails[$bcnt]['corrector_facturation_details']=$cParticipationObject->getFacturationDetails($article['correctorParticipation'],$article['id']);
						$articleDetails[$bcnt]['corrector_artproc_details']=$artprocess_obj->getLatestCorrectionArticle($article['id']);
					}
					$articleDetails[$bcnt]['comment_count']=$comments_obj->checkNewCommentsCount($article['id'],'article',$aoDetails[0]['incharge_id']);
					$bcnt++;					
				}
				//echo "<pre>";print_r($articleDetails);exit;
				$this->_view->aoDetails=$aoDetails;
				$this->_view->articleDetails=$articleDetails;
				$this->_view->render("ongoing_ao_details");	
			}
			else
    			$this->_redirect("/ongoing/list?submenuId=ML2-SL4");
    		
    	}
    	else
    		$this->_redirect("/ongoing/list?submenuId=ML2-SL4");
    	
    }
    //Get ALl premium options
    public function getPremiumOptions()
    {
    	$ao_obj=new Ep_Delivery_Options();
		$del_obj = new Ep_Delivery_Delivery();
		$contrib_obj = new Ep_User_Contributor();
		
        $AllOptions=$ao_obj->getParentOptionsAO();
        for($o=0;$o<count($AllOptions);$o++)
        {
            $AllOptions[$o]['description1']=$AllOptions[$o]['description']."<br><br><p align=center><b>Prix de l'option premium:&nbsp;".$AllOptions[$o]['option_price_bo']."&euro; par article s&eacute;lectionn&eacute;</b></p>";
            $child=$ao_obj->getChildOptionsAO($AllOptions[$o]['id']);
            if(count($child)>0)
			{
                for($op=0;$op<count($child);$op++)
                {
                    $child[$op]['description']=$child[$op]['description']."<br><br><p align=center><b>Prix de l'option premium:&nbsp;".$child[$op]['option_price_bo']."&euro; par article s&eacute;lectionn&eacute;</b></p>";
                }
                $AllOptions[$o]['childlist']=$child;
            }
        }
        return $AllOptions;
        
    }
    //ongoing  details of a Delivery
    public function editAoAction()
    {
    	$editParams=$this->_request->getParams();
    	$editObject=new EP_Ongoing_Delivery();	
		$delivery_obj = new Ep_Delivery_Delivery();
    	$ao_id=$editParams['ao_id'];
    	$client_id=$editParams['client_id'];
    	$editParams['edit_ao']=TRUE;
		$editParams['sorttype']='all';
		
		
    	
    	if($ao_id && $client_id && $_SERVER['HTTP_X_REQUESTED_WITH']=='XMLHttpRequest')
    	{
    		//private correctors
			$correctorlist=$delivery_obj->getAllCorrectors();			
			$correctorlistall=array();

				//get previous cycle correctors
				$cParticipationObject=new EP_Ongoing_CorrectorParticipation();
				$prev_cycle_correctors=$cParticipationObject->getPrevCycleUsers($art_id);

				if($prev_cycle_correctors)
				{
					foreach($prev_cycle_correctors as $corrector)
					{
						$participated_correctors[]=$corrector['corrector_id'];
					}
				}
				else
					$participated_correctors=array();

				for ($i=0;$i<count($correctorlist);$i++)
				{
					$corrector_id=$correctorlist[$i]['identifier'];
					if(!in_array($corrector_id,$participated_correctors))
					{
						$correctorlistall[$i]=$correctorlist[$i];
						$name=$correctorlistall[$i]['email'];
						$nameArr=array($correctorlistall[$i]['first_name'],$correctorlistall[$i]['last_name']);
						$nameArr=array_filter($nameArr);
						if(count($nameArr)>0)
							$name.=" (".implode(", ",$nameArr).")";
						$correctorlistall[$i]['name']=strtoupper($name);
					}
				}

			$this->_view->correctorlistall=array_values($correctorlistall);
		
    		$aoDetails=$editObject->getOngoingAODetails($editParams,1);
    		$cnt=0;
            $aoDetails[0]['publish_language'] = explode(",",$aoDetails[0]['publish_language']);
            if($aoDetails[0]['correction_pricemin'] == 0)
                $aoDetails[0]['correction_pricemin']=null;
    		foreach($aoDetails as $delivery)
    		{
    			$aoDetails[$cnt]['view_to']=explode(",",$delivery['view_to']);
    			$aoDetails[$cnt]['child_options']=explode(",",$delivery['child_options']);
    			//converting submit time in to corresponding options
    			if($delivery['submit_option']=='day')
    			{
    				$aoDetails[$cnt]['subjunior_time']=$delivery['subjunior_time']/(24*60);
    				$aoDetails[$cnt]['junior_time']=$delivery['junior_time']/(24*60);
    				$aoDetails[$cnt]['senior_time']=$delivery['senior_time']/(24*60);
    			}
    			else if($delivery['submit_option']=='hour')
    			{
    				$aoDetails[$cnt]['subjunior_time']=$delivery['subjunior_time']/(60);
    				$aoDetails[$cnt]['junior_time']=$delivery['junior_time']/(60);
    				$aoDetails[$cnt]['senior_time']=$delivery['senior_time']/(60);
    			}
    			//converting resubmit time in to corresponding options
    			if($delivery['resubmit_option']=='day')
    			{
    				$aoDetails[$cnt]['jc0_resubmission']=$delivery['jc0_resubmission']/(24*60);
    				$aoDetails[$cnt]['jc_resubmission']=$delivery['jc_resubmission']/(24*60);
    				$aoDetails[$cnt]['sc_resubmission']=$delivery['sc_resubmission']/(24*60);
    			}
    			else if($delivery['resubmit_option']=='hour')
    			{
    				$aoDetails[$cnt]['jc0_resubmission']=$delivery['jc0_resubmission']/(60);
    				$aoDetails[$cnt]['jc_resubmission']=$delivery['jc_resubmission']/(60);
    				$aoDetails[$cnt]['sc_resubmission']=$delivery['sc_resubmission']/(60);
    			}
                //converting correction aspect submit time in to corresponding options
				if($delivery['correction']=="external")
				{
					$aoDetails[$cnt]['correctorsao_array']=explode(",",$delivery['corrector_privatelist']);
					$aoDetails[$cnt]['crtsubmit_option']=$delivery['correction_submit_option'];
					if($delivery['correction_submit_option']=='day')
					{
						$aoDetails[$cnt]['crtjunior_time']=$delivery['correction_jc_submission']/(24*60);
						$aoDetails[$cnt]['crtsenior_time']=$delivery['correction_sc_submission']/(24*60);
					}
					else if($delivery['correction_submit_option']=='hour')
					{
						$aoDetails[$cnt]['crtjunior_time']=$delivery['correction_jc_submission']/(60);
						$aoDetails[$cnt]['crtsenior_time']=$delivery['correction_sc_submission']/(60);
					}
					else if($delivery['correction_submit_option']=='min')
					{
						$aoDetails[$cnt]['crtjunior_time']=$delivery['correction_jc_submission'];
						$aoDetails[$cnt]['crtsenior_time']=$delivery['correction_sc_submission'];
					}

					//converting correction aspect  resubmit time in to corresponding options
					$aoDetails[$cnt]['crtresubmit_option']=$delivery['correction_resubmit_option'];
					if($delivery['correction_resubmit_option']=='day')
					{
						$aoDetails[$cnt]['crtjc_resubmission']=$delivery['correction_jc_resubmission']/(24*60);
						$aoDetails[$cnt]['crtsc_resubmission']=$delivery['correction_sc_resubmission']/(24*60);
					}
					else if($delivery['correction_resubmit_option']=='hour')
					{
						$aoDetails[$cnt]['crtjc_resubmission']=$delivery['correction_jc_resubmission']/(60);
						$aoDetails[$cnt]['crtsc_resubmission']=$delivery['correction_sc_resubmission']/(60);
					}
					else if($delivery['correction_resubmit_option']=='min')
					{
						$aoDetails[$cnt]['crtjc_resubmission']=$delivery['correction_jc_resubmission'];
						$aoDetails[$cnt]['crtsc_resubmission']=$delivery['correction_sc_resubmission'];
					}
				}
				
				$template_obj=new Ep_Message_Template();
				$this->_view->templatelist=$template_obj->getActiveValidationtemplates($delivery['product']);
    			$this->_view->variable=$delivery['product'].'refusal';
				$this->_view->templatearray=explode("|",$delivery['refusalreasons']);
				$cnt++;
    		}
           // echo $aoDetails[0]['correction_file']; exit;
            $filearr = explode("/",$aoDetails[0]['correction_file']);
             $aoDetails[0]['crtfile_name'] = $filearr[2];

    		if($aoDetails)	
			{
				$this->_view->aoDetails=$aoDetails;	
				$AllOptions=$this->getPremiumOptions();
				$this->_view->options=$AllOptions;
			    $this->_view->optioncount=count($AllOptions);
			    $this->_view->prem_ser=array(); 
				$config=$this->getConfiguredval('refusal_reasons_max');
				$this->_view->refusal_reasons_max=$config['refusal_reasons_max']; 
				$this->_view->render("edit_ao_details");		
			}	
    	}
		else
			$this->_redirect("/ongoing/list?submenuId=ML2-SL4");
    }
	
    //save delivery info
    public function saveDeliveryAction()
    {
    	if($this->_request-> isPost())            
        { 
        	$editParams=$this->_request->getParams();
			//print_r($editParams);exit;
        	$deliveyObj=new EP_Ongoing_Delivery();
        	$deliveyOptObj=new Ep_Delivery_DeliveryOptions();
        	$articleObj=new EP_Ongoing_Article();
        	$ao_id=$editParams['ao_id'];
        	$client_id=$editParams['client_id'];
        	if($ao_id && $client_id)
        	{
	        	$deliveryDetails=$deliveyObj->getDeliveryDetails($ao_id);
				$updateArray['title']=isodec($editParams['title']);
	        	//$updateArray['plagiarism_check']=$editParams['plagiarism_check'] ? 'yes' : 'no';
	        	$updateArray['deli_anonymous']=$editParams['deli_anonymous'] ? 1 : 0;
	        	$updateArray['AOType']=$editParams['ao_type'] ? 'private' : 'public';
	        	$updateArray['view_to']= implode(",",$editParams['view_to']);

				
                $updateArray['ao_visibility']=$editParams['ao_visibility'] ? 'show' : 'hide';
                $updateArticleArray['publish_language']=$updateArray['publish_language']=implode(",",$editParams['publish_language']);

	        	//submit times
	        	$updateArticleArray['submit_option']=$updateArray['submit_option']=$editParams['submit_option'];
	        	$updateArticleArray['subjunior_time']=$updateArray['subjunior_time']=$editParams['submit_option']=='day' ? $editParams['subjunior_time']*(1440) : ($editParams['submit_option']=='hour' ? $editParams['subjunior_time']*60 : $editParams['subjunior_time']);
	        	$updateArticleArray['junior_time']=$updateArray['junior_time']=$editParams['submit_option']=='day' ? $editParams['junior_time']*(1440) : ($editParams['submit_option']=='hour' ? $editParams['junior_time']*60 : $editParams['junior_time']);
	        	$updateArticleArray['senior_time']=$updateArray['senior_time']=$editParams['submit_option']=='day' ? $editParams['senior_time']*(1440) : ($editParams['submit_option']=='hour' ? $editParams['senior_time']*60 : $editParams['senior_time']);
	        	//RE submit times
	        	$updateArticleArray['resubmit_option']=$updateArray['resubmit_option']=$editParams['resubmit_option'];
	        	$updateArticleArray['jc0_resubmission']=$updateArray['jc0_resubmission']=$editParams['resubmit_option']=='day' ? $editParams['jc0_resubmission']*24*60 : ($editParams['resubmit_option']=='hour' ? $editParams['jc0_resubmission']*60 : $editParams['jc0_resubmission']);
	        	$updateArticleArray['jc_resubmission']=$updateArray['jc_resubmission']=$editParams['resubmit_option']=='day' ? $editParams['jc_resubmission']*24*60 : ($editParams['resubmit_option']=='hour' ? $editParams['jc_resubmission']*60 : $editParams['jc_resubmission']);
	        	$updateArticleArray['sc_resubmission']=$updateArray['sc_resubmission']=$editParams['resubmit_option']=='day' ? $editParams['sc_resubmission']*24*60 : ($editParams['resubmit_option']=='hour' ? $editParams['sc_resubmission']*60 : $editParams['sc_resubmission']);
	        	//Participation Time
	        	$updateArticleArray['participation_time']=$updateArray['participation_time']=$editParams['participation_time'];
                
				$updateArticleArray['product']=$editParams['product'];
				if($editParams['product']=="redaction")
					$updateArticleArray['refusalreasons']=implode("|",$editParams['redactionrefusal']);
				else
					$updateArticleArray['refusalreasons']=implode("|",$editParams['translationrefusal']);
				//Add article history when corrector price range is updated
				if($deliveryDetails[0]['correction_pricemin']!=$updateArray['correction_pricemin'] || $deliveryDetails[0]['correction_pricemax']!=$updateArray['correction_pricemax'])
				{
					$actionId=67;
					$actparams['aoId']=$ao_id;
					$actparams['stage']='ongoing';
					$actparams['action']='pricecorrrange_updated';
					$actparams['old_article_correction_price_range']=$deliveryDetails[0]['correction_pricemin'].'-'.$deliveryDetails[0]['correction_pricemax'];
					$actparams['new_article_correction_price_range']=$updateArray['correction_pricemin'].'-'.$updateArray['correction_pricemax'];
					$actparams['currency']=$deliveryDetails[0]['currency'];
					$this->articleHistory($actionId, $actparams);						
				}
				
				//submit correctoion details times
				if($editParams['correction'] == 'external')
				{
					$updateArticleArray['correction']='yes';
					if($editParams['correction_type']=="private")
					{
						$updateArticleArray['corrector_privatelist']=implode(",",$editParams['favcorrectorcheck']);
						$updateArray['corrector_privatelist'] = implode(",",$editParams['favcorrectorcheck']);
					}
					$updateArticleArray['correction_pricemin']=$updateArray['correction_pricemin']=$editParams['correction_pricemin'];
					$updateArticleArray['correction_pricemax']=$updateArray['correction_pricemax']=$editParams['correction_pricemax'];
					$updateArray['correction_type']=$editParams['correction_type'] ? 'private' : 'public';
					$updateArticleArray['correction_submit_option']=$updateArray['correction_submit_option']=$editParams['crtsubmit_option'];
					$updateArticleArray['correction_jc_submission']=$updateArray['correction_jc_submission']=$editParams['crtsubmit_option']=='day' ? $editParams['crtjunior_time']*(1440) : ($editParams['crtsubmit_option']=='hour' ? $editParams['crtjunior_time']*60 : $editParams['crtjunior_time']);
					$updateArticleArray['correction_sc_submission']=$updateArray['correction_sc_submission']=$editParams['crtsubmit_option']=='day' ? $editParams['crtsenior_time']*(1440) : ($editParams['crtsubmit_option']=='hour' ? $editParams['crtsenior_time']*60 : $editParams['crtsenior_time']);
					//RE correciton submit times
					$updateArticleArray['correction_resubmit_option']=$updateArray['correction_resubmit_option']=$editParams['crtresubmit_option'];
					$updateArticleArray['correction_jc_resubmission']=$updateArray['correction_jc_resubmission']=$editParams['crtresubmit_option']=='day' ? $editParams['crtjc_resubmission']*24*60 : ($editParams['crtresubmit_option']=='hour' ? $editParams['crtjc_resubmission']*60 : $editParams['crtjc_resubmission']);
					$updateArticleArray['correction_sc_resubmission']=$updateArray['correction_sc_resubmission']=$editParams['crtresubmit_option']=='day' ? $editParams['crtsc_resubmission']*24*60 : ($editParams['crtresubmit_option']=='hour' ? $editParams['crtsc_resubmission']*60 : $editParams['crtsc_resubmission']);
					// correction Participation Time
					$updateArticleArray['correction_participation']=$updateArray['correction_participation']=$editParams['crtparticipation_time'];
					$expires=time()+(60*$editParams['crtparticipation_time']);
					//$updateArticleArray['correction_participationexpires']=$expires;
					$updateArray['corrector_list'] = "CB";
				}
				else
					$updateArticleArray['correction']='no';
					
                if($editParams['correction'] == 'external')
                    $updateArray['correction'] = $editParams['correction'];
                else
                    $updateArray['correction'] = "internal";
	        	//Premium option
	        	if($editParams['premium_option'])
	        	{
					$option=explode("_",$editParams['premium_option']);	
					$updateArray['premium_option']=$option[0];
					$updateArray['premium_total']=$editParams['TotPrem'];
				}		
				$updateArray['urlsexcluded']=$editParams['urlsexcluded'];
				$updateArray['product']=$editParams['product'];
				if($editParams['product']=="redaction")
					$updateArray['refusalreasons']=implode("|",$editParams['redactionrefusal']);
				else
					$updateArray['refusalreasons']=implode("|",$editParams['translationrefusal']);
				//Fileupload
					$realfilename=$_FILES['uploadfile']['name'];
					$ext=pathinfo($realfilename);
					
					$uploaddir = '/home/sites/site7/web/FO/client_spec/';
					
					$client_id=$editParams['client_id'];
					$newfilename=$client_id.".".$ext["extension"];
					if(!is_dir($uploaddir.$client_id))
					{
						mkdir($uploaddir.$client_id,0777);
						chmod($uploaddir.$client_id,0777);
					}
					else
					{					
						chmod($uploaddir.$client_id,0777);
					}	
					
					$uploaddir=$uploaddir.$client_id."/";
					$bname=basename($realfilename,".".$ext["extension"])."_".uniqid().".".$ext["extension"];
					$bname=isodec($bname);
					//echo $bname;exit;
					$file = $uploaddir . $bname;
					if (move_uploaded_file($_FILES['uploadfile']['tmp_name'], $file))
					{
						chmod($file,0777);
						$updateArray['file_name']=$bname;
						$updateArray['filepath']="/".$client_id."/".$bname;
					}
                //corrector spec Fileupload////////////////////
                $crtrealfilename=$_FILES['crtuploadfile']['name'];
                $ext=pathinfo($crtrealfilename);

                $uploaddir = '/home/sites/site7/web/FO/correction_spec/';

                $client_id=$editParams['client_id'];
                $newfilename=$client_id.".".$ext["extension"];
                if(!is_dir($uploaddir.$client_id))
                {
                    mkdir($uploaddir.$client_id,0777);
                    chmod($uploaddir.$client_id,0777);
                }
                else
                {
                    chmod($uploaddir.$client_id,0777);
                }

                $uploaddir=$uploaddir.$client_id."/";
                $bname=basename($crtrealfilename,".".$ext["extension"])."_".uniqid().".".$ext["extension"];
                $bname=isodec($bname);
                //echo $bname;exit;
                $file = $uploaddir . $bname;
                if (move_uploaded_file($_FILES['crtuploadfile']['tmp_name'], $file))
                {
                    chmod($file,0777);
                    $updateArray['correction_file']="/".$client_id."/".$bname;
                }
					//echo "<pre>";print_r($updateArray);exit;
					$query=" id='".$ao_id."' and user_id='".$client_id."'";
                    //print_r($updateArray); exit;
					$deliveyObj->updateDelivery($updateArray,$query);

					$articleQuery=" delivery_id='".$ao_id."'";
					$articleObj->updateArticle($updateArticleArray,$articleQuery);
				///updating the article table with corection_type option /////
                if($editParams['correction'] == 'external'){
                    $artcle_obj = new EP_Delivery_Article();
                    $del_obj = new Ep_Delivery_Delivery();
                    $articles = $artcle_obj->getAllArticleIds($ao_id);
                    $arraylist = array();
                    if($articles != '')
                    {
                        foreach($articles AS $key=>$value)
                        { $allartlist[] = $articles[$key]['artId'];
                            $nopartart = $artcle_obj->getCorrectionChangeArticles($articles[$key]['artId']);
                            if($nopartart != 'NO')
                              $arraylist[] = $nopartart[0]['id'];

                        }

                    }
                    $finalarr = array_diff($allartlist, $arraylist);
                    foreach($finalarr AS $keys=>$values)
                    {
                        $data = array("correction"=>"yes");////////updating
                        $query = "id= '".$finalarr[$keys]."'";
                        $artcle_obj->updateArticle($data,$query);
                    }

                }
					//Updating Delivery options					
					if(count($editParams['premium_service'])>0)
					{
						$deliveyOptObj->deleteDeliveryOptions($ao_id);
						$deliveyOptObj->insertOptions($ao_id,$editParams['premium_service']);
					}
				$this->_helper->FlashMessenger("Les d&eacute;tails de l'ao ont &eacute;t&eacute; mis &agrave; jour avec succ&egrave;s");					
				$this->_redirect("/ongoing/ao-details?client_id=$client_id&ao_id=$ao_id&submenuId=ML2-SL4");
	        }
	        else
        		$this->_redirect("/ongoing/list?submenuId=ML2-SL4");
        }
        else
        	$this->_redirect("/ongoing/list?submenuId=ML2-SL4");
    }
	
	// Edit Article info
	public function editArticleAction()
    {
		
		$editParams=$this->_request->getParams();
    	$editObject=new EP_Ongoing_Article();	
		$delivery_obj = new Ep_Delivery_Delivery();
		$participate_obj=new EP_Ongoing_Participation();
        $cparticipate_obj=new Ep_Ongoing_CorrectorParticipation();
    	$art_id=$editParams['article_id'];
    	
    	if($art_id && $_SERVER['HTTP_X_REQUESTED_WITH']=='XMLHttpRequest')
    	{
    		//Private contribs
			$contriblist=$delivery_obj->getAllContribAO(0);			
			$contriblistall=array();
				for ($i=0;$i<count($contriblist);$i++)
					{
						$contriblistall[]=$contriblist[$i];
						$name=$contriblistall[$i]['email'];
						$nameArr=array($contriblistall[$i]['first_name'],$contriblistall[$i]['last_name']);
						$nameArr=array_filter($nameArr);
						if(count($nameArr)>0)
							$name.=" (".implode(", ",$nameArr).")";
						$contriblistall[$i]['name']=strtoupper($name);
					}
			$this->_view->contriblistall=$contriblistall;

			
			//private correctors
			$correctorlist=$delivery_obj->getAllCorrectors();			
			$correctorlistall=array();

				//get previous cycle correctors
	    		$cParticipationObject=new EP_Ongoing_CorrectorParticipation();
	    		$prev_cycle_correctors=$cParticipationObject->getPrevCycleUsers($art_id);

	    		if($prev_cycle_correctors)
	    		{
	    			foreach($prev_cycle_correctors as $corrector)
	    			{
	    				$participated_correctors[]=$corrector['corrector_id'];
	    			}
	    		}
	    		else
	    			$participated_correctors=array();

	    		


				for ($i=0;$i<count($correctorlist);$i++)
				{
					
					$corrector_id=$correctorlist[$i]['identifier'];

					if(!in_array($corrector_id,$participated_correctors))
					{
						$correctorlistall[$i]=$correctorlist[$i];
						$name=$correctorlistall[$i]['email'];
						$nameArr=array($correctorlistall[$i]['first_name'],$correctorlistall[$i]['last_name']);
						$nameArr=array_filter($nameArr);
						if(count($nameArr)>0)
							$name.=" (".implode(", ",$nameArr).")";
						$correctorlistall[$i]['name']=strtoupper($name);
					}

				}

			$this->_view->correctorlistall=array_values($correctorlistall);

			
			//check article is in stage2 or not
			$stage2Exists=$participate_obj->checkParticipationInStage2($art_id);
			if($stage2Exists)
				$this->_view->stage2='yes';	

		
			$ArticleDetails=$editObject->getEditArticleDetails($art_id);
            $this->_view->artuserprice = $participate_obj->getUserPrice($art_id);
            $this->_view->artcrtprice = $cparticipate_obj->getCorrectorPrice($art_id);
    		
    		if($ArticleDetails!="NO")	
			{
				$acnt=0;				
				foreach($ArticleDetails as $article)
				{					
					if($article['contribs_list'])
						$contrib_array=explode(",",$article['contribs_list']);

					if($article['corrector_privatelist'])
						$correctors_array=explode(",",$article['corrector_privatelist']);

				}				
				$this->_view->contrib_array=$contrib_array;
				$this->_view->correctors_array=$correctors_array;
				$template_obj=new Ep_Message_Template();
				$this->_view->templatelist=$template_obj->getActiveValidationtemplates($article['product']);
    			$this->_view->variable=$article['product'].'refusal';
				$this->_view->templatearray=explode("|",$article['refusalreasons']);
				$this->_view->ArticleDetails=$ArticleDetails;	
				//echo "<pre>";print_r($ArticleDetails);
				$config=$this->getConfiguredval('refusal_reasons_max');
				$this->_view->refusal_reasons_max=$config['refusal_reasons_max'];
				$this->_view->render("edit_article_details");		
			}	
    	}
		else
        	$this->_redirect("/ongoing/list?submenuId=ML2-SL4");
	}
	
	 //save article info
    public function saveArticleAction()
    {
    	if($this->_request-> isPost())            
        {
        	$editParams=$this->_request->getParams();
			$articleObj=new EP_Ongoing_Article();
			$participate_obj=new Ep_Ongoing_Participation();
			$cparticipate_obj=new Ep_Ongoing_CorrectorParticipation();
        	
        	$ao_id=$editParams['ao_id'];
        	$client_id=$editParams['client_id'];
        	$article_id=$editParams['article_id'];
        	if($ao_id && $client_id && $article_id)
        	{      
	        	$ArticleDetails=$articleObj->getEditArticleDetails($article_id);
				
				$updateArray['title']=isodec($editParams['title']);
	        	$updateArray['language']=isodec($editParams['language']);
	        	$updateArray['category']=isodec($editParams['category']);
	        	$updateArray['type']=isodec($editParams['type']);
	        	$updateArray['sign_type']=isodec($editParams['sign_type']);
	        	$updateArray['participation_time']=$editParams['participation_time'];
	        	$updateArray['num_min']=($editParams['num_min']);
	        	$updateArray['num_max']=($editParams['num_max']);
	        	$updateArray['price_min']=str_replace(",",".",$editParams['price_min']);
	        	$updateArray['price_max']=str_replace(",",".",$editParams['price_max']);
				
				//Add article history when price range is updated
				if($ArticleDetails[0]['price_min']!=$updateArray['price_min'] || $ArticleDetails[0]['price_max']!=$updateArray['price_max'])
				{
					$actionId=64;
					$actparams['artId']=$article_id;
					$actparams['stage']='ongoing';
					$actparams['action']='pricerange_updated';
					$actparams['old_article_writing_price_range']=$ArticleDetails[0]['price_min'].'-'.$ArticleDetails[0]['price_max'];
					$actparams['new_article_writing_price_range']=$updateArray['price_min'].'-'.$updateArray['price_max'];
					$actparams['currency']=$ArticleDetails[0]['currency'];
					$this->articleHistory($actionId, $actparams);
				}				
	        	$updateArray['contrib_percentage']=($editParams['contrib_percentage']);
                if(isset($editParams['correctiontype']))    ///when correction toggle is disabled////
                    $updateArray['correction']=$editParams['correctiontype'];
                else
                    $updateArray['correction']=$editParams['correction'] ? 'yes' : 'no';
	        	//echo $updateArray['correction']=$editParams['correction'] ? 'yes' : 'no';             exit;
				
				$updateArray['product']=$editParams['product'];
				if($editParams['product']=="redaction")
					$updateArray['refusalreasons']=implode("|",$editParams['redactionrefusal']);
				else
					$updateArray['refusalreasons']=implode("|",$editParams['translationrefusal']);
				if($editParams['part_id'])
				{
					//get old price
					$participationDetail=$participate_obj->getParticipationDetails($editParams['part_id']);
					$oldprice=$participationDetail[0]['price_user'];
					
					//updating Participation table for user price
					$pricearray=array("price_user"=>str_replace(",",".",$editParams['price_writer']));
					$query = "article_id= '".$article_id."' AND id = '".$editParams['part_id']."'";
					$participate_obj->updateParticipation($pricearray,$query);
					
					//Adding ArticleHistory if price user is updated
					if($oldprice!=$editParams['price_writer'])
					{
						$actionId=62;
						$actparams['contributorId']=$participationDetail[0]['user_id'];
						$actparams['artId']=$article_id;
						$actparams['stage']='ongoing';
						$actparams['action']='price_updated';
						$actparams['old_writer_price']=$oldprice;
						$actparams['new_writer_price']=$editParams['price_writer'];
						$actparams['currency']=$ArticleDetails[0]['currency'];
						$this->articleHistory($actionId, $actparams);
					}
				}    
				
	        	if($updateArray['correction']=='yes')
	        	{
					$updateArray['correction_pricemin']=str_replace(",",".",$editParams['correction_pricemin']);
	        		$updateArray['correction_pricemax']=str_replace(",",".",$editParams['correction_pricemax']);
	        		
	        		//Add article history when corrector price range is updated
					if($ArticleDetails[0]['correction_pricemin']!=$updateArray['correction_pricemin'] || $ArticleDetails[0]['correction_pricemax']!=$updateArray['correction_pricemax'])
					{
						$actionId=65;
						$actparams['artId']=$article_id;
						$actparams['stage']='ongoing';
						$actparams['action']='pricecorrrange_updated';
						$actparams['old_article_correction_price_range']=$ArticleDetails[0]['correction_pricemin'].'-'.$ArticleDetails[0]['correction_pricemax'];
						$actparams['new_article_correction_price_range']=$updateArray['correction_pricemin'].'-'.$updateArray['correction_pricemax'];
						$actparams['currency']=$ArticleDetails[0]['currency'];
						$this->articleHistory($actionId, $actparams);						
					}
					
					if($editParams['favcorrectorcheck'] &&  count($editParams['favcorrectorcheck'])>0)
	        			$updateArray['corrector_privatelist']=implode(",",$editParams['favcorrectorcheck']);

	        		$ArticleDetails=$articleObj->getEditArticleDetails($article_id);
		    		if($ArticleDetails!="NO")
					{
	        			$cparticipation=$ArticleDetails[0]['correction_participation'];
	        			$expires=time()+($cparticipation*60);
	        			//$updateArray['correction_participationexpires']=$expires;
                	}

	        		//updating Participation table
	        		$pa_array=array("current_stage"=>'corrector');
	        		$query = "article_id= '".$article_id."' AND status = 'under_study' AND current_stage='stage1'";       		
	        		$participate_obj->updateParticipation($pa_array,$query);

                    if($editParams['crtpart_id'])
	                {
	                     //get old price
						$corrparticipationDetail=$cparticipate_obj->getCorrectorParticipationDetails($editParams['crtpart_id']);
						$oldcorrprice=$corrparticipationDetail[0]['price_corrector'];
						
						//Adding ArticleHistory if price corrector is updated
						if($oldcorrprice!=$editParams['price_corrector'])
						{
							$actionId=63;
							$actparams['contributorId']=$corrparticipationDetail[0]['corrector_id'];
							$actparams['artId']=$article_id;
							$actparams['stage']='ongoing';
							$actparams['action']='correctorprice_updated';
							$actparams['old_corrector_price']=$oldcorrprice;
							$actparams['new_corrector_price']=$editParams['price_corrector'];
							$actparams['currency']=$ArticleDetails[0]['currency'];
							$this->articleHistory($actionId, $actparams);
						}
						
						//updating corrector Participation table for corrector prices ///
	                    $pricearray=array("price_corrector"=>str_replace(",",".",$editParams['price_corrector']));
	                    $query = "article_id= '".$article_id."' AND id = '".$editParams['crtpart_id']."'";
	                    $cparticipate_obj->updateParticipation($pricearray,$query);
	                }    

	        	}
	        	elseif($updateArray['correction']=='no')
	        	{
	        		//updating Participation table
	        		$pa_array=array("current_stage"=>'stage1');
	        		$query = "article_id= '".$article_id."' AND status = 'under_study' AND current_stage='corrector'";       		
	        		$participate_obj->updateParticipation($pa_array,$query);

                   //sending bid refuse to participated correctors
	        		$refused_list=$cparticipate_obj->sendEmailCorrectors($article_id,'refused');
	        		if($refused_list)
	        		{
		        		foreach($refused_list as $refused)
		        		{

							$automail=new Ep_Message_AutoEmails();
		        			$parameters['article_title']=$updateArray['title'];

		        			$automail->messageToEPMail($refused['corrector_id'],29,$parameters);

		        		}
		        	}	
	        		//echo "<pre>";print_r($refused_list);exit;
	        		

	        		//sending refuse definite emails to selected correctors
	        		$closed_list=$cparticipate_obj->sendEmailCorrectors($article_id,'closed');

	        		if($closed_list)
	        		{
		        		foreach($closed_list as $prefused)
		        		{

							$automail=new Ep_Message_AutoEmails();
		        			$parameters['article_title']=$updateArray['title'];

		        			$automail->messageToEPMail($prefused['corrector_id'],48,$parameters);

		        		}
		        	}	

		        	$update_cycle=$cparticipate_obj->getMaxCycle($article_id);
		        	$update_cycle=$update_cycle+1;

	        		//echo "<pre>";print_r($closed_list);exit;

	        		//updating Correction table
	        		$cpa_array=array("status"=>'bid_refused',"cycle"=>$update_cycle);
	        		$query = "article_id= '".$article_id."' AND status in ('bid_corrector','bid_refused','bid_refused_temp') AND current_stage='contributor'";       		
	        		$cparticipate_obj->updateParticipation($cpa_array,$query);

	        		$cpa_array1=array("status"=>'closed',"cycle"=>$update_cycle);
	        		$query1 = "article_id= '".$article_id."' AND status in ('bid','under_study','disapproved')";
	        		$cparticipate_obj->updateParticipation($cpa_array1,$query1);


	        		$cpa_array2=array("cycle"=>$update_cycle);
	        		$query2 = "article_id= '".$article_id."' AND cycle=0 AND status in ('bid_corrector','bid_refused','bid_refused_temp','bid','under_study','disapproved')";
	        		$cparticipate_obj->updateParticipation($cpa_array2,$query2);


	        		$updateArray['correction_participationexpires']=(time()-(5*60));
	        	}
	        	
	        	
	        	if($editParams['favcontribcheck'] &&  count($editParams['favcontribcheck'])>0)
	        		$updateArray['contribs_list']=implode(",",$editParams['favcontribcheck']);
	        		
	        	$ArticleDetails=$articleObj->getEditArticleDetails($article_id);
                if($ArticleDetails[0]['article_edit_at'] == NULL)
                    $updateArray['article_edit_at'] = $this->adminLogin->userId."|".date('Y-m-d H:i:s');
                else
                    $updateArray['article_edit_at'] = $ArticleDetails[0]['article_edit_at'].",".$this->adminLogin->userId."|".date('Y-m-d H:i:s');
	        	
	        	
					$query=" id='".$editParams['article_id']."'";
					$articleObj->updateArticle($updateArray,$query);
					
				$this->_helper->FlashMessenger('Les d&eacute;tails de article ont &eacute;t&eacute; mis &agrave; jour avec succ&egrave;s');
				$this->_redirect("/ongoing/ao-details?client_id=$client_id&ao_id=$ao_id&submenuId=ML2-SL4");
	        }
	        else
	        {
        		$this->_helper->FlashMessenger('Some error occured!!!');
        		$this->_helper->FlashMessenger('error');
        		$this->_redirect("/ongoing/list?submenuId=ML2-SL4");
	        }
        }
        else
        	$this->_redirect("/ongoing/list?submenuId=ML2-SL4");
    }
	
	////download Spec file////
    public function downloadSpecfileAction()
    {
        ////////download //////////////////////////////////////////
        $prevurl = getenv("HTTP_REFERER");
        $delivery_obj = new EP_Ongoing_Delivery();
        
        $spec_path = $this->_request->getParam('spec');
        if(isset($spec_path))
        {
            $details = $delivery_obj->getDeliveryDetails($spec_path);
            $specpath = ROOT_PATH."FO/client_spec".$details[0]['filepath'];
            $old_specpath = "/home/sites/site7/web/FO/client_spec".$details[0]['filepath'];
			
			$path_parts = pathinfo($specpath);
            $ext = strtolower($path_parts["extension"]);
			
			$fileName = $details[0]['title']."-specs.".$ext;
          
			if(file_exists($specpath))
            {
                $attachment=new Ep_Message_Attachment();
                $attachment->downloadAttachment($specpath,'attachment', $fileName);
                exit;
            }
            else if(file_exists($old_specpath))
            {
                $attachment=new Ep_Message_Attachment();
                $attachment->downloadAttachment($old_specpath,'attachment', $fileName);
                exit;
            }
            else
            {
                $this->_helper->FlashMessenger('File is not Available.');
                $this->_helper->FlashMessenger('error');
                $this->_redirect($prevurl);
            }
        }
    }
    ////download Spec file////
    public function downloadArticleAction()
    {
        ////////download //////////////////////////////////////////
        $prevurl = getenv("HTTP_REFERER");
        $process_obj = new EP_Ongoing_Participation();
        
        $download_params = $this->_request->getParams();
        $type=$download_params['type'];
        $process_id=$download_params['process_id'];
        
        if($type=='process' && $process_id)
        {
            $details = $process_obj->getProcessDetails($process_id);
            $article_path=$details[0]['article_path'];
            $article_name=$details[0]['article_name'];
            $file_path = ROOT_PATH."FO/articles/".$article_path;

            $old_file_path = "/home/sites/site7/web/FO/articles/".$article_path;


			
          
			if(file_exists($file_path))
            {
                $attachment=new Ep_Message_Attachment();
                $attachment->downloadAttachment($file_path,'attachment', $article_name);
                exit;
            }
            else if(file_exists($old_file_path))
            {
                $attachment=new Ep_Message_Attachment();
                $attachment->downloadAttachment($old_file_path,'attachment', $article_name);
                exit;
            }
            else
            {
                $this->_helper->FlashMessenger('File is not Available.');
                $this->_helper->FlashMessenger('error');
                $this->_redirect($prevurl);
            }
        }
    }
    /**Extend submit time for writer/corrector***/
    public function extendTimeAction()
    {
        $articleParams=$this->_request->getParams();
        $user_type=$articleParams['utype'];
        if($user_type=='corrector')
        	$participation_obj=new EP_Ongoing_CorrectorParticipation();
        else	
        	$participation_obj=new EP_Ongoing_Participation();
        $participation_id=$articleParams['participation_id'];
        
        if($participation_id)
        {
            $overDueArticles=$participation_obj->getOverDueArticles($participation_id);
			
			$extend_hours= 0;
            $parameters['ongoinglink']="/contrib/ongoing";
            $parameters['extend_hours']=$extend_hours;
            $emailContent=$this->getMailComments(49,$parameters);
			
            if($overDueArticles != "NO")
	        {
	            $cnt=0;
	            foreach($overDueArticles as $article)
	            {
	                if($user_type=='corrector')	
	                {
	                	$overDueArticles[$cnt]['submit_expires']=date("d/m/Y H:i:s",$article['corrector_submit_expires']);
						$emailContent=str_replace(htmlentities('rédiger'),"corriger",$emailContent);
	                }
	                else
	                {
	                	$overDueArticles[$cnt]['submit_expires']=date("d/m/Y H:i:s",$article['article_submit_expires']);				
						
					}	
	                $cnt++;
	            }
				$this->_view->paginator = $overDueArticles;
			}
			else
			{
				$this->_view->nores = "true";
			}           
            
            $this->_view->mail_content= utf8_encode(stripslashes($emailContent));
        }                
        $this->_view->date = date("Y/m/d H:i");
        $this->_view->user_type=$user_type;
        $this->_view->render("ongoing_extendtime_writer_popup");
        
    }
    //extend article submit time for writer/Corrector
    public function extendArticleSubmitAction()
    {
		if($this->_request-> isPost())
		{
			$articleParams=$this->_request->getParams();
			$participation_id=$articleParams['participation_id'];	
			$user_type=$articleParams['user_type'];
			$corr_obj=new Ep_Participation_CorrectorParticipation();
			
			if($user_type=='corrector')
				$participation_obj=new Ep_Participation_CorrectorParticipation();
			else
				$participation_obj=new Ep_Participation_Participation();
			
			if($participation_id && $user_type)
			{
				$extendDate=$articleParams['extend_date'];
				if($user_type=='corrector')
				{
					$details=$participation_obj->getCrtParticipateDetails($participation_id);
					if($details[0]['corrector_submit_expires'] >= time())
						$extendTimestamp= $details[0]['corrector_submit_expires']+($extendDate*60*60);
					else
						$extendTimestamp= time()+($extendDate*60*60);
				}
				else
				{
					$details=$participation_obj->getParticipateDetails($participation_id);
					if($details[0]['article_submit_expires'] >= time())
						$extendTimestamp= $details[0]['article_submit_expires']+($extendDate*60*60);
					else
						$extendTimestamp= time()+($extendDate*60*60);
				}
				
				
				
				if($user_type=='corrector')
				{
					$data=array("status"=>'bid',"corrector_submit_expires"=>$extendTimestamp,"extend_count"=>new Zend_Db_Expr('extend_count+1'));
					$query=" id='".$participation_id."'";
					$participation_obj->updateCrtParticipation($data,$query);
					//insert this action in history table 
		            $actionId=18;
		            $actparams['contributorId']=$details[0]['corrector_id'];
		            $actparams['artId']=$details[0]['article_id'];
		            $actparams['stage']='ongoing';
		            $actparams['action']='submittime_extended';
		            $this->articleHistory($actionId, $actparams);
				}
				else
				{
					$data=array("status"=>'bid',"article_submit_expires"=>$extendTimestamp,"extend_count"=>new Zend_Db_Expr('extend_count+1'));
					$query=" id='".$participation_id."'";
					$participation_obj->updateParticipation($data,$query);
					//insert this action in history table 
		            $actionId=6;
		            $actparams['contributorId']=$details[0]['user_id'];
		            $actparams['artId']=$details[0]['article_id'];
		            $actparams['stage']='ongoing';
		            $actparams['action']='submittime_extended';
		            $this->articleHistory($actionId, $actparams);
				}	
				
				$this->_helper->FlashMessenger("Time Extended");
				$Message = utf8_decode(stripslashes($articleParams['extend_comment']));
				$automail=new Ep_Message_AutoEmails();
				$email=$automail->getAutoEmail(49);
				$Object=$email[0]['Object'];
				
				if($user_type=='corrector')
					$receiverId = $details[0]['corrector_id'];
				else
					$receiverId = $details[0]['user_id'];	
				
				
				$automail->sendMailEpMailBox($receiverId,$Object,$Message);
				//Sending mail to the corrector in simultaneous correction case
				if($user_type=='writer')
				{
					if($details[0]['correction']=="yes")
					{
						$selectedcorrector=$corr_obj->getSelectedCorrector($details[0]['article_id']);
						if($selectedcorrector!="NO")
						{
							$parameters['articlename_link']='/contrib/ongoing';
							$parameters['article_title']=$details[0]['title'];
							$parameters['time_given_to_writer']=$articleParams['extend_date'];
							$automail->messageToEPMail($selectedcorrector,183,$parameters);
						}
					}
				}
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
     /**get the mail message from automails table for extend time**/
    public function getMailComments($mailid,$parameters)
    {
        $automail=new Ep_Message_AutoEmails();

        $AO_Creation_Date='<b>'.$parameters['created_date'].'</b>';
        $link='<a href="http://ep-test.edit-place.co.uk'.$parameters['document_link'].'">Click here</a>';
        $contributor='<b>'.$parameters['contributor_name'].'</b>';
        $AO_title="<b>".$parameters['AO_title']."</b>";
        $submitdate_bo="<b>".date("d/m/Y",strtotime($parameters['submitdate_bo']))."</b>";
        $total_articles="<b>".$parameters['noofarts']."</b>";
        $invoicelink='<a href="http://ep-test.edit-place.co.uk'.$parameters['invoice_link'].'">Click here</a>';
        $article_link='<a href="'.$parameters['article_link'].'">Cliquant-ici</a>';
        $client='<b>'.$parameters['client_name'].'</b>';
        $royalty='<b>'.$parameters['royalty'].'</b>';
        $ongoinglink='<a href="http://ep-test.edit-place.co.uk'.$parameters['ongoinglink'].'">Click here</a>';
        $AO_end_date='<b>'.$parameters['AO_end_date'].'</b>';
        $article='<b>'.stripslashes($parameters['article_title']).'</b>';
        $AO_title='<b>'.stripslashes($parameters['AO_title']).'</b>';
        $resubmit_time='<b>'.stripslashes($parameters['resubmit_time']).'</b>';
        $site='<a href="http://ep-test.edit-place.co.uk">Edit-place</a>';
        $extend_hours="<b>".$parameters['extend_hours']."</b>";
		$extend_date="<b>".$parameters['extend_date']."</b>";
        $articlewithlink='<a href="'.$parameters['articlename_link'].'">'.stripslashes($parameters['article_title']).'</a>';
        $aowithlink='<a href="'.$parameters['aoname_link'].'">'.stripslashes($parameters['AO_title']).'</a>';
		
        $email=$automail->getAutoEmail($mailid);
        $Object=$email[0]['Object'];
        $Message=$email[0]['Message'];
        eval("\$Message= \"$Message\";");
        return $Message;        
    }
    //AO history
    public function aoHistoryAction()
    {
    	$aoParms=$this->_request->getParams();
    	$article_id=$aoParms['article_id'];
    	$ao_id=$aoParms['ao_id'];
    	$client_id=$aoParms['client_id'];
    	$history_obj=new Ep_Delivery_ArticleHistory();
    	$articleObject=new EP_Ongoing_Article();   	
    	
    	if(($ao_id OR $article_id OR $client_id) && $_SERVER['HTTP_X_REQUESTED_WITH']=='XMLHttpRequest')
    	{
	    	
	    	if($client_id)
	    	{

	    		$articleDetails=$articleObject->OngoingSuperClientArticleDetails($aoParms);
	    		if($articleDetails)
	    		{
	    			//$article_array['all']='all';
	    			foreach($articleDetails as $article)
	    			{
	    				$article_array[$article['delivery_id']]=$article['delivery_title'];
	    			}    	
	    			if(!$aoParms['ao_id'])			
	    				$aoParms['ao_id']=$articleDetails[0]['delivery_id'];
	    		}
	    	}
	    	else
	    	{
	    		$articleDetails=$articleObject->getOngoingArticleDetails($aoParms);
	    		if($articleDetails)
	    		{
	    			$article_array['all']='all';
	    			foreach($articleDetails as $article)
	    			{
    				$article_array[$article['id']]=utf8_encode($article['title']);
	    			}    			
	    		}	
	    	}	
	    		$this->_view->article_array=$article_array;
    		$historyDetails=$history_obj->getAOHistory($aoParms);
    		if($historyDetails)
    		{
    			$h=0;
    			foreach($historyDetails as $details)
    			{
    				$historyDetails[$h]['action_at']=date("d/m/Y H:i",strtotime($details['action_at']));
    				$h++;
    			}
    		}
    		//echo "<pre>";print_r($historyDetails);
    	}
    	$this->_view->aoHistory=$historyDetails;
    	$this->_view->render("ao_history_popup");
    }
	
	//AO contributor mail
    public function aoContribmailAction()
    {
    	$aoParms=$this->_request->getParams();
    	$ao_id=$aoParms['ao_id'];
    	$deliveryObject=new EP_Ongoing_Delivery();   	
    	
		$this->_view->deliveryid=$ao_id;
		$this->_view->allconributors=$deliveryObject->getContributorsAo($ao_id,'writer|corrector');
    	
    	$this->_view->aoHistory=$historyDetails;
    	$this->_view->render("ao_contribmail_popup");
    }
	
	public function getAowritersAction()
	{
		$aoParms=$this->_request->getParams();
		$deliveryObject=new EP_Ongoing_Delivery();
		$conributorslist=$deliveryObject->getContributorsAo($aoParms['delivery'],$aoParms['ctype']);	
		
		$selectcontrib='<select name="contributor_list[]" id="contributor_list" multiple="multiple" data-placeholder="Select contributor..." style="width:400px">';
							
		//print_r($conributorslist);exit;				
		foreach($conributorslist as $contrib)
		{
			$selectcontrib.='<option value="'.$contrib['identifier'].'" selected>'.$contrib['email'].' ('.utf8_encode($contrib['first_name']).' , '.utf8_encode($contrib['last_name']).')</option>';
		}
		
		$selectcontrib.='</select>';
		
		echo $selectcontrib;
	}
	
	//Article comment Details
	public function articleCommentsAction()
	{
		$aoParms=$this->_request->getParams();
		$comments_obj=new Ep_Delivery_Adcomments();
		$comment_type='article';
    	$article_id=$aoParms['article_id'];
		if($article_id)
		{
			//Comment Details
			$commentDetails=$comments_obj->getAdComments($article_id,$comment_type);
			if(count($commentDetails)>0)
				$commentDetails=$this->formatCommentDetails($commentDetails);
				
			$this->_view->commentDetails=$commentDetails;
			$this->_view->comment_type= $comment_type;
			$this->_view->identifier=$article_id;
            $this->_view->commentCount=count($commentDetails);	
			$this->_view->render("article_comments_popup");		
		}	
	}
	//save Comments Action
    public function saveCommentsAction()
    {
      
        if($_SERVER['HTTP_X_REQUESTED_WITH']=='XMLHttpRequest')
        {       
            $comments_obj=new Ep_Delivery_Adcomments();
            $user_identifier=$this->adminLogin->userId;
            $comment_params=$this->_request->getParams();

 			//echo "<pre>";print_r($comment_params);exit;

            $type=$comment_params['comment_type'];
            $type_identifier=$comment_params['identifier']; 
			 //for deleting or editing
            $action=$comment_params['comment_action'];
            $comment_identifier=$comment_params['comment_id'];
			
			if($action=='update')
				$comments=$comment_params['article_comments'];
			else
				$comments=utf8dec($comment_params['article_comments']);	
             
            if($action=='delete' && $comment_identifier!='' && $type && $type_identifier)
            {
                //$is_comment_user=$comments_obj->checkCommentUser($comment_identifier,$user_identifier);
				$is_comment_user='YES';
                
                if($is_comment_user=='YES')
                {
                  $comment_update['active']='no';
                  $comments_obj->updateCommentDetails($comment_update,$comment_identifier);
                }
            }
			else if($action=='update' && $comment_identifier!='' && $type)
            {
                $is_comment_user=$comments_obj->checkCommentUser($comment_identifier,$user_identifier);               
                if($is_comment_user=='YES')
                {					
					$data['comments']=$comments;
					if($comments)
						$comments_obj->updateCommentDetails($data,$comment_identifier);
                }
            }

            else if($type && $type_identifier && $comments)
            {
                $comments_obj->user_id=$user_identifier;
                $comments_obj->type=$type;
                $comments_obj->type_identifier=$type_identifier;
                $comments_obj->comments=$comments;
                $comments_obj->created_at=date("Y-m-d H:i:s");
                $comments_obj->active='yes';
  
                try
                {
                   $comments_obj->insert();

                   //send notification email to writers
                   $send_notification=$comment_params['send_notification'];
                   if($send_notification)
                   {
                   		$send_notification=explode("|",$send_notification);
                   		$notify_writers=array_values(array_unique($send_notification));

                   		$article_id=$type_identifier;

                   		$article_obj=new EP_Ongoing_Article();
                   		$ticket_obj= new Ep_Message_Ticket();
                   		     			

		        			

                   		$articleDetails=$article_obj->getEditArticleDetails($article_id);

                   		$parameters['article_title']=$articleDetails[0]['title'];
                   		$parameters['bo_user']=$ticket_obj->getUserName($this->adminLogin->userId);
                   		$parameters['ongoinglink']="/contrib/ongoing?mission_type=premium&mission_identifier=".$type_identifier;             		
                   		
                   		foreach($notify_writers as $writer)
                   		{
                   			//echo $writer;exit;
                   			$automail=new Ep_Message_AutoEmails();
                   			$automail->messageToEPMail($writer,118,$parameters);
                   		}

                   }


                }
                catch(Zend_Exception $e)
                {
                    echo $e->getMessage();exit;                    
                }
            }
            $commentDetails=$comments_obj->getAdComments($type_identifier,$type);
             $commentsData='';
             $cmtCount=count($commentDetails);
            if($cmtCount>0)
            {
                
                $commentDetails=$this->formatCommentDetails($commentDetails);
                $commentsData='';
                $cnt=0;
                foreach($commentDetails as $comment)  
                {
                  $commentsData.=
                  '<li class="media" id="comment_'.$comment['identifier'].'">';
					                
				   if($comment['user_id']==$user_identifier)
						  $commentsData.='<a  class="close hint--left" data-hint="Edit Comment" type="button" id="edit_comment_'.$comment['identifier'].'"><i class="icon-pencil"></i></a>';
					
					$commentsData.='<a  class="close hint--left" data-hint="Hide Comment" type="button" id="delete_comment_'.$comment['identifier'].'">&times;</a>';   	  
				

					 if($comment['user_id']!=$user_identifier)
					 {
					 	$commentsData.='<label class="uni-checkbox pull-left">
                            <input type="checkbox" name="user_val" id="user_val_'.$cnt.'" class="uni_style " value="'.$comment['user_id'].'">
							</label>';
					 }


                  $commentsData.='<a class="pull-left imgframe" href="#" role="button" data-toggle="modal" data-target="#viewProfile-ajax">
                        <img alt="Topito" class="media-object" width="60px" src="'.$comment['profile_pic'].'">
                      </a>
                      <div class="media-body">
                        <h4 class="media-heading">
                          <a href="#" role="button" data-toggle="modal" data-target="#viewProfile-ajax">'.utf8_encode($comment['profile_name']).'</a></h4>
                          <span id="user_comment_'.$comment['identifier'].'">'.utf8_encode(stripslashes($comment['comments'])).'</span>
						  
						  <span id="edit_user_comment_'.$comment['identifier'].'" style="display:none">
							<textarea class="span10" name="article_comments_'.$comment['identifier'].'" id="article_comments_'.$comment['identifier'].'">'.utf8_encode(stripslashes($comment['comments'])).'</textarea>
							<button type="button" id="update_submit_'.$comment['identifier'].'" name="update_submit_'.$comment['identifier'].'" class="btn">Mettre &agrave; jour</button>
						</span>
                        <p class="muted">'.$comment['time'].'</p>
                      </div>			  
                    </li>';
                }
            }
            echo  json_encode(array('comments'=>$commentsData,'count'=>$cmtCount));
        }
        else
          $this->_redirect("/contrib/home");
           
    }
	//format comment Details
	public function formatCommentDetails($commentDetails)
    {
        $ticket=new Ep_Message_Ticket();
        $user_identifier=$this->adminLogin->userId;
        $cnt=0;
        foreach($commentDetails as $details)
        {
           if($details['user_type']=='contributor')
              $commentDetails[$cnt]['profile_pic']= $this->getPicPath($details['user_id']);
           else if($details['user_type']=='client')
              $commentDetails[$cnt]['profile_pic']= $this->getClientPicPath($details['user_id'],'home');
            else
              $commentDetails[$cnt]['profile_pic']= $this->getPicPath($details['user_id'],'bo_user');
           $commentDetails[$cnt]['profile_name']= $ticket->getUserName($details['user_id']);   
           $commentDetails[$cnt]['time']= time_ago($details['created_at']);

           if($user_identifier==$details['user_id'])
              $commentDetails[$cnt]['edit']='yes';

           $cnt++;
        }   
        return $commentDetails;
    }
	/*Function to get the picture of a contributor**/
    public function getPicPath($identifer,$action='home')
    {
        $app_path='/home/sites/site7/web/';
        if($action=='bo_user')
          $profiledir='FO/profiles/bo/'.$identifer.'/';
        else  
          $profiledir='FO/profiles/contrib/pictures/'.$identifer.'/';


        if($action=='home')
            $pic=$identifer."_h.jpg";
        else if($action=='bo_user') 
            $pic="logo.jpg";
        else
            $pic=$identifer."_p.jpg";
        if(file_exists($app_path.$profiledir.$pic))
        {
            $pic_path="http://ep-test.edit-place.co.uk/".$profiledir.$pic;
        }
        else
        {
            if($action=='home' OR $action=='bo_user')
              $pic_path="http://ep-test.edit-place.co.uk/FO/images/editor-noimage_60x60.png";
            else
              $pic_path="http://ep-test.edit-place.co.uk/FO/images/editor-noimage.png";
        }
        return $pic_path;
    }
    /*Function to get the picture of a client**/
    public function getClientPicPath($identifer,$action='home')
    {
         $app_path='/home/sites/site7/web/';
        $profiledir='FO/profiles/clients/logos/'.$identifer.'/';
        if($action=='home')
            $pic=$identifer."_global.png";
        else if($action=='profile')
            $pic=$identifer."_p.jpg";
        else
            $pic=$identifer."_ao.jpg";
        if(file_exists($app_path.$profiledir.$pic))
        {
            $pic_path="http://ep-test.edit-place.co.uk/".$profiledir.$pic;
        }
        else
        {
           if($action=='home')
            $pic_path="http://ep-test.edit-place.co.uk/FO/images/customer-no-logo.png";
           else
             $pic_path="http://ep-test.edit-place.co.uk/FO/images/customer-no-logo90.png";
        }
        return $pic_path;
    }

 

    //get writers w.r.t language
    public function getWritersAction()
    {
    	if($_SERVER['HTTP_X_REQUESTED_WITH']=='XMLHttpRequest')
    	{

    		$articleParams=$this->_request->getParams();

    		if($articleParams['lang'] && $articleParams['lang']!='null')
    			$lang=$articleParams['lang'];
    		$article_id=$articleParams['article_id'];

    		if($article_id)
    		{
				$editObject=new EP_Ongoing_Article();	
    			$ArticleDetails=$editObject->getEditArticleDetails($article_id);
    			if($ArticleDetails!="NO")	
				{					
					if($ArticleDetails[0]['contribs_list'])
						$contrib_array=explode(",",$ArticleDetails[0]['contribs_list']);
    			}
    		}

    		$delivery_obj=new Ep_Delivery_Delivery();
    		$writers=$delivery_obj->getContribsByType("senior','junior','sub-junior",'',$lang);

    		if($writers)
    		{
    			$writers_select='<select multiple="multiple" name="favcontribcheck[]" data-placeholder="Select Writer..." id="favcontribcheck" style="width:400px">';

    			foreach($writers as $writer)
    			{
    				$name=$writer['email']." (".utf8_encode($writer['first_name']." ".$writer['last_name']).")";

    				if(in_array($writer['identifier'],$contrib_array))
						$writers_select.='<option value="'.$writer['identifier'].'" selected>'.$name.'</option>';
					else		
						$writers_select.='<option value="'.$writer['identifier'].'" >'.$name.'</option>';


    			}
    			$writers_select.='</select>';
    		}

    		echo $writers_select;exit;

    	}	

    }
    //get correctors w.r.t language
    public function getCorrectorsAction()
    {
    	if($_SERVER['HTTP_X_REQUESTED_WITH']=='XMLHttpRequest')
    	{

    		$articleParams=$this->_request->getParams();

    		if($articleParams['lang'] && $articleParams['lang']!='null')
    			$lang=$articleParams['lang'];
    		$article_id=$articleParams['article_id'];

    		if($article_id)
    		{
				$editObject=new EP_Ongoing_Article();	
    			$ArticleDetails=$editObject->getEditArticleDetails($article_id);
    			if($ArticleDetails!="NO")	
				{					
					if($ArticleDetails[0]['corrector_privatelist'])
						$contrib_array=explode(",",$ArticleDetails[0]['corrector_privatelist']);
    			}
    		}

    		$delivery_obj=new Ep_Delivery_Delivery();
    		$correctors=$delivery_obj->getCorrectorsByLang($lang);

    		if($correctors)
    		{
    			//get previous cycle correctors
	    		$cParticipationObject=new EP_Ongoing_CorrectorParticipation();
	    		$prev_cycle_correctors=$cParticipationObject->getPrevCycleUsers($article_id);

	    		if($prev_cycle_correctors)
	    		{
	    			foreach($prev_cycle_correctors as $corrector)
	    			{
	    				$participated_correctors[]=$corrector['corrector_id'];
	    			}
	    		}
	    		else
	    			$participated_correctors=array();

    			//echo "<pre>";print_r($prev_cycle_correctors);


    			$correctors_select='<select multiple="multiple" name="favcorrectorcheck[]" data-placeholder="Select corrector..." id="favcorrectorcheck" style="width:400px">';

    			foreach($correctors as $corrector)
    			{
	    			
	    			if(!in_array($corrector['identifier'], $participated_correctors))
	    			{	
	    				$name=$corrector['email']." (".utf8_encode($corrector['first_name']." ".$corrector['last_name']).")";

	    				/*if(count($contrib_array)>0)
	    				{
		    				if(in_array($corrector['identifier'],$contrib_array))
								$correctors_select.='<option value="'.$corrector['identifier'].'" selected>'.$name.'</option>';
							else		
								$correctors_select.='<option value="'.$corrector['identifier'].'" >'.$name.'</option>';
						}
						else*/		
								$correctors_select.='<option value="'.$corrector['identifier'].'" >'.$name.'</option>';	
					}		


    			}
    			$correctors_select.='</select>';
    		}

    		echo $correctors_select;exit;

    	}	

    }
	
	
	 //get correctors w.r.t language
    public function getCorrectorsaoAction()
    {
    	if($_SERVER['HTTP_X_REQUESTED_WITH']=='XMLHttpRequest')
    	{

    		$articleParams=$this->_request->getParams();

    		if($articleParams['lang'] && $articleParams['lang']!='null')
    			$lang=$articleParams['lang'];
    		

    		$delivery_obj=new Ep_Delivery_Delivery();
    		$correctors=$delivery_obj->getCorrectorsByLang($lang);

    		if($correctors)
    		{
    			//get previous cycle correctors
	    		$cParticipationObject=new EP_Ongoing_CorrectorParticipation();
	    		$prev_cycle_correctors=$cParticipationObject->getPrevCycleUsers($article_id);

	    		if($prev_cycle_correctors)
	    		{
	    			foreach($prev_cycle_correctors as $corrector)
	    			{
	    				$participated_correctors[]=$corrector['corrector_id'];
	    			}
	    		}
	    		else
	    			$participated_correctors=array();

    			//echo "<pre>";print_r($prev_cycle_correctors);


    			$correctors_select='<select multiple="multiple" name="favcorrectorcheck[]" data-placeholder="Select corrector..." id="favcorrectorcheckao" style="width:400px">';

    			foreach($correctors as $corrector)
    			{
	    			
	    			if(!in_array($corrector['identifier'], $participated_correctors))
	    			{	
	    				$name=$corrector['email']." (".utf8_encode($corrector['first_name']." ".$corrector['last_name']).")";

	    				$correctors_select.='<option value="'.$corrector['identifier'].'" >'.$name.'</option>';	
					}		


    			}
    			$correctors_select.='</select>';
    		}

    		echo $correctors_select;exit;

    	}	

    }

	
    //download contracts w.r.t user or article
    public function downloadContractAction()
    {

    	ini_set('max_execution_time',0);

    	$cparams=$this->_request->getParams();

    	$participationObject=new EP_Ongoing_Participation();
    	$cParticipationObject=new EP_Ongoing_CorrectorParticipation();

    	$user_id=$cparams['user_id'];
    	$article_id=$cparams['article_id'];

    	if($user_id OR $article_id)
    	{
    		//participation watchlist
    		$participation_watch_list=$participationObject->getParticipationWatchlist($user_id,$article_id);
    		
    		//correctorparticipation watchlist
    		$corrector_participation_watch_list=$cParticipationObject->getParticipationWatchlist($user_id,$article_id);

    		$watchlist=array_merge($participation_watch_list,$corrector_participation_watch_list);
    		if(count($watchlist)>0)
    		{
    			foreach($watchlist as $watchid)
    			{
    				$watchlist_user[]=$watchid['watchlist_id'];
    			}
    		}    		

    		$watchlist_user=array_values(array_unique($watchlist_user));

    		if(is_array($watchlist_user) && count($watchlist_user)>0)
    		{
    			foreach($watchlist_user as $watchlist_id )
    			{
    				//generating pdf agreements    				
    				 $zip_files[]=$this->generateContractPdf($watchlist_id,$user_id);
    			}

    			//echo "<pre>";print_r($zip_files);exit;

    			if(count($zip_files)>0)
    			{

					if($user_id &&  !$article_id) //download all contracts of a user
					{
						$file_path=$contractdir=APP_PATH_ROOT.'contract_agreements/'.$user_id.'/contract_agreements.zip';
						//echo $file_path;exit;
						
						if(file_exists($file_path))
							unlink($file_path);
						
						//generating zip
						$result = create_zip($zip_files,$file_path,true);

		    			
		    			//downloaing zip file	    			

		    			if(file_exists($file_path) && !is_dir($file_path))
			            {
			              		              
			              $this->_redirect("/BO/download_contract_agreement.php?type=zip&user_id=".$user_id);
			              exit;
			            }
			            else
			              echo "File Not found";
			        }
			        else if($user_id &&  $article_id) //downloadin contract for a published article
			        {
			        	$file_path=$zip_files[0];

			        	$path_parts = pathinfo($file_path);
			        	$file_name=$path_parts['filename'];        	

			        	if(file_exists($file_path) && !is_dir($file_path))
			            {
			              		              
			              $this->_redirect("/BO/download_contract_agreement.php?file=$file_name&type=pdf&user_id=$user_id");
			              exit;
			            }
			            else
			              echo "File Not found";
			        }
		        }  
				    		

    		}
    		else
    		{
    			echo "No Contracts found";
    		}

    		

    	}

    	
    }

    public function generateContractPdf($watchlist_id,$user_id)
    {

    	$contract_obj=new EP_Ongoing_Article();

    	$contract_details=$contract_obj->getContractText($watchlist_id,$user_id);

    	if($contract_details)
    	{

			$contract_text=$contract_details[0]['contract_text'];
			$contract_date=date("dmY",strtotime($contract_details[0]['created_at']));


			$contractdir=APP_PATH_ROOT.'contract_agreements/'.$user_id.'/';

			$pdf_name="contract_".$contract_date."_".$watchlist_id.".pdf";

			$contract_pdf_file=$contractdir.$pdf_name;

            
	        if(!is_dir($contractdir))
	         mkdir($contractdir,TRUE);
	         chmod($contractdir,0777);


			require_once(APP_PATH_ROOT.'dompdf/dompdf_config.inc.php');

			$html=$contract_text;
			if (get_magic_quotes_gpc() )
            	$html = stripslashes($html);
    		
    		
    		$html =str_replace('&ndash;', '-', $html);
    		$html =str_replace('&rsquo;', "'", $html);   
    		//$html =str_replace('<div style="width:1%;float:left">- </div>', "- ", $html);
    		$html=strip_tags($html,'<p><strong><br><address><ul><ol><li><span><b><address><a>');
    				
    		

    		//$html=isodec($html);
    		//echo $html;exit;

    		if(!file_exists($contract_pdf_file))
    		{

	    		$dompdf = new DOMPDF();
	            $dompdf->load_html( $html);
	            $dompdf->set_paper("a4");
	            $dompdf->render();
	            // $dompdf->stream("dompdf_out.pdf");
	            $pdf = $dompdf->output();
	            file_put_contents($contract_pdf_file,$pdf);
	        }    

            return $contract_pdf_file;
            //exit;
    	}
    }

    //get Refusal reasons based on Id
    public function getRefusalReasonAction()
    {
    	$reason_params=$this->_request->getParams();

    	$reason_id=$reason_params['reason_id'];

    	if($reason_id)
    	{
    		$reason_obj=new Ep_Delivery_ArticleReassignReasons();	

    		$reason_details=$reason_obj->getRefusalReason($reason_id);

    		if($reason_details!='NO')
    		{
    			$reasons=$reason_details[0]['edited_content'];
    			$reasons = preg_replace('#(<br */?>\s*)+#i', '<br />', $reasons);
    			echo utf8_encode($reasons);
    		}


    	}    	
    	
    }
	
	public function sendMailEpMailBoxOngoing($receiverId,$object,$content)
    {   
		$automail=new Ep_Message_AutoEmails();
		$UserDetails=$automail->getUserType($receiverId);
		$email=$UserDetails[0]['email'];
		$password=$UserDetails[0]['password'];
		$type=$UserDetails[0]['type'];

		$object=strip_tags($object);

		$mail = new Zend_Mail();
		$mail->addHeader('Reply-To',$this->adminLogin->loginEmail);
		$mail->setBodyHtml($content)
			 ->setFrom($this->adminLogin->loginEmail)
			 ->addTo($UserDetails[0]['email'])
			 ->setSubject($object);

		if($UserDetails[0]['alert_subscribe']=='yes')  
		{
			if($mail->send())
				return true;
		}
	}

}
