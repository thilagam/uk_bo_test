<?php
/**
 * IndexController - The default controller class
 *
 * @author
 * @version
 */
require_once 'Zend/Controller/Action.php';

class ProofreadController extends Ep_Controller_Action
{
	private $text_admin;
	public function init()
	{
		parent::init();
		$this->_view->lang = $this->_lang;
		$this->adminLogin = Zend_Registry::get('adminLogin');
        $this->_view->userId = $this->adminLogin->userId;
        $this->_view->type = $this->adminLogin->type;
        $this->sid = session_id();


    }
    ////download file////
    public function downloadfileAction()
    {
        ////////download //////////////////////////////////////////
        $prevurl = getenv("HTTP_REFERER");
        $article_obj = new EP_Delivery_Article();
        $artProcess_obj = new EP_Delivery_ArticleProcess();
        $spec_path = $this->_request->getParam('spec');
        if(isset($spec_path))
        {
            $details = $article_obj->getArticleDetails($spec_path);
            //$oldserver_path = "/home/sites/site7/web/FO/articles/";
            $specpath = ROOT_PATH."FO/client_spec".$details[0]['filepath'];
            $oldspecpath = "/home/sites/site7/web/FO/client_spec".$details[0]['filepath'];
            $fileName = $details[0]['deliveryTitle']."-specs";
            if(file_exists($specpath))
            {
                $attachment=new Ep_Message_Attachment();
                $attachment->downloadAttachment($specpath,'attachment', $fileName);
                exit;
            }
            elseif(file_exists($oldspecpath))
            {

                $attachment=new Ep_Message_Attachment();
                $attachment->downloadAttachment($oldspecpath,'attachment', $fileName);
                exit;
            }
            else
            {
                $this->_helper->FlashMessenger('File is not Available.');
                $this->_redirect($prevurl);
            }
        }
        $path = $this->_request->getParam('path');
        if(isset($path))
        {
            $details= $artProcess_obj->getArticlePath($path);
            $pathId = $details[0]['article_path'];
            $fileName = $details[0]['article_name'];
            if($pathId)
            {
                $oldserver_path = "/home/sites/site7/web/FO/articles/";
                $server_path = ROOT_PATH."FO/articles/";
                $dwfile= $server_path.$pathId;
                $olddwfile= $oldserver_path.$pathId;
                if(file_exists($dwfile))
                {
                    $attachment=new Ep_Message_Attachment();
                    $attachment->downloadAttachment($dwfile,'attachment', $fileName);
                    exit;
                }
                elseif(file_exists($olddwfile))
                {

                    $attachment=new Ep_Message_Attachment();
                    $attachment->downloadAttachment($olddwfile,'attachment', $fileName);
                    exit;
                }
                else
                {
                    $this->_helper->FlashMessenger('File is not Available.');
                    $this->_redirect($prevurl);
                }
            }
        }
    }
	/////////displays the deliveries writen by writers and send to all stages for correction//////////////
    public function stageDeliveriesAction()
    {
        $article_obj = new EP_Delivery_Article();
        $artprocess_obj = new EP_Delivery_ArticleProcess();
        $delivery_obj     = new Ep_Delivery_Delivery();
        $participate_obj = new EP_Participation_Participation();
        $stage_params=$this->_request->getParams();
        $condition['profilelist'] = $this->configval['selection_profiles'];
        $condition['loginUserId'] = $this->adminLogin->userId;
        $condition['loginUserType'] = $this->adminLogin->type;
        if($stage_params['submenuId'] == 'ML3-SL11')
            $condition['stage'] = "stage0";
        elseif($stage_params['submenuId'] == 'ML3-SL2')
            $condition['stage'] = "stage1";
        elseif($stage_params['submenuId'] == 'ML3-SL3')
            $condition['stage'] = "stage2";

        if($stage_params['search'] == 'search')
        {
            $condition['search'] = $stage_params['search'];
            $condition['aoId'] = $stage_params['aoId'];
            $condition['inchargeId'] = $stage_params['inchargeId'];
            $condition['clientId'] = $stage_params['clientId'];
            $condition['closed'] = $stage_params['closed'];
            $condition['startdate'] = $stage_params['startdate'];
            $condition['enddate'] = $stage_params['enddate'];
            if($stage_params['closed'] != '0')
            {
                $allaos = $delivery_obj->getAllAos();
                foreach($allaos as $key=>$value)
                {
                    $allList = $participate_obj->getNotClosedSelectProfiles($value['id']);
                    if($stage_params['closed'] == 'closed')
                    {
                        if($allList == 'yes')
                        {
                            $searchaos[$key] = $value['id'];
                        }
                    }
                    elseif($stage_params['closed'] == 'notclosed')
                    {
                        if($allList == 'NO')
                        {
                            $searchaos[$key] = $value['id'];
                        }
                    }
                    else
                        $searchaos[$key] = 'all';
                }
                if($searchaos == 'all')
                    $condition['searchaosarray'] = "all";
                else
                    $condition['searchaosarray'] = join(',',$searchaos);
            }
        }
        $res= $article_obj->stageDeliveries($condition);
        if($res!="NO")
        {
            foreach ($res as $key1 => $value1) {
             $res[$key1]['client_id']        =  $res[$key1]['client'];
            $res[$key1]['del_category'] = $this->category_array[$res[$key1]['del_category']];
            $res[$key1]['client']        =  $this->client_list[$res[$key1]['client']];

            $atraiter                    = $article_obj->getArticleCountStage($res[$key1]['delivery_id'], $condition['stage']);
            $res[$key1]['atraiter']    = $atraiter[0]['stageCount'];
            $notaffectart                 = $article_obj->getValidatedArticleCount($res[$key1]['delivery_id']);
            $res[$key1]['traiter'] = $notaffectart[0]['validatedCount'];
            $res[$key1]['notclosedprofiles']  = $participate_obj->getNotClosedSelectProfiles($res[$key1]['delivery_id']);
            }
            $this->_view->paginator = $res;
        }
        $totalatraiter                 = $article_obj->getToBeCorrected($condition['stage']);
        $this->_view->totalatraiter    = $totalatraiter[0]['totalstageCount'];
           $this->_view->render("proofread_stagedeliveries");
    }
    /////////displays the articles writen by writers and send to all stages for  correction//////////////
    public function stageArticlesAction()
    {
        $article_obj = new EP_Delivery_Article();
        $artprocess_obj = new EP_Delivery_ArticleProcess();
        $delivery_obj     = new Ep_Delivery_Delivery();
        $lock_obj = new Ep_User_LockSystem();
        $user_obj     = new Ep_User_User();
        $participate_obj = new EP_Participation_Participation();
        $stage_params=$this->_request->getParams();
        $delivery_flag = "yes";//delivery flag to check if delivery stage for all article are same if all are same set to
        /*added by naseer on 18-08-2015*/
        // to check where it is stencil or not//
        $checkstencils = $delivery_obj-> checkStencilsEbooker($stage_params['aoId']);
        $this->_view->checkstencils = $checkstencils;
        //$this->_view->type = $this->adminLogin->type;

        $condition['profilelist'] = $this->configval['selection_profiles'];
        $condition['loginUserId'] = $this->adminLogin->userId;
        $condition['loginUserType'] = $this->adminLogin->type;
        if($stage_params['submenuId'] == 'ML3-SL11')
            $condition['stage'] = "stage0";
        elseif($stage_params['submenuId'] == 'ML3-SL2')
            $condition['stage'] = "stage1";
        elseif($stage_params['submenuId'] == 'ML3-SL3')
            $condition['stage'] = "stage2";
        $aoId   = $stage_params['aoId'];
        $delDetails = $delivery_obj->getPrAoDetails($aoId);
        $userdetials = $user_obj->getAllUsersDetails($delDetails[0]['created_user']);
        $delDetails[0]['del_category'] = $this->category_array[$delDetails[0]['del_category']];
        $delDetails[0]['created_user'] = $userdetials[0]['first_name'];
        $this->_view->delDetails = $delDetails;
        $condition['aoId'] = $aoId;
        $res= $article_obj->stageArts($condition);
        if($res!="NO")
        {
            foreach($res as $res_key => $res_value)
            {
                $res[$res_key]['locked_by']=$lock_obj->getUserLocked($res[$res_key]['artId']);
                $res[$res_key]['contribcount']=$participate_obj->getContribCount($res[$res_key]['artId'], $condition['stage']);
                $res[$res_key]['maxcycle']=$participate_obj->getMaxCycle($res[$res_key]['artId']);
                $res[$res_key]['article_date']=$artprocess_obj->getFristVersionDate($res[$res_key]['partId']);
                $res[$res_key]['plag_percent'] =  $artprocess_obj->getFristVersionDate($res[$res_key]['partId']);
                $res[$res_key]['marks'] =  $artprocess_obj->getFristVersionDate($res[$res_key]['partId']);
                $res[$res_key]['marksreason'] = count(explode(",", $res[$res_key]['marks'][0]['reasons_marks'])) * 2;
                $reasonwithmarks = explode(",", $res[$res_key]['marks'][0]['reasons_marks']);
                $totalmarks = 0;
                foreach($reasonwithmarks as $keys){
                    $actmarks = explode("|", $keys);
                    $totalmarks+= $actmarks[1];
                }
                //$res[$res_key]['marks'] =  $totalmarks;
                $res[$res_key]['marks'] = $res[$res_key]['marks'][0]['marks'];
                $res[$res_key]['successrate'] =  $participate_obj->getContributorSuccessRate($res[$res_key]['identifier']);
                $artList[$res_value['artId']]=strtoupper($res_value['title']);
                $details=$participate_obj->getToolTipDetails($res[$res_key]['artId']);
                $res[$res_key]['details']= "Stage1  : ".$details[0]['partcount']."<br>"
                    ."Stage2  : ".$details[1]['partcount']."<br>"
                    ."Pending : ".$details[2]['partcount']."<br>"
                    ."Refused : ".$details[3]['partcount']."<br>"
                    ."Total   : ".$details[4]['partcount']."<br>";
                if($res[$res_key]['delivered'] !== 'yes')
                    $delivery_flag =  "no";
            }
            $this->_view->delivery_flag = $delivery_flag;
            $this->_view->paginator = $res;
        }
        $this->_view->render("proofread_stagearticles");
    }
    function format_time($t,$f=':') // t = seconds, f = separator
    {
        return sprintf("%02d%s%02d%s%02d", floor($t/3600), $f, ($t/60)%60, $f, $t%60);
    }
    public function refusealertAction()
    {
        $refusealert_params=$this->_request->getParams();
        $participate_obj = new EP_Participation_Participation();
        $automail=new Ep_Message_AutoEmails();
        $paricipationdetails=$participate_obj->getParticipateDetails($refusealert_params['partId']);
        $UserDetails=$automail->getUserType($paricipationdetails[0]['user_id']);
        if($UserDetails[0]['profile_type'] == 'sub-junior')
        {
            if($paricipationdetails[0]['jc0_resubmission'] != '')
                $resubtime = $paricipationdetails[0]['jc0_resubmission'];
            else
                $resubtime = $this->configval["jc0_resubmission"];
            $currenttime = strtotime($refusealert_params['currenttime']);
            $deliverytime = strtotime($refusealert_params['aodeldate']);
            $diff = $deliverytime - $currenttime;
            $hourstime = $this->format_time($diff);
            $hours = explode(":",$hourstime);
            if($resubtime <= '60')
                $resubtime=$resubtime." minutes";
            else
                $resubtime= $this->minutesToHours($resubtime)." hour(s)";
            $result['resub'] = $resubtime;
            echo json_encode($result);

        }
        else if($UserDetails[0]['profile_type'] == 'junior')
        {
            if($paricipationdetails[0]['jc_resubmission'] != '')
                $resubtime = $paricipationdetails[0]['jc_resubmission'];
            else
                $resubtime = $this->configval["jc_resubmission"];
            $currenttime = strtotime($refusealert_params['currenttime']);
            $deliverytime = strtotime($refusealert_params['aodeldate']);
            $diff = $deliverytime - $currenttime;
            $hourstime = $this->format_time($diff);
            $hours = explode(":",$hourstime);
            if($resubtime <= '60')
                $resubtime=$resubtime." minutes";
            else
                $resubtime= $this->minutesToHours($resubtime)."hour(s)";
            $result['resub'] = $resubtime;
            echo json_encode($result);

        }
        else if($UserDetails[0]['profile_type'] == 'senior')
        {
            if($paricipationdetails[0]['sc_resubmission'] != '')
                $resubtime = $paricipationdetails[0]['sc_resubmission'];
            else
                $resubtime = $this->configval["sc_resubmission"];
            $currenttime = strtotime($refusealert_params['currenttime']);
            $deliverytime = strtotime($refusealert_params['aodeldate']);
            $diff = $deliverytime - $currenttime;
            $hourstime = $this->format_time($diff);
            $hours = explode(":",$hourstime);
            if($resubtime <= '60')
                $resubtime=$resubtime." minutes";
            else
                $resubtime= $this->minutesToHours($resubtime)." hour(s)";
            $result['resub'] = $resubtime;
            echo json_encode($result);
        }
    }

    ////getting the selected options in the delivery creation////
    public function getDelSelectedOption($delId)
    {
        $deloptions_obj = new EP_Delivery_DeliveryOptions();
        $delivery_obj = new EP_Delivery_Delivery();
        $selectedoptionsres=$deloptions_obj->getDelOptions($delId);///to display in services box///
        $getparentoption =  $delivery_obj->getParentOption($delId);///to display in services box///
        if($selectedoptionsres!='NO')
        {
            for($i=0;$i<count($selectedoptionsres);$i++)
            {
                $res_seltdopts[$i]=$selectedoptionsres[$i]['option_id'];
            }
            $parent = $getparentoption[0]['premium_option'];
            if(empty($parent))
                return $res_seltdopts;
            else
            {
                $parent=$parent;
                array_unshift($res_seltdopts,$parent);
                return $res_seltdopts;
            }
        }
        else
        {
            $res_seltdopts = array();
            if(empty($parent))
                return $res_seltdopts;
            else
            {
                $parent=$parent;
                array_unshift($res_seltdopts,$parent);
                return $res_seltdopts;
            }
        }
    }
    ////////////////article under stage 1 correction ////////////////////////
    public function stage0CorrectionAction()
    {
        $artId = $this->_request->getParam('articleId');
        $artProcess_obj = new EP_Delivery_ArticleProcess();
        $article_obj = new EP_Delivery_Article();
        $delivery_obj = new Ep_Delivery_Delivery();
        $participate_obj = new EP_Participation_Participation();
        $artReassign_obj = new EP_Delivery_ArticleReassignReasons();
        $automail=new Ep_Message_AutoEmails();
		$crtpart_obj = new Ep_Participation_CorrectorParticipation();
        /////if user did not lock the perticular article it should not allow him to see detials/////////
        $lockstatus = $this->checklockAction($artId);
        if($lockstatus == 'no')
            $this->_redirect("/proofread/stage-deliveries?submenuId=ML3-SL11");

        $s0art_params=$this->_request->getParams();

        $details= $article_obj->getArticleDetails($artId);
        foreach($details as $key => $value)
        {
            $details[$key]['type']=$this->type_array[$details[$key]['type']];
           // $details[$key]['signtype']=$this->signtype_array[$details[$key]['sign_type']];
            $details[$key]['signtype']=$details[$key]['sign_type'];
        }
        $aoId = $details[0]['deliveryId'];
        $this->_view->articledetails=$details;
        //display article process grid in s1 correction/////////
        $partsOfArt = $participate_obj->getAllParticipationsStage($artId, 'stage0');
        $partId = $partsOfArt[0]['id'];
        $this->_view->partId = $partId;
        $versions_details = $artProcess_obj->getVersionDetails($partId);
        $this->_view->versions_details = $versions_details;
        ////getting the refuse count to change the button refuse to close//////
        $refused_count = $participate_obj->getRefusedCount($partId);
        $this->_view->refused_coundt = $refused_count[0]['refused_count'];
        $deldetails =  $delivery_obj->getDeliveryDetails($aoId);///to display in services box///
        $recentversion= $artProcess_obj->getRecentVersion($partId);

        $this->_view->deldetails=  $deldetails;

        $xmlfile=$artProcess_obj->getRecentVersion($partId);
        $xmlfilepath = $xmlfile[0]['article_path'];
        $xmlfileorgname = $xmlfile[0]['article_name'];
        $xmlfilename = pathinfo($xmlfilepath, PATHINFO_FILENAME);
        $filepath = APP_PATH_ROOT."plagarism/".$xmlfilename.".xml";

        if (file_exists($filepath)) {
            $this->_view->xmlplagfile = file_get_contents($filepath);       }

        if (file_exists($filepath)) {
            $plagdetails=$this->XMLParserS0correction($filepath, $xmlfileorgname);
        } else {
            $plagdetails = "no";
        }
        //////pop up displays/////
        $template_obj = new Ep_Message_Template();
        $res= $template_obj->refuseValidTemplates('null', $details[0]['product']);
        $this->_view->refusevalidtemps = $res;
        $this->_view->plagdetails=$plagdetails;
        $this->_view->filename='<b>Title : </b><span style="color:green;font-weight: bold;font-size: large;">'.$xmlfileorgname.'</span></br>';
        if(isset($s0art_params["s0art_approve"]))
        {
            $artdetails = $article_obj->getArticleDetails($artId);
            ////udate status participation table for stage///////
            if($artdetails[0]['correction'] == 'yes')
            {
                //	Simultaneous correction
				$selectedcorrector=$crtpart_obj->getSelectedCorrector($artId);
				if($selectedcorrector!="NO")
				{
					$expires = $this->correctorExpireTime($artId, $selectedcorrector);
					$datacorr = array("corrector_submit_expires"=>$expires,"participate_id"=>$partId);////////updating
					$querycorr = "corrector_id= '".$selectedcorrector."' AND article_id = '".$artId."'";
					$crtpart_obj->updateCrtParticipation($datacorr,$querycorr);
					
					$data = array("current_stage"=>"corrector", "status"=>"under_study","corrector_id"=>$selectedcorrector);
					$query = "id= '".$partId."'";
					$participate_obj->updateParticipation($data,$query);
					
					//Mail to selected corrector
					$parameters['article_title']=$artdetails[0]['title'];
					$parameters['ongoinglink']='/contrib/ongoing';
					$parameters['AO_end_date']=date('d/m/Y H:i', $expires);
						$user_obj=new Ep_User_User();
						$correctordetails=$user_obj->getAllUsersDetails($selectedcorrector);
						if($correctordetails[0]['profile_type2']=='senior')
							$resubmission=$artdetails[0]['correction_sc_resubmission'];
						else
							$resubmission=$artdetails[0]['correction_jc_resubmission'];
							
						if($resubmission < '60')
							$parameters['resubmit_hours']=$resubmission." minutes";
						else
							$parameters['resubmit_hours']= $this->minutesToHours($resubmission)." heures";
						$parameters['resubmit_hours']="<b>".$parameters['resubmit_hours']."</b>";	
					//print_r($parameters);exit;
					$automail->messageToEPMail($selectedcorrector,190,$parameters);
				}
				else
				{
					//Only if no corrector participation & participation time expired
					$newcorrector=$crtpart_obj->getNewCorrector($artId);
					
					if(($details[0]['correction_participationexpires']< strtotime('now') || $details[0]['correction_participationexpires']==0)  && $newcorrector=="NO")
            {
                $this->CorrectorParticipationExpire($artId);
                $data = array("current_stage"=>"corrector", "status"=>"under_study");////////updating
                $crtpart_obj = new Ep_Participation_CorrectorParticipation();
                $getpartid = $crtpart_obj->getCrtParticipationsUserIds($artId); //print_r($getpartid); exit;
                if($getpartid == 'NO')
                {
                   $this->sendMailToCorrectors($artId);
                }
                else
                {
                    ////get the current corrector to send him the mail////
                    $getcrttid = $crtpart_obj->getCurrenctCycleCorrector($artId);
                    $delivery_obj = new Ep_Delivery_Delivery();
                    $ao_id = $delivery_obj->getDeliveryID($artId);
                    $delartdetails = $delivery_obj->getArticlesOfDel($ao_id);
                    $expires=time()+(60*$delartdetails[0]['correction_participationexpires']);
                    $aoDetails=$delivery_obj->getPrAoDetails($ao_id);
                    $autoEmails=new Ep_Message_AutoEmails();
                    $parameters['AO_title']=$aoDetails[0]['title'];
                    $parameters['article_title'] = $aoDetails[0]['artname'];
                    $parameters['aoname_link'] = "/contrib/aosearch";
                    $parameters['submitdate_bo']=date('d/m/Y H:i', $expires);
                    $parameters['cliquant_article'] = "/contrib/mission-corrector-deliver?article_id=".$artId;
                    $partdetials = $participate_obj->getParticipateDetails($partId);
                    if($partdetials[0]['refused_count'] == 0)
                    {
                        $automail->messageToEPMail($getcrttid[0]['corrector_id'],82,$parameters);
                    }
                    else
                    {
                        $parameters['accept_refuse_at'] = date('d/m/Y H:i', strtotime($partdetials[0]['updated_at']));
                        $automail->messageToEPMail($getcrttid[0]['corrector_id'],62,$parameters);
                    }
                }
            }
            else
						$data = array("current_stage"=>"corrector", "status"=>"under_study");////////updating
				}
            }
            else
                $data = array("current_stage"=>"stage1", "status"=>"under_study");////////updating
            $query = "id= '".$partId."'";
            $participate_obj->updateParticipation($data,$query);
            //////////new record in the article process table////////////////
            $this->insertStageRecord($partId,$recentversion[0]["version"],'s0','approved');

            /////////////article history////////////////
            $actparams['artId'] = $artId;
            $actparams['stage'] = "plagiarism stage";
            $actparams['action'] = "validated";
            $this->articleHistory(11,$actparams);
            /////////////end of article history////////////////
            $this->unlockonactionAction($artId);
            $this->_helper->FlashMessenger(utf8_decode('Article Approved successfully'));
            $this->_redirect("/proofread/stage-deliveries?submenuId=ML3-SL11");
        }
        ///to post selected refused valid temps in stage s0 correction////////
        else if(isset($s0art_params['pop_s0art_disapprove']) || isset($s0art_params['pop_s0art_permdisapprove']))
        {
            $firsttversion= $artProcess_obj->getFristVersion($partId);///to get writer id
            $recentversion= $artProcess_obj->getRecentVersion($partId);
            $lastestversion= $artProcess_obj->getRecentVersionId($partId);
            $ContributorMail = $firsttversion[0]['email'];
            $CorrectorMail = $lastestversion[0]['email'];
            $contributorId = $firsttversion[0]['user_id'];
            $correctorId = $lastestversion[0]['user_id'];
            $userId = $this->adminLogin->userId;
            $refused_count= $participate_obj->getRefusedCount($partId);
            $refusedcountupdated = ++$refused_count[0]['refused_count'];

            $delivery_details=$delivery_obj->getArtDeliveryDetails($artId);
            $artReassign_obj->participate_id=$partId ;
            //$artReassign_obj->refused_by=$recentversion[0]['user_id'] ;///for corrector id//
            $artReassign_obj->refused_by=$userId ;///for corrector id//
            $artReassign_obj->contributor=$firsttversion[0]['user_id'] ;//for writer id///
            $artReassign_obj->stage="s0" ;
            if(isset($s0art_params['pop_s0art_disapprove']))
            {
                /////udate status participation table///////
                $expires = $this->writerExpireResubmitTime($artId, $contributorId); //echo "hello2"; exit;

                $data = array("status"=>"disapproved", "current_stage"=>"contributor","article_submit_expires"=>$expires, "refused_count"=>$refusedcountupdated);////////updating
                $query = "id= '".$partId."'";
                $participate_obj->updateParticipation($data,$query);
                //////////new record in the article process table////////////////
                $this->insertStageRecord($partId,$recentversion[0]["version"],'s0','disapproved');
                 ///////////sending mail to corrector that document has been  rejected///
                $Message = stripslashes($s0art_params["commentsRefuse_".$partId]) ;
                /////////////article history////////////////
                $actparams['contributorId'] = $contributorId;
                $actparams['artId'] = $artId;
                $actparams['stage'] = "plagiarism stage";
                $actparams['action'] = "refused";
                $this->articleHistory(13,$actparams);
                /////////////end of article history////////////////

            }
            else if(isset($s0art_params['pop_s0art_permdisapprove']))
            {
                if($s0art_params['sendtofo'] == 'yes')
                {
                    ///////check the cycle count in participation tabel and increament//////////
                    $this->republish($artId);///updating cycle and to show in FO////
                    if($s0art_params["anouncebyemail"] == 'yes')
                        $this->sendMailToContribs($artId);
                }
                else
                {
                    /////udate status article table///////
                    $data = array("send_to_fo"=>"no");////////updating
                    $query = "id= '".$artId."'";
                    $article_obj->updateArticle($data,$query);
                }

                /////updating the jc0 user to black list as he is not fit///////
                $this->makejc0BlackList($contributorId);
                /////udate status participation table///////
                $data = array("status"=>"closed", "current_stage"=>"stage0");////////updating
                $query = "id= '".$partId."'";
                $participate_obj->updateParticipation($data,$query);

                //////////new record in the article process table////////////////
                $this->insertStageRecord($partId,$recentversion[0]["version"],'s0','closed');

                $Message = stripslashes($s0art_params["commentsRefusePerm_".$partId]) ;
                /////////////article history////////////////
                $actparams['artId'] = $artId;
                $actparams['stage'] = "plagiarism stage";
                $actparams['action'] = "refused definite";
                $this->articleHistory(12,$actparams);
                /////////////end of article history////////////////
            }

            $this->unlockonactionAction($artId);
            /*///////////sending mail to contributor that document has been  rejected///
            $object = 'Votre article : <b>'.$delivery_details[0]['articleName'].'</b> sur Edit-place';
            $body=preg_replace('/\t/','',$Message);
            $mail = new Zend_Mail();
            $mail->addHeader('Reply-To', $this->configval['mail_from']);
            $mail->setBodyHtml($body)
                ->setFrom($this->configval['mail_from'])
                ->addTo($ContributorMail)
                ->setSubject($object);
            if($mail->send())
            {
                $object = 'Votre article : '.$delivery_details[0]['articleName'].' sur Edit-place';
                $automail->sendMailEpMailBox($contributorId,$object,$body);  ///sending mail to EP account
                $this->_helper->FlashMessenger(utf8_decode('Article Refusée avec succès.'));
                $this->_redirect("/proofread/stage-deliveries?submenuId=ML3-SL11");
            }*/
            $object = 'Your article : '.$delivery_details[0]['articleName'].' in Edit-place';
            $body=preg_replace('/\t/','',$Message);
            $automail->sendMailEpMailBox($contributorId,$object,$body);  ///sending mail to EP account
            $this->_helper->FlashMessenger(utf8_decode('Article refused successfully.'));
            $this->_redirect("/proofread/stage-deliveries?submenuId=ML3-SL11");
        }
        else
        {
            $this->render('proofread_stage0correction');
        }
    }

