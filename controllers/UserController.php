<?

class UserController extends Ep_Controller_Action
{
	private $controller = "user";
	protected $session = null;
	private $text_admin;
	private $my_obj;
	private $filesname;
    private $entryLang;
    private $cntry;
    private $totalLang;
    private $my_view;

	public function init()
	{
		parent::init();
		Zend_Loader::loadClass('Ep_Document_DocTrack');
		$this->session =  new Zend_Session_Namespace('users');
		$this->_view->lang = $this->_lang;
		$this->adminLogin	= Zend_Registry::get ( 'adminLogin' );
		$this->session = $this->adminLogin;
		$this->_view->loginName = $this->adminLogin->loginName;
        ////if session expires/////
        if($this->adminLogin->loginName == '' && $_SERVER['HTTP_X_REQUESTED_WITH']=='XMLHttpRequest') {
            echo "session expired...please <a href='/index'>click here</a> to login"; exit;
        }

    }

    public function emailExitsAction()
    {
        $user_obj = new Ep_User_User();
        $user_params=$this->_request->getParams();
		
		$emailexit = $user_obj->getExistingEmail($user_params['email']);
		$emailexit=trim($emailexit);
		if($user_params['user_type']=='super_client')
		{
			$emailexit = $user_obj->getExistingEmail($user_params['fieldValue']);
			$emailexit=trim($emailexit);
			
			$arrayToJs = array();
			$arrayToJs[0] = $user_params['fieldId'];
			
			if($emailexit=='yes')
			{			
				$arrayToJs[1] = false;
				echo json_encode($arrayToJs);				
			}			
			else
			{			
				$arrayToJs[1] = true;
				echo json_encode($arrayToJs);
			}	
		}
		else
			echo $emailexit;  
		exit;
    }
    public function yahooSkypeIdsExitsAction()
    {
        $user_obj = new Ep_User_BoUser();
        $user_params=$this->_request->getParams();
        if($user_params['messenger']=='yahoo_id')
        {
           //echo  $user_params['user_status']; echo "helo"; exit;
            $emailexit = $user_obj->getExistingIds($user_params['messenger'], $user_params['fieldValue'], $user_params['user_status'], $user_params['userId']);
            $emailexit=trim($emailexit);

            $arrayToJs = array();
            $arrayToJs[0] = $user_params['fieldId'];

            if($emailexit=='yes')
            {
                $arrayToJs[1] = false;
                echo json_encode($arrayToJs);
            }
            else
            {
                $arrayToJs[1] = true;
                echo json_encode($arrayToJs);
            }
            exit;
        }
        else if($user_params['messenger']=='skype_id')
        {
            $emailexit = $user_obj->getExistingIds($user_params['messenger'], $user_params['fieldValue'], $user_params['user_status'], $user_params['userId']);
            $emailexit=trim($emailexit);

            $arrayToJs = array();
            $arrayToJs[0] = $user_params['fieldId'];

            if($emailexit=='yes')
            {
                $arrayToJs[1] = false;
                echo json_encode($arrayToJs);
            }
            else
            {
                $arrayToJs[1] = true;
                echo json_encode($arrayToJs);
            }
            exit;
        }

    }
	//check Client  exists or not
	 public function clientExistsAction()
    {
        $client_obj = new Ep_User_Client();
        $client_params=$this->_request->getParams();
		
		$edit_client=$client_params['edit_client'];
		
		//$client_exist = $client_obj->getExistingClient($client_params['agency_name'],$edit_client);
		//$client_exist=trim($client_exist);
		$client_exist = $client_obj->getExistingClient($client_params['fieldValue'],$edit_client);
		$client_exist=trim($client_exist);
		
		$arrayToJs = array();
		$arrayToJs[0] = $client_params['fieldId'];
		
		if($client_exist=='yes')
		{			
			$arrayToJs[1] = false;
			echo json_encode($arrayToJs);				
		}			
		else
		{			
			$arrayToJs[1] = true;
			echo json_encode($arrayToJs);
		}
		
		/* if($client_exist=='yes')	
			echo "false";
		else	
			echo "true"; */
		
		exit;
    }
	//check Client  exists or not
	 public function clientNoExistsAction()
    {
        $client_obj = new Ep_User_Client();
        $client_params=$this->_request->getParams();
		
		$user_id = $client_params['user_id'];
		
		$client_exist = $client_obj->checkClientNo($client_params['fieldValue'],$user_id);
		$client_exist=trim($client_exist);
		
		$arrayToJs = array();
		$arrayToJs[0] = $client_params['fieldId'];
		
		if($client_exist=='yes')
		{			
			$arrayToJs[1] = false;
			echo json_encode($arrayToJs);				
		}			
		else
		{			
			$arrayToJs[1] = true;
			echo json_encode($arrayToJs);
		}
		
		/* if($client_exist=='yes')	
			echo "false";
		else	
			echo "true"; */
		
		exit;
    }
    public function newBoUserAction()
	{
        $usergrp_obj=new Ep_User_UserGroupAccess();
        $groups =  $usergrp_obj->getAllUserGroupNames();
        foreach($groups as $key=>$value)
        {
            $usergroups[$value['groupName']]=strtoupper($value['groupName']);
        }
        $this->_view->usergroups=$usergroups;
        if($this->_request->isPost())
        {
            $user_params=$this->_request->getParams();
            $userplus_obj = new Ep_User_UserPlus();
            $user_obj = new Ep_User_User();
            $group_obj = new Ep_User_UserGroupAccess();

            $emailexit = $user_obj->getExistingEmail($user_params["email"]);
            if($emailexit == 'yes')
            {
                $this->_helper->FlashMessenger('Email id is already exit.');
                $this->_redirect("/user/contributors?submenuId=ML10-SL1&tab=newusertab");
            }
            ////for goroup Id in users table////
            $grouparray = array('superadmin'=>1,'ceouser'=>2,'salesuser'=>3, 'chiefeditor'=>4, 'editor'=>5, 'seouser'=>6, 'customercare'=>7, 'partner'=>8, 'facturation'=>9, 'custom'=>10, 'multilingue'=>11,'techuser'=>13,'produser'=>14,'techmanager'=>15,'seomanager'=>16,'prodsubmanager'=>17,'salesmanager'=>18,'prodmanager'=>19);
            $grouppage = $group_obj->getGroup($grouparray[$user_params["type"]]);
            $user_obj->login=$user_params["login"] ;
            $user_obj->email=$user_params["email"] ;
            $user_obj->password=$user_params["password"] ;
            $user_obj->status=$user_params["status"] ;
            $user_obj->type=$user_params["type"];
            $user_obj->pageId=$grouppage[0]->pageId;
            $user_obj->profile_type="NULL" ;
            $user_group = $grouparray[$user_params["type"]];
            $user_obj->groupId=$user_group;

            try
            {
                if($user_obj->insert())
                {
                    $user_identifier = $user_obj->getIdentifier();
                    $userplus_obj->user_id=$user_identifier;
                    $userplus_obj->initial=$user_params["initial"] ;
                    $userplus_obj->first_name=$user_params["first_name"] ;
                    $userplus_obj->last_name=$user_params["last_name"] ;
                    $userplus_obj->address=$user_params["address"] ;
                    $userplus_obj->city=$user_params["city"] ;
                    $userplus_obj->state=$user_params["state"] ;
                    $userplus_obj->zipcode=$user_params["zipcode"] ;
                    $userplus_obj->country=$user_params["country"] ;
                    $userplus_obj->phone_number=$user_params["phone_number"] ;
                    $userplus_obj->insert();
                    ////////////inserting the profile pic////////////
                    $realfilename=$_FILES['uploadfile']['name'] ;
                    $uploaddir = '/home/sites/site7/web/FO/profiles/bo/'.$user_identifier.'/';
                    if(!is_dir($uploaddir))
                    {
                        mkdir($uploaddir,true);
                        chmod($uploaddir,0777);
                    }
                    $file = $uploaddir."logo.jpg";
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
                                $newimage_crop->resizeToWidth(90);
                            elseif($height>$width)
                                $newimage_crop->resizeToHeight(90);
                            else
                                $newimage_crop->resize(90,90);

                            $newimage_crop->save($file);
                            chmod($file,0777);
                        }
                    }
                }
                $this->_helper->FlashMessenger('Profile Created Successfully.');
                $this->_redirect("/user/bo-users?submenuId=ML10-SL3");
                //$this->render('user_adduser');
            }
            catch(Zend_Exception $e)
            {
                $this->_helper->FlashMessenger('Profile Creation Failed.');
                $this->_redirect("/user/bo-users?submenuId=ML10-SL3");
            }
        }
        $this->_view->render("user_newbouser");
    }
    ///////////////edit user function //////////////////
    public function userEditAction()
	{
        $userId = $this->_request->getParam('userId');
        $userplus_obj=new Ep_User_UserPlus();
        $changelog_obj = new Ep_User_ProfileChangeLog();
        $details= $userplus_obj->getUsersDetailsOnId($userId);
        $user_obj = new Ep_User_User();
        $usergrouplist = $user_obj->getUserGroups();
        foreach($usergrouplist as $key=>$value)
        {
            /* if($value['groupName']!='superadmin')*/
            $usergroups[$value['groupName']]=$value['groupName'];
        }

        $this->_view->usergroups =  $usergroups;
        $this->_view->Userdetails=$details;
        $this->_view->profilepic = "/FO/profiles/bo/".$details[0]['identifier']."/logo.jpg";

        if($this->_request-> isPost())
        {
            $user_params=$this->_request->getParams();  // print_r($user_params); exit;
            $userplus_obj = new Ep_User_UserPlus();
            $user_obj = new Ep_User_User();
            ////for goroup Id in users table////
            $grouparray = array('superadmin'=>1,'ceouser'=>2,'salesuser'=>3, 'chiefeditor'=>4, 'editor'=>5, 'seouser'=>6, 'customercare'=>7, 'partner'=>8, 'facturation'=>9, 'custom'=>10, 'multilingue'=>11,'techuser'=>13,'produser'=>14,'techmanager'=>15,'seomanager'=>16,'prodsubmanager'=>17,'salesmanager'=>18,'prodmanager'=>19);
            $user_obj->login=$user_params["login"] ;
            $user_obj->email=$user_params["email"] ;
            $user_obj->password=$user_params["password"] ;
            $user_obj->status=$user_params["status"] ;
            $user_obj->type=$user_params["type"] ;
            $user_group = $grouparray[$user_params["type"]];
            $user_obj->groupId=$user_group;
            $data_user = array("login"=>$user_obj->login, "email"=>$user_obj->email, "password"=>$user_obj->password,
                "status"=>$user_obj->status, "type"=>$user_obj->type, "groupId"=>$user_obj->groupId);
            $query_user = "identifier= '".$userId."'";
            try
            {
                $userplus_obj->initial=$user_params["initial"] ;
                $userplus_obj->first_name=$user_params["first_name"] ;
                $userplus_obj->last_name=$user_params["last_name"] ;
                $userplus_obj->address=$user_params["address"] ;
                $userplus_obj->city=$user_params["city"] ;
                $userplus_obj->state=$user_params["state"] ;
                $userplus_obj->zipcode=$user_params["zipcode"] ;
                $userplus_obj->country=$user_params["country"] ;
                $userplus_obj->phone_number=$user_params["phone_number"] ;

                $data_userplus = array("initial"=>$userplus_obj->initial, "first_name"=>$userplus_obj->first_name, "last_name"=>$userplus_obj->last_name,
                    "address"=>$userplus_obj->address, "city"=>$userplus_obj->city, "state"=>$userplus_obj->state, "zipcode"=>$userplus_obj->zipcode,
                    "country"=>$userplus_obj->country, "phone_number"=>$userplus_obj->phone_number);
                $query_userplus = "user_id= '".$userId."'";

                $user_obj->updateUser($data_user,$query_user);
                $userplus_obj->updateUserPlus($data_userplus,$query_userplus);
                if($details[0]['email'] != $_REQUEST['email'])
                {
                    $changelog_obj->user_id=$_REQUEST['userId'];
                    $changelog_obj->old_email=$details[0]['email'];
                    $changelog_obj->new_email=$_REQUEST['email'];
                    $changelog_obj->changed_by=$this->adminLogin->userId;
                    $changelog_obj->insert();
                }
                $this->_helper->FlashMessenger('Profile Updated Successfully.');
                $this->_redirect("/user/bo-users?submenuId=ML10-SL3");
                // $this->render('user_userdetails');
            }
            catch(Zend_Exception $e)
            {
                echo $e->getMessage();
                $this->_view->error_msg =$e->getMessage()." D&eacute;sol&eacute;! Mise en erreur.";
                $this->render('user_useredit');
            }
        }else
        {
            $this->render('user_useredit');
        }
    }
    ///////////edit the contributor/////////////////
    public function contributorEditAction()
    {
        $user_obj=new Ep_User_User();
        $changelog_obj = new Ep_User_ProfileChangeLog();
        $experience_obj=new EP_User_ContributorExperience();
        $mail_obj=new Ep_Message_AutoEmails();
        $userId = $this->_request->getParam('userId');
        $user_details=$user_obj->getContributordetails($userId);
        /* *getting User expeience details**/
        $jobDetails=$experience_obj->getExperienceDetails($userId,'job');
        if($jobDetails!="NO")
            $this->_view->jobDetails=$jobDetails;
        $educationDetails=$experience_obj->getExperienceDetails($userId,'education');
        if($educationDetails!="NO")
            $this->_view->educationDetails=$educationDetails;
        /* *iNOVICE inFO ***/
        $this->_view->payment_type=$user_details[0]['payment_type'];
        $this->_view->pay_info_type=$user_details[0]['pay_info_type'];
        $this->_view->SSN=$user_details[0]['SSN'];
        $this->_view->company_number=$user_details[0]['company_number'];
        $this->_view->vat_check=$user_details[0]['vat_check'];
        $this->_view->VAT_number=$user_details[0]['VAT_number'];
        /* *Paypal and RIB info**/
        $this->_view->paypal_id=$user_details[0]["paypal_id"] ;
        $RIB_ID=explode("|",$user_details[0]["rib_id"]) ;
        if(($user_details[0]['pay_info_type']=='out_france' || $user_details[0]['country']!=38)&& count($RIB_ID)==2)
        {
            $this->_view->rib_id_6=$RIB_ID[0];
            $this->_view->rib_id_7=$RIB_ID[1];
        }
        else
        {
            $this->_view->rib_id_1=$RIB_ID[0];
            $this->_view->rib_id_2=$RIB_ID[1];
            $this->_view->rib_id_3=$RIB_ID[2];
            $this->_view->rib_id_4=$RIB_ID[3];
            $this->_view->rib_id_5=$RIB_ID[4];
        }
             //////edit contributor////////////////////////////////////
            $this->_view->user_detail=$user_details;   //print_r($user_details); exit;
            $this->_view->self_details=utf8_encode($user_details[0]['self_details']);
            $this->_view->stats=$_GET['stats'];
            $this->_view->loguser=$this->adminLogin->userId;
            $this->_view->category_more=unserialize($user_details[0]['category_more']);
            $this->_view->language_more=unserialize($user_details[0]['language_more']);
            if($this->_request->getParam('submit_contrib')!= '')
            {
                $user_obj->updatecontribUser($_REQUEST);
                /*if($user_details[0]['email'] != $_REQUEST['email'])
                {
                    $changelog_obj->user_id=$_REQUEST['userId'];
                    $changelog_obj->old_email=$user_details[0]['email'];
                    $changelog_obj->new_email=$_REQUEST['email'];
                    $changelog_obj->changed_by=$this->adminLogin->userId;
                    $changelog_obj->insert();
                }*/
                $this->updateExperienceDetails($_REQUEST,'job');
                $this->updateExperienceDetails($_REQUEST,'education');
                //If profile type changed from junior to senior
                if($_REQUEST['profile_type']=='senior' && $_REQUEST['prev_profile']=='junior')
                {
                   // $parameters['jc_limit']=$this->getConfiguredval('jc_limit');
                   // $parameters['sc_limit']=$this->getConfiguredval('sc_limit');
                    $mail_obj->messageToEPMail($userId,30,$parameters);
                }
                //If profile type2 in corrector aspect changed from junior to senior
                if($_REQUEST['type2']=='yes' && $_REQUEST['profile_type2']=='senior' && $_REQUEST['prev_profile2']=='junior')
                {
                    $mail_obj->messageToEPMail($userId,110,$parameters);
                }
                $this->_helper->FlashMessenger('Profile Updated Successfully.');
                $this->_redirect("/user/contributor-edit?submenuId=ML2-SL7&tab=editcontrib&userId=".$userId);
            }
             //////view contributor////////////////////////////////////
            $this->_view->ep_contrib_profile_language_more=explode(",",$user_details[0]['language_more']);
            $this->_view->ep_contrib_profile_category=explode(",",$user_details[0]['favourite_category']);
            $this->_view->self_details=utf8_encode($user_details[0]['self_details']);
            $this->_view->stats=$_GET['stats'];
            $this->_view->loguser=$this->adminLogin->userId;
            $contrib_picture_path = "/home/sites/site7/web/FO/profiles/contrib/pictures/".$user_details[0]['user_id']."/".$user_details[0]['user_id']."_h.jpg";
            if(file_exists($contrib_picture_path))
                $contrib_picture_path = "http://ep-test.edit-place.co.uk/FO/profiles/contrib/pictures/".$user_details[0]['user_id']."/".$user_details[0]['user_id']."_h.jpg";
            else
                $contrib_picture_path = "http://ep-test.edit-place.co.uk/FO/images/Contrib/profile-img-def.png";
            $this->_view->user_pic=$contrib_picture_path;

            $expCat =   explode(',', preg_replace("/\([^)]+\)/","",$this->unserialisearray($user_details[0]['category_more']))) ;
            preg_match_all('#\((.*?)\)#', $this->unserialisearray($user_details[0]['category_more']), $match) ;

            foreach ($expCat as $key => $value) {
                $impCat[]   =   $this->category_array[$value] . '(' . $match[1][$key] . ')' ;
            }
            $user_details[0]['category_more'] = implode(',', $impCat) ;
            unset($impCat) ;

            $user_details[0]['language_more'] = $this->unserialisearray($user_details[0]['language_more']);
            $workexpDetails=$user_obj->getExperienceDetails($user_details[0]['user_id'],'job');
            if($workexpDetails!="NO")
            {
                $ecnt=0;
                foreach($workexpDetails as $workexp)
                {
                    $workexpDetails[$ecnt]['start_date']=date('FY',strtotime($workexp['from_year']."-".$workexp['from_month']));
                    if($workexp['still_working']=='yes')
                        $workexpDetails[$ecnt]['end_date']='Actuel';
                    else
                        $workexpDetails[$ecnt]['end_date']=date('FY',strtotime($workexp['to_year']."-".$workexp['to_month']));
                    $ecnt++;
                }
                $this->_view->educationDetailsview=$workexpDetails;
            }
            // print_r($user_details);    exit;
            $this->_view->user_detail=$user_details;
            $this->_view->country_name=$this->country_array[$user_details[0]['country']];
            $this->_view->ep_contrib_profile_language_more=explode(",",$user_details[0]['language_more']);
            $this->_view->ep_contrib_profile_category=explode(",",$user_details[0]['favourite_category']);
            $this->_view->profession=$this->profession_array[$user_details[0]['profession']];
            $this->_view->language=$this->language_array[$user_details[0]['language']];
            $this->_view->nationality=$this->nationality_array[$user_details[0]['nationality']];
            $this->_view->self_details=utf8_encode(strip_tags($user_details[0]['self_detailss']));
            //lang_more str
            $language_more="";
            if($user_details[0]['language_more']!=NULL)
            {
                $lang_more=explode(",",$user_details[0]['language_more']);

                if(count($lang_more)>0)
                {
                    for($l=0;$l<count($lang_more);$l++)
                    {
                        $language_more.=$this->language_array[$lang_more[$l]];

                        if($l!=count($lang_more)-1)
                            $language_more.=",";
                    }
                }
            }
            $this->_view->language_more1=$language_more;
            //fav category str
            $favourite_category="";
            if($user_details[0]['favourite_category']!=NULL)
            {
                $fav_cat=explode(",",$user_details[0]['favourite_category']);

                if(count($fav_cat)>0)
                {
                    for($l=0;$l<count($fav_cat);$l++)
                    {
                        $favourite_category.=$this->category_array[$fav_cat[$l]];

                        if($l!=count($fav_cat)-1)
                            $favourite_category.=",";
                    }
                }
            }
            $this->_view->favourite_category=$favourite_category;
            $eduarray=array("1" => "Bac +1","2" => "Bac +2","3" => "Bac +3","4" => "Bac +4","5" => "Bac +5","6" => "Bac+5 et plus");
            $this->_view->education=$eduarray[$user_details[0]['education']];

        $this->_view->render("user_contributoredit");
    }
    ///////////edit the contributor/////////////////
    public function briefContributorEditAction()
    {
        $user_obj=new Ep_User_User();
        $changelog_obj = new Ep_User_ProfileChangeLog();
        $userId = $this->_request->getParam('userId');
        $user_details=$user_obj->getContributordetails($userId);
        //////edit contributor////////////////////////////////////
        $this->_view->user_detail=$user_details;   //print_r($user_details); exit;

        if($this->_request->getParam('submit_contrib')!= '') {
            $user_obj->updateBriefContribUser($_REQUEST);
            if ($user_details[0]['email'] != $_REQUEST['email']) {
                $changelog_obj->user_id = $_REQUEST['userId'];
                $changelog_obj->old_email = $user_details[0]['email'];
                $changelog_obj->new_email = $_REQUEST['email'];
                $changelog_obj->changed_by = $this->adminLogin->userId;
                $changelog_obj->insert();
            }
            $this->_helper->FlashMessenger('Profile Updated Successfully.');
            //$this->_redirect("/user/contributor-edit?submenuId=ML10-SL1&tab=briefeditcontrib&userId=".$userId);
            $this->_redirect("/user/search-contributors?submenuId=ML10-SL6");
        }
        $this->_view->render("user_briefwriteredit");
    }
    /* Inserting or Updating User Experince**/
    /*edited by naseer on 14-09-2015 */
    public function updateExperienceDetails($profile_params,$type)
    {
        $experience_obj=new EP_User_ContributorExperience();
        /* *Inserting or Updating User Experince**/
        $contrib_identifier=$profile_params['userId'];
        if($type=='job')
            $details=$profile_params['job_title'];
        else if($type=='education')
            $details=$profile_params['training_title'];

        //print_r($profile_params); exit;
        if (count($details) > 0) {
            foreach ($details as $key => $title) {
                if ($type == 'job') {
                    $institute = $profile_params['job_institute'][$key];
                    $contract = $profile_params['ep_job'][$key];
                    $start_month = $profile_params['start_month'][$key];
                    $start_year = $profile_params['start_year'][$key];
                    $end_month = $profile_params['end_month'][$key];
                    $end_year = $profile_params['end_year'][$key];
                    $still_working = $profile_params['still_working'][$key];
                    $job_identifier = $profile_params['job_identifier'][$key];

                    $condition = $title && $institute && $contract && $start_month && $start_year && (($end_month && $end_year) || $still_working);
                } else if ($type == 'education') {
                    $institute = $profile_params['training_institute'][$key];
                    $start_month = $profile_params['start_train_month'][$key];
                    $start_year = $profile_params['start_train_year'][$key];
                    $end_month = $profile_params['end_train_month'][$key];
                    $end_year = $profile_params['end_train_year'][$key];
                    $still_working = $profile_params['still_training'][$key];
                    $contract = '';
                    $job_identifier = $profile_params['training_identifier'][$key];

                    $condition = $title && $institute && $start_month && $start_year && (($end_month && $end_year) || $still_working);
                }
                if ($condition) {
                    $experience_obj->user_id = $contrib_identifier;
                    $experience_obj->title = utf8_decode($title);
                    $experience_obj->institute = utf8_decode($institute);
                    $experience_obj->contract = $contract;
                    $experience_obj->type = $type;
                    $experience_obj->from_month = $start_month;
                    $experience_obj->from_year = $start_year;
                    if ($still_working) {
                        $experience_obj->still_working = 'yes';
                        /*$experience_obj->to_month='0';
                        $experience_obj->to_year='0';*///commented by naseer on 13-11-2015//
                    } else {
                        $experience_obj->to_month = $end_month;
                        $experience_obj->to_year = $end_year;
                        $experience_obj->still_working = 'no';
                    }
                    //echo "<pre>";print_r($experience_obj);exit;
                    if ($job_identifier !== '') {

                        $experience_obj->updated_at = date('Y-m-d h:i:s');
                        $experience_obj->identifier = $job_identifier;
                        $updateExperienceArray = $experience_obj->loadintoArray();
                        unset($updateExperienceArray['identifiers']);
                        if ($still_working) {
                            unset($updateExperienceArray['to_month']);
                            unset($updateExperienceArray['to_year']);
                        }


                        /*added by naseer on 09-11-2015*/
                        //fetch the old values saved in the datbase//
                        $jobDetails = $experience_obj->getIndividualExperienceDetails($job_identifier, $type);
                        $this->updateuserlogs($jobDetails[0], $updateExperienceArray);

                        /*end of added by naseer on 09-11-2015*/

                        $response1 = $experience_obj->updateExperience($updateExperienceArray, $job_identifier);
                        //return ($response1) ? '1' : '0';
                    } else {
                        $experience_obj->insert();
                    }

                }
            }
        }

    }

    public function deleteProfileDataAction(){
        $profile_params=$this->_request->getParams();
        $experience_obj=new Ep_User_ContributorExperience();

        if($profile_params['type'] && $profile_params['identifier'])
        {
            $identifier=$profile_params['identifier'];
            if($profile_params['type']=='education' || $profile_params['type']=='job')
            {
                $experience_obj->deleteExperience($identifier);
            }
        }
    }
    ///////////edit the client/////////////////
    public function clientEditAction()
    {
        $user_obj=new Ep_User_User();
        $changelog_obj = new Ep_User_ProfileChangeLog();
        $ao_obj = new Ep_Delivery_Delivery();
        $mail_obj=new Ep_Message_AutoEmails();
        $userId = $this->_request->getParam('userId');
        $user_details=$user_obj->getUserdetails($userId);
        $this->_view->user_detail=$user_details;
        
		//////edit client/////////////////////////////////
            //Favourite contributors
            $favcontribslist=array();
            $favcontribs=$user_obj->ListallfavContribs($userId);
            for($f=0;$f<count($favcontribs);$f++)
                $favcontribslist[]=$favcontribs[$f]['identifier'];

            $this->_view->favcontribslist=$favcontribslist;

            //List of contributors
            $contribslist=array();
            $user_array=$user_obj->listusers('2');
            for($u=0;$u<count($user_array);$u++)
            {
                $name=$user_array[$u]['email'];
                $nameArr=array($user_array[$u]['company_name'],$user_array[$u]['first_name'],$user_array[$u]['last_name']);
                $nameArr=array_filter($nameArr);

                if(count($nameArr)>0)
                    $name.="(".implode(", ",$nameArr).")";
                $contribslist[$user_array[$u]['identifier']]=strtoupper($name);
            }
            $this->_view->contribslist=$contribslist;
            if($this->_request->getParam('submit_client')!= '')
            {   //echo $user_details[0]['email'];  print_r($_REQUEST); exit;
                //Code To Check and Update Paypercent Updater and Date
                $userId = $this->_request->getParam('userId');
                $user_details=$user_obj->getUserdetails($userId);
                if($user_details[0]['paypercent']!= $_REQUEST['paypercent']){
                    $user_obj->updatePaypercentChangeLog($userId,$this->adminLogin->userId);
                }
				$user_obj->updateclientUser($_REQUEST);
                if($user_details[0]['email'] != $_REQUEST['email'])
                {
                    $changelog_obj->user_id=$_REQUEST['userId'];
                    $changelog_obj->old_email=$user_details[0]['email'];
                    $changelog_obj->new_email=$_REQUEST['email'];
                    $changelog_obj->changed_by=$this->adminLogin->userId;
                    $changelog_obj->insert();
                }
                $this->_helper->FlashMessenger('Profile Updated Successfully.');
                $this->_redirect("/user/client-edit?submenuId=ML2-SL7&tab=editclient&userId=".$userId);
            }
        //////////////view client/////////////////////////////////////////
            $client_picture_path = "/home/sites/site7/web/FO/profiles/clients/logos/".$user_details[0]['identifier']."/".$user_details[0]['identifier']."_global.png";
            if(file_exists($client_picture_path))
                $client_picture_path = "http://ep-test.edit-place.co.uk/FO/profiles/clients/logos/".$user_details[0]['identifier']."/".$user_details[0]['identifier']."_global.png";
            else
                $client_picture_path = "http://ep-test.edit-place.co.uk/FO/images/Contrib/profile-img-def.png";
            $this->_view->user_pic=$client_picture_path;

            //Favourite comtributors
            $favlist=$user_obj->ListallfavContribs($userId);

            $favcontrib=array();
            for($f=0;$f<count($favlist);$f++)
            {
                if($favlist[$f]['first_name']!="")
                    $favcontrib[]=$favlist[$f]['first_name'].'&nbsp;'.$favlist[$f]['last_name'];
                else
                    $favcontrib[]=$favlist[$f]['email'];
            }
            $this->_view->favcontributors=implode(", ",$favcontrib);
            $this->_view->country_name=$this->country_array[$user_details[0]['country']];
        
		///////////////////client aos list /////////////////////////////////////
        $payment_obj = new Ep_Payment_Payment();

        $ao =   $ao_obj->getAOviewinfo($userId);
        if($ao != '')
        {
            $i = 0;
            do {
                $details= $payment_obj->getInvoices($ao[$i]['id']);
                if(file_exists('/home/sites/site7/web/FO/invoice/client/'.$details[0]['user_id'].'/'.$details[0]['invoice_id'].'.pdf')) :
                    $ao[$i]['inv'] = 1;
                else :
                    $ao[$i]['inv'] = 0;
                endif ;

                $i++ ;
            } while ($i < sizeof($ao));
            $this->_view->ao =   $ao;
        }
        $this->_view->render("user_clientedit");
    }
