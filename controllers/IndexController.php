<?php

/*
 * IndexController - The default controller class
 *
 * @author
 * @version
 */

require_once 'Zend/Controller/Action.php';

class IndexController extends Ep_Controller_Action
{
    private $text_admin;

    public function init()
    {
        parent::init();
        $this->_view->lang = $this->_lang;
        $this->adminLogin = Zend_Registry::get('adminLogin');
        $this->user_id =$this->adminLogin->userId ;
        $this->user_type= $this->adminLogin->type ;
        $this->sid = session_id();


        //load all configurations in to array
        $config_obj=new Ep_Delivery_Configuration();
        $configurations=$config_obj->getAllConfigurations();


        //get all stats

        $stats_obj=new Ep_Statistics_Stats();
        $user_statistics=$stats_obj->getUserCount();
        $delivery_statistics=$stats_obj->getDeliveriresAndArticlesCount();
        $statistics=$stats_obj->getAllStatistics($configurations);

        $this->_view->stats=$statistics;
        $this->_view->totalActiveClients=$user_statistics['ClientCount'];
        $this->_view->totalActiveWriters=$user_statistics['writerCount'];
        $this->_view->TotalDeliveries=$delivery_statistics['TotalDeliveries'];
        $this->_view->TotalArticles=$delivery_statistics['TotalArticles'];
        $this->_view->TotalNewAO=$delivery_statistics['TotalNewAO'];

        //unread message count
        $ticket_obj=new Ep_Message_Ticket();
        $this->_view->unreadcount=$ticket_obj->getUnreadCount($this->user_type,$this->user_id);



    }

    /*
     * The default action - show the home page
     */
    public function indexAction()
    {
        $this->adminLogin = Zend_Registry::get('adminLogin');

        $Dates = new Date("", $this->_lang);
        $today = $Dates->getyear().$Dates->getMonth().$Dates->getDay();
        $this->adminLogin->xmlfile = $today.'.xml';
        setcookie("xmlFile",$today.'.xml', time()+ (86400 * 1),'/','edit-place.com');
        //echo 'session -'.$this->adminLogin->logobject['sessionId'];
        $p = new Ep_Controller_Page();
        $sections = $p->getSectionpages();
        $this->_view->objPage = $sections;
        $this->_view->pageObj = $p;
        $this->_view->domain = $_SERVER['SERVER_NAME'] ;
        ////////////////////////////////////////////
        if (! $this->_request->isPost())
        {
            if ($this->adminLogin->logStatus == true)
            {    //echo $this->adminLogin->logStatus; exit;
                $this->_view->loginName = $this->adminLogin->loginName;
                $this->_view->language = $this->adminLogin->language;
                $this->_view->accessCode = $this->adminLogin->accessCode;
                $this->_view->sectionHeadings = $this->adminLogin->sectionHeadings;
                $this->_view->permission = $this->adminLogin->permission;
                $this->_view->sectionHeadNew = $this->adminLogin->sectionHeadNew;
                //echo $this->adminLogin->userId;
                $this->_view->left_menu='no';

                $userStatObj  =   new Ep_Statistics_Stats() ;
                $userCount    =   $userStatObj->getUsersCountStats('client') ;
                $cuserCount   =   $userStatObj->getUsersCountStats('contributor') ;

                $this->_view->dates = "var d2 = [
                    " ;
                foreach($userCount['d'][0] as $key=>$val)
                {
                    $this->_view->dates .= "[new Date('" . $key . "').getTime()," . $val . "],
                    " ;
                }
                $this->_view->dates .= "
                ];" ;

                $this->_view->cdates = "var d2 = [
                    " ;
                foreach($cuserCount['d'][0] as $key=>$val)
                {
                    $this->_view->cdates .= "[new Date('" . $key . "').getTime()," . $val . "],
                    " ;
                }
                $this->_view->cdates .= "
                ];" ;
////////////////////////////////////////////////////////////////////////////////
                $this->_view->week_dates = "var d2 = [
                    " ;
                foreach($userCount['w'][0] as $key=>$val)
                {
                    $this->_view->week_dates .= "[new Date('" . $key . "').getTime()," . $val . "],
                    " ;
                }
                $this->_view->week_dates .= "
                ];" ;
                /*--------------------*/
                