    ////////////////article under stage 1 correction ////////////////////////
    public function stage1CorrectionAction()
    {
        $prevurl = getenv("HTTP_REFERER");
        $artId = $this->_request->getParam('articleId');
        $partId = $this->_request->getParam('participateId') ;
        $participate_obj = new EP_Participation_Participation();
        $article_obj = new EP_Delivery_Article();
        $options_obj = new EP_Delivery_Options();
        $delivery_obj = new EP_Delivery_Delivery();
        $artProcess_obj = new EP_Delivery_ArticleProcess();
        $autoEmails = new Ep_Message_AutoEmails();
        /////if user did not lock the perticular article it should not allow him to see detials/////////
         $lockstatus = $this->checklockAction($artId);
        if($lockstatus == 'no')
            $this->_redirect("/proofread/stage-deliveries?submenuId=ML3-SL2");
        $s1art_params=$this->_request->getParams();
        if($artId!=NULL)
        {
            $details= $article_obj->getArticleDetails($artId);
        //////pop up displays/////
        $template_obj = new Ep_Message_Template();
            $res= $template_obj->refuseValidTemplates('null',$details[0]['product']);
        $this->_view->refusevalidtemps = $res;
            $this->_view->selectedrefusalreasons = explode("|",$details[0]['refusalreasons']);
            $refualreasonContent = "";
            for($i=0; $i<count($res); $i++)
        {
                if(in_array($res[$i]['identifier'], $this->_view->selectedrefusalreasons))
                {
                    $refualreasonContent.= $res[$i]['content']."<br><br>";
                }
            }
             $this->_view->refualreasonContent = $refualreasonContent;

            foreach($details as $key => $value)
            {
                $details[$key]['type']=$this->type_array[$details[$key]['type']];
                //$details[$key]['sign_type']=$this->signtype_array[$details[$key]['sign_type']];
                $details[$key]['signtype']=$details[$key]['sign_type'];
            }
            $aoId = $details[0]['deliveryId'];
            $rreasons = explode("|",$details[0]['refusalreasons']);
            if(count($rreasons) != 0)
            {
                foreach($rreasons as $keys)
                {
                    $res = $template_obj->refuseValidTemplatesOnId($keys);
                    $refreason[$keys]  = $res[0]['title'];
                }
            }else{
                $refreason[0]  = 'overall rating';
            }
            $this->_view->articledetails=$details;
            $this->_view->rreasons=$refreason;
            ////////getting previous versions for the marks////
            $recentversion = $artProcess_obj->getRecentVersionWithMarks($partId);
			//print_r($recentversion);
            if($recentversion != 'NO')
            {
                if($recentversion[0]['reasons_marks']!=0)
				{
					$rreasons = explode(",", $recentversion[0]['reasons_marks']); 
					$prevreasons = array();
					for ($i = 0; $i < count($rreasons); $i++)
					{
						$array = explode("|", $rreasons[$i]);
						$res = $template_obj->refuseValidTemplatesOnId(trim($array[0]));
						$prevreasons[$array[0]] = $res[0]['title'];
						//$s1reasons[$i] = $array[0];
						$prevmarks[$i] = $array[1];
					}
					//print_r($prevmarks); exit;
					$this->_view->prevreasons=$prevreasons;
					$this->_view->prevmarks=$prevmarks;
				}
				else
					$prevmarks = array_fill(0, count($rreasons), 0); 
                //$this->_view->prevmarkscount=array_sum($prevmarks);
                $this->_view->prevmarkscount=$recentversion[0]['marks'];
                $this->_view->avgs1marks=$recentversion[0]['marks'];
                $this->_view->previousdetails=$recentversion[0]['reasons_marks'];
                $this->_view->previouscomments=$recentversion[0]['comments'];
                $this->_view->rreasonscount=count($rreasons)*2;
				$this->_view->reasonslist=implode(",",$prevmarks);
            }else{
                $rreasons = explode("|",$details[0]['refusalreasons']);
                if(count($rreasons) != 0)
                {
                    foreach($rreasons as $keys)
                    {
                        $res = $template_obj->refuseValidTemplatesOnId($keys);
                        $refreason[$keys]  = $res[0]['title'];
                    }
                }else{
                    $refreason[0]  = 'overall rating';
                }
                $this->_view->rreasons=$refreason;
                $prevmarks = array_fill(0, count($rreasons), 0);   ///print_r($prevmarks); exit;
                $this->_view->prevmarks=$prevmarks;//print_r($refreason);
                $this->_view->prevmarkscount=0;
                $this->_view->avgs1marks=0;
                $this->_view->previousdetails=0;
                $this->_view->previouscomments=NULL;
                $this->_view->rreasonscount=count($rreasons)*2;
                $this->_view->reasonslist=implode(",",$prevmarks);
            }
            //display article process grid in s1 correction/////////
            $partsOfArt = $participate_obj->getAllParticipationsStage($artId, 'stage1');
            $partId = $partsOfArt[0]['id'];
            $this->_view->partId = $partId;
            $versions_details = $artProcess_obj->getVersionDetails($partId);
			$this->_view->fileName=$versions_details[0]['article_name'];
            $this->_view->versions_details = $versions_details;
            ////getting the refuse count to change the button refuse to close//////
            $refused_count = $participate_obj->getRefusedCount($partId);
            $this->_view->refused_count = $refused_count[0]['refused_count'];
            $deldetails =  $delivery_obj->getDeliveryDetails($aoId);///to display in services box///


            $this->_view->deldetails=  $deldetails;
            $this->_view->options=$options_obj->getOptions($aoId);///to display in services box///
            $this->_view->res_seltdopts = $this->getDelSelectedOption($aoId); ///option selected for the delivery
			$this->_view->now= strtotime('now');

        }
        if(isset($s1art_params["s1art_approve"]))
        {  
             $partId = $s1art_params["participateId"] ;
            /////upload file/////////////////
            $recentversion= $artProcess_obj->getRecentVersion($partId);
            $recentDetials = $artProcess_obj->getVersionDetailsByVersion($partId, $recentversion[0]["version"]);
            if($_FILES['art_doc_'.$partId]['tmp_name'] != '')
            {
                $tmpName = $_FILES['art_doc_'.$partId]['tmp_name'];
                $artName = $_FILES['art_doc_'.$partId]['name'];
                $ext = explode('.',$artName);
                $extension = $ext[1];
                $art_path = $artId."_".$this->adminLogin->userId."_".rand(10000, 99999).".".$extension;
                $artProcess_obj->participate_id=$partId ;
                $artProcess_obj->user_id=$this->adminLogin->userId;
                $artProcess_obj->stage='s1' ;
                $artProcess_obj->status='approved' ;
                $artProcess_obj->marks=$s1art_params["marks"] ;
                $artProcess_obj->reasons_marks=$s1art_params["markswithreason"] ;
                $artProcess_obj->comments=$s1art_params["marks_comments"] ;
                $artProcess_obj->article_path=$artId."/".$art_path ;
                $artProcess_obj->article_name=$artName ;
                $version = $recentversion[0]["version"]+1;
                $artProcess_obj->version=$version ;
                $artProcess_obj->plagxml='' ;
                $artProcess_obj->art_file_size_limit_email ='' ;
                //////////////////uploading the document///////////////////////////////
                $server_path = "/home/sites/site7/web/FO/articles/";
                //$server_path = ROOT_PATH."FO/articles/";
                $articleDir = $server_path.$artId;
                echo $newfile = $articleDir."/".$art_path;
                if (move_uploaded_file($tmpName, $newfile))
                {
                    //Antiword obj to get content from uploaded article
                    $antiword_obj=new Ep_Antiword_Antiword($newfile);
                    $artProcess_obj->article_doc_content=$antiword_obj->getContent();
                    $artProcess_obj->article_words_count=$antiword_obj->count_words($artProcess_obj->article_doc_content);
                    $artProcess_obj->insert();
                }
            }
            else{
                $this->insertStageRecord($partId,$recentversion[0]["version"],'s1','approved');
                $recentversion= $artProcess_obj->getRecentVersion($partId);
                $data = array("marks"=>$s1art_params["marks"], "reasons_marks"=>$s1art_params["markswithreason"], "comments"=>$s1art_params["marks_comments"]);////////updating
                $query = "participate_id= '".$partId."' AND version='".$recentversion[0]['version']."'";
                $artProcess_obj->updateArticleProcess($data,$query);
                ////udate status article process table///////
                //$data = array("status"=>"approved","stage"=>'s1');////////updating
               // $query = "participate_id= '".$partId."' AND version='".$recentversion[0]['version']."'";
              //  $artProcess_obj->updateArticleProcess($data,$query);
            }
            ///end of file upload///////////
            ////udate status participation table for stage///////
            $data = array("current_stage"=>"stage2", "status"=>"under_study", "marks"=>$recentversion[0]['marks']);////////updating
            $query = "id= '".$partId."'";
            $participate_obj->updateParticipation($data,$query);


            $paricipationdetails=$participate_obj->getParticipateDetails($partId);
            $Message = $s1art_params["commentsValidate_".$partId] ;

            //sendidng the mail to contirbutor //
            $automail=new Ep_Message_AutoEmails();
            $email=$automail->getAutoEmail(84);
            $Object=$email[0]['Object'];
            $receiverId = $paricipationdetails[0]['user_id'];
            $autoEmails->sendMailEpMailBox($receiverId,$Object,$Message);
            /////////////article history////////////////
            $actparams['artId'] = $artId;
            $actparams['stage'] = "stage1";
            $actparams['action'] = "validated";
            $this->articleHistory(21,$actparams);
            /////////////end of article history////////////////
            /// unlock the article///////////////
            $this->unlockonactionAction($artId);
            $this->_helper->FlashMessenger(utf8_decode('Article Approved successfully'));
            //$this->_redirect("/proofread/stage-articles?submenuId=ML3-SL2&aoId=".$aoId);
            $this->_redirect("/proofread/stage-deliveries?submenuId=ML3-SL2");
        }
        else
        {
            
	    $this->render('proofread_stage1correction');
        }
    }

   ////////////////article under stage 2 correction ////////////////////////
    public function stage2CorrectionAction(){

    $url = getenv('REQUEST_URI');
    $artId = $this->_request->getParam('articleId');
    $participId = $this->_request->getParam('participId');    //this Id is from Participation table not CorrectorParticicpation table//
    $participate_obj = new EP_Participation_Participation();
    $crtparticipate_obj  = new Ep_Participation_CorrectorParticipation();
    $autoEmails = new Ep_Message_AutoEmails();
    $article_obj = new EP_Delivery_Article();
    $options_obj = new Ep_Delivery_Options();
    $del_obj = new Ep_Delivery_Delivery();
    $artProcess_obj = new EP_Delivery_ArticleProcess();
    $details = $article_obj->getArticleDetails($artId);
    $royalty_obj=new Ep_Payment_Royalties();
    /////if user did not lock the perticular article it should not allow him to see detials/////////
    $lockstatus = $this->checklockAction($artId);
    $template_obj = new Ep_Message_Template();
    if($lockstatus == 'no')
        $this->_redirect("/proofread/stage-deliveries?submenuId=ML3-SL3");

    $s2art_params=$this->_request->getParams();
    foreach ($details as $key => $value) {
        $details[$key]['type']=$this->type_array[$details[$key]['type']];
        //$details[$key]['sign_type']=$this->signtype_array[$details[$key]['sign_type']];
        $details[$key]['signtype']=$details[$key]['sign_type'];
    }
    $aoId = $details[0]['deliveryId'];
    $this->_view->articledetails=$details;
    //////pop up displays/////
    $res = $template_obj->refuseValidTemplates('null', $details[0]['product']);
    $this->_view->refusevalidtemps = $res;
    $this->_view->selectedrefusalreasons = explode("|",$details[0]['refusalreasons']);
    $refualreasonContent = "";
    for($i=0; $i<count($res); $i++)
    {
        if(in_array($res[$i]['identifier'], $this->_view->selectedrefusalreasons))
        {
            $refualreasonContent.= $res[$i]['content']."<br><br>";
        }
    }
    $this->_view->refualreasonContent = $refualreasonContent;
    //display article process grid in s1 correction/////////

    $partsOfArt = $participate_obj->getAllParticipationsStage($artId, 'stage2');
    $partId = $partsOfArt[0]['id'];
    $this->_view->partId = $partId;
    $versions_details = $artProcess_obj->getVersionDetails($partId);
    $this->_view->versions_details = $versions_details;
    $versions_user_details = $artProcess_obj->getRecentVersionId($partId);
    $this->_view->versions_user_details = $versions_user_details;
    $versions_contributor_details = $artProcess_obj->getRecentContributorVersionId($partId);
    $this->_view->versions_contributor_details = $versions_contributor_details;
    $recentversion = $artProcess_obj->getRecentVersion($partId);
    //print_r($recentversion);
    if($recentversion[0]['reasons_marks'] == '')
    {
        $rreasons = explode("|",$details[0]['refusalreasons']);
        if(count($rreasons) != 0)
        {
            foreach($rreasons as $keys)
            {
                $res = $template_obj->refuseValidTemplatesOnId($keys);
                $refreason[$keys]  = $res[0]['title'];
            }
        }else{
            $refreason[0]  = 'overall rating';
        }
        $this->_view->rreasons=$refreason;
        $this->_view->s1reasons=$refreason;
        $s1marks=array_fill(0, 10, 0);
        $this->_view->reasonslist=implode(",",$s1marks);
        $this->_view->s1markscount=0;
        $this->_view->avgs1marks=0;
        $this->_view->previousdetails=$recentversion[0]['reasons_marks'];
        $this->_view->rreasonscount=count($refreason)*2;
        if($recentversion[0]['stage'] == 'corrector') // if  prevous version isn from corrector then  desable the rating and commntes
            $this->_view->versionfromCorrector = 'true';
        else
            $this->_view->versionfromCorrector = 'false';
    }
    else
    {
        $rreasons = explode(",", $recentversion[0]['reasons_marks']); //print_r($rreasons);
        $s1reasons = array();
        if(count($rreasons) != 0)
        {
            for ($i = 0; $i < count($rreasons); $i++)
            {
                $array = explode("|", $rreasons[$i]);
                $res = $template_obj->refuseValidTemplatesOnId(trim($array[0]));
                $s1reasons[$array[0]] = $res[0]['title'];
                //$s1reasons[$i] = $array[0];
                $s1marks[$i] = $array[1];
            }
        }else{
            $s1reasons[0] = 'overall rating';
        }
        $this->_view->s1reasons=$s1reasons;
        $this->_view->s1marks=$s1marks;
        $this->_view->reasonslist=implode(",",$s1marks);
        // $this->_view->s1markscount=array_sum($s1marks);
        $this->_view->s1markscount=$recentversion[0]['marks'];
        $this->_view->avgs1marks=$recentversion[0]['marks'];
        $this->_view->previousdetails=$recentversion[0]['reasons_marks'];
        $this->_view->rreasonscount=count($rreasons)*2;
        if($recentversion[0]['stage'] == 'corrector') // if  prevous version isn from corrector then  desable the rating and commntes
            $this->_view->versionfromCorrector = 'true';
        else
            $this->_view->versionfromCorrector = 'false';
    }
    //display article process grid in s1 correction/////////
    if($details[0]['correction'] == 'yes')
    {
        $crtpartsOfArt = $crtparticipate_obj->getAllCrtParticipationsStage2($artId);
        $crtpartId = $crtpartsOfArt[0]['id'];
        $this->_view->crtpartId = $crtpartId;
    }
    ////getting the refuse count to change the button refuse to close//////
    $refused_count= $participate_obj->getRefusedCount($partId);
    $this->_view->refused_count = $refused_count[0]['refused_count'];
    $deldetails =  $del_obj->getDeliveryDetails($aoId);///to display in services box///
    $this->_view->deldetails=  $deldetails;
    $this->_view->options=$options_obj->getOptions($aoId);///to display in services box///
    $this->_view->res_seltdopts = $this->getDelSelectedOption($aoId); ///option selected for the delivery
    ///check whether the marks given in the stage1 correction phase////
    $specsmarks = $artProcess_obj->getRecentVersionByTime($partId);// echo $specsmarks[0]['marks']; exit;///to display in services box///
    $this->_view->marksgiven = $specsmarks[0]['marks'];
    if(isset($s2art_params["s2art_approve"]) && $s2art_params["s2art_approve"]=='yes')//from s2 correction and writerarts pages/
    {
        if($s2art_params['marks'] == '')
            $s2art_params['marks'] = 0;
        if($s2art_params["markswithreason"] == '')
            $s2art_params["markswithreason"] = 0;
        $partId = $s2art_params["participateId"] ;
        $crtpartId = $s2art_params["crtparticipateId"] ;
        $recentversion= $artProcess_obj->getRecentVersion($partId);
        if($s2art_params["participateType"] == "normalParticipation")
        {
            $Message = $s2art_params["commentsValidate_".$partId] ;
        }
        elseif($s2art_params["participateType"] == "correctorParticipation"){

            $Message = $s2art_params["commentsCrtValidate_".$crtpartId] ;
        }
        $recentDetials = $artProcess_obj->getVersionDetailsByVersion($partId, $recentversion[0]["version"]);
        ////file upload code///////////
        if($_FILES['art_doc_'.$partId]['tmp_name'] != '')
        {
            $tmpName = $_FILES['art_doc_'.$partId]['tmp_name'];
            $artName = $_FILES['art_doc_'.$partId]['name'];
            $ext = explode('.',$artName);
            $extension = $ext[1];
            $art_path = $artId."_".$this->adminLogin->userId."_".rand(10000, 99999).".".$extension;
            $artProcess_obj->participate_id=$partId ;
            $artProcess_obj->user_id=$this->adminLogin->userId;
            $artProcess_obj->stage='s2' ;
            $artProcess_obj->status='approved' ;
            $artProcess_obj->marks=$s2art_params["markstotal"] ;
            $artProcess_obj->reasons_marks=$s2art_params["markswithreason"] ;
            $artProcess_obj->comments=$s2art_params["marks_comments"] ;
            $artProcess_obj->article_path=$artId."/".$art_path ;
            $artProcess_obj->article_name=$artName ;
            $version = $recentversion[0]["version"]+1;
            $artProcess_obj->version=$version ;
            $artProcess_obj->plagxml='' ;
            $artProcess_obj->art_file_size_limit_email ='' ;

            //////////////////uploading the document///////////////////////////////
            $server_path = "/home/sites/site7/web/FO/articles/";
            $articleDir = $server_path.$artId;
            $newfile = $articleDir."/".$art_path;
            if (move_uploaded_file($tmpName, $newfile))
            {
                //Antiword obj to get content from uploaded article
                $antiword_obj=new Ep_Antiword_Antiword($newfile);
                $artProcess_obj->article_doc_content=$antiword_obj->getContent();
                $artProcess_obj->article_words_count=$antiword_obj->count_words($artProcess_obj->article_doc_content);
                $artProcess_obj->insert();
            }
        }
        else{
            ///inserting a record into artcleprocess tabel///
            $this->insertStageRecord($partId,$recentversion[0]["version"],'s2','approved');
            /////udate status article process table///////
            $recentversion= $artProcess_obj->getRecentVersion($partId);
            $data = array("marks"=>$s2art_params["markstotal"], "reasons_marks"=>$s2art_params["markswithreason"], "comments"=>$s2art_params["marks_comments"]);////////updating
            $query = "participate_id= '".$partId."' AND version='".$recentversion[0]['version']."'";
            $artProcess_obj->updateArticleProcess($data,$query);
            /////udate status article process table///////
            /* $data = array("status"=>"approved", "stage"=>'s2');////////updating
             $query = "participate_id= '".$partId."' AND version='".$recentversion[0]['version']."'";
             $artProcess_obj->updateArticleProcess($data,$query);*/
        }
        $artId = $this->_request->getParam('articleId');

        ////udate status participation table for stage///////
        $premium=$del_obj->checkPremiumAO($artId);
        $paricipationdetails=$participate_obj->getParticipateDetails($partId);

        if($premium=='NO')
            $data = array("current_stage"=>"client", "status"=>"under_study", "marks"=>$recentversion[0]['marks']);////////updating
        else
        {
            $data = array("current_stage"=>"client", "status"=>"published", "marks"=>$recentversion[0]['marks']);////////updating

            if($royalty_obj->checkRoyaltyExists($paricipationdetails[0]['article_id'])=='NO')
            {
                $royalty_obj->participate_id=$paricipationdetails[0]['participateId'];
                $royalty_obj->article_id=$paricipationdetails[0]['article_id'];
                $royalty_obj->user_id=$paricipationdetails[0]['user_id'];
                $royalty_obj->price=$paricipationdetails[0]['price_user'];
                $royalty_obj->correction="no";
                $royalty_obj->currency=$paricipationdetails[0]['currency'];
                $royalty_obj->insert();
                if($s2art_params["participateType"] == "correctorParticipation")
                {
                    $royalty_obj=new Ep_Payment_Royalties();
                    $crtparicipationdetails=$crtparticipate_obj->getCrtParticipateDetails($crtpartId);
                    if($crtparicipationdetails != 'NO')
                    {
                        $royalty_obj->participate_id=$partId;
                        $royalty_obj->crt_participate_id=$crtpartId;
                        $royalty_obj->article_id=$crtparicipationdetails[0]['article_id'];
                        $royalty_obj->user_id=$crtparicipationdetails[0]['corrector_id'];
                        $royalty_obj->price=$crtparicipationdetails[0]['price_corrector'];
                        $royalty_obj->correction="yes";
                        $royalty_obj->currency=$paricipationdetails[0]['currency'];
                        $royalty_obj->insert();
                    }
                }
            }
        }
        $query = "article_id= '".$artId."' AND id = '".$partId."'";
        $participate_obj->updateParticipation($data,$query);
        if($s2art_params["participateType"] == "correctorParticipation")
        {
            if($premium=='NO')
                $data1 = array("current_stage"=>"client", "status"=>"under_study");////////updating
            else
                $data1= array("current_stage"=>"client", "status"=>"published");////////updating

            $query = "article_id= '".$artId."' AND id = '".$crtpartId."'";
            $crtparticipate_obj->updateCrtParticipation($data1,$query);
        }

        ///////update in article///////////
        $data = array("file_path"=>$recentversion[0]['article_path']);////////updating
        $query = "id= '".$artId."'";
        $article_obj->updateArticle($data,$query);




        /* *Sending Mails ***/
        /////////send mail to contributor///////////////////////////////////////
        $paricipationdetails=$participate_obj->getParticipateDetails($partId);
        $contribDetails=$autoEmails->getContribUserDetails($paricipationdetails[0]['user_id']);
        ///////////////if user is sub-junior then update him to jc/////////
        $user_obj=new Ep_User_User();
        $userDetails =  $user_obj->getAllUsersDetails($paricipationdetails[0]['user_id']);
        if($userDetails[0]['profile_type'] == "sub-junior")
        {
            $data = array("profile_type"=>"junior");////////updating
            $query = "identifier = '".$userDetails[0]['identifier']."' ";
            $user_obj->updateUser($data,$query);
        }

        if($contribDetails[0]['firstname']!=NULL)
            $parameters['contributor_name']= $contribDetails[0]['firstname']." ".$contribDetails[0]['lastname'];
        else
            $parameters['contributor_name']= $contribDetails[0]['email'];

        $parameters['created_date']=date("d/m/Y",strtotime($paricipationdetails[0]['created_at']));
        $parameters['document_link']="/client/ongoingao";
        $parameters['invoice_link']="/client/invoice";
        if($paricipationdetails[0]['currency']=='euro')
            $curr='&euro;';
        else
            $curr='&pound;';
        $parameters['royalty']=$paricipationdetails[0]['price_user'].$curr;
        //sendidng the mail to contirbutor //
        $automail=new Ep_Message_AutoEmails();
        $email=$automail->getAutoEmail(53);
        $Object=$email[0]['Object'];
        $receiverId = $paricipationdetails[0]['user_id'];
        $autoEmails->sendMailEpMailBox($receiverId,$Object,$Message);

        /////////send mail to corrector/////////////////////////////////
        if($s2art_params["participateType"] == "correctorParticipation")
        {
            $paricipationdetails=$crtparticipate_obj->getCrtParticipateDetails($crtpartId);
            $correctorDetails=$autoEmails->getContribUserDetails($paricipationdetails[0]['corrector_id']);
            if($correctorDetails[0]['firstname']!=NULL)
                $parameters['corrector_name']= $correctorDetails[0]['firstname']." ".$correctorDetails[0]['lastname'];
            else
                $parameters['corrector_name']= $correctorDetails[0]['email'];

            $parameters['created_date']=date("d/m/Y",strtotime($paricipationdetails[0]['created_at']));
            $parameters['document_link']="/client/ongoingao";
            $parameters['invoice_link']="/client/invoice";
            $parameters['royalty']=$paricipationdetails[0]['price_corrector'];
            $parameters['article_title']= $paricipationdetails[0]['title'];
            $parameters['articlename_link']="/contrib/mission-deliver?article_id=".$artId;
            //sendidng the mail to corrector //
            $receiverId = $paricipationdetails[0]['corrector_id'];
            $autoEmails->messageToEPMail($receiverId,59,$parameters);
        }
        /////////////article history////////////////
        $actparams['artId'] = $artId;
        $actparams['stage'] = "stage2";
        $actparams['action'] = "validated";
        $this->articleHistory(25,$actparams);
        /////////////end of article history////////////////
        /* *sending mail to Client**/
        $clientDetails=$autoEmails->getUserDetails($paricipationdetails[0]['clientId']);
        if($clientDetails[0]['username']!=NULL)
            $parameters['client_name']= $clientDetails[0]['username'];
        else
            $parameters['client_name']= $clientDetails[0]['email'];
        if($deldetails[0]['mail_send']=='yes')
        {
            // $this->messageToEPMail($paricipationdetails[0]['clientId'],1,$parameters);
        }
        //Insert Recent Activities
        $recent_acts_obj=new Ep_User_RecentActivities();
        $ract=array("type" => "bopublish","user_id"=>$paricipationdetails[0]['clientId'],"activity_by"=>"bo","article_id"=>$artId);
        $recent_acts_obj->insertRecentActivities($ract);
        $deliveryId=$del_obj->getDeliveryID($artId);
        if($deliveryId!="NO")
        {
            $checkLastAO=$del_obj->checkLastArticleAO($deliveryId);
            if($checkLastAO=="YES")
            {
                //sending the mail to client when last alrticle is validated;
                if($deldetails[0]['mail_send']=='yes')
                {
                    //  $this->messageToEPMail($paricipationdetails[0]['clientId'],12,$parameters);
                }
                ///////////////////////////////////////////
                $delcreateduser=$del_obj->getDelCreatedUser($deliveryId);

                $object="L'appel d'offres ".$delcreateduser[0]['title']." est complete; ";
                $text_mail="<p>Cher ".$delcreateduser[0]['first_name']." ,<br><br>
                                        Le dernier article de l'appel d'offres ".$delcreateduser[0]['title']." vient d'&ecirc;tre valid&eacute;!<br><br>
                                        Merci de cliquer ici pour acc&eacute;der &agrave; la page de suivi de l'AO.<br><br>
                                        Cordialement,<br>
                                        <br>
                                        Toute l'&eacute;quipe d&rsquo;Edit-place</p>";
                $mail = new Zend_Mail();
                $mail->addHeader('Reply-To',$this->configval['mail_from']);
                $mail->setBodyHtml($text_mail)
                    ->setFrom($this->configval['mail_from'])
                    ->addTo($delcreateduser[0]['email'])
                    ->setSubject($object);
                if($mail->send())
                {
                    $this->_helper->FlashMessenger(utf8_decode('Article Approuvé avec succès'));
                    $this->_redirect("/proofread/stage-deliveries?submenuId=ML3-SL3");
                }
            }
            $this->_redirect("/proofread/stage-deliveries?submenuId=ML3-SL3");
        }

        $this->_helper->FlashMessenger(utf8_decode('Article Approved successfully'));
        $this->_redirect("/proofread/stage-deliveries?submenuId=ML3-SL3");
    }
    else if(isset($s2art_params['s2art_corrector_disapprove']) || isset($s2art_params['s2art_corrector_permdisapprove']))
    {
        $crtparticipate_obj  = new Ep_Participation_CorrectorParticipation();
        $crtpartId = $s2art_params["crtparticipateId"] ;
        //$partId = $s2art_params["participateId"] ;
        $delivery_obj=new Ep_Delivery_Delivery();
        $user_obj = new Ep_User_User();
        $refused_count= $crtparticipate_obj->getCrtRefusedCount($crtpartId);
        $refusedcountupdated = ++$refused_count[0]['refused_count'];
        $CorrectorMail= $crtparticipate_obj->getCorrectorDetails($crtpartId);///to get writer id
        $CorrectorMail = $CorrectorMail[0]['email'];
        $paticipateTableId= $crtparticipate_obj->getParticipateId($crtpartId); //getting the paticipation table id
        $partId =   $paticipateTableId[0]['participate_id'];
        $recentversion= $artProcess_obj->getRecentVersion($partId);  //with paticipation tabel id
        $correctorId = $crtparticipate_obj->getCorrectorId($crtpartId, $artId);
        $correctorId = $correctorId[0]['corrector_id'];
        if(isset($s2art_params['s2art_corrector_disapprove'])) ///when corrector refused by editor temporaryly
        {
            $profiletype = $user_obj->getAllUsersDetails($correctorId);
            $delivery_details=$delivery_obj->getArtDeliveryDetails($artId);
            if($profiletype[0]['type2'] == 'corrector')
            {
                $resubtime = $this->correctorResubmitTime($artId, $correctorId);
            }
            else
            {
                $resubtime = $this->writerResubmitTime($artId, $correctorId);
            }
            $parameters['resubmit_time']= $resubtime;
            // $subtime = $deldetails[0]['correction_resubmission'];//2days
            $expires=time()+(60*$resubtime);
            // $expires=time()+(60*60*$subtime);
            $data = array("status"=>"disapproved", "current_stage"=>"corrector", "corrector_submit_expires"=>$expires, "refused_count"=>$refusedcountupdated);////////updating
            $query = "id= '".$crtpartId."'";
            $crtparticipate_obj->updateCrtParticipation($data,$query);
            ///////updating the participation table ///////
            $data = array("status"=>"under_study", "current_stage"=>"corrector", "marks"=>$recentversion[0]['marks']);////////updating
            $query = "id= '".$partId."'";
            $participate_obj->updateParticipation($data,$query);
            /////udate status article process table///////
            $this->insertStageRecord($partId,$recentversion[0]["version"],'s2','disapproved');
            $recentversion= $artProcess_obj->getRecentVersion($partId);
            $data = array("marks"=>$s2art_params["marks"], "comments"=>$s2art_params["marks_comments"]);////////updating
            $query = "participate_id= '".$partId."' AND version='".$recentversion[0]['version']."'";
            $artProcess_obj->updateArticleProcess($data,$query);
            /////////////article history////////////////
            $actparams['artId'] = $artId;
            $actparams['stage'] = "stage2";
            $actparams['action'] = "refused";
            $this->articleHistory(27,$actparams);
            /////////////end of article history////////////////
            /// unlock the article///////////////
            $this->unlockonactionAction($artId);
            ///////////sending mail to corrector that document has been  rejected///
            $Message = stripslashes($s2art_params["commentsCrtRefuse"]) ;
            $body=preg_replace('/\t/','',$Message);
            $mail = new Zend_Mail();
            $mail->addHeader('Reply-To', $this->configval['mail_from']);
            $mail->setBodyHtml($body)
                ->setFrom($this->configval['mail_from'])
                ->addTo($CorrectorMail)
                ->setSubject('Correction refus&eacute;e par Edit-place');
            if($mail->send())
            {
                $Object = 'Correction refus&eacute;e par Edit-place';
                $autoEmails->sendMailEpMailBox($correctorId,$Object,$body);  ///sending mail to EP account
                $this->_helper->FlashMessenger(utf8_decode('Article refused successfully.'));
                $this->_redirect("/proofread/stage-deliveries?submenuId=ML3-SL3");
            }
        }
        else if(isset($s2art_params['s2art_corrector_permdisapprove'])) ///when corrector refused by editor permanently
        {
            if($s2art_params['sendtofo'] == 'yes')
            {
                ///////check the cycle count in participation tabel and increament//////////
                $cycleCount = $crtparticipate_obj->getCrtParticipationCycles($artId);
                $cycleCount = $cycleCount[0]['cycle']+1;
                /////udate status currector participation table with article id///////
                $data = array("cycle"=>$cycleCount);////////updating
                $query = "article_id= '".$artId."' and cycle=0";
                $crtparticipate_obj->updateCrtParticipation($data,$query);

                $this->CorrectorParticipationExpire($artId);
                ///////updating the participation table ///////
                $data = array("status"=>"under_study", "current_stage"=>"corrector");////////updating
                $query = "id= '".$partId."'";
                $participate_obj->updateParticipation($data,$query);

                if($s2art_params["anouncebyemail"] == 'yes')
                {
                    $this->sendMailToCorrectors($artId);
                }
            }
            else
            {
                /////udate status article table///////
                $data = array("send_to_fo"=>"no", "correction"=>"no");////////updating
                $query = "id= '".$artId."'";
                $article_obj->updateArticle($data,$query);
                ///////updating the participation table ///////
                $data = array("status"=>"under_study", "current_stage"=>"stage1");////////updating
                $query = "id= '".$partId."'";
                $participate_obj->updateParticipation($data,$query);
            }
            $data = array("status"=>"closed", "current_stage"=>"stage2", "refused_count"=>$refusedcountupdated);////////updating
            $query = "id= '".$crtpartId."'";
            $crtparticipate_obj->updateCrtParticipation($data,$query);
            /////udate status article process table///////
            $this->insertStageRecord($partId,$recentversion[0]["version"],'s2','closed');
            $recentversion= $artProcess_obj->getRecentVersion($partId);
            $data = array("marks"=>$s2art_params["marks"], "comments"=>$s2art_params["marks_comments"]);////////updating
            $query = "participate_id= '".$partId."' AND version='".$recentversion[0]['version']."'";
            $artProcess_obj->updateArticleProcess($data,$query);
            /////////////article history////////////////
            $actparams['artId'] = $artId;
            $actparams['stage'] = "stage2";
            $actparams['action'] = "refused definite";
            $this->articleHistory(26,$actparams);
            /////////////end of article history////////////////
            /// unlock the article///////////////
            $this->unlockonactionAction($artId);
            ///////////sending mail to contributor that document has been  rejected///
            //$Message = stripslashes($s2art_params["commentsCrtRefuse_".$crtpartId]) ;
            $Message = stripslashes($s2art_params["commentsCrtRefuse"]) ;
            $body=preg_replace('/\t/','',$Message);
            $mail = new Zend_Mail();
            $mail->addHeader('Reply-To', $this->configval['mail_from']);
            $mail->setBodyHtml($body)
                ->setFrom($this->configval['mail_from'])
                ->addTo($CorrectorMail)
                ->setSubject('Correction refus&eacute;e par Edit-place');
            if($mail->send())
            {
                $Object = 'Refus  definitif - Edit-place';
                //$Object = $s2art_params["commentsCrtRefuseObject"];
                $autoEmails->sendMailEpMailBox($correctorId,$Object,$body);  ///sending mail to EP account
                $this->_helper->FlashMessenger(utf8_decode('Article refused successfully.'));
                $this->_redirect("/proofread/stage-deliveries?submenuId=ML3-SL3");
            }
        }
    }
    else
    {
        $this->_view->now= strtotime('now');
	$this->render('proofread_stage2correction');
    }
}
    
