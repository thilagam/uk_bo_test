<?php
/**
 * Ongoing Controller for ongoing Ao actions and Edit AO
 *
 * @author
 * @version
 */
class DeliveryongoingController extends Ep_Controller_Action
{
	
	public function init()
	{
		parent::init();
		$this->_view->lang = $this->_lang;
		$this->adminLogin = Zend_Registry::get('adminLogin');
        $this->_view->userId = $this->userId = $this->adminLogin->userId;    
		$this->_view->user_type= $this->adminLogin->type ;
        $this->sid = session_id();	
        $this->_view->fo_path = $this->fo_path = $this->_config->path->fo_path;
        $this->_view->fo_base_path = $this->fo_base_path = $this->_config->path->fo_base_path;
        $this->_view->fo_root_path = $this->fo_root_path = $this->_config->path->fo_root_path;
		$this->_view->ebookerid = $this->ebookerid = $this->configval['ebooker_id'];
        if($this->_helper->FlashMessenger->getMessages()) {
	            $this->_view->actionmessages=$this->_helper->FlashMessenger->getMessages();
	            //echo "<pre>";print_r($this->_view->actionmessages); 
	    }
		$this->configvalues = $this->getConfiguredval();
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
			
			for($c=0;$c<count($_POST['contributor_list']);$c++)
			{
				$this->sendMailEpMailBoxOngoing($_POST['contributor_list'][$c],$_POST['email_subject'],$_POST['email_content'],$_POST['cemail_from']);
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
    	$ongoing=new EP_Quote_Ongoing();	
    	$ao_id=$editParams['ao_id'];
    	$client_id=$editParams['client_id'];
    	$editParams['edit_ao']=TRUE;
		$editParams['sorttype']='all';
    	
    	if($ao_id && $client_id && $_SERVER['HTTP_X_REQUESTED_WITH']=='XMLHttpRequest')
    	{
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
    			$cnt++;
    		}
           // echo $aoDetails[0]['correction_file']; exit;
            $filearr = explode("/",$aoDetails[0]['correction_file']);
             $aoDetails[0]['crtfile_name'] = $filearr[2];

    		if($aoDetails)	
			{
				/* Checking article status to restrict brief uploads and plag url */
				$search = array();
				$search['ao_id'] = $ao_id;
				$search['client_id'] = $client_id;
				$search['missiontest'] = $aoDetails[0]['missiontest'];
				$articleDetails=$ongoing->getOngoingArticleDetails($search);
				/* Setting writer, corrector participating and selection, plag phase to false and again checking in all articles and setting to true
				In order to restrict brief uploads and plag urls */
				$this->_view->writer_participating = $this->_view->corrector_participating = $this->_view->writer_selection = $this->_view->corrector_selection = false;
				$this->_view->plag_phase = false;
				$upload_writer_error = $upload_corrector_error = $plag_error = "";
				$participationObject=new EP_Ongoing_Participation();
				foreach($articleDetails as $article)
				{
					if(($article['totalParticipations'] > 0 && $article['participation_expires'] > time()))
					{
						$this->_view->writer_participating = true;
						if(!$upload_writer_error)
						$upload_writer_error = "Vous ne pouvez pas &eacute;diter le brief de r&eacute;daction l&rsquo;article $article[title] car son statut est PARTICIPATIONS EN COURS";
					}
					if($article['totalParticipations'] > 0)
					{
						if($article['totalParticipations']==$article['unselectedwriters'])
						{
							$this->_view->writer_selection = true;
							if(!$upload_writer_error)
							$upload_writer_error = "Vous ne pouvez pas &eacute;diter le brief de r&eacute;daction l&rsquo;article $article[title] car son statut est EN S&Eacute;LECTION DE PROFIL";
						}
					}
					if(($article['totalCorrectionParticipations'] > 0 && $article['correction_participationexpires'] > time()))
					{
						$this->_view->corrector_participating = true;
						if(!$upload_corrector_error)
						$upload_corrector_error = "Vous ne pouvez pas &eacute;diter le brief de l&rsquo;article $article[title] car son statut est PARTICIPATIONS EN COURS";
					}
					if($article['totalCorrectionParticipations'] > 0)
					{
						if($article['totalCorrectionParticipations']==$article['unselectedcorrectors'])
						{
							$this->_view->corrector_selection = true;
							if(!$upload_corrector_error)
							$upload_corrector_error = "Vous ne pouvez pas &eacute;diter le brief de l&rsquo;article $article[title] car son statut est EN S&Eacute;LECTION DE PROFIL";
						}
					}
					if($article['writerParticipation'])
    				{
						$writer_bid_details = $participationObject->getBiddingDetails($article['writerParticipation']);
						if($writer_bid_details[0]['writer_status']=='plag_exec')
						{
							$this->_view->plag_phase = true;
							if(!$plag_error)
							$plag_error = "You cannot edit the URLs as article $article[title] is in EN PHASE PLAGIAT";
						}
					}
					if($plag_error && $upload_writer_error && $upload_corrector_error)
						break;
				}
				$this->_view->upload_writer_error = $upload_writer_error;
				$this->_view->upload_corrector_error = $upload_corrector_error;
				$this->_view->plag_error = $plag_error;
				$this->_view->aoDetails=$aoDetails;	
				$AllOptions=$this->getPremiumOptions();
				$this->_view->options=$AllOptions;
			    $this->_view->optioncount=count($AllOptions);
			    $this->_view->prem_ser=array();
				$this->_view->artid = $editParams['art_id'];
				if($editParams['cmid'])
				{
					$this->_view->prod='prod';
					$this->_view->cmid=$editParams['cmid'];
				}
				else
				{
					$this->_view->prod='';
					$this->_view->cmid='';
				}
				$this->_view->render("delivery_edit_ao_details");		
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
			//print_r($_FILES);exit;
        	$deliveyObj=new EP_Ongoing_Delivery();
        	$deliveyOptObj=new Ep_Delivery_DeliveryOptions();
        	$articleObj=new EP_Ongoing_Article();
        	$ao_id=$editParams['ao_id'];
        	$client_id=$editParams['client_id'];
        	if($ao_id && $client_id)
        	{
	        	$deliveryDetails=$deliveyObj->getDeliveryDetails($ao_id);
				$updateArray['title']=isodec($editParams['title']);
	        	$updateArray['deli_anonymous']=$editParams['deli_anonymous'] ? 1 : 0;
	        	$updateArray['AOType']=$editParams['ao_type'] ? 'private' : 'public';

				$updateArray['correction_type']=$editParams['correction_type'] ? 'private' : 'public';
                
                
				$updateArray['urlsexcluded'] = $editParams['urlsexcluded'];
				if($editParams['plag_excel_file']=="yes")
				{
					$updateArray['plag_xls'] = $editParams['plag_xls'];
					$updateArray['column_xls'] = $editParams['xls_columns'];
				}
				else
					$updateArray['plag_xls'] = "";
				$updateArray['urlsexcluded'] = $editParams['urlsexcluded'];

               
                
					

                if($editParams['correction'] == 'external')
                    $updateArray['correction'] = $editParams['correction'];
                else
                    $updateArray['correction'] = "internal";
	        
				//Fileupload fo_root_path
					$realfilename=$_FILES['uploadfile']['name'];
					$ext=pathinfo($realfilename);
					
					$uploaddir = $this->fo_root_path.'/client_spec/';
					
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
					
					$uploaddir = $uploaddir.$client_id."/";
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

                $uploaddir = $this->fo_root_path.'/correction_spec/';

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
				$query=" id='".$ao_id."' and user_id='".$client_id."'";
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
			
				$this->_helper->FlashMessenger("Details of Ao has been updated successfully");	
				if($editParams['view']=='prod')
				$this->_redirect("/followup/prod?cmid=".$editParams['cmid']."&submenuId=ML13-SL4");
				else
				$this->_redirect("/followup/delivery?ao_id=$ao_id&client_id=$client_id&article_id=$editParams[artid]&submenuId=ML13-SL4");
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
		$ongoing_obj = new Ep_Quote_Ongoing();
		$recruitmentObj = new Ep_Quote_Recruitment();
		$participate_obj=new EP_Ongoing_Participation();
        $cparticipate_obj=new Ep_Ongoing_CorrectorParticipation();
    	$art_id=$editParams['article_id'];
    	
    	if($art_id && $_SERVER['HTTP_X_REQUESTED_WITH']=='XMLHttpRequest')
    	{
			$ArticleDetails=$ongoing_obj->getEditArticleDetails($art_id);
			
    		//Private contribs
			$hiredProfiles=$recruitmentObj->getHiredParticipants($ArticleDetails[0]['contract_mission_id']);
			if($hiredProfiles)
			$contriblist=$hiredProfiles;	
			else 
			$contriblist=$ongoing_obj->getAllContribAO(0,$ArticleDetails[0]['language']);	
	
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
			$correctorlist= $delivery_obj->getContribsByType("senior','junior','sub-junior",'',$ArticleDetails[0]['language']);			
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

            $this->_view->artuserprice = $ongoing_obj->getUserPrice($art_id);
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
					$ArticleDetails[$acnt]['view_to']=explode(",",$article['view_to']);
					
					if($article['submit_option']=='min')
						$sub_multiple = 1;
					elseif($article['submit_option']=='hour')
						$sub_multiple = 60;
					elseif($article['submit_option']=='day')
						$sub_multiple = (60*24);
					
					$ArticleDetails[$acnt]["submit_option"]=$article['submit_option'];
					$ArticleDetails[$acnt]["junior_time"] = $article['junior_time']/$sub_multiple;
					$ArticleDetails[$acnt]["senior_time"] = $article['senior_time']/$sub_multiple;
					$ArticleDetails[$acnt]["subjunior_time"] = $article['subjunior_time']/$sub_multiple;
					if($ArticleDetails[$acnt]["junior_time"]==0 || $ArticleDetails[$acnt]["senior_time"]==0 || $ArticleDetails[$acnt]["subjunior_time"]==0)
					{
						$ArticleDetails[$acnt]["junior_time"]= $this->configvalues['jc_time']/60;
						$ArticleDetails[$acnt]["senior_time"]= $this->configvalues['sc_time']/60;
						$ArticleDetails[$acnt]["subjunior_time"]= $this->configvalues['jc0_time']/60;
						$ArticleDetails[$acnt]['submit_option'] = 'hour';
					}
					//Resubmit time
					if($article['resubmit_option']=='min')
						$resub_multiple = 1;
					elseif($article['resubmit_option']=='hour')
						$resub_multiple = 60;
					elseif($article['resubmit_option']=='day')
						$resub_multiple = (60*24);
			
					$ArticleDetails[$acnt]["resubmit_option"]=$article['resubmit_option'];
					$ArticleDetails[$acnt]["jc_resubmission"] = $article['jc_resubmission']/$resub_multiple;
					$ArticleDetails[$acnt]["sc_resubmission"] = $article['sc_resubmission']/$resub_multiple;
					$ArticleDetails[$acnt]["jc0_resubmission"] = $article['jc0_resubmission']/$resub_multiple;	
					if($ArticleDetails[$acnt]["jc_resubmission"]==0 || $ArticleDetails[$acnt]["sc_resubmission"]==0 || $ArticleDetails[$acnt]["jc0_resubmission"]==0)
					{
						$ArticleDetails[$acnt]["jc_resubmission"]= $this->configvalues['jc_resubmission']/60;
						$ArticleDetails[$acnt]["sc_resubmission"]= $this->configvalues['sc_resubmission']/60;
						$ArticleDetails[$acnt]["jc0_resubmission"]= $this->configvalues['jc0_resubmission']/60;
						$ArticleDetails[$acnt]['resubmit_option'] = 'hour';
					}
					
					$ArticleDetails[$acnt]["writer_selection"] = false;
					$ArticleDetails[$acnt]["corrector_selection"] = false;
					if($article['totalpart'])
					{
						if($article['totalpart'] == $article['unselectedwriters'])
							$ArticleDetails[$acnt]["writer_selection"] = true;
					}
					if($article['totalcorrectpart'])
					{
						if($article['totalcorrectpart'] == $article['unselectedcorrectors'])
							$ArticleDetails[$acnt]["corrector_selection"] = true;
					}
					
					if($article['correction_submit_option']=='min')
						$sub_multiple = 1;
					elseif($article['correction_submit_option']=='hour')
						$sub_multiple = 60;
					elseif($article['correction_submit_option']=='day')
						$sub_multiple = (60*24);
					
					$ArticleDetails[$acnt]["correction_jc_submission"] = $article['correction_jc_submission']/$sub_multiple;
					$ArticleDetails[$acnt]["correction_sc_submission"] = $article['correction_sc_submission']/$sub_multiple;
					if($ArticleDetails[$acnt]["correction_jc_submission"]==0 || $ArticleDetails[$acnt]["correction_sc_submission"]==0)
					{
						$ArticleDetails[$acnt]["correction_sc_submission"] = $this->configvalues['correction_sc_submission']/60;
						$ArticleDetails[$acnt]["correction_jc_submission"] = $this->configvalues['correction_jc_submission']/60;
						$ArticleDetails[$acnt]['correction_submit_option'] = 'hour';
					}
					
					if($article['correction_resubmit_option']=='min')
						$sub_multiple = 1;
					elseif($article['correction_resubmit_option']=='hour')
						$sub_multiple = 60;
					elseif($article['correction_resubmit_option']=='day')
						$sub_multiple = (60*24);
					
					$ArticleDetails[$acnt]["correction_jc_resubmission"] = $article['correction_jc_resubmission']/$sub_multiple;
					$ArticleDetails[$acnt]["correction_sc_resubmission"] = $article['correction_sc_resubmission']/$sub_multiple;
					if($ArticleDetails[$acnt]["correction_jc_resubmission"]==0 || $ArticleDetails[$acnt]["correction_sc_resubmission"]==0)
					{
						$ArticleDetails[$acnt]["correction_sc_resubmission"] = $this->configvalues['correction_sc_resubmission']/60;
						$ArticleDetails[$acnt]["correction_jc_resubmission"] = $this->configvalues['correction_jc_resubmission']/60;
						$ArticleDetails[$acnt]['correction_resubmit_option'] = 'hour';
					}
					if($article['correction_participation']==0 || $article['correction_participation']=="")
						$ArticleDetails[$acnt]['correction_participation'] = $this->configvalues['correction_participation'];
					$acnt++;
				}			
				
				$this->_view->contrib_array=$contrib_array;
				$this->_view->correctors_array=$correctors_array;
				$this->_view->ArticleDetails=$ArticleDetails;	
				//echo "<pre>";print_r($ArticleDetails);
				$this->_view->render("delivery_edit_article_details");		
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
				/* updating only if the data available since we are restricting particpation and selection time, prices and soon in different stage of articles */
				$updateArray['title']=isodec($editParams['title']);
				if(!empty($editParams['participation_time']))
	        	$updateArray['participation_time']=$editParams['participation_time'];
				if(!empty($editParams['selection_time']))
                $updateArray['selection_time']=$editParams['selection_time'];
				if(!empty($editParams['price_min']))
                $updateArray['price_min']=str_replace(",",".",$editParams['price_min']);
				if(!empty($editParams['price_max']))
	        	$updateArray['price_max']=str_replace(",",".",$editParams['price_max']);
	        	$updateArray['files_pack']= $editParams['files_pack'];
	        	
				if(!empty($editParams['view_to']))
				{
					$updateArray['view_to'] = implode(',',$editParams['view_to']);
				}

				//Add article history when price range is updated
				if($ArticleDetails[0]['price_min']!=$updateArray['price_min'] || $ArticleDetails[0]['price_max']!=$updateArray['price_max'])
				{
					$actionId=64;
					$actparams['artId']=$article_id;
					$actparams['stage']='ongoing';
					$actparams['action']='pricerange_updated';
					$actparams['old_article_writing_price_range']=$ArticleDetails[0]['price_min'].'-'.$ArticleDetails[0]['price_max'];
					$actparams['new_article_writing_price_range']=$updateArray['price_min'].'-'.$updateArray['price_max'];
					$this->articleHistory($actionId, $actparams);
				}
				
				// Updating Submission and Resubmission time
				if(!empty($editParams['submit_option']))
				{
				if($editParams['submit_option']=='min')
					$sub_multiple = 1;
				elseif($editParams['submit_option']=='hour')
					$sub_multiple = 60;
				elseif($editParams['submit_option']=='day')
					$sub_multiple = (60*24);
				$updateArray["submit_option"]=$editParams['submit_option'];
				}
				if(!empty($editParams['junior_time']))
				$updateArray["junior_time"] = $editParams['junior_time']*$sub_multiple;
				if(!empty($editParams['senior_time']))
				$updateArray["senior_time"] = $editParams['senior_time']*$sub_multiple;
				if(!empty($editParams['subjunior_time']))
				$updateArray["subjunior_time"] = $editParams['subjunior_time']*$sub_multiple;
				
				if(!empty($editParams['resubmit_option']))
				{
				if($editParams['resubmit_option']=='min')
					$sub_multiple = 1;
				elseif($editParams['resubmit_option']=='hour')
					$sub_multiple = 60;
				elseif($editParams['resubmit_option']=='day')
					$sub_multiple = (60*24);
				$updateArray["resubmit_option"]=$editParams['resubmit_option'];
				}
				if(!empty($editParams['jc0_resubmission']))
				$updateArray["jc0_resubmission"] = $editParams['jc0_resubmission']*$sub_multiple;
				if(!empty($editParams['jc_resubmission']))
				$updateArray["jc_resubmission"] = $editParams['jc_resubmission']*$sub_multiple;
				if(!empty($editParams['sc_resubmission']))
				$updateArray["sc_resubmission"] = $editParams['sc_resubmission']*$sub_multiple;
				
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
						$this->articleHistory($actionId, $actparams);
					}
				}    
	               
				if(isset($editParams['correctiontype']))   
                    $updateArray['correction']=$editParams['correctiontype'];
                else
                    $updateArray['correction']=$editParams['correction'] ? 'yes' : 'no';
                /*added by naseer on 04.12.2015*/
                //extra parameters realted to surce langauge CHECK and source langauge of corrector CHECK//
                $updateArray['sourcelang_nocheck']=($editParams['sourcelang_nocheck'] === 'yes') ?'yes' : 'no';

                if($updateArray['correction']=='yes')
	        	{
					/* Added By arun*/
					$updateArray['correction_type']='extern';
					$updateArray['correction_participationexpires']=time();					
					/* END */
					
					if(!empty($editParams['corrector_list']))
					{
						$implode = implode(",",$editParams['corrector_list']);
						if($implode=='sc,jc')
							$updateArray['corrector_list'] = 'CB';
						else if($implode=='sc')
							$updateArray['corrector_list'] = 'CSC';
						else
							$updateArray['corrector_list'] = 'CJC';
					}
					if(!empty($editParams['correction_pricemin']))
						$updateArray['correction_pricemin']=str_replace(",",".",$editParams['correction_pricemin']);
					else
						$updateArray['correction_pricemin']=0;
					
					if(!empty($editParams['correction_pricemax']))
					$updateArray['correction_pricemax']=str_replace(",",".",$editParams['correction_pricemax']);
					if(!empty($editParams['correction_selection_time']))
	        		$updateArray['correction_selection_time']=$editParams['correction_selection_time'];
					if(!empty($editParams['correction_participation']))
	        		$updateArray['correction_participation']=$editParams['correction_participation'];
				
					if(!empty($editParams['correction_submit_option']))
					{
						if($editParams['correction_submit_option']=='min')
							$sub_multiple = 1;
						elseif($editParams['correction_submit_option']=='hour')
							$sub_multiple = 60;
						elseif($editParams['correction_submit_option']=='day')
							$sub_multiple = (60*24);
						$updateArray['correction_submit_option'] = $editParams['correction_submit_option'];
						$updateArray["correction_jc_submission"] = $editParams['correction_jc_submission']*$sub_multiple;
						$updateArray["correction_sc_submission"] = $editParams['correction_sc_submission']*$sub_multiple;
					}
					
					if(!empty($editParams['correction_resubmit_option']))
					{
						if($editParams['correction_resubmit_option']=='min')
							$sub_multiple = 1;
						elseif($editParams['correction_resubmit_option']=='hour')
							$sub_multiple = 60;
						elseif($editParams['correction_resubmit_option']=='day')
							$sub_multiple = (60*24);
						$updateArray['correction_resubmit_option'] = $editParams['correction_resubmit_option'];
						$updateArray["correction_jc_resubmission"] = $editParams['correction_jc_resubmission']*$sub_multiple;
						$updateArray["correction_sc_resubmission"] = $editParams['correction_sc_resubmission']*$sub_multiple;
					}
					$updateArray['nomoderation']=$editParams['nomoderation'] ? 'no' : 'yes';
					//Add article history when corrector price range is updated
					if($ArticleDetails[0]['correction_pricemin']!=$updateArray['correction_pricemin'] || $ArticleDetails[0]['correction_pricemax']!=$updateArray['correction_pricemax'])
					{
						$actionId=65;
						$actparams['artId']=$article_id;
						$actparams['stage']='ongoing';
						$actparams['action']='pricecorrrange_updated';
						$actparams['old_article_correction_price_range']=$ArticleDetails[0]['correction_pricemin'].'-'.$ArticleDetails[0]['correction_pricemax'];
						$actparams['new_article_correction_price_range']=$updateArray['correction_pricemin'].'-'.$updateArray['correction_pricemax'];
						$this->articleHistory($actionId, $actparams);						
					}
				
	        		if($editParams['favcorrectorcheck'] &&  count($editParams['favcorrectorcheck'])>0)
	        			$updateArray['corrector_privatelist']=implode(",",$editParams['favcorrectorcheck']);
					else
						$updateArray['corrector_privatelist']= "";

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
							$this->articleHistory($actionId, $actparams);
						}
						//updating corrector Participation table for corrector prices ///
	                    $pricearray=array("price_corrector"=>str_replace(",",".",$editParams['price_corrector']));
	                    $query = "article_id= '".$article_id."' AND id = '".$editParams['crtpart_id']."'";
	                    $cparticipate_obj->updateParticipation($pricearray,$query);
	                }    

                    $updateArray['sourcelang_nocheck_correction']=($editParams['sourcelang_nocheck_correction'] === 'yes') ?'yes' : 'no';
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
				else
					$updateArray['contribs_list']="";

                //$ArticleDetails=$articleObj->getEditArticleDetails($article_id);
                if($ArticleDetails[0]['article_edit_at'] == NULL)
                    $updateArray['article_edit_at'] = $this->adminLogin->userId."|".date('Y-m-d H:i:s');
                else
                    $updateArray['article_edit_at'] = $ArticleDetails[0]['article_edit_at'].",".$this->adminLogin->userId."|".date('Y-m-d H:i:s');
	        	
					$query=" id='".$editParams['article_id']."'";
					$articleObj->updateArticle($updateArray,$query);
					
				$this->_helper->FlashMessenger('Les d&eacute;tails de article ont &eacute;t&eacute; mis &agrave; jour avec succ&egrave;s');
				$this->_redirect("/followup/delivery?ao_id=$ao_id&client_id=$client_id&submenuId=ML13-SL4");
	        }
	        else
	        {
        		$this->_helper->FlashMessenger('Some error occured!!!');
        		$this->_helper->FlashMessenger('error');
        		$this->_redirect("/contractmission/missions-list?submenuId=ML13-SL4");
	        }
        }
        else
        	$this->_redirect("/contractmission/missions-list?submenuId=ML13-SL4");
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
            $old_specpath = $this->fo_root_path."/client_spec".$details[0]['filepath'];
			
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
            $old_file_path = $this->fo_root_path."/articles/".$article_path;
			
          
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
        $this->_view->render("delivery_ongoing_extendtime_writer_popup");
        
    }
    //extend article submit time for writer/Corrector
    public function extendArticleSubmitAction()
    {
		if($this->_request-> isPost())
		{
			$articleParams=$this->_request->getParams();
			$participation_id=$articleParams['participation_id'];	
			$user_type=$articleParams['user_type'];
			
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
                    /*echo "jill".$details[0]['corrector_submit_expires'];
                    print_r($details); exit;*/
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
				if($articleParams['pagefrom']=='overdue')
					$this->_redirect("/ao/over-due?submenuId=ML2-SL11");
				else
				{
					$client_id=$details[0]['clientId'];
					$ao_id=$details[0]['deliveryId'];
					$this->_redirect("/followup/delivery?ao_id=$ao_id&client_id=$client_id&submenuId=ML13-SL4");
				}	
			}
			else
			{
				if($articleParams['pagefrom']=='overdue')
					$this->_redirect("/ao/over-due?submenuId=ML2-SL11");
				else
					$this->_redirect("/contractmission/missions-list?submenuId=ML13-SL4");
			}
		}
    }
     /**get the mail message from automails table for extend time**/
    public function getMailComments($mailid,$parameters)
    {
        $automail=new Ep_Message_AutoEmails();
        $AO_Creation_Date='<b>'.$parameters['created_date'].'</b>';
        $link='<a href="'.$this->fo_base_path.'/'.$parameters['document_link'].'">Cliquant ici</a>';
        $contributor='<b>'.$parameters['contributor_name'].'</b>';
        $AO_title="<b>".$parameters['AO_title']."</b>";
        $submitdate_bo="<b>".date("d/m/Y",strtotime($parameters['submitdate_bo']))."</b>";
        $total_articles="<b>".$parameters['noofarts']."</b>";
        $invoicelink='<a href="'.$this->fo_base_path.'/'.$parameters['invoice_link'].'">cliquant ici</a>';
        $article_link='<a href="'.$parameters['article_link'].'">Cliquant-ici</a>';
        $client='<b>'.$parameters['client_name'].'</b>';
        $royalty='<b>'.$parameters['royalty'].'</b>';
        $ongoinglink='<a href="'.$this->fo_base_path.'/'.$parameters['ongoinglink'].'">cliquant ici</a>';
        $AO_end_date='<b>'.$parameters['AO_end_date'].'</b>';
        $article='<b>'.stripslashes($parameters['article_title']).'</b>';
        $AO_title='<b>'.stripslashes($parameters['AO_title']).'</b>';
        $resubmit_time='<b>'.stripslashes($parameters['resubmit_time']).'</b>';
        $site='<a href="'.$this->fo_base_path.'">Edit-place</a>';
        $extend_hours="<b>".$parameters['extend_hours']."</b>";
        $articlewithlink='<a href="'.$parameters['articlename_link'].'">'.stripslashes($parameters['article_title']).'</a>';
        $aowithlink='<a href="'.$parameters['aoname_link'].'">'.stripslashes($parameters['AO_title']).'</a>';
		
        $email=$automail->getAutoEmail($mailid);
        $Object=$email[0]['Object'];
        $Message=$email[0]['Message'];
        eval("\$Message= \"$Message\";");
        return $Message;
        /**Inserting into EP mail Box**/
        //$this->sendMailEpMailBox($receiverId,$Object,$Message);
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
	    				$article_array[$article['id']]=$article['title'];
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
    	$this->_view->render("delivery_ao_contribmail_popup");
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
			$this->_view->render("delivery_article_comments_popup");		
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
        $app_path=$this->fo_root_path;
        if($action=='bo_user')
          $profiledir='profiles/bo/'.$identifer.'/';
        else  
          $profiledir='profiles/contrib/pictures/'.$identifer.'/';


        if($action=='home')
            $pic=$identifer."_h.jpg";
        else if($action=='bo_user') 
            $pic="logo.jpg";
        else
            $pic=$identifer."_p.jpg";
        if(file_exists($app_path.$profiledir.$pic))
        {
            $pic_path= $this->fo_path.$profiledir.$pic;
        }
        else
        {
            if($action=='home' OR $action=='bo_user')
              $pic_path= $this->fo_path."images/editor-noimage_60x60.png";
            else
              $pic_path= $this->fo_path."images/editor-noimage.png";
        }
        return $pic_path;
    }
    /*Function to get the picture of a client**/
    public function getClientPicPath($identifer,$action='home')
    {
         $app_path=$this->fo_root_path;
        $profiledir='profiles/clients/logos/'.$identifer.'/';
        if($action=='home')
            $pic=$identifer."_global.png";
        else if($action=='profile')
            $pic=$identifer."_p.jpg";
        else
            $pic=$identifer."_ao.jpg";
        if(file_exists($app_path.$profiledir.$pic))
        {
            $pic_path= $this->fo_path.$profiledir.$pic;
        }
        else
        {
           if($action=='home')
            $pic_path= $this->fo_path."images/customer-no-logo.png";
           else
             $pic_path= $this->fo_path."images/customer-no-logo90.png";
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

	    				if(count($contrib_array)>0)
	    				{
		    				if(in_array($corrector['identifier'],$contrib_array))
								$correctors_select.='<option value="'.$corrector['identifier'].'" selected>'.$name.'</option>';
							else		
								$correctors_select.='<option value="'.$corrector['identifier'].'" >'.$name.'</option>';
						}
						else		
								$correctors_select.='<option value="'.$corrector['identifier'].'" >'.$name.'</option>';	
					}		


    			}
    			$correctors_select.='</select>';
    		}

    		echo $correctors_select;exit;

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
    //get message sent to cleint from message table based on Id
    public function getMailContentAction()
    {
        $reason_params=$this->_request->getParams();
        $msg_id=$reason_params['ticket_id'];
        if($msg_id)
        {
            $msg_obj=new Ep_Message_Message();
            $msg_details=$msg_obj->getClientMessage($msg_id);
            if($msg_details!='NO')
            {
                $msg=$msg_details[0]['content'];

                echo utf8_encode($msg);
            }
        }
    }
	
	public function deleterefernceAction()
	{
		$rid=$_REQUEST['ref'];
		
		$ref_obj=new Ep_User_DeliveryReference();
		$ref_obj->deleteRef($rid);
		
		
		$sccontact_obj=new Ep_User_ScBoUserPermissions();
		$sccontact_obj->deleteScPerm($rid);
		echo 'deleted';
		exit;
		
	}	
	
	public function sendMailEpMailBoxOngoing($receiverId,$object,$content,$from='')
    {   
		$automail=new Ep_Message_AutoEmails();
		$UserDetails=$automail->getUserType($receiverId);
		$email=$UserDetails[0]['email'];
		$password=$UserDetails[0]['password'];
		$type=$UserDetails[0]['type'];
		if($from=='')
		$from = $this->adminLogin->loginEmail;
		if(!$object)
			$object="Vous avez reçu un email-Edit-place";

		$object=strip_tags($object);

		if($UserDetails[0]['alert_subscribe']=='yes')
		{	
			if($this->getConfiguredval('critsend') == 'yes')
			{
				critsendMail($from, $UserDetails[0]['email'], $object, $content);
				return true;
			}
			else
			{
				$mail = new Zend_Mail();
				$mail->addHeader('Reply-To',$this->adminLogin->loginEmail);
				$mail->setBodyHtml($content)
					->setFrom($from)
					->addTo($UserDetails[0]['email'])
					->setSubject($object);
				if($mail->send())
					return true;
			}
		}
    }
	// To load the actions of Articles
	function loadArtActionsAction()
	{
		if($this->_request->isPost() && $_SERVER['HTTP_X_REQUESTED_WITH']=='XMLHttpRequest')
		{
			$request = $this->_request->getParams();
			$artids = $request['ids'];
			
			$aoObject=new EP_Quote_Ongoing();
			$queryids =""; 
			
			foreach($artids as $row => $value):
				if($queryids)
				$queryids .= ",'".$value."'";
				else
				$queryids .= "'".$value."'";
			endforeach;
			$aoParams = array();
			$aoParams['missiontest'] = $request['missiontest'];
			$aoParams['article_ids'] = $queryids;
			/* Getting all the selected articles */
			$articleDetails = $aoObject->getOngoingArticleDetails($aoParams);
			$this->_view->artids = implode(",",$artids);
			$this->_view->ao_id = $request['ao_id'];
			/* If more than one article selected check for republish else check for edit, close, download writer and corrector article and extending time of writer and corrector  */
			if(count($artids)>1)
			{
				$this->_view->writer_republish = true;	
				$this->_view->corrector_republish = true;
				$this->_view->close = true;
				foreach($articleDetails as $article)
				{
					$this->_view->bulk = true;
					$participationObject=new EP_Ongoing_Participation();
    				$cParticipationObject=new EP_Ongoing_CorrectorParticipation();
    				if($article['writerParticipation'])
    				{
						$articleDetails[$bcnt]['writer_bid_details']=$participationObject->getBiddingDetails($article['writerParticipation']);
						
						if($article['bo_closed_status']!="closed")
						{
														
							if($articleDetails[$bcnt]['writer_bid_details'][0]['writer_status'] != 'published' &&$articleDetails[$bcnt]['writer_bid_details'][0]['writer_status'] != 'closed' && $articleDetails[$bcnt]['writer_bid_details'][0]['current_stage'] != 'stage2')
							{
								if($this->_view->close !=false)
								{
									$this->_view->close = true;
									$this->_view->totalParticipations = $article['totalParticipations'];
								}
							}
							else
							$this->_view->close = false;
							
						}
    				}
					// To check republish writer
					if(($article['totalParticipations']==0 && $article['participation_expires'] < time() && $articleDetails[$bcnt]['writer_bid_details'][0]['writer_stage'] != 'stage0' && $articleDetails[$bcnt]['writer_bid_details'][0]['writer_stage'] !='stage1'&& $articleDetails[$bcnt]['writer_bid_details'][0]['writer_stage'] !='stage2' && $articleDetails[$bcnt]['writer_bid_details'][0]['writer_stage'] !='corrector' && $article['missiontest'] != 'yes' && $article['status'] != 'refused')
					|| ($article['totalParticipations']>0 && $article['participation_expires'] < time() && $articleDetails[$bcnt]['writer_bid_details'][0]['writer_stage'] != 'published' && $articleDetails[$bcnt]['writer_bid_details'][0]['writer_stage'] != 'stage0' && $articleDetails[$bcnt]['writer_bid_details'][0]['writer_stage'] !='stage1' && $articleDetails[$bcnt]['writer_bid_details'][0]['writer_stage'] !='stage2' && $articleDetails[$bcnt]['writer_bid_details'][0]['writer_stage'] !='corrector' && $article['missiontest'] != 'yes' && $article['status'] != 'refused' && $articleDetails[$bcnt]['writer_bid_details'][0]['writer_status'] != 'published'))
					{
						if($this->_view->writer_republish != false)
						$this->_view->writer_republish = true;
					}
					else
						$this->_view->writer_republish = false;		
			
					if($article['correctorParticipation'])
					{
						$articleDetails[$bcnt]['corrector_bid_details']=$cParticipationObject->getBiddingDetails($article['correctorParticipation']);
                    }
					
					// To check republish corrector			
					if(($article['totalCorrectionParticipations']==0 && $article['correction']=='yes' && $article['correction_participationexpires'] < time() && $article['correction_participationexpires'] != 0 && $article['writerParticipation'] && $article['missiontest'] != 'yes' &&  $article['status'] != 'refused') || ($article['totalCorrectionParticipations'] > 0 && $article['correction'] == 'yes' && $articleDetails[$bcnt][ 'corrector_bid_details'][0]['corrector_status'] != 'published' && $article['correction_participationexpires'] < time() && $article['correction_participationexpires'] != 0 && $article['missiontest'] != 'yes' &&  $article['status'] != 'refused' && $articleDetails[$bcnt]['corrector_bid_details'][0]['corrector_status'] != 'published'))
					{
						if($this->_view->corrector_republish != false)
						$this->_view->corrector_republish = true;
					}
					else
						$this->_view->corrector_republish = false;		
					
					//stop participation writing & correction
					if($article['participation_expires'] >= time())
					{
						$this->_view->participation_ongoing = true;
						$this->_view->writer_stopparticipation = true;
					}
						
					if($article['correction']=='yes' && $article['correction_participationexpires'] >= time())
					{
						$this->_view->participation_ongoing = true;
						$this->_view->corrector_stopparticipation = true;
					}
					
					if($this->_view->corrector_republish == false && $this->_view->writer_republish == false && $this->_view->close == false)
					exit;
					
					$bcnt++;					
				}
			}
			else
			{
				$bcnt = 0;
				$artprocess_obj= new EP_Delivery_ArticleProcess();
				foreach($articleDetails as $article)
				{
					$participationObject=new EP_Ongoing_Participation();
    				$cParticipationObject=new EP_Ongoing_CorrectorParticipation();
					
					$this->_view->edit = true;
					$this->_view->article_id = $article['id'];
    				if($article['writerParticipation'])
    				{
						if($article['expiredWriterParticipation'])
						{
							$this->_view->writer_moretime = true;
							$this->_view->expiredWriterParticipation = $article['expiredWriterParticipation'];
						}
						if($article['bo_closed_status']!="closed")
						{
							$articleDetails[$bcnt]['writer_bid_details']=$participationObject->getBiddingDetails($article['writerParticipation']);
							if($articleDetails[$bcnt]['writer_bid_details'][0]['status']!='bid')
							$this->_view->download_art = true;
							
							if($articleDetails[$bcnt]['writer_bid_details'][0]['writer_status'] != 'published' &&$articleDetails[$bcnt]['writer_bid_details'][0]['writer_status'] != 'closed' && $articleDetails[$bcnt]['writer_bid_details'][0]['current_stage'] != 'stage2')
							{
								$this->_view->close = true;
								$this->_view->totalParticipations = $article['totalParticipations'];
							}
						}
    				}
					elseif($article['participation_expires'] < time())
					{
						$this->_view->close = true;
						$this->_view->totalParticipations = $article['totalParticipations'];
					}
				// To check republish writer
					if(($article['totalParticipations']==0 && $article['participation_expires'] < time() && $articleDetails[$bcnt]['writer_bid_details'][0]['writer_stage'] != 'stage0' && $articleDetails[$bcnt]['writer_bid_details'][0]['writer_stage'] !='stage1'&& $articleDetails[$bcnt]['writer_bid_details'][0]['writer_stage'] !='stage2' && $articleDetails[$bcnt]['writer_bid_details'][0]['writer_stage'] !='corrector' && $article['missiontest'] != 'yes' && $article['status'] != 'refused') 
					|| ($article['totalParticipations']>0 && $article['participation_expires'] < time() && $articleDetails[$bcnt]['writer_bid_details'][0]['writer_stage'] != 'published' && $articleDetails[$bcnt]['writer_bid_details'][0]['writer_stage'] != 'stage0' && $articleDetails[$bcnt]['writer_bid_details'][0]['writer_stage'] !='stage1' && $articleDetails[$bcnt]['writer_bid_details'][0]['writer_stage'] !='stage2' && $articleDetails[$bcnt]['writer_bid_details'][0]['writer_stage'] !='corrector' && $article['missiontest'] != 'yes' && $article['status'] != 'refused' && $articleDetails[$bcnt]['writer_bid_details'][0]['writer_status'] != 'published'))
					{
						$this->_view->writer_republish = true;
					}
			

					if($article['correctorParticipation'])
					{
						if($article['expiredcorrectorParticipation'])
						{
							$this->_view->corrector_moretime = true;
							$this->_view->expiredcorrectorParticipation = $article['expiredcorrectorParticipation'];
						}
						
						$articleDetails[$bcnt]['corrector_bid_details']=$cParticipationObject->getBiddingDetails($article['correctorParticipation']);
						$articleDetails[$bcnt]['corrector_artproc_details']=$artprocess_obj->getLatestCorrectionArticle($article['id']);
						if($articleDetails[$bcnt]['corrector_artproc_details'] !='NO')
							$this->_view->download_corrector_art = true;
							
					}
					// To check republish corrector			
					if(($article['totalCorrectionParticipations']==0 && $article['correction']=='yes' && $article['correction_participationexpires'] < time() && $article['correction_participationexpires'] != 0 && $article['writerParticipation'] && $article['missiontest'] != 'yes' &&  $article['status'] != 'refused') 
					|| ($article['totalCorrectionParticipations'] > 0 && $article['correction'] == 'yes' && $articleDetails[$bcnt][ 'corrector_bid_details'][0]['corrector_status'] != 'published' && $article['correction_participationexpires'] < time() && $article['correction_participationexpires'] != 0 && $article['missiontest'] != 'yes' &&  $article['status'] != 'refused' && $articleDetails[$bcnt]['corrector_bid_details'][0]['corrector_status'] != 'published'))
					{
						$this->_view->corrector_republish = true;
					}
		
					//stop participation writing & correction
					if($article['participation_expires'] >= time())
					{
						$this->_view->participation_ongoing = true;
						$this->_view->writer_stopparticipation = true;
					}
						
					if($article['correction']=='yes' && $article['correction_participationexpires'] >= time())
					{
						$this->_view->participation_ongoing = true;
						$this->_view->corrector_stopparticipation = true;
					}
					
					$bcnt++;					
				}
			}
		
			$this->_view->artdetails = $articleDetails;
			$this->render('load-art-actions');
		}
	}
	
	// To view Article in followup page
	function viewArticleAction()
	{
		$request = $this->_request->getParams();
		$artid = $request['article_id'];	
		
		if($artid)
		{
			$aoObject=new EP_Quote_Ongoing();
			$aoParams = array();
			$aoParams['missiontest'] = 'no';
			$aoParams['article_ids'] = $artid;
			$articleDetails = $aoObject->getOngoingArticleDetails($aoParams);
						
			$bcnt=0;
			foreach($articleDetails as $article)
			{
				$participationObject=new EP_Ongoing_Participation();
				$cParticipationObject=new EP_Ongoing_CorrectorParticipation();
				$artprocess_obj= new EP_Delivery_ArticleProcess();
				$user_obj = new EP_User_User();
				if($article['writerParticipation'])
				{
					$articleDetails[$bcnt]['writer_bid_details']=$participationObject->getBiddingDetails($article['writerParticipation']);
					$user_res = $user_obj->getUserdetails($articleDetails[$bcnt]['writer_bid_details'][0]['user_id']);
					$articleDetails[$bcnt]['writer_type'] = $user_res[0]['profile_type'];
					$articleDetails[$bcnt]['writer_facturation_details']=$participationObject->getFacturationDetails($article['writerParticipation'],$article['id']);
				}
				if($article['correctorParticipation'])
				{
					$articleDetails[$bcnt]['corrector_bid_details']=$cParticipationObject->getBiddingDetails($article['correctorParticipation']);
					$user_res = $user_obj->getUserdetails($articleDetails[$bcnt]['corrector_bid_details'][0]['corrector_id']);
					$articleDetails[$bcnt]['corrector_type'] = $user_res[0]['profile_type2'];
					$articleDetails[$bcnt]['corrector_facturation_details']=$cParticipationObject->getFacturationDetails($article['correctorParticipation'],$article['id']);
					$articleDetails[$bcnt]['corrector_artproc_details']=$artprocess_obj->getLatestCorrectionArticle($article['id']);
				}
				
				//stop participation writing & correction
				if($article['participation_expires'] >= time())
				{
					$this->_view->participation_ongoing = true;
					$this->_view->writer_stopparticipation = true;
				}
					
				if($article['correction']=='yes' && $article['correction_participationexpires'] >= time())
				{
					$this->_view->participation_ongoing = true;
					$this->_view->corrector_stopparticipation = true;
				}
				
				$bcnt++;					
			}
			$this->_view->articleDetails=$articleDetails;
			$this->_view->currency = $request['currency'];
			
			// To fetch Comments
			$comments_obj=new Ep_Delivery_Adcomments();
			$comment_type='article';
			$article_id=$artid;
			$commentDetails=$comments_obj->getAdComments($article_id,$comment_type);
			if(count($commentDetails)>0)
				$commentDetails=$this->formatCommentDetails($commentDetails);
				
			$this->_view->commentDetails=$commentDetails;
			$this->_view->comment_type= $comment_type;
			$this->_view->identifier=$article_id;
			$this->_view->commentCount=count($commentDetails);			

			
			// To fetch Article History
			$historyDetails=$aoObject->getAOHistory($request);
			
    		$this->_view->histories = $historyDetails;
			
			$this->render('deliveryongoing-view-article');
		}
	}

    /*edited by naseer on 04.12.2015*/
    //added tranlator condtion in below function//
	// To load users on ajax
	function loadusersAction()
	{	
		$request = $this->_request->getParams();
		$art_id = $request['artid'];
		if( $_SERVER['HTTP_X_REQUESTED_WITH']=='XMLHttpRequest' && $art_id)
		{
			$editObject=new EP_Ongoing_Article();
			$recruitmentObj = new Ep_Quote_Recruitment();
			$delivery_obj = new Ep_Delivery_Delivery();
			$ongoing_obj = new Ep_Quote_Ongoing();
			$ArticleDetails=$editObject->getEditArticleDetails($art_id);
    		
			if($request['type']=='contributors')
			{
			//Private contribs
			//$hiredProfiles=$recruitmentObj->getHiredParticipants($ArticleDetails[0]['contract_mission_id']);
			//if($hiredProfiles)
			//$contriblist=$hiredProfiles;	
			//else
			// /*added by naseer on 04.12.2015*/
                $request['profiletype']=explode(",",$request['profiletype']);
				if($request['product'] === 'translation')
             		$contriblist=$ongoing_obj->getAllTranslatorContribAO($request['profiletype'],$ArticleDetails[0]['language'],$ArticleDetails[0]['language_source'],$request['sourcecheck']);
                else
                    $contriblist=$ongoing_obj->getAllwritersAO($request['profiletype'],$ArticleDetails[0]['language']);
					
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
			}
			
			if($request['type']=='correctors')
			{
                //$correctorlist= $delivery_obj->getContribsByType("senior','junior','sub-junior",'',$ArticleDetails[0]['language']);
				 $request['profiletype']=explode(",",$request['profiletype']);
                if($request['product'] === 'translation'){
                    $correctorlist= $delivery_obj->getTranslatorCorrectorsByLang($request['profiletype'],$ArticleDetails[0]['language'],$ArticleDetails[0]['language_source'],$request['sourcecorrectioncheck']);
                }
                else{
                    $correctorlist= $delivery_obj->getCorrectorsByLangType($request['profiletype'],$ArticleDetails[0]['language']);
                    //$correctorlist= $delivery_obj->getCorrectorsByLang($request['profiletype'],$ArticleDetails[0]['language']);
                }


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
			}
			
			if($ArticleDetails!="NO")	
			{
				$acnt=0;				
				foreach($ArticleDetails as $article)
				{					
					if($article['contribs_list'])
						$contrib_array=explode(",",$article['contribs_list']);

					if($article['corrector_privatelist'])
						$correctors_array=explode(",",$article['corrector_privatelist']);
					$ArticleDetails[$acnt]['view_to']=explode(",",$article['view_to']);
					$acnt++;
				}				
				$this->_view->contrib_array=$contrib_array;
				$this->_view->correctors_array=$correctors_array;
				$this->_view->userreqtype=$request['type'];
			}	
			$this->_view->render('load-contrib-correctors');
		}
	}
	
	public function stopParticipationAction()
	{
		$artcle_obj = new EP_Delivery_Article();
		$data=array();
		
		if($_REQUEST['type']=='writing')
			$data['participation_expires'] = time();
		elseif($_REQUEST['type']=='correction')
			$data['correction_participationexpires'] = time();
		
		$articles=explode(",",$_REQUEST['article']);
		
		if(count($articles)>1)
		{
			foreach($articles as $art)
			{	
				$query = "id = '".$art."'";
				$artcle_obj->updateArticle($data,$query);
			}
		}
		else
		{
			$query = "id = '".$_REQUEST['article']."'";
			$artcle_obj->updateArticle($data,$query);
		}
	}
}