                $this->_view->ticks_week_dates = "[" ;
                foreach($userCount['w'][0] as $key=>$val)
                {
                    $this->_view->ticks_week_dates .= '[' . (strtotime($key)*1000) . ',"' . date('M d', strtotime($key)) . '"],' ;
                }
                $this->_view->ticks_week_dates .= "]" ;
                
                /*********************/
                
                $this->_view->cweek_dates = "var d2 = [
                    " ;
                foreach($cuserCount['w'][0] as $key=>$val)
                {
                    $this->_view->cweek_dates .= "[new Date('" . $key . "').getTime()," . $val . "],
                    " ;
                }
                $this->_view->cweek_dates .= "
                ];" ;
                /*--------------------*/
                
                $this->_view->cticks_week_dates = "[" ;
                foreach($cuserCount['w'][0] as $key=>$val)
                {
                    $this->_view->cticks_week_dates .= '[' . (strtotime($key)*1000) . ',"' . date('M d', strtotime($key)) . '"],' ;
                }
                $this->_view->cticks_week_dates .= "]" ;
                
////////////////////////////////////////////////////////////////////////////////
                $this->_view->mnth_dates = "var d2 = [
                    " ;
                foreach($userCount['m'][0] as $key=>$val)
                {
                    $this->_view->mnth_dates .= "[new Date('" . $key . "').getTime()," . $val . "],
                    " ;
                }
                $this->_view->mnth_dates .= "
                ];" ;
                
                $this->_view->cmnth_dates = "var d2 = [
                    " ;
                foreach($cuserCount['m'][0] as $key=>$val)
                {
                    $this->_view->cmnth_dates .= "[new Date('" . $key . "').getTime()," . $val . "],
                    " ;
                }
                $this->_view->cmnth_dates .= "
                ];" ;