    ////////////////article under stage 1 correction ////////////////////////
    public function clientRejectedArtsCorrectionAction()
    {
        $prevurl = getenv("HTTP_REFERER");
        $artId = $this->_request->getParam('articleId');
        $participate_obj = new EP_Participation_Participation();
        $article_obj = new EP_Delivery_Article();
        $options_obj = new EP_Delivery_Options();
        $delivery_obj = new EP_Delivery_Delivery();
        $artProcess_obj = new EP_Delivery_ArticleProcess();
        $autoEmails = new Ep_Message_AutoEmails();
        //////pop up displays/////
        $template_obj = new Ep_Message_Template();
        $res= $template_obj->refuseValidTemplates('null');
        $this->_view->refusevalidtemps = $res;
        $clientreject_params=$this->_request->getParams();
        if($artId!=NULL)
        {
            $details= $article_obj->getArticleDetails($artId);
            foreach($details as $key => $value)
            {
                $details[$key]['type']=$this->type_array[$details[$key]['type']];
                //$details[$key]['sign_type']=$this->signtype_array[$details[$key]['sign_type']];
                $details[$key]['signtype']=$details[$key]['sign_type'];
            }
            $aoId = $details[0]['deliveryId'];
            $this->_view->articledetails=$details;
            //display article process grid in s1 correction/////////
            $partsOfArt = $participate_obj->getAllClientRejectedArts($artId);
            $partId = $partsOfArt[0]['id'];
            $this->_view->partId = $partId;
            $versions_details = $artProcess_obj->getVersionDetails($partId);
            $this->_view->versions_details = $versions_details;
            ////getting the refuse count to change the button refuse to close//////
            $refused_count = $participate_obj->getRefusedCount($partId);
            $this->_view->refused_count = $refused_count[0]['refused_count'];
            $deldetails =  $delivery_obj->getDeliveryDetails($aoId);///to display in services box///


            $this->_view->deldetails=  $deldetails;
            $this->_view->options=$options_obj->getOptions($aoId);///to display in services box///
            $this->_view->res_seltdopts = $this->getDelSelectedOption($aoId); ///option selected for the delivery
        }
        if($clientreject_params["close"] == 'yes')
        {
            $artId = $clientreject_params['articleId'];
            $data = array("bo_closed_status"=>'closed');
            $query = "id= '".$artId."'";
           // $article_obj->updateArticle($data,$query);
            if($clientreject_params["closedrecreate"] == 'yes')
            {
                $this->_redirect("/ao/ao-create1?submenuId=ML2-SL3&ao=".$deldetails[0]['delId']);
            }
            else if($clientreject_params["closedpayment"] == 'yes')
            {
                $this->_redirect("/proofread/client-rejected-arts-correction?submenuId=ML3-SL5&articleId=".$artId);
            }
        }
        if(isset($clientreject_params["clientrejectart_approve"]))
        {
            $partId = $clientreject_params["participateId"] ;
            /////upload file/////////////////
            $recentversion= $artProcess_obj->getRecentVersion($partId);
            if($_FILES['art_doc_'.$partId]['tmp_name'] != '')
            {
                $tmpName = $_FILES['art_doc_'.$partId]['tmp_name'];
                $artName = $_FILES['art_doc_'.$partId]['name'];
                $ext = explode('.',$artName);
                $extension = $ext[1];
                $art_path = $artId."_".$this->adminLogin->userId."_".rand(10000, 99999).".".$extension;
                $artProcess_obj->participate_id=$partId ;
                $artProcess_obj->user_id=$this->adminLogin->userId;
                $artProcess_obj->stage='clientreject' ;
                $artProcess_obj->status='process' ;
                $artProcess_obj->article_path=$artId."/".$art_path ;
                $artProcess_obj->article_name=$artName ;
                $version = $recentversion[0]["version"]+1;
                $artProcess_obj->version=$version ;
                //////////////////uploading the document///////////////////////////////
                $server_path = "/home/sites/site7/web/FO/articles/";
                //$server_path = ROOT_PATH."FO/articles/";
                $articleDir = $server_path.$artId;
                echo $newfile = $articleDir."/".$art_path;
                if (move_uploaded_file($tmpName, $newfile))
                {
                    //Antiword obj to get content from uploaded article
                    $antiword_obj=new Ep_Antiword_Antiword($newfile);
                    $artProcess_obj->article_doc_content=$antiword_obj->getContent();
                    $artProcess_obj->article_words_count=$antiword_obj->count_words($artProcess_obj->article_doc_content);
                    $artProcess_obj->insert();
                }
            }
            ///udate status participation table for stage///////
            $data = array("current_stage"=>"client", "status"=>"under_study");////////updating
            $query = "id= '".$partId."'";
            $participate_obj->updateParticipation($data,$query);
            //sendidng the mail to client //
            $automail=new Ep_Message_AutoEmails();
            $Object="correction from bo user :".$details[0]['title'];
            $receiverId = $deldetails[0]['user_id'];
            $Message = $clientreject_params["clientmail"];
            $autoEmails->sendMailEpMailBox($receiverId,$Object,$Message);
             /// unlock the article///////////////
            $this->unlockonactionAction($artId);
            $this->_helper->FlashMessenger(utf8_decode('Article sent successfully'));
            //$this->_redirect("/proofread/stage-articles?submenuId=ML3-SL2&aoId=".$aoId);
            $this->_redirect("/proofread/client-rejected-arts?submenuId=ML3-SL5");
        }
        else
        {
            $this->render('proofread_clientrejectedartscorrection');
        }
    }

