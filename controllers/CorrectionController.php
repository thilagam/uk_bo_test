<?php
/**
 * IndexController - The default controller class
 *
 * @author
 * @version
 */
require_once 'Zend/Controller/Action.php';

class CorrectionController extends Ep_Controller_Action
{
    private $text_admin;
    public function init()
    {
        parent::init();
        $this->_view->lang = $this->_lang;
        $this->adminLogin = Zend_Registry::get('adminLogin');
        $this->sid = session_id();
        ////if session expires/////
        if($this->adminLogin->loginName == '' && $_SERVER['HTTP_X_REQUESTED_WITH']=='XMLHttpRequest') {
            echo "session expired...please <a href='/index'>click here</a> to login"; exit;
        }        
		$this->config=$this->configval;

    }
    public function getSFTPobjectAction()
    {
        if(!is_object($this->sftp)) :
            $this->ssh2_server = "50.116.62.9" ;
            $this->ssh2_user_name = "oboulo" ;
            $this->ssh2_user_pass = "3DitP1ace" ;

            require_once APP_PATH_ROOT.'nlibrary/script/Net/SFTP.php' ;

            $this->sftp = new Net_SFTP($this->ssh2_server);
            if (!$this->sftp->login($this->ssh2_user_name, $this->ssh2_user_pass)) {
                throw new Exception('Login Failed');
            }
        endif ;
    }

    /////////converting minuter to houres
    public function minutesToHours($mins)
    {
        if ($mins < 0) {
            $min = Abs($mins);
        } else {
            $min = $mins;
        }
        $H = Floor($min / 60);
        $M = ($min - ($H * 60)) / 100;
        $hours = $H +  $M;
        if ($mins < 0) {
            $hours = $hours * (-1);
        }
        $expl = explode(".", $hours);
        $H = $expl[0];
        if (empty($expl[1])) {
            //$expl[1] = 00;
        }
        $M = $expl[1];
        if (strlen($M) < 2 && $M) {
            $M = $M . 0;
        }
        if($M)
            $hours = $H . ":" . $M;
        else
            $hours = $H;
        return $hours;
    }
    ////////get all bidded for participations for corrector profile selections////////////////
    public function correctorProfilesListAction()
    {
        $crtparticipate_obj = new Ep_Participation_CorrectorParticipation();
        $delivery_obj    = new Ep_Delivery_Delivery();
        $condition['profilelist'] = $this->configval['selection_profiles'];
        $condition['loginUserId'] = $this->adminLogin->userId;
        $condition['loginUserType'] = $this->adminLogin->type;
        $profile_params=$this->_request->getParams();

        if($profile_params['search'] == 'search')
        {
            $condition['search'] = $profile_params['search'];
            $condition['aoId'] = $profile_params['aoId'];
            $condition['inchargeId'] = $profile_params['inchargeId'];
            $condition['clientId'] = $profile_params['clientId'];
            $condition['closed'] = $profile_params['closed'];
            $condition['startdate'] = $profile_params['startdate'];
            $condition['enddate'] = $profile_params['enddate'];
            /*if($profile_params['closed'] != '0')
            {
                $allaos = $delivery_obj->getAllAos();
                foreach($allaos as $key=>$value)
                {
                    $allList = $crtparticipate_obj->getNotClosedSelectProfiles($value['id']);
                    if($profile_params['closed'] == 'closed')
                    {
                        if($allList == 'yes')
                        {
                            $searchaos[$key] = $value['id'];
                        }
                    }
                    elseif($profile_params['closed'] == 'notclosed')
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
            }*/
        }
        $res = $crtparticipate_obj->correctorProfilesList($condition);
        if($res != 'NO')
        {
            foreach ($res as $key1 => $value1) {
                $crtarts                    = $crtparticipate_obj->getArticlesInCorrection($res[$key1]['id']);
                $res[$key1]['artsincrt']    = $crtarts[0]['artsincrt'];
                $affectart                    = $crtparticipate_obj->getCrtAffectedArticles($res[$key1]['id']);
                $res[$key1]['affectedart']    = $affectart[0]['affectedart'];
                if($profile_params['closed'] == 'closed')
                {
                    if($res[$key1]['artCount'] == $affectart[0]['affectedart'])
                    {
                        $res1[$key1]   = $res[$key1];
                    }
                }
                elseif($profile_params['closed'] == 'all')
                {
                    $res1[$key1]   = $res[$key1];
                }
                else{
                    if($res[$key1]['artCount'] != $affectart[0]['affectedart'])
                    {
                        $res1[$key1]   = $res[$key1];
                    }
                }
                if($res1[$key1]['id'] != '') {
                    $notaffectart                 = $crtparticipate_obj->getCrtNotAffectedArticles($res1[$key1]['id']);
                    $res1[$key1]['notaffectedart'] = $notaffectart;
                    $bidencours                   = $crtparticipate_obj->getCrtBidEncoursArticles($res1[$key1]['id']);
                    $res1[$key1]['bidencours']     = $bidencours[0]['bidencours'];
                    $res1[$key1]['notclosedprofiles']  = $crtparticipate_obj->getNotClosedSelectProfiles($res1[$key1]['id']);
                }

            }
        }
        if ($res1 != "NO")
            $this->_view->paginator = $res1;
        else
            $this->_view->nores = "true";

        $this->_view->render("correction_correctorprofilelist");
    }
    ////////get all bidded for participations for profile selections////////////////
    public function correctorArticleProfilesAction() {
        $participate_obj = new EP_Participation_Participation();
        $crtparticipate_obj = new Ep_Participation_CorrectorParticipation();
        $article_obj     = new EP_Delivery_Article();
        $delivery_obj     = new Ep_Delivery_Delivery();
        $contrib_obj     = new EP_User_Contributor();
        $user_obj     = new Ep_User_User();
        $lock_obj = new Ep_User_LockSystem();
        $partParams      = $this->_request->getParams();
        $aoId            = $partParams['aoId'];
        if ($aoId != NULL) {
            if(isset($partParams['status']))
            {
                $condition['status'] = $partParams['status'];
            }
            $condition['aoId'] = $aoId;
            $res = $article_obj->getArticleDetailsWithAoid($condition);

            $delDetails = $delivery_obj->getPrAoDetails($aoId);
            $userdetials = $user_obj->getAllUsersDetails($delDetails[0]['created_user']);
            $delDetails[0]['created_user'] = $userdetials[0]['first_name'];
            $delDetails[0]['del_category'] = $this->category_array[$delDetails[0]['del_category']];
            $this->_view->delDetails = $delDetails;
            if ($res != "NO") {
                foreach ($res as $key1 => $value1) {
                    $status_array     = '';
                    $status_text      = '';
                    $contributor_text = '';
                    $user_array       = '';
                    $user_text        = '';
                    $status_array     = $crtparticipate_obj->getAllPartsStatusOfArt($res[$key1]['artId']);
                    if ($status_array != 'NO') {
                        foreach ($status_array as $participate_status) {
                            if($participate_status['type2']!='')
                                $status_text.= $participate_status['status']."|".$participate_status['profile_type2']."-".$participate_status['type2'].",";
                            else
                                $status_text.= $participate_status['status']."|".$participate_status['profile_type'].",";
                            if ($participate_status['first_name'] != '')
                                $contirb_name = $participate_status['first_name'] . " " . $participate_status['last_name'];
                            else
                                $contirb_name = $participate_status['email'];
                            $contributor_text .= $participate_status['status'] . "|" . $contirb_name . "|" . $participate_status['identifier'] . ",";
                        }
                    }
                    ////////////////////////////////////////////////////////////
                    $userCount = $crtparticipate_obj->getUserCountInArticle($res[$key1]['artId']);
                    $lastartbacktoFO = $crtparticipate_obj->getlastCrtArticlesBackToFo($res[$key1]['artId']);
                    $lastartrepublish = $participate_obj->getDetailsForRepublish($res[$key1]['artId']);
                    $res[$key1]['lastpartcount']          = $lastartbacktoFO[0]['lastpartcount'];
                    if($lastartrepublish != 'NO'){
                        $res[$key1]['article_id']             = $lastartrepublish[0]['article_id'];
                        $res[$key1]['participate_id']         = $lastartrepublish[0]['id'];
                        $res[$key1]['user_id']                = $lastartrepublish[0]['user_id'];
                        $res[$key1]['repub_status']           = $lastartrepublish[0]['status'];
                        $res[$key1]['part_current_stage']     = $lastartrepublish[0]['current_stage'];  }
                    $res[$key1]['pstatus']                = $status_text;
                    $res[$key1]['contribstatus']          = $contributor_text;
                    $res[$key1]['userCount']              = $userCount[0]['userCount'];
                    $artdetials                           = $article_obj->getArticleDetails($res[$key1]['artId']);
                    $res[$key1]['correction_closed_status'] =  $artdetials[0]['correction_closed_status'];
                    $res[$key1]['price_max']              = $artdetials[0]['price_max'];
                    $res[$key1]['price_min']              = $artdetials[0]['price_min'];
                    $res[$key1]['lockedby_name']          = $lock_obj->getUserLocked($res[$key1]['artId']);
                     ///////////////////////////////////////////////////////////
                    $res[$key1]['pstatus']=$status_text;
                    $res[$key1]['contribstatus']=$contributor_text;

                    /**refused participation Count**/
                    $refused_participations=$crtparticipate_obj->getRefusedCrtPartsCount($res[$key1]['artid']);
                    $res[$key1]['refused_part_count']=$refused_participations;
                    $artdeldetails = $delivery_obj->getPrAoDetailsWithArtid($res[$key1]['artid']);
                    $res[$key1]['aotype']=$artdeldetails[0]['correction_type'];
                    $privatecontribs = explode(",",$artdeldetails[0]['corrector_privatelist']);
                    $res[$key1]['private_correctors']=count($privatecontribs);
                    if($_REQUEST['contribnames'])
                    {
                        if($contribnames!='0')
                        {
                            $contribs = implode(',',$contribnames);
                            $condition1.= " AND up.user_id  IN (".$contribs.")";
                        }
                    }
                    $profilelistparts = $crtparticipate_obj->profilesListParticipation($res[$key1]['artId'], $condition1);

                    //echo "<pre>";print_r($profilelistparts);

                    if($profilelistparts!='NO')
                    {
                        $res[$key1]['corrector_id'] =  $profilelistparts[0]['corrector_id'];
                        $res[$key1]['email'] =  $profilelistparts[0]['email'];
                        $res[$key1]['profile_type'] =  $profilelistparts[0]['profile_type'];
                        $res[$key1]['first_name'] =  $profilelistparts[0]['first_name'];
                        $res[$key1]['last_name'] =  $profilelistparts[0]['last_name'];
                        $res[$key1]['userCount'] =  $profilelistparts[0]['userCount'];
                        $res[$key1]['step'] =  $profilelistparts[0]['step'];
                        $res[$key1]['article_id'] =  $profilelistparts[0]['article_id'];
                        $res[$key1]['price_corrector'] =  $profilelistparts[0]['price_corrector'];
                        $res[$key1]['status'] =  $profilelistparts[0]['status'];
                        $res[$key1]['selection_type'] =  $profilelistparts[0]['selection_type'];
                        $res[$key1]['cycle'] =  $profilelistparts[0]['cycle'];
                        $res[$key1]['cycle0UserCount']        = $profilelistparts[0]['cycle0UserCount'];
                        $res[$key1]['corrector_submit_expires'] =  $profilelistparts[0]['corrector_submit_expires'];
                        $res[$key1]['correction_closed_bo'] =  $profilelistparts[0]['correction_closed_bo'];
                    }
                    else
                        $res[$key1]['cycle0UserCount'] =0;
                }
                $this->_view->statusarray = array('bid','bid_corrector','under_study','dissaproved','on_hold','time_out','published');
                $this->_view->paginator   = $res;
                // echo "<pre>";print_r($res);
            } else
                $this->_view->nores = "true";
            $this->_view->render("correction_correctorarticleprofiles");
        }
    }
    ////////////display pop up with detail of multiple contributors who made biding when the article title is clicked///////////////////
    public function correctorGroupProfilesAction() {
        $usercomment_obj = new Ep_Message_UserComments();
        $contrib_obj     = new EP_User_Contributor();
        $participate_obj = new Ep_Participation_CorrectorParticipation();
        $part_obj = new Ep_Participation_Participation();
        $article_obj     = new EP_Delivery_Article();
        $delivery_obj     = new Ep_Delivery_Delivery();
        $user_obj     = new Ep_User_User();

        $partParams      = $this->_request->getParams();

        $lastartbacktoFO = $participate_obj->getlastArticlesBackToFo($partParams['artId']);
        if ($partParams['artId'] != NULL) {
            $artId        = $partParams['artId'];
            $participants = $participate_obj->getGroupCrtParticipants($artId);
            $delDetails = $delivery_obj->getCrtPrAoDetailsWithArtid($artId);
			
			//simultaneous correction conditions
				$writers=explode(",",$delDetails[0]['article_contribs']);
				if($delDetails[0]['AOtype']== "private" && count($writers)==1)
					$selectedwriter=$delDetails[0]['article_contribs'];
				else
					$selectedwriter=$part_obj->getSelectedwriters($artId);
					
					$this->_view->selectedwriter = $selectedwriter;
				
				//writer status
				$this->_view->writerstatus=$part_obj->getArticleStatus($artId);
				
            $delDetails[0]['art_category'] = $this->category_array[$delDetails[0]['art_category']];
            $this->_view->delDetails = $delDetails;

           /* $partStatus=$participate_obj->articleProfiles($artId);
            if($partStatus=='NO')
                $partStatus=array();
            $this->_view->partStatus = $partStatus;*/
            if(!$_SERVER['HTTP_X_REQUESTED_WITH']=='XMLHttpRequest')///if modal is open directly in url///
            { $this->_redirect("/processao/article-profiles?submenuId=ML2-SL2&aoId=".$delDetails[0]['id']); }

            if ($participants == "NO") {
                $this->_view->contribDetails = NULL;
                $this->_view->render("correction_correctorgroupprofiles");
                exit;
            }

            $noarts = $participate_obj->getCrtPartsCount($artId);
            for ($i = 0; $i < count($participants); $i++) {
                $contribDetails[$i]     = $contrib_obj->getGroupCrtProfilesInfo($participants[$i]['corrector_id'], $participants[$i]['id'], $artId);
                $gobalcommentscount[$i] = $usercomment_obj->getCommentsCount($participants[$i]['corrector_id']);
                $cnt                    = 0;
                foreach ($contribDetails[$i] as $details) {
                    $percentage  = $contribDetails[$i][$cnt]['contrib_percentage'];
                    $minPrice    = $contribDetails[$i][$cnt]['correction_pricemin'];
                    $maxPrice    = $contribDetails[$i][$cnt]['correction_pricemax'];
                    $writerPrice = $contribDetails[$i][$cnt]['corrector_user'];

                    if ($percentage != NULL) {
                        $contribDetails[$i][$cnt]['correction_pricemin'] = $minPrice / 100 * $percentage;
                        $contribDetails[$i][$cnt]['correction_pricemax'] = $maxPrice / 100 * $percentage;
                    } else {
                        $contribDetails[$i][$cnt]['correction_pricemin'] = $writerPrice;
                        $contribDetails[$i][$cnt]['correction_pricemax'] = $writerPrice;
                    }
                    $contribDetails[$i][$cnt]['countcomments'] = $gobalcommentscount[$i][$cnt]['countcomments'];
                    $contribDetails[$i][$cnt]['profession']    = utf8_encode($this->profession_array[$details['profession']]);
                    $contribDetails[$i][$cnt]['language']      = utf8_encode($this->language_array[$details['language']]);
                    $contribDetails[$i][$cnt]['fav_category']  = utf8_encode($this->category_array[$details['favourite_category']]);
                    $contribDetails[$i][$cnt]['education']     = $details['education'];
                    $contribDetails[$i][$cnt]['categories']    = $this->unserialiseCategories($details['category_more']);
                    $contribDetails[$i][$cnt]['language_more'] = $this->unserialiseLanguage($details['language_more']);
                    if($details['identifier'] != '')
                        $contribDetails[$i][$cnt]['successrate']   = $part_obj->getContributorSuccessRate($details['identifier']);
                    // $contrib_workedwith                      = $contrib_obj->getContribWorkedCompanies($details['identifier']);

                    $contrib_parts_inao                        = $contrib_obj->contribCrtPartsInAo($details['identifier'], $details['delId']);
                    $contribDetails[$i][$cnt]['contrib_parts_inao']  = $contrib_parts_inao[0]['partscount'];
                   // echo $details['partId']; exit;
                    if($details['partId'] != ''){
                        $cyclecount                                = $participate_obj->getCrtParticipationCyclesOnPartId($details['partId']);
                         $contribDetails[$i][$cnt]['cyclecount'] = $cyclecount[0]['cycle'];  }
                    ///working details of user////
                    $workexpDetails=$user_obj->getExperienceDetails($details['identifier'],'job');
                    $contribDetails[$i][$cnt]['workDetails']=$workexpDetails;
                    $educationDetails=$user_obj->getExperienceDetails($details['identifier'],'education');
                    $contribDetails[$i][$cnt]['educationDetails']=$educationDetails;
                    $cnt++;
                }
            }
            $this->_view->lastparticipant         = $lastartbacktoFO[0]['lastpartcount'];
            $this->_view->pagetype                = $cond;
            $this->_view->totalusers              = count($participants);
            $this->_view->bid_arts                = $noarts[0]['partcount'];
            $this->_view->refused_arts            = $noarts[1]['partcount'];
            $this->_view->proccesing_arts         = $noarts[2]['partcount'];
            $this->_view->contribDetails          = $contribDetails;
            $maxcycle                             = $participate_obj->getCrtParticipationCycles($artId);
            $this->_view->maxcycle                = $maxcycle[0]['cycle'];
            $anyvalidatedCorrector              = $participate_obj->anyValidatedCorrector($artId);
            $this->_view->anyvalidatedCorrector =  $anyvalidatedCorrector;
            $this->_view->render("correction_correctorgroupprofiles");
        }
    }
    ///////////getting the send mail display when corrector is refused and accepted////////////
    public function getcommentpopupAction()
    {
        $prevurl = getenv("HTTP_REFERER");
        $participate_obj = new Ep_Participation_CorrectorParticipation();
        $user_obj = new Ep_User_User();
        $delivery_obj = new Ep_Delivery_Delivery();
        $profile_params=$this->_request->getParams();
        ////////////////////////////
        $contrib_params=$this->_request->getParams();
        $contrib_id = $contrib_params['contrib_id'];
        $particip_id = $contrib_params['particip_id'];
        $art_id = $contrib_params['artid'];
        $mailid = $contrib_params['mailId'];
        ////////////resubmission date in mail content/////////
        if($contrib_id !="")
        {
            $profiletype = $user_obj->getAllUsersDetails($contrib_id);
            $delivery_details=$delivery_obj->getArtDeliveryDetails($art_id);
            if($profiletype[0]['type2']=='corrector')
            {
                $resubtime = $this->correctorResubmitTime($art_id, $contrib_id);
            }
            else
            {
                $resubtime = $this->writerResubmitTime($art_id, $contrib_id);
            }

            if($resubtime <= '60')
                $parameters['resubmit_time']= $resubtime." minutes";
            else
                $parameters['resubmit_time']= $this->minutesToHours($resubtime)." hour(s)";
        }
        if($art_id !="")///for groupprofilepopup mails show off////
        {
            $delivery_details=$delivery_obj->getArtDeliveryDetails($art_id);
            $user_details=$user_obj->getAllUsersDetails($contrib_id);
            if($user_details[0]['type2']=='corrector')
            {
                $time = $this->correctorExpireTime($art_id, $contrib_id);
            }
            else
            {
                $time = $this->writerExpireTime($art_id, $contrib_id);
            }
        }
        $expires=$time;
        ///////////////////////////////////////
        $autoEmails=new Ep_Message_AutoEmails();
        $paricipationdetails=$participate_obj->getCrtParticipateDetails($particip_id);
        ///for groupprofilepopup mails show off////
        $parameters['AO_end_date']=date('d/m/Y H:i', $expires);

        $parameters['article_title']=$paricipationdetails[0]['title'];
        $parameters['articlename_link']="/contrib/mission-deliver?article_id=".$art_id;
        $parameters['aoname_link']="http://ep-test.edit-place.co.uk/contrib/mission-corrector-deliver?article_id=".$art_id;
        $parameters['ongoinglink']="/contrib/ongoing";
		if($paricipationdetails[0]['currency']=='euro')
			$curr='&euro;';
		else
			$curr='&pound;';
        $parameters['royalty']=$paricipationdetails[0]['price_user'].$curr;
        if($paricipationdetails[0]['deli_anonymous']=='1')
            $parameters['client_name']='inconnu';
        else
        {
            $clientDetails=$autoEmails->getUserDetails($paricipationdetails[0]['clientId']);
            if($clientDetails[0]['username']!=NULL)
                $parameters['client_name']= $clientDetails[0]['username'];
            else
                $parameters['client_name']= $clientDetails[0]['email'];
        }
        /*$contribDetails=$autoEmails->getContribUserDetails($paricipationdetails[0]['user_id']);
        $parameters['contributor_name'] = $contribDetails[0]['firstname']." ".$contribDetails[0]['lastname'];*/
        $contribDetails=$autoEmails->getUserDetails($paricipationdetails[0]['corrector_id']);

        if($contribDetails[0]['username']!=NULL)
            $parameters['contributor_name']= $contribDetails[0]['username'];
        else
            $parameters['contributor_name']= $contribDetails[0]['email'];
			
				$max=max($delivery_details[0]['st'], $delivery_details[0]['jt'], $delivery_details[0]['sjt']);
			$writersubmission=time()+(60*$max);
			$writerparticipation=60*$delivery_details[0]['articleparttime'];
			
		$parameters['max_reception_writer_file_date_hour']=strftime("%d/%m/%Y &agrave; %H:%M",($writerparticipation+$writersubmission)); 	
        //$email=$autoEmails->getAutoEmail($mailid);
        $email = $autoEmails->getMailComments(NULL,$mailid,$parameters);
       // echo  $emailComments = utf8_encode(stripslashes(html_entity_decode($email)));exit;
        echo  $emailComments = stripslashes(utf8_encode($email));exit;
    }