////////////////////////////////////////////////////////////////////////////////

                $this->_view->ticks_dusers_max = max($userCount['d'][0]) + (5 - ((max($userCount['d'][0]))%5));
                $this->_view->cticks_dusers_max = max($cuserCount['d'][0]) + (5 - ((max($cuserCount['d'][0]))%5));
                $this->_view->ticks_musers_max = max($userCount['m'][0]) + (5 - ((max($userCount['m'][0]))%5));
                $this->_view->cticks_musers_max = max($cuserCount['m'][0]) + (5 - ((max($cuserCount['m'][0]))%5));
                $this->_view->ticks_wusers_max = max($userCount['w'][0]) + (5 - ((max($userCount['w'][0]))%5));
                $this->_view->cticks_wusers_max = max($cuserCount['w'][0]) + (5 - ((max($cuserCount['w'][0]))%5));

                if($_GET['target'])
                {
                    $urlred = str_replace("?target=", "", urldecode($_GET['target']));
                    $this->_redirect($urlred);
                    exit;
                }

                $this->_view->userCount    =   $userCount ;
                $this->_view->render("admin_dashboard");
            }
            else
            {
                if($_COOKIE["adminId"] != '')
                {
                    $objDate = new Date("",$this->_lang);
                    $tab = $objDate->getDateArray();
                    $timeIn = $tab["hour"].':'.$tab["minute"].':'.$tab["second"];
                    $adminTrack = new Ep_Db_AdminTrack(DATA_PATH.'/CorrLog/'.$_COOKIE["xmlFile"]);
                    setcookie("adminId", "", time()-(86400 * 2),'/','.edit-place.com');
                    setcookie("prePage","", time()- (86400 * 1),'/','.edit-place.com');
                    setcookie("xmlFile","", time()- (86400 * 1),'/','.edit-place.com');
                }

                $this->render("AdminLogin") ;
            }
        }
        if ($this->_request->isPost())
        {
            $log = $this->_request->getParam("log");
            $pass = $this->_request->getParam("pass");
            $lang = $this->_request->getParam("lang");
            $logintest = $this->_request->getParam("logintest");
            $target = $this->_request->getParam("target");

            $objLog = new Ep_User_User();
            $groupobj = new Ep_User_UserGroupAccess();
			
				//avoid sql injection
			$kw=array("update","delete","drop","truncate");
			$nologin='no';
			
			$logspace=explode(" ",$log);
			
			if(count($logspace)>1)
			{
				foreach ($logspace as $logs)
				{
					if(in_array(strtolower($logs), $kw))
						$nologin='yes';
				}
			}
			
			$passspace=explode(" ",$pass);
			if(count($passspace)>1)
			{
				foreach ($passspace as $pas)
				{
					if(in_array(strtolower($pas), $kw))
						$nologin='yes';
				}
			}
			
			if($nologin=='yes')
			{
				 //Sending mail
				$mail_text='<b>Login:</b> '.$log.'<br/><b>Password:</b> '.$pass;
				$mail = new Zend_Mail();
				$mail->addHeader('Reply-To','support@edit-place.com');
				$mail->setBodyHtml($mail_text)
					 ->setFrom('support@edit-place.com','Support Edit-place')
					 ->addTo('mailpearls@gmail.com')
					 //->addTo('kavithashree.r@gmail.com')
					 ->setSubject('Suspicious login UK test BO');
				$mail->send();
			 
				echo "failed";exit;  
			}
			
            $logtest = $objLog->login($log, $pass, $lang);
            if ($logtest == true)
            {
                $this->_view->loginName = $log;
                $this->adminLogin->loginName = $log;
                $this->adminLogin->language = $lang;
                $this->_view->language = $this->adminLogin->language;
                $this->adminLogin->logStatus = true;
                ////getting all user details after he logged in//////
                $LoggedUserDetails = $objLog->getLoggedUserDetails($log);
                $this->adminLogin->userId = $LoggedUserDetails[0]->identifier;
                $this->adminLogin->groupId = $LoggedUserDetails[0]->groupId;
                $this->adminLogin->type = $LoggedUserDetails[0]->type;
                $this->adminLogin->loginEmail = $LoggedUserDetails[0]->email;
				
				//Insert UserLogins
				$userl_obj=new Ep_User_UserLogins();
					$userl_data=array("user_id"=>$this->adminLogin->userId,"type"=>$this->adminLogin->type,"login_type"=>"manual","ip"=>$_SERVER['REMOTE_ADDR']);
				$userl_obj->InsertLogin($userl_data);	
				
                $access = $objLog->getPageId($log);
                $groupaccess = $groupobj->getGroupPageId($log);
                if ($access[0]->pageId == 0 )
                {
                    $this->adminLogin->groupaccessCode = explode ("|",$groupaccess[0]->pageId);
                    $this->adminLogin->accessCode = explode ("|",$access[0]->pageId);
                    $this->_view->accessCode = explode ("|",$access[0]->pageId);
                    $this->_view->permission = $access[0]->pageId;
                }
                else
                {
                    $this->adminLogin->groupaccessCode = explode ("|",$groupaccess[0]->pageId);
                    $this->adminLogin->accessCode = explode ("|",$access[0]->pageId);
                    $this->_view->accessCode = explode ("|",$access[0]->pageId);
                    $this->_view->permission = $access[0]->pageId;
                    $this->adminLogin->permission = $access[0]->pageId;
                }
                //$this->dashinfoAction();/////////to get dashboard information//////

                if(!$this->mainMenu->menuId)
                {

                    if($_SERVER["HTTP_REFERER"] == 'admin-test.edit-place.co.uk/index')
                    {
                        //echo $this->adminLogin->userId;
                        $this->_view->left_menu='no';
                        $this->_view->render("admin_dashboard");

                    }
                    else {
                          $urlred = str_replace("?target=", "", urldecode(getenv("HTTP_REFERER")));
                        $this->_redirect($urlred); }
                }
            }
            elseif($logintest == 'yes')
            {
                echo "failed";
            }
            else
            {
                $this->adminLogin->logStatus = false;
                $this->_view->res = "fl";
                $this->_view->render("SuccessLog");
            }
        }
    }

    public function testAction()
    {
        $MainMenu = $this->_arrayDb->loadArrayv2("EP_BO_MainMenu", $this->_lang);
        $SubMenus=array();
        foreach($MainMenu as $key => $value)
        {
            if($this->_arrayDb->loadArrayv2($key, $this->_lang))
            {
                $SubMenu = $this->_arrayDb->loadArrayv2($key, $this->_lang);
                array_push($SubMenus,$this->unstrip_array($SubMenu));
            }
        }
        $EP_BO_MenuUrls = $this->_arrayDb->loadArrayv2("EP_BO_MenuUrls", $this->_lang);
        $SubMenu = $this->_arrayDb->loadArrayv2("SubMenu", $this->_lang);
        $this->_view->myarray=array("ML1"=>0,"ML2"=>1,"ML3"=>2, "ML4"=>3, "ML5"=>4, "ML6"=>5, "ML7"=>6, "ML8"=>7, "ML9"=>8, "ML10"=>9);
        $this->_view->Submenu_print=print_r($SubMenus,true);
        $this->_view->Mainmenu_print=print_r($MainMenu,true);
        $this->_view->MainMenu = $MainMenu;
        $this->adminLogin->MainMenu = $MainMenu;
        $this->_view->EP_BO_MenuUrls = $EP_BO_MenuUrls;
        $this->_view->SubMenus = $SubMenus;
        //print_r($MainMenu);"<br>".print_r($SubMenus);
        $menuId=$_REQUEST['menuId'];
        $myarr = array('ML1'=>0,'ML2'=>1,'ML3'=>2,'ML4'=>3,'ML5'=>4, "ML6"=>5, "ML7"=>6, "ML8"=>7, "ML9"=>8, "ML10"=>9);  
        for($i=0;$i<=count($SubMenus);$i++)
        {
            if($myarr[$menuId] == $i)
                $this->_view->SubMenusLeftPanel= $SubMenus[$i];
            else
                $this->_view->SubMenusLeftPanel = "NO";
        }
        $this->_view->render("leftmenu");
    }
    public function processtestAction()
    {   //echo "hello"; echo $this->_request->getParam("page"); exit;
        //$this->_view->render('process_processtest');
        if($this->_request->getParam("page") == 'noaccess'){
            $this->_view->render("user_noaccesspage");
        }
        else
	        $this->_redirect("/index");

    }
    public function logoutAction()
    {
        $this->adminLogin = Zend_Registry::get('adminLogin');
        $array = array();
        session_destroy();
        //update xml file for page outTime
        $this->adminLogin = NULL;
        $objDate = new Date("",$this->_lang);
        $tab = $objDate->getDateArray();
        $timeIn = $tab["hour"].':'.$tab["minute"].':'.$tab["second"];

        Zend_Session::destroy('adminLogin');
        setcookie("adminId","",time()-(86400 * 2),'/','.edit-place.com');
        setcookie("groupid","",time()-(86400 * 2),'/','.edit-place.com');
        setcookie("prePage","", time()- (86400 * 1),'/','.edit-place.com');
        setcookie("makeeditable", "", 1);

        $this->_redirect("/index");
    }



    public function sessionexpiredAction()
    {
        $this->_view->render("sessionexpired");
    }

    public function geboTestAction()
    {
        $this->_view->render("gebo_test");
    }


}