    ////////////// temporary and permanent dissaprove in all stages//////////////
    public function disapproveAction()
    {
        $disapprove_params=$this->_request->getParams();
        $partId = $disapprove_params["pop_partId"] ;
        $crtpartId = $disapprove_params["pop_crtpartId"] ;
        $artId = $disapprove_params["art_id"] ;
        $automail=new Ep_Message_AutoEmails();
        $delivery_obj = new Ep_Delivery_Delivery();
        $article_obj = new EP_Delivery_Article();
        $artProcess_obj = new EP_Delivery_ArticleProcess();
        $participate_obj = new EP_Participation_Participation();
        $crtparticipate_obj  = new Ep_Participation_CorrectorParticipation();
        $artReassign_obj = new EP_Delivery_ArticleReassignReasons();
        $firsttversion= $artProcess_obj->getFristVersion($partId);///to get writer id
        $recentversion= $artProcess_obj->getRecentVersion($partId);
        $lastestversion= $artProcess_obj->getRecentVersionId($partId);

        $ContributorMail = $firsttversion[0]['email'];
        $CorrectorMail = $lastestversion[0]['email'];
        $contributorId = $firsttversion[0]['user_id'];
        $correctorId = $lastestversion[0]['user_id'];
        $userId = $this->adminLogin->userId;
        $refused_count= $participate_obj->getRefusedCount($partId);
        $refusedcountupdated = ++$refused_count[0]['refused_count'];
        $delivery_details=$delivery_obj->getArtDeliveryDetails($artId);
        $aoId = $delivery_details[0]['id'];
        $recentDetials = $artProcess_obj->getVersionDetailsByVersion($partId, $recentversion[0]["version"]);

        ///to post selected refused valid tempsa in stage s1 correction////////
        if(isset($disapprove_params['pop_disapproves1']) || isset($disapprove_params['pop_disapproves1_permanent']))
        {
            $artReassign_obj->participate_id=$partId ;
            //$artReassign_obj->refused_by=$recentversion[0]['user_id'] ;///for corrector id//
            $artReassign_obj->refused_by=$userId ;///for corrector id//
            $artReassign_obj->contributor=$firsttversion[0]['user_id'] ;//for writer id///
            $artReassign_obj->stage="s1" ;
            //$artReassign_obj->reasons=$disapprove_params["comment_s1"] ;
            $artReassign_obj->reasons=$disapprove_params["hide_total1"] ;
            $artReassign_obj->edited_content=nl2br((stripslashes($disapprove_params["comment_s1"])));
            if(isset($disapprove_params['pop_disapproves1']))
            {    $artReassign_obj->type="temporaire";    }
            else if(isset($disapprove_params['pop_disapproves1_permanent']))
                $artReassign_obj->type="permanent";
            if($artReassign_obj->edited_content != '')
                $artReassign_obj->insert();
            $getlatestReason = $artReassign_obj->getLatestReason($partId);
            $reasons = explode(",",$getlatestReason[0]['reasons']);
            $template_obj = new Ep_Message_Template();
            foreach($reasons as $reason)
            {
                $getReason = $template_obj->getEmailTempDetails($reason);
                $tempname[] = $getReason[0]['title'];
            }
            if(isset($disapprove_params['pop_disapproves1']) && $disapprove_params['pop_disapproves1'] == 'yes')
            {
                $expires = $this->writerExpireResubmitTime($artId, $contributorId); //echo "hello2"; exit;
                $data = array("status"=>"disapproved", "current_stage"=>"contributor","article_submit_expires"=>$expires, "refused_count"=>$refusedcountupdated);////////updating
                $query = "id= '".$partId."'";
                $participate_obj->updateParticipation($data,$query);
                $footer = nl2br(stripslashes($disapprove_params["content_footer"]));

                /////udate status article process table///////
                $comments = '';
                $marks = '' ;
                $status = 'disapproved';

                /////udate status article process table///////
                $this->insertStageRecord($partId,$recentversion[0]["version"],'s1','disapproved');
                $recentversion= $artProcess_obj->getRecentVersion($partId);
                $data = array("marks"=>$disapprove_params["dismarks"], "reasons_marks"=>$disapprove_params["dismarkswithreason"], "comments"=>$disapprove_params["dismarks_comments"]);////////updating
                $query = "participate_id= '".$partId."' AND version='".$recentversion[0]['version']."'";
                $artProcess_obj->updateArticleProcess($data,$query);

                /////////////article history////////////////
                $actparams['artId'] = $artId;
                $actparams['stage'] = "stage1";
                $actparams['action'] = "refused";
                $actparams['article_download'] = $recentversion[0]['id'];
                $actparams['contributorId'] = $contributorId;
                $actparams['refusereason'] = $getlatestReason[0]['identifier'];
                $actparams['refusereasontitles'] = implode(",", $tempname);
                $this->articleHistory(45,$actparams); // previously it was 22.
                /////////////end of article history////////////////
            }
            else if(isset($disapprove_params['pop_disapproves1_permanent']) && $disapprove_params['pop_disapproves1_permanent'] == 'yes')
            {
                /////////////article history////////////////
                $partscount = $participate_obj->getNoOfParticipants($artId);
                $actparams['participation_count'] = $partscount[0]['partsCount'];
                $actparams['artId'] = $artId;
                $actparams['stage'] = "stage1";
                $actparams['action'] = "refused definite";
                $actparams['article_download'] = $recentversion[0]['id'];
                $actparams['contributorId'] = $contributorId;
                $actparams['refusereason'] = $getlatestReason[0]['identifier'];
                $actparams['refusereasontitles'] = implode(",", $tempname);
                $this->articleHistory(46,$actparams);// previously it was 23.
                /////////////end of article history////////////////
                if($disapprove_params['sendtofo'] == 'yes')
                {
                    $this->republish($artId);///updating cycle and to show in FO////
                    if($disapprove_params["anouncebyemail"] == 'yes')
                        $this->sendMailToContribs($artId);
                    $partdetails =  $participate_obj->getParticipateDetails($partId);
                    if($partdetails[0]['status'] == 'bid' || $partdetails[0]['status'] == 'disapproved')  {
                        $actparams['contributorId'] = $partdetails[0]['user_id'];
                        $actparams['action'] = "article not sent and republished";
                        $this->articleHistory(7,$actparams);
                    }
                    else{
                        $actparams['action'] = "republished";
                        $this->articleHistory(4,$actparams);
                    }
                }
                else
                {
                    /////udate status article table///////
                    $data = array("send_to_fo"=>"no");////////updating
                    $query = "id= '".$artId."'";
                    $article_obj->updateArticle($data,$query);
                }
                /////udate status participation table///////
                $data = array("status"=>"closed", "current_stage"=>"stage1");////////updating
                $query = "id= '".$partId."'";
                $participate_obj->updateParticipation($data,$query);
                $footer = nl2br(stripslashes($disapprove_params["content_footerPermanent"]));
                /////updating the jc0 user to black list as he is not fit///////
                $this->makejc0BlackList($contributorId);


                /////udate status article process table///////
                $this->insertStageRecord($partId,$recentversion[0]["version"],'s1','closed');
                $recentversion= $artProcess_obj->getRecentVersion($partId);
                $data = array("marks"=>$disapprove_params["dismarks"], "reasons_marks"=>$disapprove_params["dismarkswithreason"], "comments"=>$disapprove_params["dismarks_comments"]);////////updating
                $query = "participate_id= '".$partId."' AND version='".$recentversion[0]['version']."'";
                $artProcess_obj->updateArticleProcess($data,$query);

                ////updating the participation with recent version marks in articl process//
                $data = array("marks"=>$disapprove_params["marks"]);////////updating
                $query = "id= '".$partId."'";
                $participate_obj->updateParticipation($data,$query);

            }

            $this->unlockonactionAction($artId);

            ///////////sending mail to contributor that document has been  rejected///
            $object = 'Your article : <b>'.$delivery_details[0]['articleName'].'</b> in Edit-place';
            $body = nl2br(stripslashes($disapprove_params["content_head"]))."<br>".nl2br(stripslashes($disapprove_params["comment_s1"]))."<br><br>".$footer;
            $message=preg_replace('/\t/','',$body);
            //critsendMail($this->configval['mail_from'], $ContributorMail, $object, $message);
            ///////////sending mail to contributor that document has been  rejected///
           /* $mail = new Zend_Mail();
            $mail->addHeader('Reply-To', $this->configval['mail_from']);
            $mail->setBodyHtml($message)
                ->setFrom($this->configval['mail_from'])
                ->addTo($ContributorMail)
                ->setSubject($object);*/
            ///sending mail to ep mail box////
            $automail->sendMailEpMailBox($contributorId,$object,$message);  ///sending mail to EP account
            /// unlock the article///////////////
            $this->unlockonactionAction($artId);
            $this->resetDeliveredDetail($artId);//call the function to reset the delivered details of article
            $this->_helper->FlashMessenger(utf8_decode('Article refused successfully.'));
            //$this->_redirect("/proofread/stage-articles?submenuId=ML3-SL2&aoId=".$aoId);
            $this->_redirect("/proofread/stage-deliveries?submenuId=ML3-SL2");

        }
        else if(isset($disapprove_params['pop_disapproves2'])  || isset($disapprove_params['pop_disapproves2_permanent']))
        {   
            $artReassign_obj->participate_id=$partId ;
            //$artReassign_obj->refused_by=$recentversion[0]['user_id'] ;///for corrector id//
            $artReassign_obj->refused_by=$userId ;///for corrector id//
            $artReassign_obj->contributor=$firsttversion[0]['user_id'] ;//for writer id///
            $artReassign_obj->stage="s2";
            $artReassign_obj->reasons=$disapprove_params["hide_total2"] ;
            $artReassign_obj->edited_content=nl2br((stripslashes($disapprove_params["comment_s2"])));
            if(isset($disapprove_params['pop_disapproves2']))
                $artReassign_obj->type="temporaire";
            else if(isset($disapprove_params['pop_disapproves2_permanent']))
                $artReassign_obj->type="permanent";
            $artReassign_obj->insert();
			
            $getlatestReason = $artReassign_obj->getLatestReason($partId);
            $reasons = explode(",",$getlatestReason[0]['reasons']);
            $template_obj = new Ep_Message_Template(); //print_r($reasons); exit;
           // print_r($reasons);
            if(!empty($reasons)){  
				foreach($reasons as $reason)
				{  
					$getReason = $template_obj->getEmailTempDetails($reason);
					$tempname[] = $getReason[0]['title'];
				}
			}	
            if(isset($disapprove_params['pop_disapproves2']) && $disapprove_params['pop_disapproves2'] == 'yes')
            {   
                $expires = $this->writerExpireResubmitTime($artId, $contributorId);
                $data = array("status"=>"disapproved", "current_stage"=>"contributor","article_submit_expires"=>$expires, "refused_count"=>$refusedcountupdated);////////updating
                $query = "id= '".$partId."'";
                $participate_obj->updateParticipation($data,$query);
					
                $footer = nl2br(stripslashes($disapprove_params["content_footer"]));
                $data = array("status"=>"bid");////////corrector participation table updating
                $query = "id= '".$crtpartId."'";                     //
                $crtparticipate_obj->updateCrtParticipation($data,$query);
                $footer = nl2br(stripslashes($disapprove_params["content_footer"]));
                /////udate status article process table///////
                $comments = '';
                $marks = '' ;
                $status = 'disapproved';

                /////udate status article process table///////
                $this->insertStageRecord($partId,$recentversion[0]["version"],'s2','disapproved');
                $recentversion= $artProcess_obj->getRecentVersion($partId);
                $data = array("marks"=>$disapprove_params["dismarks"], "reasons_marks"=>$disapprove_params["dismarkswithreason"], "comments"=>$disapprove_params["dismarks_comments"]);////////updating
                $query = "participate_id= '".$partId."' AND version='".$recentversion[0]['version']."'";
                $artProcess_obj->updateArticleProcess($data,$query);

                /////////////article history////////////////
                $actparams['artId'] = $artId;
                $actparams['stage'] = "stage2";
                $actparams['action'] = "refused";
                $actparams['article_download'] = $recentversion[0]['id'];
                 $actparams['contributorId'] = $contributorId;
                $actparams['refusereason'] = $getlatestReason[0]['identifier'];
                $actparams['refusereasontitles'] = implode(",", $tempname);
                $this->articleHistory(45,$actparams); //previously 27
                /////////////end of article history////////////////
            }
            else if(isset($disapprove_params['pop_disapproves2_permanent']) && $disapprove_params['pop_disapproves2_permanent'] == 'yes')
            {
                /////////////article history////////////////
                $partscount = $participate_obj->getNoOfParticipants($artId);
                $actparams['participation_count'] = $partscount[0]['partsCount'];
                $actparams['artId'] = $artId;
                $actparams['stage'] = "stage2";
                $actparams['article_download'] = $recentversion[0]['id'];
                $actparams['contributorId'] = $contributorId;
                $actparams['action'] = "refused definite";
                $actparams['refusereason'] = $getlatestReason[0]['identifier'];
                $actparams['refusereasontitles'] = implode(",", $tempname);
                $this->articleHistory(46,$actparams); //previously 26
                /////////////end of article history////////////////
                if($disapprove_params['sendtofo'] == 'yes')
                {
                    $this->republish($artId);///updating cycle and to show in FO////
                    if($disapprove_params["anouncebyemail"] == 'yes')
                        $this->sendMailToContribs($artId);
                    $partdetails =  $participate_obj->getParticipateDetails($partId);
                    if($partdetails[0]['status'] == 'bid' || $partdetails[0]['status'] == 'disapproved')  {
                        $actparams['contributorId'] = $partdetails[0]['user_id'];
                        $actparams['action'] = "article not sent and republished";
                        $this->articleHistory(7,$actparams);
                    }
                    else{
                        $actparams['action'] = "republished";
                        $this->articleHistory(4,$actparams);
                    }
                }
                else
                {
                    /////udate status article table///////
                    $data = array("send_to_fo"=>"no");////////updating
                    $query = "id= '".$artId."'";
                    $article_obj->updateArticle($data,$query);
                }
                $footer = nl2br(stripslashes($disapprove_params["content_footerPermanent"]));
                /////udate status participation table///////
                $data = array("status"=>"closed", "current_stage"=>"stage2");////////updating
                $query = "id= '".$partId."'";
                $participate_obj->updateParticipation($data,$query);
                $data = array("status"=>"bid");////////corrector participation table updating
                $query = "id= '".$crtpartId."'";                     //
                $crtparticipate_obj->updateCrtParticipation($data,$query);
                /////updating the jc0 user to black list as he is not fit///////
                $this->makejc0BlackList($contributorId);



                /////udate status article process table///////
                $this->insertStageRecord($partId,$recentversion[0]["version"],'s2','closed');
                $recentversion= $artProcess_obj->getRecentVersion($partId);
                $data = array("marks"=>$disapprove_params["dismarks"], "reasons_marks"=>$disapprove_params["dismarkswithreason"], "comments"=>$disapprove_params["dismarks_comments"]);////////updating
                $query = "participate_id= '".$partId."' AND version='".$recentversion[0]['version']."'";
                $artProcess_obj->updateArticleProcess($data,$query);

                ////updating the participation with recent version marks in articl process//
                $data = array("marks"=>$disapprove_params["marks"]);////////updating
                $query = "id= '".$partId."'";
                $participate_obj->updateParticipation($data,$query);
            }


            $this->unlockonactionAction($artId);

            ///////////sending mail to contributor that document has been  rejected///
            $object = "Your article : ".$delivery_details[0]['articleName']." in Edit-place";
            $body = stripslashes(nl2br($disapprove_params["content_head"]))."<br>".nl2br(stripslashes($disapprove_params["comment_s2"]))."<br><br>".$footer;
            $message=preg_replace('/\t/','',$body);
            //$automail->critsendMail($this->mail_from, $ContributorMail, $object, $message);
             ////////sending the mail to ep mail box//////
            $automail->sendMailEpMailBox($contributorId,$object,$message);  ///sending mail to EP account
            //////sending mail to conrrector with reasons////
            if($disapprove_params['commentstocorrector'] == "yes")
                $comments = nl2br(stripslashes($disapprove_params["comment_s2"]));
            else
                $comments = '';
            $message =  "<p>Cher Correcteur, ch&egrave;re  Correcteur,<br><br>
                                    The Article Refused by Edit-place Team&nbsp;!<br>
                                    ".$comments."
                                    Cordialement,<br>
                                    <br>
                                    Toute l'&eacute;quipe d&rsquo;Edit-place</p>";
            //$automail->critsendMail($this->mail_from, $CorrectorMail, $object, $message);
            /// unlock the article///////////////
            $this->unlockonactionAction($artId);
            $this->resetDeliveredDetail($artId);//call the function to reset the delivered details of article
            $this->_helper->FlashMessenger(utf8_decode('Article refused successfully.'));
            //$this->_redirect("/proofread/stage-articles?submenuId=ML3-SL3&aoId=".$aoId);
            $this->_redirect("/proofread/stage-deliveries?submenuId=ML3-SL3");
        }
    }

   ////////function for lock system//////////
   public function locksystemAction()
   {
        $artId = $this->_request->getParam('artId');
        $mode = $this->_request->getParam('mode');
        $stage = $this->_request->getParam('stage');
        $submenuId = $this->_request->getParam('submenuId');
       $participate_obj = new EP_Participation_Participation();

        $lock_obj = new Ep_User_LockSystem();
        $userId=$this->adminLogin->userId ;
        if($mode == 'lock')
        {
            $checkLock = $lock_obj->lockExist($artId);
            if($checkLock == 'NO')
            {
               $lock_obj->article_id=$artId ;
               $lock_obj->user_id=$this->adminLogin->userId ;
               $lock_obj->lock_status="yes" ;
               $lock_obj->insert();
            }
            else
            {
               $user_id=$this->adminLogin->userId ;
               $data = array("lock_status"=>"yes", "user_id"=>$user_id);////////updating
               $query = "article_id= '".$artId."'";
               $lock_obj->updateLockSystem($data,$query);
            }
        }
        else
        {
            $data = array("lock_status"=>"no");////////updating
            $query = "article_id= '".$artId."' AND user_id='".$userId."'";
            $lock_obj->updateLockSystem($data,$query);
            $data = array("status"=>"under_study");////////updating
            $query = "article_id= '".$artId."' AND status='on_hold'";
            $participate_obj->updateParticipation($data,$query);
        }
    }
    ////////get created user of article/////////
   public function missionownerAction()
   {
        $artId = $this->_request->getParam('artId');
        $sendmail = $this->_request->getParam('sendmail');
        $article_obj = new EP_Delivery_Article();
        $automail_obj=new Ep_Message_AutoEmails();
        $userId=$this->adminLogin->userId ;
        $userName=$this->adminLogin->loginName ;
        $articles = $article_obj->getArticleDetails($artId);
        $artOwner = $articles[0]['created_user'];
        if($sendmail == 'yes') //sending the mail to personal account to mission owner
        {
            $superadminId = "110823103540627";
            $this->sendMailToMissionOwner($artOwner, $userName);//
            $this->sendMailToMissionOwner($superadminId, $userName);//
            exit;
        }
        else if($artOwner == $this->adminLogin->userId || $this->adminLogin->userId == '110823103540627')
           echo "yes,".$articles[0]['first_name'];
        else
           echo "no,".$articles[0]['first_name'];

    }
    public function sendMailToMissionOwner($userId, $userName)
    {
        $user_obj = new Ep_User_User();
        $automail=new Ep_Message_AutoEmails();
        $userdetails = $user_obj->getAllUsersDetails($userId);
        $emailfrom = "support@edit-place.com";
        $emailto = $userdetails[0]['email'];
        $subject="Your mission is locked by ".$userName."";
        $msg="<p>Bonjour,<br/><br/>
                                Your mission is locked by <b>".$userName."</b>
                                <br/><br/>
                                Cordialement,<br/><br/>
                                Toute l'&eacute;quipe d'Edit-place</p>";
       // critsendMail($emailfrom, $emailto, $subject, $msg);
        $mail = new Zend_Mail();
        $mail->addHeader('Reply-To',$emailfrom);
        $mail->setBodyHtml($msg)
            ->setFrom($emailfrom)
            ->addTo($emailto)
            ->setSubject($subject);

        if($mail->send())
            return true;

    }
    ///////////writing the comments storing to database while s1 and s2 correction
    public function getwritecommentsAction()
    {
        $comment_params=$this->_request->getParams();
        $reason_obj=new EP_Delivery_ArticleReassignReasons();
        $usercomment_obj=new Ep_Message_UserComments();
        $user_obj = new Ep_User_User();
        ///////if the comment from group profile popup comments///////////911010042353261
        if($comment_params['commented_on_userid'] != '')
        {
             $usercomment_obj=new Ep_Message_UserComments();
             $usercomment_obj->commented_by=$comment_params['commented_by_userid'] ;
             $usercomment_obj->commented_on=$comment_params['commented_on_userid'] ;
             $usercomment_obj->comments=$comment_params['comment'] ;
             $usercomment_obj->created_at=date("Y-m-d H:i:s", time());
             $usercomment_obj->user_type='bouser';
             $usercomment_obj->insert();
			 $comment_details=$usercomment_obj->getBoUsersComments($comment_params['commented_on_userid']);
			  foreach($comment_details as $key => $value)
				{
					echo "<div class='alignleft'>".$comment_details[$key]['first_name']."&nbsp".$comment_details[$key]['last_name']." <label class='label label-info'>at ".$comment_details[$key]['created_at']."</label></div>
					<br><table class='table table-bordered'><tr><td class='alert alert-success'>".$comment_details[$key]['comments']."</td></tr></table>";
				}
        }
        else
        {
             ///////////////if the comment from s1 or s2 correction pages//////////
             $this->_view->articleId = $comment_params['artId'];
             $this->_view->partId = $comment_params['partId'];
             $this->_view->userId = $this->adminLogin->userId;
             // echo $this->request->action;exit;
             $reason_obj->participate_id=$comment_params['partId'] ;
             $reason_obj->refused_by=$comment_params['userId'] ;
             //$reason_obj->contributor='s1' ;
             $reason_obj->stage=$comment_params['stage'] ;
             $reason_obj->edited_content=$comment_params['comment'] ;
             $reason_obj->type='comment' ;
             $reason_obj->created_at=date("Y-m-d H:i:s", time());
             $reason_obj->insert();
			$comment_details=$reason_obj->getCorrectorComments($comment_params['partId']);
			 foreach($comment_details as $key => $value)
				{
					echo "<div class='alignleft'>".$comment_details[$key]['first_name']."&nbsp".$comment_details[$key]['last_name']." <label class='label label-info'>at ".$comment_details[$key]['created_at']."</label></div>
					<br><table class='table table-bordered'><tr><td class='alert alert-success'>".$comment_details[$key]['edited_content']."</td></tr></table>";
				}
        }

       
       
       exit;

    }
    ///////getting  and showing comments of BO office user while selecting profiles in groupporfilepopup//////////////
    public function getallbousercommentsAction()
    {
        $groupprofile_params=$this->_request->getParams();
        $usercomment_obj=new Ep_Message_UserComments();
        $user_obj = new Ep_User_User();
        $this->_view->articleId = $groupprofile_params['artid'];
        $this->_view->commented_on_userid = $groupprofile_params['userId'];///commented on user id
        $this->_view->commented_by_userid = $this->adminLogin->userId;
        if(isset($groupprofile_params['userId']))
        {
            $comment_details=$usercomment_obj->getBoUsersComments($groupprofile_params['userId']);
            $this->_view->writerName=$user_obj->getAllUsersDetails($groupprofile_params['userId']);
            $this->_view->comments= $comment_details;
            $this->_view->bouserscomments= 'yes';
        }
        $this->_view->render("proofread_commentpop");
    }
    ///////giving the provision for enter marks and comments by proofreader//////////////
    public function markscommentsAction()
    {
        $groupprofile_params=$this->_request->getParams();
        $artProcess_obj = new EP_Delivery_ArticleProcess();
        $this->_view->articleId = $groupprofile_params['artid'];
        $this->_view->partId = $groupprofile_params['partid'];
			
        if($groupprofile_params['save_marks_comments'] == 'yes')
        {
            $recentversion= $artProcess_obj->getRecentVersion($groupprofile_params['partId']);
            ////udate status article process table///////
            $data = array("marks"=>$groupprofile_params['marks'],"comments"=>$groupprofile_params['comments']);
             $query = "participate_id= '".$groupprofile_params['partId']."' AND version='".$recentversion[0]['version']."'";
            $artProcess_obj->updateArticleProcess($data,$query); exit;
        }
        $this->_view->render("proofread_markscomments_popup");
    }
    /////////////////////display pop up with the key word density///////////////////
    public function showkeyworddensityAction()
    {
        $contrib_obj=new EP_User_Contributor();
        $participate_obj=new EP_Participation_Participation();
        $artprocess_obj=new EP_Delivery_ArticleProcess();
        $correctionParams=$this->_request->getParams();
        $result = $artprocess_obj->getArticlePath($correctionParams['artprocessId']);
        $article_title = $participate_obj->getParticipateDetails($result[0]['participate_id']);
        $version = $result[0]['version'];
        $article_name = $result[0]['article_name'];
        $contents = $result[0]['article_doc_content'];
        $str = str_replace("\n"," ",$contents);
        $str = strip_tags($str);
        $str = utf8_decode($str);
        $str = strtolower($str);
        $str = str_replace("«", "",$str);
        $listtoomit = array('.',',','?','!');

        $words = explode(" ",$str);
        $words = array_filter($words, 'strlen');
        //$words = str_word_count($str,1);$result[0]['article_words_count']
        $word_count = array_count_values($words);
        array_multisort($word_count,SORT_DESC);

        $filename = "/home/sites/site8/web/BO/documents/stopwords.txt";
        //chmod($filename,777);
        $filehandle = fopen($filename, "r");
        if(filesize($filename)>1)
        {
            $spotwordcontents = fread($filehandle, filesize($filename));
            //$spotwordcontents = utf8_decode($spotwordcontents);
            fclose($filehandle);
            $file_array=explode(" ",trim($spotwordcontents));
            $op=str_replace(' ',',',preg_replace('/\s+ /', ' ',implode(" ",array_unique($file_array)))) ;
            $op_new=str_replace(',',' ',$op);
            $op_newlower = strtolower($op_new);
            $op_new=explode(' ',$op_newlower);
        }
        echo "
        <table  id='grptabledetails' class='table btn-gebo' >
        <tr><td colspan ='2'><b>Keyword Density for '".$article_title[0]['title']."' Article</b></td></tr>
        <tr><td><b>Article File Name : </b>".$article_name."</td>
            <td><b>Version : </b>".$version."</td></tr>
         <tr><td><b>Words in Article : </b>".count($words)."</td>
            <td><b>Density : </b><input type=text name='denval' id='denval' value='0-100%' onclick='clearDensity();'/>&nbsp;&nbsp;
            <button type=button name='dengo' class='btn btn-info' id='dengo' onclick='return getDensity(".$correctionParams['artprocessId'].",1);'>Go</button></td></tr>
         </table>
         <div style='max-height:250px;overflow:auto;margin-left:270px;width:700px'>
        <table class='table table-bordered pull-center'>
        <tr><th>Sl no.</th><th>WORD</th><th> COUNT</th><th> DENSITY</th></tr>";
        $slno = 1;
        foreach ($word_count as $key=>$val)
        {
            $density = ($val/count($words))*100;
            if ($density > $correctionParams['density'])
            {
                $flag=0;
                foreach($op_new as $match1)
                {
                    $match1.":".$key."<br/>";
                    if(utf8_encode($match1)==$key)
                    {
                        //echo utf8_encode($match1).":".$key."<br/>";
                    }
                    if(utf8_encode($match1)!==str_replace(",","",$key))
                    {
                        $flag+=1;
                    }
                    if($flag==count($op_new))
                    {
                        for($q=0;$q<3;$q++)
                        {
                            $key = str_replace($listtoomit[$q],'',$key);
                        }
                        $kdres_op.= "<tr class=highlight><td>$slno</td><td>$key</td><td>  $val</td><td> ".number_format($density,2)."%</td></tr>";
                        $shop[]= array($key, $val , number_format($density,2));
                        $slno++;

                    }
                }
            }
        }
        $kdres_op.="</table></div></table></div>";
        echo $kdres_op;

    }
    ////////////////// Plagiarisms///////////////////////
    public function contentplagiarismAction()
    {
        $art_obj=new EP_Delivery_ArticleProcess();
        $ArtPro_detail=$art_obj->articledetail($_REQUEST['artprocessId']);
        $this->_view->ArtPro_detail=$ArtPro_detail;
        $percent_value="20";
        $words_value="20";

        if(isset($_REQUEST['cutoff']))
            $percent_value=$_REQUEST['cutoff'];

        if(isset($_REQUEST['words']))
            $words_value=$_REQUEST['words'];

        $ArtPlag_detail=$art_obj->plagiarismlist($_REQUEST['artprocessId']);

        $plag_array=array();
        $pg=0;
        $Apcount=count($ArtPlag_detail);
        if($Apcount>0)
        {
            $text1=trim($ArtPro_detail[0]['article_doc_content']);
            $text1=str_replace("<br />"," ",$text1);
            //$text1=str_replace("’","'",$text1);

            $input_array=$this->splitintowords($text1,$words_value);

            for($p=0;$p<$Apcount;$p++)
            {
                $text2=trim($ArtPlag_detail[$p]['article_doc_content']);
                $text2=str_replace("<br />"," ",$text2);

                $match_array=array();

                for($i=0;$i<count($input_array);$i++)
                {
                    if(stripos($text2, $input_array[$i])!== false)
                        $match_array[]=$input_array[$i];
                }

                $percent=(count($match_array)/count($input_array)) * 100;
                //echo count($match_array).'-'.count($input_array);print_r($input_array);
                if($percent>=$percent_value)
                {
                    $plag_array[$pg]['article_name']=$ArtPlag_detail[$p]['article_name'];
                    $plag_array[$pg]['title']=$ArtPlag_detail[$p]['title'];
                    $plag_array[$pg]['article_doc_content']=$ArtPlag_detail[$p]['article_doc_content'];
                    $plag_array[$pg]['first_name']=$ArtPlag_detail[$p]['first_name'];
                    $plag_array[$pg]['last_name']=$ArtPlag_detail[$p]['last_name'];
                    $plag_array[$pg]['status']=$ArtPlag_detail[$p]['status'];
                    $plag_array[$pg]['percent']=round($percent,2);
                    $plag_array[$pg]['common_content']=implode(" ",$match_array);
                    $pg++;
                    //print_r($match_array);
                }
            }
            $this->_view->plag_array=$plag_array;
        }
        $this->_view->percent_value=$percent_value;
        $this->_view->words_value=$words_value;
        $this->_view->apid=$_REQUEST['artprocessId'];
        $this->_view->render("proofread_plagiarism");
    }
    // PHP function to split text block into n number of words
    public function splitintowords($text,$n=3)
    {
        $text=str_replace(array("  ","\n","\t","<br/>")," ",$text);

        $words=explode(" ",$text);
        //$count = (int)ceil(count($words = str_word_count($text, 1)) );
        $count = count($words);
        $cnt=$count;
        $i=0;
        unset($word_array);
        $word_array=array();

        while($cnt>0)
        {
            $start=$i+0;
            $end=$n;
            $word_array[]=implode(' ', array_slice($words, $start, $end));

            $cnt=$cnt-$n;
            $i=$i+$n;
        }

        return $word_array;
    }
   ///make jc0 user as black listed when the article disappoved in so, s1, s2 corrections////////
   public function makejc0BlackList($contributorId)
   {
       $user_obj = new Ep_User_User();
       $userDetails =  $user_obj->getAllUsersDetails($contributorId);
       if($userDetails[0]['profile_type'] == "sub-junior") ///if user is jc0///
       {
           $data = array("blackstatus"=>"yes");////////updating
           $query = "identifier= '".$contributorId."'";
           $user_obj->updateUser($data,$query);
       }
   }
    ///xml data grid presention in s0 correction page/////////
    public function XMLParserS0correction($file, $fileorgname)
    {
        $xml = simplexml_load_file($file);
        //echo "<pre>";print_r($xml->url);echo "</pre>";
        // $data='<b>Title : </b>'.$xml->article1->Title.'</br>';
        $name='<b>Title : </b><span style="color:green;font-weight: bold;font-size: large;">'.$fileorgname.'</span></br>';
        $j=0;
        if($xml->article->result->url != '')
        {
            foreach($xml->article->result->url as $URL)
            {
                $j++;  //calculating the no of row in xml object mean count($xml)
            }
            for($i=0; $i<$j; $i++)
            {
                $data[$i]['result1']=($i+1);
                $data[$i]['url1']=$xml->article->result->url[$i]->object;
                $data[$i]['content1']=$xml->article->result->content[$i]->object;
                $data[$i]['percentage1']=$xml->article->result->percentage[$i]->object;
            }
        }
        return $data;
        exit;
    }
    public function ContirbutorSuccessRate()
    {
        $participate_obj=new EP_Participation_Participation();
        $user_obj = new Ep_User_User();
        $ajaxParams=$this->_request->getParams();
        $userdetails = $user_obj->getAllUsersDetails($ajaxParams['userId']);
        $userpublishdetails = $participate_obj->getContributorSuccessRate($ajaxParams['userId']);
        $this->_view->contributordetials = $userpublishdetails;
    }
    public function articleHistoryAction()
    {
        $arthistory_obj = new Ep_Delivery_ArticleHistory();
        $article_obj = new EP_Delivery_Article();
        $arthistoryParams=$this->_request->getParams();
        $artId = $arthistoryParams['articleId'];
        $details= $article_obj->getArticleDetails($artId);

        $this->_view->articledetails=$details;

        $res= $arthistory_obj->articleHistoryDetails($artId);
        /*foreach ($res as $key1 => $value1) {
           // $res[$key1]['difftime'] = date_diff($res[$key1]['user_in'],$res[$key1]['user_out'], '');
            $time1 = strtotime($res[$key1]['user_in']);
            $time2 = strtotime($res[$key1]['user_out']);

          echo   $res[$key1]['difftime'] = $time2-$time1;
        }*/
        if($res!="NO")
        {
            $this->_view->paginator = $res;
        }
        $this->_view->render("proofread_articlehistory");
    }
    /////////displays the premium articles which rejected by client after apporved in stage 2//////////////
    public function clientRejectedArtsAction()
    {
        $article_obj = new EP_Delivery_Article();
        $artprocess_obj = new EP_Delivery_ArticleProcess();
        $res= $article_obj->clientRejectedArts();
        if($res!="NO")
        {
            foreach($res as $res_key => $res_value)
            {
               $date=$artprocess_obj->getRecentVersion($res[$res_key]['partId']);
                $res[$res_key]['article_sent_at']  = $date[0]['article_sent_at'];
            }
            $this->_view->paginator = $res;
        }
        $this->_view->render("proofread_clientrejectedarts");
    }
	