    //////////when a writer is selected and pop_submit and refus buttons are clicked/////////////////
    public function selectcorrectorAction()
    {
        $prevurl = getenv("HTTP_REFERER");
        $participate_obj = new Ep_Participation_CorrectorParticipation();
        $particip_obj = new EP_Participation_Participation();
        $autoEmails=new Ep_Message_AutoEmails();
        $profile_params=$this->_request->getParams();
        $contrib_params=$this->_request->getParams();
        $contrib_id = $contrib_params['contrib_id'];
        $particip_id = $contrib_params['particip_id'];    ////corrector  paricipation id//
        $participation_id = $participate_obj->getParticipateId($particip_id);   ///getting the paticipate id from the Corretor particiaption table///
        $part_id = $participation_id[0]['participate_id'];
        $Message = utf8_decode(stripslashes($contrib_params['comment']));
        $artId = $contrib_params['art_id'];
        $comments = $contrib_params['comments'];
        //////////////////////////////////////
        $delivery_obj = new Ep_Delivery_Delivery();
        $article_obj = new EP_Delivery_Article();
        $user_obj = new Ep_User_User();
        $delivery_details=$delivery_obj->getArtDeliveryDetails($artId);
        $user_details=$user_obj->getAllUsersDetails($contrib_id);
        if($user_details[0]['type2']=='corrector')
        {
            $expires = $this->correctorExpireTime($artId, $contrib_id);
        }
        else
        {
            $expires = $this->writerExpireTime($artId, $contrib_id);
        }
        ///////////////////////////////////////
        if(isset($profile_params["submit_pop"]) || $profile_params["button"]=="submit_pop")
        {
            $delDetails = $delivery_obj->getCrtPrAoDetailsWithArtid($artId);
			
			//simultaneous correction conditions
				$writers=explode(",",$delDetails[0]['article_contribs']);
				if($delDetails[0]['AOtype']== "private" && count($writers)==1)
					$selectedwriter=$delDetails[0]['article_contribs'];
				else
					$selectedwriter=$particip_obj->getSelectedwriters($artId);
					
					
			if($contrib_id!=$selectedwriter)
			{
				//Check already selected any other through cron
				$bidpresent=$participate_obj->checkRecordPresent($artId,'bid');
				if($bidpresent=="YES")
				{
					echo 'selectedcorr';exit;
				}
            $this->_view->type = 'accept';
            ////udate status participation table for status///////
				$participate_id=$particip_obj->getParticipateId($selectedwriter,$artId);	
				$ParticipationDetail=$particip_obj->getParticipationDetail($participate_id);
				
				if($ParticipationDetail[0]['status']=='under_study' && $ParticipationDetail[0]['current_stage']=='corrector')
				{
					$data = array("status"=>"bid", "accept_refuse_at"=>date("Y-m-d H:i:s", time()), "corrector_submit_expires"=>$expires, "selection_type"=>"bo","participate_id"=>$participate_id);////////updating
				}
				else
					$data = array("status"=>"bid", "accept_refuse_at"=>date("Y-m-d H:i:s", time()), "selection_type"=>"bo","participate_id"=>$participate_id);
					////////updating
            $query = "corrector_id= '".$contrib_id."' AND id = '".$particip_id."'";
            $participate_obj->updateCrtParticipation($data,$query);
            $refusedcontribs = $participate_obj->getRefusedCorrectors($artId);
            if($refusedcontribs!="NO")
            {
                for($i=0; $i<count($refusedcontribs); $i++)
                {
                    ////udate status participation table for status refuse remaining///////
						//$data1 = array("status"=>"bid_refused", "accept_refuse_at"=>date("Y-m-d H:i:s", time()), "corrector_submit_expires"=>$expires, "selection_type"=>"bo");////////updating
						$data1 = array("status"=>"bid_refused", "accept_refuse_at"=>date("Y-m-d H:i:s", time()), "selection_type"=>"bo");////////updating
                    $query1 = "corrector_id= '".$refusedcontribs[$i]['corrector_id']."' AND article_id = '".$artId."' AND cycle='0'";
                    $participate_obj->updateCrtParticipation($data1,$query1);
                }
            }
            /* *sending Mail**/
            //////sending mail to corrector who got selected in profile selections///////////////
            $automail=new Ep_Message_AutoEmails();
            $email=$automail->getAutoEmail(28);//
            //$Object=$email[0]['Object'];
            $Object="Attribution d'article : ".$delivery_details[0]['articleName']." - Edit Place";
            $receiverId = $contrib_id;
            $automail->sendMailEpMailBox($receiverId,$Object,$Message);
            /////////////sending the emails to remaining contributors who got refused//////////////
            $paricipationdetails=$participate_obj->getCrtParticipateDetails($particip_id);

            $parameters['article_title']=$paricipationdetails[0]['title'];
				$parameters['articlename_link']="/contrib/mission-deliver?article_id=".$artId;
            if($paricipationdetails[0]['deli_anonymous']=='1')
                $parameters['client_name']='inconnu';
            else
            {
                $clientDetails=$autoEmails->getUserDetails($paricipationdetails[0]['clientId']);
                if($clientDetails[0]['username']!=NULL)
                    $parameters['client_name']= $clientDetails[0]['username'];
                else
                    $parameters['client_name']= $clientDetails[0]['email'];
            }
				//Refuse same user in participation
				$corrPart=$particip_obj->checkParticipation($artId,$contrib_id);
				if($corrPart!='NO')
				{
					$data = array("status"=>"bid_refused", "accept_refuse_at"=>date("Y-m-d H:i:s", time()),  "selection_type"=>"bo");////////updating
					$query =  "article_id= '".$artId."' AND user_id='".$contrib_id."' AND status IN ('bid_premium','bid_nonpremium') AND cycle=0";
					$particip_obj->updateParticipation($data,$query);
					
					//Mail
					$automail->messageToEPMail($contrib_id,27,$parameters);
				}
            if($refusedcontribs!="NO")
            {
                for($i=0; $i<count($refusedcontribs); $i++)
                {
                    $automail->messageToEPMail($refusedcontribs[$i]['corrector_id'],29,$parameters);
                }
            }
            /////////////article history////////////////
            $actparams['correctorId'] = $contrib_id;  ////its corrector id///
            $actparams['artId'] = $artId;
            $actparams['stage'] = "corrector selection profile";
            $actparams['action'] = "profile accepted";
            $this->articleHistory(14,$actparams);
            /////////////end of article history////////////////
			}
			else
			{
				echo 'selectedwriter';exit;
			}
            $this->_redirect($prevurl);

        }
        else if(isset($profile_params["refuse_pop"]) || $profile_params["button"]=="refuse_pop")
        {
            ////udate status participation table for status///////
            $crtpartdetails = 	$participate_obj->getCrtParticipateDetails($particip_id);
            if($crtpartdetails[0]['status'] == 'bid_corrector'){
                $data = array("status"=>"bid_refused", "accept_refuse_at"=>date("Y-m-d H:i:s", time()),  "corrector_submit_expires"=>$expires, "selection_type"=>"bo");////////updating
                $sendmail = "forrefused";
            }
            elseif($crtpartdetails[0]['status'] == 'bid' || $crtpartdetails[0]['status'] == 'disapproved' || $crtpartdetails[0]['status'] == 'time_out'){
                $data = array("status"=>"closed", "accept_refuse_at"=>date("Y-m-d H:i:s", time()),  "corrector_submit_expires"=>$expires, "selection_type"=>"bo");////////updating
                $sendmail = "forclosed";
            }
            ////udate status participation table for status///////

            $query = "corrector_id= '".$contrib_id."' AND id = '".$particip_id."'";
            $participate_obj->updateCrtParticipation($data,$query);
            /////////////article history////////////////
            $partscount = $participate_obj->getNoOfCrtParticipants($artId);
            $actparams['participation_count'] = $partscount[0]['partsCount'];
            $actparams['correctorId'] = $contrib_id;
            $actparams['artId'] = $artId;
            $actparams['stage'] = "corrector selection profile";
            /////////////end of article history////////////////
            if($profile_params["sendtofo"] == 'yes')
            {   //print_r($profile_params); exit;
                ////////////updating article time to zero as article should go back FO again  ///////
                $artbacktoFO = $participate_obj->getArticlesBackToFo($artId);
                if($artbacktoFO == "NO")
                {
                    ////updating the article tabel article submit expire wiht zero///////
                    $this->CorrectorParticipationExpire($artId);
                    ///////check the cycle count in participation tabel and increament//////////
                    $cycleCount = $participate_obj->getCrtParticipationCycles($artId);
                    $cycleCount1 = $cycleCount[0]['cycle']+1;
                    /////udate status participation table with article id///////
                    $data = array("cycle"=>$cycleCount1);////////updating
                    $query = "id= '".$particip_id."' and cycle=0";
                    $participate_obj->updateCrtParticipation($data,$query);

                }
                if($profile_params["mailannoucement"] == 'sendmail') {
                    $this->sendMailToCorrectors($artId); }
                if($crtpartdetails[0]['status'] == 'bid' || $crtpartdetails[0]['status'] == 'disapproved')  {
                    $actparams['correctorId'] = $crtpartdetails[0]['corrector_id'];
                    $actparams['action'] = "article not sent and republished";
                    $this->articleHistory(19,$actparams);
                }
                else{
                    $actparams['action'] = "republished";
                    $this->articleHistory(16,$actparams);
                }
            }
            elseif($profile_params["sendtofo"] == 'no')
            {
                ////updating the article tabel article submit expire wiht zero///////
                $data = array("send_to_fo"=>"no","file_path"=>"");////////updating
                $query = "id = '".$artId."'";
                $article_obj->updateArticle($data,$query);
                /* Updating the paticipation table to appear the article in s1 stage**/
                $data = array("status"=>"under_study", "current_stage"=>"stage1");////////updating
                $query="id='".$part_id."'";
                $participate_obj->updateCrtParticipation($data,$query);
            }
            elseif($profile_params['nocrtclose'] == 'yes')
            {
                $data = array("correction_closed_status"=>"closed");////////updating
                $query = "id= '".$artId."'";
                $article_obj->updateArticle($data,$query);
            }
            if($sendmail == 'forrefused'){
                //Delete royalities if any
                $Roy_obj= new Ep_Payment_Royalties();
                $Roy_obj->deleteRoyality($artId,$particip_id);
                /* *sending Mail**/
                $email=$autoEmails->getAutoEmail(29);//
                $Object=$email[0]['Object'];
                $receiverId = $contrib_id;
                $autoEmails->sendMailEpMailBox($receiverId,$Object,$Message);
                /////////////article history////////////////
                $actparams['correctorId'] = $contrib_id;
                $actparams['artId'] = $artId;
                $actparams['stage'] = "selection profile";
                $actparams['action'] = "profile rejected";
                $this->articleHistory(37,$actparams);
                /////////////end of article history////////////////
            }else if($sendmail == 'forclosed'){
                //  print_r($paricipationdetails); exit;
                $parameters['article_title']=$crtpartdetails[0]['title'];
                $parameters['articlename_link']="/contrib/mission-deliver?article_id=".$artId;
                $autoEmails->messageToEPMail($contrib_id,48,$parameters);///
                if($crtpartdetails[0]['status'] == 'bid' || $crtpartdetails[0]['status'] == 'disapproved')
                {
                    $actparams['action'] = "article not sent and closed";
                    $this->articleHistory(17,$actparams);
                }
                else{
                    $actparams['action'] = "closed";
                    $this->articleHistory(20,$actparams);
                }
            }

            $this->unlockonactionAction($artId);
            $this->_redirect("correction/corrector-profiles-list?submenuId=ML2-SL18");
        }

    }
    ////////////display pop up with detail of multiple contributors who made biding when the article title is clicked///////////////////
    public function republishcorrectorpopupAction()
    {
        $delivery_obj=new Ep_Delivery_Delivery();
        $article_obj = new EP_Delivery_Article();
		$articleObj=new EP_Ongoing_Article();
        $participate_obj=new EP_Participation_Participation();
        $crtparticipate_obj=new Ep_Participation_CorrectorParticipation();
        $automail=new Ep_Message_AutoEmails();
        $republishParams=$this->_request->getParams();
        $artId=$republishParams['artId'];
        $artdeldetails = $delivery_obj->getArtDeliveryDetails($artId);
        if($republishParams['save'] == 'save')
        {
            //$parttime = $republishParams['parttime'];
            if($republishParams['parttime_option'] == 'min' )
                $parttime=$republishParams['parttime'];
            elseif($republishParams['parttime_option'] == 'hour')
                $parttime=60*$republishParams['parttime'];
            elseif($republishParams['parttime_option'] == 'day')
                $parttime=60*24*$republishParams['parttime'];

            $subopttime = $republishParams['subopttime'];
            if($subopttime == 'min')
            {
                $jctime = $republishParams['jctime'];
                $sctime = $republishParams['sctime'];
            }
            elseif($subopttime == 'hour')
            {
                $jctime = $republishParams['jctime']*60;
                $sctime = $republishParams['sctime']*60;
            }
            elseif($subopttime == 'day')
            {
                $jctime = $republishParams['jctime']*60*24;
                $sctime = $republishParams['sctime']*60*24;
            }
            $suboptresub = $republishParams['suboptresub'];
            if($suboptresub == 'min')
            {
                $jcresub = $republishParams['jcresub'];
                $scresub = $republishParams['scresub'];
            }
            elseif($suboptresub == 'hour')
            {
                $jcresub = $republishParams['jcresub']*60;
                $scresub = $republishParams['scresub']*60;
            }
            elseif($suboptresub == 'day')
            {
                $jcresub = $republishParams['jcresub']*60*24;
                $scresub = $republishParams['scresub']*60*24;
            }
            
			$min_price=$republishParams['price_min'];
            $max_price=$republishParams['price_max'];
			
			//Add article history when corrector price range is updated
			$ArticleDetails=$articleObj->getEditArticleDetails($artId);
			/*if($ArticleDetails[0]['correction_pricemin']!=$min_price || $ArticleDetails[0]['correction_pricemax']!=$max_price)
			{
				$actionId=65;
				$actparams['artId']=$artId;
				$actparams['stage']='ongoing';
				$actparams['action']='pricecorrrange_updated';
				$actparams['old_article_correction_price_range']=$ArticleDetails[0]['correction_pricemin'].'-'.$ArticleDetails[0]['correction_pricemax'];
				$actparams['new_article_correction_price_range']=$min_price.'-'.$max_price;
				$actparams['currency']=$ArticleDetails[0]['currency'];
				$this->articleHistory($actionId, $actparams);						
			}*/
			
            ///udate status_bo in delivery table for delete as trash///////
            /*$data = array("correction_participation"=>$parttime, "correction_submit_option"=>$subopttime, "correction_jc_submission"=>$jctime, "correction_sc_submission"=>$sctime, "correction_pricemin"=>$min_price, "correction_pricemax"=>$max_price,
                "correction_resubmit_option"=>$suboptresub, "correction_jc_resubmission"=>$jcresub, "correction_sc_resubmission"=>$scresub);*/////////updating
            $data = array("correction_participation"=>$parttime, "correction_submit_option"=>$subopttime, "correction_jc_submission"=>$jctime, "correction_sc_submission"=>$sctime,  "correction_pricemin"=>$min_price, "correction_pricemax"=>$max_price,
						"correction_resubmit_option"=>$suboptresub, "correction_jc_resubmission"=>$jcresub, "correction_sc_resubmission"=>$scresub);
			$query = "id= '".$artId."'";
            $article_obj->updateArticle($data,$query); exit;
        }
        $artId=$republishParams['artId'];
        if($artdeldetails[0]['corrector_privatelist'] == '')      ///this ao is private
        {
            $profiles = explode(",", $artdeldetails[0]['view_to']);
            $profiles = implode(",", $profiles);
            $profs=explode(",",$profiles);
            $proflist=array();
            for($p=0;$p<count($profs);$p++)
            {
                if($profs[$p]=="jc")
                    $proflist[]="junior";
                elseif($profs[$p]=="sc")
                    $proflist[]="senior";
            }
            $pubprofiles=implode("','",$proflist);
            $aoprofiles=$delivery_obj->getViewToOfAO($pubprofiles);
            $aoprofiles = $aoprofiles[0]['AoCorrectors'];
        }
        else{
            $priprofiles = explode(",",$artdeldetails[0]['corrector_privatelist']);
            $aoprofiles=count($priprofiles);
        }
        $partinart = $crtparticipate_obj->getCrtPartsCountInArticle($artId);
        if($partinart[0]['partscountinart'] == $aoprofiles)
            $this->_view->nopartsforrepublish  = "yes";  ////there are no user to participate in article if article is republished///
        else
            $this->_view->nopartsforrepublish = "no";


        $this->_view->refusedcontributors = $crtparticipate_obj->getRefusedCrtParts($artId);
        if($artdeldetails[0]['corrector_privatelist'] == '')
        {
            if($artdeldetails[0]['view_to'] == 'sc')
                $this->_view->missiontitle = "Mission SC";
            else
                $this->_view->missiontitle = "Mission publique";
        }
        else
        {
            $this->_view->missiontitle = "Mission privée";
        }
        if($artdeldetails[0]['correction_submit_option'] == 'min' )
            $convertval = 1;
        elseif($artdeldetails[0]['correction_submit_option'] == 'hour')
            $convertval = 60;
        elseif($artdeldetails[0]['correction_submit_option'] == 'day')
            $convertval = 60*24;

        if($artdeldetails[0]['correction_resubmit_option'] == 'min')
            $reconvertval = 1;
        elseif($artdeldetails[0]['correction_resubmit_option'] == 'hour')
            $reconvertval = 60;
        elseif($artdeldetails[0]['correction_resubmit_option'] == 'day')
            $reconvertval = 60*24;
       // echo $convertval; echo $reconvertval;
        $artdeldetails[0]['correction_jc_submission'] = $artdeldetails[0]['correction_jc_submission']/$convertval;
        $artdeldetails[0]['correction_sc_submission'] = $artdeldetails[0]['correction_sc_submission']/$convertval;

         $artdeldetails[0]['correction_jc_resubmission'] = $artdeldetails[0]['correction_jc_resubmission']/$reconvertval;
         $artdeldetails[0]['correction_sc_resubmission'] = $artdeldetails[0]['correction_sc_resubmission']/$reconvertval;

        $this->_view->artdeldetails =  $artdeldetails;

        $parameters['article_title']=$artdeldetails[0]['articleName'];
        $clientDetails=$automail->getUserDetails($artdeldetails[0]['user_id']);
        if($clientDetails[0]['username']!=NULL)
            $parameters['client_name']= $clientDetails[0]['username'];
        else
        {
            $email = explode("@",$clientDetails[0]['email']);
            $parameters['client_name']= $email[0];
        }
        $parameters['corrector_ao_link']= "/contrib/aosearch";
        $expires=time()+(60*$artdeldetails[0]['correction_participation']);
        $parameters['crtsubmitdate_bo']=date('d/m/Y H:i', $expires);

        //// creattion for new ao for corrector and mail to remaining correctors//////
            $mailId = 21;
            $email=$automail->getAutoEmail($mailId);
            $this->_view->object=$email[0]['Object'];
            $email = $automail->getMailComments($user_id=NULL,$mailId,$parameters);
            $this->_view->message = utf8_encode(stripslashes($email));
            $this->_view->stage = $republishParams['stage'];       ////when final refused and republished from correction stages 0,1,2///
        if($republishParams['close'] == 'yes')
        {
            if($republishParams['stage'] == '2')    ///if the request from the stage 2
            {
                $mailId = 91;
                $this->_view->stage = 2;
            }
            else    ////if the request is from the corrector selection profile page/////
                $mailId = 29;
            $refuseemail = $automail->getMailComments($user_id=NULL,$mailId,$parameters);
            $this->_view->refusemessage = utf8_encode(stripslashes($refuseemail));
            $this->_view->close = "yes";
           // $this->articleshistory($artId, 'selectionprofile', 'closed_published');   ///when last participants is there///
        }
        /*else
        {
            if($republishParams['nopart'] == 'no')      ///republished when no participats///
                $this->articleshistory($artId, 'selectionprofile', 'noparticipant_republish');
            else
                $this->articleshistory($artId, 'selectionprofile', 'republish');
        }*/
        $this->resetDeliveredDetail($artId);//call the function to reset the delivered details of article
        $this->_view->render("correction_republishcorrectorpopup");
    }
    ////////////display pop up with detail for bulk republish popup for correctors///////////////////
    public function bulkrepublishcorrectorpopupAction()
    {
        $delivery_obj=new Ep_Delivery_Delivery();
        $article_obj = new EP_Delivery_Article();
        $participate_obj=new EP_Participation_Participation();
        $crtparticipate_obj=new Ep_Participation_CorrectorParticipation();
        $automail=new Ep_Message_AutoEmails();
        $republishParams=$this->_request->getParams(); /// print_r($republishParams); exit;
        $aoId=$republishParams['aoId'];
        $this->_view->artlist = $republishParams['artlist'];
        $this->_view->aoId = $aoId;
        $artlist=explode(",",$republishParams['artlist']);
        $artdeldetails = $delivery_obj->getArtDelDetails($aoId);   //print_r($artdeldetails); exit;
       // $artId=$republishParams['artId'];
       // $artdeldetails = $delivery_obj->getArtDeliveryDetails($artId);
        if($republishParams['save'] == 'save')
        {
            $prices_min = explode(",",$republishParams['crtprice_min']);
            for($i=0; $i<count($prices_min); $i++)
            {
                $pricemin = explode("_",$prices_min[$i]);
                $crtmin_pricearry[$pricemin[1]] = $pricemin[2];
            }
            $prices_max = explode(",",$republishParams['crtprice_max']);
            for($i=0; $i<count($prices_max); $i++)
            {
                $pricemax = explode("_",$prices_max[$i]);
                $crtmax_pricearry[$pricemax[1]] = $pricemax[2];
            }

            //$parttime = $republishParams['parttime'];
            if($republishParams['parttime_option'] == 'min' )
                $parttime=$republishParams['parttime'];
            elseif($republishParams['parttime_option'] == 'hour')
                $parttime=60*$republishParams['parttime'];
            elseif($republishParams['parttime_option'] == 'day')
                $parttime=60*24*$republishParams['parttime'];

            $subopttime = $republishParams['subopttime'];
            if($subopttime == 'min')
            {
                $jctime = $republishParams['jctime'];
                $sctime = $republishParams['sctime'];
            }
            elseif($subopttime == 'hour')
            {
                $jctime = $republishParams['jctime']*60;
                $sctime = $republishParams['sctime']*60;
            }
            elseif($subopttime == 'day')
            {
                $jctime = $republishParams['jctime']*60*24;
                $sctime = $republishParams['sctime']*60*24;
            }
            $suboptresub = $republishParams['suboptresub'];
            if($suboptresub == 'min')
            {
                $jcresub = $republishParams['jcresub'];
                $scresub = $republishParams['scresub'];
            }
            elseif($suboptresub == 'hour')
            {
                $jcresub = $republishParams['jcresub']*60;
                $scresub = $republishParams['scresub']*60;
            }
            elseif($suboptresub == 'day')
            {
                $jcresub = $republishParams['jcresub']*60*24;
                $scresub = $republishParams['scresub']*60*24;
            }
            for($i=0; $i<count($artlist); $i++)
            {
                ///udate status_bo in delivery table for delete as trash///////
                $data = array("correction_participation"=>$parttime, "correction_submit_option"=>$subopttime, "correction_jc_submission"=>$jctime, "correction_sc_submission"=>$sctime,
                    "correction_resubmit_option"=>$suboptresub, "correction_jc_resubmission"=>$jcresub, "correction_sc_resubmission"=>$scresub);////////updating
                $query = "id= '".$artlist[$i]."'";
                $article_obj->updateArticle($data,$query);
            }
            ///udate delivery table also///////
            $data1 = array("correction_participation"=>$parttime, "correction_submit_option"=>$subopttime, "correction_jc_submission"=>$jctime, "correction_sc_submission"=>$sctime,
                "correction_resubmit_option"=>$suboptresub, "correction_jc_resubmission"=>$jcresub, "correction_sc_resubmission"=>$scresub);////////updating
            $query1 = "id= '".$aoId."'";
            $delivery_obj->updateDelivery($data1,$query1);
            exit;
        }
        $artId=$republishParams['artId'];// print_r($artdeldetails); exit;
        if($artdeldetails[0]['corrector_privatelist'] == '')      ///this ao is private
        {
            $profiles = explode(",", $artdeldetails[0]['view_to']);
            $profiles = implode(",", $profiles);
            $profs=explode(",",$profiles);
            $proflist=array();
            for($p=0;$p<count($profs);$p++)
            {
                if($profs[$p]=="jc")
                    $proflist[]="junior";
                elseif($profs[$p]=="sc")
                    $proflist[]="senior";
            }
            $pubprofiles=implode("','",$proflist);
            $aoprofiles=$delivery_obj->getViewToOfAO($pubprofiles);
            $aoprofiles = $aoprofiles[0]['AoCorrectors'];
        }
        else{
            $priprofiles = explode(",",$artdeldetails[0]['corrector_privatelist']);
            $aoprofiles=count($priprofiles);
        }
        $partinartcount = 0; $refusedcontributors=0;
        for($i=0; $i<count($artlist); $i++)
        {
             $partinart = $crtparticipate_obj->getCrtPartsCountInArticle($artlist[$i]);
             $partinartcount= $partinart[0]['partscountinart'];

            $refusedcontributors+= $crtparticipate_obj->getRefusedCrtParts($artlist[$i]);
        } //echo $partinartcount; //echo $refusedcontributors;

      /*  if($partinartcount == $aoprofiles)
            $this->_view->nopartsforrepublish  = "yes";  ////there are no user to participate in article if article is republished///
        else
            $this->_view->nopartsforrepublish = "no";*/


        $this->_view->refusedcontributors = $refusedcontributors;
        if($artdeldetails[0]['corrector_privatelist'] == '')
        {
            if($artdeldetails[0]['view_to'] == 'sc')
                $this->_view->missiontitle = "Mission SC";
            else
                $this->_view->missiontitle = "Mission publique";
        }
        else
        {
            $this->_view->missiontitle = "Mission privée";
        }
        if($artdeldetails[0]['correction_submit_option'] == 'min' )
            $convertval = 1;
        elseif($artdeldetails[0]['correction_submit_option'] == 'hour')
            $convertval = 60;
        elseif($artdeldetails[0]['correction_submit_option'] == 'day')
            $convertval = 60*24;

        if($artdeldetails[0]['correction_resubmit_option'] == 'min')
            $reconvertval = 1;
        elseif($artdeldetails[0]['correction_resubmit_option'] == 'hour')
            $reconvertval = 60;
        elseif($artdeldetails[0]['correction_resubmit_option'] == 'day')
            $reconvertval = 60*24;
        // echo $convertval; echo $reconvertval;
        $artdeldetails[0]['correction_jc_submission'] = $artdeldetails[0]['correction_jc_submission']/$convertval;
        $artdeldetails[0]['correction_sc_submission'] = $artdeldetails[0]['correction_sc_submission']/$convertval;

        $artdeldetails[0]['correction_jc_resubmission'] = $artdeldetails[0]['correction_jc_resubmission']/$reconvertval;
        $artdeldetails[0]['correction_sc_resubmission'] = $artdeldetails[0]['correction_sc_resubmission']/$reconvertval;

        $this->_view->artdeldetails =  $artdeldetails;

        $parameters['article_title']=$artdeldetails[0]['deliveryTitle'];   ////need to display the delivery title in bulk aspect
        $clientDetails=$automail->getUserDetails($artdeldetails[0]['user_id']);
        if($clientDetails[0]['username']!=NULL)
            $parameters['client_name']= $clientDetails[0]['username'];
        else
        {
            $email = explode("@",$clientDetails[0]['email']);
            $parameters['client_name']= $email[0];
        }
        $parameters['corrector_ao_link']= "/contrib/aosearch";
        $expires=time()+(60*$artdeldetails[0]['correction_participation']);
        $parameters['crtsubmitdate_bo']=date('d/m/Y H:i', $expires);

        //// creattion for new ao for corrector and mail to remaining correctors//////
        $mailId = 21;
        $email=$automail->getAutoEmail($mailId);
        $this->_view->object=$email[0]['Object'];
        $email = $automail->getMailComments($user_id=NULL,$mailId,$parameters);
        $this->_view->message = utf8_encode(stripslashes($email));
        $this->_view->stage = $republishParams['stage'];       ////when final refused and republished from correction stages 0,1,2///
        if($republishParams['close'] == 'yes')
        {
            if($republishParams['stage'] == '2')    ///if the request from the stage 2
            {
                $mailId = 91;
                $this->_view->stage = 2;
            }
            else    ////if the request is from the corrector selection profile page/////
            $mailId = 29;
            $refuseemail = $automail->getMailComments($user_id=NULL,$mailId,$parameters);
            $this->_view->refusemessage = utf8_encode(stripslashes($refuseemail));
            $this->_view->close = "yes";
            // $this->articleshistory($artId, 'selectionprofile', 'closed_published');   ///when last participants is there///
        }
        else
        {
            $this->_view->close = "no";
        }
        /*else
        {
            if($republishParams['nopart'] == 'no')      ///republished when no participats///
                $this->articleshistory($artId, 'selectionprofile', 'noparticipant_republish');
            else
                $this->articleshistory($artId, 'selectionprofile', 'republish');
        }*/
        $this->_view->render("republish_bulkrepublishcrtpopup");
    }
    ///changing the ao particiption time (dynamically in republishpopup////
    public function getextendcrtparticipationtimeAction()
    {
        $articleParams=$this->_request->getParams();
        $participation_obj=new Ep_Participation_CorrectorParticipation();
        $automail=new Ep_Message_AutoEmails();
        $delivery_obj = new Ep_Delivery_Delivery();
        if($articleParams['publishaomail'] == 'yes')   ///when time changes in mail content in publish ao popup//
        {
            if($articleParams['now'] == 'yes')
                $crtsubmitdate_bo="<b>".strftime("%d/%m/%Y &agrave; %H:%M",$expires)."</b>";
            else
            {
                $expires+=60*60*24;
                $crtsubmitdate_bo="<b>".strftime("%d/%m/%Y &agrave; %H:%M",$expires)."</b>";
            }

        }
        $artdeldetails = $delivery_obj->getArtDeliveryDetails($articleParams['artname']);

        if(!$articleParams['part_time'])
        {
            $articleParams['part_time']=0;
        }
        if($articleParams['parttime_option'] == 'min' )
            $expires=time()+(60*$articleParams['part_time']);
        elseif($articleParams['parttime_option'] == 'hour')
            $expires=time()+(60*60*$articleParams['part_time']);
        elseif($articleParams['parttime_option'] == 'day')
            $expires=time()+(60*60*24*$articleParams['part_time']);

        $parameters['crtsubmitdate_bo']=date('d/m/Y H:i', $expires);
        if($articleParams['bulk'] == 'yes')
            $parameters['article_title']=$artdeldetails[0]['deliveryTitle'];
        else
            $parameters['article_title']=$artdeldetails[0]['articleName'];
        $parameters['corrector_ao_link']= "/contrib/aosearch";
        $email = $automail->getMailComments($user_id=NULL,21,$parameters);
        $emailComments = utf8_encode(stripslashes($email));
        echo $emailComments;
        exit;

    }
    ////making the category in readable formate/////
    public function unserialiseCategories($value) {
        $catorlag         = unserialize($value);
        $i                = 0;
        if ($catorlag != '') {
            foreach ($catorlag as $key => $value) {
                $key     = $this->category_array[$key];
                $res[$i] = "&nbsp;".$key."<b>(".$value.")</b>";
                $i++;
            }
            if ($res != '')
                return implode(",", $res);
        }
    }
    ////making the category in readable formate/////
    public function unserialiseLanguage($value) {
        $langlag         = unserialize($value);
        $i                = 0;
        if ($langlag != '') {
            foreach ($langlag as $key => $value) {
                $key     = $this->language_array[$key];
                $res[$i] = "&nbsp;".$key."<b>(".$value.")</b>";
                $i++;
            }
            if ($res != '')
                return implode(",", $res);
        }
    }
    ////when cron is run for plagarism//////////
    public  function plagarismAction()
    {
        $cron_obj = new Ep_Delivery_CronLock();
        $cron = $cron_obj->getCronLock('plagarism');
        $lockstatus = $cron[0]['locked'];
        if($lockstatus == 'locked2') // updated by Rakesh as cron stops without execution of all files. So status will not be change.
        { echo "in process"; exit; }
        else
        {
            $this->updateCronLock('plagarism', 'locked');  
			ini_set('max_execution_time', 259200);
			//ini_set('display_errors', "on");
			
			$artProcess_obj = new EP_Delivery_ArticleProcess();
			$participate_obj = new EP_Participation_Participation();
			$crtpart_obj = new Ep_Participation_CorrectorParticipation();
			$delivery_obj = new Ep_Delivery_Delivery();
			$article_obj = new EP_Delivery_Article();
			$automail=new Ep_Message_AutoEmails();
			
			$this->adminLogin->userId = $this->adminLogin->userId ? $this->adminLogin->userId : '110823103540627' ;
			$this->adminLogin->loginName = $this->adminLogin->loginName ? $this->adminLogin->loginName : 'farid' ;
			$partArtProcDetails =  $participate_obj->stage0PartArtProcDetails();
			$partArtProcDetailsCount =   count($partArtProcDetails);

			require_once APP_PATH_ROOT.'nlibrary/script/filecontent.php';
			if($partArtProcDetails != 'NO')
			{
				for($i=0; $i<$partArtProcDetailsCount; $i++)
				{
					$server_path = "/home/sites/site7/web/FO/articles/";
					//$srcFile =  $server_path.$partArtProcDetails[0]['article_path'];
                    $recentversion= $artProcess_obj->getRecentVersion($partArtProcDetails[$i]['partId']);
					$filedetials = explode("/",$partArtProcDetails[$i]['article_path']);
					$u_file_name =  $filedetials[1];
					$u_file_name_filename =  pathinfo($u_file_name, PATHINFO_FILENAME);
					$u_file_name_file_ext =  pathinfo($u_file_name, PATHINFO_EXTENSION);
					$filename =  $partArtProcDetails[$i]['article_name'];
                    $srcFile =  $server_path.$partArtProcDetails[$i]['article_id']."/".$u_file_name_filename.".".$u_file_name_file_ext;
					$srcZipFile =  $u_file_name_filename.".zip";
					$srcRarFile =  $u_file_name_filename.".rar";
                    $apId = $partArtProcDetails[$i]['apId'];
					if(file_exists($srcFile))
					{
                        $partArtProcDetails1[0]['article_name'] = $partArtProcDetails[$i]['article_name'];
                        $partArtProcDetails1[0]['urlsexcluded'] = $partArtProcDetails[$i]['urlsexcluded'];
                        $partArtProcDetails1[0]['column_xls'] = $partArtProcDetails[$i]['column_xls'];
                        $response = $this->uploadAndProcess($srcFile,$u_file_name,$filename,1,$partArtProcDetails1);
						if($response == 'File Size is more Than 800kb')
						{
							
							if(!$artProcess_obj->getArticleFileSizeLimitEmailStatus($partArtProcDetails[$i]['article_path']))
							{
								$this->sendPlagEpMailBox($partArtProcDetails[$i], 0, '');
								$data = array("art_file_size_limit_email"=>1);
								$query = "article_path= '".$partArtProcDetails[$i]['article_path']."'";
								$artProcess_obj->updateArticleProcess($data,$query);
							}
                            $maildetials[$i]['artname'] = $partArtProcDetails[$i]['title'];
                            $maildetials[$i]['aoname'] = $partArtProcDetails[$i]['delName'];
                            $maildetials[$i]['aoid'] = $partArtProcDetails[$i]['delId'];
                            $sendmail = 'yes';
                            /////stuck up///
                            $artProcess_obj = new EP_Delivery_ArticleProcess();
                            $data = array("plag_stuck"=>'yes');
                            $query = "article_path= '".$partArtProcDetails[$i]['article_path']."'";
                            $artProcess_obj->updateArticleProcess($data,$query);
						}
                        elseif(!file_exists($response)) {
                            /////stuck up///
                            $artProcess_obj = new EP_Delivery_ArticleProcess();
                            $data = array("plag_stuck"=>'yes');
                            $query = "article_path= '".$partArtProcDetails[$i]['article_path']."'";
                            $artProcess_obj->updateArticleProcess($data,$query);
                            $maildetials[$i]['artname'] = $partArtProcDetails[$i]['title'];
                            $maildetials[$i]['aoname'] = $partArtProcDetails[$i]['delName'];
                            $maildetials[$i]['aoid'] = $partArtProcDetails[$i]['delId'];
                            $sendmail = 'yes';
                        }
                        elseif(file_exists($response))
                        {
                            $xml_data=$this->XMLParserPercentage($response) ;
                            $artdetails = $article_obj->getPlagResultDetails($partArtProcDetails[$i]['article_id']);

                            $xmlpercentage = array();
                            if ($xml_data != '') {
                                foreach ($xml_data as $key => $value) {
                                    array_push($xmlpercentage, "$value");
                                }
                                $xmlpercentage = array_diff($xmlpercentage, array("NA"));
                                if ($xmlpercentage != NULL)
                                    $maxpercentage = @max($xmlpercentage);
                                else
                                    $maxpercentage = "NA";
                            } else {
                                $maxpercentage = 0;
                            }

                            ////udate status in participate table///////
                            $getParticipateId = $participate_obj->getParticipationsIdStage0($partArtProcDetails[$i]['article_id'], $partArtProcDetails[$i]['user_id']);
                            $participate_id = $getParticipateId[0]['id'];
                            if ($maxpercentage >= ($this->configval['plag_cutoff_percentage'])) {

                                $recentversion = $artProcess_obj->getRecentVersion($partArtProcDetails[$i]['partId']);
                                $artprocdata = array("plag_percent" => $maxpercentage, "plagxml" => strrev(substr(strrev($response), 0, strpos(strrev($response), '/'))));////////updating
                                $artprocquery = "participate_id= '" . $partArtProcDetails[$i]['partId'] . "' AND version=" . $recentversion[0]["version"] . "";
                                $artProcess_obj->updateArticleProcess($artprocdata, $artprocquery);

                                $data = array("status" => 'under_study', "current_stage" => 'stage0');////////updating
                                $currentsatge = "stage0";
                                $this->sendPlagEpMailBox($partArtProcDetails[$i], 1, $maxpercentage);
                            }
                            else
                            {
                                $this->insertStageRecord($partArtProcDetails[$i]['partId'], $recentversion[0]["version"], 's0', 'approved');
                                ////udate status article process table///////
                                $artpdata = array("plag_percent" => $maxpercentage, "plagxml" => strrev(substr(strrev($response), 0, strpos(strrev($response), '/'))));////////updating
                                $artpquery = "participate_id= '" . $partArtProcDetails[$i]['partId'] . "' AND version=" . $recentversion[0]["version"] . "";
                                $artProcess_obj->updateArticleProcess($artpdata, $artpquery);
                                if ($partArtProcDetails[$i]['correction'] == 'yes') ///if the article is corection type
                                {
                                    $data = array("status" => 'under_study', "current_stage" => 'corrector');////////updating
                                    $currentsatge = "corrector";
									//	Simultaneous correction
									$selectedcorrector=$crtpart_obj->getSelectedCorrector($partArtProcDetails[$i]['article_id']);
									if($selectedcorrector!="NO")
									{
										$expires = $this->correctorExpireTime($partArtProcDetails[$i]['article_id'], $selectedcorrector);
										$datacorr = array("corrector_submit_expires"=>$expires,"participate_id"=>$participate_id);////////updating
										$querycorr = "corrector_id= '".$selectedcorrector."' AND article_id = '".$partArtProcDetails[$i]['article_id']."'";
										$crtpart_obj->updateCrtParticipation($datacorr,$querycorr);
										
										$data1 = array("corrector_id"=>$selectedcorrector);
										$query1 = "id= '".$participate_id."'";
										$participate_obj->updateParticipation($data1,$query1);
										
										//Mail to selected corrector
										$parameters['article_title']=$partArtProcDetails[0]['title'];
										$parameters['ongoinglink']='/contrib/ongoing';
										$parameters['AO_end_date']=date('d/m/Y H:i', $expires);
											$user_obj=new Ep_User_User();
											$correctordetails=$user_obj->getAllUsersDetails($selectedcorrector);
											if($correctordetails[0]['profile_type2']=='senior')
												$resubmission=$partArtProcDetails[0]['correction_sc_resubmission'];
											else
												$resubmission=$partArtProcDetails[0]['correction_jc_resubmission'];
												
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
										$newcorrector=$crtpart_obj->getNewCorrector($partArtProcDetails[$i]['article_id']);
										
										if(($partArtProcDetails[0]['correction_participationexpires']< strtotime('now') || $partArtProcDetails[0]['correction_participationexpires']==0)  && $newcorrector=="NO")
										{
                                    ////update the artlcle table with partcipation time/////////
                                    $this->CorrectorParticipationExpire($partArtProcDetails[$i]['article_id']);
                                    $artId = $partArtProcDetails[$i]['article_id'];
                                    $getpartid = $crtpart_obj->getCrtParticipationsUserIds($artId);
                                    if ($getpartid == 'NO') {
                                        $this->sendMailToCorrectors($artId);
                                    } else {
                                        ////get the current corrector to send him the mail////
                                        $getcrttid = $crtpart_obj->getCurrenctCycleCorrector($artId);
                                        /* * Sending mail to client when publish **/
                                        $ao_id = $delivery_obj->getDeliveryID($artId);
                                        $delartdetails = $delivery_obj->getArticlesOfDel($ao_id);
                                        $expires = time() + (60 * $delartdetails[0]['participation_time']);
                                        $aoDetails = $delivery_obj->getPrAoDetails($ao_id);
                                        $autoEmails = new Ep_Message_AutoEmails();
                                        $parameters['AO_title'] = $aoDetails[0]['title'];
                                        $parameters['article_title'] = $aoDetails[0]['artname'];
                                        $parameters['AO_end_date'] = $aoDetails[0]['delivery_date'];
                                        //$parameters['submitdate_bo']=$aoDetails[0]['submitdate_bo'];
                                        //$parameters['submitdate_bo']=date('d/m/Y H:i', $expires);
                                        $parameters['submitdate_bo'] = date('d/m/Y', $expires) . " &agrave; " . date('H:i', $expires);
                                        if ($aoDetails[0]['missiontest'] == 'yes')
                                            $mailId = 104;
                                        else
                                            $mailId = 21;
                                        $parameters['noofarts'] = $aoDetails[0]['noofarts'];
                                        if ($aoDetails[0]['deli_anonymous'] == '0')
                                            $parameters['article_link'] = "/contrib/aosearch?client_contact=" . $aoDetails[0]['user_id'];
                                        else
                                            $parameters['article_link'] = "/contrib/aosearch?client_contact=anonymous";
                                        $parameters['aoname_link'] = "/contrib/aosearch";
                                        $parameters['clientartname_link'] = "/client/quotes?id=" . $aoDetails[0]['articleid'];
                                        $autoEmails->messageToEPMail($getcrttid[0]['corrector_id'], $mailId, $parameters);
                                    }
										}
									}
                                } else
                                    $data = array("status" => 'under_study', "current_stage" => 'stage1');////////updating
                                $currentsatge = "stage1";
                                /////////////article history////////////////
                                $actparams['artId'] = $partArtProcDetails[$i]['article_id'];
                                $actparams['stage'] = "plagiarism cron";
                                $actparams['action'] = "plagiarised and validated";
                                $this->articleHistory(10, $actparams);
                                /////////////end of article history////////////////
                            }
                            $query = "id= '" . $participate_id . "'";
                            $participate_obj->updateParticipation($data, $query);
                            //}
                            $resultarr[$i]['artName'] = $artdetails[0]['title'];
                            $resultarr[$i]['aoName'] = $artdetails[0]['deliveryTitle'];
                            $resultarr[$i]['email'] = $artdetails[0]['email'];
                            $resultarr[$i]['plagpercent'] = $maxpercentage;
                            $resultarr[$i]['currentstage'] = $currentsatge;

                            $article_ext = pathinfo($partArtProcDetails[$i]['article_name']);
                            $resultarr[$i]['ext'] = $article_ext['extension'];
                        }
					}
                }
                ///sending alert mail to tech user on stuch up///
                if($sendmail == 'yes')
                    $this->plagstuckupcronmail($maildetials);
				if(count($resultarr)>0)
				{
					array_unshift($resultarr, array('ArticleName', 'AoTitle', 'Contributor', 'Plagiarism Result','Current Stage','Article Type'));
					$filename = "/home/sites/site8/web/BO/documents/plagresult.xls";
					$this->WriteXLS($resultarr, $filename);
					$filename = "plagresult.xls";
					$path="/home/sites/site8/web/BO/documents/";
					$mailto = "mailpearls@gmail.com";
					$from_mail = "mailpearls@gmail.com";
					$replyto = "mailpearls@gmail.com";
					$subject = "Plagiarism Results";
					$message = "Please find the attachement";
					//$this->sendMailWithAttachment($filename, $path, $mailto, $from_mail, $from_name, $replyto, $subject, $message);
				}
			}
			else
				echo "No files are available for plagiarism check";

            $this->updateCronLock('plagarism', 'unlocked');
        }
    }
    
