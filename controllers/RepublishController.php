<?php
/*
 * IndexController - The default controller class
 *
 * @author
 * @version
 */
require_once 'Zend/Controller/Action.php';
class RepublishController extends Ep_Controller_Action {
    private $text_admin;
    public function init() {
        parent::init();
        $this->_view->lang = $this->_lang;
        $this->adminLogin  = Zend_Registry::get('adminLogin');
        $this->_view->userId = $this->adminLogin->userId;
        $this->sid         = session_id();
        ////if session expires/////
        if($this->adminLogin->loginName == '' && $_SERVER['HTTP_X_REQUESTED_WITH']=='XMLHttpRequest') {
           echo "session expired...please <a href='http://admin-test.edit-place.co.uk/index'>click here</a> to login"; exit;
        }
    }
    /////////when u click on "to be closed" link it will closed the  profiles on that article/////////////////
    public function closeartprofileAction()
    {
        $article=new EP_Delivery_Article();
        $participate_obj = new EP_Participation_Participation();
        $profilelist_params=$this->_request->getParams();
        $artId=$profilelist_params["art_id"] ;
        $Message = $profilelist_params["comment"];
        $participants = $profilelist_params["participants"];
        $beforetime = time()-300;
        if($participants == 'no') {
            $data1 = array("bo_closed_status"=>'closed', "participation_expires"=>$beforetime);
            $query = "id= '".$artId."'";
            $article->updateArticle($data1,$query);
            /////////////article history////////////////
            $partscount = $participate_obj->getNoOfParticipants($artId);
            $actparams['participation_count'] = $partscount[0]['partsCount'];
            $actparams['artId'] = $artId;
            $actparams['stage'] = "selection profile and close the article no participations";
            $this->articleHistory(41,$actparams);
            /////////////end of article history////////////////
        }
        else
        {
            $data1 = array("bo_closed_status"=>'closed', "participation_expires"=>$beforetime);
            $query = "id= '".$artId."'";
            $article->updateArticle($data1,$query);
            $useraccepted = $participate_obj->getAcceptedParticipant($artId);
            if($useraccepted != 'NO')
            {
                //////changing the status to refuse of participants ///////
                $data = array("status"=>"closed", "accept_refuse_at"=>date("Y-m-d H:i:s", time()), "selection_type"=>"bo");////////updating
                $query =  "id= '".$useraccepted[0]['id']."' ";
                $participate_obj->updateParticipation($data,$query);
                // sending the refused mails to contributor
                $automail=new Ep_Message_AutoEmails();
                $email=$automail->getAutoEmail(91);
                $Object=$email[0]['Object'];
                $receiverId = $useraccepted[0]['user_id'];
                $automail->sendMailEpMailBox($receiverId,$Object,$Message);
                /////////////article history////////////////
                $partscount = $participate_obj->getNoOfParticipants($artId);
                $actparams['contributorId'] = $receiverId;
                $actparams['artId'] = $artId;
                $actparams['stage'] = "selection profile-close new participants";
                $this->articleHistory(43,$actparams);
                /////////////end of article history////////////////
            }
            else
            {
                $partsUserids = $participate_obj->getNewParticipants($artId);
                ////udate status participation table for status///////
                $data = array("status"=>"bid_refused", "accept_refuse_at"=>date("Y-m-d H:i:s", time()), "selection_type"=>"bo");////////updating
                $query =  "article_id= '".$artId."' AND status IN ('bid_premium', 'bid_temp') AND cycle='0'";
                $participate_obj->updateParticipation($data,$query);
                //////this refusal mail is sent to participants when republished and close the article///////
                if($partsUserids != 'NO')
                {
                    for($i=0; $i<count($partsUserids); $i++)
                    {
                        // sending the refused mails to contributor
                        $automail=new Ep_Message_AutoEmails();
                        $email=$automail->getAutoEmail(27);
                        $Object=$email[0]['Object'];
                        $receiverId = $partsUserids[$i]['user_id'];
                        $automail->sendMailEpMailBox($receiverId,$Object,$Message);
                    }
                }
                /////////////article history////////////////
                $actparams['participation_count'] = count($partsUserids);
                $actparams['artId'] = $artId;
                $actparams['stage'] = "selection profile-close selected participants";
                $this->articleHistory(42,$actparams);
                /////////////end of article history////////////////
            }
        }

    }
    public function getclosemailpopupAction()
    {
        $prevurl = getenv("HTTP_REFERER");
        $participate_obj = new EP_Participation_Participation();
        $crtparticipate_obj = new Ep_Participation_CorrectorParticipation();
        $autoEmails=new Ep_Message_AutoEmails();
        $user_obj = new Ep_User_User();
        $delivery_obj = new Ep_Delivery_Delivery();
        $contrib_params=$this->_request->getParams();
        $usercount  = $contrib_params['usercount'];
        $artId = $contrib_params['artid'];
        $useraccepted = $participate_obj->getAcceptedParticipant($artId);

        if($useraccepted != 'NO')
        {
            $userdetails  = $autoEmails->getUserDetails($useraccepted[0]['user_id']);
            $mailid = 100;
            $popuphead = $userdetails[0]['username']." has been selected on this article. If you close this article, here is the email that will be sent:" ;
        }
        else
        {
            $mailid = 27;
            $popuphead = $text = "Do you really want to close this article? There was ".$usercount." participations, here is the email that will be sent:";
        }

        $paricipationdetails=$delivery_obj->getArtDeliveryDetails($artId);

        $parameters['article_title']=$paricipationdetails[0]['articleName'];
        if($paricipationdetails[0]['deli_anonymous']=='1')
            $parameters['client_name']='inconnu';
        else
        {
            $clientDetails=$autoEmails->getUserDetails($paricipationdetails[0]['user_id']);
            if($clientDetails[0]['username']!=NULL)
                $parameters['client_name']= $clientDetails[0]['username'];
            else
            {
                $email = explode("@",$clientDetails[0]['email']);
                $parameters['client_name']= $email[0];
            }
        }
        $contribDetails=$autoEmails->getUserDetails($paricipationdetails[0]['user_id']);

        if($contribDetails[0]['username']!=NULL)
            $parameters['contributor_name']= $contribDetails[0]['username'];
        else
        {
            $email = explode("@",$contribDetails[0]['email']);
            $parameters['contributor_name']= $email[0];
        }
        //$email=$autoEmails->getAutoEmail($mailid);
        $email = $autoEmails->getMailComments(NULL,$mailid,$parameters);
        echo  $emailComments = utf8_encode(stripslashes(html_entity_decode($email)))."*".$popuphead; exit;

    }
    ////////////display pop up with detail of multiple contributors who made biding when the article title is clicked///////////////////
    public function republishpopupAction()
    {
        $delivery_obj=new Ep_Delivery_Delivery();
        $article_obj = new EP_Delivery_Article();
		$articleObj=new EP_Ongoing_Article();
        $participate_obj=new EP_Participation_Participation();
        $automail=new Ep_Message_AutoEmails();
        $contrib_obj = new EP_User_Contributor();
        $user_obj = new Ep_User_User();
        $republishParams=$this->_request->getParams();
        $artId=$republishParams['artId'];
        if($republishParams['stage'] != '')
            $this->_view->stage = $republishParams['stage'];
        $artdeldetails = $delivery_obj->getArtDeliveryDetails($artId);
        if($republishParams['save'] == 'save')
        {     // print_r($republishParams);   exit;
            if($republishParams['parttime_option'] == 'min' )
                $parttime=$republishParams['parttime'];
            elseif($republishParams['parttime_option'] == 'hour')
                $parttime=60*$republishParams['parttime'];
            elseif($republishParams['parttime_option'] == 'day')
                $parttime=60*24*$republishParams['parttime'];

            $subopttime = $republishParams['subopttime'];
            if($subopttime == 'min')
            {
                $jc0time = $republishParams['jc0time'];
                $jctime = $republishParams['jctime'];
                $sctime = $republishParams['sctime'];
            }
            elseif($subopttime == 'hour')
            {
                $jc0time = $republishParams['jc0time']*60;
                $jctime = $republishParams['jctime']*60;
                $sctime = $republishParams['sctime']*60;
            }
            elseif($subopttime == 'day')
            {
                $jc0time = $republishParams['jc0time']*60*24;
                $jctime = $republishParams['jctime']*60*24;
                $sctime = $republishParams['sctime']*60*24;
            }
            $suboptresub = $republishParams['suboptresub'];
            if($suboptresub == 'min')
            {
                $jc0resub = $republishParams['jc0resub'];
                $jcresub = $republishParams['jcresub'];
                $scresub = $republishParams['scresub'];
            }
            elseif($suboptresub == 'hour')
            {
                $jc0resub = $republishParams['jc0resub']*60;
                $jcresub = $republishParams['jcresub']*60;
                $scresub = $republishParams['scresub']*60;
            }
            elseif($suboptresub == 'day')
            {
                $jc0resub = $republishParams['jc0retime']*60*24;
                $jcresub = $republishParams['jcresub']*60*24;
                $scresub = $republishParams['scresub']*60*24;
            }
            $viewto = explode(",",$republishParams['view_to']);
            for($i=0; $i<count($viewto); $i++)
            {
                if($viewto[$i] == "senior")
                    $viewto[$i] = "sc";
                if($viewto[$i] == "junior")
                    $viewto[$i] = "jc";
                if($viewto[$i] == "sub-junior")
                    $viewto[$i] = "jc0";
            }
            $view_to =   implode(",",$viewto);
             $min_price=$republishParams['price_min'];
             $max_price=$republishParams['price_max'];
             $publish_langs = implode(",",$republishParams['pubselectedlang']);
            if($republishParams['aotype'] == "private")
            {
                if($republishParams['sendmailtoonlysc'] == 'true')  ///if only mail to sc checkbox is checked///
                {
                    $selectedcontribs = explode(",",$republishParams['selectedcontribs']);
                    $participatedlist = $participate_obj->participatedContributors($artId);   //get list of participants who are aleady participated in articel//
                    if($participatedlist != 'NO')
                    {
                        for($i=0; $i<count($participatedlist); $i++)
                        {
                            $userprofile = $user_obj->getProfileType($participatedlist[$i]);
                            if(trim($userprofile[0]['profile_type']) == "senior")
                                $participatedsclist[$i] = $participatedlist[$i];
                        }
                    }
                    $allsccontribs = $user_obj->seniorContributors('senior');
                    for($i=0; $i<count($participatedlist); $i++)
                    {
                        $key = array_search($participatedsclist[$i],$allsccontribs);
                        if($key!==false){
                            unset($allsccontribs[$key]);
                        }
                    }
                    $allsccontribs=array_filter($allsccontribs);
                    $mergedarr = array_merge($allsccontribs,$selectedcontribs);
                    $totalcontribs = array_unique($mergedarr);
                    $totalcontribs = implode(",",$totalcontribs);
                }
                else
                    $totalcontribs = $republishParams['selectedcontribs'];
            }
            else
                $totalcontribs = NULL;
            $fbcomments = $republishParams['fbcomments'];
			
			//Add article history when price range is updated
			$ArticleDetails=$articleObj->getEditArticleDetails($artId);
				if($ArticleDetails[0]['price_min']!=$min_price || $ArticleDetails[0]['price_max']!=$max_price)
				{
					$actionId=64;
					$actparams['artId']=$artId;
					$actparams['stage']='ongoing';
					$actparams['action']='pricerange_updated';
					$actparams['old_article_writing_price_range']=$ArticleDetails[0]['price_min'].'-'.$ArticleDetails[0]['price_max'];
					$actparams['new_article_writing_price_range']=$min_price.'-'.$max_price;
					$actparams['currency']=$ArticleDetails[0]['currency'];
					$this->articleHistory($actionId, $actparams);
				}
            ///udate status_bo in delivery table for delete as trash///////
            $data = array("participation_time"=>$parttime, "submit_option"=>$subopttime, "subjunior_time"=>$jc0time, "junior_time"=>$jctime, "price_min"=>$min_price, "price_max"=>$max_price,"publish_language"=>$publish_langs,
                "senior_time"=>$sctime, "resubmit_option"=>$suboptresub, "jc0_resubmission"=>$jc0resub, "jc_resubmission"=>$jcresub, "sc_resubmission"=>$scresub, "contribs_list"=>$totalcontribs);////////updating
            $query = "id= '".$artId."'";
            $article_obj->updateArticle($data,$query);
            ///udate delivery table also///////
            $data1 = array("view_to"=>$view_to, "fbcomment"=>$fbcomments);////////updating
            $query1 = "id= '".$artdeldetails[0]['id']."'";
            $delivery_obj->updateDelivery($data1,$query1);
            exit;
        }
        ///////working on view to option ///////////
        ///count of all contributo based on type///
        $participatedsccount = 0;  $participatedjccount = 0;  $participatedjc0count = 0;
        $participatedlist = $participate_obj->participatedContributors($artId);   //get list of participants who are aleady participated in articel//
        if($participatedlist != 'NO')
        {
            for($i=0; $i<count($participatedlist); $i++)
            {
                $userprofile = $user_obj->getProfileType($participatedlist[$i]);

                if(trim($userprofile[0]['profile_type']) == "senior")
                    $participatedsccount++;
                if(trim($userprofile[0]['profile_type']) == "junior")
                    $participatedjccount++;
                if(trim($userprofile[0]['profile_type']) == "sub-junior")
                    $participatedjc0count++;
            }
        }
        //total counts of individual contribitors type////
        $sclist = $contrib_obj->getContributorcount('senior');
        $this->_view->sc_count= $sclist-$participatedsccount;
        $sclist= $sclist-$participatedsccount;
        $jclist = $contrib_obj->getContributorcount('junior');
        $this->_view->jc_count = $jclist-$participatedjccount;
        $jclist = $jclist-$participatedjccount;
        $jc0list = $contrib_obj->getContributorcount('sub-junior');
        $this->_view->jc0_count=  $jc0list-$participatedjc0count;
        $jc0list =  $jc0list-$participatedjc0count;



        $profiles = explode(",", $artdeldetails[0]['view_to']);
        $profiles = implode(",", $profiles);
        $profs=explode(",",$profiles);
        $proflist=array();
        if($artdeldetails[0]['AOtype'] == 'public')
        {
            for($p=0;$p<count($profs);$p++)
            {
                if($profs[$p]=="jc")
                {
                    $proflist[]="junior";
                    $this->_view->jc="yes";
                    $jcmailcount = $jclist;
                }
                elseif($profs[$p]=="sc")
                {
                    $proflist[]="senior";
                    $this->_view->sc="yes";
                    $scmailcount = $sclist;
                }
                elseif($profs[$p]=="jc0")
                {
                    $proflist[]="sub-junior";
                    $this->_view->jc0="yes";
                    $jc0mailcount = $jc0list;
                }
            }
            $pubprofiles=implode("','",$proflist);
            $aoprofiles=$delivery_obj->getViewToOfAO($pubprofiles);
            $aoprofiles = $aoprofiles[0]['AoContributors'];
            $this->_view->createmailtobesentcount = $scmailcount+$jcmailcount+$jc0mailcount;
        }
        else{
            for($p=0;$p<count($profs);$p++)
            {
                if($profs[$p]=="jc")
                {
                    $this->_view->jc="yes";
                    $jcmailcount = $jclist;
                }
                if($profs[$p]=="sc")
                {
                    $this->_view->sc="yes";
                    $scmailcount = $sclist;
                }
                if($profs[$p]=="jc0")
                {
                    $this->_view->jc0="yes";
                    $jc0mailcount = $jc0list;
                }
               // $this->_view->createmailtobesentcount = $scmailcount+$jcmailcount+$jc0mailcount;
            }
            $priprofiles = explode(",",$artdeldetails[0]['contribslist']);

            if($participatedlist != 'NO')
                $participatedlistcount = count($participatedlist);
            else
                $participatedlistcount = 0;
            $this->_view->createmailtobesentcount = count($priprofiles)-$participatedlistcount;
            $aoprofiles=count($priprofiles);

            //Private contributors
            $ep_lang_array = $this->_arrayDb->loadArrayv2("EP_LANGUAGES", $this->_lang);
            $categories_array  = $this->_arrayDb->loadArrayv2("EP_ARTICLE_CATEGORY", $this->_lang);
            $contrib_lang_array=$ep_lang_array;
            if($artdeldetails[0]['AOtype'] == 'public')
                $this->_view->contrib_langarray =array("fr");
            else
                $this->_view->contrib_langarray =array("all");
            $this->_view->Contrib_langs = $contrib_lang_array;
            $contrib_cat_array=array_merge(array("all"=>"All"),$categories_array);
            $this->_view->Contrib_cats = $contrib_cat_array;
        }
        $partinart = $participate_obj->getPartsCountInArticle($artId);
        if($partinart[0]['partcountinart'] == $aoprofiles)
            $this->_view->nopartsforrepublish  = "yes";  ////there are no user to participate in article if article is republished///
        else
            $this->_view->nopartsforrepublish = "no";
        $this->_view->aotype = $artdeldetails[0]['AOtype'];
        $this->_view->toberefusedcontribs = $participate_obj->getToBeRefusedParticipants($artId);
        ///to display the refusal mail if avtive participants are there.
        $activparts = $participate_obj->getParticipantsForRefuse($artId);
        if($activparts == 'NO')
            $this->_view->refusemail = "no";
        else
            $this->_view->refusemail = "yes";

        if($artdeldetails[0]['AOtype'] == 'public')
        {
            if($artdeldetails[0]['view_to'] == 'sc')
                $this->_view->missiontitle = "Mission SC";
            else
                $this->_view->missiontitle = "Mission public";
        }
        else
        {
            $this->_view->missiontitle = "Mission private";
        }
        if($artdeldetails[0]['submit_option'] == 'min' )
            $convertval = 1;
        elseif($artdeldetails[0]['submit_option'] == 'hour')
            $convertval = 60;
        elseif($artdeldetails[0]['submit_option'] == 'day')
            $convertval = 60*24;

        if($artdeldetails[0]['resubmit_option'] == 'min')
            $reconvertval = 1;
        elseif($artdeldetails[0]['resubmit_option'] == 'hour')
            $reconvertval = 60;
        elseif($artdeldetails[0]['resubmit_option'] == 'day')
            $reconvertval = 60*24;

        $artdeldetails[0]['subjunior_time'] = $artdeldetails[0]['subjunior_time']/$convertval;
        $artdeldetails[0]['junior_time'] = $artdeldetails[0]['junior_time']/$convertval;
        $artdeldetails[0]['senior_time'] = $artdeldetails[0]['senior_time']/$convertval;

        $artdeldetails[0]['jc0_resubmission'] = $artdeldetails[0]['jc0_resubmission']/$reconvertval;
        $artdeldetails[0]['jc_resubmission'] = $artdeldetails[0]['jc_resubmission']/$reconvertval;
        $artdeldetails[0]['sc_resubmission'] = $artdeldetails[0]['sc_resubmission']/$reconvertval;

        $this->_view->artdeldetails =  $artdeldetails;

        ////set the contributors count in mail by default////
        $selectedcontribs = explode(",",$artdeldetails[0]['contribslist']);
        $contriblist = $participate_obj->participatedContributors($artId);
        for($i=0; $i<count($selectedcontribs); $i++)
        {
            $key = array_search($contriblist[$i],$selectedcontribs);
            if($key!==false){
                unset($selectedcontribs[$key]);
            }
        }
        $selectedcontribs = count(array_values($selectedcontribs));
        $parameters['article_title']=$artdeldetails[0]['articleName'];
        $clientDetails=$automail->getUserDetails($artdeldetails[0]['user_id']);
        if($clientDetails[0]['username']!=NULL)
            $parameters['client_name']= $clientDetails[0]['username'];
        else
        {
            $email = explode("@",$clientDetails[0]['email']);
            $parameters['client_name']= $email[0];
        }
        $expires=time()+(60*$artdeldetails[0]['participation_time']);
        $parameters['submitdate_bo']=date('d/m/Y H:i', $expires);
        $parameters['datetime_republish']=date('d/m/Y', $expires)." &agrave; ".date('H:i', $expires);
		$parameters['sccontribnum'] = 0;
        $parameters['contribnum'] = $selectedcontribs;
        $parameters['articlenum'] = $artdeldetails[0]['total_article'];
        $parameters['aoname_link'] = "http://ep-test.edit-place.co.uk/contrib/aosearch";
        $parameters['AO_title']= $artdeldetails[0]['deliveryTitle'];
		$submitdate_bo="<b>".$parameters['submitdate_bo']."</b>";
        //sending the mails when bulk republishe  done////////////
        if($artdeldetails[0]['AOtype'] == 'public')
        {
            /// the refuse mail of the participated user on article
            $refuseemail = $automail->getMailComments($user_id=NULL,92,$parameters);
            $this->_view->refusemessage = utf8_encode(stripslashes($refuseemail));
            if(in_array('jc',$profs) || in_array('jc0',$profs)) {
                $scmailId = 15;    //////sending the mail only leftover sc contributor////
                $email=$automail->getAutoEmail($scmailId);
                //$this->_view->scobject=$email[0]['Object'];
				$this->_view->scobject=$artdeldetails[0]['mailsubject'];
            }
            else{
                $scmailId = 99;  ///when other than senior selected////
                $email=$automail->getAutoEmail($scmailId);
                //$this->_view->scobject=$email[0]['Object'];
				$this->_view->scobject=$artdeldetails[0]['mailsubject'];
            }

            $email = $automail->getMailComments($user_id=NULL,$scmailId,$parameters);
            //$this->_view->scmessage = utf8_encode(stripslashes($email));
			$message=$artdeldetails[0]['mailcontent'];
			$message=preg_replace('/(<span\sid="submitdate">)([^<]|<.+>.*<\/.+>)+(<\/span>)/i', $submitdate_bo, $message);
            $this->_view->scmessage = utf8_encode(stripslashes($message));
        }
        else
        {
            /// the refuse mail of the participated user on article
            $refuseemail = $automail->getMailComments($user_id=NULL,96,$parameters);
            $this->_view->refusemessage = utf8_encode(stripslashes($refuseemail));
            $scmailId = 97;    //////sending the mail only leftover sc contributor////
            $email=$automail->getAutoEmail($scmailId);
            $this->_view->scobject=$email[0]['Object'];
            $this->_view->scmessage = ("<b style='color: red; font: 14px;'>All seniors are already in the private list, no specific email will be sent to seniors</b>.");
            $allmailId = 98;   //////sending the mails to all non sc contributor who have not paricipated as new article arrived////
            $email=$automail->getAutoEmail($allmailId);
            //$this->_view->allobject=$email[0]['Object'];
			$this->_view->allobject=$artdeldetails[0]['mailsubject'];
            $email = $automail->getMailComments($user_id=NULL,$allmailId,$parameters);
            //$this->_view->allmessage = utf8_encode(stripslashes($email));
			//$this->_view->allmessage = utf8_encode(stripslashes($artdeldetails[0]['mailcontent']));
			$message=$artdeldetails[0]['mailcontent'];
			$message=preg_replace('/(<span\sid="submitdate">)([^<]|<.+>.*<\/.+>)+(<\/span>)/i', $submitdate_bo, $message);
            $this->_view->allmessage = utf8_encode(stripslashes($message));
        }
        $this->resetDeliveredDetail($artId);//call the function to reset the delivered details of article
        $this->_view->render("processao_republishaopopup");
    }
	