	//get all published articles archived///
	public function validrefusedartsAction()
	{
        //print_r($res[$res_key]['contribcount']);
		$this->_view->render("proofread_validrefusedarts");
	}
    public function loadvalidrefusedartsAction()
    {
        $article_obj = new EP_Delivery_Article();
        $lock_obj = new Ep_User_LockSystem();
        $participate_obj = new EP_Participation_Participation();
        $artProcess_obj = new EP_Delivery_ArticleProcess();

        $aColumns = array('artId','title','deliveryTitle','full_name','updated_at','details','download');
        /* * Paging	 */
        $sLimit = "";
        if ( isset( $_GET['iDisplayStart'] ) && $_GET['iDisplayLength'] != '-1' )
        {
            $sLimit = "LIMIT ".intval( $_GET['iDisplayStart'] ).", ".
                intval( $_GET['iDisplayLength'] );
        }
        /* 	 * Ordering   	 */
        $sOrder = "";
        if ( isset( $_GET['iSortCol_0'] ) )
        {
            if($aColumns['details'] == 'details')
                break;
            if($aColumns['download'] == 'download')
                break;
            $sOrder = "ORDER BY  ";
            for ( $i=0 ; $i<intval( $_GET['iSortingCols'] ) ; $i++ )
            {
                if ( $_GET[ 'bSortable_'.intval($_GET['iSortCol_'.$i]) ] == "true" )
                {
                    $sOrder .= "`".$aColumns[ intval( $_GET['iSortCol_'.$i] ) ]."` ".
                        ($_GET['sSortDir_'.$i]==='asc' ? 'asc' : 'desc') .", ";
                }
            }

            $sOrder = substr_replace( $sOrder, "", -2 );
            if ( $sOrder == "ORDER BY" )
            {
                $sOrder = "";
            }
        }
        $sWhere = "";
        if ( isset($_GET['sSearch']) && $_GET['sSearch'] != "" )
        {
            $sWhere = " HAVING (";
            for ( $i=0 ; $i<count($aColumns) ; $i++ )
            {
                if($aColumns[$i] == 'details')
                    break;
                if($aColumns[$i] == 'download')
                    break;

                $keyword=addslashes($_GET['sSearch']);
                $keyword = preg_replace('/\s*$/','',$keyword);
                $keyword=preg_replace('/\(|\)/','',$keyword);
                $words=explode(" ",$keyword);
                if(count($words)>1)
                {
                    $sWhere.=$aColumns[$i]." like '%".utf8_decode($keyword)."%' OR ";
                    foreach($words as $key=>$word)
                    {
                        $word=trim($word);
                        if($word!='')
                        {
                            $sWhere .= "".$aColumns[$i]." LIKE '%".utf8_decode($word)."%' OR ";
                        }
                    }
                }
                else
                    $sWhere .= "".$aColumns[$i]." LIKE '%".utf8_decode($keyword)."%' OR ";
            }
            $sWhere = substr_replace( $sWhere, "", -3 );
            $sWhere .= ')';
        }
        /* Individual column filtering */
        for ( $i=0 ; $i<count($aColumns) ; $i++ )
        {
            if ( isset($_GET['bSearchable_'.$i]) && $_GET['bSearchable_'.$i] == "true" && $_GET['sSearch_'.$i] != '' )
            {
                if ( $sWhere == "" )
                {
                    $sWhere = " WHERE  ";
                }
                else
                {
                    $sWhere .= " AND  ";
                }
                $sWhere .= "`".$aColumns[$i]."` LIKE '%".$_GET['sSearch_'.$i]."%' ";
            }
        }
        $rResult= $article_obj->validRefusedArts($sWhere, $sOrder, $sLimit);
        $rResultcount = count($rResult);
        /*if($rResult!="NO")
        {
            foreach($rResult as $res_key => $res_value)
            {
                $rResult[$res_key]['downloadpath']=$artProcess_obj->getRecentVersionId($rResult[$res_key]['partId']);
            }
           // $this->_view->publishedmissions = $rResult;
        }
        echo $rResult[0]['downloadpath']; print_r($rResult); exit;*/
        /////total count
        $sLimit = "";
        $countarts  = $article_obj->validRefusedArts($sWhere, $sOrder, $sLimit);
        $iTotal = count($countarts);

        $output = array(
            "sEcho" => intval($_GET['sEcho']),
            "iTotalRecords" => $iTotal,
            "iTotalDisplayRecords" => $iTotal,
            "aaData" => array()
        );
        $count = 1;
        if($rResult != 'NO') //if non relavent data is given in search column//
        {
            for( $i=0 ; $i<$rResultcount; $i++)
            {
                $row = array();
                for ( $j=0 ; $j<count($aColumns) ; $j++ )
                {
                    if($j == 0)
                        $row[] = $count;
                    else
                    {
                        if($aColumns[$j] == 'title')
                            $row[] = utf8_encode($rResult[$i]['title']);
                        elseif($aColumns[$j] == 'deliveryTitle')
                            $row[] = '<a href="/ongoing/ao-details?submenuId=ML2-SL4&client_id='.$rResult[$i]['owner'].'&ao_id='.$rResult[$i]['delivery_id'].'">'.utf8_encode($rResult[$i]['deliveryTitle']).'</a>';
                        elseif($aColumns[$j] == 'updated_at')
                            $row[] = date("d-m-Y H:i", strtotime($rResult[$i]['updated_at']));
                        elseif($aColumns[$j] == 'full_name')
                            $row[] = '<a href="/user/contributor-edit?submenuId=ML2-SL7&tab=viewcontrib&userId='.$rResult[$i]['contributor'].'">'.utf8_encode($rResult[$i]['full_name']).'</a>';
                        elseif($aColumns[$j] == 'details')
                        {
                            $row[] = '<a href="/proofread/archivecorrection?submenuId=ML3-SL9&articleId='.$rResult[$i]['artId'].'" class="hint--left hint--info" data-hint="Archive Details"><i class="splashy-information"></i></a>';
                        }
                        elseif($aColumns[$j] == 'download'){
                            if($rResult[$i]['artprocessId'] == '')
                                $row[] = "-";
                            else
                               $row[] = '<a class="hint--left hint--info" data-hint="Download Article" href="/proofread/downloadarticle?path='.$rResult[$i]['artprocessId'].'" ><i class="splashy-document_small_download"></i></a>';
                        }
                        else
                            $row[] = $rResult[$i][$aColumns[$j]];
                    }
                }
                $output['aaData'][] = $row;
                $count++;
            }
        }
        // print_r($output);  exit;
        echo json_encode( $output );

    }
	public function archivecorrectionAction()
	{
        $article_obj = new EP_Delivery_Article();
        $options_obj = new EP_Delivery_Options();
        $del_obj = new EP_Delivery_Delivery();
        $deloptions_obj = new EP_Delivery_DeliveryOptions();
        $template_obj = new Ep_Message_Template();
		$participate_obj = new EP_Participation_Participation();
		
		$artId = $this->_request->getParam('articleId');
		$details= $article_obj->getArticleDetails($artId);

		$type_array=$this->_arrayDb->loadArrayv2("EP_ARTICLE_TYPE", $this->_lang);
		$sign_type_array=$this->_arrayDb->loadArrayv2("EP_ARTICLE_SIGN_TYPE", $this->_lang);
			$details[0]['type']=$type_array[$details[0]['type']];
			$details[0]['sign_type']=$sign_type_array[$details[0]['sign_type']];
		$this->_view->articledetails=$details;
       
        //////pop up displays/////
        $res= $template_obj->refuseValidTemplates('null');
        $this->_view->refusevalidtemps = $res;
        /////////////////////////////////////
        $deldetails =  $del_obj->getDeliveryDetails($details[0]['deliveryId']);///to display in services box///
        $getparentoption =  $del_obj->getParentOption($details[0]['deliveryId']);///to display in services box///

        $this->_view->deldetails=  $deldetails;
        $this->_view->options=$options_obj->getOptions($details[0]['deliveryId']);///to display in services box///
        $selectedoptionsres=$deloptions_obj->getDelOptions($details[0]['deliveryId']);///to display in services box///
        if($selectedoptionsres!='NO')
        {
            for($i=0;$i<count($selectedoptionsres);$i++)
            {
               $res_seltdopts[$i]=$selectedoptionsres[$i]['option_id'];
            }
            $parent = $getparentoption[0]['premium_option'];
            if(empty($parent))
                 $this->_view->res_seltdopts=$res_seltdopts;
            else
            {
                 $parent=$parent;
                array_unshift($res_seltdopts,$parent);
                $this->_view->res_seltdopts=$res_seltdopts;
            }
        }
        else
        {
            $res_seltdopts = array();
            if(empty($parent))

                $this->_view->res_seltdopts=$res_seltdopts;
            else
            {
                $parent=$parent;
                array_unshift($res_seltdopts,$parent);
                $this->_view->res_seltdopts=$res_seltdopts;
            }
        }
        $archive_params=$this->_request->getParams();
		$partsOfArt= $participate_obj->getAllParticipationsArchives($archive_params['articleId']);
		$this->_view->partsOfArt=$partsOfArt;
		
        if(isset($archive_params["archive_retour"]))
           $this->_redirect("/proofread/validrefusedarts?submenuId=ML3-SL9");
        else
            $this->render('proofread_archivecorrection');
    }
	
	public function downloadarticleAction()
	{
		$artProcess_obj = new EP_Delivery_ArticleProcess();
		$article_obj = new EP_Delivery_Article();
		
		$path = $this->_request->getParam('path');
        if(isset($path))
        {
            $details= $artProcess_obj->getArticlePath($path);
            $pathId = $details[0]['article_path'];
            $fileName = $details[0]['article_name'];
            if($pathId)
            {
                $server_path = "/home/sites/site7/web/FO/articles/";
                $dwfile= $server_path.$pathId;
                    //echo $dwfile;exit;
                $attachment=new Ep_Message_Attachment();
                $attachment->downloadAttachment($dwfile,'attachment', $fileName);
                exit;
            }
        }
		
		$spec_path = $this->_request->getParam('spec');
        if(isset($spec_path))
        {
           $details= $article_obj->getArticleDetails($spec_path);
           $specpath="/home/sites/site7/web/FO/client_spec".$details[0]['filepath'];
            $fileName = $details[0]['deliveryTitle']."-specs";
     		if(!is_dir($specpath))
			{
				$attachment=new Ep_Message_Attachment();
                $attachment->downloadAttachment($specpath,'attachment', $fileName);
                exit;
			}
			else
			{
                $this->_helper->FlashMessenger('File is not Available.');
                $this->_redirect($prevurl);
			}
        }
	}
	
	public function getcorrectorcommentsAction()
    {
        $s1_params=$this->_request->getParams();
        $reason_obj=new EP_Delivery_ArticleReassignReasons();
        $this->_view->articleId = $s1_params['artId'];
        $this->_view->partId = $s1_params['partId'];
        $this->_view->userId = $this->adminLogin->userId;
        $this->_view->stage = $s1_params['stage'];
        if(isset($s1_params['partId']))
        {
            $comment_details=$reason_obj->getCorrectorComments($s1_params['partId']);
            $this->_view->comments= $comment_details;
        }
        $this->_view->render("proofread_commentpop");
    }
	/*edited by naseer on 06-10-2015*/
	public function bulkvalidatestage1artsAction()
    {
        $participate_obj = new EP_Participation_Participation();
        $crtparticipate_obj  = new Ep_Participation_CorrectorParticipation();
        $autoEmails = new Ep_Message_AutoEmails();
        $article_obj = new EP_Delivery_Article();
        $del_obj = new Ep_Delivery_Delivery();
        $artProcess_obj = new EP_Delivery_ArticleProcess();
        $user_obj=new Ep_User_User();
        $recent_acts_obj=new Ep_User_RecentActivities();

        $s1art_params = $this->_request->getParams();
        $validateDetails = explode('|', $s1art_params['validate_art']) ;
        //echo '<pre>'; //exit('partId='.$partId);

        foreach($validateDetails as $validateDetail) :
            
            $validateArt_ = explode('_', $validateDetail) ;
            $artId = $validateArt_[0];
            $partId = $validateArt_[1];
            //$details = $article_obj->getArticleDetails($artId);

            $recentversion= $artProcess_obj->getRecentVersion($partId);
            
            ////udate status participation table for stage///////
            $data = array("current_stage"=>"stage2", "status"=>"under_study", "marks"=>$recentversion[0]['marks']);
            $query = "id= '".$partId."'";
            $participate_obj->updateParticipation($data,$query);

            /////udate status article process table///////
            $this->insertStageRecord($partId,$recentversion[0]["version"],'s1','approved');

            /////////send mail to contributor///////////////////////////////////////
            //$contribDetails=$autoEmails->getContribUserDetails($paricipationdetails[0]['user_id']);
            $paricipationdetails=$participate_obj->getParticipateDetails($partId);
            $parameters['article_title']= $paricipationdetails[0]['title'];
            //$autoEmails->messageToEPMail($paricipationdetails[0]['user_id'],84,$parameters);

            /////////////article history////////////////
            $actparams['artId'] = $artId;
            $actparams['stage'] = "stage1";
            $actparams['action'] = "validated";
            $this->articleHistory(21,$actparams);
            /////////////end of article history////////////////
        endforeach ;

        $this->_helper->FlashMessenger(utf8_decode('Articles Approuvé avec succès'));
        $this->_redirect("/proofread/stage-deliveries?submenuId=ML3-SL2");
    }

