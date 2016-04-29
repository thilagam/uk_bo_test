<?php
//Author:Thilagam
	class UserlistController extends Ep_Controller_Action
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
	        $this->_view->langList=$ep_lang_array;
		}

		public function getListAction()
		{
			$ulObj=new Ep_Userlist_List();
			$list=$ulObj->getList();
			$this->_view->list=$list;
			$this->_view->listCount=count($list);
			$this->_view->render("userlist-list");
		}

		public function getDetailsAction()
		{
			$id = $_REQUEST['id'];
			$uldetobj=new Ep_Userlist_List();
			$details=$uldetobj->getDetails($id);
			//print_r($details);
			$this->_view->userDetails=$details;
			$this->_view->render("userlist-details");
		}

		public function saveDetailsAction()
		{
			$id=$_REQUEST['id'];
			$status=$_REQUEST['status'];
			$uleditobj=new Ep_Userlist_List();
			$result=$uleditobj->saveDetails($id,$status);
			echo $result;

		}
		public function getWritersAction()
		{
			$userType=array("1"=>"Writer","2"=>"Corrector");
			$seniority=array("senior"=>"Senior","junior"=>"Junior");
			$status=array("Active"=>"Active","Inactive"=>"Inactive");
			$this->_view->userType=$userType;
			$this->_view->seniority=$seniority;
			$this->_view->status=$status;
			$this->_view->render('writerslist-list');
		}
		public function loadTableAction()
		{
			$wlObj=new Ep_Userlist_List();
			$userTypeArray=array();
			$userLangArray=array();
			$userSeniorityArray=array();
			$userStatusArray=array();
			if(!empty($_POST['usertype']))
			{
				$userTypeArray=$_POST['usertype'];
			}
			if(!empty($_POST['lang']))
			{
				$userLangArray=$_POST['lang'];
			}
			if(!empty($_POST['seniority']))
			{
				$userSeniorityArray=$_POST['seniority'];
			}
			if(!empty($_POST['status']))
			{
				$userStatusArray=$_POST['status'];
			}
			$wlist=$wlObj->getWritersList($userTypeArray,$userLangArray,$userSeniorityArray,$userStatusArray);
			$i=0;
			while($wlist[$i])
			{
				$wlist[$i]['action']='<button class="btn btn-info btn-lg writerView" data-toggle="modal" data-target="#editModal" onclick="viewWriter('.$wlist[$i]['id'].');">View</button>&nbsp;
				<button  class="btn btn-default btn-lg writerEdit" data-toggle="modal" data-target="#editModal" onclick="editWriter('.$wlist[$i]['id'].');">Edit</button>&nbsp;
				<button class="btn btn-default btn-lg writerClient" data-toggle="modal" data-target="#clientModal" onclick="clientWriter('.$wlist[$i]['id'].')">Clients</button>';
				$i++;
			}
			echo json_encode($wlist);
		}
		

		public function viewWriterAction()
		{
			$id = $_REQUEST['id'];
			$wldetObj=new Ep_Userlist_List();
			$wrDetails = $wldetObj->getWriterDetails($id);
			$this->_view->writerDetails=$wrDetails;
			$this->_view->render('writerslist-details');
		}

		public function editWriterAction()
		{
			$id = $_REQUEST['id'];
			$wldetObj=new Ep_Userlist_List();
			$wrEdit = $wldetObj->getWriterDetails($id);
			$userType=array("1"=>"Writer","2"=>"Corrector");
			$seniority=array("senior"=>"Senior","junior"=>"Junior");
			$status=array("Active"=>"Active","Inactive"=>"Inactive");
			$this->_view->userType=$userType;
			$this->_view->seniority=$seniority;
			$this->_view->status=$status;
			$this->_view->writerEdit=$wrEdit;
			$this->_view->render('writerslist-edit');
		}

		public function saveWriterAction()
		{
			$id=$_REQUEST['id'];
			$data=array(
				'fname'=>$_REQUEST['fname'],
				'lname'=>$_REQUEST['lname'],
				'address'=>$_REQUEST['address'],
				'city'=>$_REQUEST['city'],
				'state'=>$_REQUEST['state'],
				'zipcode'=>$_REQUEST['zipcode'],
				'phone'=>$_REQUEST['phone'],
				'dob'=>date("Y-m-d",strtotime($_REQUEST['dob'])),
				'usertype'=>$_REQUEST['usertype'],
				'seniority'=>$_REQUEST['seniority'],
				'lang'=>$_REQUEST['lang'],
				'status'=>$_REQUEST['status']	
			);	
			$wleditobj= new Ep_Userlist_List();
			$res=$wleditobj->saveWriterDetails($id,$data);
			echo $res;
		}

		public function clientWriterAction()
		{
			$id=$_REQUEST['id'];
			$wlclients = new Ep_Userlist_List();
			$res = $wlclients->getWritersClients($id);
			$clientList = " ";
			if(!empty($res)):
				foreach($res as $res1):
					$clientList .= "<a href='/userlist/client-download?cid=".$res1['user_id']."&id=".$id."' class='clientLink'>".$res1['company_name']."</a></br>";
				endforeach;
			else:
				$clientList .= "No clients available";
			endif;
			echo $clientList;
		}

		public function clientDownloadAction()
		{
			$cid=$_REQUEST['cid'];
			$id=$_REQUEST['id'];
			$wlclientDownload = new Ep_Userlist_List();
			$articles = $wlclientDownload->downloadArticles($cid,$id);
			if(!empty($articles))
			{
				$articlesArray = array();
				for ($i=0; $i < count($articles); $i++) 
				{ 
					$articlesArray[] = FO_ARTICLE.$articles[$i]['article_path'];
				}
				$filename = ASSETS.'writer/writer_articles.zip';
				$zip = $this->create_zip($articlesArray, $filename);
				if($zip) 
				{ 
               		$this->_redirect("/BO/download-files.php?function=downloadarticlezip&fullPath=$filename");
            	}
			}
		}

	public function create_zip($files = array(), $destination = '', $overwrite = true)
    {
		if (file_exists($destination) && !$overwrite) 
		{
	        return false;
	    }
        $valid_files = array();
        if (is_array($files)) 
        {
            foreach ($files as $file) 
            {
                if (file_exists($file)) 
                {
                    $valid_files[] = $file;	
                }
            }
        }
        if (count($valid_files)) 
        {
            $zip = new ZipArchive();
            if ($zip->open($destination, $overwrite ? ZIPARCHIVE::OVERWRITE : ZIPARCHIVE::CREATE) !== true) 
            {
                return false;
            }
            foreach ($valid_files as $file) 
            {
                $zip->addFile($file, basename($file));
            }
            $zip->close();
            return file_exists($destination);
        } 
        else 
        {
            return false;
        }
    }

    public function getQuotesAction()
    {
    	$this->_view->render('quoteslist-list');
    }

    public function loadQuotesAction()
    {
    	$count = $this->_request->getParam('count');
    	$max = $count + 10 ;
    	$min = 0;
    	$qoLoadObj = new Ep_Userlist_List();
    	if($this->_request->getParam('cname'))
    	{
    		$cname = $this->_request->getParam('cname');
    	}
    	else
    	{
    		$cname = " ";
    	}
    	if($this->_request->getParam('cstatus'))
    	{
    		$cstatus = $this->_request->getParam('cstatus');
    		$cstat = explode(",",$cstatus);
    	}
    	else
    	{
    		$cstat = array();
    	}
    	$qoLoadList = $qoLoadObj->getQuotes($min,$max,$cname,$cstat);
    	$qoLoad = $this->quoteArray($qoLoadList);
    	$this->_view->quotesList=$qoLoad;
		$this->_view->render('quoteslist-table');
    }

    public function quoteFilterAction()
    {
    	if($this->_request->getParam('cname'))
    	{
    		$cname = $this->_request->getParam('cname');
    	}
    	else
    	{
    		$cname = " ";
    	}
    	if($this->_request->getParam('cstatus'))
    	{
    		$cstatus = $this->_request->getParam('cstatus');
    		$cstat = explode(",",$cstatus);
    	}
    	else
    	{
    		$cstat = array();
    	}
    	$min = 0;
    	$max = 10;
    	$qoLoadObj = new Ep_Userlist_List();
    	$qoLoadList = $qoLoadObj->getQuotes($min,$max,$cname,$cstat);
    	$qoLoad = $this->quoteArray($qoLoadList);
    	$this->_view->quotesList=$qoLoad;
		$this->_view->render('quoteslist-table');
    }

    public function quoteArray($list)
    {
    	$i = 0;
    	while($list[$i])
		{
			$listFinal[$i]['client']=$list[$i]['company_name'];
			$listFinal[$i]['category']=ucfirst($list[$i]['category']);
			$listFinal[$i]['title']=$list[$i]['title'];
			$listFinal[$i]['created_at']=$list[$i]['created_at'];
			$listFinal[$i]['created_by']=$list[$i]['first_name']." ".$list[$i]['last_name'];
			if($list[$i]['sales_review'] == "validated")
			{
				$listFinal[$i]['status']='Sent';
			}
			else if($list[$i]['sales_review'] == "not_done")
			{
				$listFinal[$i]['status']='Ongoing';
			}
			else if($list[$i]['sales_review'] == "closed")
			{
				$listFinal[$i]['status']='Lost';
			}
			else
			{
				$listFinal[$i]['status']=ucfirst($list[$i]['sales_review']);
			}
			$listFinal[$i]['turn_over']=$list[$i]['final_turnover'];
			$listFinal[$i]['curency']=$list[$i]['sales_suggested_currency'];
			$listFinal[$i]['turn_over']=$list[$i]['final_turnover'];
			$listFinal[$i]['action']='<button class="btn btn-info">View</button>';
			$i++;
		}
		return $listFinal;
    }
}
?>