	//// 
    public function changecontribcountAction()
    {
        $contrib_obj = new EP_User_Contributor();
        $participate_obj = new EP_Participation_Participation();
        $user_obj = new Ep_User_User();
        $contribParams = $this->_request->getParams();
        $artId = $contribParams['artId'];
        $langs = $contribParams['langs'];
        ///count of all contributo based on type///
        $participatedsccount = 0;  $participatedjccount = 0;  $participatedjc0count = 0;
        $participatedlist = $participate_obj->participatedContributors($artId);   //get list of participants who are aleady participated in articel//
        if($participatedlist != 'NO')
        {
            for($i=0; $i<count($participatedlist); $i++)
            {
                $userprofile = $user_obj->getProfileType($participatedlist[$i]);

                if(trim($userprofile[0]['profile_type']) == "senior")
                    $participatedsccount++;
                if(trim($userprofile[0]['profile_type']) == "junior")
                    $participatedjccount++;
                if(trim($userprofile[0]['profile_type']) == "sub-junior")
                    $participatedjc0count++;
            }
        }
        //total counts of individual contribitors type////
        $sclist = $contrib_obj->getWriterCountOnLang('senior', $langs);
        if($sclist!=0)
			$resarray[0]= $sclist-$participatedsccount;
		else
			$resarray[0]= $sclist;
			
        $jclist = $contrib_obj->getWriterCountOnLang('junior', $langs);
		if($jclist!=0)
			$resarray[1] = $jclist-$participatedjccount;
		else
			$resarray[1]= $jclist;
        
        $jc0list = $contrib_obj->getWriterCountOnLang('sub-junior', $langs);
		if($jc0list!=0)
			$resarray[2] =  $jc0list-$participatedjc0count;
		else
			$resarray[2]= $jc0list;
        
		//print_r($resarray);
        echo  json_encode($resarray); exit;

    }
	