//    public function getPagesAction()
//	{
//         $userdetails=new Ep_User_UserPlus();
//         $details= $userdetails->getUsersDetails();
//         $this->_view->Userdetails=$details;
//         $this->render('user_userdetails');
//    }
    /////////permissions to users/////////
    public function permissionsAction()
	{
        $p = new Ep_Controller_Page($this->pageFile);
        ////getting groups///////
        $group_obj = new Ep_User_UserGroupAccess();
        $groups = $group_obj->getAllGroups();
        $this->_view->groupList = $groups;
       ////getting users////////
        $user_obj = new Ep_User_User();
        $users = $user_obj->getUsers();
        $this->_view->userList = $users;
        /////////////////////////////////////
		if($this->_request->getParam('sel_group'))
		$this->_view->sel_group = $this->_request->getParam('sel_group');
		else
		$this->_view->sel_group = "";
		
		if($this->_request->getParam('sel_user'))
		$this->_view->sel_user = $this->_request->getParam('sel_user');
		else
		$this->_view->sel_user = "";
		
        if($this->_request->getParam('tab') == 'permissionstab')
        {
            $sel_group = $this->_request->getParam('sel_group');
            $sel_user = $this->_request->getParam('sel_user');
            $this->_view->GpSel=$sel_group;
            $this->_view->UsrSel=$sel_user;
            $total = $this->_request->getParam('hid_totalrows');///no of pages///
            if($this->_request->getParam('assign') == 'assign')
            {
               /*  for( $i=1 ; $i<=$total; $i++)
                {
                    //chk_.$i = $this->_request->getParam('chk_',$i);
                    if($this->_request->getParam('chk_'.$i) == 'chk_'.$i)
                    {
                        $chkPages[] = $this->_request->getParam('chk_',$i);
                        $chkPagesIds[] = $i;
                    }
                } 
				 $chkedpageIds = implode ("|",$chkPagesIds);
				*/
				/* $selectedpages = $this->_request->getParam('selectedpages');
                $selectedpages = str_replace("on,", "", $selectedpages);
                $chkedpageIds = str_replace(",", "|", $selectedpages); */
				$chkedpageIds = implode("|",$this->_request->getParam('chk'));
                if($sel_user!='0')
                {
                    $data = array("pageId"=>$chkedpageIds);////////updating
                    $query = "identifier= '".$sel_user."'";
                    $user_obj->updateUser($data,$query);
                }
                else if($sel_group!='0')
                {
                    $data = array("pageId"=>$chkedpageIds);
                    $query = "id= '".$sel_group."'";
                    $group_obj->updateGroup($data,$query);
                }
                $this->_helper->FlashMessenger('Permissions updated Successfully.');
            }
            //////for groups
            if($sel_group != '')
            {
                $grouppage = $group_obj->getGroup($sel_group);
                $this->_view->pageGpSel = explode ("|",$grouppage[0]->pageId);
            }
            /////for users
            if($sel_user != '')
            {
                $userpage = $user_obj->getUserPages($sel_user);
                $this->_view->pageUsrSel = explode ("|",$userpage[0]->pageId);
            }
			if($this->_request->getParam('permissionpassto') == 'yes')
            {
                /*for( $i=1 ; $i<=$total; $i++)
                            {
                                //chk_.$i = $this->_request->getParam('chk_',$i);
                                if($this->_request->getParam('chk_'.$i) == 'chk_'.$i)
                                {
                                    $chkPages[] = $this->_request->getParam('chk_',$i);
                                    $chkPagesIds[] = $i;
                                }
                            }
                            $chkedpageIds = implode ("|",$chkPagesIds);*/
                $selectedpages = $this->_request->getParam('selectedpages');
                $selectedpages = str_replace("on,", "", $selectedpages);
                $chkedpageIds = str_replace(",", "|", $selectedpages);
				$chkedpageIds = implode("|",$this->_request->getParam('chk'));
                for($i=0; $i<count($to_sel_user); $i++)
                {
                    $data = array("pageId"=>$chkedpageIds);////////updating
                    $query = "identifier= '".$to_sel_user[$i]."'";
                    $user_obj->updateUser($data,$query);
                }
                $this->_helper->FlashMessenger('Permissions Inherited Successfully.');
            }
        }

        $pageList = $p->selectAllPagesBySegment(1);
      	$this->_view->pageList = $pageList;
        $this->render('user_permissions');
    }
    /////////permissions to users/////////
    public function menuPermissionsAction()
    {
        ////// main menu  ////////
        $MainMenu = $this->_arrayDb->loadArrayv2("EP_BO_MainMenu", $this->_lang);
        //$this->_view->sel_mainmenu = $MainMenu;
        $subMenus=array();
        foreach($MainMenu as $key => $value)
        {
            if($this->_arrayDb->loadArrayv2($key, $this->_lang))
            {
                $SubMenu = $this->_arrayDb->loadArrayv2($key, $this->_lang);
                    array_push($subMenus,$SubMenu);

            }
        }
        $allMenus=array();
        for($i=0; $i<count($subMenus); $i++)
        {
            foreach($MainMenu as $mainkey => $mainvalue)
            {
                $allMenus[$mainkey]=  $mainvalue;
            }
            foreach($subMenus[$i] as $alkey => $alvalue)
            {
                $allMenus[$alkey]=  $alvalue;
            }
        }
        ////getting groups///////
        $group_obj = new Ep_User_UserGroupAccess();
        $groups = $group_obj->getAllGroups();
        $this->_view->groupList = $groups;
        ////getting users////////
        $user_obj = new Ep_User_User();
        $users = $user_obj->getUsers();
        $this->_view->userList = $users;
        /////////////////////////////////////
        if($this->_request->getParam('tab') == 'permissionstab')
        {
            $sel_group = $this->_request->getParam('sel_group');
            $sel_user = $this->_request->getParam('sel_user');
            $this->_view->GpSel=$sel_group;
            $this->_view->UsrSel=$sel_user;

            if($this->_request->getParam('assign') == 'assign')
            {
                $menus = $this->_request->getParam('selectedmenus');
                 $chkedmenuIds = str_replace(",","|",$menus);
                if($sel_user!='0')
                {
                    $data = array("menuId"=>$chkedmenuIds);////////updating
                    $query = "identifier= '".$sel_user."'";
                    $user_obj->updateUser($data,$query);
                }
                else if($sel_group!='0')
                {
                    $data = array("menuId"=>$chkedmenuIds);
                    $query = "id= '".$sel_group."'";
                    $group_obj->updateGroup($data,$query);
                }
                $this->_helper->FlashMessenger('Permissions updated Successfully.');
            }
            //////for groups
            if($sel_group != '')
            {
                $grouppage = $group_obj->getGroup($sel_group);
                if($grouppage != '')
                    $this->_view->menuGpSel = explode ("|",$grouppage[0]->menuId);
            }
            /////for users
            if($sel_user != '')
            {
                $userpage = $user_obj->getUserMenus($sel_user);
                if($userpage != '')
                    $this->_view->menuUsrSel = explode ("|",$userpage[0]->menuId);
            }

        }
        $this->_view->menuList = $allMenus;
        $this->render('user_menu_permissions');
    }
    /////////////////////display pop up with users list in dashboard user stats///////////////////
    public function dashboarduserspopupAction()
    {
        $userobj = new Ep_User_User();
        if($this->_request->getParam('usertype') == 5)
        {
            $this->_view->usernames = $userobj->getBoEditorNames();
            $this->_view->usertype = "Editors";
        }
        else if($this->_request->getParam('usertype') == 4)
        {
            $this->_view->usernames = $userobj->getBoChiefEditorNames();
            $this->_view->usertype = "Chief Editors";
        }
        else if($this->_request->getParam('usertype') == 6)
        {
            $this->_view->usernames = $userobj->getBoSeoNames();
            $this->_view->usertype = "Seo Team";
        }
        else if($this->_request->getParam('usertype') == 2)
        {
            $this->_view->usernames = $userobj->getBoCeoNames();
            $this->_view->usertype = "Ceo Team";
        }
        else if($this->_request->getParam('usertype') == 8)
        {
            $this->_view->usernames = $userobj->getBoPartnerNames();
            $this->_view->usertype = "Partners";
        }
        else if($this->_request->getParam('usertype') == 3)
        {
            $this->_view->usernames = $userobj->getBoSalesNames();
            $this->_view->usertype = "Sales Team";
        }
        else if($this->_request->getParam('usertype') == 7)
        {
            $this->_view->usernames = $userobj->getBoCustormercareNames();
            $this->_view->usertype = "Customer Care Team";
        }
        else if($this->_request->getParam('usertype') == 9)
        {
            $this->_view->usernames = $userobj->getBoFacturationNames();
            $this->_view->usertype = "Facturation Team";
        }

            $this->_view->render("user_userinfopopup");

    }
    /* Listing all contributors */
    public function contributorsoldAction ()
    {
        $contributorobj = new Ep_User_Contributor();   
        $this->_view->contributorsList   = $contributorobj->ListContributorsinfo() ;

        $this->_view->render("contributors");
    }
    /* Listing all contributors */
    public function clientsoldAction ()
    {
        $clientobj = new Ep_User_Client();
        //echo '<pre>'; print_r($clientobj->ListClientsinfo()); exit;
        $this->_view->clientsList   = $clientobj->ListClientsinfo() ;

        $this->_view->render("clients");
    }
    /* Upload Profile Photo*/
    public function uploadprofilepicAction()
    {

        error_reporting(E_ERROR | E_PARSE);
        $path=pathinfo($_FILES['uploadpic']['name']);
        $uploadpicname=$_FILES['uploadpic']['name'];
        $ext="jpg";//$path['extension'];//$this->findexts($uploadpicname);

        $contrib_identifier= $_REQUEST['userid'];
        $app_path="/home/sites/site7/web/FO/";
        $profiledir='profiles/bo/'.$contrib_identifier.'/';
        $uploadpicdir = $app_path.$profiledir;
        if(!is_dir($uploadpicdir))
            mkdir($uploadpicdir,TRUE);
        chmod($uploadpicdir,0777);
        $contrib_picture=$uploadpicdir.$contrib_identifier.".".$ext;
        $contrib_picture_home= $uploadpicdir.$contrib_identifier."_h.".$ext;
        $contrib_picture_profile= $uploadpicdir.$contrib_identifier."_p.".$ext;
        $contrib_picture_offer= $uploadpicdir.$contrib_identifier."_ao.".$ext;
        $contrib_picture_crop= $uploadpicdir.$contrib_identifier."_crop.".$ext;
        list($width, $height)  = getimagesize($_FILES['uploadpic']['tmp_name']);
        if($width>=90 && $height>=90)
        {
            if (move_uploaded_file($_FILES['uploadpic']['tmp_name'], $contrib_picture))
            {
                chmod($contrib_picture,0777);
                /*Image for cropping**/
                $newimage_crop= new Ep_User_Image();
                $newimage_crop->load($contrib_picture);
                list($width, $height) = getimagesize($contrib_picture);
                if($width>400)
                    $newimage_crop->resizeToWidth(400);
                elseif($height>600)
                    $newimage_crop->resizeToHeight(600);
                else
                    $newimage_crop->resize($width,$height);
                $newimage_crop->save($contrib_picture_crop);
                chmod($contrib_picture_crop,0777);
                $array=array("status"=>"success","identifier"=>$contrib_identifier,"path"=>$profiledir,"ext"=>$ext);
                echo json_encode($array);
                //echo "success";
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
    /* Cropping Profile images**/
    public function cropprofilepicAction()
    {
        if($this->_request-> isPost())
        {
            $image_params=$this->_request->getParams();
            $function=$image_params['function'];
            $new_x=$image_params['x'];
            $new_y=$image_params['y'];
            $post_width=$image_params['w'];
            $post_height=$image_params['h'];

            $contrib_identifier= $image_params['userId'];
            $ext="jpg";

            $app_path="/home/sites/site7/web/FO/";
            $profiledir='profiles/bo/'.$contrib_identifier.'/';
            $uploadpicdir = $app_path.$profiledir;
            $contrib_picture_home= $uploadpicdir.$contrib_identifier."_h.".$ext;
            $contrib_picture_profile= $uploadpicdir.$contrib_identifier."_p.".$ext;
            $contrib_picture_offer= $uploadpicdir.$contrib_identifier."_ao.".$ext;
            $contrib_picture=$uploadpicdir.$contrib_identifier.".".$ext;
            $contrib_picture_crop= $uploadpicdir.$contrib_identifier."_crop.".$ext;
            $contrib_picture_logo = $uploadpicdir."/logo.jpg";
            if($function=="saveimage")
            {
                /* Contrib home image with 60x60**/
                $newimage_h= new Ep_User_Image();
                $newimage_h->load($contrib_picture_crop);
                $newimage_h->cropImage($new_x,$new_y,60,60,$post_width,$post_height);
                $newimage_h->save($contrib_picture_home);
                // chmod($contrib_picture_home,777);
                unset($newimage_h);
                /* Contrib Profile image with 90x90**/
                $newimage_p= new Ep_User_Image();
                $newimage_p->load($contrib_picture_crop);
                $newimage_p->cropImage($new_x,$new_y,90,90,$post_width,$post_height);
                $newimage_p->save($contrib_picture_profile);
                //chmod($contrib_picture_profile,777);
                unset($newimage_p);
                /* Contrib Profile image with 90x90**/

                $newimage_l= new Ep_User_Image();
                $newimage_l->load($contrib_picture_crop);
                $newimage_l->cropImage($new_x,$new_y,90,90,$post_width,$post_height);
                $newimage_l->save($contrib_picture_logo);
                //chmod($contrib_picture_profile,777);
                unset($newimage_l);
                /* Contrib Profile image with width 90**/
                $newimage_p= new Ep_User_Image();
                $newimage_p->load($contrib_picture_crop);
                list($width, $height) = getimagesize($contrib_picture_crop);
                $ao_image_height=(($height/$width)*90);
                $newimage_p->cropImage($new_x,$new_y,90,$ao_image_height,$post_width,$post_height);
                $newimage_p->save($contrib_picture_offer);
                //chmod($contrib_picture_offer,777);
                unset($newimage_p);
            }
            elseif($function=="original")
            {
                /* Contrib home image with 60x60**/
                $newimage_h= new Ep_User_Image();
                $newimage_h->load($contrib_picture);
                $newimage_h->resize(60,60);
                $newimage_h->save($contrib_picture_home);
                //chmod($contrib_picture_home,0777);
                unset($newimage_h);
                /*Contrib Profile image with 90x90**/
                $newimage_p= new Ep_User_Image();
                $newimage_p->load($contrib_picture);
                $newimage_p->resize(90,90);
                $newimage_p->save($contrib_picture_profile);
                // chmod($contrib_picture_profile,0777);
                unset($newimage_h);
                $newimage_p= new Ep_User_Image();
                $newimage_p->load($contrib_picture);
                list($width, $height) = getimagesize($contrib_picture);
                $ao_image_height=(($height/$width)*90);
                $newimage_p->resize(90,$ao_image_height);
                $newimage_p->save($contrib_picture_offer);
                // chmod($contrib_picture_offer,0777);
                unset($newimage_p);
            }
            /* Unlink the Original file**/
            if(file_exists($contrib_picture) && !is_dir($contrib_picture))
                unlink($contrib_picture);
            $array=array("identifier"=>$contrib_identifier,"path"=>$profiledir,"ext"=>$ext);
            echo json_encode($array);
        }
    }
    public function userHistoryAction()
    {
        //echo "manage";  exit;
        $user_obj=new Ep_User_User();
        $user_details=$user_obj->getUserdetails($_GET['user']);
        $eduarray=array("1" => "Bac +1","2" => "Bac +2","3" => "Bac +3","4" => "Bac +4","5" => "Bac +5","6" => "Bac+5 et plus");
        $favourite_category="";
        if($user_details[0]['favourite_category']!=NULL)
        {
            $fav_cat=explode(",",$user_details[0]['favourite_category']);

            if(count($fav_cat)>0)
            {
                for($l=0;$l<count($fav_cat);$l++)
                {
                    $favourite_category.=utf8_encode($this->category_array[$fav_cat[$l]]);

                    if($l!=count($fav_cat)-1)
                        $favourite_category.=",";
                }
            }
        }
        $this->_view->user_detail=$user_details;
        $this->_view->favourite_category=$favourite_category;
        $this->_view->language=$this->language_array[$user_details[0]['language']];
        $this->_view->profession=$this->profession_array[$user_details[0]['profession']];
        $eduarray=array("1" => "Bac +1","2" => "Bac +2","3" => "Bac +3","4" => "Bac +4","5" => "Bac +5","6" => "Bac+5 et plus");
        $this->_view->education=$eduarray[$user_details[0]['education']];
        //Participation details
        $parti_details=$user_obj->getContribPartinfo($_GET['user']);
        $this->_view->parti_detail=$parti_details;
        $this->_view->render("user_userhistory");
    }
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
    /**function to get the category name**/
    public function getCategoryName($category_value)
    {
        $category_name='';
        $categories=explode(",",$category_value);
        $categories_array=$this->_arrayDb->loadArrayv2("EP_ARTICLE_CATEGORY", $this->_lang);
        $cnt=0;
        foreach($categories as $category)
        {
            if($cnt==4)
                break;
            $category_name.=$categories_array[$category].", ";
            $cnt++;
        }
        $category_name=substr($category_name,0,-2);
        return $category_name;
    }

    ///////to display the search page in stats contributor/////////
    public function searchstatscontributorsAction()
    {
        //echo $prevurl = getenv("HTTP_REFERER"); echo $_GET['searchsubmit']; echo $this->_request->getParam('searchsubmit');
        $userdetails=new Ep_User_UserPlus();
        $user_obj = new Ep_User_User();
        $statscontrib_params=$this->_request->getParams();
        $this->_view->start_date = $this->_request->getParam('start_date');
        $this->_view->end_date = $this->_request->getParam('end_date');
        $this->_view->aolist = $this->_request->getParam('aoList');
        $this->_view->arttitle = $this->_request->getParam('arttitle');
        $this->_view->contrib = $this->_request->getParam('contrib');
        $this->_view->type = $this->_request->getParam('type');
        $this->_view->status = $this->_request->getParam('status');
        $this->_view->blacklist = $this->_request->getParam('blacklist');
        $this->_view->nationalism = $this->_request->getParam('nationalism');
        $this->_view->age = $this->_request->getParam('age');
        $this->_view->category = $_REQUEST['category'];
        $this->_view->language = $this->_request->getParam('language');
        $this->_view->minage = $this->_request->getParam('minage');
        $this->_view->maxage = $this->_request->getParam('maxage');
        $this->render('stats_searchstatscontributors');
    }
    ///////////////saving search forms/////////////////
    public function savesearchAction()
    {
        $params=$this->_request->getParams();
        $user_obj = new Ep_User_User();
        $userplus_obj=new Ep_User_UserPlus();
        if(isset($params['compare']) && $params['compare']=="yes")
        {
            $contribIds = $params['contribids'];
            $where= " AND u.identifier IN (".$contribIds.")";
            // $this->_view->compareContribs = $user_obj->getSearchedContributorsList($where);
            $contribIds = $params['contribids'];
            $contribIds = explode(",",$contribIds);
            for($i=0; $i<count($contribIds); $i++)
            {
                $compareContribs[] = $userplus_obj->getCompareContributors($contribIds[$i]);
            }

            //print_r($compareContribs);
            //$maxs = array_keys($compareContribs, max($compareContribs[0]['3']['no_paritcipations']));

            $category=$this->_arrayDb->loadArrayv2("EP_ARTICLE_CATEGORY", $this->_lang);
            $nationality=$this->_arrayDb->loadArrayv2("Nationality", $this->_lang);
            $language=$this->_arrayDb->loadArrayv2("EP_LANGUAGES", $this->_lang);
            $this->_view->compareContribs = $compareContribs;
            $this->_view->category = $category;
            $this->_view->country = $nationality;
            $this->_view->language = $language;
            $this->render('stats_comparecontribs');   exit;

        }
        if(isset($params['emailcontribs']))
        {
            /* $contribids = $params['contribcheck'];
             for($i=0; $i<count($contribids); $i++)
             {
                 //$userdetails[] = $user_obj->getAllUsersDetails($contribIds[$i]);
                 $contribIds.= "&usercontacts[]=".$contribids[$i];
             }

             $this->_redirect("https://admin-test.edit-place.co.uk/mails/newsletter?submenuId=ML4-SL10&selectgroup=contributor".$contribIds);*/
            $contribids = $params['userchecks'];


            $this->_redirect("http://admin-test.edit-place.co.uk/mails/newsletter?submenuId=ML4-SL10&selectgroup=contributor&".$contribids);
        }

        else
        {
            $prevurl = getenv("HTTP_REFERER");
            $savesearch_obj = new Ep_User_SaveSearch();
            $savesearch_obj->user_id=$this->adminLogin->userId ;
            $savesearch_obj->search_name = $params['searchname'] ;
            $savesearch_obj->url=$prevurl ;
            $savesearch_obj->insert();
            $this->_redirect($prevurl);
        }
    }
    ///////////////delete search ////////////////
    public function deletesearchAction()
    {
        $params=$this->_request->getParams();
        $prevurl = getenv("HTTP_REFERER");
        $savesearch_obj = new Ep_User_SaveSearch();
        $savesearch_obj->user_id=$this->adminLogin->userId ;
        $data = array("active"=>"no");////////updating
        $query = "id= '".$params['searchId']."'";
        $savesearch_obj->updateSaveSearch($data,$query);
        $this->_redirect($prevurl);
    }
    ///////////////active or inactive the users////////////////
    public function changeuserstatusAction()
    {
        $params=$this->_request->getParams();
        $user_obj = new Ep_User_User();
        $data = array("status"=>$params['status'], "changestatus_by"=>$this->adminLogin->userId, "changestatus_at"=>date("Y-m-d H:i:s"));
        $query = "identifier= '".$params['user_id']."'";
        $user_obj->updateUser($data,$query);

    }
    public function usersListAction()
    {
        $userdetails=new Ep_User_UserPlus();
        $user_obj = new Ep_User_User();
        $usergrp_obj=new Ep_User_UserGroupAccess();
        $groups =  $usergrp_obj->getAllUserGroupNames();


		/* * download XLS file***/
		$contrib_params=$this->_request->getParams();
		 if(isset($contrib_params['download']) && $contrib_params['download']!='')
		 {
			//echo $_SERVER['QUERY_STRING'];exit;
			$condition['searchsubmit'] = $contrib_params['searchsubmit'];
			$condition['start_date'] = $contrib_params['start_date'];
			$condition['end_date'] = $contrib_params['end_date'];
			$condition['act_start_date'] = $contrib_params['activity_start_date'];
			$condition['act_end_date'] = $contrib_params['activity_end_date'];
			$condition['aolist'] = $contrib_params['aoList'];
			$condition['arttitle'] = $contrib_params['arttitle'];
			$condition['contrib'] = $contrib_params['contrib'];
			$condition['type'] = $contrib_params['type'];
			$condition['type2'] = $contrib_params['type2'];
			$condition['status'] = $contrib_params['status'];
			$condition['blacklist'] = $contrib_params['blacklist'];
			$condition['nationalism'] = $contrib_params['nationalism'];
			$condition['category'] = $contrib_params['categ'];
			$condition['language'] = $contrib_params['language'];
			$condition['language2'] = $contrib_params['language2'];
			$condition['aotitle'] = $contrib_params['aotitle'];
			$condition['minage'] = $contrib_params['minage'];
			$condition['maxage'] = $contrib_params['maxage'];
			$condition['minartsvalid'] = $contrib_params['min_arts_validated'];
			$condition['maxartsvalid'] = $contrib_params['max_arts_validated'];
			$condition['mintotalparts'] = $contrib_params['min_total_parts'];
			$condition['maxtotalparts'] = $contrib_params['max_total_parts'];
			$condition['minartssent'] = $contrib_params['min_arts_sent'];
			$condition['maxartssent'] = $contrib_params['max_arts_sent'];
			$condition['minpartsrefused'] = $contrib_params['min_parts_refused'];
			$condition['maxpartsrefused'] = $contrib_params['max_parts_refused'];
			$condition['minartsrefused'] = $contrib_params['min_arts_refused'];
			$condition['maxartsrefused'] = $contrib_params['max_arts_refused'];
			$condition['noofdisapproved'] = $contrib_params['noof_disapproved'];

			if($contrib_params['total_contribs'] == 'yes')
				$condition['total_contribs'] = $contrib_params['total_contribs'];
			if($contrib_params['never_participated'] == 'yes')
				$condition['never_participated'] = $contrib_params['never_participated'];
			if($contrib_params['never_sent'] == 'yes')
				$condition['never_sent'] = $contrib_params['never_sent'];
			if($contrib_params['never_validated'] == 'yes')
				$condition['never_validated'] = $contrib_params['never_validated'];
			if($contrib_params['once_validated'] == 'yes')
				$condition['once_validated'] = $contrib_params['once_validated'];
			if($contrib_params['once_published'] == 'yes')
				$condition['once_published'] = $contrib_params['once_published'];


			/////total count
			$sLimit1 = "";
			$contributors = $user_obj->loadContributor($sWhere, $sOrder, $sLimit1, $condition);
			//echo count($contributors );exit;
			//echo "<pre>";print_r($contributors);exit;
			$category=$this->_arrayDb->loadArrayv2("EP_ARTICLE_CATEGORY", $this->_lang);
			$nationality=$this->_arrayDb->loadArrayv2("Nationality", $this->_lang);
			$language=$this->_arrayDb->loadArrayv2("EP_LANGUAGES", $this->_lang);
			$profession=$this->_arrayDb->loadArrayv2("CONTRIB_PROFESSION", $this->_lang);
			//$contributors= $user_obj->getSearchedContributorsList($where);

			//print_r($contributors); exit;
			 $file = 'excelFile-'.date("Y-M-D")."-".time().'.xls';
			 ob_start();
             $content= '<table border="1"> ';
             $content.= '<tr><th>Contributor Name</th><th>Email</th><th>Initial</th><th>Status</th>';
             $content.= '<th>Date of Join</th><th>Profile Type</th><th>Black Status</th><th>City</th><th>State</th>';
             $content.= '<th>Country</th><th>Pin code</th>';
            // $content.= '<th>Phone Number</th><th>DoB</th><th>University</th><th>Profession</th><th>Language</th><th>More Language</th><th>Category</th>';
            // $content.= '<th>Description</th>';
             $content.= '</tr>';
			 for($i=0; $i<count($contributors); $i++)
			 {
                 if($contributors[$i]["language_more"] != '' && $contributors[$i]["language_more"] != 'N')
                 {
                     $lang_list = array();
                     if($contributors[$i]["language_more"] != ''){
                         $laninfo=unserialize($contributors[$i]['language_more']) ;
                         if($laninfo != '')
                         {
                             foreach($laninfo as $key1 => $value1)
                             {
                                 $lang_list[]=$this->language_array[$key1].( ($value1 > 0) ? ('(' . $value1 . '%)') : '' ) ;
                             }
                         }
                         $row=implode(',',$lang_list);
                     }
                     else
                         $row="-";
                 }
                 if($contributors[$i]["category_more"] != '')
                 {
                     $cat_list = array();
                     if($contributors[$i]['category_more'] != ''){
                         $catinfo=unserialize($contributors[$i]['category_more']) ;
                         if($catinfo != '')
                         {
                             foreach ($catinfo as $key2 => $value2)
                             {
                                 $cat_list[]=$this->category_array[$key2].( ($value2 > 0) ? ('(' . $value2 . '%)') : '' ) ;
                             }
                         }
                         $crow=implode(',',$cat_list);
                     }
                     else
                         $crow="-";


                 }
                 $contributors[$i]["category_more"] =  $crow;
                 $contributors[$i]["language_more"] =  $row;
                 //if($contributors[$i]["first_name"] == '') { $name =  $contributors[$i]["email"]; } else { $name =  $contributors[$i]["first_name"]; }
                 $content.= '<tr><td>'.$contributors[$i]["full_name"].'</td><td>'.$contributors[$i]["email"].'</td><td>'.$contributors[$i]["initial"].'</td>';
                 $content.= '<td>'.$contributors[$i]["status"].'</td><td>'.date("d-m-Y", strtotime($contributors[$i]["created_at"])).'</td>';
                 $content.= '<td>'.$contributors[$i]["profile_type"].'</td><td>'.$contributors[$i]["blackstatus"].'</td>';
                 $content.= '<td>'.$contributors[$i]["city"].'</td><td>'.$contributors[$i]["state"].'</td><td>'.$nationality[$contributors[$i]["country"]].'</td><td>'.$contributors[$i]["zipcode"].'</td>';
                // $content.= '<td>'.$contributors[$i]["phone_number"].'</td><td>'.$contributors[$i]["dob"].'</td><td>'.$contributors[$i]["university"].'</td>';
                // $content.= '<td>'.$profession[$contributors[$i]["profession"]].'</td><td>'.$language[$contributors[$i]["language"]].'</td><td>'.$contributors[$i]["language_more"].'</td><td>'.$contributors[$i]["category_more"].'</td>';

                 $breaks = array("<br />","<br>","<br/>");
                 $contributors[$i]["self_details"] = str_ireplace($breaks, "\r\n", $contributors[$i]["self_details"]);
                 $content.= '<td>'.$contributors[$i]["self_details"].'</td></tr>';
			 }
			 $content.='</table>'; //echo $content;exit;  
			 //header("Content-Disposition: attachment; filename=hello");
			// header("Content-Type: application/vnd.ms-excel");

			// $content = ob_get_contents($content);  
			// $this->adminLogin->content = $content;
			//session_start();
			// $_SESSION['content']="hello";
			// header("Location:/BO/download_users_xls.php?zill=".$content);
			 

			  ob_end_clean();
			 header("Expires: 0");
			 header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
			 header("Cache-Control: no-store, no-cache, must-revalidate");
			 header("Cache-Control: post-check=0, pre-check=0", false);
			 header("Pragma: no-cache");
			 header("Content-type: application/vnd.ms-excel;charset:UTF-8");
			 header('Content-length: '.strlen($content));
			 header('Content-disposition: attachment; filename='.basename($file));
			 echo $content; 
			 exit;

		 }






        $this->_view->contribCount=$userdetails->getStatsContributorsCount();
        foreach($groups as $key=>$value)
        {
            $usergroups[$value['groupName']]=strtoupper($value['groupName']);
        }
        $this->_view->usergroups=$usergroups;
        $userId = $this->_request->getParam('userId');
        $user_obj = new Ep_User_User();

        $details= $userdetails->getUsersDetails();
        $var = 0;
        foreach ($details as $details1) {
            $details[$var]['country'] = $this->country_array[$details1['country']];
            $var++;
        }
        $this->_view->bouserdetails=$details;

        $savesearch_obj = new Ep_User_SaveSearch();
        if($this->adminLogin->userId != '')
            $this->_view->savedSearchesUrls =$savesearch_obj->getSearchedUrls($this->adminLogin->userId);
        $quiz_obj = new Ep_Quizz_quizz();
        $quizlist = $quiz_obj->ListQuizz();
        foreach($quizlist as $key=>$value)
        {
            $quiz_list[$value['id']]=$value['title'];
        }
        $this->_view->quizlist=$quiz_list;

        //////////////permissions data to dispay permissions grid ////////////////////
        $p = new Ep_Controller_Page($this->pageFile);
        ////getting groups///////
        $group_obj = new Ep_User_UserGroupAccess();
        $groups = $group_obj->getAllGroups();
        $this->_view->groupList = $groups;
        ////getting users////////
        $user_obj = new Ep_User_User();
        $users = $user_obj->getUsers();
        $this->_view->userList = $users;
        /////////////////////////////////////
        //if($this->_request-> isPost())
        if($this->_request->getParam('tab') == 'permissionstab')
        {
            $sel_group = $this->_request->getParam('sel_group');
            $sel_user = $this->_request->getParam('sel_user');
            $this->_view->GpSel=$sel_group;
            $this->_view->UsrSel=$sel_user;
            $total = $this->_request->getParam('hid_totalrows');///no of pages///

            if($this->_request->getParam('assign') == 'assign')
            {
                for( $i=1 ; $i<=$total; $i++)
                {
                    //chk_.$i = $this->_request->getParam('chk_',$i);
                    if($this->_request->getParam('chk_'.$i) == 'chk_'.$i)
                    {
                        $chkPages[] = $this->_request->getParam('chk_',$i);
                        $chkPagesIds[] = $i;
                    }
                }
                $chkedpageIds = implode ("|",$chkPagesIds);
                if($sel_user!='0')
                {
                    $data = array("pageId"=>$chkedpageIds);////////updating
                    $query = "identifier= '".$sel_user."'";
                    $user_obj->updateUser($data,$query);
                }
                else if($sel_group!='0')
                {
                    $data = array("pageId"=>$chkedpageIds);
                    $query = "id= '".$sel_group."'";
                    $group_obj->updateGroup($data,$query);
                }
                $this->_helper->FlashMessenger('Permissions updated Successfully.');
            }
            //////for groups
            if($sel_group != '')
            {
                $grouppage = $group_obj->getGroup($sel_group);
                $this->_view->pageGpSel = explode ("|",$grouppage[0]->pageId);
            }
            /////for users
            if($sel_user != '')
            {
                $userpage = $user_obj->getUserPages($sel_user);
                $this->_view->pageUsrSel = explode ("|",$userpage[0]->pageId);
            }

            //  $this->_redirect("/user/users-list?submenuId=ML3-SL6&tab=permissionstab");
        }
        $pageList = $p->selectAllPagesBySegment(1);
        $this->_view->pageList = $pageList;

        $this->render('user_userslist');
    }
    public function searchContributorsAction()
    {
        $userdetails=new Ep_User_UserPlus();
        $user_obj = new Ep_User_User();
        $usergrp_obj=new Ep_User_UserGroupAccess();
        /* * download XLS file***/
        $contrib_params=$this->_request->getParams();
        if(isset($contrib_params['download']) && $contrib_params['download']!='')
        {
            foreach($contrib_params['categ'] as $key => $value)
            {
                $categ[]=  $key."=".$value;
            }
            foreach($contrib_params['lange'] as $key => $value)
            {
                $lange[]=  $key."=".$value;
            }

            $condition['searchsubmit'] = $contrib_params['searchsubmit'];
            $condition['start_date'] = $contrib_params['start_date'];
            $condition['end_date'] = $contrib_params['end_date'];
            $condition['act_start_date'] = $contrib_params['activity_start_date'];
            $condition['act_end_date'] = $contrib_params['activity_end_date'];
            $condition['aolist'] = $contrib_params['aoList'];
            $condition['arttitle'] = $contrib_params['arttitle'];
            $condition['contrib'] = $contrib_params['contrib'];
            $condition['type'] = $contrib_params['type'];
            $condition['type2'] = $contrib_params['type2'];
            $condition['status'] = $contrib_params['status'];
            $condition['blacklist'] = $contrib_params['blacklist'];
            $condition['nationalism'] = $contrib_params['nationalism'];
            $condition['category'] = $contrib_params['category'];
            $condition['categ'] = $categ;
            $condition['language'] = $contrib_params['language'];
            $condition['lange'] = $lange;
            $condition['aotitle'] = $contrib_params['aotitle'];
            $condition['minage'] = $contrib_params['minage'];
            $condition['maxage'] = $contrib_params['maxage'];
            $condition['minartsvalid'] = $contrib_params['min_arts_validated'];
            $condition['maxartsvalid'] = $contrib_params['max_arts_validated'];
            $condition['mintotalparts'] = $contrib_params['min_total_parts'];
            $condition['maxtotalparts'] = $contrib_params['max_total_parts'];
            $condition['minartssent'] = $contrib_params['min_arts_sent'];
            $condition['maxartssent'] = $contrib_params['max_arts_sent'];
            $condition['minpartsrefused'] = $contrib_params['min_parts_refused'];
            $condition['maxpartsrefused'] = $contrib_params['max_parts_refused'];
            $condition['minartsrefused'] = $contrib_params['min_arts_refused'];
            $condition['maxartsrefused'] = $contrib_params['max_arts_refused'];
            $condition['noofdisapproved'] = $contrib_params['noof_disapproved'];
            $condition['selfdetails'] = trim(urldecode($contrib_params['contrib_self_details']));
            $condition['contributortest'] = $contrib_params['contributortest'];


            if($contrib_params['total_contribs'] == 'yes')
                $condition['total_contribs'] = $contrib_params['total_contribs'];
            if($contrib_params['never_participated'] == 'yes')
                $condition['never_participated'] = $contrib_params['never_participated'];
            if($contrib_params['never_sent'] == 'yes')
                $condition['never_sent'] = $contrib_params['never_sent'];
            if($contrib_params['never_validated'] == 'yes')
                $condition['never_validated'] = $contrib_params['never_validated'];
            if($contrib_params['once_validated'] == 'yes')
                $condition['once_validated'] = $contrib_params['once_validated'];
            if($contrib_params['once_published'] == 'yes')
                $condition['once_published'] = $contrib_params['once_published'];

            $sLimit1 = "";
            $contributors = $user_obj->loadContributor($sWhere=null, $sOrder=null, $sLimit1, $condition);

            $category=$this->_arrayDb->loadArrayv2("EP_ARTICLE_CATEGORY", $this->_lang);
            $nationality=$this->_arrayDb->loadArrayv2("Nationality", $this->_lang);
            $language=$this->_arrayDb->loadArrayv2("EP_LANGUAGES", $this->_lang);
            $profession=$this->_arrayDb->loadArrayv2("CONTRIB_PROFESSION", $this->_lang);
           // if($nationality)
              //  array_unshift($nationality, "ALL");
			///print_r($contributors); exit;
            $file = 'excelFile-'.date("Y-M-D")."-".time().'.xls';
             ob_start();
             $content= '<table border="1"> ';
             $content.= '<tr><th>Contributor Name</th><th>Email</th><th>Initial</th><th>Status</th>';
             $content.= '<th>Date of Join</th><th>Profile Type</th><th>Black Status</th><th>City</th><th>State</th>';
             $content.= '<th>Country</th><th>Pin code</th>';
             $content.= '<th>Phone Number</th><th>DoB</th><th>University</th><th>Profession</th><th>Language</th><th>More Language</th><th>Category</th>';
             $content.= '<th>Description</th>';
             $content.= '</tr>';
			 for($i=0; $i<count($contributors); $i++)
			 {
                 if($contributors[$i]["language_more"] != '' && $contributors[$i]["language_more"] != 'N')
                 {
                     $lang_list = array();
                     if($contributors[$i]["language_more"] != ''){
                         $laninfo=unserialize($contributors[$i]['language_more']) ;
                         if($laninfo != '')
                         {
                             foreach($laninfo as $key1 => $value1)
                             {
                                 $lang_list[]=$this->language_array[$key1].( ($value1 > 0) ? ('(' . $value1 . '%)') : '' ) ;
                             }
                         }
                         $row=implode(',',$lang_list);
                     }
                     else
                         $row="-";
                 }
                 if($contributors[$i]["category_more"] != '')
                 {
                     $cat_list = array();
                     if($contributors[$i]['category_more'] != ''){
                         $catinfo=unserialize($contributors[$i]['category_more']) ;
                         if($catinfo != '')
                         {
                             foreach ($catinfo as $key2 => $value2)
                             {
                                 $cat_list[]=$this->category_array[$key2].( ($value2 > 0) ? ('(' . $value2 . '%)') : '' ) ;
                             }
                         }
                         $crow=implode(',',$cat_list);
                     }
                     else
                         $crow="-";


                 }
                 $contributors[$i]["category_more"] =  $crow;
                 $contributors[$i]["language_more"] =  $row;
                 //if($contributors[$i]["first_name"] == '') { $name =  $contributors[$i]["email"]; } else { $name =  $contributors[$i]["first_name"]; }
                 $content.= '<tr><td>'.$contributors[$i]["full_name"].'</td><td>'.$contributors[$i]["email"].'</td><td>'.$contributors[$i]["initial"].'</td>';
                 $content.= '<td>'.$contributors[$i]["status"].'</td><td>'.date("d-m-Y", strtotime($contributors[$i]["created_at"])).'</td>';
                 $content.= '<td>'.$contributors[$i]["profile_type"].'</td><td>'.$contributors[$i]["blackstatus"].'</td>';
                 $content.= '<td>'.$contributors[$i]["city"].'</td><td>'.$contributors[$i]["state"].'</td><td>'.$nationality[$contributors[$i]["country"]].'</td><td>'.$contributors[$i]["zipcode"].'</td>';
                 $content.= '<td>'.$contributors[$i]["phone_number"].'</td><td>'.$contributors[$i]["dob"].'</td><td>'.$contributors[$i]["university"].'</td>';
                 $content.= '<td>'.$profession[$contributors[$i]["profession"]].'</td><td>'.$language[$contributors[$i]["language"]].'</td><td>'.$contributors[$i]["language_more"].'</td><td>'.$contributors[$i]["category_more"].'</td>';

                 $breaks = array("<br />","<br>","<br/>");
                 $contributors[$i]["self_details"] = str_ireplace($breaks, "\r\n", $contributors[$i]["self_details"]);
                 $content.= '<td>'.$contributors[$i]["self_details"].'</td></tr>';
			 }
            $content.='</table>'; //echo $content; exit;
             

			 $content = ob_get_contents();
			 $_SESSION['content']=$content;
			 header("Location:/BO/download_users_xls.php");
			 ob_end_clean();
			 //echo $content;exit;
			/* header("Content-Disposition: attachment; filename=\"$filetable\"");
			 header("Content-Type: application/vnd.ms-excel");
			 ob_end_clean();
			 header("Expires: 0");
			 header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
			 header("Cache-Control: no-store, no-cache, must-revalidate");
			 header("Cache-Control: post-check=0, pre-check=0", false);
			 header("Pragma: no-cache");
			 header("Content-type: application/vnd.ms-excel;charset:UTF-8");
			 header('Content-length: '.strlen($content));
			 header('Content-disposition: attachment; filename='.basename($file));
			 echo $content; */
			 exit;
        }
        $savesearch_obj = new Ep_User_SaveSearch();
        if($this->adminLogin->userId != '')
            $this->_view->savedSearchesUrls =$savesearch_obj->getSearchedUrls($this->adminLogin->userId);
        $quiz_obj = new Ep_Quizz_quizz();
        $quizlist = $quiz_obj->ListQuizz();
        foreach($quizlist as $key=>$value)
        {
            $quiz_list[$value['id']]=$value['title'];
        }
        $this->_view->quizlist=$quiz_list;
        $this->_view->contribCount=$userdetails->getStatsContributorsCount();
        $this->render('user_searchcontributors');
    }
    public function boUsersAction()
    {
        $userdetails=new Ep_User_UserPlus();
        $user_obj = new Ep_User_User();
        $usergrp_obj=new Ep_User_UserGroupAccess();
        $groups =  $usergrp_obj->getAllUserGroupNames();
        $details= $userdetails->getUsersDetails();
        $var = 0;
        foreach ($details as $details1) {
            $details[$var]['country'] = $this->country_array[$details1['country']];
            $var++;
        }
        $this->_view->bouserdetails=$details;

        $this->render('user_bousers');
    }
    public function contributorsAction()
    {
        $userdetails=new Ep_User_UserPlus();
        $user_obj = new Ep_User_User();
        $usergrp_obj=new Ep_User_UserGroupAccess();
        $groups =  $usergrp_obj->getAllUserGroupNames();
        /* * download XLS file***/
        $contrib_params=$this->_request->getParams();
        if(isset($contrib_params['download']) && $contrib_params['download']!='')
        {
            $condition['searchsubmit'] = $contrib_params['searchsubmit'];
            $condition['start_date'] = $contrib_params['start_date'];
            $condition['end_date'] = $contrib_params['end_date'];
            $condition['act_start_date'] = $contrib_params['activity_start_date'];
            $condition['act_end_date'] = $contrib_params['activity_end_date'];
            $condition['aolist'] = $contrib_params['aoList'];
            $condition['arttitle'] = $contrib_params['arttitle'];
            $condition['contrib'] = $contrib_params['contrib'];
            $condition['type'] = $contrib_params['type'];
            $condition['type2'] = $contrib_params['type2'];
            $condition['status'] = $contrib_params['status'];
            $condition['blacklist'] = $contrib_params['blacklist'];
            $condition['nationalism'] = $contrib_params['nationalism'];
            $condition['category'] = $contrib_params['categ'];
            $condition['language'] = $contrib_params['language'];
            $condition['language2'] = $contrib_params['language2'];
            $condition['aotitle'] = $contrib_params['aotitle'];
            $condition['minage'] = $contrib_params['minage'];
            $condition['maxage'] = $contrib_params['maxage'];
            $condition['minartsvalid'] = $contrib_params['min_arts_validated'];
            $condition['maxartsvalid'] = $contrib_params['max_arts_validated'];
            $condition['mintotalparts'] = $contrib_params['min_total_parts'];
            $condition['maxtotalparts'] = $contrib_params['max_total_parts'];
            $condition['minartssent'] = $contrib_params['min_arts_sent'];
            $condition['maxartssent'] = $contrib_params['max_arts_sent'];
            $condition['minpartsrefused'] = $contrib_params['min_parts_refused'];
            $condition['maxpartsrefused'] = $contrib_params['max_parts_refused'];
            $condition['minartsrefused'] = $contrib_params['min_arts_refused'];
            $condition['maxartsrefused'] = $contrib_params['max_arts_refused'];
            $condition['noofdisapproved'] = $contrib_params['noof_disapproved'];
            $condition['selfdetails'] = trim(urldecode($contrib_params['contrib_self_details']));

            if($contrib_params['total_contribs'] == 'yes')
                $condition['total_contribs'] = $contrib_params['total_contribs'];
            if($contrib_params['never_participated'] == 'yes')
                $condition['never_participated'] = $contrib_params['never_participated'];
            if($contrib_params['never_sent'] == 'yes')
                $condition['never_sent'] = $contrib_params['never_sent'];
            if($contrib_params['never_validated'] == 'yes')
                $condition['never_validated'] = $contrib_params['never_validated'];
            if($contrib_params['once_validated'] == 'yes')
                $condition['once_validated'] = $contrib_params['once_validated'];
            if($contrib_params['once_published'] == 'yes')
                $condition['once_published'] = $contrib_params['once_published'];

            $sLimit1 = "";
            $contributors = $user_obj->loadContributor($sWhere, $sOrder, $sLimit1, $condition);

            $category=$this->_arrayDb->loadArrayv2("EP_ARTICLE_CATEGORY", $this->_lang);
            $nationality=$this->_arrayDb->loadArrayv2("Nationality", $this->_lang);
            $language=$this->_arrayDb->loadArrayv2("EP_LANGUAGES", $this->_lang);
            $profession=$this->_arrayDb->loadArrayv2("CONTRIB_PROFESSION", $this->_lang);

            $file = 'excelFile-'.date("Y-M-D")."-".time().'.xls';
            ob_start();
            $content= '<table border="1"> ';
            $content.= '<tr><th>Contributor Name</th><th>Email</th><th>Initial</th><th>First Name</th><th>Last Name</th><th>Status</th>';
            $content.= '<th>Date of Join</th><th>Profile Type</th><th>Black Status</th><th>City</th><th>State</th>';
            $content.= '<th>Country</th><th>Pin code</th>';
            $content.= '<th>Phone Number</th><th>DoB</th><th>University</th><th>Profession</th><th>Language</th><th>Category</th>';
            $content.= '<th>Description</th>';
            $content.= '</tr>';
            for($i=0; $i<count($contributors); $i++)
            {  if($contributors[$i]["first_name"] == '') { $name =  $contributors[$i]["email"]; } else { $name =  $contributors[$i]["first_name"]; }
                $content.= '<tr><td>'.$name.'</td><td>'.$contributors[$i]["email"].'</td><td>'.$contributors[$i]["initial"].'</td><td>'.$contributors[$i]["first_name"].'</td>';
                $content.= '<td>'.$contributors[$i]["last_name"].'</td><td>'.$contributors[$i]["status"].'</td><td>'.date("d-m-Y", strtotime($contributors[$i]["created_at"])).'</td>';
                $content.= '<td>'.$contributors[$i]["profile_type"].'</td><td>'.$contributors[$i]["blackstatus"].'</td>';
                $content.= '<td>'.$contributors[$i]["city"].'</td><td>'.$contributors[$i]["state"].'</td><td>'.$nationality[$contributors[$i]["country"]].'</td><td>'.$contributors[$i]["zipcode"].'</td>';
                $content.= '<td>'.$contributors[$i]["phone_number"].'</td><td>'.$contributors[$i]["dob"].'</td><td>'.$contributors[$i]["university"].'</td>';
                $content.= '<td>'.$profession[$contributors[$i]["profession"]].'</td><td>'.$language[$contributors[$i]["language"]].'</td><td>'.$category[$contributors[$i]["favourite_category"]].'</td>';

                $breaks = array("<br />","<br>","<br/>");
                $contributors[$i]["self_details"] = str_ireplace($breaks, "\r\n", $contributors[$i]["self_details"]);
                $content.= '<td>'.$contributors[$i]["self_details"].'</td></tr>';
            }
            $content.='</table>';
            $_SESSION['content']=$content;
            header("Location:/BO/download_users_xls.php");
            exit;
        }
        $sLimit1 = "";
        //$contributors = $user_obj->loadContributor($sWhere, $sOrder, $sLimit1, $condition);
        $this->_view->contribCount=$userdetails->getStatsContributorsCount();
        $this->render('user_contributors');
    }

    public function loadcontributorAction()
    {
        $user_obj = new Ep_User_User();
        $aColumns = array('identifier','full_name','email','profile_type',	'status','created_at','category_more','language','contributortest','actions');
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
                if($aColumns[$i] == 'status')
                    $aColumns[$i] = 'u.status';
                if($aColumns[$i] == 'created_at')
                    $aColumns[$i] = 'u.created_at';
                if($aColumns[$i] == 'actions')
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
        ///////////////////contributor details in search and normal grid display ////////
        $contrib_params=$this->_request->getParams();
        if($contrib_params['searchsubmit'] == 'Search')
        {
            $urlarr =  explode("&",urldecode($contrib_params['fullurl']));
            for($i=0; $i<count($urlarr); $i++)
            {
                if (preg_match('/categ/',$urlarr[$i]) && !preg_match('/category/',$urlarr[$i]))
                {
                    $urlarr[$i] = str_replace("categ[", "", $urlarr[$i]);
                    $urlarr[$i] = str_replace("]", "", $urlarr[$i]);
                    $categ[] = $urlarr[$i];
                }
                if (preg_match('/lange/',$urlarr[$i]))
                {
                    $urlarr[$i] = str_replace("lange[", "", $urlarr[$i]);
                    $urlarr[$i] = str_replace("]", "", $urlarr[$i]);
                    $lange[] = $urlarr[$i];
                }
            }
            $condition['searchsubmit'] = $contrib_params['searchsubmit'];
            $condition['start_date'] = $contrib_params['start_date'];
            $condition['end_date'] = $contrib_params['end_date'];
            $condition['act_start_date'] = $contrib_params['activity_start_date'];
            $condition['act_end_date'] = $contrib_params['activity_end_date'];
            $condition['aolist'] = $contrib_params['aoList'];
            $condition['arttitle'] = $contrib_params['arttitle'];
            $condition['contrib'] = $contrib_params['contrib'];
            $condition['type'] = $contrib_params['type'];
            $condition['type2'] = $contrib_params['type2'];
            $condition['status'] = $contrib_params['status'];
            $condition['blacklist'] = $contrib_params['blacklist'];
            $condition['nationalism'] = $contrib_params['nationalism'];
            $condition['category'] = $contrib_params['category'];
            $condition['categ'] = $categ;
            $condition['language'] = $contrib_params['language'];
            $condition['lange'] = $lange;
            $condition['aotitle'] = $contrib_params['aotitle'];
            $condition['minage'] = $contrib_params['minage'];
            $condition['maxage'] = $contrib_params['maxage'];
            $condition['minartsvalid'] = $contrib_params['min_arts_validated'];
            $condition['maxartsvalid'] = $contrib_params['max_arts_validated'];
            $condition['mintotalparts'] = $contrib_params['min_total_parts'];
            $condition['maxtotalparts'] = $contrib_params['max_total_parts'];
            $condition['minartssent'] = $contrib_params['min_arts_sent'];
            $condition['maxartssent'] = $contrib_params['max_arts_sent'];
            $condition['minpartsrefused'] = $contrib_params['min_parts_refused'];
            $condition['maxpartsrefused'] = $contrib_params['max_parts_refused'];
            $condition['minartsrefused'] = $contrib_params['min_arts_refused'];
            $condition['maxartsrefused'] = $contrib_params['max_arts_refused'];
            $condition['noofdisapproved'] = $contrib_params['noof_disapproved'];
             $selfdetails =  trim(urldecode($contrib_params['contrib_self_details']));
             $condition['selfdetails'] = utf8_encode($selfdetails);
             $condition['contributortest'] = $contrib_params['contributortest'];

                // $condition['selfdetails'] =  trim(urldecode($contrib_params['contrib_self_details']));
        }

        if($contrib_params['total_contribs'] == 'yes')
            $condition['total_contribs'] = $contrib_params['total_contribs'];
        if($contrib_params['never_participated'] == 'yes')
            $condition['never_participated'] = $contrib_params['never_participated'];
        if($contrib_params['never_sent'] == 'yes')
            $condition['never_sent'] = $contrib_params['never_sent'];
        if($contrib_params['never_validated'] == 'yes')
            $condition['never_validated'] = $contrib_params['never_validated'];
        if($contrib_params['once_validated'] == 'yes')
            $condition['once_validated'] = $contrib_params['once_validated'];
        if($contrib_params['once_published'] == 'yes')
            $condition['once_published'] = $contrib_params['once_published'];
        $rResult  = $user_obj->loadContributor($sWhere, $sOrder, $sLimit, $condition);
         $rResultcount = count($rResult);
        /////total count
        $sLimit = "";
        $countcontribs  = $user_obj->loadContributor($sWhere, $sOrder, $sLimit, $condition);
        $iTotal = count($countcontribs);

        $output = array(
            "sEcho" => intval($_GET['sEcho']),
            "iTotalRecords" => $iTotal,
            "iTotalDisplayRecords" => $iTotal,
            "aaData" => array()
        );
        $count = 1;
        if($rResult != 'NO')
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
                       if($aColumns[$j] == 'full_name')
                           $row[] = utf8_encode($rResult[$i]['full_name']);
                       elseif($aColumns[$j] == 'category_more')
                       {
                           $cat_list = array();
                           if($rResult[$i]['category_more'] != ''){
                               $catinfo=unserialize($rResult[$i]['category_more']) ;
                               if($catinfo != '')
                               {
                                   foreach ($catinfo as $key1 => $value1)
                                   {
                                       $cat_list[]=$this->category_array[$key1].( ($value1 > 0) ? ('(' . $value1 . '%)') : '' ) ;
                                   }
                               }
                               $row[]=utf8_encode(implode(',',$cat_list));
                           }
                           else
                               $row[]="-";
                       }
                       /*elseif($aColumns[$j] == 'language_more')
                       {
                           $lang_list = array();
                           if($rResult[$i]['language_more'] != ''){
                               $laninfo=unserialize($rResult[$i]['language_more']) ;
                               if($laninfo != '')
                               {
                                   foreach ($laninfo as $key1 => $value1)
                                   {
                                       $lang_list[]=$this->language_array[$key1].( ($value1 > 0) ? ('(' . $value1 . '%)') : '' ) ;
                                   }
                               }
                               $row[]=utf8_encode(implode(',',$lang_list));
                           }
                           else
                               $row[]="-";
                       }*/
                       elseif($aColumns[$j] == 'language')
                       {
                           $lang_list = array();
                           if($rResult[$i]['language_more'] != '' && $rResult[$i]['language_more'] != 'N;'){
                               $laninfo=unserialize($rResult[$i]['language_more']) ;
                               if($laninfo != '')
                               {
                                   foreach ($laninfo as $key1 => $value1)
                                   {
                                       $lang_list[]=$this->language_array[$key1].( ($value1 > 0) ? ('(' . $value1 . '%)') : '' ) ;
                                   }
                               }
                               $langmore=utf8_encode(implode(',',$lang_list));
                           }
                           else
                               $langmore="no more languages";
                           if($rResult[$i][$aColumns[$j]] != '')
                               $row[] = '<a href="#" class="hint--left hint--info" data-hint="'.$langmore.'" >'.utf8_encode($this->language_array[$rResult[$i][$aColumns[$j]]]).'</a>';
                           else
                               $row[] = "-";
                       }
                       elseif($aColumns[$j] == 'created_at' || $aColumns[$j] == 'u.created_at')
                           $row[] = date("d-m-Y", strtotime($rResult[$i]['created_at']));
                       elseif($aColumns[$j] == 'profile_type') {
                           if($rResult[$i]['profile_type'] == 'junior')
                               $row[] = '<span class="label label-info">JUNIOR</span>';
                           elseif($rResult[$i]['profile_type'] == 'senior')
                               $row[] = '<span class="label label-info">SENIOR</span>';
                           elseif($rResult[$i]['profile_type'] == 'sub-junior')
                               $row[] = '<span class="label label-info">d&eacute;buts</span>';
                           else
                               $row[] = '-';
                       }
                       elseif($aColumns[$j] == 'status' || $aColumns[$j] == 'u.status') {
                           if($rResult[$i]['payment_type'] == 'paypal')
                                $row[] = $rResult[$i]['status'];
                           else  {
                                $row[] = '<a href="#"  onclick="return changeStatusUser('.$rResult[$i]['identifier'].', \''.$rResult[$i]['status'].'\');" >'.$rResult[$i]['status'].'</a>';    }
                       }
                       elseif($aColumns[$j] == 'actions'){   // echo  $rResult[$i]['identifier']; exit;
                           $email =  $rResult[$i]['email'];
                           $password =  $rResult[$i]['password'];
                           $type = "contributor";
                           if($this->adminLogin->groupId == 1)
                           {
                               $row[] = '<a href="brief-contributor-edit?submenuId=ML10-SL6&userId='.$rResult[$i]['identifier'].'" class="hint--left hint--info" data-hint="credentials edit"><i class="splashy-contact_blue_edit"></i> </a>
                                    <a href="contributor-edit-new?submenuId=ML10-SL6&tab=editcontrib&userId='.$rResult[$i]['identifier'].'" class="hint--left hint--info" data-hint="edit profile"><i class="icon-pencil"></i> </a>
                                   <a href="contributor-edit-new?submenuId=ML10-SL6&tab=viewcontrib&userId='.$rResult[$i]['identifier'].'" class="hint--left hint--info" data-hint="view profile" ><i class="icon-eye-open"></i></a>
                                  <a href="http://ep-test.edit-place.co.uk/user/email-login?user='.MD5("ep_login_".$email).'&hash='.MD5("ep_login_".$password).'&type='.$type.'&redirectpage=home" target="_blank"><i class="splashy-contact_blue"></i></a>';
                           }else{
                           $row[] = '<a href="contributor-edit-new?submenuId=ML2-SL7&tab=editcontrib&userId='.$rResult[$i]['identifier'].'" class="hint--left hint--info" data-hint="edit profile"><i class="icon-pencil"></i> </a>
                                   <a href="contributor-edit-new?submenuId=ML2-SL7&tab=viewcontrib&userId='.$rResult[$i]['identifier'].'" class="hint--left hint--info" data-hint="view profile" ><i class="icon-eye-open"></i></a>
                                  <a href="http://ep-test.edit-place.co.uk/user/email-login?user='.MD5("ep_login_".$email).'&hash='.MD5("ep_login_".$password).'&type='.$type.'&redirectpage=home" target="_blank"><i class="splashy-contact_blue"></i></a>';
						   }
                       }
                       else
                         $row[] = $rResult[$i][ $aColumns[$j] ];
                    }
                }
                $output['aaData'][] = $row;
                $count++;
            }
        }
        echo json_encode( $output );
    }
    public function clientsAction()
    {
        $userdetails=new Ep_User_UserPlus();
        $user_obj = new Ep_User_User();
        $usergrp_obj=new Ep_User_UserGroupAccess();
        $groups =  $usergrp_obj->getAllUserGroupNames();
        $this->render('user_clients');
    }
    public function loadclientAction()
    {
        $userplus_obj=new Ep_User_UserPlus();
        $user_obj = new Ep_User_User();
        $aColumns = array('identifier','company_name','email','type','created_at','ao_count','art_count','art_pcount','download','actions');
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
                if($aColumns[$i] == 'status')
                    $aColumns[$i] = 'u.status';
                if($aColumns[$i] == 'created_at')
                    $aColumns[$i] = 'u.created_at';
                if($aColumns[$i] == 'ao_count')
                    break;
                if($aColumns[$i] == 'art_count')
                    break;
                if($aColumns[$i] == 'art_pcount')
                    break;
                if($aColumns[$i] == 'download')
                    break;
                if($aColumns[$i] == 'actions')
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
          // echo $sWhere; exit;
        $rResult  = $userplus_obj->ListStatsClientsinfo($sWhere, $sOrder, $sLimit, $condition);
        $rResultcount = count($rResult);

        /////total count
        $sLimit = "";
        $countclients  = $userplus_obj->ListStatsClientsinfo($sWhere, $sOrder, $sLimit, $condition);
        $iTotal = count($countclients);

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
                        if($aColumns[$j] == 'ao_count')
                            $row[] = '<a href="client-edit?submenuId=ML2-SL7&tab=aolistclient&userId='.$rResult[$i]['identifier'].'" class="num-large" target="_blank">'.$rResult[$i]['art_count'].'</a>';
                        elseif($aColumns[$j] == 'art_count')
                            $row[] = '<label class="label label-warning">'.$rResult[$i]['art_count'].'</label>';
                        elseif($aColumns[$j] == 'art_pcount')
                            $row[] = '<label class="label label-warning">'.$rResult[$i]['art_pcount'].'</label>';
                        elseif($aColumns[$j] == 'created_at')
                            $row[] = date("d-m-Y H:i", strtotime($rResult[$i]['created_at']));
                        elseif($aColumns[$j] == 'download')
                        {
                            if($rResult[$i]['ao_count'] != 0)
                                $row[] = '<a href="http://ep-test.edit-place.co.uk/getClientArticles.php?client_id='.$rResult[$i]['identifier'].'">Download</a>';
                            else
                                $row[] = '-';
                        }
						elseif($aColumns[$j] == 'type'){
                            $row[] = '<label class="label label-info">'.$rResult[$i]['type'].'</label>';
						}
                        elseif($aColumns[$j] == 'actions')
						{
                            $email =  $rResult[$i]['email'];
                            $password =  $rResult[$i]['password'];
                            $type = $rResult[$i]['type'];//"client";
							if($type=='client')
							{
								$row[] = '<a href="client-edit?submenuId=ML2-SL7&tab=editclient&userId='.$rResult[$i]['identifier'].'" class="hint--left hint--info" data-hint="edit profile"><i class="icon-pencil"></i> </a>
                                    <a href="client-edit?submenuId=ML2-SL7&tab=viewclient&userId='.$rResult[$i]['identifier'].'" class="hint--left hint--info" data-hint="view profile"><i class="icon-eye-open"></i></a>
                                    <a href="http://ep-test.edit-place.co.uk/user/email-login?user='.MD5("ep_login_".$email).'&hash='.MD5("ep_login_".$password).'&type='.$type.'&redirectpage=home" target="_blank"><i class="splashy-contact_blue"></i></a>';
							}
							if($type=='superclient')
								$row[] = '<a href="super-client-create?submenuId=ML2-SL7&uaction=edit&userId='.$rResult[$i]['identifier'].'" class="hint--left hint--info" data-hint="edit profile"><i class="icon-pencil"></i> </a>';
                            if($type=='sccontact')
                                $row[] = '';
                        }
						elseif($aColumns[$j] == 'company_name')
						{
							/*if($rResult[$i]['type']=='superclient')
								$row[] = $rResult[$i]['sc_name'];
							else*/
								$row[] = $rResult[$i][ $aColumns[$j] ];
						}
                        else
                            $row[] = $rResult[$i][ $aColumns[$j] ];
                    }
                }
                $output['aaData'][] = $row;
                $count++;
            }
        }
        // print_r($output);  exit;
         echo json_encode( $output );

    }
	
	public function bulkwritercreationAction()
	{
		
		$language=$this->_arrayDb->loadArrayv2("EP_LANGUAGES", $this->_lang);
		$this->_view->language = $language;
		
		if($_POST['email_list']!="")
		{
			$emails=explode(",",$_POST['email_list']);
			if(count($emails)>0)
			{
				for($e=0;$e<count($emails);$e++)
				{
					$user_obj=new Ep_User_User();
					$postarray=array();
					$postarray['email']=$emails[$e];
					$postarray['writerpassword']=$_POST['writerpassword'];
					$postarray['writerlanguage']=$_POST['writerlanguage'];
					$postarray['created_user']=$this->adminLogin->userId;
					$user_obj->CreateBulkContribUser($postarray);
				}
				
				//Email
				if($_POST['intimatecheck']=="yes" && $_POST['mailobject']!="")
				{
					for($m=0;$m<count($emails);$m++)
					{	
						$email=$emails[$m];
						$password=$_POST['writerpassword'];
						
						$subject=$_POST['mailsubject'];
						$object=$_POST['mailobject'];
						eval("\$subject= \"$subject\";");
						eval("\$object= \"$object\";");
						
						$mail = new Zend_Mail();
						$mail->addHeader('Reply-To','support@edit-place.com');
						$mail->setBodyHtml($object)
							->setFrom('support@edit-place.com','Support Edit-place')
							->addTo($emails[$m])
							->addCc('kavithashree.r@gmail.com')
							->setSubject($subject);
						$mail->send();
							
						
					}
				}
				$this->_helper->FlashMessenger('Users created.');	
				$this->_redirect("/user/bulkwritercreation?submenuId=ML10-SL10");
			}	
		}
		
		$this->_view->render("user_bulkwritercreation"); 
	}
	
	public function emailListExistsAction()
    {
        $user_obj = new Ep_User_User();
        $user_params=$this->_request->getParams();
		
		$emails=explode(",",$user_params['email']);
		
		$existarray=array();
		for($e=0;$e<count($emails);$e++)
		{
			$emailexist=$user_obj->getExistingEmail($emails[$e]);
			if($emailexist=="yes")
				$existarray[]=$emails[$e];
				
		}
		
		if(count($existarray)>0)
			echo implode(",",$existarray);
		else
			echo "no";
		exit;
    }
	
	/***************************** White book *************************************/
		public function whitebookDownloadAction()  
	{
		$wb_obj = new EP_User_WhitebookDownloads();	
		
		//Multple delete
		if($_POST['deletewb']!="")
		{	
			if(count($_POST['deletecheck'])>0)
			{
				for($w=0;$w<count($_POST['deletecheck']);$w++)
					$wb_obj->deleteWhitebook($_POST['deletecheck'][$w]);
			}
		}
		
		//Upload white book
		if($_POST['submitwb']!="")
		{
			$uploaddir = '/home/sites/site7/web/FO/whitebook/';
					
			$ext=pathinfo($_FILES['uploadwb']['name']);
			if($ext['extension']=="pdf" || $ext['extension']=="zip")  
			{ 
				$file=$uploaddir.'whitebook.'.$ext['extension'];
				unlink($file);
				if (move_uploaded_file($_FILES['uploadwb']['tmp_name'], $file))
					chmod($file,0777);
			}
		}
		
		$searchvals = array();
		if($_REQUEST['search_submit']!="")
		{
			$searchvals['start_date'] = $_GET['start_date'];
			$searchvals['end_date'] = $_GET['end_date'];
			
			$this->_view->start_date = $searchvals['start_date'];
			$this->_view->end_date = $searchvals['end_date'];
		}
		
		$this->_view->wlist=$wb_obj->listWdownloads($searchvals);
		$this->_view->render("user_whitebookdownload"); 
	}
	
	public function uploadWhitebookAction()
	{
		$this->_view->render("user_uploadwhitebook"); 
	}
    ///////////added by naseer on 06.08.2015/////////////////
    ///////////edit the contributor new /////////////////
    public function contributorEditNewAction()
    {
        $user_obj=new Ep_User_User();
        $changelog_obj = new Ep_User_ProfileChangeLog();
        $experience_obj=new EP_User_ContributorExperience();
        $mail_obj=new Ep_Message_AutoEmails();
        $userId = $this->_request->getParam('userId');
        $user_details=$user_obj->getContributordetails($userId);
        /* *getting User expeience details**/
        $jobDetails=$experience_obj->getExperienceDetails($userId,'job');
        if($jobDetails!="NO")
            $this->_view->jobDetails=$jobDetails;
        $educationDetails=$experience_obj->getExperienceDetails($userId,'education');


        if($educationDetails!="NO")
            $this->_view->educationDetails=$educationDetails;
        /* *iNOVICE inFO ***/
        $this->_view->payment_type=$user_details[0]['payment_type'];
        $this->_view->pay_info_type=$user_details[0]['pay_info_type'];
        $this->_view->SSN=$user_details[0]['SSN'];
        $this->_view->company_number=$user_details[0]['company_number'];
        $this->_view->vat_check=$user_details[0]['vat_check'];
        $this->_view->VAT_number=$user_details[0]['VAT_number'];
        /* *Paypal and RIB info**/
        $this->_view->paypal_id=$user_details[0]["paypal_id"] ;
        $RIB_ID=explode("|",$user_details[0]["rib_id"]) ;
        if (($user_details[0]['pay_info_type'] == 'out_france' || $user_details[0]['country'] != 38) && count($RIB_ID) == 2) {
            $this->_view->rib_id_6 = $RIB_ID[0];
            $this->_view->rib_id_7 = $RIB_ID[1];
        } else {
            $this->_view->rib_id_1 = $RIB_ID[0];
            $this->_view->rib_id_2 = $RIB_ID[1];
            $this->_view->rib_id_3 = $RIB_ID[2];
            $this->_view->rib_id_4 = $RIB_ID[3];
            $this->_view->rib_id_5 = $RIB_ID[4];
        }       
        //////edit contributor////////////////////////////////////
        $this->_view->user_detail=$user_details;   //echo "<pre>";print_r($user_details); exit;

        $this->_view->dob=date('d-m-Y',strtotime($user_details[0]['dob']));
        $this->_view->self_details=utf8_encode($user_details[0]['self_details']);
        $this->_view->stats=$_GET['stats'];
        $this->_view->loguser=$this->adminLogin->userId;
        $this->_view->category_more=unserialize($user_details[0]['category_more']);
        $this->_view->language_more=unserialize($user_details[0]['language_more']);
        /* by naseer on 06.08.2015 */

        $REQ = $this->_request->getParams();
        if($this->_request->getParam('submit_contrib_bo_only')!= ''){
            $user_obj->updateContribBoOnly($REQ);
            $this->_redirect("/user/contributor-edit-new?submenuId=ML10-SL1&tab=editcontrib&userId=" . $userId);
        } elseif ($this->_request->getParam('submit_contrib_user_basic') != '') {
            $user_obj->updateContribUserBasic($REQ);
            $this->_redirect("/user/contributor-edit-new?submenuId=ML10-SL1&tab=editcontrib&userId=" . $userId);
        } elseif ($this->_request->getParam('submit_contrib_categories_and_lang') != '') {
            $user_obj->updateContribCategoriesAndLang($REQ);
            $this->_redirect("/user/contributor-edit-new?submenuId=ML10-SL1&tab=editcontrib&userId=" . $userId);
        } elseif ($this->_request->getParam('submit_contrib_experience') != '') {
            $this->updateExperienceDetails($REQ, 'job');
            $this->updateExperienceDetails($REQ, 'education');
            $this->_redirect("/user/contributor-edit-new?submenuId=ML10-SL1&tab=editcontrib&userId=" . $userId);
        } elseif ($this->_request->getParam('submit_contrib_payment_info_form') != '') {
            $user_obj->updateContribPaymentInfo($REQ);
            $this->_redirect("/user/contributor-edit-new?submenuId=ML10-SL1&tab=editcontrib&userId=" . $userId);
        } elseif ($this->_request->getParam('submit_contrib_more_info_form') != '') {
            $user_obj->updateContribMoreInfo($REQ);
            $this->_redirect("/user/contributor-edit-new?submenuId=ML10-SL1&tab=editcontrib&userId=" . $userId);
        }
        /* end of by naseer on 06.08.201*/
        if ($this->_request->getParam('submit_contrib') != '') {
            $user_obj->updatecontribUser($REQ);
            $this->updateExperienceDetails($REQ, 'job');
            $this->updateExperienceDetails($REQ, 'education');
            //If profile type changed from junior to senior
            if ($REQ['profile_type'] == 'senior' && $REQ['prev_profile'] == 'junior') {
                // $parameters['jc_limit']=$this->getConfiguredval('jc_limit');
                // $parameters['sc_limit']=$this->getConfiguredval('sc_limit');
                $mail_obj->messageToEPMail($userId, 30, $parameters);
            }
            //If profile type2 in corrector aspect changed from junior to senior
            if ($REQ['type2'] == 'yes' && $REQ['profile_type2'] == 'senior' && $REQ['prev_profile2'] == 'junior') {
                $mail_obj->messageToEPMail($userId, 110, $parameters);
            }
            $this->_helper->FlashMessenger('Profile Updated Successfully.');
            $this->_redirect("/user/contributor-edit?submenuId=ML2-SL7&tab=editcontrib&userId=".$userId);
        }
        //////view contributor////////////////////////////////////
        $this->_view->ep_contrib_profile_language_more = explode(",", $user_details[0]['language_more']);
        $this->_view->ep_contrib_profile_category = explode(",", $user_details[0]['favourite_category']);
        $this->_view->self_details = utf8_encode($user_details[0]['self_details']);
        $this->_view->stats = $_GET['stats'];
        $this->_view->loguser = $this->adminLogin->userId;
        $contrib_picture_path = "/home/sites/site7/web/FO/profiles/contrib/pictures/".$user_details[0]['user_id']."/".$user_details[0]['user_id']."_h.jpg";
        if (file_exists($contrib_picture_path))
            $contrib_picture_path = "http://ep-test.edit-place.co.uk/FO/profiles/contrib/pictures/".$user_details[0]['user_id']."/".$user_details[0]['user_id']."_h.jpg";
        else
            $contrib_picture_path = "http://ep-test.edit-place.co.uk/FO/images/Contrib/profile-img-def.png";
        $this->_view->user_pic=$contrib_picture_path;

        $expCat =   explode(',', preg_replace("/\([^)]+\)/","",$this->unserialisearray($user_details[0]['category_more']))) ;
        preg_match_all('#\((.*?)\)#', $this->unserialisearray($user_details[0]['category_more']), $match) ;

        foreach ($expCat as $key => $value) {
            $impCat[]   =   $this->category_array[$value] . '(' . $match[1][$key] . ')' ;
        }
        $user_details[0]['category_more'] = implode(',', $impCat) ;
        unset($impCat) ;

        $user_details[0]['language_more'] = $this->unserialisearray($user_details[0]['language_more']);
        $workexpDetails=$user_obj->getExperienceDetails($user_details[0]['user_id'],'job');
        if ($workexpDetails != "NO") {
            $ecnt = 0;
            foreach ($workexpDetails as $workexp) {
                $workexpDetails[$ecnt]['start_date'] = date('FY', strtotime($workexp['from_year'] . "-" . $workexp['from_month']));
                if ($workexp['still_working'] == 'yes')
                    $workexpDetails[$ecnt]['end_date'] = 'Actuel';
                else
                    $workexpDetails[$ecnt]['end_date'] = date('FY', strtotime($workexp['to_year'] . "-" . $workexp['to_month']));
                $ecnt++;
            }
            $this->_view->educationDetailsview = $workexpDetails;
        }
        //echo "<pre>"; print_r($user_details);    exit;
        $this->_view->user_detail = $user_details;
        $this->_view->country_name = $this->country_array[$user_details[0]['country']];
        $this->_view->ep_contrib_profile_language_more = explode(",", $user_details[0]['language_more']);
        $this->_view->ep_contrib_profile_category = explode(",", $user_details[0]['favourite_category']);
        $this->_view->profession = $this->profession_array[$user_details[0]['profession']];
        $this->_view->language = $this->language_array[$user_details[0]['language']];
        $this->_view->nationality = $this->nationality_array[$user_details[0]['nationality']];
        $this->_view->self_details = utf8_encode(strip_tags($user_details[0]['self_detailss']));
        //lang_more str
        $language_more = "";
        if ($user_details[0]['language_more'] != NULL) {
            $lang_more = explode(",", $user_details[0]['language_more']);

            if (count($lang_more) > 0) {
                for ($l = 0; $l < count($lang_more); $l++) {
                    $language_more .= $this->language_array[$lang_more[$l]];

                    if ($l != count($lang_more) - 1)
                        $language_more .= ",";
                }
            }
        }
        $this->_view->language_more1 = $language_more;
        //fav category str
        $favourite_category = "";
        if ($user_details[0]['favourite_category'] != NULL) {
            $fav_cat = explode(",", $user_details[0]['favourite_category']);

            if (count($fav_cat) > 0) {
                for ($l = 0; $l < count($fav_cat); $l++) {
                    $favourite_category .= $this->category_array[$fav_cat[$l]];

                    if ($l != count($fav_cat) - 1)
                        $favourite_category .= ",";
                }
            }
        }
        $this->_view->favourite_category=$favourite_category;
        $eduarray=array("1" => "Bac +1","2" => "Bac +2","3" => "Bac +3","4" => "Bac +4","5" => "Bac +5","6" => "Bac+5 et plus");
        $this->_view->education=$eduarray[$user_details[0]['education']];
        /*added by naseer on 04-11-2015*/
        //fetch new fields "software_list"//
        $software_array = $this->_arrayDb->loadArrayv2("EP_SOFTWARE_LIST", $this->_lang);
        //explodig all the software list and saing in multidimentaional array for later use//
        foreach ($software_array as $k => $v) {
            $software_array[$k] = explode("-", $v);
        }
        $this->_view->ep_software_array = $software_array;
        //exploding and saving the values since i've imploded at the time of insertion//
        $software_list_temp = explode("###$$$###", $user_details[0]['software_list']);
        for ($i = 0; $i < count($software_list_temp); $i++) {
            $software_list[$i] = explode('|', $software_list_temp[$i]);
        }
        $this->_view->software_list = $software_list;
        $this->_view->software_list_count = count($software_list);//saving the count for later use in phtml file//
        /* end of added by naseer on 04-11-2015*/
        /*add by naseer on 09-11-2015*/
        //fetch the userlogs with the user_id//
        $this->_view->userId = $userId;
        $this->_view->user_logs = $this->getUserLogs($userId);
        /*end of add by naseer on 09-11-2015*/
        $this->_view->render("user_contributoreditnew");
    }

    /*fetch all the userLogs for individual user in readable format */
    public function getUserLogs($userId)
    {
        $userlogs_obj = new Ep_User_UserLogs();
        $user_logs = $userlogs_obj->getUserLogs($userId);
        $listfieldnames = array(
            'password' => 'Mon mot de passe',
            'initial' => 'Mon identit&eacute;',
            'first_name' => 'Mon prÃ©nom',
            'last_name' => 'Mon nom',
            'dob' => 'Ma date de naissance',
            'language' => 'Langue maternelle',
            'address' => 'Adresse',
            'city' => 'Ville',
            'country' => 'Pays',
            'nationality' => 'Nationalit&eacute;',
            'zipcode' => 'Code Postal',
            'phone_number' => 'T&eacute;l&eacute;phone',
            'self_details' => 'Texte de pr&eacute;sentation',
            'passport_no' => 'Num&eacute;ro de passeport',
            'id_card' => 'Carte d\'identit&eacute;',
            'language_more' => 'Autre(s) langue(s) parl&eacute;e(s)',
            'category_more' => 'Comp&eacute;tences & niveau de maitrise',
            'title' => 'title',
            'institute' => 'Institute',
            'contract' => 'Contract',
            'from_month' => 'From Month',
            'from_year' => 'From Year',
            'still_working' => 'Still Working',
            'to_month' => 'To Month',
            'to_year' => 'TO Year',
            'software_list' => 'Software Ownership and Experience Level',
            'tva_number' => 'Num&eacute;ro de TVA Intracommunautaire',
            'com_name' => 'D&eacute;nomination sociale',
            'com_address' => 'Adresse',
            'com_city' => 'Ville',
            'com_zipcode' => 'Code Postal',
            'com_country' => 'Pays',
            'com_siren' => 'Siren',
            'com_tva_number' => 'Num&eacute;ro de TVA Intracommunautaire',
            'com_phone' => 'T&eacute;l&eacute;phone',
            'siren_number' => 'Siren',
            'paypal_id' => 'Pay Pal Id',
            'bank_account_name' => 'Nom du bÃ©nÃ©ficiaire',
            'virement' => 'Virement',
            'rib_id' => 'Virement Details',
            'payment_type' => 'Payment Type',
            'options_flag' => 'Payment Option',
            'alert_subscribe' => 'Alert Subscribe',
            'subscribe' => 'Subscribe',
            'status' => 'Status',
            'profile_type' => 'Type ',
            'type2' => 'Corrector ',
            'profile_type2' => 'Corrector Status',
            'blackstatus' => 'Black list ',
            'contributortest' => 'Ce contributeur a &eacute;t&eacute; test&eacute;',
            'contributortestcomment' => 'Comments',
            'contributortestmarks' => 'Marks',
            'translator' => 'Translator',
            'translator_type' => 'Translator Type',
            'writer_preference' => 'Writer Preference',
            'twitter_id' => 'Twitter ID',
            'facebook_id' => 'Facebook ID',
            'website' => 'Website',
            'translator_type' => 'Translator Type',
            'Other' => 'Others',
        );
        $log_type_name = array(
            'profile' => 'Profile',
            'language_update' => 'Language Update',
            'category_update' => 'Category Update',
            'job_update' => 'job Update',
            'edu_update' => 'Education Update',
            'skill_update' => 'Skill Update',
            'payment' => 'Payment',
            'payment_type' => 'Payment Type',
            'company_entrepreneur_update' => 'company/entrepreneur Update',
            'subscription' => 'Subscription',
            'onlybo' => 'BO(only BO can Change)',
            'other' => 'Others');
        $rib = array('Nom de l\'&eacute;tablissemen|Code Banque|Code Guichet|Num&eacute;ro de compte|Cl&eacute; RIB', 'BIC|IBAN');
        $software_list = array("Software", "Level", "Own It");
        $software_array = $this->_arrayDb->loadArrayv2("EP_SOFTWARE_LIST", $this->_lang);
        //explodig all the software list and saing in multidimentaional array for later use//
        foreach ($software_array as $k => $v) {
            $software_array[$k] = explode("-", $v);
        }

        for ($i = 0; $i < count($user_logs); $i++) {
            $user_logs[$i]['field_name'] = $listfieldnames[$user_logs[$i]['field']];//fetchs the values from array $listfieldnames
            $user_logs[$i]['log_type'] = $log_type_name[$user_logs[$i]['log_type']];//fetchs the values from array $log_type_name
            if ($user_logs[$i]['field'] === 'dob') {
                $user_logs[$i]['old_value'] = date('d-m-Y', strtotime($user_logs[$i]['old_value']));
                $user_logs[$i]['new_value'] = date('d-m-Y', strtotime($user_logs[$i]['new_value']));
            } elseif ($user_logs[$i]['field'] === 'language') {
                $user_logs[$i]['old_value'] = $this->language_array[$user_logs[$i]['old_value']];
                $user_logs[$i]['new_value'] = $this->language_array[$user_logs[$i]['new_value']];
            } elseif ($user_logs[$i]['field'] === 'country' || $user_logs[$i]['field'] === 'com_country') {
                $user_logs[$i]['old_value'] = $this->country_array[$user_logs[$i]['old_value']];
                $user_logs[$i]['new_value'] = $this->country_array[$user_logs[$i]['new_value']];
            } elseif ($user_logs[$i]['field'] === 'nationality') {
                $user_logs[$i]['old_value'] = $this->nationality_array[$user_logs[$i]['old_value']];
                $user_logs[$i]['new_value'] = $this->nationality_array[$user_logs[$i]['new_value']];
            } elseif ($user_logs[$i]['field'] === 'language_more' || $user_logs[$i]['field'] === 'category_more') {
                $old_value = unserialize($user_logs[$i]['old_value']);
                $temp1 = "";
                foreach ($old_value as $key => $value) {
                    $temp1 .= $key . "($value),";
                }
                $user_logs[$i]['old_value'] = $temp1;
                //for new value//
                $new_value = unserialize($user_logs[$i]['new_value']);
                $temp2 = "";
                foreach ($new_value as $key => $value) {
                    $temp2 .= $key . "($value),";
                }
                $user_logs[$i]['new_value'] = $temp2;
            } elseif ($user_logs[$i]['field'] === 'rib_id') {
                $old_value = explode("|", $user_logs[$i]['old_value']);
                $temp1 = "";
                $names = "";
                if (count($old_value) > 2) {
                    $names = explode("|", $rib[0]);
                } else {
                    $names = explode("|", $rib[1]);
                }
                for ($j = 0; $j < count($names); $j++) {
                    $temp1 .= $names[$j] . " : " . $old_value[$j] . "<br />";
                }
                $user_logs[$i]['old_value'] = $temp1;
                //for new values
                $new_value = explode("|", $user_logs[$i]['new_value']);
                $temp2 = "";
                $names = "";
                if (count($new_value) > 2) {
                    $names = explode("|", $rib[0]);
                } else {
                    $names = explode("|", $rib[1]);
                }
                for ($j = 0; $j < count($names); $j++) {
                    $temp2 .= $names[$j] . " : " . $new_value[$j] . "<br />";
                }
                $user_logs[$i]['new_value'] = $temp2;

            } elseif ($user_logs[$i]['field'] === 'software_list') {
                $old_value = explode("###$$$###", $user_logs[$i]['old_value']);
                $str1 = '';

                for ($j = 0; $j < count($old_value) - 1; $j++) {
                    $temp1 = explode("|", $old_value[$j]);
                    $str1 .= $software_list[0] . ' : ' . $software_array[$temp1[0]][1];
                    $str1 .= "(" . $software_list[1] . ' : ' . $temp1[1] . ") - ";
                    $str1 .= $software_list[2] . ' : ' . (($temp1[2] === 'on') ? 'Yes' : 'No');

                    $str1 .= "<br />";
                }
                $user_logs[$i]['old_value'] = $str1;
                //for new values
                $new_value = explode("###$$$###", $user_logs[$i]['new_value']);
                $str1 = '';

                for ($j = 0; $j < count($new_value) - 1; $j++) {
                    $temp1 = explode("|", $new_value[$j]);
                    $str1 .= $software_list[0] . ' : ' . $software_array[$temp1[0]][1];
                    $str1 .= "(" . $software_list[1] . ' : ' . $temp1[1] . ") - ";
                    $str1 .= $software_list[2] . ' : ' . (($temp1[2] === 'on') ? 'Yes' : 'No');

                    $str1 .= "<br />";
                }
                $user_logs[$i]['new_value'] = $str1;
            }

        }
        //    echo "<pre>";print_r($user_logs);exit;
        return $user_logs;
    }

    public function loadUserLogsAction()
    {
        $userId = $this->_request->getParam('userId');
        $user_logs = $this->getUserLogs($userId);
        $data = '<table class="table table-bordered table-striped table_vam">
                        <thead>
                        <tr>
                            <th>Log Type Changed</th>
                            <th>Field Changed</th>
                            <th>Previous values</th>
                            <th>New values</th>
                            <th>Updated At</th>
                        </tr>
                        </thead>';
        for ($i = 0; $i < count($user_logs); $i++) {
            $data .= '<tr>
                <td>' . $user_logs[$i]['log_type'] . '</td>
                <td>' . $user_logs[$i]['field_name'] . '</td>
                <td>' . (($user_logs[$i]['old_value'] !== "") ? utf8_encode($user_logs[$i]['old_value']) : "-") . '</td>
                <td>' . (($user_logs[$i]['new_value'] !== "") ? utf8_encode($user_logs[$i]['new_value']) : "-") . '</td>
                <td>' . $user_logs[$i]['updated_at'] . '</td>
            </tr>';
        }
        $data .= '</table>';
        echo $data;
        exit;
    }
    
    public function updateContribAjaxAction()
    {
        $params = $this->_request->getParams();
        $user_obj = new Ep_User_User();
        $userId = $this->_request->getParam('userId');
        //fetch the data before any changes are made//
        $old_data = $user_obj->getContributordetails($userId);
        if ($user_obj->checkCreateUserPlus($params['userId']) && $user_obj->checkCreateContributor($params['userId'])) {
            if ($this->_request->getParam('submit') == 'submit_contrib_bo_only') {
                //$user_obj->updateContribBoOnly($params);
                $uarr = array();
                $uarr['status'] = $params['status'];
                $uarr['profile_type'] = $params['profile_type'];
                //$uarr['subscribe'] = $params['subscribe'];
                //$uarr['alert_subscribe'] = $params['alert_subscribe'];
                if ($params['type2'] == 'yes') {
                    $uarr['type2'] = 'corrector';
                } else {
                    $uarr['type2'] = NULL;
                }
                $uarr['profile_type2'] = $params['profile_type2'];
                $uarr['blackstatus'] = $params['blackstatus'];
                $this->updateUserLogs($old_data[0], $uarr);//fucntion to insert userlogs if the old data is different from the request sent//
                $response1 = $user_obj->updateContribBoOnlyUser($userId, $uarr);

                $contribarr['contributortest'] = $params["contributortest"];
                if ($params["contributortest"] == "yes") {
                    $contribarr['contributortestcomment'] = utf8_decode($params["contributortestcomment"]);
                    $contribarr['contributortestmarks'] = $params["contributortestmarks"];
                }
				 /*edited by naseer on 26.11.2015*/

                $contribarr['translator'] = $params['translator'];

                $contribarr['translator_type'] = $params['translator_type'];
                $this->updateUserLogs($old_data[0], $contribarr);//fucntion to insert userlogs if the old data is different from the request sent//
                $response2 = $user_obj->updateContribBoOnlyContrib($userId, $contribarr);
                echo ($response1 && $response2) ? "1"/*true*/ : '0'/*false*/;
            } elseif ($this->_request->getParam('submit') == 'submit_contrib_user_basic') {
                //$user_obj->updateContribUserBasic($params);
                $userarr = array();
                //$userarr['first_name']=$params['first_name'];
                //$userarr['last_name']=$params['last_name'];
                $userarr['address'] = utf8_decode($params['address']);
                $userarr['city'] = utf8_decode($params['city']);
                $userarr['zipcode'] = $params['zipcode'];
                $userarr['country'] = utf8_decode($params['country']);
                $userarr['phone_number'] = $params['phone_number'];
                $this->updateUserLogs($old_data[0], $userarr);//fucntion to insert userlogs if the old data is different from the request sent//
                $response1 = $user_obj->updateContribUserBasicUserPlus($userId, $userarr);

                $contribarr = array();
                $contribarr['dob'] = date('Y-m-d', strtotime($params['birth_date']));
                $contribarr['language'] = $params['language'];
                $contribarr['nationality'] = $params['nationality'];
                $contribarr['self_details'] = utf8_decode(nl2br($params['self_details']));
                $this->updateUserLogs($old_data[0], $contribarr);//fucntion to insert userlogs if the old data is different from the request sent//
                $response2 = $user_obj->updateContribUserBasicContributor($userId, $contribarr);

                $uarr = array();
                if ($params['subscribe'] !== '')
                    $uarr['subscribe'] = $params['subscribe'];
                if ($params['subscribe'] !== '')
                    $uarr['alert_subscribe'] = $params['alert_subscribe'];
                $this->updateUserLogs($old_data[0], $uarr);//fucntion to insert userlogs if the old data is different from the request sent//
                $response3 = $user_obj->updateContribUserBasicUser($userId, $uarr);
                echo ($response1 && $response2 && $response3) ? "1"/*true*/ : '0'/*false*/;

            } elseif ($this->_request->getParam('submit') == 'submit_contrib_categories_and_lang') {
                if (count($params['ep_category']) > 0) {
                    $category_more = $params['ep_category'];
                    $category_sliders_more = $params['category_slider_more'];
                    foreach ($category_more as $key => $category) {
                        if ($category)
                            $moreCategories[$category] = str_replace("%", "", $category_sliders_more[$key]);
                    }
                    $contribarr['category_more'] = serialize($moreCategories);
                }
                if (count($params['language_more']) > 0) {
                    $language_more = $params['language_more'];
                    $lang_sliders_more = $params['lang_slider_more'];
                    foreach ($language_more as $key => $lang) {
                        if ($lang)
                            $moreLanguages[$lang] = str_replace("%", "", $lang_sliders_more[$key]);
                    }
                    $contribarr['language_more'] = serialize($moreLanguages);
                }
                $this->updateUserLogs($old_data[0], $contribarr);//fucntion to insert userlogs if the old data is different from the request sent//
                $response1 = $user_obj->updateContribCategoriesAndLang($userId, $contribarr);
                echo ($response1 ) ? '1'/*true*/ : '0'/*false*/;
            } elseif ($this->_request->getParam('submit') == 'submit_contrib_experience') {
                $response1 = $this->updateExperienceDetails($params, 'job');
                $response2 =  $this->updateExperienceDetails($params, 'education');
                //echo ($response1 && $response2) ? "1"/*true*/ : '0'/*false*/;
                echo '1';
            } elseif ($this->_request->getParam('submit') == 'submit_contrib_payment_info_form') {
                $contribarr = array();
                $contribarr['payment_type'] = $params["payment_type"];
                $contribarr['pay_info_type'] = $params["pay_info_type"];
                /*$contribarr['SSN'] = $params["ssn"];
                $contribarr['company_number'] = $params["company_number"];
                $contribarr['vat_check'] = $params["vat_check"];
                $contribarr['VAT_number'] = $params["VAT_number"]; */
                /* * Inserting Paypal and RIB info**/
                $contribarr['paypal_id'] = $params["paypal_id"];
                $userplus_obj = new Ep_User_UserPlus();
                $result = $userplus_obj->getUsersDetailsOnId($userId);
                $country = $result[0]['country'];
                if ($country == 38) {
                    $contribarr['rib_id'] = utf8_decode($params["rib_id_1"]) . "|" . utf8_decode($params["rib_id_2"]) . "|" . utf8_decode($params["rib_id_3"]) . "|" .
                        utf8_decode($params["rib_id_4"]) . "|" . utf8_decode($params["rib_id_5"]);
                } else {
                    $contribarr['rib_id'] = utf8_decode($params["rib_id_6"]) . "|" . utf8_decode($params["rib_id_7"]);
                }
                $this->updateUserLogs($old_data[0], $contribarr);//fucntion to insert userlogs if the old data is different from the request sent//
                $response1 = $user_obj->updateContribPaymentInfo($userId, $contribarr);
                echo ($response1 ) ? '1'/*true*/ : '0'/*false*/;
            } elseif ($this->_request->getParam('submit') == 'submit_contrib_more_info_form') {
                $contribarr = array();
                $options_flag = $params['options_flag'];
                if ($options_flag == 'reg_check') {
                    $contribarr["options_flag"] = $params['options_flag'];
                    $contribarr["passport_no"] = $params['passport_no'];
                    $contribarr["id_card"] = $params['id_card'];

                } elseif ($options_flag == 'com_check') {
                    $contribarr["options_flag"] = $params['options_flag'];
                    $contribarr["com_name"] = utf8_decode($params['com_name']);
                    $contribarr["com_country"] = utf8_decode($params['com_country']);
                    $contribarr["com_address"] = utf8_decode($params['com_address']);
                    $contribarr["com_phone"] = $params['com_phone'];
                    $contribarr["com_city"] = utf8_decode($params['com_city']);
                    $contribarr["com_zipcode"] = $params['com_zipcode'];
                    $contribarr["com_siren"] = utf8_decode($params['com_siren']);
                    $contribarr["com_tva_number"] = utf8_decode($params['com_tva_number']);
                } elseif ($options_flag == 'tva_check') {
                    $contribarr["options_flag"] = $params['options_flag'];
                    $contribarr["siren_number"] = utf8_decode($params['siren_number']);
                    $contribarr["denomination_sociale"] = utf8_decode($params['denomination_sociale']);
                    $contribarr["tva_number"] = utf8_decode($params['tav_number']);
                }
                $this->updateUserLogs($old_data[0], $contribarr);//fucntion to insert userlogs if the old data is different from the request sent//
                $response1 = $user_obj->updateContribMoreInfo($userId, $contribarr);
                echo ($response1 ) ? '1'/*true*/ : '0'/*false*/;
            }
            exit;
        } else {
            echo "error updating";
        }
    }

    /* added by naseer on 05-11-2015*/
    //this function will check if values have been edited and insert into userLogs table *if any//
    public function updateUserLogs($old_data, $new_data)
    {
        $updated_by = $this->adminLogin->userId;
        //arrays that will be used for log_type field//
        $profile = array('password', 'initial', 'first_name', 'last_name', 'dob', 'language', 'address', 'city', 'country', 'nationality', 'zipcode', 'phone_number', 'self_details', 'passport_no', 'id_card','translator');//edited on 26-11-2015 by naseer//
        $language_update = array('language_more');
        $category_update = array('category_more', 'favourite_category');
        $job_edu_update = array('title', 'institute', 'contract', 'from_month', 'from_year', 'still_working', 'to_month', 'to_year');
        $skill_update = array('software_list');
        $company_entrepreneur_update = array('tav_number', 'com_name', 'com_address', 'com_city', 'com_zipcode', 'com_country', 'com_siren', 'com_tva_number', 'com_phone', 'siren_number');
        $payment = array('paypal_id', 'bank_account_name', 'virement', 'rib_id');
        $payment_type = array('payment_type', 'options_flag');
        $subscription = array('alert_subscribe', 'subscribe');
        $onlybo = array('status', 'profile_type', 'type2', 'profile_type2', 'blackstatus', 'contributortest', 'contributortestcomment', 'contributortestmarks','translator_type');
        // end arrays that will be used for log_type field//
        $userlogs_obj = new Ep_User_UserLogs();
        foreach ($new_data as $key => $value) {
            // enterprise/updated_at doesnt matter if its chained//
            if ($old_data[$key] !== $value && $key !== 'updated_at' && $key !== 'entreprise' && $key !== 'identifier' && $key !== 'staus_self_details_update' && $key !== 'controller' && $key !== 'action' && $key !== 'module' && $key !== 'userId' && $key !== 'submit') {
                if (in_array($key, $profile))
                    $log_type = 'profile';
                elseif (in_array($key, $language_update))
                    $log_type = 'language_update';
                elseif (in_array($key, $category_update))
                    $log_type = 'category_update';
                elseif (in_array($key, $job_edu_update)) {
                    if ($new_data['type'] === 'job')
                        $log_type = 'job_update';
                    elseif ($new_data['type'] === 'education')
                        $log_type = 'edu_update';
                } elseif (in_array($key, $skill_update))
                    $log_type = 'skill_update';
                elseif (in_array($key, $company_entrepreneur_update))
                    $log_type = 'company_entrepreneur_update';
                elseif (in_array($key, $payment))
                    $log_type = 'payment';
                elseif (in_array($key, $payment_type))
                    $log_type = 'payment_type';
                elseif (in_array($key, $subscription))
                    $log_type = 'subscription';
                elseif (in_array($key, $onlybo))
                    $log_type = 'onlybo';
                else
                    $log_type = 'other';
		if($key === 'type2'){
                    $value = (trim($value) === "corrector") ? $value : '-';
                }
                //fetch user type from user table //
                $user_obj = new Ep_User_User();
                $user_type = $user_obj->getUserType($old_data['primary_id']);
                $data = array("user_id" => $old_data['primary_id'], "type" => $user_type, "old_value" => utf8dec($old_data[$key]), "new_value" => utf8dec($value), "log_type" => $log_type, "field" => $key, "updated_by" => $updated_by);
                $userlogs_obj->InsertLogs($data);
                /*echo "inserted: ======>>>";
                echo "Key: $key; old value : $old_data[$key] ;new Value: $value<br /><br />\n";*/
            }
        }
    }
    /* end of added by naseer on 05-11-2015*/

}