    ///////when performing the plagarism for individual artlicle///////
    public function s0correctionplagarismAction()
    {
		ini_set('memory_limit', '-1');
		ini_set('suhosin.memory_limit', '-1');
		ini_set('upload_max_filesize', '500M');
		ini_set('post_max_size', '500M');
		ini_set('max_execution_time',0);
		
        error_reporting(1);
        $s0correctionplag = $this->_request->getParams();
        $artProcess_obj = new EP_Delivery_ArticleProcess();
        $delivery_obj = new Ep_Delivery_Delivery();
        $participate_obj = new EP_Participation_Participation();
        $autoemail_obj = new Ep_Message_AutoEmails();
		$crtpart_obj = new Ep_Participation_CorrectorParticipation();
		$automail=new Ep_Message_AutoEmails();
        $partArtProcDetails =  $participate_obj->s0CorrectionArtProcDetails($s0correctionplag['participateId']);
        $recentversion= $artProcess_obj->getRecentVersion($s0correctionplag['participateId']);
        
        require_once APP_PATH_ROOT.'nlibrary/script/filecontent.php';

        if($partArtProcDetails != 'NO')
        {
            $server_path = "/home/sites/site7/web/FO/articles/";
            //$srcFile =  $server_path.$partArtProcDetails[0]['article_path'];
            $filedetials = explode("/",$partArtProcDetails[0]['article_path']);
            $u_file_name =  $filedetials[1];
            $u_file_name_filename =  pathinfo($u_file_name, PATHINFO_FILENAME);
            $u_file_name_file_ext =  pathinfo($u_file_name, PATHINFO_EXTENSION);
            $filename =  $partArtProcDetails[0]['article_name'];
            $srcFile =  $server_path.$partArtProcDetails[0]['article_id']."/".$u_file_name_filename.".".$u_file_name_file_ext;
            $srcZipFile =  $u_file_name_filename.".zip";
            $srcRarFile =  $u_file_name_filename.".rar";
            $apId = $partArtProcDetails[0]['apId'];

            if(file_exists($srcFile) | file_exists($server_path.$partArtProcDetails[0]['article_id']."/".$srcZipFile) | file_exists($srcRarFile) | file_exists($server_path.$partArtProcDetails[0]['article_id']."/".$srcRarFile))
            {
               // echo "<pre>".$srcFile; echo "<pre>".$u_file_name; echo "<pre>".$filename; //exit;
                $response = $this->uploadAndProcess($srcFile,$u_file_name,$filename,0,$partArtProcDetails);
                if($response == 'File Size is more Than 800kb')
                {
                    if(!$artProcess_obj->getArticleFileSizeLimitEmailStatus($partArtProcDetails[0]['article_path']))
                    {
                        $this->sendPlagEpMailBox($partArtProcDetails[0], 0, '');
                        $data = array("art_file_size_limit_email"=>1);
                        $query = "article_path= '".$partArtProcDetails[0]['article_path']."'";
                        $artProcess_obj->updateArticleProcess($data,$query);    //exit('***---'.$query);
                    }
                    $maildetials['artname'] = $partArtProcDetails[0]['title'];
                    $maildetials['aoname'] = $partArtProcDetails[0]['delName'];
                    $maildetials['aoid'] = $partArtProcDetails[0]['delId'];
                    $this->plagstuckupmail($maildetials, $partArtProcDetails[0]['article_path']);
                    exit($response);
                }
                elseif(!file_exists($response))
                {
                    $maildetials['artname'] = $partArtProcDetails[0]['title'];
                    $maildetials['aoname'] = $partArtProcDetails[0]['delName'];
                    $maildetials['aoid'] = $partArtProcDetails[0]['delId'];
                    $this->plagstuckupmail($maildetials, $partArtProcDetails[0]['article_path']);
                    exit($response);
                }

                $xml_data=$this->XMLParserPercentage($response);
                $xmlpercentage=array();
                if($xml_data != '')
                {
                    $maxpercentage = @max($xml_data);
                    /*foreach($xml_data as $key => $value)
                    {
                        array_push($xmlpercentage, "$value");
                    }
                    $xmlpercentage = array_diff($xmlpercentage, array("NA"));
                    if($xmlpercentage != NULL)
                        $maxpercentage = @max($xmlpercentage);
                    else
                        $maxpercentage = "NA";*/
                }
                else
                {  $maxpercentage = 0;  }



                ////udate status in participate table///////
               $getParticipateId = $participate_obj->getParticipationsIdStage0($partArtProcDetails[0]['article_id'], $partArtProcDetails[0]['user_id']);
                $participate_id = $getParticipateId[0]['id'];

                if($maxpercentage >= ($this->configval['plag_cutoff_percentage'])){
                    $recentversion= $artProcess_obj->getRecentVersion($s0correctionplag['participateId']);
                    $artprocdata = array("plag_percent"=>$maxpercentage,"plagxml"=>strrev(substr(strrev($response), 0, strpos(strrev($response), '/'))));////////updating
                    $artprocquery = "participate_id= '".$s0correctionplag['participateId']."' AND version=".$recentversion[0]["version"]."";
                    $artProcess_obj->updateArticleProcess($artprocdata,$artprocquery);
                    $data = array("status"=>'under_study', "current_stage"=>'stage0');////////updating
                    $this->sendPlagEpMailBox($partArtProcDetails[0], 1, $maxpercentage);

                }
                else{
                    //////////new record in the article process table////////////////
                    $this->insertStageRecord($s0correctionplag['participateId'],$recentversion[0]["version"],'s0','approved');
                    ////udate status article process table///////
                    $recentversion= $artProcess_obj->getRecentVersion($s0correctionplag['participateId']);
                    $data = array("plag_percent"=>$maxpercentage,"plagxml"=>strrev(substr(strrev($response), 0, strpos(strrev($response), '/'))));////////updating
                    // $query = "article_path= '".$partArtProcDetails[0]['article_path']."'";
                    $query = "participate_id= '".$s0correctionplag['participateId']."' AND version=".$recentversion[0]["version"]."";
                    $artProcess_obj->updateArticleProcess($data,$query);
                    if($partArtProcDetails[0]['correction'] == 'yes') ///if the article is corection type
                    {
                        $data = array("status"=>'under_study', "current_stage"=>'corrector') ;////////updating
						//Simultaneous correction
						$selectedcorrector=$crtpart_obj->getSelectedCorrector($partArtProcDetails[0]['article_id']);
						if($selectedcorrector!="NO")
						{
							$expires = $this->correctorExpireTime($partArtProcDetails[0]['article_id'], $selectedcorrector);
							$datacorr = array("corrector_submit_expires"=>$expires,"participate_id"=>$participate_id);////////updating
							$querycorr = "corrector_id= '".$selectedcorrector."' AND article_id = '".$partArtProcDetails[0]['article_id']."'";
							$crtpart_obj->updateCrtParticipation($datacorr,$querycorr);
							
							$data1 = array("corrector_id"=>$selectedcorrector);
							$query1 = "id= '".$participate_id."'";
							$participate_obj->updateParticipation($data1,$query1);
							
							//Mail to selected corrector
							$parameters['article_title']=$partArtProcDetails[0]['title'];
							$parameters['ongoinglink']='/contrib/ongoing';
							$parameters['AO_end_date']=date('d/m/Y H:i', $expires);
								$user_obj=new Ep_User_User();
								$correctordetails=$user_obj->getAllUsersDetails($selectedcorrector);
								if($correctordetails[0]['profile_type2']=='senior')
									$resubmission=$partArtProcDetails[0]['correction_sc_resubmission'];
								else
									$resubmission=$partArtProcDetails[0]['correction_jc_resubmission'];
									
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
							$newcorrector=$crtpart_obj->getNewCorrector($partArtProcDetails[0]['article_id']);
							
							if(($partArtProcDetails[0]['correction_participationexpires']< strtotime('now') || $partArtProcDetails[0]['correction_participationexpires']==0)  && $newcorrector=="NO")
							{
                        $this->CorrectorParticipationExpire($partArtProcDetails[0]['article_id']);
                        $artId  =   $partArtProcDetails[0]['article_id'] ;
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
                            /**Sending mail to client when publish **/
                            $ao_id = $delivery_obj->getDeliveryID($artId);
                            $delartdetails = $delivery_obj->getArticlesOfDel($ao_id);
                            $expires=time()+(60*$delartdetails[0]['participation_time']);
                            $aoDetails=$delivery_obj->getPrAoDetails($ao_id);
                            $autoEmails=new Ep_Message_AutoEmails();
                            $parameters['AO_title']=$aoDetails[0]['title'];
                            $parameters['article_title'] = $aoDetails[0]['artname'];
                            $parameters['AO_end_date']=$aoDetails[0]['delivery_date'];
                            //$parameters['submitdate_bo']=$aoDetails[0]['submitdate_bo'];
                            //$parameters['submitdate_bo']=date('d/m/Y H:i', $expires);
                            $parameters['submitdate_bo']=date('d/m/Y', $expires)." &agrave; ".date('H:i', $expires);
                            if($aoDetails[0]['missiontest'] == 'yes')
                                $mailId = 104;
                            else
                                $mailId = 21;
                            $parameters['noofarts']=$aoDetails[0]['noofarts'];
                            if($aoDetails[0]['deli_anonymous']=='0')
                                $parameters['article_link']="/contrib/aosearch?client_contact=".$aoDetails[0]['user_id'];
                            else
                                $parameters['article_link']="/contrib/aosearch?client_contact=anonymous";
                            $parameters['aoname_link'] = "/contrib/aosearch";
                            $parameters['clientartname_link'] = "/client/quotes?id=".$aoDetails[0]['articleid'];
                            $autoemail_obj->messageToEPMail($getcrttid[0]['corrector_id'],$mailId,$parameters);
                        }
							}
						}

                    }
                    else{
                        $data = array("status"=>'under_study', "current_stage"=>'stage1');////////updating

                    }
                }
                $query = "id= '".$participate_id."'";
                $participate_obj->updateParticipation($data,$query);
                /// unlock the article///////////////
                if($maxpercentage <= 20)
                    $this->unlockonactionAction($artId);
                echo $maxpercentage; exit;////seding to the ajax fucntion back///*/
            }
        }
        else
            echo "There are no files to check plagiarism";
    }

    ////when file is stuck up in plagiarism stage ///
    public function plagstuckupmail($maildetials, $articlepath)
    {
        $artProcess_obj = new EP_Delivery_ArticleProcess();
        $data = array("plag_stuck"=>'yes');
        $query = "article_path= '".$articlepath."'";
        $artProcess_obj->updateArticleProcess($data,$query);

        ///sending personal mails to chandu and manohar///
        $Message="<p>Hi,<br><br>
                            The file have been stuck up in plagiarism stage. Details are as follows :<br><br>
                            Ao Title : ".($maildetials['aoname'])."<br>
                            Article Title : ".($maildetials['artname'])."<br>
                            link to palgiarism stage : <a href=\"http://admin-test.edit-place.co.uk/proofread/stage-articles?submenuId=ML3-SL11&aoId=".$maildetials['aoid']."\">Stage Plagiarism</a><br>
                            link to palgiarism stage : <a href=\"http://admin-test.edit-place.co.uk/proofread/plag-stuck-arts?submenuId=ML3-SL13\">List of stuck up files</a><br><br>
                            <br><br>
                             Cordialement,<br><br>
                            Toute l'&eacute;quipe d&rsquo;Edit-place<br><br><p>  ";

        $mail = new Zend_Mail();
        $mail->addHeader('Reply-To','support@edit-place.com');
        $mail->setBodyHtml($Message)
            ->setFrom('support@edit-place.com')
            ->addTo('chandu@edit-place.com')
            ->addCc('rakeshm@edit-place.com')
            ->addCc('claurent@edit-place.com')
            ->addCc('manohar@edit-place.com')
            ->setSubject("File stuck in plagiarism stage");
        $mail->send();
    }
    ////when file is stuck up in plagiarism stage ///
    public function plagstuckupcronmail($maildetials)
    {
      $msgdetails = "<table border='1'><tr><th>AO Title</th><th>Article Title</th><th>Plagiarism Stage</th><th>Stuck files</th></tr>";
        for($i= 0; $i < count($maildetials); $i++){
            $msgdetails.= "<tr><td>".($maildetials[$i]['aoname'])."</td><td>".($maildetials[$i]['artname'])."</td>";
            $msgdetails.= "<td><a href=\"http://admin-test.edit-place.co.uk/proofread/stage-articles?submenuId=ML3-SL11&aoId=".$maildetials[$i]['aoid']."\">Stage Plagiarism</a></td>";
            $msgdetails.= "<td><a href=\"http://admin-test.edit-place.co.uk/proofread/plag-stuck-arts?submenuId=ML3-SL13\">List of stuck up files</a></td></tr>";
        }
        $msgdetails.= "</table>";
        ///sending personal mails to chandu and manohar///
        $Message="<p>Hi,<br><br>
                            Some file have been stuck up in plagiarism stage. Details are as follows :<br><br>
                            ".$msgdetails."<br><br>
                             Cordialement,<br><br>
                            Toute l'&eacute;quipe d&rsquo;Edit-place<br><br><p>  ";

        $mail = new Zend_Mail();
        $mail->addHeader('Reply-To','support@edit-place.com');
        $mail->setBodyHtml($Message)
            ->setFrom('support@edit-place.com')
            ->addTo('chandu@edit-place.com')
            ->addCc('rakeshm@edit-place.com')
            ->addCc('claurent@edit-place.com')
            ->addCc('manohar@edit-place.com')
            ->setSubject("File stuck in plagiarism stage");
        $mail->send();
    }

    /**function to connect to the linode server, uploading the csv and processing the csv file**/
    public function uploadAndProcess($srcFile,$u_filename,$filename,$cron, $partArtProcDetails)
    {
        $this->getSFTPobjectAction() ;

        //Path to execute ruby command
        $file_exec_path=$this->sftp->exec("./test_ep_plag_exec.sh "); //ruby execution path

        /**getting upload path from alias**/
        $file_upload_path=$this->sftp->exec("./test_ep_plag_upload.sh");


        /**getting download path from alias**/
        $file_download_path=$this->sftp->exec("./test_ep_plag_download.sh");

        /**sending uploaded file to the server**/
        $this->sftp->chdir(trim($file_upload_path));

        if($u_filename ==  'many')
            $u_file_name    =   strrev(substr(strrev($srcFile), 0, strpos(strrev($srcFile), '/'))) ;
        else
            $u_file_name    =   $u_filename ;

        $this->sftp->put($u_file_name,$srcFile,NET_SFTP_LOCAL_FILE);

        /**processing the file**/

        /**passing file name**/
        $src=pathinfo($u_file_name);
        $download_fname=$src['filename'];
        $dstfile=$download_fname.".".$src['extension'];
        $dstfile_xml=$download_fname.".xml";
        $ext = $src['extension'];
        $articleName = $partArtProcDetails[0]['article_name'];
        $urlsexcluded = str_replace(',','|',$partArtProcDetails[0]['urlsexcluded']);
        if($ext == 'xls' || $ext == 'xlsx'){
            if($partArtProcDetails[0]['column_xls'] == null)
                $xlscolumns = "all";
            else
                $xlscolumns = $partArtProcDetails[0]['column_xls'];
        }else
            $xlscolumns = "all";

        /**processing File based on Options**/
        //$ruby_file="check_backup.rb";
        //$ruby_file="plag_top.rb";
        $ruby_file="plag_allformat.rb";
        $user_id = $this->adminLogin->userId ;
        $user_name = $this->adminLogin->loginName ;
        $max = 3;
        //$cmd="ruby -W0 $ruby_file $u_file_name $dstfile $dstfile_xml $user_id $user_name 2>&1 ";

                
          $cmd="bundle exec ruby -W0 $ruby_file $user_id $user_name '$ext' '$dstfile' '$articleName' '$dstfile_xml' $max '$xlscolumns' '$urlsexcluded' 2>&1 ";

        ///writing cmd to file for reference///
        $cmdfilename = "/home/sites/site8/web/BO/plagarism/cmdfile.txt";
        $cmdfile = fopen($cmdfilename, "a") or die("Unable to open file!");
        $txt = $cmd." at ".date('Y-m-d H:i:s')."\n";
        fwrite($cmdfile, $txt);
        fclose($cmdfile);
        //////////////////
        $this->sftp->setTimeout(3000);
        //echo $ssh->exec("whoami; source ~/.rvm/scripts/rvm; rvm use 1.9.3-head; which ruby");
        //exit(0);
        $file_exec_path=trim($file_exec_path);
        $ruby_switch_prefix = "source ~/.rvm/scripts/rvm; rvm use 1.9.3-head ";
        $output= $this->sftp->exec("$ruby_switch_prefix ;cd $file_exec_path;$cmd ;");

        //echo $sftp->exec($cmd);
        //sleep($total_rows*10);

        /**Downloading the Processed File**/

        /**processed file path**/
        $remoteFile=trim($file_download_path)."/".$dstfile_xml;

        $this->sftp->chdir(trim($file_download_path));
        $file_path=pathinfo($remoteFile);
        $xmlfilefolder = pathinfo($srcFile, PATHINFO_FILENAME);
        // echo  "<br>".$localFile=APP_PATH_ROOT."plagarism/".$xmlfilefolder."/".$filename;
        $localFile=APP_PATH_ROOT."plagarism/".$dstfile_xml;
        $serverfile=$file_path;
        $fname=$file_path['filename'];
        $ext=$file_path['extension'];
        
        if(strlen(strip_tags(nl2br(trim($output)))) == strlen('Using /home/oboulo/.rvm/gems/ruby-1.9.3-head File Size is more Than 800kb'))
        {
            if($cron)   return 0 ;  else    return 'File Size is more Than 800kb';
        }
        else {
            //downloading the file from remote server
            $this->sftp->get($dstfile_xml,$localFile);
            if(file_exists($localFile))
                return  $localFile;
            else
                return  $output;
        }
    }

    //display xml data of plagarism in popup////
    public function XMLParser($file, $fileorgname)
    {
        $xml = file_get_contents($file);
        $data = $xml;

        return $data;
        exit;
    }

    public function XMLParserPercentage($file)
    {
        $xml = simplexml_load_file($file);
        $i=0;
        /*foreach($xml->article1->Result->url as $URL)
        {
            $percentage[$i] = $xml->article1->Result->percentage[$i];
            $i++;
        }
        foreach($xml->article->result->url as $URL)
        {
            $percentage[$i] = $xml->article->result->percentage[$i]->object;
            $i++;
        }*/
		error_reporting(0) ;
        foreach( $xml->children() AS $child ){
            foreach( $child->results->children() AS $child1 ){
                foreach( $child1->percentage->children() AS $child2 ){
                    if($child2->getName() == 'p') {
                        $percentage[$i] =   (int)$child2 ;
                        $i++ ;
                    }
                }
            }
        }
        return $percentage;
        //exit;
    }

    public function plagdetailsAction()
    {
        $plag_params=$this->_request->getParams();
        $artprocess_obj=new EP_Delivery_ArticleProcess();
        $xmlfile=$artprocess_obj->getVersionDetailsByVersion($plag_params['part_id'], $plag_params['version']);
        $xmlfilepath = $xmlfile[0]['article_path'];
        $xmlfileorgname = $xmlfile[0]['article_name'];
        $xmlfilename = pathinfo($xmlfilepath, PATHINFO_FILENAME);
        $filepath = APP_PATH_ROOT."plagarism/".$xmlfilename.".xml";
        //$filepath = APP_PATH_ROOT."plagarism/222120138483308_120723140200206_34865.xml";
        if (file_exists($filepath)) {
            $plagdetails=$this->XMLParser($filepath, $xmlfileorgname);
        } else {
            $plagdetails = "<b style='color: #550000;padding-top: 25px;'>The plagarism check not been done to this file</b>";
        }
        //$plagdetails=$this->XMLParser($filepath, $xmlfileorgname);
        $this->_view->plagdetails=$plagdetails;
        $this->_view->render("proofread_plagpopup");
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
    /**function to create XLS file**/
    function WriteXLS($data,$file_name)
    {
        // include package
        include 'Spreadsheet/Excel/Writer.php';

        // create empty file
        $excel = new Spreadsheet_Excel_Writer($file_name);
        $excel->setVersion(8);

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
            $column = 0 ;
            foreach ($row as $key => $value) {

                if($this->getOS($_SERVER['HTTP_USER_AGENT']) != 'Windows' )
                    $value=utf8_decode($value);

                if($rowCount==0)
                    $sheet->write($rowCount, $column, $value,$firstRow);
                else
                    $sheet->write($rowCount, $column, $value);
                $column++;
            }
            $rowCount++;
        }
        // save file to disk
        $excel->close();
    }
    function sendMailWithAttachment($filename, $path, $mailto, $from_mail, $from_name, $replyto, $subject, $message)
    {
        $file = $path.$filename;
        $file_size = filesize($file);
        $handle = fopen($file, "r");
        $content = fread($handle, $file_size);
        fclose($handle);
        $content = chunk_split(base64_encode($content));
        $uid = md5(uniqid(time()));
        $name = basename($file);
        $header = "From: ".$from_name." <".$from_mail.">\r\n";
        $header .= "Reply-To: ".$replyto."\r\n";
        $header .= "MIME-Version: 1.0\r\n";
        $header .= "Content-Type: multipart/mixed; boundary=\"".$uid."\"\r\n\r\n";
        $header .= "This is a multi-part message in MIME format.\r\n";
        $header .= "--".$uid."\r\n";
        $header .= "Content-type:text/plain; charset=iso-8859-1\r\n";
        $header .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
        $header .= $message."\r\n\r\n";
        $header .= "--".$uid."\r\n";
        $header .= "Content-Type: application/octet-stream; name=\"".$filename."\"\r\n"; // use different content types here
        $header .= "Content-Transfer-Encoding: base64\r\n";
        $header .= "Content-Disposition: attachment; filename=\"".$filename."\"\r\n\r\n";
        $header .= $content."\r\n\r\n";
        $header .= "--".$uid."--";
        if (mail($mailto, $subject, "", $header)) {
            echo "mail send ... OK"; // or use booleans here
        } else {
            echo "mail send ... ERROR!";
        }
    }
    ///to unzip files///
    function unzip($file)
    {
        // get the absolute path to $file
        //$path = pathinfo(realpath($file), PATHINFO_DIRNAME);
        $zip_file=pathinfo($file);
        $zip_file['filename']=str_replace(" ","-",$zip_file['filename']);
        $path=$zip_file['dirname']."/".$zip_file['filename'];
        if(!is_dir($path))
            mkdir($path,0777,TRUE);

        chmod($path,0777) ;

        $zip = new ZipArchive;
        $res = $zip->open($file);
        if ($res === TRUE) {

            // extract it to the path we determined above
            for ( $i=0; $i < $zip->numFiles; $i++ )
            {
                $entry = $zip->getNameIndex($i);

                if ( (substr( $entry, -1 ) == '/') || strstr($entry,'__MACOSX') ) continue; // skip directories

                $entry1=frenchCharsToEnglish(str_replace(' ', '_',$entry));
                $fp = $zip->getStream( $entry );
                $ofp = fopen( $path.'/'.basename($entry1), 'w' );
                if ($fp )
                {
                    while ( ! feof( $fp ) )
                        fwrite( $ofp, fread($fp, 8192) );
                }
                fclose($fp);
                fclose($ofp);
            }
            return $path ;
        } else {
            echo "Doh! I couldn't open $file";
        }
    }

    function is_empty_dir($dir)
    {
        if (($files = @scandir($dir)) && count($files) <= 2) {
            return true;
        }
        return false;
    }

    /////////////////////when corrector disapproves from FO it will come to moderation page for BO approval///////////////////
    public function moderationAction()
    {
        $prevurl = getenv("HTTP_REFERER");
        $userplus_obj=new Ep_User_UserPlus();
        $automail=new Ep_Message_AutoEmails();
        $artProcess_obj = new EP_Delivery_ArticleProcess();
        $partParams=$this->_request->getParams();
        $userobj = new Ep_User_User();
        ////getting BO users////////
        $user_obj = new Ep_User_User();
        $users = $user_obj->getUsers();
        $this->_view->userList = $users;
        $this->_view->loginuser = $this->adminLogin->userId;
        $correctorparticipate_obj = new Ep_Participation_CorrectorParticipation();
        $article_obj = new EP_Delivery_Article();
        $delivery_obj = new Ep_Delivery_Delivery();
        $participate_obj = new EP_Participation_Participation();
        $condition = '';
        if(isset($partParams['bouser']) && $partParams['bouser'] != '')
            $condition.= " AND d.created_user= '".$partParams['bouser']."'" ;
        else
            $condition.= " AND d.created_user= '".$this->adminLogin->userId."'" ;
        $condition.= " AND (p.moderate_closed IS NULL OR p.moderate_closed = 'yes')";
        $res= $article_obj->CorrectorDisapprovals($condition);
        if($res != "NO")
        {
            foreach($res as $res_key => $res_value)
            {
                $contributorname = $userplus_obj->getUsersDetailsOnId($res[$res_key]['contributor']);
                $res[$res_key]['contributor_name'] =  $contributorname[0]['first_name']." ".$contributorname[0]['last_name']."(".$contributorname[0]['email'].")";

                $correctorname = $userplus_obj->getUsersDetailsOnId($res[$res_key]['corrector']);
                $res[$res_key]['corrector_name'] = $correctorname[0]['first_name']." ".$correctorname[0]['last_name']."(".$correctorname[0]['email'].")";
                $artList[$res_value['artId']]=strtoupper($res_value['title']);
            }
            $this->_view->paginator = $res;

        }
        ///////////////////////////////////////////////////////////////////////////
        if(isset($partParams["moderate_disapprove"])) ///when refuse link id clicked
        {     
             //////////////////////////////////////////////////////////////////////////
            $partsOfArt= $participate_obj->getAllParticipationsCorrectorDisapprove($partParams['articleid']);
            $partId =  $partsOfArt[0]['id'];
			
			//Added By arun
			$prt_details=$participate_obj->getParticipateDetails($partId);
			if($prt_details[0]['user_id'])
				$partParams['contributorId']=$prt_details[0]['user_id'];
			if($prt_details[0]['corrector_id'])
				$partParams['correctorId']=$prt_details[0]['corrector_id'];	
			
            $crtpartsOfArt= $correctorparticipate_obj->getParticipationsCorrectorToDisapprove($partParams['articleid']); 
            $crtpartId =  $crtpartsOfArt[0]['id'];  
            $partdetials = $correctorparticipate_obj->getCrtParticipationsStatus($crtpartId); 
            $partStatus = $partdetials[0]['status'];
            $partUser =  $partdetials[0]['corrector_id'];
            $refusedcountupdated =$partdetials[0]['refused_count'];
            $refusedcountupdated++;
            $partuserdetials = $userplus_obj->getUsersDetailsOnId($partUser);
            $deliveryDetails = $delivery_obj->getArtDeliveryDetails($partParams['articleid']);

            if($partuserdetials[0]['profile_type2']=='senior')
            {
                if($deliveryDetails[0]['correction_sc_resubmission'])
                    $resubmission=$deliveryDetails[0]['correction_sc_resubmission'];
                else
                    $resubmission=$this->config['correction_sc_resubmission'];
            }
            else
            {
                if($deliveryDetails[0]['correction_jc_resubmission'])
                    $resubmission=$deliveryDetails[0]['correction_jc_resubmission'];
                else
                    $resubmission=$this->config['correction_jc_resubmission'];
            }

            $expires=time()+(60*$resubmission);
            /////////////////inserting the article process////////////////////////////////////
            $recentversion= $artProcess_obj->getRecentVersionByTime($partId);
            ////udate article process table for EP user decision///////
            $data = array("moderate_epdecision"=>"refused");////////updating
            $query = "id= '".$recentversion[0]["id"]."'";
            $artProcess_obj->updateArticleProcess($data,$query);
            ////udate status participation table for stage///////
            $data = array("current_stage"=>"corrector", "status"=>"under_study", "moderate_closed"=>"yes");////////updating
            $query = "id= '".$partId."'";
            $participate_obj->updateParticipation($data,$query);
            ////udate status corrector participation table for stage///////
            $data = array("current_stage"=>"contributor", "status"=>"bid", "corrector_submit_expires"=> $expires);////////updating
            $query = "participate_id= '".$crtpartId."' AND status = 'bid' AND cycle = 0 ";
            $correctorparticipate_obj->updateCrtParticipation($data,$query);

            //////sending mail to corrector///////////////
            $correctorId = $partParams['correctorId'];
            $email=$automail->getAutoEmail($partParams["refusemailid"]);
            $Object = $email[0]['Object'];
            $automail->sendMailEpMailBox($correctorId,$Object,$partParams["Moderator_comment"]);  ///sending mail to EP account
            if($partParams["status"] != "disapproved_temp")
            {
                /////////////article history////////////////
                $actparams['contributorId'] = $correctorId;  ///this corrector who did refused definite from FC///
                $actparams['artId'] = $partParams['articleid'];
                $actparams['stage'] = "refused definite from corrector and arrived in moderation";
                $actparams['action'] = "Refused";
                $this->articleHistory(28,$actparams);
            }
            else{
                /////////////article history////////////////
                $actparams['contributorId'] = $correctorId;  ///this corrector who did refused definite from FC///
                $actparams['artId'] = $partParams['articleid'];
                $actparams['stage'] = "refused from corrector and arrived in moderation";
                $actparams['action'] = "Refused";
                $this->articleHistory(34,$actparams);
            }
            /////////////end of article history////////////////
            //$this->sendMailEpMailBox($receiverId,$Object,$Message);
            $this->_helper->FlashMessenger(utf8_decode('Article modèrè avec succès'));
            $this->_redirect("/correction/moderation?submenuId=ML3-SL10");
        }
        if(isset($partParams["moderate_approve"]))///when accept link id clicked
        {   //echo "hi"; print_r($partParams); exit;
            //////////////////////////////////////////////////////////////////////////
            $partsOfArt= $participate_obj->getAllParticipationsCorrectorDisapprove($partParams['articleid']);
            $partId =  $partsOfArt[0]['id'];
			
			//Added By arun
			$prt_details=$participate_obj->getParticipateDetails($partId);
			if($prt_details[0]['user_id'])
				$partParams['contributorId']=$prt_details[0]['user_id'];
			if($prt_details[0]['corrector_id'])
				$partParams['correctorId']=$prt_details[0]['corrector_id'];	
			
            $partdetials = $participate_obj->getParticipationsStatus($partId);
            $partStatus = $partdetials[0]['status'];
            $partUser =  $partdetials[0]['user_id'];
            $refusedcountupdated =$partdetials[0]['refused_count'];
            $refusedcountupdated++;
            $partuserdetials = $userplus_obj->getUsersDetailsOnId($partUser);
            $deliveryDetails = $delivery_obj->getArtDeliveryDetails($partParams['articleid']);
            //////////getting the latest version from article process tabel///////////////
            $versions_details= $artProcess_obj->getVersionModerationDetails($partId);
            for($j=0; $j<count($versions_details); $j++)
            {
                $correctorComments = $versions_details[$j]['comments'];
                $articlesentat = $versions_details[$j]['article_sent_at'];
            }
                if($partuserdetials[0]['profile_type']=='senior')
                {
                    if($deliveryDetails[0]['sc_resubmission'])
                        $resubmission=$deliveryDetails[0]['sc_resubmission'];
                    else
                        $resubmission=$this->config['sc_resubmission'];
                }
                else if($partuserdetials[0]['profile_type']=='junior')
                {
                    if($deliveryDetails[0]['jc_resubmission'])
                        $resubmission=$deliveryDetails[0]['jc_resubmission'];
                    else
                        $resubmission=$this->config['jc_resubmission'];
                }
                else if($partuserdetials[0]['profile_type']=='sub-junior')
                {
                    if($deliveryDetails[0]['jc0_resubmission'])
                        $resubmission=$deliveryDetails[0]['jc0_resubmission'];
                    else
                        $resubmission=$this->config['jc0_resubmission'];
                }
            $expires=time()+(60*$resubmission);
            /////////////////inserting the article process////////////////////////////////////
            $recentversion= $artProcess_obj->getRecentVersionByTime($partId);
            ////udate article process table for EP user decision///////
            $data = array("moderate_epdecision"=>"accepted");////////updating
            $query = "id= '".$recentversion[0]["id"]."'";
            $artProcess_obj->updateArticleProcess($data,$query);
            /////udate status article process table///////
            $recentversion2= $artProcess_obj->getRecentVersion($partId);
            $this->insertStageRecord($partId,$recentversion2[0]["version"],'corrector','disapproved');
            ///////////////////////////////////////////////////////////////////////////
            if($partParams["status"] == "disapproved_temp")
            {    //echo "hi 1"; exit;
                $data = array("current_stage"=>"corrector", "status"=>"disapproved", "article_submit_expires"=> $expires,
                    "refused_count"=>$refusedcountupdated, "marks"=>$partParams['latestmarks'], "moderate_closed"=>"yes");////////updating
                $query = "id= '".$partId."'";
                $participate_obj->updateParticipation($data,$query);
                //////sending mail to contributor///////////////
                if($resubmission <= '60')
                    $parameters['resubmit_hours']=$resubmission." minutes";
                else
                    $parameters['resubmit_hours']= $this->minutesToHours($resubmission)." hour(s)";
                $parameters['comments']=$partParams['Moderator_comment'];
                $parameters['article_title']=$deliveryDetails[0]['articleName'];
                $parameters['articlename_link']="/contrib/mission-deliver?article_id=".$partParams['articleid'];
                $parameters['article_link']="http://ep-test.edit-place.co.uk/contrib/refused";
                $parameters['correctorcomments']=$correctorComments;
                $contribId = $partParams['contributorId'];
                $automail->messageToEPMail($contribId,57,$parameters);//    sending mail to contributor
                $correctorId = $partParams['correctorId'];
                //  $this->messageToEPMail($correctorId,58,$parameters);//
                /*$Object = $partParams["Contribmailobject"];
                $this->sendMailEpMailBox($contribId,$Object,$partParams["Moderator_contribwindow"]);  ///sending mail to EP account*/
                $email=$automail->getAutoEmail($partParams["acceptmailid"]);
                $Object = $email[0]['Object'];
                $automail->sendMailEpMailBox($correctorId,$Object,$partParams["Moderator_crtwindow"]);  ///sending mail to EP account
                /////////////article history////////////////
                $actparams['contributorId'] = $correctorId;  ///this corrector who did refused definite from FC///
                $actparams['artId'] = $partParams['articleid'];
                $actparams['stage'] = "refused from corrector and arrived in moderation";
                $actparams['action'] = "validated";
                $this->articleHistory(35,$actparams);
                /////////////end of article history////////////////
            }
            else
            {
                if($partParams["sendtofo"] == 'yes')
                {
                    $cycleCount = $participate_obj->getParticipationCycles($partParams['articleid']);
                    $cycleCount = $cycleCount[0]['cycle']+1;
                    /////udate status participation table with article id///////
                    $data = array("cycle"=>$cycleCount, "moderate_closed"=>"yes");////////updating
                    $query = "article_id= '".$partParams['articleid']."' and cycle=0";
                    $participate_obj->updateParticipation($data,$query);
                    ////update the artlcle table with partcipation time/////////
                    $this->WriterParticipationExpire($partParams['articleid']);
                    $data = array("current_stage"=>"corrector", "status"=>"closed",  "moderate_closed"=>"yes",
                        "marks"=>$partParams['latestmarks']);////////updating
                    $query = "id= '".$partId."'";
                    $participate_obj->updateParticipation($data,$query);
                    if($partParams["anouncebyemail"] == 'yes')
                        $this->sendMailToContribs($partParams['articleid']);
                }
                elseif($partParams["sendtofo"] == 'no')
                {
                    ////updating the article tabel article submit expire wiht zero///////
                    $data = array("send_to_fo"=>"no","file_path"=>"");////////updating
                    $query = "id = '".$partParams['articleid']."'";
                    $article_obj->updateArticle($data,$query);
					
					 $data = array("current_stage"=>"corrector", "status"=>"closed",  "moderate_closed"=>"yes",
                        "marks"=>$partParams['latestmarks']);////////updating
                    $query = "id= '".$partId."'";
                    $participate_obj->updateParticipation($data,$query);
                }
                //////sending mail to contributor///////////////
                $contributor_participation=$participate_obj->getParticipateDetails($partId);
                $parameters['correcteddate']=date("d/m/Y H:i",strtotime($contributor_participation[0]['updated_at']));
				$parameters['article_sent_date']=date("d/m/Y H:i",strtotime($contributor_participation[0]['updated_at']));
                $parameters['comments']=$partParams['Moderator_comment'];
                $parameters['correctorcomments']=$correctorComments;
                $contribId = $partParams['contributorId'];
                $automail->messageToEPMail($contribId,60,$parameters);
                // $correctorId = $partParams['correctorId'];
                // $this->messageToEPMail($correctorId,61,$parameters);//  */
                // $contribId = $partParams['contributorId'];
                // $this->messageToEPMail($receiverId,57,$parameters);//
                $correctorId = $partParams['correctorId'];
                //  $this->messageToEPMail($correctorId,58,$parameters);//
                /* $Object = $partParams["Contribmailobject"];
                 $this->sendMailEpMailBox($contribId,$Object,$partParams["Moderator_contribwindow"]);  ///sending mail to EP account*/
                $email=$automail->getAutoEmail($partParams["acceptmailid"]);
                $Object = $email[0]['Object'];
                //$Object = $partParams["Correctormailobject"];
                $automail->sendMailEpMailBox($correctorId,$Object,$partParams["Moderator_crtwindow"]);  ///sending mail to EP account
                /////////////article history////////////////
                $actparams['contributorId'] = $correctorId;  ///this corrector who did refused definite from FC///
                $actparams['artId'] = $partParams['articleid'];
                $actparams['stage'] = "refused definite from corrector and arrived in moderation";
                $actparams['action'] = "validated";
                $this->articleHistory(29,$actparams);
                /////////////end of article history////////////////
            }
            $this->_helper->FlashMessenger(utf8_decode('Article modèrè avec succès'));
            //$this->_redirect($prevurl);
            $this->_redirect("/correction/moderation?submenuId=ML3-SL10");
        }
        $this->_view->render("correction_moderation");
    }
    ////////////////article under moderation correction page////////////////////////
    public function moderationCorrectionAction()
    {
        $prevurl = getenv("HTTP_REFERER");
        $artId = $this->_request->getParam('articleid');
        $partId = $this->_request->getParam('participateId');
        $article_obj = new EP_Delivery_Article();
        $del_obj = new Ep_Delivery_Delivery();
        $user_obj = new Ep_User_User();
        $participate_obj = new EP_Participation_Participation();
        $artProcess_obj = new EP_Delivery_ArticleProcess();
        if($artId!=NULL)
        {
            //display article process grid in moderation correction/////////
            $partsOfArt = $participate_obj->getAllParticipationsModeration($artId);
            if($partsOfArt != 'NO')
            {
                $partId = $partsOfArt[0]['id'];
                $this->_view->partId = $partId;
                $versions_details = $artProcess_obj->getVersionModerationDetails($partId);
				
				$j=0;
				foreach($versions_details as $version)
				{
					$versions_details[$j]['comments']=str_replace('"',"'",$version['comments']);
					$versions_details[$j]['contribmarks'] = $participate_obj->getContributorMarks($versions_details[$j]['participate_user']);
					$versions_details[$j]['epcontribmarks']  = $participate_obj->getEpContributorMarks($versions_details[$j]['participate_user']);
					$j++;
				}
                $this->_view->versions_details = $versions_details;
            }
            $details= $article_obj->getArticleDetails($artId);
            $this->_view->articledetails=$details;
            $users = $user_obj->getAllUsersDetails($details[0]['created_user']);
            $this->_view->loginusername = $users[0]['login'];
            $deldetails =  $del_obj->getDeliveryDetails($details[0]['deliveryId']);///to display in services box///
            $this->_view->deldetails=  $deldetails;
        }
        $this->_view->render("correction_moderationcorrection");
    }
    ///when accepted in moderation//////
    public function getmoderatemailcontentAction()
    {
        $prevurl = getenv("HTTP_REFERER");
        $participate_obj = new EP_Participation_Participation();
        $crtparticipate_obj = new Ep_Participation_CorrectorParticipation();
        $user_obj = new Ep_User_User();
        $userplus_obj = new Ep_User_UserPlus();
        $delivery_obj = new Ep_Delivery_Delivery();
        $automail_obj = new Ep_Message_AutoEmails();
        $artProcess_obj = new EP_Delivery_ArticleProcess();
        $profile_params=$this->_request->getParams();
        ////////////////////////////
        $partParams=$this->_request->getParams();
        $autoEmails=new Ep_Message_AutoEmails();
        //display article process popup/////////
        $partsOfArt = $participate_obj->getAllParticipationsCorrectorDisapprove($partParams['articleid']);
        $partId = $partsOfArt[0]['id'];
        $this->_view->partId = $partId;
        $versions_details = $artProcess_obj->getVersionDetails($partId);
        $this->_view->versions_details = $versions_details;
        //////////////////////////////////////////////////////////////////////////
        $partsOfArt= $participate_obj->getAllParticipationsCorrectorDisapprove($partParams['articleid']);
        $partId =  $partsOfArt[0]['id'];
        $partdetials = $participate_obj->getParticipationsStatus($partId);
        $partStatus = $partdetials[0]['status'];
        $partUser =  $partdetials[0]['user_id'];
        $refusedcountupdated =$partdetials[0]['refused_count'];
        $refusedcountupdated++;
        $partuserdetials = $userplus_obj->getUsersDetailsOnId($partUser);
        $deliveryDetails = $delivery_obj->getArtDeliveryDetails($partParams['articleid']);
        /////getting the last version comments from the corrector when he refuses from versions////
        $artProcess_obj = new EP_Delivery_ArticleProcess();
        $versions_details= $artProcess_obj->getVersionDetails($partId);
        for($j=0; $j<count($versions_details); $j++)
        {
            $correctorComments = $versions_details[$j]['comments'];
            $articlesentat = $versions_details[$j]['article_sent_at'];
        }

            if($partuserdetials[0]['profile_type']=='senior')
            {
                if($deliveryDetails[0]['sc_resubmission'])
                    $resubmission=$deliveryDetails[0]['sc_resubmission'];
                else
                    $resubmission=$this->config['sc_resubmission'];
            }
            else if($partuserdetials[0]['profile_type']=='junior')
            {
                if($deliveryDetails[0]['jc_resubmission'])
                    $resubmission=$deliveryDetails[0]['jc_resubmission'];
                else
                    $resubmission=$this->config['jc_resubmission'];
            }
            else if($partuserdetials[0]['profile_type']=='sub-junior')
            {
                if($deliveryDetails[0]['jc0_resubmission'])
                    $resubmission=$deliveryDetails[0]['jc0_resubmission'];
                else
                    $resubmission=$this->config['jc0_resubmission'];
            }
        $expires=time()+(60*$resubmission);
        ///////////////////////////////////////////////////////////////////////////
        if($partParams["actionmode"] == "accept")
        {
            //////sending mail to contributor///////////////
            if($resubmission <= '60')
                $parameters['resubmit_hours']=$resubmission." minutes";
            else
                $parameters['resubmit_hours']= $this->minutesToHours($resubmission)."hour(s)";
            $parameters['correctorcomments']=$correctorComments;
            $parameters['articlesentat']=date("d/m/Y H:i",strtotime($articlesentat));
            $parameters['article_title']=$deliveryDetails[0]['articleName'];
            $parameters['articlename_link']="/contrib/mission-deliver?article_id=".$partParams['articleid'];
            $parameters['article_link']="http://ep-test.edit-place.co.uk/contrib/ongoing";
            //////sending mail to contributor///////////////
            $contributor_participation=$participate_obj->getParticipateDetails($partId);

            $parameters['correcteddate']=date("d/m/Y H:i",strtotime($contributor_participation[0]['updated_at']));
            $parameters['comments']=$partParams['Moderator_comment'];
            $receiverId = $partParams['contributorId'];
            $emailContrib = $automail_obj->getMailComments(NULL,$partParams['contribmailId'],$parameters);
            $emailCorrector = $automail_obj->getMailComments(NULL,$partParams['crtmailId'],$parameters);
            $objectContrib = $automail_obj->getMailObject($partParams['contribmailId']);
            $objectCorrector = $automail_obj->getMailObject($partParams['crtmailId']);
            echo  $this->_view->Correctormailcontent = utf8_encode(stripslashes($emailCorrector)); exit;

        }
        else
        {
            //////sending mail to contributor///////////////
            if($resubmission <= '60')
                $parameters['resubmit_hours']=$resubmission." minutes";
            else
                $parameters['resubmit_hours']= $this->minutesToHours($resubmission)." hour(s)";

            $parameters['comments']=$partParams['Moderator_comment'];
            $parameters['article_title']=$deliveryDetails[0]['articleName'];
            $parameters['articlename_link']="/contrib/mission-corrector-deliver?article_id=".$partParams['articleid'];
            $parameters['article_link']="http://ep-test.edit-place.co.uk/contrib/ongoing";
            //////sending mail to contributor///////////////
            $contributor_participation=$participate_obj->getParticipateDetails($partId);
            $parameters['correcteddate']=date("d/m/Y H:i",strtotime($contributor_participation[0]['updated_at']));
            $parameters['comments']=$partParams['Moderator_comment'];
            $emailCorrector = $automail_obj->getMailComments(NULL,$partParams['crtmailId'],$parameters);
            $this->_view->closed = "yes";
            $this->_view->artId = $partParams['articleid'];
            echo $onlycorrectorcontent = utf8_encode(stripslashes($emailCorrector));exit;
        }
    }
    function checklastcorrectorAction()
    {
        $crtpart_obj = new Ep_Participation_CorrectorParticipation();
        $article_obj = new EP_Delivery_Article();
        $crtparts=$this->_request->getParams();
        $list = $crtpart_obj->getCrtPartsCountInArticle($crtparts['artid']);
        $crtlist = $article_obj->getArticledetails($crtparts['artid']);
        if($crtlist[0]["corrector_privatelist"] != NULL)
        {
            $crtlist = explode(",", $crtlist[0]["corrector_privatelist"]);
            $countcrtlist = count($crtlist);
            if((integer)$countcrtlist == (int)$list[0]["partscountinart"])
            {
                /* $data = array("correction_closed_status"=>"closed");////////updating
                 $query = "id= '".$crtparts['artid']."'";
                 $article_obj->updateArticle($data,$query);*/
                echo "yes";
            }
            else
                echo "no";
        }
        else
            echo "no";

    }
    /* *function to publish article back to FO**/
    public function publishcrtarticlefoAction()
    {
        $prevurl = getenv("HTTP_REFERER");
        $delivery=new Ep_Delivery_Delivery();
        $article_obj=new EP_Delivery_Article();
        $autoEmails = new Ep_Message_AutoEmails();
        $crtparticipate_obj = new Ep_Participation_CorrectorParticipation();
        $profile_params=$this->_request->getParams();
        $artId = $profile_params['art_id'];
        $partdetails =  $crtparticipate_obj->getCrtParticipantsDetailsCycle0($artId);
        ////////////updating article time to zero as article should go back FO again  ///////
        ////udate status participation table for status///////
        $data = array("status"=>"bid_refused", "accept_refuse_at"=>date("Y-m-d H:i:s", time()),  "selection_type"=>"bo");////////updating
        $query =  "article_id= '".$artId."' AND status IN ('bid_corrector', 'bid_temp') AND cycle='0'";
        $crtparticipate_obj->updateCrtParticipation($data,$query);
        ////udate status participation table for status///////
        $data = array("status"=>"closed", "accept_refuse_at"=>date("Y-m-d H:i:s", time()),  "selection_type"=>"bo");////////updating
        $query =  "article_id= '".$artId."' AND status IN ('bid', 'disapproved', 'time_out') AND cycle='0'";
        $crtparticipate_obj->updateCrtParticipation($data,$query);
        $cycleZero = $crtparticipate_obj->findAnyCycleZero($artId);
        if($cycleZero == "NO")
        {
            $this->CorrectorParticipationExpire($artId);
        }
        else
        {
            ////////////updating article time to zero as article should go back FO again  ///////
            $artbacktoFO = $crtparticipate_obj->getCrtArticlesBackToFo($artId);
            if($artbacktoFO == "NO")
            {
                $this->correctorRepublish($artId);///updating cycle and to show in FO////
            }
        }
        if($profile_params['sendmail'] == 'yes'){
            $this->sendMailToRepublishedCorrectors($artId,$profile_params);
        }
        //////this refusal mail is sent to participants when republished and close the article///////

        if($profile_params['sendrefusalmail'] == 'yes'){
            $partsUserids = $crtparticipate_obj->getActiveParicipants($artId);
            if($partsUserids != 'NO')
            {
                $email=$autoEmails->getAutoEmail(27);//
                $Object=$email[0]['Object'];
                $receiverId = $partsUserids[0]['corrector_id'];
                $Message =  $profile_params['refusalmailcontent'];
                $autoEmails->sendMailEpMailBox($receiverId,$Object,$Message);
            }
        }
        /////////////article history////////////////
        $partscount = $crtparticipate_obj->getNoOfCrtParticipants($artId);
        $actparams['participation_count'] = $partscount[0]['partsCount'];

        $actparams['artId'] = $artId;
        $actparams['stage'] = "selection Profile or stages";
        if($partdetails != 'NO')
        {
            if($partdetails[0]['status'] == 'bid' || $partdetails[0]['status'] == 'disapproved')  {
                $actparams['correctorId'] = $partdetails[0]['corrector_id'];
                $actparams['action'] = "article not sent and republished";
                $this->articleHistory(19,$actparams);
            }
            else{
                $actparams['action'] = "republished";
                $this->articleHistory(16,$actparams);
            }
        }
        else{
            $actparams['action'] = "republished";
            $this->articleHistory(16,$actparams);
        }
        /////////////end of article history////////////////
        $this->_redirect($prevurl);
    }
    /* *function to bulk publish article back to FO**/
    public function bulkpublishcrtarticlefoAction()
    {
        $prevurl = getenv("HTTP_REFERER");
        $delivery=new Ep_Delivery_Delivery();
        $article_obj=new EP_Delivery_Article();
        $autoEmails = new Ep_Message_AutoEmails();
        $crtparticipate_obj = new Ep_Participation_CorrectorParticipation();
        $profile_params=$this->_request->getParams();
        $aoId = $profile_params['aoId'];
        $artlist = $profile_params['artlist'];
        $artlist = explode(",",$artlist);
        for($i=0; $i<count($artlist); $i++)
        {
            $artId = $artlist[$i];
            $partdetails =  $crtparticipate_obj->getCrtParticipantsDetailsCycle0($artId);
            ////////////updating article time to zero as article should go back FO again  ///////
            ////udate status participation table for status///////
            $data = array("status"=>"bid_refused", "accept_refuse_at"=>date("Y-m-d H:i:s", time()),  "selection_type"=>"bo");////////updating
            $query =  "article_id= '".$artId."' AND status IN ('bid_corrector', 'bid_temp') AND cycle='0'";
            $crtparticipate_obj->updateCrtParticipation($data,$query);
            ////udate status participation table for status///////
            $data = array("status"=>"closed", "accept_refuse_at"=>date("Y-m-d H:i:s", time()),  "selection_type"=>"bo");////////updating
            $query =  "article_id= '".$artId."' AND status IN ('bid', 'disapproved', 'time_out') AND cycle='0'";
            $crtparticipate_obj->updateCrtParticipation($data,$query);
            $artdeldetails = $delivery->getArtDeliveryDetails($artId);
            $repubcount = $artdeldetails[0]['correction_republish_count']+1;
            if($artdeldetails[0]['republish_by_at'] == NULL)
                $repubbyat = $this->adminLogin->userId."|".date('Y-m-d H:i:s');
            else
                $repubbyat = $artdeldetails[0]['republish_by_at'].",".$this->adminLogin->userId."|".date('Y-m-d H:i:s');
            ////updating the article every time when republished///////
            $data = array("correction_republish_count"=>$repubcount,"republish_by_at"=>$repubbyat);////////updating
            $query = "id = '".$artId."'";
            $article_obj->updateArticle($data,$query);
            $cycleZero = $crtparticipate_obj->findAnyCycleZero($artId);
            if($cycleZero == "NO")
            {
                $this->CorrectorParticipationExpire($artId);
            }
            else
            {
                ////////////updating article time to zero as article should go back FO again  ///////
                $artbacktoFO = $crtparticipate_obj->getCrtArticlesBackToFo($artId);
                if($artbacktoFO == "NO")
                {
                    $this->correctorRepublish($artId);///updating cycle and to show in FO////
                }
            }

            //////this refusal mail is sent to participants when republished and close the article///////

            if($profile_params['sendrefusalmail'] == 'yes'){
                $partsUserids = $crtparticipate_obj->getActiveParicipants($artId);
                if($partsUserids != 'NO')
                {
                    $email=$autoEmails->getAutoEmail(27);//
                    $Object=$email[0]['Object'];
                    $receiverId = $partsUserids[0]['corrector_id'];
                    $Message =  $profile_params['refusalmailcontent'];
                    $autoEmails->sendMailEpMailBox($receiverId,$Object,$Message);
                }
            }
            /////////////article history////////////////
            $partscount = $crtparticipate_obj->getNoOfCrtParticipants($artId);
            $actparams['participation_count'] = $partscount[0]['partsCount'];

            $actparams['artId'] = $artId;
            $actparams['stage'] = "selection Profile or stages";
            if($partdetails != 'NO')
            {
                if($partdetails[0]['status'] == 'bid' || $partdetails[0]['status'] == 'disapproved')  {
                    $actparams['correctorId'] = $partdetails[0]['corrector_id'];
                    $actparams['action'] = "article not sent and republished";
                    $this->articleHistory(19,$actparams);
                }
                else{
                    $actparams['action'] = "republished";
                    $this->articleHistory(16,$actparams);
                }
            }
            else{
                $actparams['action'] = "republished";
                $this->articleHistory(16,$actparams);
            }
        }
        if($profile_params['sendmail'] == 'yes'){
            $this->sendBulkRepublishMailToCorrectors($aoId,$profile_params);
        }
        /////////////end of article history////////////////
        $this->_redirect($prevurl);
    }
    /////////mails send in bulk republish development////////////
    function sendBulkRepublishMailToCorrectors($aoId, $profile_params)
    {
        $delivery_obj = new Ep_Delivery_Delivery();
        $article_obj = new EP_Delivery_Article();
        $user_obj = new Ep_User_User();
        $autoEmails = new Ep_Message_AutoEmails();
        $participate_obj = new Ep_Participation_CorrectorParticipation();
        $part_obj = new EP_Participation_Participation();
        $artlist = explode(",",$profile_params['artlist']);
        for($i=0; $i<count($artlist); $i++)
        {
            $getpartcrts1 = $participate_obj->getCrtParticipationsUserIdsnotcycle0($artlist[$i]);
            if($getpartcrts1 != 'NO')
                $getpartcrts[$i] = $getpartcrts1[0]['corrector_id'];
            $getpartwrts1 = $part_obj->getNotRefusedParticipationsUserIds($artlist[$i]);
            if($getpartcrts1 != 'NO')
                $getpartwrts[$i] = $getpartwrts1[0]['user_id'];
        }

        $getpartcrts = array_unique($getpartcrts);  $getpartwrts = array_unique($getpartwrts);
       // print_r($getpartcrts); print_r($getpartwrts);  print_r(array_merge($getpartcrts,$getpartwrts));

        $nomailsendlist = array_merge($getpartcrts,$getpartwrts);
        /*  Sending mail to client when publish **/
        $delartdetails = $delivery_obj->getArticlesOfDel($aoId);
        $aoDetails = $delivery_obj->getArtDelDetails($aoId);
        $autoEmails=new Ep_Message_AutoEmails();

        if($aoDetails[0]['corrector_privatelist']!= NULL)
        {
            $correctors=array_unique(explode(",",$aoDetails[0]['corrector_privatelist']));
            if(is_array($correctors) && count($correctors)>0)
            {
                foreach($correctors as $corrector)
                {
                    if(!in_array($corrector,$nomailsendlist)) ///sending to only non participants
                    $autoEmails->sendMailEpMailBox($corrector,$profile_params['republishsubject'],$profile_params['republishmail']);
                }
            }
        }
        elseif($aoDetails[0]['corrector_privatelist']== NULL)
        {
            $delviews = $delivery_obj->getDeliveryDetails($aoId);
            $profiles = explode(",", $delviews[0]['corrector_list']);
            $profiles = implode(",", $profiles);
            // $contributors=$delivery_obj->getContributorsAO($profiles);
            $correctorswriters=$delivery_obj->getCorrectorsWritersAO($profiles);
            if(is_array($correctorswriters) && count($correctorswriters)>0)
            {
                foreach($correctorswriters as $correctorswriter)
                {
                    if(!in_array($correctorswriter['identifier'],$nomailsendlist)) ///sending to only non participants
                    {
                        //$autoEmails->messageToEPMail($correctorswriter['identifier'],$mailId,$parameters);///
                        $autoEmails->sendMailEpMailBox($correctorswriter['identifier'],$profile_params['republishsubject'],$profile_params['republishmail']);
                        $arrayuser[] = $correctorswriter['identifier'];
                    }
                }
            }
        }
    }
    public function sendPlagEpMailBox($partArtProcDetails, $plagged, $plag_percent)
    {
        $automail=new Ep_Message_AutoEmails();
        $user=$automail->getArticleCreatedUserId($partArtProcDetails['article_id']);
        $UserDetails=$automail->getUserType($user[0]['created_user_id']);
        //echo '<pre>'; print_r($user);exit;
        
        if($plagged)
        {
            $text_mail="<p>Dear project manager,<br><br>
 
The article <b>" . $user[0]['title'] . "</b> is currently being in  plagiarism on BO Edit Place because it was detected <b>" . $plag_percent . " %</b> Content was plagiarized.

<a href=\"http://admin-test.edit-place.co.uk/proofread/stage0-correction?submenuId=ML3-SL11&articleId=".$partArtProcDetails['article_id']."&participateId=".$partArtProcDetails['partId']."\">Click here</a> to check the plagiarism manually.<br><br>


Regards,<br><br>


Edit-place team"
            ;
            $subject= "BO Edit Place - Article plagiarized";
        }
        else 
        {
            $text_mail="<p>Dear project manager,<br><br>
 
The article <b>" . $user[0]['title'] . "</b> is currently being blocked plagiarism on the Edit-place and can not be processed automatically.<br> 

<a href=\"http://admin-test.edit-place.co.uk/proofread/stage0-correction?submenuId=ML3-SL11&articleId=".$partArtProcDetails['article_id']."&participateId=".$partArtProcDetails['partId']."\">Click here</a> to check the plagiarism manually.<br><br>


Regards,<br><br>


Edit-place team"
            ;
            $subject= "BO Edit Place - Article blocked plagiarism stage";
        }
        
        $subject=utf8_decode($subject);
        
        $mail = new Zend_Mail();
        $mail->addHeader('Reply-To',$this->config['mail_from']);
        $mail->setBodyHtml($text_mail)
            ->setFrom($this->config['mail_from'])
            ->addTo($UserDetails[0]['email'])
            ->addCc('arunravuri@edit-place.com')
            ->setSubject($subject);
        $mail->send();
    }
    public function updateCronLock($cron_name, $status)
    {
        $cron_obj = new Ep_Delivery_CronLock();
        $data_leave = array("locked"=>$status, "created_at"=>date('Y-m-d H:i:s'));
        $query_leave = "cron_name= '".$cron_name."'";
        $cron_obj->updateCronLock($data_leave,$query_leave);
    }
	public function checkparticipationAction()
	{
		$part_obj = new EP_Participation_Participation();
		$corrPart=$part_obj->checkParticipation($_REQUEST['artid'],$_REQUEST['contrib_id']);
		
		if($corrPart=='NO')
			echo 'no';
		else
			echo 'yes';
			
	}
	
	public function refuseparticipationAction()
	{
		$part_obj = new EP_Participation_Participation();
		$data = array("status"=>"bid_refused", "accept_refuse_at"=>date("Y-m-d H:i:s", time()),  "selection_type"=>"bo");////////updating
        $query =  "article_id= '".$_REQUEST['artid']."' AND user_id='".$_REQUEST['contrib_id']."' AND status IN ('bid_premium','bid_nonpremium') AND cycle=0";
        $part_obj->updateParticipation($data,$query);
	}
    /* *** added on 28.01.2016 *** */
    //function to reset the devilered realted fields only if it a 'descriptif_produit' product type//
    public function resetDeliveredDetail($artId){
        $article_obj = new EP_Delivery_Article();
        $data = array("delivered" => "", "delivered_updated_at" =>  NULL , "delivered_updated_by" =>  NULL , "delivered_updated_from" =>  NULL );
        $query = "id ='" . $artId . "'";
        $article_obj->updateArticle($data, $query);
    }
    // END OF function to reset the devilered realted fields only if it a 'descriptif_produit' product type//
}