    public function loaduserslistAction()
    {
        $delivery_obj = new Ep_Delivery_Delivery();
        $participate_obj = new EP_Participation_Participation();
        $republishParams=$this->_request->getParams();
        $artId=$_REQUEST['artId'];
        $artdeldetails = $delivery_obj->getArtDeliveryDetails($artId);
        $selectedcontribs = explode(",",$artdeldetails[0]['contribslist']);
        ////removeing the arleady participated contributors list from the private contrib list////
        $contriblist = $participate_obj->participatedContributors($artId);
        for($i=0; $i<count($selectedcontribs); $i++)
        {
            $key = array_search($contriblist[$i],$selectedcontribs);
            if($key!==false){
                unset($selectedcontribs[$key]);
            }
        }
        $selectedcontribs = array_values($selectedcontribs);
        $contriblistall = $delivery_obj->getContribsByTypeLangCat($_REQUEST['type'],$_REQUEST['category'],$_REQUEST['language']);
        $contriblistall1=array();
        if($contriblist != 'NO')  {
            for ($i=0;$i<count($contriblistall);$i++)
            {
                if(!in_array($contriblistall[$i]['identifier'], $contriblist))  {
                    $contriblistall1[$i]=$contriblistall[$i];
                    $name=$contriblistall1[$i]['email'];
                    $nameArr=array($contriblistall1[$i]['first_name'],$contriblistall1[$i]['last_name']);
                    $nameArr=array_filter($nameArr);
                    if(count($nameArr)>0)
                        $name.=" (".implode(", ",$nameArr).")";
                    $contriblistall1[$i]['name']=strtoupper($name);
                }
            }    // print_r($selectedcontribs); exit;
        }
        else{
            for ($i=0;$i<count($contriblistall);$i++)
            {
                $contriblistall1[$i]=$contriblistall[$i];
                $name=$contriblistall1[$i]['email'];
                $nameArr=array($contriblistall1[$i]['first_name'],$contriblistall1[$i]['last_name']);
                $nameArr=array_filter($nameArr);
                if(count($nameArr)>0)
                    $name.=" (".implode(", ",$nameArr).")";
                $contriblistall1[$i]['name']=strtoupper($name);
            }    // print_r($selectedcontribs); exit;
        }
        $contriblistall1=array_values($contriblistall1);
        //$contriblist=':';
        $contriblist='<select multiple="multiple" name="favcontribcheck[]" class="span9" id="favcontribcheck" onchange="fngetmailcontent();">';
        foreach ($contriblistall1 as $contrib)
        {
            if(in_array($contrib['identifier'],$selectedcontribs))
                $contriblist.='<option value="'.$contrib['identifier'].'" selected>'.utf8_encode($contrib['name']).'</option>';
            else
                $contriblist.='<option value="'.$contrib['identifier'].'" >'.utf8_encode($contrib['name']).'</option>';
        }
        $contriblist.='</select>
			<div id="favcontrib_err"></div>';

        echo $contriblist;

    }
    public function loadbulkuserslistAction()
    {
        $delivery_obj = new Ep_Delivery_Delivery();
        $participate_obj = new EP_Participation_Participation();
        $article_obj = new EP_Delivery_Article();
        $republishParams=$this->_request->getParams();
        $aoId=$_REQUEST['aoId'];
        $artlist = explode(",",$_REQUEST['artlist']);
        $artdeldetails = $delivery_obj->getArtDelDetails($aoId);
        //$selectedcontribs = explode(",",$artdeldetails[0]['contribslist']);
        $selectedcontribs = array();
        for($i=0; $i<count($artlist); $i++)
        {
            $artcontribs = $article_obj->getArticleDetails($artlist[$i]);
              $singleartcontribs[$i] = explode(",",$artcontribs[0]["contribs_list"]);
              for($j=0; $j<count($singleartcontribs[$i]); $j++)
              {
                  $selectedcontribs[] = $singleartcontribs[$i][$j];
              }
        }
        $selectedcontribs = array_filter($selectedcontribs);
        $selectedcontribs = array_unique($selectedcontribs);

        ////removeing the arleady participated contributors list from the private contrib list////
        for($i=0; $i<count($artlist); $i++)
        {
            $art_id = $artlist[$i];
            $getpartusers = $participate_obj->getParticipationsUserIds($art_id);
            if($getpartusers != 'NO')
            {
                foreach($getpartusers as $notsendmail)
                {
                   $participatedlist[$notsendmail['user_id']]+=1;
                }
            }
        }
        //print_r($getpartusers);
        //print_r($participatedlist);
        if($participatedlist != '') {

            while ($allarray = current($participatedlist)) {
                if ($allarray == count($artlist)) {
                    $participatedcontriblist[] = key($participatedlist);
                }
                next($participatedlist);
            }
        }
        /*if($selectedcontribs != 'null')
        {
            for($i=0; $i<count($selectedcontribs); $i++)
            {
                $key = array_search($participatedcontriblist[$i],$selectedcontribs);
                if($key!==false){
                    unset($selectedcontribs[$key]);
                }
            }
        }
        //////////////////////////////////
        $selectedcontribs = array_values($selectedcontribs);*/
        //print_r($participatedcontriblist);
        $contriblistall = $delivery_obj->getContribsByTypeLangCat($_REQUEST['type'],$_REQUEST['category'],$_REQUEST['language']);
        $contriblistall1=array();
        if($participatedcontriblist != '')  {
            for ($i=0;$i<count($contriblistall);$i++)
            {
                if(!in_array($contriblistall[$i]['identifier'], $participatedcontriblist))  {
                    $contriblistall1[$i]=$contriblistall[$i];
                    $name=$contriblistall1[$i]['email'];
                    $nameArr=array($contriblistall1[$i]['first_name'],$contriblistall1[$i]['last_name']);
                    $nameArr=array_filter($nameArr);
                    if(count($nameArr)>0)
                        $name.=" (".implode(", ",$nameArr).")";
                    $contriblistall1[$i]['name']=strtoupper($name);
                }
            }    // print_r($selectedcontribs); exit;
        }
        else{
            for ($i=0;$i<count($contriblistall);$i++)
            {
                $contriblistall1[$i]=$contriblistall[$i];
                $name=$contriblistall1[$i]['email'];
                $nameArr=array($contriblistall1[$i]['first_name'],$contriblistall1[$i]['last_name']);
                $nameArr=array_filter($nameArr);
                if(count($nameArr)>0)
                    $name.=" (".implode(", ",$nameArr).")";
                $contriblistall1[$i]['name']=strtoupper($name);
            }    // print_r($selectedcontribs); exit;
        }
        $contriblistall1=array_values($contriblistall1);
        //$contriblist=':';
        $contriblist='<select multiple="multiple" name="favcontribcheck[]" class="span9" id="favcontribcheck" onchange="fngetbulkmailcontent();">';
        foreach ($contriblistall1 as $contrib)
        {
            if(in_array($contrib['identifier'],$selectedcontribs))
                $contriblist.='<option value="'.$contrib['identifier'].'" selected>'.utf8_encode($contrib['name']).'</option>';
            else
                $contriblist.='<option value="'.$contrib['identifier'].'" >'.utf8_encode($contrib['name']).'</option>';
        }
        $contriblist.='</select>
			<div id="favcontrib_err"></div>';

        echo $contriblist;

    }
    public function loadpublicaouserslistAction()
    {
        $delivery_obj = new Ep_Delivery_Delivery();
        $participate_obj = new EP_Participation_Participation();
        $republishParams=$this->_request->getParams();

        $contriblistall = $delivery_obj->getContribsByTypeLangCat($_REQUEST['type'],$_REQUEST['category'],$_REQUEST['language']);
        $contriblistall1=array();
        for ($i=0;$i<count($contriblistall);$i++)
        {
            $contriblistall1[]=$contriblistall[$i]['identifier'];
        }
        $participatedcontriblist = $participate_obj->participatedContributors($_REQUEST['artId']);
        for($i=0; $i<count($contriblistall1); $i++)
        {
            $key = array_search($participatedcontriblist[$i],$contriblistall1);
            if($key!==false){
                unset($contriblistall1[$key]);
            }
        }

        echo $contriblistall1."*".count($contriblistall1);

    }