    public function bulkvalidatestage2artsAction()
    {
        $participate_obj = new EP_Participation_Participation();
        $crtparticipate_obj  = new Ep_Participation_CorrectorParticipation();
        $autoEmails = new Ep_Message_AutoEmails();
        $article_obj = new EP_Delivery_Article();
        $del_obj = new Ep_Delivery_Delivery();
        $artProcess_obj = new EP_Delivery_ArticleProcess();
        $user_obj=new Ep_User_User();
        $recent_acts_obj=new Ep_User_RecentActivities();

        $s2art_params = $this->_request->getParams();
        $validateDetails = explode('|', $s2art_params['validate_art']) ;
        //echo '<pre>'; print_r($s2art_params);exit;//exit('partId='.$partId);

        foreach($validateDetails as $validateDetail) :

            $validateArt_ = explode('_', $validateDetail) ;
            $artId = $validateArt_[0];
            $partId = $validateArt_[1];
            $details = $article_obj->getArticleDetails($artId);    //print_r($details); exit;
            if($details[0]['correction'] == 'yes')
            {
                $crtpartsOfArt = $crtparticipate_obj->getAllCrtParticipationsStage2($artId);
				if($crtpartsOfArt != 'NO')
                	$crtpartId = $crtpartsOfArt[0]['id'] ;
            }
            $recentversion= $artProcess_obj->getRecentVersion($partId);
                /*added by naseer  */
                $stencil_obj = new Ep_Ebookers_Stencils();
                $results = $stencil_obj->getStencilsDetails($artId,$partId);
                if($results[0]['stencils_ebooker'] === 'yes' && $results[0]['product'] === 'redaction' ) {
                    //saving individual stencils in data base//
                    $stencil_text = explode("###$$$###",$results[0]['article_doc_content']);
                    for ($k = 0; $k < count($stencil_text); $k++) {
                        $stencil_data = array("stencil_content" => $stencil_text[$k], "article_id" => $artId, "delivery_id" => $results[0]['delivery_id'], "token_ids" => $results[0]['ebooker_tokenids'], "language" => $results[0]['lang']);
                        $stencil_obj->insertStencil($stencil_data);
                    }
                }
                else if($results[0]['stencils_ebooker'] === 'yes' && $results[0]['product'] === 'translation' ) {
                    //saving individual stencils in data base//
                    $translateStencils = $stencil_obj->getStencilstoTranslate($artId);

                    $stencil_text = explode("###$$$###",$results[0]['article_doc_content']);

                    for ($k = 0; $k < count($stencil_text); $k++) {
                        $stencil_data = array("translation_id" => $translateStencils[$k]['id'],"stencil_content" => $stencil_text[$k], "article_id" => $artId, "delivery_id" => $results[0]['delivery_id'], "token_ids" => $results[0]['ebooker_tokenids'], "language" => $results[0]['lang']);
                        $stencil_obj->insertStencil($stencil_data);
                    }
                }
            /* end of added by naseer on 10-09-2015 */
            ////udate status participation table for stage///////
            $premium=$del_obj->checkPremiumAO($artId);
            $paricipationdetails=$participate_obj->getParticipateDetails($partId);

            //print_r($paricipationdetails);
            if($premium=='NO')
                $data = array("current_stage"=>"client", "status"=>"under_study", "marks"=>$recentversion[0]['marks']);////////updating
            else
            {
                $data = array("current_stage"=>"client", "status"=>"published", "marks"=>$recentversion[0]['marks']);////////updating

                $royalty_obj=new Ep_Payment_Royalties();
                if($royalty_obj->checkRoyaltyExists($paricipationdetails[0]['article_id'])=='NO')
                {  
                    //Added w.r.t Recruitment 
					if($paricipationdetails[0]['free_article']=='yes' && $paricipationdetails[0]['missiontest']=='yes')
						$price_writer=0;
					else
						$price_writer=$paricipationdetails[0]['price_user'];
                    $royalty_obj->participate_id=$paricipationdetails[0]['participateId'];
                    $royalty_obj->article_id=$paricipationdetails[0]['article_id'];
                    $royalty_obj->user_id=$paricipationdetails[0]['user_id'];
                    $royalty_obj->price=$price_writer;
                    $royalty_obj->correction="no";
					$royalty_obj->currency=$paricipationdetails[0]['currency'];
                    $royalty_obj->insert();
                    if($details[0]['correction'] == "yes")
                    {
                        $royalty_obj_c=new Ep_Payment_Royalties();
                        $crtparicipationdetails=$crtparticipate_obj->getCrtParticipateDetails($crtpartId);
                        if($crtparicipationdetails != 'NO')
                        {
                            $royalty_obj_c->participate_id=$partId;
                            $royalty_obj_c->crt_participate_id=$crtpartId;
                            $royalty_obj_c->article_id=$crtparicipationdetails[0]['article_id'];
                            $royalty_obj_c->user_id=$crtparicipationdetails[0]['corrector_id'];
                            $royalty_obj_c->price=$crtparicipationdetails[0]['price_corrector'];
                            $royalty_obj_c->correction="yes";
							$royalty_obj_c->currency=$paricipationdetails[0]['currency'];
                            $royalty_obj_c->insert();/////*
                            //unset($royalty_obj_c);
                        }
                    }
                }
                //unset($royalty_obj);    unset($royalty_obj_c);
            }
			
            $query = "article_id= '".$artId."' AND id = '".$partId."'";
            $participate_obj->updateParticipation($data,$query);/////*
            if($details[0]['correction'] == "yes")
            {
                if($premium=='NO')
                    $data = array("current_stage"=>"client", "status"=>"under_study");////////updating
                else
                    $data = array("current_stage"=>"client", "status"=>"published");////////updating

                $query = "article_id= '".$artId."' AND id = '".$crtpartId."'";
                $crtparticipate_obj->updateCrtParticipation($data,$query);/////*
            }

            ///////update in article///////////
            $data = array("file_path"=>$recentversion[0]['article_path']);////////updating
            $query = "id= '".$artId."'";
            $article_obj->updateArticle($data,$query);/////*

            /////udate status article process table///////
            $this->insertStageRecord($partId,$recentversion[0]["version"],'s2','approved');

            /* *Sending Mails ***/
            /////////send mail to contributor///////////////////////////////////////
            $paricipationdetails=$participate_obj->getParticipateDetails($partId);
            $contribDetails=$autoEmails->getContribUserDetails($paricipationdetails[0]['user_id']);
            ///////////////if user is sub-junior then update him to jc/////////
            $userDetails =  $user_obj->getAllUsersDetails($paricipationdetails[0]['user_id']);
            if($userDetails[0]['profile_type'] == "sub-junior")
            {
                $data = array("profile_type"=>"junior");
                $query = "identifier = '".$userDetails[0]['identifier']."' ";
                $user_obj->updateUser($data,$query);
            }

            if($contribDetails[0]['firstname']!=NULL)
                $parameters['contributor_name']= $contribDetails[0]['firstname']." ".$contribDetails[0]['lastname'];
            else
                $parameters['contributor_name']= $contribDetails[0]['email'];

            $parameters['created_date']=date("d/m/Y",strtotime($paricipationdetails[0]['created_at']));
            $parameters['document_link']="/client/ongoingao";
            $parameters['invoice_link']="/client/invoice";
            $parameters['royalty']=$paricipationdetails[0]['price_user'];
            $receiverId = $paricipationdetails[0]['user_id'];
            $parameters['article_title']= $paricipationdetails[0]['title'];
            $parameters['articlename_link']="/contrib/mission-published?article_id=".$artId;
            $autoEmails->messageToEPMail($receiverId,53,$parameters);

            /////////send mail to corrector/////////////////////////////////
            if($details[0]['correction'] == "yes" && $crtpartId!= '')
            {   
                $paricipationdetails=$crtparticipate_obj->getCrtParticipateDetails($crtpartId); 
				$correctorDetails=$autoEmails->getContribUserDetails($paricipationdetails[0]['corrector_id']);
				if($correctorDetails[0]['firstname']!=NULL)
					$parameters['corrector_name']= $correctorDetails[0]['firstname']." ".$correctorDetails[0]['lastname'];
				else
					$parameters['corrector_name']= $correctorDetails[0]['email'];
 
				$parameters['created_date']=date("d/m/Y",strtotime($paricipationdetails[0]['created_at']));
				$parameters['document_link']="/client/ongoingao";
				$parameters['invoice_link']="/client/invoice";
				$parameters['royalty']=$paricipationdetails[0]['price_corrector'];
				$parameters['article_title']= $paricipationdetails[0]['title'];
				$parameters['articlename_link']="/contrib/mission-published?article_id=".$artId;
				//sendidng the mail to corrector //
				$receiverId = $paricipationdetails[0]['corrector_id'];
				$autoEmails->messageToEPMail($receiverId,59,$parameters);
            } 
            /////////////article history////////////////
            $actparams['artId'] = $artId;
            $actparams['stage'] = "stage2";
            $actparams['action'] = "validated";
            /////*$this->articleHistory(25,$actparams);
            /////////////end of article history////////////////
            /* *sending mail to Client**/
            $clientDetails=$autoEmails->getUserDetails($paricipationdetails[0]['clientId']);
            if($clientDetails[0]['username']!=NULL)
                $parameters['client_name']= $clientDetails[0]['username'];
            else
                $parameters['client_name']= $clientDetails[0]['email'];

            //Insert Recent Activities
            $ract=array("type" => "bopublish","user_id"=>$paricipationdetails[0]['clientId'],"activity_by"=>"bo","article_id"=>$artId);
            $recent_acts_obj->insertRecentActivities($ract);/////*

            $deliveryId=$del_obj->getDeliveryID($artId);
            if($deliveryId!="NO")
            {
                $checkLastAO=$del_obj->checkLastArticleAO($deliveryId);
                if($checkLastAO=="YES")
                {
                    $delcreateduser=$del_obj->getDelCreatedUser($deliveryId);          // print_r($delcreateduser);  exit;
                    $object="L'appel d'offres ".$delcreateduser[0]['title']." vient d'&ecirc;tre valid&eacute; ";
                    $text_mail="<p>Cher ".$delcreateduser[0]['first_name']." ,<br><br>
                                        Le dernier article de l'appel d'offres ".$delcreateduser[0]['title']." vient d'&ecirc;tre valid&eacute;!<br><br>
                                        Cliquez <a href=\"http://admin-test.edit-place.co.uk/ongoing/ao-details?client_id=".$delcreateduser[0]['user_id']."&ao_id=".$delcreateduser[0]['id']."&submenuId=ML2-SL4\">ici</a> si vous souhaitez acc&eacute;der &agrave; la page de suivi de l'AO.<br><br>
                                        Cordialement,<br>
                                        <br>
                                        Toute l'&eacute;quipe d&rsquo;Edit-place</p>";
                    $mail = new Zend_Mail();
                    $mail->addHeader('Reply-To',$this->configval['mail_from']);
                    $mail->setBodyHtml($text_mail)
                        ->setFrom($this->configval['mail_from'])
                        ->addTo($delcreateduser[0]['email'])
                        ->setSubject($object);
                    $mail->send();
                }
            }
            //exit($partId.'***'.$artId);
        //}
        endforeach ;

        $this->_helper->FlashMessenger(utf8_decode('Articles Approuvé avec succès'));
        $this->_redirect("/proofread/stage-deliveries?submenuId=ML3-SL3");
    }
    public function directValidationS1Action()
    {
        $participate_obj = new EP_Participation_Participation();
        $autoEmails = new Ep_Message_AutoEmails();
        $article_obj = new EP_Delivery_Article();
        $del_obj = new Ep_Delivery_Delivery();
        $artProcess_obj = new EP_Delivery_ArticleProcess();
        $user_obj=new Ep_User_User();
        $recent_acts_obj=new Ep_User_RecentActivities();

        $s1art_params = $this->_request->getParams();

        $artId = $s1art_params['articleId'];
        $partId = $s1art_params['participateId'];
        $recentversion= $artProcess_obj->getRecentVersion($partId);
        ////udate status participation table for stage///////
        $premium=$del_obj->checkPremiumAO($artId);
        $paricipationdetails=$participate_obj->getParticipateDetails($partId);
        //print_r($paricipationdetails);
        if($premium=='NO')
            $data = array("current_stage"=>"client", "status"=>"under_study", "marks"=>$recentversion[0]['marks']);////////updating
        else
        {
            $data = array("current_stage"=>"client", "status"=>"published", "marks"=>$recentversion[0]['marks']);////////updating

            $royalty_obj=new Ep_Payment_Royalties();
            if($royalty_obj->checkRoyaltyExists($paricipationdetails[0]['article_id'])=='NO')
            {
                $royalty_obj->participate_id=$paricipationdetails[0]['participateId'];
                $royalty_obj->article_id=$paricipationdetails[0]['article_id'];
                $royalty_obj->user_id=$paricipationdetails[0]['user_id'];
                $royalty_obj->price=$paricipationdetails[0]['price_user'];
                $royalty_obj->correction="no";
                $royalty_obj->currency=$paricipationdetails[0]['currency'];
                $royalty_obj->insert();/////*
            }
        }
        $query = "article_id= '".$artId."' AND id = '".$partId."'";
        $participate_obj->updateParticipation($data,$query);/////*

        ///////update in article///////////
        $data = array("file_path"=>$recentversion[0]['article_path']);////////updating
        $query = "id= '".$artId."'";
        $article_obj->updateArticle($data,$query);/////*
        /////udate status article process table///////
        //$this->insertStageRecord($partId,$recentversion[0]["version"],'s1','directapproved');
        /* *Sending Mails ***/
        /////////send mail to contributor///////////////////////////////////////
        $paricipationdetails=$participate_obj->getParticipateDetails($partId);
        $contribDetails=$autoEmails->getContribUserDetails($paricipationdetails[0]['user_id']);
        ///////////////if user is sub-junior then update him to jc/////////
        $userDetails =  $user_obj->getAllUsersDetails($paricipationdetails[0]['user_id']);
        if($userDetails[0]['profile_type'] == "sub-junior")
        {
            $data = array("profile_type"=>"junior");
            $query = "identifier = '".$userDetails[0]['identifier']."' ";
            $user_obj->updateUser($data,$query);
        }
        if($contribDetails[0]['firstname']!=NULL)
            $parameters['contributor_name']= $contribDetails[0]['firstname']." ".$contribDetails[0]['lastname'];
        else
            $parameters['contributor_name']= $contribDetails[0]['email'];

        $parameters['created_date']=date("d/m/Y",strtotime($paricipationdetails[0]['created_at']));
        $parameters['document_link']="/client/ongoingao";
        $parameters['invoice_link']="/client/invoice";
        $parameters['royalty']=$paricipationdetails[0]['price_user'];
        $receiverId = $paricipationdetails[0]['user_id'];
        $parameters['article_title']= $paricipationdetails[0]['title'];
        $parameters['articlename_link']="/contrib/mission-published?article_id=".$artId;
        $autoEmails->messageToEPMail($receiverId,53,$parameters);

        /////////////article history////////////////
        $actparams['artId'] = $artId;
        $actparams['stage'] = "stage1";
        $actparams['action'] = "directvalidated";
        $this->articleHistory(68,$actparams);
        /////////////end of article history////////////////
        /////////////end of article history////////////////
        /* *sending mail to Client**/
        $clientDetails=$autoEmails->getUserDetails($paricipationdetails[0]['clientId']);
        if($clientDetails[0]['username']!=NULL)
            $parameters['client_name']= $clientDetails[0]['username'];
        else
            $parameters['client_name']= $clientDetails[0]['email'];
        //Insert Recent Activities
        $ract=array("type" => "bopublish","user_id"=>$paricipationdetails[0]['clientId'],"activity_by"=>"bo","article_id"=>$artId);
        $recent_acts_obj->insertRecentActivities($ract);/////*

        $deliveryId=$del_obj->getDeliveryID($artId);
        if($deliveryId!="NO")
        {
            $checkLastAO=$del_obj->checkLastArticleAO($deliveryId);
            if($checkLastAO=="YES")
            {
                $delcreateduser=$del_obj->getDelCreatedUser($deliveryId);          // print_r($delcreateduser);  exit;
                $object="L'appel d'offres ".$delcreateduser[0]['title']." vient d'&ecirc;tre valid&eacute; ";
                $text_mail="<p>Cher ".$delcreateduser[0]['first_name']." ,<br><br>
                                    Le dernier article de l'appel d'offres ".$delcreateduser[0]['title']." vient d'&ecirc;tre valid&eacute;!<br><br>
                                    Cliquez <a href=\"http://admin-test.edit-place.co.uk/ongoing/ao-details?client_id=".$delcreateduser[0]['user_id']."&ao_id=".$delcreateduser[0]['id']."&submenuId=ML2-SL4\">ici</a> si vous souhaitez acc&eacute;der &agrave; la page de suivi de l'AO.<br><br>
                                    Cordialement,<br>
                                    <br>
                                    Toute l'&eacute;quipe d&rsquo;Edit-place</p>";
                $mail = new Zend_Mail();
                $mail->addHeader('Reply-To',$this->configval['mail_from']);
                $mail->setBodyHtml($text_mail)
                    ->setFrom($this->configval['mail_from'])
                    ->addTo($delcreateduser[0]['email'])
                    ->setSubject($object);
                $mail->send();
            }
        }
        $this->_helper->FlashMessenger(utf8_decode('Articles Approuvé avec succès'));
        $this->_redirect("/proofread/stage-deliveries?submenuId=ML3-SL2");
    }
    ////////////////article versions stuck up in plagiarism stage ////////////////////////
    public function plagStuckArtsAction()
    {
        $prevurl = getenv("HTTP_REFERER");
        $participate_obj = new EP_Participation_Participation();
        $artProcess_obj = new EP_Delivery_ArticleProcess();
        $plagcomments_obj = new Ep_Delivery_PlagStuckComments();

        $artversions_details = $artProcess_obj->getPlagStuckVersions();
        if($artversions_details != 'NO')
        {
            foreach($artversions_details as $key=>$val){
                $phperror = $plagcomments_obj->getAllPhpErrors($artversions_details[$key]['id']);
                $artversions_details[$key]['php']= $phperror[0]['phpcount'];
                $rubyerror = $plagcomments_obj->getAllRubyErrors($artversions_details[$key]['id']);
                $artversions_details[$key]['ruby']= $rubyerror[0]['rubycount'];
            }

        }

        $this->_view->stuckartversions = $artversions_details;
        $this->_view->render("proofread_plag_stuck_arts");

    }
    ///////getting  and showing comments of tech team on plagiarism stuck up files//////////////
    public function plagstuckcommentsAction()
    {
        $groupprofile_params=$this->_request->getParams();
        $usercomment_obj=new Ep_Message_UserComments();
        $user_obj = new Ep_User_User();

        $plagcomments_obj = new Ep_Delivery_PlagStuckComments();
        $artprocid = $groupprofile_params['artprocid'];
        $userid = $this->adminLogin->userId;

        $comment_details=$plagcomments_obj->getAllPlagComments($artprocid);
        $this->_view->plagcomments= $comment_details;
        $this->_view->userid= $userid;
        $this->_view->artprocid= $artprocid;
        $this->_view->render("proofread_plagcommentspopup");
    }
    ///////////get all the commets of the tech user with reasons of plag stuck///
    public function writeplagstuckcommentsAction()
    {
        $comment_params=$this->_request->getParams();
        $artprocess_obj = new Ep_Delivery_ArticleProcess();
        $article_obj = new Ep_Delivery_Article();
        $reason_obj=new EP_Delivery_ArticleReassignReasons();
        $plagcomments_obj = new Ep_Delivery_PlagStuckComments();
        $user_obj = new Ep_User_User();
        $artdetails = $artprocess_obj->getArticlePath($comment_params['artprocId']);
        $articleIds =explode("/",$artdetails[0]['article_path']);
        $articleId = $articleIds[0];
         $artdeliveydetials = $article_obj->getArticleDetails($articleId); //print_r($artdeliveydetials); exit;
        ///////if the comment from group profile popup comments///////////911010042353261
        if($comment_params['userid'] != '')
        {
            $plagcomments_obj =new Ep_Delivery_PlagStuckComments();
            $plagcomments_obj->article_id=$articleId; //$comment_params['article_id'] ;
            $plagcomments_obj->artprocess_id=$comment_params['artprocId'] ;
            $plagcomments_obj->user_id=$comment_params['userid'] ;
            $plagcomments_obj->comments=$comment_params['comment'] ;
            $plagcomments_obj->created_at=date("Y-m-d H:i:s", time());
            $plagcomments_obj->php=$comment_params['errorphp'];
            $plagcomments_obj->ruby=$comment_params['errorruby'];
            $plagcomments_obj->insert();
            $comment_details=$plagcomments_obj->getAllPlagComments($comment_params['artprocId']);
            foreach($comment_details as $key => $value)
            {
                echo "<div class='alignleft'>".$comment_details[$key]['first_name']."&nbsp".$comment_details[$key]['last_name']." <label class='label label-info'>at ".$comment_details[$key]['created_at']."</label></div>
					<br><table class='table table-bordered'><tr><td class='alert alert-success alignleft'>".$comment_details[$key]['comments']."</td></tr></table>";
            }
        }
        if($comment_params['errorphp'] == 1)
            $errortype = 'php';
        elseif($comment_params['errorruby'] == 1)
            $errortype = 'ruby';
        elseif($comment_params['errorphp'] == 1 && $comment_params['errorruby'] == 1)
            $errortype = 'php&ruby';
        elseif($comment_params['errorwriter'] == 1)
            $errortype = 'writer';

        $details['aoId'] = $artdeliveydetials[0]['deliveryId'];
        $details['aotitle'] = $artdeliveydetials[0]['deliveryTitle'];
        $details['arttitle'] = $artdeliveydetials[0]['title'];
        $details['comments'] = $comment_params['comment'] ;
        $details['aocreateduser'] = $artdeliveydetials[0]['first_name']." ".$artdeliveydetials[0]['last_name'];
        $details['aocreateduseremail'] = $artdeliveydetials[0]['email'];

        $autoEmails = new Ep_Message_AutoEmails();
        $autoEmails->plagStuckToPersonalEmail($errortype, $details);

        exit;
    }
    /* added by  naseer on 14-08-2015*/
    //functio to redirect if there are stencils in the delivery//
    public function stage2EbookersCorrectionAction(){
       //echo "<pre>";print_r($_REQUEST);exit;
       $url = getenv('REQUEST_URI');
       $artId = $this->_request->getParam('articleId');
       $participId = $this->_request->getParam('participateId');    //this Id is from Participation table not CorrectorParticicpation table//
       $participate_obj = new EP_Participation_Participation();
       $crtparticipate_obj  = new Ep_Participation_CorrectorParticipation();
       $autoEmails = new Ep_Message_AutoEmails();
       $article_obj = new EP_Delivery_Article();
       $options_obj = new Ep_Delivery_Options();
       $del_obj = new Ep_Delivery_Delivery();
       $artProcess_obj = new EP_Delivery_ArticleProcess();
       $details = $article_obj->getArticleDetails($artId);
       $royalty_obj=new Ep_Payment_Royalties();
       $userplus_obj=new Ep_User_UserPlus();
        $partUserInfo = $userplus_obj->getPartUserinfo($participId);
        $this->_view->partUserInfo = $partUserInfo;
        $crtPartUserInfo = $userplus_obj->getCrtPartUserinfo($participId);
        $this->_view->crtPartUserInfo = $crtPartUserInfo;
        /////if user did not lock the perticular article it should not allow him to see detials/////////
       $lockstatus = $this->checklockAction($artId);
       $template_obj = new Ep_Message_Template();
       if($lockstatus == 'no')
           $this->_redirect("/proofread/stage-deliveries?submenuId=ML3-SL3");

       $s2art_params=$this->_request->getParams();
	   //print_r($s2art_params);exit;
       foreach ($details as $key => $value) {
           $details[$key]['type']=$this->type_array[$details[$key]['type']];
           //$details[$key]['sign_type']=$this->signtype_array[$details[$key]['sign_type']];
           $details[$key]['signtype']=$details[$key]['sign_type'];
       }
       $aoId = $details[0]['deliveryId'];
       $this->_view->articledetails=$details;
       //////pop up displays/////
       $res = $template_obj->refuseValidTemplates('null', $details[0]['product']);
       $this->_view->refusevalidtemps = $res;
       $this->_view->selectedrefusalreasons = explode("|",$details[0]['refusalreasons']);
       $refualreasonContent = "";
       for($i=0; $i<count($res); $i++)
       {
           if(in_array($res[$i]['identifier'], $this->_view->selectedrefusalreasons))
           {
               $refualreasonContent.= $res[$i]['content']."<br><br>";
           }
       }
       $this->_view->refualreasonContent = $refualreasonContent;
       //display article process grid in s1 correction/////////

       $partsOfArt = $participate_obj->getAllParticipationsStage($artId, 'stage2');
       $partId = $partsOfArt[0]['id'];
       $this->_view->partId = $partId;
       $versions_details = $artProcess_obj->getVersionDetails($partId);
       $i=0;
       while($versions_details[$i]['id']){
           $versions_details[$i]['article_doc_content'] = explode('###$$$###',$versions_details[$i]['article_doc_content']);
           $i++;
       }
       $getEbookersTokenIds = $article_obj->getEbookersTokenIds($artId);
       $this->_view->token_ids = $getEbookersTokenIds[0]['ebooker_tokenids'];
       $this->_view->versions_details = $versions_details;
       $versions_user_details = $artProcess_obj->getRecentVersionId($partId);
       $this->_view->versions_user_details = $versions_user_details;
       $versions_contributor_details = $artProcess_obj->getRecentContributorVersionId($partId);
       $this->_view->versions_contributor_details = $versions_contributor_details;
       $recentversion = $artProcess_obj->getRecentVersion($partId);
       //print_r($recentversion);
       if($recentversion[0]['reasons_marks'] == '')
       {
           $rreasons = explode("|",$details[0]['refusalreasons']);
           if(count($rreasons) != 0)
           {
               foreach($rreasons as $keys)
               {
                   $res = $template_obj->refuseValidTemplatesOnId($keys);
                   $refreason[$keys]  = $res[0]['title'];
               }
           }else{
               $refreason[0]  = 'overall rating';
           }
           $this->_view->rreasons=$refreason;
           $this->_view->s1reasons=$refreason;
           $s1marks=array_fill(0, 10, 0);
           $this->_view->reasonslist=implode(",",$s1marks);
           $this->_view->s1markscount=0;
           $this->_view->avgs1marks=0;
           $this->_view->previousdetails=$recentversion[0]['reasons_marks'];
           $this->_view->rreasonscount=count($refreason)*2;
           if($recentversion[0]['stage'] == 'corrector') // if  prevous version isn from corrector then  desable the rating and commntes
               $this->_view->versionfromCorrector = 'true';
           else
               $this->_view->versionfromCorrector = 'false';
       }
       else
       {
           $rreasons = explode(",", $recentversion[0]['reasons_marks']); //print_r($rreasons);
           $s1reasons = array();
           if(count($rreasons) != 0)
           {
               for ($i = 0; $i < count($rreasons); $i++)
               {
                   $array = explode("|", $rreasons[$i]);
                   $res = $template_obj->refuseValidTemplatesOnId(trim($array[0]));
                   $s1reasons[$array[0]] = $res[0]['title'];
                   //$s1reasons[$i] = $array[0];
                   $s1marks[$i] = $array[1];
               }
           }else{
               $s1reasons[0] = 'overall rating';
           }
           $this->_view->s1reasons=$s1reasons;
           $this->_view->s1marks=$s1marks;
           $this->_view->reasonslist=implode(",",$s1marks);
           // $this->_view->s1markscount=array_sum($s1marks);
           $this->_view->s1markscount=$recentversion[0]['marks'];
           $this->_view->avgs1marks=$recentversion[0]['marks'];
           $this->_view->previousdetails=$recentversion[0]['reasons_marks'];
           $this->_view->rreasonscount=count($rreasons)*2;
           if($recentversion[0]['stage'] == 'corrector') // if  prevous version isn from corrector then  desable the rating and commntes
               $this->_view->versionfromCorrector = 'true';
           else
               $this->_view->versionfromCorrector = 'false';
       }
       //display article process grid in s1 correction/////////
       if($details[0]['correction'] == 'yes')
       {
           $crtpartsOfArt = $crtparticipate_obj->getAllCrtParticipationsStage2($artId);
           $crtpartId = $crtpartsOfArt[0]['id'];
           $this->_view->crtpartId = $crtpartId;
       }
       ////getting the refuse count to change the button refuse to close//////
       $refused_count= $participate_obj->getRefusedCount($partId);
       $this->_view->refused_count = $refused_count[0]['refused_count'];
       $deldetails =  $del_obj->getDeliveryDetails($aoId);///to display in services box///
       $this->_view->deldetails=  $deldetails;
       $this->_view->options=$options_obj->getOptions($aoId);///to display in services box///
       $this->_view->res_seltdopts = $this->getDelSelectedOption($aoId); ///option selected for the delivery
       ///check whether the marks given in the stage1 correction phase////
       $specsmarks = $artProcess_obj->getRecentVersionByTime($partId);// echo $specsmarks[0]['marks']; exit;///to display in services box///
       $this->_view->marksgiven = $specsmarks[0]['marks'];
       if(isset($s2art_params["s2art_approve"]) && $s2art_params["s2art_approve"]=='yes')//from s2 correction and writerarts pages/
       {
           if($s2art_params['marks'] == '')
               $s2art_params['marks'] = 0;
           if($s2art_params["markswithreason"] == '')
               $s2art_params["markswithreason"] = 0;
           $partId = $s2art_params["participateId"] ;
           $crtpartId = $s2art_params["crtparticipateId"] ;
           $recentversion= $artProcess_obj->getRecentVersion($partId);
           if($s2art_params["participateType"] == "normalParticipation")
           {
               $Message = $s2art_params["commentsValidate_".$partId] ;
           }
           elseif($s2art_params["participateType"] == "correctorParticipation"){

               $Message = $s2art_params["commentsCrtValidate_".$crtpartId] ;
           }
           $recentDetials = $artProcess_obj->getVersionDetailsByVersion($partId, $recentversion[0]["version"]);
           ////file upload code///////////
           if($_FILES['art_doc_'.$partId]['tmp_name'] != '')
           {
               $tmpName = $_FILES['art_doc_'.$partId]['tmp_name'];
               $artName = $_FILES['art_doc_'.$partId]['name'];
               $ext = explode('.',$artName);
               $extension = $ext[1];
               $art_path = $artId."_".$this->adminLogin->userId."_".rand(10000, 99999).".".$extension;
               $artProcess_obj->participate_id=$partId ;
               $artProcess_obj->user_id=$this->adminLogin->userId;
               $artProcess_obj->stage='s2' ;
               $artProcess_obj->status='approved' ;
               $artProcess_obj->marks=$s2art_params["markstotal"] ;
               $artProcess_obj->reasons_marks=$s2art_params["markswithreason"] ;
               $artProcess_obj->comments=$s2art_params["marks_comments"] ;
               $artProcess_obj->article_path=$artId."/".$art_path ;
               $artProcess_obj->article_name=$artName ;
               $version = $recentversion[0]["version"]+1;
               $artProcess_obj->version=$version ;
               $artProcess_obj->plagxml='' ;
               $artProcess_obj->art_file_size_limit_email ='' ;

               //////////////////uploading the document///////////////////////////////
               $server_path = "/home/sites/site7/web/FO/articles/";
               $articleDir = $server_path.$artId;
               $newfile = $articleDir."/".$art_path;
               if (move_uploaded_file($tmpName, $newfile))
               {
                   //Antiword obj to get content from uploaded article
                   $antiword_obj=new Ep_Antiword_Antiword($newfile);
                   $artProcess_obj->article_doc_content=$antiword_obj->getContent();
                   $artProcess_obj->article_words_count=$antiword_obj->count_words($artProcess_obj->article_doc_content);
                   $artProcess_obj->insert();
               }
           }
           else{
               ///inserting a record into artcleprocess tabel///
               $this->insertStageRecord($partId,$recentversion[0]["version"],'s2','approved');
               /////udate status article process table///////
               $recentversion= $artProcess_obj->getRecentVersion($partId);
               /*added by naseer  */
               //saving individual stencils in data base//
               $stencil_obj = new Ep_Ebookers_Stencils();
               $results = $stencil_obj->getStencilsDetails($artId,$partId);
               for($k=0;$k<count($s2art_params['stencil_text']);$k++)
               {
                   $stencil_data = array("stencil_content"=>$s2art_params['stencil_text'][$k],"article_id"=>$artId,"delivery_id"=>$aoId,"token_ids"=>$s2art_params['token_ids'],"language"=>$results[0]['lang']);//,"language "=>$results[0]['language ']
                   $stencil_obj->insertStencil($stencil_data);
               }
               //concatinating the sencile before inserting//
               $article_doc_content =implode("###$$$###", $s2art_params['stencil_text'] );
               //code to create a article file in fo//
               $articleDir=FO_ARTICLE_PATH;
               if(!is_dir($articleDir))
                   mkdir($articleDir,TRUE);
               chmod($articleDir,0777);
               $articleName=$artId."_".$this->adminLogin->userId."_".mt_rand(10000,99999).".txt";
               $article_path = $artId."/".$articleName;
               $article_full_path=$articleDir.$article_path;

               //create text stencils file
               $stencil_file = fopen($article_full_path,"w");
               fwrite($stencil_file,$article_doc_content);
               fclose($stencil_file);
               $article_name = 'Stencils_file_'.$artId.'.txt';
               $data = array("marks"=>$s2art_params["markstotal"], "reasons_marks"=>$s2art_params["markswithreason"], "comments"=>$s2art_params["marks_comments"],"article_doc_content"=>$article_doc_content,"article_path"=>$article_path,"article_name"=>$article_name);////////updating
               $query = "participate_id= '".$partId."' AND version='".$recentversion[0]['version']."'";
               $artProcess_obj->updateArticleProcess($data,$query);
               /////udate status article process table///////
               /* $data = array("status"=>"approved", "stage"=>'s2');////////updating
                $query = "participate_id= '".$partId."' AND version='".$recentversion[0]['version']."'";
                $artProcess_obj->updateArticleProcess($data,$query);*/
           }
           $artId = $this->_request->getParam('articleId');

           ////udate status participation table for stage///////
           $premium=$del_obj->checkPremiumAO($artId);
           $paricipationdetails=$participate_obj->getParticipateDetails($partId);

           if($premium=='NO')
               $data = array("current_stage"=>"client", "status"=>"under_study", "marks"=>$recentversion[0]['marks']);////////updating
           else
           {
               $data = array("current_stage"=>"client", "status"=>"published", "marks"=>$recentversion[0]['marks']);////////updating

               if($royalty_obj->checkRoyaltyExists($paricipationdetails[0]['article_id'])=='NO')
               {
                   $royalty_obj->participate_id=$paricipationdetails[0]['participateId'];
                   $royalty_obj->article_id=$paricipationdetails[0]['article_id'];
                   $royalty_obj->user_id=$paricipationdetails[0]['user_id'];
                   $royalty_obj->price=$paricipationdetails[0]['price_user'];
                   $royalty_obj->correction="no";
                   $royalty_obj->currency=$paricipationdetails[0]['currency'];
                   $royalty_obj->insert();
                   if($s2art_params["participateType"] == "correctorParticipation")
                   {
                       $royalty_obj=new Ep_Payment_Royalties();
                       $crtparicipationdetails=$crtparticipate_obj->getCrtParticipateDetails($crtpartId);
                       if($crtparicipationdetails != 'NO')
                       {
                           $royalty_obj->participate_id=$partId;
                           $royalty_obj->crt_participate_id=$crtpartId;
                           $royalty_obj->article_id=$crtparicipationdetails[0]['article_id'];
                           $royalty_obj->user_id=$crtparicipationdetails[0]['corrector_id'];
                           $royalty_obj->price=$crtparicipationdetails[0]['price_corrector'];
                           $royalty_obj->correction="yes";
                           $royalty_obj->currency=$paricipationdetails[0]['currency'];
                           $royalty_obj->insert();
                       }
                   }
               }
           }
           $query = "article_id= '".$artId."' AND id = '".$partId."'";
           //edited by naseer on 18-09-2015
           $participate_obj->updateParticipation($data,$query);
           if($s2art_params["participateType"] == "correctorParticipation")
           {
               if($premium=='NO')
                   $data1 = array("current_stage"=>"client", "status"=>"under_study");////////updating
               else
                   $data1= array("current_stage"=>"client", "status"=>"published");////////updating

               $query = "article_id= '".$artId."' AND id = '".$crtpartId."'";
               //edited by naseer on 18-09-2015
               $crtparticipate_obj->updateCrtParticipation($data1,$query);
           }

           ///////update in article///////////
           $data = array("file_path"=>$recentversion[0]['article_path']);////////updating
           $query = "id= '".$artId."'";
           $article_obj->updateArticle($data,$query);




           /* *Sending Mails ***/
           /////////send mail to contributor///////////////////////////////////////
           $paricipationdetails=$participate_obj->getParticipateDetails($partId);
           $contribDetails=$autoEmails->getContribUserDetails($paricipationdetails[0]['user_id']);
           ///////////////if user is sub-junior then update him to jc/////////
           $user_obj=new Ep_User_User();
           $userDetails =  $user_obj->getAllUsersDetails($paricipationdetails[0]['user_id']);
           if($userDetails[0]['profile_type'] == "sub-junior")
           {
               $data = array("profile_type"=>"junior");////////updating
               $query = "identifier = '".$userDetails[0]['identifier']."' ";
               $user_obj->updateUser($data,$query);
           }

           if($contribDetails[0]['firstname']!=NULL)
               $parameters['contributor_name']= $contribDetails[0]['firstname']." ".$contribDetails[0]['lastname'];
           else
               $parameters['contributor_name']= $contribDetails[0]['email'];

           $parameters['created_date']=date("d/m/Y",strtotime($paricipationdetails[0]['created_at']));
           $parameters['document_link']="/client/ongoingao";
           $parameters['invoice_link']="/client/invoice";
           if($paricipationdetails[0]['currency']=='euro')
               $curr='&euro;';
           else
               $curr='&pound;';
           $parameters['royalty']=$paricipationdetails[0]['price_user'].$curr;
           //sendidng the mail to contirbutor //
           $automail=new Ep_Message_AutoEmails();
           $email=$automail->getAutoEmail(53);
           $Object=$email[0]['Object'];
           $receiverId = $paricipationdetails[0]['user_id'];
           $autoEmails->sendMailEpMailBox($receiverId,$Object,$Message);

           /////////send mail to corrector/////////////////////////////////
           if($s2art_params["participateType"] == "correctorParticipation")
           {
               $paricipationdetails=$crtparticipate_obj->getCrtParticipateDetails($crtpartId);
			   
               $correctorDetails=$autoEmails->getContribUserDetails($paricipationdetails[0]['corrector_id']);
               if($correctorDetails[0]['firstname']!=NULL)
                   $parameters['corrector_name']= $correctorDetails[0]['firstname']." ".$correctorDetails[0]['lastname'];
               else
                   $parameters['corrector_name']= $correctorDetails[0]['email'];

               $parameters['created_date']=date("d/m/Y",strtotime($paricipationdetails[0]['created_at']));
               $parameters['document_link']="/client/ongoingao";
               $parameters['invoice_link']="/client/invoice";
               $parameters['royalty']=$paricipationdetails[0]['price_corrector'];
               $parameters['article_title']= $paricipationdetails[0]['title'];
               $parameters['articlename_link']="/contrib/mission-deliver?article_id=".$artId;
               //sendidng the mail to corrector //
               $receiverId = $paricipationdetails[0]['corrector_id'];
               $autoEmails->messageToEPMail($receiverId,59,$parameters);
			  // print_r($paricipationdetails);exit;
           }
           /////////////article history////////////////
           $actparams['artId'] = $artId;
           $actparams['stage'] = "stage2";
           $actparams['action'] = "validated";
           $this->articleHistory(25,$actparams);
           /////////////end of article history////////////////
           /* *sending mail to Client**/
           $clientDetails=$autoEmails->getUserDetails($paricipationdetails[0]['clientId']);
           if($clientDetails[0]['username']!=NULL)
               $parameters['client_name']= $clientDetails[0]['username'];
           else
               $parameters['client_name']= $clientDetails[0]['email'];
           if($deldetails[0]['mail_send']=='yes')
           {
               // $this->messageToEPMail($paricipationdetails[0]['clientId'],1,$parameters);
           }
           //Insert Recent Activities
           $recent_acts_obj=new Ep_User_RecentActivities();
           $ract=array("type" => "bopublish","user_id"=>$paricipationdetails[0]['clientId'],"activity_by"=>"bo","article_id"=>$artId);
           $recent_acts_obj->insertRecentActivities($ract);
           $deliveryId=$del_obj->getDeliveryID($artId);
           if($deliveryId!="NO")
           {
               $checkLastAO=$del_obj->checkLastArticleAO($deliveryId);
               if($checkLastAO=="YES")
               {
                   //sending the mail to client when last alrticle is validated;
                   if($deldetails[0]['mail_send']=='yes')
                   {
                       //  $this->messageToEPMail($paricipationdetails[0]['clientId'],12,$parameters);
                   }
                   ///////////////////////////////////////////
                   $delcreateduser=$del_obj->getDelCreatedUser($deliveryId);

                   $object="L'appel d'offres ".$delcreateduser[0]['title']." est complete; ";
                   $text_mail="<p>Cher ".$delcreateduser[0]['first_name']." ,<br><br>
                                        Le dernier article de l'appel d'offres ".$delcreateduser[0]['title']." vient d'&ecirc;tre valid&eacute;!<br><br>
                                        Merci de cliquer ici pour acc&eacute;der &agrave; la page de suivi de l'AO.<br><br>
                                        Cordialement,<br>
                                        <br>
                                        Toute l'&eacute;quipe d&rsquo;Edit-place</p>";
                   $mail = new Zend_Mail();
                   $mail->addHeader('Reply-To',$this->configval['mail_from']);
                   $mail->setBodyHtml($text_mail)
                       ->setFrom($this->configval['mail_from'])
                       ->addTo($delcreateduser[0]['email'])
                       ->setSubject($object);
                   if($mail->send())
                   {
                       $this->_helper->FlashMessenger(utf8_decode('Article Approuvé avec succès'));
                       $this->_redirect("/proofread/stage-deliveries?submenuId=ML3-SL3");
                   }
               }
               $this->_redirect("/proofread/stage-deliveries?submenuId=ML3-SL3");
           }

           $this->_helper->FlashMessenger(utf8_decode('Article Approved successfully'));
           $this->_redirect("/proofread/stage-deliveries?submenuId=ML3-SL3");
       }
       else if(isset($s2art_params['s2art_corrector_disapprove']) || isset($s2art_params['s2art_corrector_permdisapprove']))
       {
           $crtparticipate_obj  = new Ep_Participation_CorrectorParticipation();
           $crtpartId = $s2art_params["crtparticipateId"] ;
           //$partId = $s2art_params["participateId"] ;
           $delivery_obj=new Ep_Delivery_Delivery();
           $user_obj = new Ep_User_User();
           $refused_count= $crtparticipate_obj->getCrtRefusedCount($crtpartId);
           $refusedcountupdated = ++$refused_count[0]['refused_count'];
           $CorrectorMail= $crtparticipate_obj->getCorrectorDetails($crtpartId);///to get writer id
           $CorrectorMail = $CorrectorMail[0]['email'];
           $paticipateTableId= $crtparticipate_obj->getParticipateId($crtpartId); //getting the paticipation table id
           $partId =   $paticipateTableId[0]['participate_id'];
           $recentversion= $artProcess_obj->getRecentVersion($partId);  //with paticipation tabel id
           $correctorId = $crtparticipate_obj->getCorrectorId($crtpartId, $artId);
           $correctorId = $correctorId[0]['corrector_id'];
           if(isset($s2art_params['s2art_corrector_disapprove'])) ///when corrector refused by editor temporaryly
           {
               $profiletype = $user_obj->getAllUsersDetails($correctorId);
               $delivery_details=$delivery_obj->getArtDeliveryDetails($artId);
               if($profiletype[0]['type2'] == 'corrector')
               {
                   $resubtime = $this->correctorResubmitTime($artId, $correctorId);
               }
               else
               {
                   $resubtime = $this->writerResubmitTime($artId, $correctorId);
               }
               $parameters['resubmit_time']= $resubtime;
               // $subtime = $deldetails[0]['correction_resubmission'];//2days
               $expires=time()+(60*$resubtime);
               // $expires=time()+(60*60*$subtime);
               $data = array("status"=>"disapproved", "current_stage"=>"corrector", "corrector_submit_expires"=>$expires, "refused_count"=>$refusedcountupdated);////////updating
               $query = "id= '".$crtpartId."'";
               $crtparticipate_obj->updateCrtParticipation($data,$query);
               ///////updating the participation table ///////
               $data = array("status"=>"under_study", "current_stage"=>"corrector", "marks"=>$recentversion[0]['marks']);////////updating
               $query = "id= '".$partId."'";
               $participate_obj->updateParticipation($data,$query);
               /////udate status article process table///////
               $this->insertStageRecord($partId,$recentversion[0]["version"],'s2','disapproved');
               $recentversion= $artProcess_obj->getRecentVersion($partId);
               $data = array("marks"=>$s2art_params["marks"], "comments"=>$s2art_params["marks_comments"]);////////updating
               $query = "participate_id= '".$partId."' AND version='".$recentversion[0]['version']."'";
               $artProcess_obj->updateArticleProcess($data,$query);
               /////////////article history////////////////
               $actparams['artId'] = $artId;
               $actparams['stage'] = "stage2";
               $actparams['action'] = "refused";
               $this->articleHistory(27,$actparams);
               /////////////end of article history////////////////
               /// unlock the article///////////////
               $this->unlockonactionAction($artId);
               ///////////sending mail to corrector that document has been  rejected///
               $Message = stripslashes($s2art_params["commentsCrtRefuse"]) ;
               $body=preg_replace('/\t/','',$Message);
               $mail = new Zend_Mail();
               $mail->addHeader('Reply-To', $this->configval['mail_from']);
               $mail->setBodyHtml($body)
                   ->setFrom($this->configval['mail_from'])
                   ->addTo($CorrectorMail)
                   ->setSubject('Correction refus&eacute;e par Edit-place');
               if($mail->send())
               {
                   $Object = 'Correction refus&eacute;e par Edit-place';
                   $autoEmails->sendMailEpMailBox($correctorId,$Object,$body);  ///sending mail to EP account
                   $this->_helper->FlashMessenger(utf8_decode('Article refused successfully.'));
                   $this->_redirect("/proofread/stage-deliveries?submenuId=ML3-SL3");
               }
           }
           else if(isset($s2art_params['s2art_corrector_permdisapprove'])) ///when corrector refused by editor permanently
           {
               if($s2art_params['sendtofo'] == 'yes')
               {
                   ///////check the cycle count in participation tabel and increament//////////
                   $cycleCount = $crtparticipate_obj->getCrtParticipationCycles($artId);
                   $cycleCount = $cycleCount[0]['cycle']+1;
                   /////udate status currector participation table with article id///////
                   $data = array("cycle"=>$cycleCount);////////updating
                   $query = "article_id= '".$artId."' and cycle=0";
                   $crtparticipate_obj->updateCrtParticipation($data,$query);

                   $this->CorrectorParticipationExpire($artId);
                   ///////updating the participation table ///////
                   $data = array("status"=>"under_study", "current_stage"=>"corrector");////////updating
                   $query = "id= '".$partId."'";
                   $participate_obj->updateParticipation($data,$query);

                   if($s2art_params["anouncebyemail"] == 'yes')
                   {
                       $this->sendMailToCorrectors($artId);
                   }
               }
               else
               {
                   /////udate status article table///////
                   $data = array("send_to_fo"=>"no", "correction"=>"no");////////updating
                   $query = "id= '".$artId."'";
                   $article_obj->updateArticle($data,$query);
                   ///////updating the participation table ///////
                   $data = array("status"=>"under_study", "current_stage"=>"stage1");////////updating
                   $query = "id= '".$partId."'";
                   $participate_obj->updateParticipation($data,$query);
               }
               $data = array("status"=>"closed", "current_stage"=>"stage2", "refused_count"=>$refusedcountupdated);////////updating
               $query = "id= '".$crtpartId."'";
               $crtparticipate_obj->updateCrtParticipation($data,$query);
               /////udate status article process table///////
               $this->insertStageRecord($partId,$recentversion[0]["version"],'s2','closed');
               $recentversion= $artProcess_obj->getRecentVersion($partId);
               $data = array("marks"=>$s2art_params["marks"], "comments"=>$s2art_params["marks_comments"]);////////updating
               $query = "participate_id= '".$partId."' AND version='".$recentversion[0]['version']."'";
               $artProcess_obj->updateArticleProcess($data,$query);
               /////////////article history////////////////
               $actparams['artId'] = $artId;
               $actparams['stage'] = "stage2";
               $actparams['action'] = "refused definite";
               $this->articleHistory(26,$actparams);
               /////////////end of article history////////////////
               /// unlock the article///////////////
               $this->unlockonactionAction($artId);
               ///////////sending mail to contributor that document has been  rejected///
               //$Message = stripslashes($s2art_params["commentsCrtRefuse_".$crtpartId]) ;
               $Message = stripslashes($s2art_params["commentsCrtRefuse"]) ;
               $body=preg_replace('/\t/','',$Message);
               $mail = new Zend_Mail();
               $mail->addHeader('Reply-To', $this->configval['mail_from']);
               $mail->setBodyHtml($body)
                   ->setFrom($this->configval['mail_from'])
                   ->addTo($CorrectorMail)
                   ->setSubject('Correction refus&eacute;e par Edit-place');
               if($mail->send())
               {
                   $Object = 'Refus  definitif - Edit-place';
                   //$Object = $s2art_params["commentsCrtRefuseObject"];
                   $autoEmails->sendMailEpMailBox($correctorId,$Object,$body);  ///sending mail to EP account
                   $this->_helper->FlashMessenger(utf8_decode('Article refused successfully.'));
                   $this->_redirect("/proofread/stage-deliveries?submenuId=ML3-SL3");
               }
           }
       }
        else
        {
            $this->render('proofread_stage2ebookerscorrection');
        }
   }
    /* end of added by  naseer on 14-08-2015*/
    /* added by  naseer on 06-10-2015*/
    //functio to redirect if there are Translation stencils in the delivery //
    public function stage2EbookersTranslationCorrectionAction(){
        //echo "<pre>";print_r($_REQUEST);exit;
        $url = getenv('REQUEST_URI');
        $artId = $this->_request->getParam('articleId');
        $participId = $this->_request->getParam('participateId');    //this Id is from Participation table not CorrectorParticicpation table//
        $participate_obj = new EP_Participation_Participation();
        $crtparticipate_obj  = new Ep_Participation_CorrectorParticipation();
        $autoEmails = new Ep_Message_AutoEmails();
        $article_obj = new EP_Delivery_Article();
        $options_obj = new Ep_Delivery_Options();
        $del_obj = new Ep_Delivery_Delivery();
        $artProcess_obj = new EP_Delivery_ArticleProcess();
        $details = $article_obj->getArticleDetails($artId);
        $royalty_obj=new Ep_Payment_Royalties();
        $stencil_obj = new Ep_Ebookers_Stencils();
        $userplus_obj=new Ep_User_UserPlus();
        //fetch particpator info and corrector's info//
        $partUserInfo = $userplus_obj->getPartUserinfo($participId);
        $this->_view->partUserInfo = $partUserInfo;
        $crtPartUserInfo = $userplus_obj->getCrtPartUserinfo($participId);
        $this->_view->crtPartUserInfo = $crtPartUserInfo;
        /////if user did not lock the perticular article it should not allow him to see detials/////////
        $lockstatus = $this->checklockAction($artId);
        $template_obj = new Ep_Message_Template();
        if($lockstatus == 'no')
            $this->_redirect("/proofread/stage-deliveries?submenuId=ML3-SL3");

        $s2art_params=$this->_request->getParams();
        //print_r($s2art_params);exit;
        foreach ($details as $key => $value) {
            $details[$key]['type']=$this->type_array[$details[$key]['type']];
            //$details[$key]['sign_type']=$this->signtype_array[$details[$key]['sign_type']];
            $details[$key]['signtype']=$details[$key]['sign_type'];
        }
        $aoId = $details[0]['deliveryId'];
        $this->_view->articledetails=$details;
        //////pop up displays/////
        $res = $template_obj->refuseValidTemplates('null', $details[0]['product']);
        $this->_view->refusevalidtemps = $res;
        $this->_view->selectedrefusalreasons = explode("|",$details[0]['refusalreasons']);
        $refualreasonContent = "";
        for($i=0; $i<count($res); $i++)
        {
            if(in_array($res[$i]['identifier'], $this->_view->selectedrefusalreasons))
            {
                $refualreasonContent.= $res[$i]['content']."<br><br>";
            }
        }
        $this->_view->refualreasonContent = $refualreasonContent;
        //display article process grid in s1 correction/////////

        $partsOfArt = $participate_obj->getAllParticipationsStage($artId, 'stage2');
        $partId = $partsOfArt[0]['id'];
        $this->_view->partId = $partId;
        $versions_details = $artProcess_obj->getVersionDetails($partId);
        //fetch the respective stenciles from  validstenciles//
        $translateStencils = $stencil_obj->getStencilstoTranslate($artId);
        $i=0;
        while($versions_details[$i]['id']){
            $versions_details[$i]['article_doc_content'] = explode('###$$$###',$versions_details[$i]['article_doc_content']);
            $i++;
        }
        $getEbookersTokenIds = $article_obj->getEbookersTokenIds($artId);
        $this->_view->token_ids = $getEbookersTokenIds[0]['ebooker_tokenids'];
        $this->_view->versions_details = $versions_details;
        $this->_view->translateStencils = $translateStencils;
        $versions_user_details = $artProcess_obj->getRecentVersionId($partId);
        $this->_view->versions_user_details = $versions_user_details;
        $versions_contributor_details = $artProcess_obj->getRecentContributorVersionId($partId);
        $this->_view->versions_contributor_details = $versions_contributor_details;
        $recentversion = $artProcess_obj->getRecentVersion($partId);
        //print_r($recentversion);
        if($recentversion[0]['reasons_marks'] == '')
        {
            $rreasons = explode("|",$details[0]['refusalreasons']);
            if(count($rreasons) != 0)
            {
                foreach($rreasons as $keys)
                {
                    $res = $template_obj->refuseValidTemplatesOnId($keys);
                    $refreason[$keys]  = $res[0]['title'];
                }
            }else{
                $refreason[0]  = 'overall rating';
            }
            $this->_view->rreasons=$refreason;
            $this->_view->s1reasons=$refreason;
            $s1marks=array_fill(0, 10, 0);
            $this->_view->reasonslist=implode(",",$s1marks);
            $this->_view->s1markscount=0;
            $this->_view->avgs1marks=0;
            $this->_view->previousdetails=$recentversion[0]['reasons_marks'];
            $this->_view->rreasonscount=count($refreason)*2;
            if($recentversion[0]['stage'] == 'corrector') // if  prevous version isn from corrector then  desable the rating and commntes
                $this->_view->versionfromCorrector = 'true';
            else
                $this->_view->versionfromCorrector = 'false';
        }
        else
        {
            $rreasons = explode(",", $recentversion[0]['reasons_marks']); //print_r($rreasons);
            $s1reasons = array();
            if(count($rreasons) != 0)
            {
                for ($i = 0; $i < count($rreasons); $i++)
                {
                    $array = explode("|", $rreasons[$i]);
                    $res = $template_obj->refuseValidTemplatesOnId(trim($array[0]));
                    $s1reasons[$array[0]] = $res[0]['title'];
                    //$s1reasons[$i] = $array[0];
                    $s1marks[$i] = $array[1];
                }
            }else{
                $s1reasons[0] = 'overall rating';
            }
            $this->_view->s1reasons=$s1reasons;
            $this->_view->s1marks=$s1marks;
            $this->_view->reasonslist=implode(",",$s1marks);
            // $this->_view->s1markscount=array_sum($s1marks);
            $this->_view->s1markscount=$recentversion[0]['marks'];
            $this->_view->avgs1marks=$recentversion[0]['marks'];
            $this->_view->previousdetails=$recentversion[0]['reasons_marks'];
            $this->_view->rreasonscount=count($rreasons)*2;
            if($recentversion[0]['stage'] == 'corrector') // if  prevous version isn from corrector then  desable the rating and commntes
                $this->_view->versionfromCorrector = 'true';
            else
                $this->_view->versionfromCorrector = 'false';
        }
        //display article process grid in s1 correction/////////
        if($details[0]['correction'] == 'yes')
        {
            $crtpartsOfArt = $crtparticipate_obj->getAllCrtParticipationsStage2($artId);
            $crtpartId = $crtpartsOfArt[0]['id'];
            $this->_view->crtpartId = $crtpartId;
        }
        ////getting the refuse count to change the button refuse to close//////
        $refused_count= $participate_obj->getRefusedCount($partId);
        $this->_view->refused_count = $refused_count[0]['refused_count'];
        $deldetails =  $del_obj->getDeliveryDetails($aoId);///to display in services box///
        $this->_view->deldetails=  $deldetails;
        $this->_view->options=$options_obj->getOptions($aoId);///to display in services box///
        $this->_view->res_seltdopts = $this->getDelSelectedOption($aoId); ///option selected for the delivery
        ///check whether the marks given in the stage1 correction phase////
        $specsmarks = $artProcess_obj->getRecentVersionByTime($partId);// echo $specsmarks[0]['marks']; exit;///to display in services box///
        $this->_view->marksgiven = $specsmarks[0]['marks'];
        if(isset($s2art_params["s2art_approve"]) && $s2art_params["s2art_approve"]=='yes')//from s2 correction and writerarts pages/
        {
            if($s2art_params['marks'] == '')
                $s2art_params['marks'] = 0;
            if($s2art_params["markswithreason"] == '')
                $s2art_params["markswithreason"] = 0;
            $partId = $s2art_params["participateId"] ;
            $crtpartId = $s2art_params["crtparticipateId"] ;
            $recentversion= $artProcess_obj->getRecentVersion($partId);
            if($s2art_params["participateType"] == "normalParticipation")
            {
                $Message = $s2art_params["commentsValidate_".$partId] ;
            }
            elseif($s2art_params["participateType"] == "correctorParticipation"){

                $Message = $s2art_params["commentsCrtValidate_".$crtpartId] ;
            }
            $recentDetials = $artProcess_obj->getVersionDetailsByVersion($partId, $recentversion[0]["version"]);
            ////file upload code///////////
            if($_FILES['art_doc_'.$partId]['tmp_name'] != '')
            {
                $tmpName = $_FILES['art_doc_'.$partId]['tmp_name'];
                $artName = $_FILES['art_doc_'.$partId]['name'];
                $ext = explode('.',$artName);
                $extension = $ext[1];
                $art_path = $artId."_".$this->adminLogin->userId."_".rand(10000, 99999).".".$extension;
                $artProcess_obj->participate_id=$partId ;
                $artProcess_obj->user_id=$this->adminLogin->userId;
                $artProcess_obj->stage='s2' ;
                $artProcess_obj->status='approved' ;
                $artProcess_obj->marks=$s2art_params["markstotal"] ;
                $artProcess_obj->reasons_marks=$s2art_params["markswithreason"] ;
                $artProcess_obj->comments=$s2art_params["marks_comments"] ;
                $artProcess_obj->article_path=$artId."/".$art_path ;
                $artProcess_obj->article_name=$artName ;
                $version = $recentversion[0]["version"]+1;
                $artProcess_obj->version=$version ;
                $artProcess_obj->plagxml='' ;
                $artProcess_obj->art_file_size_limit_email ='' ;

                //////////////////uploading the document///////////////////////////////
                $server_path = "/home/sites/site7/web/FO/articles/";
                $articleDir = $server_path.$artId;
                $newfile = $articleDir."/".$art_path;
                if (move_uploaded_file($tmpName, $newfile))
                {
                    //Antiword obj to get content from uploaded article
                    $antiword_obj=new Ep_Antiword_Antiword($newfile);
                    $artProcess_obj->article_doc_content=$antiword_obj->getContent();
                    $artProcess_obj->article_words_count=$antiword_obj->count_words($artProcess_obj->article_doc_content);
                    $artProcess_obj->insert();
                }
            }
            else{
                ///inserting a record into artcleprocess tabel///
                $this->insertStageRecord($partId,$recentversion[0]["version"],'s2','approved');
                /////udate status article process table///////
                $recentversion= $artProcess_obj->getRecentVersion($partId);
                /*added by naseer  */
                //saving individual stencils in data base//
                $results = $stencil_obj->getStencilsDetails($artId,$partId);
                for($k=0;$k<count($s2art_params['stencil_text']);$k++)
                {
                    $stencil_data = array("stencil_content"=>$s2art_params['stencil_text'][$k],"translation_id"=>$s2art_params['translateStencilsId'][$k],"article_id"=>$artId,"delivery_id"=>$aoId,"token_ids"=>$s2art_params['token_ids'],"language"=>$results[0]['lang']);//,"language "=>$results[0]['language ']
                    $stencil_obj->insertStencil($stencil_data);
                }
                //concatinating the sencile before inserting//
                $article_doc_content =implode("###$$$###", $s2art_params['stencil_text'] );
                //code to create a article file in fo//
                $articleDir=FO_ARTICLE_PATH;
                if(!is_dir($articleDir))
                    mkdir($articleDir,TRUE);
                chmod($articleDir,0777);
                $articleName=$artId."_".$this->adminLogin->userId."_".mt_rand(10000,99999).".txt";
                $article_path = $artId."/".$articleName;
                $article_full_path=$articleDir.$article_path;

                //create text stencils file
                $stencil_file = fopen($article_full_path,"w");
                fwrite($stencil_file,$article_doc_content);
                fclose($stencil_file);
                $article_name = 'Stencils_file_'.$artId.'.txt';
                $data = array("marks"=>$s2art_params["markstotal"], "reasons_marks"=>$s2art_params["markswithreason"], "comments"=>$s2art_params["marks_comments"],"article_doc_content"=>utf8_decode($article_doc_content),"article_path"=>$article_path,"article_name"=>$article_name);////////updating
                $query = "participate_id= '".$partId."' AND version='".$recentversion[0]['version']."'";
                $artProcess_obj->updateArticleProcess($data,$query);
                /////udate status article process table///////
                /* $data = array("status"=>"approved", "stage"=>'s2');////////updating
                 $query = "participate_id= '".$partId."' AND version='".$recentversion[0]['version']."'";
                 $artProcess_obj->updateArticleProcess($data,$query);*/
            }
            $artId = $this->_request->getParam('articleId');

            ////udate status participation table for stage///////
            $premium=$del_obj->checkPremiumAO($artId);
            $paricipationdetails=$participate_obj->getParticipateDetails($partId);

            if($premium=='NO')
                $data = array("current_stage"=>"client", "status"=>"under_study", "marks"=>$recentversion[0]['marks']);////////updating
            else
            {
                $data = array("current_stage"=>"client", "status"=>"published", "marks"=>$recentversion[0]['marks']);////////updating

                if($royalty_obj->checkRoyaltyExists($paricipationdetails[0]['article_id'])=='NO')
                {
                    $royalty_obj->participate_id=$paricipationdetails[0]['participateId'];
                    $royalty_obj->article_id=$paricipationdetails[0]['article_id'];
                    $royalty_obj->user_id=$paricipationdetails[0]['user_id'];
                    $royalty_obj->price=$paricipationdetails[0]['price_user'];
                    $royalty_obj->correction="no";
                    $royalty_obj->currency=$paricipationdetails[0]['currency'];
                    $royalty_obj->insert();
                    if($s2art_params["participateType"] == "correctorParticipation")
                    {
                        $royalty_obj=new Ep_Payment_Royalties();
                        $crtparicipationdetails=$crtparticipate_obj->getCrtParticipateDetails($crtpartId);
                        if($crtparicipationdetails != 'NO')
                        {
                            $royalty_obj->participate_id=$partId;
                            $royalty_obj->crt_participate_id=$crtpartId;
                            $royalty_obj->article_id=$crtparicipationdetails[0]['article_id'];
                            $royalty_obj->user_id=$crtparicipationdetails[0]['corrector_id'];
                            $royalty_obj->price=$crtparicipationdetails[0]['price_corrector'];
                            $royalty_obj->correction="yes";
                            $royalty_obj->currency=$paricipationdetails[0]['currency'];
                            $royalty_obj->insert();
                        }
                    }
                }
            }
            $query = "article_id= '".$artId."' AND id = '".$partId."'";
            //edited by naseer on 18-09-2015
            $participate_obj->updateParticipation($data,$query);
            if($s2art_params["participateType"] == "correctorParticipation")
            {
                if($premium=='NO')
                    $data1 = array("current_stage"=>"client", "status"=>"under_study");////////updating
                else
                    $data1= array("current_stage"=>"client", "status"=>"published");////////updating

                $query = "article_id= '".$artId."' AND id = '".$crtpartId."'";
                //edited by naseer on 18-09-2015
                $crtparticipate_obj->updateCrtParticipation($data1,$query);
            }

            ///////update in article///////////
            $data = array("file_path"=>$recentversion[0]['article_path']);////////updating
            $query = "id= '".$artId."'";
            $article_obj->updateArticle($data,$query);




            /* *Sending Mails ***/
            /////////send mail to contributor///////////////////////////////////////
            $paricipationdetails=$participate_obj->getParticipateDetails($partId);
            $contribDetails=$autoEmails->getContribUserDetails($paricipationdetails[0]['user_id']);
            ///////////////if user is sub-junior then update him to jc/////////
            $user_obj=new Ep_User_User();
            $userDetails =  $user_obj->getAllUsersDetails($paricipationdetails[0]['user_id']);
            if($userDetails[0]['profile_type'] == "sub-junior")
            {
                $data = array("profile_type"=>"junior");////////updating
                $query = "identifier = '".$userDetails[0]['identifier']."' ";
                $user_obj->updateUser($data,$query);
            }

            if($contribDetails[0]['firstname']!=NULL)
                $parameters['contributor_name']= $contribDetails[0]['firstname']." ".$contribDetails[0]['lastname'];
            else
                $parameters['contributor_name']= $contribDetails[0]['email'];

            $parameters['created_date']=date("d/m/Y",strtotime($paricipationdetails[0]['created_at']));
            $parameters['document_link']="/client/ongoingao";
            $parameters['invoice_link']="/client/invoice";
            if($paricipationdetails[0]['currency']=='euro')
                $curr='&euro;';
            else
                $curr='&pound;';
            $parameters['royalty']=$paricipationdetails[0]['price_user'].$curr;
            //sendidng the mail to contirbutor //
            $automail=new Ep_Message_AutoEmails();
            $email=$automail->getAutoEmail(53);
            $Object=$email[0]['Object'];
            $receiverId = $paricipationdetails[0]['user_id'];
            $autoEmails->sendMailEpMailBox($receiverId,$Object,$Message);

            /////////send mail to corrector/////////////////////////////////
            if($s2art_params["participateType"] == "correctorParticipation")
            {
                $paricipationdetails=$crtparticipate_obj->getCrtParticipateDetails($crtpartId);

                $correctorDetails=$autoEmails->getContribUserDetails($paricipationdetails[0]['corrector_id']);
                if($correctorDetails[0]['firstname']!=NULL)
                    $parameters['corrector_name']= $correctorDetails[0]['firstname']." ".$correctorDetails[0]['lastname'];
                else
                    $parameters['corrector_name']= $correctorDetails[0]['email'];

                $parameters['created_date']=date("d/m/Y",strtotime($paricipationdetails[0]['created_at']));
                $parameters['document_link']="/client/ongoingao";
                $parameters['invoice_link']="/client/invoice";
                $parameters['royalty']=$paricipationdetails[0]['price_corrector'];
                $parameters['article_title']= $paricipationdetails[0]['title'];
                $parameters['articlename_link']="/contrib/mission-deliver?article_id=".$artId;
                //sendidng the mail to corrector //
                $receiverId = $paricipationdetails[0]['corrector_id'];
                $autoEmails->messageToEPMail($receiverId,59,$parameters);
                // print_r($paricipationdetails);exit;
            }
            /////////////article history////////////////
            $actparams['artId'] = $artId;
            $actparams['stage'] = "stage2";
            $actparams['action'] = "validated";
            $this->articleHistory(25,$actparams);
            /////////////end of article history////////////////
            /* *sending mail to Client**/
            $clientDetails=$autoEmails->getUserDetails($paricipationdetails[0]['clientId']);
            if($clientDetails[0]['username']!=NULL)
                $parameters['client_name']= $clientDetails[0]['username'];
            else
                $parameters['client_name']= $clientDetails[0]['email'];
            if($deldetails[0]['mail_send']=='yes')
            {
                // $this->messageToEPMail($paricipationdetails[0]['clientId'],1,$parameters);
            }
            //Insert Recent Activities
            $recent_acts_obj=new Ep_User_RecentActivities();
            $ract=array("type" => "bopublish","user_id"=>$paricipationdetails[0]['clientId'],"activity_by"=>"bo","article_id"=>$artId);
            $recent_acts_obj->insertRecentActivities($ract);
            $deliveryId=$del_obj->getDeliveryID($artId);
            if($deliveryId!="NO")
            {
                $checkLastAO=$del_obj->checkLastArticleAO($deliveryId);
                if($checkLastAO=="YES")
                {
                    //sending the mail to client when last alrticle is validated;
                    if($deldetails[0]['mail_send']=='yes')
                    {
                        //  $this->messageToEPMail($paricipationdetails[0]['clientId'],12,$parameters);
                    }
                    ///////////////////////////////////////////
                    $delcreateduser=$del_obj->getDelCreatedUser($deliveryId);

                    $object="L'appel d'offres ".$delcreateduser[0]['title']." est complete; ";
                    $text_mail="<p>Cher ".$delcreateduser[0]['first_name']." ,<br><br>
                                        Le dernier article de l'appel d'offres ".$delcreateduser[0]['title']." vient d'&ecirc;tre valid&eacute;!<br><br>
                                        Merci de cliquer ici pour acc&eacute;der &agrave; la page de suivi de l'AO.<br><br>
                                        Cordialement,<br>
                                        <br>
                                        Toute l'&eacute;quipe d&rsquo;Edit-place</p>";
                    $mail = new Zend_Mail();
                    $mail->addHeader('Reply-To',$this->configval['mail_from']);
                    $mail->setBodyHtml($text_mail)
                        ->setFrom($this->configval['mail_from'])
                        ->addTo($delcreateduser[0]['email'])
                        ->setSubject($object);
                    if($mail->send())
                    {
                        $this->_helper->FlashMessenger(utf8_decode('Article Approuvé avec succès'));
                        $this->_redirect("/proofread/stage-deliveries?submenuId=ML3-SL3");
                    }
                }
                $this->_redirect("/proofread/stage-deliveries?submenuId=ML3-SL3");
            }

            $this->_helper->FlashMessenger(utf8_decode('Article Approved successfully'));
            $this->_redirect("/proofread/stage-deliveries?submenuId=ML3-SL3");
        }
        else if(isset($s2art_params['s2art_corrector_disapprove']) || isset($s2art_params['s2art_corrector_permdisapprove']))
        {
            $crtparticipate_obj  = new Ep_Participation_CorrectorParticipation();
            $crtpartId = $s2art_params["crtparticipateId"] ;
            //$partId = $s2art_params["participateId"] ;
            $delivery_obj=new Ep_Delivery_Delivery();
            $user_obj = new Ep_User_User();
            $refused_count= $crtparticipate_obj->getCrtRefusedCount($crtpartId);
            $refusedcountupdated = ++$refused_count[0]['refused_count'];
            $CorrectorMail= $crtparticipate_obj->getCorrectorDetails($crtpartId);///to get writer id
            $CorrectorMail = $CorrectorMail[0]['email'];
            $paticipateTableId= $crtparticipate_obj->getParticipateId($crtpartId); //getting the paticipation table id
            $partId =   $paticipateTableId[0]['participate_id'];
            $recentversion= $artProcess_obj->getRecentVersion($partId);  //with paticipation tabel id
            $correctorId = $crtparticipate_obj->getCorrectorId($crtpartId, $artId);
            $correctorId = $correctorId[0]['corrector_id'];
            if(isset($s2art_params['s2art_corrector_disapprove'])) ///when corrector refused by editor temporaryly
            {
                $profiletype = $user_obj->getAllUsersDetails($correctorId);
                $delivery_details=$delivery_obj->getArtDeliveryDetails($artId);
                if($profiletype[0]['type2'] == 'corrector')
                {
                    $resubtime = $this->correctorResubmitTime($artId, $correctorId);
                }
                else
                {
                    $resubtime = $this->writerResubmitTime($artId, $correctorId);
                }
                $parameters['resubmit_time']= $resubtime;
                // $subtime = $deldetails[0]['correction_resubmission'];//2days
                $expires=time()+(60*$resubtime);
                // $expires=time()+(60*60*$subtime);
                $data = array("status"=>"disapproved", "current_stage"=>"corrector", "corrector_submit_expires"=>$expires, "refused_count"=>$refusedcountupdated);////////updating
                $query = "id= '".$crtpartId."'";
                $crtparticipate_obj->updateCrtParticipation($data,$query);
                ///////updating the participation table ///////
                $data = array("status"=>"under_study", "current_stage"=>"corrector", "marks"=>$recentversion[0]['marks']);////////updating
                $query = "id= '".$partId."'";
                $participate_obj->updateParticipation($data,$query);
                /////udate status article process table///////
                $this->insertStageRecord($partId,$recentversion[0]["version"],'s2','disapproved');
                $recentversion= $artProcess_obj->getRecentVersion($partId);
                $data = array("marks"=>$s2art_params["marks"], "comments"=>$s2art_params["marks_comments"]);////////updating
                $query = "participate_id= '".$partId."' AND version='".$recentversion[0]['version']."'";
                $artProcess_obj->updateArticleProcess($data,$query);
                /////////////article history////////////////
                $actparams['artId'] = $artId;
                $actparams['stage'] = "stage2";
                $actparams['action'] = "refused";
                $this->articleHistory(27,$actparams);
                /////////////end of article history////////////////
                /// unlock the article///////////////
                $this->unlockonactionAction($artId);
                ///////////sending mail to corrector that document has been  rejected///
                $Message = stripslashes($s2art_params["commentsCrtRefuse"]) ;
                $body=preg_replace('/\t/','',$Message);
                $mail = new Zend_Mail();
                $mail->addHeader('Reply-To', $this->configval['mail_from']);
                $mail->setBodyHtml($body)
                    ->setFrom($this->configval['mail_from'])
                    ->addTo($CorrectorMail)
                    ->setSubject('Correction refus&eacute;e par Edit-place');
                if($mail->send())
                {
                    $Object = 'Correction refus&eacute;e par Edit-place';
                    $autoEmails->sendMailEpMailBox($correctorId,$Object,$body);  ///sending mail to EP account
                    $this->_helper->FlashMessenger(utf8_decode('Article refused successfully.'));
                    $this->_redirect("/proofread/stage-deliveries?submenuId=ML3-SL3");
                }
            }
            else if(isset($s2art_params['s2art_corrector_permdisapprove'])) ///when corrector refused by editor permanently
            {
                if($s2art_params['sendtofo'] == 'yes')
                {
                    ///////check the cycle count in participation tabel and increament//////////
                    $cycleCount = $crtparticipate_obj->getCrtParticipationCycles($artId);
                    $cycleCount = $cycleCount[0]['cycle']+1;
                    /////udate status currector participation table with article id///////
                    $data = array("cycle"=>$cycleCount);////////updating
                    $query = "article_id= '".$artId."' and cycle=0";
                    $crtparticipate_obj->updateCrtParticipation($data,$query);

                    $this->CorrectorParticipationExpire($artId);
                    ///////updating the participation table ///////
                    $data = array("status"=>"under_study", "current_stage"=>"corrector");////////updating
                    $query = "id= '".$partId."'";
                    $participate_obj->updateParticipation($data,$query);

                    if($s2art_params["anouncebyemail"] == 'yes')
                    {
                        $this->sendMailToCorrectors($artId);
                    }
                }
                else
                {
                    /////udate status article table///////
                    $data = array("send_to_fo"=>"no", "correction"=>"no");////////updating
                    $query = "id= '".$artId."'";
                    $article_obj->updateArticle($data,$query);
                    ///////updating the participation table ///////
                    $data = array("status"=>"under_study", "current_stage"=>"stage1");////////updating
                    $query = "id= '".$partId."'";
                    $participate_obj->updateParticipation($data,$query);
                }
                $data = array("status"=>"closed", "current_stage"=>"stage2", "refused_count"=>$refusedcountupdated);////////updating
                $query = "id= '".$crtpartId."'";
                $crtparticipate_obj->updateCrtParticipation($data,$query);
                /////udate status article process table///////
                $this->insertStageRecord($partId,$recentversion[0]["version"],'s2','closed');
                $recentversion= $artProcess_obj->getRecentVersion($partId);
                $data = array("marks"=>$s2art_params["marks"], "comments"=>$s2art_params["marks_comments"]);////////updating
                $query = "participate_id= '".$partId."' AND version='".$recentversion[0]['version']."'";
                $artProcess_obj->updateArticleProcess($data,$query);
                /////////////article history////////////////
                $actparams['artId'] = $artId;
                $actparams['stage'] = "stage2";
                $actparams['action'] = "refused definite";
                $this->articleHistory(26,$actparams);
                /////////////end of article history////////////////
                /// unlock the article///////////////
                $this->unlockonactionAction($artId);
                ///////////sending mail to contributor that document has been  rejected///
                //$Message = stripslashes($s2art_params["commentsCrtRefuse_".$crtpartId]) ;
                $Message = stripslashes($s2art_params["commentsCrtRefuse"]) ;
                $body=preg_replace('/\t/','',$Message);
                $mail = new Zend_Mail();
                $mail->addHeader('Reply-To', $this->configval['mail_from']);
                $mail->setBodyHtml($body)
                    ->setFrom($this->configval['mail_from'])
                    ->addTo($CorrectorMail)
                    ->setSubject('Correction refus&eacute;e par Edit-place');
                if($mail->send())
                {
                    $Object = 'Refus  definitif - Edit-place';
                    //$Object = $s2art_params["commentsCrtRefuseObject"];
                    $autoEmails->sendMailEpMailBox($correctorId,$Object,$body);  ///sending mail to EP account
                    $this->_helper->FlashMessenger(utf8_decode('Article refused successfully.'));
                    $this->_redirect("/proofread/stage-deliveries?submenuId=ML3-SL3");
                }
            }
        }
        else
        {
            $this->render('proofread_stage2ebookerstranslationcorrection');
        }
    }

    /* end of added by  naseer on 06-10-2015*/
	
	/********************** Delivered update in stages *********************************/
	public function deliveryupdateAction()
	{
		$article_obj = new EP_Delivery_Article();
        $artProcess_obj = new EP_Delivery_ArticleProcess();
		if($_REQUEST['validate_art'])
		{
			$validateDetails = explode('|', $_REQUEST['validate_art']) ;
            $respone= array();
            $bulkvalidatestage_flag = false;
            $status_flag = false;
            foreach($validateDetails as $validateDetail)
			{
                $validateArt_ = explode('_', $validateDetail) ;
				$artId = $validateArt_[0];
                $partId = $validateArt_[1];
                $articledetails = $article_obj->getArticleDetails($artId);
				if($articledetails[0]['delivered'] === 'yes'){
                    //echo "Delivery was already been updated";
                    //$respone[$i] = array("status"=>"fail","bulkvalidatestage"=>"no");
                }
                else{
                    $data = array("delivered" => "yes", "delivered_updated_at" => date('Y-m-d H:i:s'), "delivered_updated_by" => $this->adminLogin->userId, "delivered_updated_from" => 'stage' . $_REQUEST['stage']);
                    $query = "id ='" . $artId . "'";
                    $article_obj->updateArticle($data, $query);
                    /* *** added on 18.02.2016 **** */
                    //insert a row in article process table when delivered status is changed to yes//
                    $recentversion= $artProcess_obj->getRecentVersion($partId);
                    $data = array("participate_id"=>$partId,
                        "user_id"=>$this->adminLogin->userId,
                        "stage"=>'s'.$_REQUEST['stage'],
                        "status"=>'delivered',
                        "version"=>$recentversion[0]["version"]+1);
                        $artProcess_obj->insertArticleProcess($data);
                    //end of insert a row in article process table when delivered status is changed to yes//
                    $status_flag = true;
                }
                //if product type is DP/PD then if(stage1){validate it to s1 to s2} and if(stage2){final validate }
                if($articledetails[0]['type'] === 'descriptif_produit' && ($_REQUEST['stage'] === '1' ||$_REQUEST['stage'] === '2') ){//descriptif_produit
                    //$respone[$i] = array("status"=>"success","bulkvalidatestage"=>"yes");
                    $bulkvalidatestage_flag=true;
                }
                elseif($_REQUEST['stage'] === '1' ){
                    $bulkvalidatestage_flag=true;
                }
                else{
                    //$respone[$i] = array("status"=>"success","bulkvalidatestage"=>"no");
                }
			}
            if($bulkvalidatestage_flag) {
                $respone['bulkvalidatestage'] = "yes";
            }
            else{

                $respone['bulkvalidatestage'] = "no";
            }
            if($status_flag){
                $respone['status'] = "success";
            }
            else{
                $respone['status'] =  "fail";
            }
            echo json_encode($respone);
        }
		elseif($_REQUEST['article'])
		{
            $artId = $_REQUEST['article'];
            $partId = $_REQUEST['participateId'];
            $respone= array();
            $validatestage_flag = false;
            $status_flag = false;

            $articledetails = $article_obj->getArticleDetails($_REQUEST['article']);
            if($articledetails[0]['delivered'] === 'yes'){
                //echo "Delivery was already been updated";
                //$respone[$i] = array("status"=>"fail","bulkvalidatestage"=>"no");
            }
            else {
                $data = array("delivered" => "yes", "delivered_updated_at" => date('Y-m-d H:i:s'), "delivered_updated_by" => $this->adminLogin->userId, "delivered_updated_from" => 'stage' . $_REQUEST['stage']);
                $query = "id ='" . $_REQUEST['article'] . "'";
                $article_obj->updateArticle($data, $query);
                /* *** added on 18.02.2016 **** */
                //insert a row in article process table when delivered status is changed to yes//
                $recentversion= $artProcess_obj->getRecentVersion($partId);
                $data = array("participate_id"=>$partId,
                    "user_id"=>$this->adminLogin->userId,
                    "stage"=>'s'.$_REQUEST['stage'],
                    "status"=>'delivered',
                    "version"=>$recentversion[0]["version"]+1);
                $artProcess_obj->insertArticleProcess($data);
                //end of insert a row in article process table when delivered status is changed to yes//
                $status_flag = true;
            }
            if($articledetails[0]['type'] === 'descriptif_produit' ){//descriptif_produit
                //$respone[$i] = array("status"=>"success","bulkvalidatestage"=>"yes");
                $validatestage_flag=true;
            }
            else{
                //$respone[$i] = array("status"=>"success","bulkvalidatestage"=>"no");
            }
            if($validatestage_flag) {
                $respone['validatestage'] = "yes";
            }
            else{

                $respone['validatestage'] = "no";
            }
            if($status_flag){
                $respone['status'] = "success";
            }
            else{
                $respone['status'] =  "fail";
            }
            echo json_encode($respone);

		}
	}
    /* *** added on 18.02.2016 *** */
    //function to reset the devilered realted fields only if it a 'descriptif_produit' product type//
    public function resetDeliveredDetail($artId){
        $article_obj = new EP_Delivery_Article();
        $data = array("delivered" => "", "delivered_updated_at" =>  NULL , "delivered_updated_by" =>  NULL , "delivered_updated_from" =>  NULL );
        $query = "id ='" . $artId . "'";
        $article_obj->updateArticle($data, $query);
    }
    // END OF function to reset the devilered realted fields only if it a 'descriptif_produit' product type//
    /* *** tetsing the downloads *** */
    public function testOnlyAction(){
        include_once (DOWNLOAD_FILES);
        $obj = new downloadFiles();
        //$obj->downloadXlsx();//call the function (file name to be function)

    }
}