    public function changeaotypeAction()
    {
        $delivery_obj=new Ep_Delivery_Delivery();
        $republishParams=$this->_request->getParams();
        $aoId=$republishParams['aoId'];
       // $view_to = $republishParams['view_to'];
        $aotype = $republishParams['aotype'];
        ///udate delivery table also///////
        $data = array("AOtype"=>$aotype);////////updating
        $query = "id= '".$aoId."'";
        $delivery_obj->updateDelivery($data,$query);
    }
    ////////////display pop up with detail of multiple contributors who made biding when the article title is clicked///////////////////
    public function bulkrepublishpopupAction()
    {
        $delivery_obj=new Ep_Delivery_Delivery();
        $article_obj = new EP_Delivery_Article();
        $participate_obj=new EP_Participation_Participation();
        $contrib_obj = new EP_User_Contributor();
        $user_obj = new Ep_User_User();
        $automail=new Ep_Message_AutoEmails();
        $republishParams=$this->_request->getParams();
        $aoId=$republishParams['aoId'];
        $this->_view->artlist = $republishParams['artlist'];
        $this->_view->aoId = $aoId;
        $artlist=explode(",",$republishParams['artlist']);
        $artdeldetails = $delivery_obj->getArtDelDetails($aoId);   //print_r($artdeldetails); exit;
        if($republishParams['save'] == 'save')
        {
           // $artlist = explode(',',$artlist);
            $parttime = $republishParams['parttime'];
            if($republishParams['parttime_option'] == 'min' )
                $parttime=$republishParams['parttime'];
            elseif($republishParams['parttime_option'] == 'hour')
                $parttime=60*$republishParams['parttime'];
            elseif($republishParams['parttime_option'] == 'day')
                $parttime=60*24*$republishParams['parttime'];

            $min_price=$republishParams['price_min'];
            $max_price=$republishParams['price_max'];
            $viewto = explode(",",$republishParams['view_to']);
            for($i=0; $i<count($viewto); $i++)
            {
                if($viewto[$i] == "senior")
                    $viewto[$i] = "sc";
                if($viewto[$i] == "junior")
                    $viewto[$i] = "jc";
                if($viewto[$i] == "sub-junior")
                    $viewto[$i] = "jc0";
            }
            $view_to =   implode(",",$viewto);
            $fbcomments = $republishParams['fbcomments'];
           // $aotype = $republishParams['aotype'];
            for($i=0; $i<count($artlist); $i++)
            {
                if($republishParams['aotype'] == "private")
                {
                    if($republishParams['sendmailtoonlysc'] == 'true')  ///if only mail to sc checkbox is checked///
                    {
                        $selectedcontribs = explode(",",$republishParams['selectedcontribs']);
                        $participatedlist = $participate_obj->participatedContributors($artlist[$i]);   //get list of participants who are aleady participated in articel//

                        if($participatedlist != 'NO')
                        {
                            for($j=0; $j<count($participatedlist); $j++)
                            {
                                $userprofile = $user_obj->getProfileType($participatedlist[$j]);
                                if(trim($userprofile[0]['profile_type']) == "senior")
                                    $participatedsclist[$j] = $participatedlist[$j];
                            }
                        }
                        $allsccontribs = $user_obj->seniorContributors('senior');
                        for($k=0; $k<count($participatedlist); $k++)
                        {
                            $key = array_search($participatedsclist[$k],$allsccontribs);
                            if($key!==false){
                                unset($allsccontribs[$key]);
                            }
                        }
                        $allsccontribs=array_filter($allsccontribs);
                        $mergedarr = array_merge($allsccontribs,$selectedcontribs);
                        $totalcontribs = array_unique($mergedarr);
                        $totalcontribs = implode(",",$totalcontribs);   //echo   $totalcontribs."eeeee".$artlist[$i];  exit;
                    }
                    else
                        $totalcontribs = $republishParams['selectedcontribs'];
                }
                else{
                    $totalcontribs = NULL;
                }
                ///udate status_bo in delivery table for delete as trash///////
                $data = array("participation_time"=>$parttime, "price_min"=>$min_price, "price_max"=>$max_price, "contribs_list"=>$totalcontribs);////////updating
                $query = "id= '".$artlist[$i]."'";
                $article_obj->updateArticle($data,$query);
            }
            ///udate delivery table also///////
            $data1 = array("participation_time"=>$parttime, "price_min"=>$min_price, "price_max"=>$max_price, "view_to"=>$view_to, "fbcomment"=>$fbcomments);////////updating
            $query1 = "id= '".$aoId."'";
            $delivery_obj->updateDelivery($data1,$query1);
                exit;
        }
        ///////working on view to option ///////////
        ///count of all contributo based on type///
        $participatedsccount = 0;  $participatedjccount = 0;  $participatedjc0count = 0;
        $participatedlist = $participate_obj->participatedContributorsAo($aoId);   //get list of participants who are aleady participated in articel//
        /*if($participatedlist != 'NO')
        {
            for($i=0; $i<count($participatedlist); $i++)
            {
                $userprofile = $user_obj->getProfileType($participatedlist[$i]);

                if(trim($userprofile[0]['profile_type']) == "senior")
                    $participatedsccount++;
                if(trim($userprofile[0]['profile_type']) == "junior")
                    $participatedjccount++;
                if(trim($userprofile[0]['profile_type']) == "sub-junior")
                    $participatedjc0count++;
            }
        }*/
        $nomailsendsclist=array();  $nomailsendjclist=array();  $nomailsendjc0list=array();
        for($i=0; $i<count($artlist); $i++)
        {
            $art_id = $artlist[$i];
            $getpartusers = $participate_obj->getParticipationsUserIds($art_id);
            if($getpartusers != 'NO')
            {
                foreach($getpartusers as $notsendmail)
                {
                    $nomailsendlist[] = $notsendmail['user_id'];
                    if($notsendmail['profile_type'] == 'senior')
                        $nomailsendsclist1[$notsendmail['user_id']]+= 1;
                    if($notsendmail['profile_type'] == 'junior')
                        $nomailsendjclist1[$notsendmail['user_id']]+= 1;
                    if($notsendmail['profile_type'] == 'sub-junior')
                        $nomailsendjc0list1[$notsendmail['user_id']]+= 1;
                }
            }
        }
        if($nomailsendsclist1 != '') {
            while ($scarray = current($nomailsendsclist1)) {
                if ($scarray == count($artlist)) {
                    $nomailsendsclist[] = key($nomailsendsclist1);
                }
                next($nomailsendsclist1);
            }
        }
        if($nomailsendjclist1 != '') {
            while ($jcarray = current($nomailsendjclist1)) {
                if ($jcarray == count($artlist)) {
                    $nomailsendjclist[] = key($nomailsendjclist1);
                }
                next($nomailsendjclist1);
            }
        }
        if($nomailsendjc0list1 != '') {
            while ($jc0array = current($nomailsendjc0list1)) {
                if ($jc0array == count($artlist)) {
                    $nomailsendjc0list[] = key($nomailsendjc0list1);
                }
                next($nomailsendjc0list1);
            }
        }
        //total counts of individual contribitors type////
        $sclist = $contrib_obj->getContributorcount('senior');
        $this->_view->sc_count= $sclist-count($nomailsendsclist);
        $sclist= $sclist-count($nomailsendsclist);
        $jclist = $contrib_obj->getContributorcount('junior');
        $this->_view->jc_count = $jclist-count($nomailsendjclist);
        $jclist = $jclist-count($nomailsendjclist);
        $jc0list = $contrib_obj->getContributorcount('sub-junior');
        $this->_view->jc0_count=  $jc0list-count($nomailsendjc0list);
        $jc0list =  $jc0list-count($nomailsendjc0list);

        $profiles = explode(",", $artdeldetails[0]['view_to']);
        $profiles = implode(",", $profiles);
        $profs=explode(",",$profiles);
        $proflist=array();
        if($artdeldetails[0]['AOtype'] == 'public')
        {
            for($p=0;$p<count($profs);$p++)
            {
                if($profs[$p]=="jc")
                {
                    $proflist[]="junior";
                    $this->_view->jc="yes";
                    $jcmailcount = $jclist;
                }
                elseif($profs[$p]=="sc")
                {
                    $proflist[]="senior";
                    $this->_view->sc="yes";
                    $scmailcount = $sclist;
                }
                elseif($profs[$p]=="jc0")
                {
                    $proflist[]="sub-junior";
                    $this->_view->jc0="yes";
                    $jc0mailcount = $jc0list;
                }
            }
            $pubprofiles=implode("','",$proflist);
            $aoprofiles=$delivery_obj->getViewToOfAO($pubprofiles);
            $aoprofiles = $aoprofiles[0]['AoContributors'];
            $this->_view->createmailtobesentcount = $scmailcount+$jcmailcount+$jc0mailcount;
        }
        else{
            for($p=0;$p<count($profs);$p++)
            {
                if($profs[$p]=="jc")
                {
                    $this->_view->jc="yes";
                    $jcmailcount = $jclist;
                }
                if($profs[$p]=="sc")
                {
                    $this->_view->sc="yes";
                    $scmailcount = $sclist;
                }
                if($profs[$p]=="jc0")
                {
                    $this->_view->jc0="yes";
                    $jc0mailcount = $jc0list;
                }
                // $this->_view->createmailtobesentcount = $scmailcount+$jcmailcount+$jc0mailcount;
            }
            $contrib_list = array();
            for($i=0; $i<count($artlist); $i++)
            {
                $artcontribs = $article_obj->getArticleDetails($artlist[$i]);
                  $singleartcontribs[$i] = explode(",",$artcontribs[0]["contribs_list"]);

                  for($j=0; $j<count($singleartcontribs[$i]); $j++)
                  {
                      $contrib_list[] = $singleartcontribs[$i][$j];
                  }
            }
            $contrib_list = array_filter($contrib_list);
            $selectedcontribscount = count(array_unique($contrib_list));
            //$priprofiles = explode(",",$artdeldetails[0]['contribslist']);
            $priprofiles = $scmailcount+$jcmailcount+$jc0mailcount;
            if($participatedlist != 'NO')
                $participatedlistcount = count($nomailsendsclist)+count($nomailsendjclist)+count($nomailsendjc0list);
            else
                $participatedlistcount = 0;

            $mergearray = array_merge($nomailsendsclist,$nomailsendjclist,$nomailsendjc0list);
            for($k=0; $k<count($selectedcontribscount); $k++)
            {
                $key = array_search($mergearray[$k],$contrib_list);
                if($key!==false){
                    unset($contrib_list[$key]);
                }
            }
            $contrib_listcount=array_filter($contrib_list);
            $contrib_listcount = array_unique($contrib_listcount);
            $this->_view->createmailtobesentcount = count($contrib_listcount);

            $aoprofiles=count($priprofiles);

            //Private contributors
            $ep_lang_array = $this->_arrayDb->loadArrayv2("EP_LANGUAGES", $this->_lang);
            $categories_array  = $this->_arrayDb->loadArrayv2("EP_ARTICLE_CATEGORY", $this->_lang);
            $contrib_lang_array=$ep_lang_array;
            if($artdeldetails[0]['AOtype'] == 'public')
                $this->_view->contrib_langarray =array("fr");
            else
                $this->_view->contrib_langarray =array("all");
            $this->_view->Contrib_langs = $contrib_lang_array;
            $contrib_cat_array=array_merge(array("all"=>"All"),$categories_array);
            $this->_view->Contrib_cats = $contrib_cat_array;
        }
        $this->_view->aotype = $artdeldetails[0]['AOtype'];
        $this->_view->language = $artdeldetails[0]['language'];
        ///to display the refusal mail if avtive participants are there.
        $activparts = $participate_obj->getParticipantsForRefuseAo($aoId);
        if($activparts == 'NO')
        {
            $this->_view->refusemail = "no";
            $this->_view->toberefusedcontribs = 0;
        }
        else
        {
            $this->_view->refusemail = "yes";
            $this->_view->toberefusedcontribs = count($activparts);
        }
        if($artdeldetails[0]['AOtype'] == 'public')
        {
            if($artdeldetails[0]['view_to'] == 'sc')
                $this->_view->missiontitle = "Mission SC";
            else
                $this->_view->missiontitle = "Mission public";
        }
        else
        {
            $this->_view->missiontitle = "Mission private";
        }
        $this->_view->aotype = $artdeldetails[0]['AOtype'];
        $artdeldetails[0]['participation_time'] = $artdeldetails[0]['participation_time'];
        $artdeldetails[0]['price_min'] = $artdeldetails[0]['price_min'];
        $artdeldetails[0]['price_max'] = $artdeldetails[0]['price_max'];

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
        $expires=time()+(60*$artdeldetails[0]['participation_time']);
        $parameters['submitdate_bo']=date('d/m/Y H:i', $expires);
        $parameters['datetime_republish']=date('d/m/Y', $expires)." &agrave; ".date('H:i', $expires);
        $parameters['sccontribnum'] = 0;
        $parameters['contribnum'] = $selectedcontribscount;
        $parameters['articlenum'] = $artdeldetails[0]['total_article'];
        $parameters['aoname_link'] = "http://ep-test.edit-place.co.uk/contrib/aosearch";
        $parameters['AO_title']= $artdeldetails[0]['deliveryTitle'];

        //sending the mails when bulk republishe  done////////////
        if($artdeldetails[0]['AOtype'] == 'public')
        {
            $scmailId = 95;  ///when other than senior selected////
            $email=$automail->getAutoEmail($scmailId);
            $this->_view->scobject=$email[0]['Object'];
            $email = $automail->getMailComments($user_id=NULL,$scmailId,$parameters);
            $this->_view->scmessage = utf8_encode(stripslashes($email));
        }
        else
        {
            $scmailId = 93;    //////sending the mail only leftover sc contributor////
            $email=$automail->getAutoEmail($scmailId);
            $this->_view->scobject=$email[0]['Object'];
            $this->_view->scmessage = ("<b style='color: red; font: 14px;'>All seniors are already in the private list, no specific email will be sent to seniors</b>.");
            $allmailId = 94;   //////sending the mails to all non sc contributor who have not paricipated as new article arrived////
            $email=$automail->getAutoEmail($allmailId);
            $this->_view->allobject=$email[0]['Object'];
            $email = $automail->getMailComments($user_id=NULL,$allmailId,$parameters);
            $this->_view->allmessage = utf8_encode(stripslashes($email));
        }
         /// the refuse mail of the participated user on article
        $refuseemail = $automail->getMailComments($user_id=NULL,92,$parameters);
        $this->_view->refusemessage = utf8_encode(stripslashes($refuseemail));

        $this->_view->render("republish_bulkrepublishaopopup");
    }
    public function bulkAlreadyParticipatedContributors($artlist)
    {
        $participate_obj = new EP_Participation_Participation();
        $nomailsendsclist1=0;  $nomailsendjclist1=0;  $nomailsendjc0list1=0;
        for($i=0; $i<count($artlist); $i++)
        {
            $art_id = $artlist[$i];
            $getpartusers = $participate_obj->getParticipationsUserIds($art_id);
            if($getpartusers != 'NO')
            {
                foreach($getpartusers as $notsendmail)
                {
                    $nomailsendlist[] = $notsendmail['user_id'];
                    if($notsendmail['profile_type'] == 'senior')
                        $nomailsendsclist1[$notsendmail['user_id']]+= 1;
                    if($notsendmail['profile_type'] == 'junior')
                        $nomailsendjclist1[$notsendmail['user_id']]+= 1;
                    if($notsendmail['profile_type'] == 'sub-junior')
                        $nomailsendjc0list1[$notsendmail['user_id']]+= 1;
                }
            }
        }
        if($nomailsendsclist1 != '') {
            while ($scarray = current($nomailsendsclist1)) {
                if ($scarray == count($artlist)) {
                    $nomailsendsclist[] = key($nomailsendsclist1);
                }
                next($nomailsendsclist1);
            }
        }
        if($nomailsendjclist1 != '') {
            while ($jcarray = current($nomailsendjclist1)) {
                if ($jcarray == count($artlist)) {
                    $nomailsendjclist[] = key($nomailsendjclist1);
                }
                next($nomailsendjclist1);
            }
        }
        if($nomailsendjc0list1 != '') {
            while ($jc0array = current($nomailsendjc0list1)) {
                if ($jc0array == count($artlist)) {
                    $nomailsendjc0list[] = key($nomailsendjc0list1);
                }
                next($nomailsendjc0list1);
            }
        }
        return  array($nomailsendsclist, $nomailsendjclist, $nomailsendjc0list);
    }
    /* *function to publish article back to FO**/
    public function bulkpublisharticlefoAction()
    {
        $prevurl = getenv("HTTP_REFERER");
        $delivery=new Ep_Delivery_Delivery();
        $article_obj=new EP_Delivery_Article();
        $autoEmails = new Ep_Message_AutoEmails();
        $participate_obj = new EP_Participation_Participation();
        $profile_params=$this->_request->getParams();    //print_r($profile_params); exit;
         ////$this->sendBulkRepublishMailToContribs($profile_params);  exit;
        $artdeldetails = $delivery->getArtDeliveryDetails($artId);
        $artlist = $profile_params['artlist'];
        $artlist = explode(",",$artlist);
        for($i=0; $i<count($artlist); $i++)
        {
            $artId = $artlist[$i];
            $partdetails =  $participate_obj->getParticipantsDetailsCycle0($artId);
            $partsUserids = $participate_obj->getParticipantsForRefuse($artId);   // print_r($partsUserids);  print_r($profile_params); exit;
            ////udate status participation table for status///////
            $data = array("status"=>"closed", "accept_refuse_at"=>date("Y-m-d H:i:s", time()),  "selection_type"=>"bo");////////updating
            $query =  "article_id= '".$artId."' AND status IN ('bid', 'disapproved', 'time_out','plag_exec','under_study') AND cycle='0'";
            $participate_obj->updateParticipation($data,$query);
            ////udate status participation table for status///////
            $data = array("status"=>"bid_refused", "accept_refuse_at"=>date("Y-m-d H:i:s", time()),  "selection_type"=>"bo");////////updating
            $query =  "article_id= '".$artId."' AND status IN ('bid_premium', 'bid_temp') AND cycle='0'";
            $participate_obj->updateParticipation($data,$query);
            $repubcount = $artdeldetails[0]['republish_count']+1;
            if($artdeldetails[0]['republish_by_at'] = NULL)
                $repubbyat = $this->adminLogin->userId."|".date('Y-m-d H:i:s');
            else
                $repubbyat = $artdeldetails[0]['republish_by_at'].",".$this->adminLogin->userId."|".date('Y-m-d H:i:s');
            ////updating the article every time when republished///////
            $data = array("republish_count"=>$repubcount,"republish_by_at"=>$repubbyat);////////updating
            $query = "id = '".$artId."'";
            $article_obj->updateArticle($data,$query);
            if($profile_params['sendtofo'] == 'no'){ // echo $profile_params['refusalmailcontent']; echo "hello"; exit;
                ////updating the article tabel article submit expire wiht zero///////
                $data = array("send_to_fo"=>"no","file_path"=>"");////////updating
                $query = "id = '".$artId."'";
                $article_obj->updateArticle($data,$query);
            }
            else
            {
                $cycleZero = $participate_obj->findAnyCycleZero($artId);
                if($cycleZero == "NO")
                {
                    ////update the artlcle table with partcipation time/////////
                    $this->WriterParticipationExpire($artId);
                }
                else
                {
                    ////////////updating article time to zero as article should go back FO again  ///////
                    $artbacktoFO = $participate_obj->getArticlesBackToFo($artId);
                    if($artbacktoFO == "NO")
                    {
                        $this->republish($artId);///updating cycle and to show in FO////
                    }
                }
               /* if($profile_params['sendmail'] == 'yes'){
                    $this->sendMailToContribs($artId);
                }*/

                $artdeldetails = $delivery->getArtDeliveryDetails($artId);
                //FB & TWT posting
                if($profile_params['fbpost'] == 'yes' && trim($artdeldetails[0]['fbcomment']) != '')
                {
                    require_once '/home/sites/site7/web/FO/postfb/facebook.php';
                   // require_once "/home/sites/site7/web/FO/tmhOAuth/tmhOAuth.php";

                    //FB details
                    $appId = $this->configval['fb_app_id'];
                    $secret = $this->configval['fb_secret'];
                    //$returnurl = 'http://localhost:8086/posttofb/';
                    $permissions = 'publish_stream,offline_access';

                    $fb = new Facebook(array("appId" => $appId, "secret" => $secret,'scope' => $permissions));

                    //TWT details
                   /* $tmhOAuth = new tmhOAuth(array(
                        'consumer_key' => $this->configval['twt_consumer_key'],
                        'consumer_secret' => $this->configval['twt_consumer_secret'],
                        'token' => $this->configval['twt_token'],
                        'secret' => $this->configval['twt_secret'],
                    ));*/

                    if($artdeldetails[0]['fbcomment']!="")
                    {
                        //FB posting
                        $message = array(
                            //'access_token'=>'CAACxflqXW94BAJ98HE6bGmtwoClekKGxSpYkqypF8LcQcJKWkPPZCUZAwkO0QkXYo67ceYrHjrn4ScrZA48KMyvBm2bHQIF3KVYCZB5SwR8nEjhwgEg2UZCDIpYHthaTuX9XKAv9w54j0PZA8jUGJUOgcb7x6pGi3AAxgt3MZAaK9zZC2ELMbs3Q',
                            'access_token'=>$this->configval['fb_access_token'],
                            'message'=>utf8_encode(stripslashes($artdeldetails[0]['fbcomment']))." http://goo.gl/CUioSK"
                        );

                        $url='/237274402982745/feed';
                        $result = $fb->api($url, 'POST', $message);

                        //TWT posting
                       /* $response = $tmhOAuth->request('POST', $tmhOAuth->url('1.1/statuses/update'), array(
                            'status' => utf8_encode(stripslashes($artdeldetails[0]['fbcomment'])
                            )));*/

                        $mail_text='<b>AO ID</b> : '.$artdeldetails[0]['id'].'<br><br>
													<b>Title</b> : '.$this->AO_creation->ao_step1['title'].'<br><br>
													<b>Comment</b> : '.$artdeldetails[0]['fbcomment'];
                        $mail = new Zend_Mail();
                        $mail->addHeader('Reply-To','support@edit-place.com');
                        $mail->setBodyHtml($mail_text)
                            ->setFrom('support@edit-place.com','Support Edit-place')
                            ->addTo('mailpearls@gmail.com')
                        //->addCc('kavithashree.r@gmail.com')
                            ->setSubject('FB & TWT posting Test Site');
                        //$mail->send();
                    }
                    // update fbpost status
                    $array['fbpost']='yes';
                    $array['postoftheday']='yes';
                    $where=" id='".$artdeldetails[0]['id']."'";
                    $delivery->updateDelivery($array,$where);
                }
                //////this refusal mail is sent to participants when republished and close the article///////
                if($profile_params['sendrefusalmail'] == 'yes')
                {
                    if($partsUserids != 'NO')
                    {
                        if($artdeldetails[0]['AOtype'] == 'private')
                            $email=$autoEmails->getAutoEmail(92);//
                        else
                            $email=$autoEmails->getAutoEmail(92);//
                        $Object=$email[0]['Object'];
                        for($j=0; $j<count($partsUserids); $j++)
                        {
                            $receiverIds[$j] = $partsUserids[$j]['user_id'];
                        }
                    }
                    $actparams['artId'] = $artId;
                    $actparams['stage'] = "selection profile";
                    $actparams['action'] = "closed";
                    $this->articleHistory(5,$actparams);
                    /////////////end of article history////////////////
                }
                /////////////article history////////////////
                $partscount = $participate_obj->getNoOfParticipants($artId);
                $actparams['participation_count'] = $partscount[0]['partsCount'];

                $actparams['artId'] = $artId;
                $actparams['stage'] = "selection Profile or stages";
                if($partdetails != 'NO')
                {
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
                else{
                    $actparams['action'] = "republished";
                    $this->articleHistory(4,$actparams);
                }
                /////////////end of article history////////////////
            }
            /// unlock the article///////////////
            $this->unlockonactionAction($artId);
        }

        if($profile_params['sendrefusalmail'] == 'yes')
        {
            $receiverId = array_unique($receiverIds);
            for($n=0; $n<count($receiverId); $n++)
            {
                 $Message =  $profile_params['refusalmailcontent'];
                $autoEmails->sendMailEpMailBox($receiverId[$n],$Object,$Message);
            }
        }
        if($profile_params['anouncebymail'] == 'true'){
            $this->sendBulkRepublishMailToContribs($profile_params);
        }
        $this->_redirect($prevurl);
    }
    /* *function to publish article back to FO**/
    public function publisharticlefoAction()
    {
        $prevurl = getenv("HTTP_REFERER");
        $delivery=new Ep_Delivery_Delivery();
        $article_obj=new EP_Delivery_Article();
        $autoEmails = new Ep_Message_AutoEmails();
        $participate_obj = new EP_Participation_Participation();
        $profile_params=$this->_request->getParams();
        $artId = $profile_params['artId'];
        $artdeldetails = $delivery->getArtDeliveryDetails($artId);
       // $this->sendRepublishMailToContribs($profile_params); echo "ehllo"; exit;
        $partdetails =  $participate_obj->getParticipantsDetailsCycle0($artId);
        $partsUserids = $participate_obj->getParticipantsForRefuse($artId);   // print_r($partsUserids);  print_r($profile_params); exit;
        ////udate status participation table for status///////
        $data = array("status"=>"closed", "accept_refuse_at"=>date("Y-m-d H:i:s", time()),  "selection_type"=>"bo");////////updating
        $query =  "article_id= '".$artId."' AND status IN ('bid', 'disapproved', 'time_out','plag_exec','under_study') AND cycle='0'";
        $participate_obj->updateParticipation($data,$query);
        ////udate status participation table for status///////
        $data = array("status"=>"bid_refused", "accept_refuse_at"=>date("Y-m-d H:i:s", time()),  "selection_type"=>"bo");////////updating
        $query =  "article_id= '".$artId."' AND status IN ('bid_premium', 'bid_temp') AND cycle='0'";
        $participate_obj->updateParticipation($data,$query);
        $repubcount = $artdeldetails[0]['republish_count']+1;
        if($artdeldetails[0]['republish_by_at'] == NULL)
            $repubbyat = $this->adminLogin->userId."|".date('Y-m-d H:i:s');
        else
            $repubbyat = $artdeldetails[0]['republish_by_at'].",".$this->adminLogin->userId."|".date('Y-m-d H:i:s');
        ////updating the article every time when republished///////
        $data = array("republish_count"=>$repubcount,"republish_by_at"=>$repubbyat);////////updating
        $query = "id = '".$artId."'";
        $article_obj->updateArticle($data,$query);
        if($profile_params['sendtofo'] == 'no'){ // echo $profile_params['refusalmailcontent']; echo "hello"; exit;
            ////updating the article tabel article submit expire wiht zero///////
            $data = array("send_to_fo"=>"no","file_path"=>"");////////updating
            $query = "id = '".$artId."'";
            $article_obj->updateArticle($data,$query);

        }
        else
        {
            $cycleZero = $participate_obj->findAnyCycleZero($artId);
            if($cycleZero == "NO")
            {
                ////update the artlcle table with partcipation time/////////
                $this->WriterParticipationExpire($artId);
            }
            else
            {
                ////////////updating article time to zero as article should go back FO again  ///////
                $artbacktoFO = $participate_obj->getArticlesBackToFo($artId);
                if($artbacktoFO == "NO")
                {
                    $this->republish($artId);///updating cycle and to show in FO////
                }
            }
           /* if($profile_params['sendmail'] == 'yes'){
                $this->sendMailToContribs($artId);
            }*/
            if($profile_params['anouncebymail'] == 'true'){
                $this->sendRepublishMailToContribs($profile_params);
            }
                // print_r($artdeldetails);  exit;
            //FB & TWT posting
            if($profile_params['fbpost'] == 'yes' && trim($artdeldetails[0]['fbcomment']) != '')
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
                // $Isposted=$delivery->checkfbpost($artdeldetails[0]['client_id']);
                //  if($Isposted!="yes")
                //  {
                if($artdeldetails[0]['fbcomment']!="")
                {
                    //FB posting
                    $message = array(
                        //'access_token'=>'CAACxflqXW94BAJ98HE6bGmtwoClekKGxSpYkqypF8LcQcJKWkPPZCUZAwkO0QkXYo67ceYrHjrn4ScrZA48KMyvBm2bHQIF3KVYCZB5SwR8nEjhwgEg2UZCDIpYHthaTuX9XKAv9w54j0PZA8jUGJUOgcb7x6pGi3AAxgt3MZAaK9zZC2ELMbs3Q',
                        'access_token'=>$this->configval['fb_access_token'],
                        'message'=>utf8_encode(stripslashes($artdeldetails[0]['fbcomment']))." http://goo.gl/CUioSK"
                    );

                    $url='/237274402982745/feed';
                    $result = $fb->api($url, 'POST', $message);

                    //TWT posting
                    $response = $tmhOAuth->request('POST', $tmhOAuth->url('1.1/statuses/update'), array(
                        'status' => utf8_encode(stripslashes($artdeldetails[0]['fbcomment'])
                        )));

                    $mail_text='<b>AO ID</b> : '.$artdeldetails[0]['id'].'<br><br>
													<b>Title</b> : '.$this->AO_creation->ao_step1['title'].'<br><br>
													<b>Comment</b> : '.$artdeldetails[0]['fbcomment'];
                    $mail = new Zend_Mail();
                    $mail->addHeader('Reply-To','support@edit-place.com');
                    $mail->setBodyHtml($mail_text)
                        ->setFrom('support@edit-place.com','Support Edit-place')
                        ->addTo('mailpearls@gmail.com')
                    //->addCc('kavithashree.r@gmail.com')
                        ->setSubject('FB & TWT posting Test Site');
                    //$mail->send();
                }
                // }
                // update fbpost status
                $array['fbpost']='yes';
                $array['postoftheday']='yes';
                $where=" id='".$artdeldetails[0]['id']."'";
                $delivery->updateDelivery($array,$where);
            }
            //////this refusal mail is sent to participants when republished and close the article///////
            /*if($profile_params['sendrefusalmail'] == 'yes'){
               // $partsUserids = $participate_obj->getActiveParicipants($artId);
                $partsUserids = $participate_obj->getActiveParicipants($artId);
                if($partsUserids != 'NO')
                {
                    $email=$autoEmails->getAutoEmail(27);//
                    $Object=$email[0]['Object'];
                    $receiverId = $partsUserids[0]['user_id'];
                    $Message =  $profile_params['refusalmailcontent'];
                    $autoEmails->sendMailEpMailBox($receiverId,$Object,$Message);
                }
            }*/
            /////////////article history////////////////
            $partscount = $participate_obj->getNoOfParticipants($artId);
            $actparams['participation_count'] = $partscount[0]['partsCount'];

            $actparams['artId'] = $artId;
            $actparams['stage'] = "selection Profile or stages";
            if($partdetails != 'NO')
            {
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
            else{
                $actparams['action'] = "republished";
                $this->articleHistory(4,$actparams);
            }
            /////////////end of article history////////////////
        }

        //////this refusal mail is sent to participants when republished and close the article///////
        if($profile_params['sendrefusalmail'] == 'yes')
        {
            if($partsUserids != 'NO')
            {
                if($artdeldetails[0]['AOtype'] == 'private')
                    $email=$autoEmails->getAutoEmail(96);//
                else
                    $email=$autoEmails->getAutoEmail(92);//
                $Object=$email[0]['Object'];
                for($i=0; $i<count($partsUserids); $i++)
                {
                    $receiverId = $partsUserids[$i]['user_id'];
                    $Message =  $profile_params['refusalmailcontent'];
                    $autoEmails->sendMailEpMailBox($receiverId,$Object,$Message);
                }
            }
            $actparams['artId'] = $artId;
            $actparams['stage'] = "selection profile";
            $actparams['action'] = "closed";
            $this->articleHistory(5,$actparams);
            /////////////end of article history////////////////
        }
        /// unlock the article///////////////
        $this->unlockonactionAction($artId);
        $this->_redirect($prevurl);
    }
    ////////mail content for publish pop up///////////
    public function publishmailcontent($ao_id, $publish)
    {
        ////update the artlcle table with partcipation time/////////
        $delivery_obj = new Ep_Delivery_Delivery();
        $article_obj = new EP_Delivery_Article();
        $automail = new Ep_Message_AutoEmails();
        $user_obj = new Ep_User_User();
        $participate_obj = new EP_Participation_Participation();

        $delartdetails = $delivery_obj->getArticlesOfDel($ao_id);
        $expires=time()+(60*$delartdetails[0]['participation_time']);
        $data = array("participation_expires"=>$expires);////////updating
        $artIds = explode("@",$delartdetails[0]['artIds']);
        for($i=0; $i<=count($artIds); $i++)
        {
            $query = "id= '".$artIds[$i]."'";
            $article_obj->updateArticle($data,$query);
        }
        /*Sending mail to client when publish **/
        $aoDetails=$delivery_obj->getPrAoDetails($ao_id);
        $autoEmails=new Ep_Message_AutoEmails();
        $parameters['aoname_link'] = "http://ep-test.edit-place.co.uk/contrib/aosearch";
        $parameters['AO_title']=$aoDetails[0]['title'];
        $parameters['AO_end_date']=$aoDetails[0]['delivery_date'];
        //$parameters['submitdate_bo']=$aoDetails[0]['submitdate_bo'];
        if($publish=='no')
            $parameters['submitdate_bo']=date('d/m/Y H:i', $expires);
        elseif($publish=='now')
            $parameters['submitdate_bo']=date('d/m/Y H:i', $expires);
        elseif($publish=='late')
        {
            $expires+=60*60*24;
            //$parameters['submitdate_bo']=date('d/m/Y H:i', $expires);
            $parameters['submitdate_bo']=strftime("%d/%m/%Y &agrave; %H:%M",$expires);
        }
        else
        {
           // $parameters['submitdate_bo']=date('d/m/Y H:i', $publish);
            //  echo strtotime($publish); echo date('d/m/Y H:i', $publish); exit;
            $expires+=strtotime($publish);
            $parameters['submitdate_bo']=date('d/m/Y H:i', $expires);
        }

        $parameters['noofarts']=$aoDetails[0]['noofarts'];
        if($aoDetails[0]['deli_anonymous']=='0')
            $parameters['article_link']="/contrib/aosearch?client_contact=".$aoDetails[0]['user_id'];
        else
            $parameters['article_link']="/contrib/aosearch?client_contact=anonymous";
        $parameters['aoname_link'] = "http://ep-test.edit-place.co.uk/contrib/aosearch";
        $parameters['clientartname_link'] = "/client/quotes?id=".$aoDetails[0]['articleid'];

        if($aoDetails[0]['AOtype']=='private')
        {
            $contributors=array_unique(explode(",",$aoDetails[0]['article_contribs']));
            if(is_array($contributors) && count($contributors)>0)
            {
                if(count($contributors)==1)
                {
                    if($aoDetails[0]['premium_option']=='0')
                        $automailid=19;//
                    else
                        $automailid=20;//
                }
                else
                {
                    if($aoDetails[0]['premium_option']=='0')
                        $automailid=17;//
                    else
                        $automailid=18;//
                }
                $emailobj=$automail->getAutoEmail($automailid);
                $Object=utf8_encode(stripslashes($emailobj[0]['Object']));
                $email = $automail->getMailComments($userid=null,$automailid,$parameters);
                $emailComments = utf8_encode(stripslashes($email));
                $emailComments = $emailComments.'*@#'.$Object;
            }
        }
        elseif($aoDetails[0]['AOtype']=='public')
        {
            if($deldetails[0]['created_by'] != 'BO')
            {
                $contributors=$user_obj->getSeniorContributors();
                if(is_array($contributors) && count($contributors)>0)
                {
                    $sclimit=$this->configval["sc_limit"];
                    foreach($contributors as $contributor)
                    {
                        $countofparts=$participate_obj->getCountOnStatus($contributor['identifier']);
                        if($sclimit > $countofparts[0]['partscount'])
                        {
                            if($aoDetails[0]['premium_option']=='0')
                                $automailid=14;//
                            else
                                $automailid=15;//
                            $emailobj=$automail->getAutoEmail($automailid);
                            $Object=utf8_encode(stripslashes($emailobj[0]['Object']));
                            $email = $automail->getMailComments($userid=null,$automailid,$parameters);
                            $emailComments = utf8_encode(stripslashes($email));
                            $emailComments = $emailComments.'*@#'.$Object;
                        }
                    }
                }
            }
        }
        if($publish=='no')
            return  $emailComments;
        else
            echo  $emailComments;
    }
    /////////mails send in bulk republish development////////////
    function sendBulkRepublishMailToContribs($profile_params)
    {
        $delivery_obj = new Ep_Delivery_Delivery();
        $article_obj = new EP_Delivery_Article();
        $user_obj = new Ep_User_User();
        $automail=new Ep_Message_AutoEmails();
        $participate_obj = new EP_Participation_Participation();
        $partcrt_obj = new Ep_Participation_CorrectorParticipation();
        $artlist = $profile_params['artlist'];
        $ao_id = $profile_params['aoId'];
        $ao_type = $profile_params['aoType'];
        $scobject = utf8_decode($profile_params['scobject']);
        $scmessage = $profile_params['scmessage'];
        $allobject = utf8_decode($profile_params['allobject']);
        $allmessage = $profile_params['allmessage'];
        $selectedcontribs =  $profile_params['selectedcontribs'];
        $sendmailtousertype = explode(",",$profile_params['sendmailtousertype']);
        if($profile_params['pubselectedlang']!='null')
            $pubselectedlang = explode(",",$profile_params['pubselectedlang']);
        $artlist = explode(",",$artlist);
		
		//get article detail
		$Article_details=$delivery_obj->getArtDeliveryDetails($artlist[0]);
		$testrequired=$Article_details[0]['testrequired'];
		$testmarks=$Article_details[0]['testmarks'];
        for($i=0; $i<count($artlist); $i++)
        {
            $art_id = $artlist[$i];
            $getpartusers = $participate_obj->getParticipationsUserIds($art_id);
            if($getpartusers != 'NO')
            {
                /*foreach($getpartusers as $notsendmail)
                {
                    $nomailsendlist[] = $notsendmail['user_id'];
                    if($notsendmail['profile_type'] == 'senior')
                        $nomailsendsclist[] = $notsendmail['user_id'];
                    if($notsendmail['profile_type'] == 'junior')
                        $nomailsendjclist[] = $notsendmail['user_id'];
                    if($notsendmail['profile_type'] == 'sub-junior')
                        $nomailsendjc0list[] = $notsendmail['user_id'];
                }*/
                foreach($getpartusers as $notsendmail)
                {
                    $nomailsendlist[] = $notsendmail['user_id'];
                    if($notsendmail['profile_type'] == 'senior')
                        $nomailsendsclist1[$notsendmail['user_id']]+= 1;
                    if($notsendmail['profile_type'] == 'junior')
                        $nomailsendjclist1[$notsendmail['user_id']]+= 1;
                    if($notsendmail['profile_type'] == 'sub-junior')
                        $nomailsendjc0list1[$notsendmail['user_id']]+= 1;
                }
            }
        }
        if($nomailsendsclist1 != '') {
            while ($scarray = current($nomailsendsclist1)) {
                if ($scarray == count($artlist)) {
                    $nomailsendsclist[] = key($nomailsendsclist1);
                }
                next($nomailsendsclist1);
            }
        }
        if($nomailsendjclist1 != '') {
            while ($jcarray = current($nomailsendjclist1)) {
                if ($jcarray == count($artlist)) {
                    $nomailsendjclist[] = key($nomailsendjclist1);
                }
                next($nomailsendjclist1);
            }
        }
        if($nomailsendjc0list1 != '') {
            while ($jc0array = current($nomailsendjc0list1)) {
                if ($jc0array == count($artlist)) {
                    $nomailsendjc0list[] = key($nomailsendjc0list1);
                }
                next($nomailsendjc0list1);
            }
        }
        if($ao_type == 'private')
        {
            $contributors=array_unique(explode(",",$selectedcontribs));
            /////sending the mail to only to sc contributors////////
            if($profile_params['sendmailtoonlysc'] == 'true')
            {
                $sccontributors = $user_obj->scContributorsNotSelected($selectedcontribs);  ///all sc's except selected contributors list from popup//
                if(is_array($sccontributors) && count($sccontributors)>0)
                {
                    foreach($sccontributors as $sccontributor)
                    {
                        if($nomailsendsclist != '')
                        {
                            if(!in_array($sccontributor['identifier'],$nomailsendsclist)) ///sending to only non participants
                             {
                                if($testrequired=="yes")
								{
									$contrib_details=$user_obj->getContributordetails($sccontributor['identifier']);
									if($contrib_details[0]['contributortest']=="yes" && ($testmarks==NULL ||  $contrib_details[0]['contributortestmarks']>=$testmarks))
										$automail->sendMailEpMailBox($sccontributor['identifier'],$scobject,$scmessage);
								}								
								else
									$automail->sendMailEpMailBox($sccontributor['identifier'],$scobject,$scmessage);
							}
                        }
                        else
                        {
                             if($testrequired=="yes")
							{
								$contrib_details=$user_obj->getContributordetails($sccontributor['identifier']);
								if($contrib_details[0]['contributortest']=="yes" && ($testmarks==NULL ||  $contrib_details[0]['contributortestmarks']>=$testmarks))
									$automail->sendMailEpMailBox($sccontributor['identifier'],$scobject,$scmessage);
							}								
							else
								$automail->sendMailEpMailBox($sccontributor['identifier'],$scobject,$scmessage);
						}
                    }
                }
            }
            /////sending the mail to only to all contributors////////
            if(is_array($contributors) && count($contributors)>0)
            {
                foreach($contributors as $contributor)
                {
                    if($nomailsendlist != '')
                    {
                        if(!in_array($contributor,$nomailsendlist)) ///sending to only non participants
                        {
                            if($testrequired=="yes")
							{
								$contrib_details=$user_obj->getContributordetails($contributor);
								if($contrib_details[0]['contributortest']=="yes" && ($testmarks==NULL ||  $contrib_details[0]['contributortestmarks']>=$testmarks))
									$automail->sendMailEpMailBox($contributor,$allobject,$allmessage);
							}								
							else
								$automail->sendMailEpMailBox($contributor,$allobject,$allmessage);
						}
                    }
                    else
                    {
                        if($testrequired=="yes")
						{
							$contrib_details=$user_obj->getContributordetails($contributor);
							if($contrib_details[0]['contributortest']=="yes" && ($testmarks==NULL ||  $contrib_details[0]['contributortestmarks']>=$testmarks))
								$automail->sendMailEpMailBox($contributor,$allobject,$allmessage);
						}								
						else
							$automail->sendMailEpMailBox($contributor,$allobject,$allmessage);
					}
                }
            }
        }
        if($ao_type == 'public')
        {
            /////sending the mail to only to sc contributors////////
            if(in_array('senior', $sendmailtousertype))
            {
                $sccontributors = $user_obj->contributorsByTypeLang('senior', $pubselectedlang);
                //print_r($nomailsendsclist);
                if(is_array($sccontributors) && count($sccontributors)>0)
                {
                    foreach($sccontributors as $sccontributor)
                    {
                        //echo  $sccontributor['identifier']."<br>";
                        if($nomailsendsclist != '')
                        {
                            if(!in_array($sccontributor['identifier'],$nomailsendsclist)) ///sending to only non participants
                             {
                                if($testrequired=="yes")
								{
									$contrib_details=$user_obj->getContributordetails($sccontributor['identifier']);
									if($contrib_details[0]['contributortest']=="yes" && ($testmarks==NULL ||  $contrib_details[0]['contributortestmarks']>=$testmarks))
										$automail->sendMailEpMailBox($sccontributor['identifier'],$scobject,$scmessage);
								}								
								else
									$automail->sendMailEpMailBox($sccontributor['identifier'],$scobject,$scmessage);
							}
                        }
                        else
                        {
                            if($testrequired=="yes")
							{
								$contrib_details=$user_obj->getContributordetails($sccontributor['identifier']);
								if($contrib_details[0]['contributortest']=="yes" && ($testmarks==NULL ||  $contrib_details[0]['contributortestmarks']>=$testmarks))
									$automail->sendMailEpMailBox($sccontributor['identifier'],$scobject,$scmessage);
							}								
							else
								$automail->sendMailEpMailBox($sccontributor['identifier'],$scobject,$scmessage);
						}
                    }
                }
            }
            //exit;
            /////sending the mail to only to jc contributors////////
           /* if(in_array('junior', $sendmailtousertype))
            {
                $jccontributors = $user_obj->contributorsByTypeLang('junior', $pubselectedlang);
                if(is_array($jccontributors) && count($jccontributors)>0)
                {
                    foreach($jccontributors as $jccontributor)
                    {
                        if($nomailsendjclist != '')
                        {
                            if(!in_array($jccontributor['identifier'],$nomailsendjclist)) ///sending to only non participants
                                $automail->sendMailEpMailBox($jccontributor['identifier'],$scobject,$scmessage);
                        }
                        else
                            $automail->sendMailEpMailBox($jccontributor['identifier'],$scobject,$scmessage);
                    }
                }
            }
            /////sending the mail to only to sc contributors////////
            if(in_array('sub-junior', $sendmailtousertype))
            {
                $jc0contributors = $user_obj->contributorsByTypeLang('sub-junior', $pubselectedlang);
                if(is_array($jc0contributors) && count($jc0contributors)>0)
                {
                    foreach($jc0contributors as $jc0contributor)
                    {
                        if($nomailsendjc0list != '')
                        {
                            if(!in_array($jc0contributor['identifier'],$nomailsendjc0list)) ///sending to only non participants
                                $automail->sendMailEpMailBox($jc0contributor['identifier'],$scobject,$scmessage);
                        }
                        else
                            $automail->sendMailEpMailBox($jc0contributor['identifier'],$scobject,$scmessage);
                    }
                }
            }*/   
        }
    }
    /////////mails send in individual republish development////////////
    function sendRepublishMailToContribs($profile_params)
    {
        $delivery_obj = new Ep_Delivery_Delivery();
        $article_obj = new EP_Delivery_Article();
        $user_obj = new Ep_User_User();
        $automail=new Ep_Message_AutoEmails();
        $participate_obj = new EP_Participation_Participation();
        $partcrt_obj = new Ep_Participation_CorrectorParticipation();
        $art_id = $profile_params['artId'];
        $ao_type = $profile_params['aoType'];
        $scobject = $profile_params['scobject'];
       // $scobject = utf8_decode($profile_params['scobject']);
        $scmessage = $profile_params['scmessage'];
        //$allobject = utf8_decode($profile_params['allobject']);
        $allobject = $profile_params['allobject'];
        $allmessage = $profile_params['allmessage'];
        $selectedcontribs =  $profile_params['selectedcontribs'];
        $sendmailtousertype = explode(",",$profile_params['sendmailtousertype']);
        if($profile_params['pubselectedlang']!='null')
        $pubselectedlang = explode(",",$profile_params['pubselectedlang']);


        $getpartusers = $participate_obj->getParticipationsUserIds($art_id);
		//get article detail
		$Article_details=$delivery_obj->getArtDeliveryDetails($art_id);
        if($getpartusers != 'NO')
        {
            foreach($getpartusers as $notsendmail)
            {
                $nomailsendlist[] = $notsendmail['user_id'];
                if($notsendmail['profile_type'] == 'senior')
                    $nomailsendsclist[] = $notsendmail['user_id'];
                if($notsendmail['profile_type'] == 'junior')
                    $nomailsendjclist[] = $notsendmail['user_id'];
                if($notsendmail['profile_type'] == 'sub-junior')
                    $nomailsendjc0list[] = $notsendmail['user_id'];
            }
        } // echo $nomailsendsclist; print_r($nomailsendsclist); exit;
        if($ao_type == 'private')
        {
            $contributors=array_unique(explode(",",$selectedcontribs));
            /////sending the mail to only to sc contributors////////
            if($profile_params['sendmailtoonlysc'] == 'true')
            {
                $sccontributors = $user_obj->scContributorsNotSelected($selectedcontribs);  ///all sc's except selected contributors list from popup//
                if(is_array($sccontributors) && count($sccontributors)>0)
                {
                    foreach($sccontributors as $sccontributor)
                    {
                        $contrib_details=$user_obj->getContributordetails($contributor);
						
						if($nomailsendsclist != '')
                        {
                            if(!in_array($sccontributor['identifier'],$nomailsendsclist)) ///sending to only non participants
                             {
                                if($Article_details[0]['testrequired']=="yes")
								{
									$contrib_details=$user_obj->getContributordetails($sccontributor['identifier']);
									if($contrib_details[0]['contributortest']=="yes" && ($Article_details[0]['testmarks']==NULL ||  $contrib_details[0]['contributortestmarks']>=$Article_details[0]['testmarks']))
										$automail->sendMailEpMailBoxPM($sccontributor['identifier'],$scobject,$scmessage,$profile_params['sendfrom']);
								}								
								else
									$automail->sendMailEpMailBoxPM($sccontributor['identifier'],$scobject,$scmessage,$profile_params['sendfrom']);
							}
                        }
                        else
                        {
                            if($Article_details[0]['testrequired']=="yes")
							{
								$contrib_details=$user_obj->getContributordetails($sccontributor['identifier']);
								if($contrib_details[0]['contributortest']=="yes" && ($Article_details[0]['testmarks']==NULL ||  $contrib_details[0]['contributortestmarks']>=$Article_details[0]['testmarks']))
									$automail->sendMailEpMailBoxPM($sccontributor['identifier'],$scobject,$scmessage,$profile_params['sendfrom']);
							}
							else
								$automail->sendMailEpMailBoxPM($sccontributor['identifier'],$scobject,$scmessage,$profile_params['sendfrom']);
						}
                    }
                }
            }
            /////sending the mail to only to all contributors////////
            if(is_array($contributors) && count($contributors)>0)
            {
                foreach($contributors as $contributor)
                {
                    $contrib_details=$user_obj->getContributordetails($contributor);
					if($nomailsendsclist != '')
                    {
                        if(!in_array($contributor,$nomailsendsclist)) ///sending to only non participants
                        {
                            if($Article_details[0]['testrequired']=="yes")
							{
								if($contrib_details[0]['contributortest']=="yes" && ($Article_details[0]['testmarks']==NULL ||  $contrib_details[0]['contributortestmarks']>=$Article_details[0]['testmarks']))
									$automail->sendMailEpMailBoxPM($contributor,$allobject,$allmessage,$profile_params['sendfrom']);
							}
							else
								$automail->sendMailEpMailBoxPM($contributor,$allobject,$allmessage,$profile_params['sendfrom']);
						}
                    }

                    if($nomailsendjclist != '')
                    {
                        if(!in_array($contributor,$nomailsendjclist)) ///sending to only non participants
                         {
                            if($Article_details[0]['testrequired']=="yes")
							{
								if($contrib_details[0]['contributortest']=="yes" && ($Article_details[0]['testmarks']==NULL ||  $contrib_details[0]['contributortestmarks']>=$Article_details[0]['testmarks']))
									$automail->sendMailEpMailBoxPM($contributor,$allobject,$allmessage,$profile_params['sendfrom']);
							}
							else
								$automail->sendMailEpMailBoxPM($contributor,$allobject,$allmessage,$profile_params['sendfrom']);
						}
                    }

                    if($nomailsendjc0list != '')
                    {
                        if(!in_array($contributor,$nomailsendjc0list)) ///sending to only non participants
                         {
                            if($Article_details[0]['testrequired']=="yes")
							{
								if($contrib_details[0]['contributortest']=="yes" && ($Article_details[0]['testmarks']==NULL ||  $contrib_details[0]['contributortestmarks']>=$Article_details[0]['testmarks']))
									$automail->sendMailEpMailBoxPM($contributor,$allobject,$allmessage,$profile_params['sendfrom']);
							}
							else
								$automail->sendMailEpMailBoxPM($contributor,$allobject,$allmessage,$profile_params['sendfrom']);
						}
                    }
					 if($nomailsendjc0list == '' && $nomailsendjclist == '' && $nomailsendsclist == '')
                    {
                        if($Article_details[0]['testrequired']=="yes")
						{
							if($contrib_details[0]['contributortest']=="yes" && ($Article_details[0]['testmarks']==NULL ||  $contrib_details[0]['contributortestmarks']>=$Article_details[0]['testmarks']))
								$automail->sendMailEpMailBoxPM($contributor,$allobject,$allmessage,$profile_params['sendfrom']);
						}
						else
							$automail->sendMailEpMailBoxPM($contributor,$allobject,$allmessage,$profile_params['sendfrom']); // if no participant on this article
                    }
                }
            }
        }
        if($ao_type == 'public')
        {
            /////sending the mail to only to sc contributors////////
            if(in_array('senior', $sendmailtousertype))
            {
                $sccontributors = $user_obj->contributorsByTypeLang('senior', $pubselectedlang);
                //print_r($nomailsendsclist);
                if(is_array($sccontributors) && count($sccontributors)>0)
                {
                    foreach($sccontributors as $sccontributor)
                    {
                        //echo  $sccontributor['identifier']."<br>";
                        if($nomailsendsclist != '')
                        {
                            if(!in_array($sccontributor['identifier'],$nomailsendsclist)) ///sending to only non participants
                            {
                                 if($Article_details[0]['testrequired']=="yes")
								 {
									$contrib_details=$user_obj->getContributordetails($sccontributor['identifier']);
									if($contrib_details[0]['contributortest']=="yes" && ($Article_details[0]['testmarks']==NULL ||  $contrib_details[0]['contributortestmarks']>=$Article_details[0]['testmarks']))
										 $automail->sendMailEpMailBoxPM($sccontributor['identifier'],$scobject,$scmessage,$profile_params['sendfrom']);
								 }
							     else
								    $automail->sendMailEpMailBoxPM($sccontributor['identifier'],$scobject,$scmessage,$profile_params['sendfrom']);
							}
                        }
                        else
                        {
                            if($Article_details[0]['testrequired']=="yes")
							 {
								$contrib_details=$user_obj->getContributordetails($sccontributor['identifier']);
								if($contrib_details[0]['contributortest']=="yes" && ($Article_details[0]['testmarks']==NULL ||  $contrib_details[0]['contributortestmarks']>=$Article_details[0]['testmarks']))
									 $automail->sendMailEpMailBoxPM($sccontributor['identifier'],$scobject,$scmessage,$profile_params['sendfrom']);
							 }
							else
								$automail->sendMailEpMailBoxPM($sccontributor['identifier'],$scobject,$scmessage,$profile_params['sendfrom']);
						}
                    }
                }
            }
            //exit;
            /////sending the mail to only to jc contributors////////
            if(in_array('junior', $sendmailtousertype))
            {
                $jccontributors = $user_obj->contributorsByTypeLang('junior', $pubselectedlang);
                if(is_array($jccontributors) && count($jccontributors)>0)
                {
                    foreach($jccontributors as $jccontributor)
                    {
                        if($nomailsendjclist != '')
                        {
                            if(!in_array($jccontributor['identifier'],$nomailsendjclist)) ///sending to only non participants
                             {
                                if($Article_details[0]['testrequired']=="yes")
								 {
									$contrib_details=$user_obj->getContributordetails($jccontributor['identifier']);
									if($contrib_details[0]['contributortest']=="yes" && ($Article_details[0]['testmarks']==NULL ||  $contrib_details[0]['contributortestmarks']>=$Article_details[0]['testmarks']))
										 $automail->sendMailEpMailBoxPM($jccontributor['identifier'],$scobject,$scmessage,$profile_params['sendfrom']);
								 }
								else
									$automail->sendMailEpMailBoxPM($jccontributor['identifier'],$scobject,$scmessage,$profile_params['sendfrom']);
							}
                        }
                        else
                        {
                            if($Article_details[0]['testrequired']=="yes")
							 {
								$contrib_details=$user_obj->getContributordetails($jccontributor['identifier']);
								if($contrib_details[0]['contributortest']=="yes" && ($Article_details[0]['testmarks']==NULL ||  $contrib_details[0]['contributortestmarks']>=$Article_details[0]['testmarks']))
									 $automail->sendMailEpMailBoxPM($jccontributor['identifier'],$scobject,$scmessage,$profile_params['sendfrom']);
							 }
							else
							    $automail->sendMailEpMailBoxPM($jccontributor['identifier'],$scobject,$scmessage,$profile_params['sendfrom']);
						}
                    }
                }
            }
            /////sending the mail to only to sc contributors////////
            if(in_array('sub-junior', $sendmailtousertype))
            {
                $jc0contributors = $user_obj->contributorsByTypeLang('sub-junior', $pubselectedlang);
                if(is_array($jc0contributors) && count($jc0contributors)>0)
                {
                    foreach($jc0contributors as $jc0contributor)
                    {
                        if($nomailsendjc0list != '')
                        {
                            if(!in_array($jc0contributor['identifier'],$nomailsendjc0list)) ///sending to only non participants
                            {
                                if($Article_details[0]['testrequired']=="yes")
								 {
									$contrib_details=$user_obj->getContributordetails($jc0contributor['identifier']);
									if($contrib_details[0]['contributortest']=="yes" && ($Article_details[0]['testmarks']==NULL ||  $contrib_details[0]['contributortestmarks']>=$Article_details[0]['testmarks']))
										 $automail->sendMailEpMailBoxPM($jc0contributor['identifier'],$scobject,$scmessage,$profile_params['sendfrom']);
								 }
								 else
									$automail->sendMailEpMailBoxPM($jc0contributor['identifier'],$scobject,$scmessage,$profile_params['sendfrom']);
							}
                        }
                        else
                        {
                             if($Article_details[0]['testrequired']=="yes")
							 {
								$contrib_details=$user_obj->getContributordetails($jc0contributor['identifier']);
								if($contrib_details[0]['contributortest']=="yes" && ($Article_details[0]['testmarks']==NULL ||  $contrib_details[0]['contributortestmarks']>=$Article_details[0]['testmarks']))
									 $automail->sendMailEpMailBoxPM($jc0contributor['identifier'],$scobject,$scmessage,$profile_params['sendfrom']);
							 }
							 else
								$automail->sendMailEpMailBoxPM($jc0contributor['identifier'],$scobject,$scmessage,$profile_params['sendfrom']);
						}
                    }
                }
            }

        }

    }
    ///changing the ao particiption time (dynamically in republishpopup////
    public function getdynamicselectedcontribsAction()
    {
        $articleParams=$this->_request->getParams();
        $participation_obj=new EP_Participation_Participation();
        $automail=new Ep_Message_AutoEmails();
		$user_obj = new Ep_User_User();
        $delivery_obj = new Ep_Delivery_Delivery();
        $artdeldetails = $delivery_obj->getArtDeliveryDetails($articleParams['artname']);
        if($artdeldetails[0]['AOtype'] == "private")
        {
           /* $selectedcontribs = explode(",",$articleParams['selectedcontribs']);*/
           // $contriblist = $participation_obj->participatedContributors($articleParams['artname']);
            /* echo count($selectedcontribs); exit;
           if(count($selectedcontribs) != 0)
            {
                for($i=0; $i<count($selectedcontribs); $i++)
                {
                    $key = array_search($contriblist[$i]['user_id'],$selectedcontribs);
                    if($key!==false){
                        unset($selectedcontribs[$key]);
                    }
                    $alreadyparticipated[$i] = $contriblist[$i]['user_id'];
                }
            }
            $newcontribs = $selectedcontribs; */
            /*$parameters['contribnum'] = count($selectedcontribs);*/
            $parameters['articlenum'] = $artdeldetails[0]['total_article'];
			
			if($articleParams['selectedcontribs'] != 'null')
			{
				$selectedcontribs = explode(",",$articleParams['selectedcontribs']);
				for($i=0; $i<count($selectedcontribs); $i++)
				{
					$userprofile = $user_obj->getProfileType($selectedcontribs[$i]);
					if($userprofile != 'NO') {
						if(trim($userprofile[0]['profile_type']) == "senior")
							$participatedsccount++;
					}
					else
						$participatedsccount = 0;
				}
				$parameters['contribnum'] = count($selectedcontribs);
			}
			else
			{
				$parameters['contribnum'] = 0;
				$participatedsccount = 0;
			}
			if($articleParams['onlyscmail'] == 'true')
			{
				$parameters['sccontribnum'] = $articleParams['scCount']-$participatedsccount;
                $createmailtobesentcount = ($articleParams['scCount']-$participatedsccount)+count($selectedcontribs);
			}
			else
			{
				$parameters['sccontribnum'] = 0;
                $createmailtobesentcount = count($selectedcontribs);
			}
        }
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

       // $expires=time()+(60*$artdeldetails[0]['participation_time']);
        $parameters['article_title']=$artdeldetails[0]['articleName'];
        $parameters['submitdate_bo']=date('d/m/Y H:i', $expires);
        $parameters['datetime_republish']=date('d/m/Y', $expires)." &agrave; ".date('H:i', $expires);
        $parameters['aoname_link'] = "http://ep-test.edit-place.co.uk/contrib/aosearch";
        $parameters['AO_title']= $artdeldetails[0]['deliveryTitle'];
		$submitdate_bo="<b>".$parameters['submitdate_bo']."</b>";
		
        if($artdeldetails[0]['AOtype'] == "public")
        {
            if($articleParams['checkedcontribs'] == 'senior') {
                $scmailId = 99;    //////sending the mail only leftover sc contributor////
            }
            else{
                $scmailId = 15;  ///when other than senior selected////
            }
            $email=$automail->getAutoEmail($scmailId);
            $scobject=utf8_encode($email[0]['Object']);
            $email = $automail->getMailComments($user_id=NULL,$scmailId,$parameters);
            $message=$artdeldetails[0]['mailcontent'];
			$message=preg_replace('/(<span\sid="submitdate">)([^<]|<.+>.*<\/.+>)+(<\/span>)/i', $submitdate_bo, $message);
			$scmessage = utf8_encode(stripslashes($message));
            echo $scmessage;  // echo $scmailId ;
            exit;
        }
        else
        {
            if($parameters['sccontribnum'] == 0) ///if no contributors are the to particiapte
            {
                $scmessage = ("<b style='color: red; font: 14px;'>All seniors are already in the private list, no specific email will be sent to seniors</b>.");
            }
            else{
                $scmailId = 97;    //////sending the mail only leftover sc contributor////
                $email=$automail->getAutoEmail($scmailId);
                $this->_view->scobject=$email[0]['Object'];
                $email = $automail->getMailComments($user_id=NULL,$scmailId,$parameters);
                $scmessage = utf8_encode(stripslashes($email));
            }
            $allmailId = 98;   //////sending the mails to all non sc contributor who have not paricipated as new article arrived////
            $email=$automail->getAutoEmail($allmailId);
            $this->_view->allobject=$email[0]['Object'];
            $email = $automail->getMailComments($user_id=NULL,$allmailId,$parameters);
            //$allmessage = utf8_encode(stripslashes($email));
			$message=$artdeldetails[0]['mailcontent'];
			$message=preg_replace('/(<span\sid="submitdate">)([^<]|<.+>.*<\/.+>)+(<\/span>)/i', $submitdate_bo, $message);
			$allmessage = utf8_encode(stripslashes($message));
			
            echo $scmessage."*".$allmessage."*".$createmailtobesentcount;
            exit;
        }
    }
    ///changing the ao particiption time (dynamically in republishpopup////
    public function getbulkdynamicselectedcontribsAction()
    {
        $articleParams=$this->_request->getParams();
        $participation_obj=new EP_Participation_Participation();
        $user_obj = new Ep_User_User();
        $automail=new Ep_Message_AutoEmails();
        $delivery_obj = new Ep_Delivery_Delivery();
        $artdeldetails = $delivery_obj->getArtDelDetails($articleParams['aoId']);
        if($articleParams['selectedcontribs'] != 'null')
        {
            $selectedcontribs = explode(",",$articleParams['selectedcontribs']);
            for($i=0; $i<count($selectedcontribs); $i++)
            {
                $userprofile = $user_obj->getProfileType($selectedcontribs[$i]);
                if($userprofile != 'NO') {
                    if(trim($userprofile[0]['profile_type']) == "senior")
                        $participatedsccount++;
                }
                else
                    $participatedsccount = 0;
            }
            $parameters['contribnum'] = count($selectedcontribs);
        }
        else
        {
            $parameters['contribnum'] = 0;
            $participatedsccount = 0;
        }
        if($articleParams['onlyscmail'] == 'true')
        {
            $parameters['sccontribnum'] = $articleParams['scCount']-$participatedsccount;
            $createmailtobesentcount = ($articleParams['scCount']-$participatedsccount)+count($selectedcontribs);
        }
        else
        {
            $parameters['sccontribnum'] = 0;
            $createmailtobesentcount = count($selectedcontribs);
        }
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

        // $expires=time()+(60*$artdeldetails[0]['participation_time']);
        $parameters['articlenum'] = $artdeldetails[0]['total_article'];
        $parameters['article_title']=$artdeldetails[0]['articleName'];
        $parameters['submitdate_bo']=date('d/m/Y H:i', $expires);
        $parameters['datetime_republish']=date('d/m/Y', $expires)." &agrave; ".date('H:i', $expires);
        $parameters['aoname_link'] = "http://ep-test.edit-place.co.uk/contrib/aosearch";
        $parameters['AO_title']= $artdeldetails[0]['deliveryTitle'];
        if($artdeldetails[0]['AOtype'] == "public")
        {
            if($articleParams['checkedcontribs'] == 'senior') {
                $scmailId = 95;    //////sending the mail only leftover sc contributor////
            }
            else{
                $scmailId = 95;  ///when other than senior selected////
            }
            $email=$automail->getAutoEmail($scmailId);
            //$scobject=utf8_encode($email[0]['Object']);
			$scobject=utf8_encode($artdeldetails[0]['mailsubject']);
            $email = $automail->getMailComments($user_id=NULL,$scmailId,$parameters);
            //$scmessage = utf8_encode(stripslashes($email));
			$scmessage = utf8_encode(stripslashes($artdeldetails[0]['mailcontent']));
             echo $scmessage;
            exit;
        }
        else
        {
            if($parameters['sccontribnum'] == 0) ///if no contributors are the to particiapte
            {

                $scmessage = ("<b style='color: red; font: 14px;'>All seniors are already in the private list, no specific email will be sent to seniors</b>.");
            }
            else{
                $scmailId = 93;    //////sending the mail only leftover sc contributor////
                $email=$automail->getAutoEmail($scmailId);
                $this->_view->scobject=$email[0]['Object'];
                $email = $automail->getMailComments($user_id=NULL,$scmailId,$parameters);
                $scmessage = utf8_encode(stripslashes($email));
            }
            $allmailId = 94;   //////sending the mails to all non sc contributor who have not paricipated as new article arrived////
            $email=$automail->getAutoEmail($allmailId);
            //$this->_view->allobject=$email[0]['Object'];
			$this->_view->allobject=$artdeldetails[0]['mailsubject'];
            $email = $automail->getMailComments($user_id=NULL,$allmailId,$parameters);
            //$allmessage = utf8_encode(stripslashes($email));
			$allmessage = utf8_encode(stripslashes($artdeldetails[0]['mailcontent']));
            echo $scmessage."*".$allmessage."*".$createmailtobesentcount;
            exit;
        }
    }
    ///changing the mail change in republish pup up of public ao////
    public function changepublicmailAction()
    {
        $articleParams=$this->_request->getParams();
        $participation_obj=new EP_Participation_Participation();
        $automail=new Ep_Message_AutoEmails();
        $delivery_obj = new Ep_Delivery_Delivery();

        $artdeldetails = $delivery_obj->getArtDeliveryDetails($articleParams['artId']);

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

       // $expires=time()+(60*$artdeldetails[0]['participation_time']);
        $parameters['article_title']=$artdeldetails[0]['articleName'];
        $parameters['submitdate_bo']=date('d/m/Y H:i', $expires);
        $parameters['datetime_republish']=date('d/m/Y', $expires)." &agrave; ".date('H:i', $expires);
        $parameters['articlenum'] = $artdeldetails[0]['total_article'];
        $parameters['aoname_link'] = "http://ep-test.edit-place.co.uk/contrib/aosearch";
        $parameters['AO_title']= $artdeldetails[0]['deliveryTitle'];
        if($articleParams['contribtype'] == 'onlysc') {
            $scmailId = 99;    //////sending the mail only leftover sc contributor////
        }
        else{
            $scmailId = 15;  ///when other than senior selected////
        }
        $email=$automail->getAutoEmail($scmailId);
        $scobject=utf8_encode($email[0]['Object']);
        $email = $automail->getMailComments($user_id=NULL,$scmailId,$parameters);
        $scmessage = utf8_encode(stripslashes($email));
        echo $scmessage."*".$scobject;  // echo $scmailId ;
        exit;

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