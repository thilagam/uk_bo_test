<?php
/**
 * Followup for the SEO, Tech and Prod Missions
 * @version 1.0
*/
class FollowupController extends Ep_Controller_Action
{
	var $url='';
	var $mail_from = "work@edit-place.com";
	/* Setting paths and arrays in global variables */
	public function init()
	{
		parent::init();	
		$this->_view->fo_path = $this->_config->path->fo_path;
		$this->url = $this->_config->path->bo_base_path;
		$this->quote_documents_path=APP_PATH_ROOT.$this->_config->path->quote_documents;
		$this->mission_documents_path=APP_PATH_ROOT.$this->_config->path->mission_documents;
		$this->task_documents_path=APP_PATH_ROOT.$this->_config->path->task_documents;
		//$this->_view->ebookerid = $this->ebookerid = $this->_config->wp->ebookerid;
		$this->_view->ebookerid = $this->ebookerid = $this->configval['ebooker_id'];
		$this->product_array=array(
    							"redaction"=>"Writing",
								"translation"=>"Translation",
								"autre"=>"Other",
								"proofreading"=>"Correction",
								"seo_audit"=>"SEO Audit",
								"smo_audit"=>"SMO Audit",
        						);
		$this->producttype_array=array(
    							"article_de_blog"=>"Article de blog",
								"descriptif_produit"=>"Desc.Produit",
								"article_seo"=>"Article SEO",
								"guide"=>"Guide",
								"news"=>"News",
								"autre"=>"Others"
        						);
		$this->_view->tempo_duration_array = $this->duration_array=array(
						"days"=>"Days",
						"week"=>"Week",
						"month"=>"Month",
						"year"=>"Year"
					);	
		$this->adminLogin = Zend_Registry::get('adminLogin');
		$this->_view->tempo_duration_array=$this->duration_array=array(
							"days"=>"Days",
							"week"=>"Week",
							"month"=>"Month",
							"year"=>"Year"
						);		
		$this->_view->tempo_array=$this->tempo_array=array(
							"max"=>"Max",
							"fix"=>"Fix"
						);	
		$this->_view->volume_option_array=$this->volume_option_array=array(
							"every"=>"Every",
							"within"=>"Within"
						);	
		$this->_view->user_type= $this->adminLogin->type ;
		$this->_view->userId = $this->adminLogin->userId;
		Zend_Loader::loadClass('Ep_Ebookers_Stencils');
	}
	/* General function to get the name from XML file based on filetype */
	function getCustomName($type,$name)
	{
		$categories_array = $this->_arrayDb->loadArrayv2($type, $this->_lang);
		return $categories_array[$name];
	}
	/* Tech followup page */
	function techAction()
	{
		$request = $this->_request->getParams();
		$id = $request['cmid'];
		
		$contract_obj = new Ep_Quote_Quotecontract();
		$res = $contract_obj->getContractTechMissions(array('cmid'=>$id));
		$client_obj = new Ep_Quote_Client();
		/* Setting values in the view array and sending it to view */ 
		if($res)
		{
			$view = array();
			if($res[0]['title'])
			$view['title'] = $res[0]['title'];
			else
			$view['title'] = 'New Tech Mission';
			if($res[0]['before_prod']=='yes')
			$view['priority'] = 'blocker';
			else
			$view['priority'] = 'non blocker';
		/* 	if($res[0]['from_contract'])
			$view['chargeble'] = 'free';
			else
			$view['chargeble'] = 'chargable';
			$view['percentage'] = '50';
			*/
			/* Adding turnover with product of team fee and packs if package is team */
			/* If free mission Turnover will be Zero and showed as Free */
			if($res[0]['free_mission']=="yes")
				$view['turnover'] = "Free";
			elseif($res[0]['package']=='team')
			$view['turnover'] = $res[0]['turnover']+$res[0]['team_fee']*$res[0]['team_packs'];
			else
			$view['turnover'] = $res[0]['turnover'];
			$view['production_cost'] = $res[0]['cost'];
			$view['contract_name'] = $res[0]['contractname'];
			$view['contract_files'] = $res[0]['contractfilepaths'];
			$view['contract_id'] = $res[0]['quotecontractid'];
			$view['mission_id'] = $res[0]['tmid'];
			$view['contract_date'] = date('d M Y',strtotime($res[0]['expected_launch_date']))." - ".date('d M Y',strtotime($res[0]['expected_end_date']));
			$view['tech_team'] = $res[0]['assigned_to'];
			$view['cm_status'] = $res[0]['cm_status'];
			$userDetails = $client_obj->getQuoteUserDetails($res[0]['assigned_to']);
			if($res[0]['assigned_to'])
			{
			$userDetails = $client_obj->getQuoteUserDetails($res[0]['assigned_to']);
			$view['tech_name'] = $userDetails[0]['first_name']." ".$userDetails[0]['last_name'];
			}
			else
			$view['tech_name'] = "";
			$quote_obj = new Ep_Quote_Quotes();
			/* Fetching quote details */
			$quote_details = $quote_obj->getQuoteDetails($res[0]['quoteid']);
			$view['client_id'] = $quote_details[0]['client_id'];
			$view['cname'] = $quote_details[0]['company_name'];
			$view['cano'] = $quote_details[0]['ca_number'];
			$view['client_code'] = $quote_details[0]['client_code'];
			$view['category_name'] = $this->getCategoryName($quote_details[0]['category']);
			$view['cmid'] = $id;
			$userDetails = $client_obj->getQuoteUserDetails($quote_details[0]['created_by']);
			$view['sales_owner'] = $userDetails[0]['first_name']." ".$userDetails[0]['last_name'];
			$view['mailto'] = $userDetails[0]['email'];
			$view['sales_id'] = $quote_details[0]['created_by'];
			$view['telphone'] = $userDetails[0]['phone_number'];
			$view['city'] = $userDetails[0]['city'];
			$view['currency'] =  $res[0]['sales_suggested_currency'];
			$view['assigned'] = $res[0]['assigned_to'];
			$view['quote_signed_at'] = date('d M Y',strtotime($quote_details[0]['signed_at']));
			$view['from_date'] = date('d M Y',strtotime($res[0]['assigned_at']));
			if($res[0]['delivery_option']=='hours')
			$no_days = ceil($res[0]['delivery_time']/24);
			else
			$no_days = $res[0]['delivery_time'];
			/* to date from contractmissions(assinged date) plus number of days from techmissions */
			$view['to_date'] = date('d M Y',strtotime($res[0]['assigned_at']."+ $no_days days"));
			/*tech tempo define*/
			$view['volume'] = $res[0]['volume'];
			if($res[0]['oneshot'] =="yes")
			{
				$view['tempo_text'] ="<h3>One shot mission : ".$no_days." ".$this->duration_array[$res[0]['delivery_option']]." </h3>";	
			}
			elseif($res[0]['oneshot'] =="no")
			{
				//$volume=$res[0]['volume_max'] ? $res[0]['volume_max'] : $res[0]['volume'];
				$view['tempo_text'] = "<h3> Recurring :   ".$res[0]['volume_max']." (".$this->_view->tempo_array[$res[0]['tempo']].") ".$this->producttype_array[$res[0]['product_type']]." ".$this->_view->volume_option_array[$res[0]['delivery_volume_option']].' '.$res[0]['tempo_length'].' '.$this->duration_array[$res[0]['tempo_length_option']]."</h3>";
			}
			else
			$view['tempo_text'] = "";
		
			$user_obj=new Ep_User_User();
			
			$user_info = $user_obj->getAllUsersDetails($quote_details[0]['created_by']);
				
			if($user_info)
			$quote_details[0]['created_name'] = $user_info[0]['first_name']." ".$user_info[0]['last_name'];
			else
			$quote_details[0]['created_name'] = "";
			$quote_details[0]['created_time'] = time_ago($quote_details[0]['created_at']);
			
			$this->_view->quote_details = $quote_details[0];
			/* Comments and files from quotes, missions, contracts and contractmissions */
			$comment = array();
			if($quote_details[0]['sales_comment'])
			{
				$comments['created_by'] = $quote_details[0]['created_by'];
				$comments['created_name'] = $quote_details[0]['created_name'];
				$comments['created_time'] = $quote_details[0]['created_time'];
				$comments['comment'] = $quote_details[0]['sales_comment'];
				$comments['created_at'] = $quote_details[0]['created_at'];
				$comment[] = $comments;
			}
			
			if($res[0]['comments'])
			{
				$comments['comment'] = $res[0]['comments'];
				$comments['created_by'] = $res[0]['created_by'];
				
				$user_info = $user_obj->getAllUsersDetails($res[0]['created_by']);
				
				if($user_info)
				$comments['created_name'] = $user_info[0]['first_name']." ".$user_info[0]['last_name'];
				else
				$comments['created_name'] = "";
				$comments['created_time'] = time_ago($res[0]['created_at']);
				$comments['created_at'] = $res[0]['created_at'];
				$comment[] = $comments;
			}
			
			$contractcomments = array();
			if($res[0]['contractcomment'])
			{
				$comments['comment'] = $res[0]['contractcomment'];
				$comments['created_by'] = $res[0]['sales_creator_id'];
				
				$user_info = $user_obj->getAllUsersDetails($res[0]['sales_creator_id']);
				
				if($user_info)
				$comments['created_name'] = $user_info[0]['first_name']." ".$user_info[0]['last_name'];
				else
				$comments['created_name'] = "";
				$comments['created_time'] = time_ago($res[0]['qctime']);
				$comments['created_at'] = $res[0]['qctime'];
				$comment[] = $comments;
			}
			
			$contractmissioncomments = array();
			if($res[0]['cmcomment'])
			{
				$comments['comment'] = $res[0]['cmcomment'];
				if($res[0]['updated_by'])
				{
					$comments['created_by'] = $res[0]['updated_by'];
					$user_info = $user_obj->getAllUsersDetails($res[0]['updated_by']);
				}
				else
				{
					$comments['created_by'] = $res[0]['assigned_by'];
					$user_info = $user_obj->getAllUsersDetails($res[0]['assigned_by']);
				}
				
				if($user_info)
				$comments['created_name'] = $user_info[0]['first_name']." ".$user_info[0]['last_name'];
				else
				$comments['created_name'] = "";
				$comments['created_time'] = time_ago($res[0]['updated_at']);
				$comments['created_at'] = $res[0]['updated_at'];
				$comment[] = $comments;
			}
			
			//$this->_view->contractmissioncomments = $contractmissioncomments;
			$this->_view->comments = $this->sortTimewise($comment);
			$this->_view->files = "";
			$this->_view->quotefiles = "";
			$files = "";
			$quotefiles = "";
			if($quote_details[0]['documents_path'])
			{
				$exploded_file_paths = explode("|",$quote_details[0]['documents_path']);
				$exploded_file_names = explode("|",$quote_details[0]['documents_name']);
				
				$k=0;
				foreach($exploded_file_paths as $row)
				{
					$file_path=$this->quote_documents_path.$row;
					if(file_exists($file_path) && !is_dir($file_path))
					{
						$fname = $exploded_file_names[$k];
						if($fname=="")
							$fname = basename($row);
						$ofilename = pathinfo($file_path);
						/* <span class="deletequote" rel="'.$k.'_'.$quote_details[0]['identifier'].'"> <i class="icon-adt_trash"></i></span> */
						$quotefiles .= '<tr><td width="30%">'.$fname.'</td><td width="35%">'.substr($ofilename['filename'],0,-3).".".$ofilename['extension'].'</td><td width="20%">'.formatSizeUnits(filesize($file_path)).'</td><td>Sales</td><td align="center" width="15%"><a href="/quote/download-document?type=quote&quote_id='.$quote_details[0]['identifier'].'&index='.$k.'"><i style="margin-right:5px" class="splashy-download"></i></a><td></tr>';	
					}
					$k++;
				}
				
			}
			$this->_view->quotefiles = $quotefiles;
			if($res[0]['documents_path'])
			{
				$exploded_file_paths = explode("|",$res[0]['documents_path']);
				$exploded_file_names = explode("|",$res[0]['documents_name']);
				
				$k=0;
				foreach($exploded_file_paths as $row)
				{
					$file_path=$this->mission_documents_path.$row;
					if(file_exists($file_path) && !is_dir($file_path))
					{
						$fname = $exploded_file_names[$k];
						if($fname=="")
							$fname = basename($row);
						$ofilename = pathinfo($file_path);
						$files .= '<tr><td width="30%">'.$fname.'</td><td width="35%">'.substr($ofilename['filename'],0,-3).".".$ofilename['extension'].'</td><td width="20%">'.formatSizeUnits(filesize($file_path)).'</td><td>Tech</td><td align="center" width="15%"><a href="/quote/download-document?type=tech_mission&mission_id='.$res[0]['tmid'].'&index='.$k.'"><i style="margin-right:5px" class="splashy-download"></i></a><td></tr>';	
					}
					$k++;
				}
				
			}
			$this->_view->files = $files;
			$this->_view->viewarray = $view;
			//echo "<pre>"; print_r($view);exit;
			//Fetching Logs
			$log_obj = new Ep_Quote_QuotesLog();
			$search = array('contract_id'=>$res[0]['quotecontractid'],'mission_id'=>$res[0]['tmid'],'mission_type'=>'tech\',\'task_tech','time'=>'task_added\',\'new_tech_mission');
			$this->_view->logs = $log_obj->getLogs($search);
		
			//Fetching Tasks
			$task_obj = new Ep_Quote_Task();
			$this->_view->tasks = $task_obj->getTasks(array('cmid'=>$id));
		
			$this->render('tech-followup');
		}
		else
			$this->_redirect('contractmission/missions-list');
	}
	
	// Staff Followup for staff missions
	function staffAction()
	{
		$request = $this->_request->getParams();
		$id = $request['cmid'];
		$contract_obj = new Ep_Quote_Quotecontract();
		$res = $contract_obj->getStaffMissions(array('cmid'=>$id));
		$client_obj = new Ep_Quote_Client();
		if($res)
		{
			$view = array();
			if($res[0]['title'])
			$view['title'] = $res[0]['title'];
			else
			$view['title'] = 'New Staff Mission';
			if($res[0]['before_prod']=='yes')
			$view['priority'] = 'blocker';
			else
			$view['priority'] = 'non blocker';
		/* 	if($res[0]['from_contract'])
			$view['chargeble'] = 'free';
			else
			$view['chargeble'] = 'chargable';
			$view['percentage'] = '50';
			*/
			$view['turnover'] = $res[0]['turnover'];
			$view['production_cost'] = $res[0]['cost'];
			$view['contract_name'] = $res[0]['contractname'];
			$view['contract_files'] = $res[0]['contractfilepaths'];
			$view['contract_id'] = $res[0]['contract_id'];
			$view['mission_id'] = $res[0]['staff_missionId'];
			$view['contract_date'] = date('d M Y',strtotime($res[0]['expected_launch_date']))." - ".date('d M Y',strtotime($res[0]['expected_end_date']));
			$view['staff_team'] = $res[0]['assigned_to'];
			$view['cm_status'] = $res[0]['cm_status'];
			if($res[0]['assigned_to'])
			{
			$userDetails = $client_obj->getQuoteUserDetails($res[0]['assigned_to']);
			$view['staff_name'] = $userDetails[0]['first_name']." ".$userDetails[0]['last_name'];
			}
			else
			$view['staff_name'] = "";
			$quote_obj = new Ep_Quote_Quotes();
			$quote_details = $quote_obj->getQuoteDetails($res[0]['quoteid']);
			$view['client_id'] = $quote_details[0]['client_id'];
			$view['cname'] = $quote_details[0]['company_name'];
			$view['cano'] = $quote_details[0]['ca_number'];
			$view['client_code'] = $quote_details[0]['client_code'];
			$view['category_name'] = $this->getCategoryName($quote_details[0]['category']);
			$view['cmid'] = $id;
			$userDetails = $client_obj->getQuoteUserDetails($quote_details[0]['created_by']);
			$view['sales_owner'] = $userDetails[0]['first_name']." ".$userDetails[0]['last_name'];
			$view['mailto'] = $userDetails[0]['email'];
			$view['sales_id'] = $quote_details[0]['created_by'];
			$view['telphone'] = $userDetails[0]['phone_number'];
			$view['city'] = $userDetails[0]['city'];
			$view['currency'] =  $quote_details[0]['sales_suggested_currency'];
			$view['assigned'] = $res[0]['assigned_to'];
			$view['quote_signed_at'] = date('d M Y',strtotime($quote_details[0]['signed_at']));
			$view['from_date'] = date('d M Y',strtotime($res[0]['assigned_at']));
			if($res[0]['delivery_option']=='hours')
			$no_days = ceil($res[0]['delivery_time']/24);
			else
			$no_days = $res[0]['delivery_time'];
			$view['to_date'] = date('d M Y',strtotime($res[0]['assigned_at']."+ $no_days days"));
			$user_obj=new Ep_User_User();
			$user_info = $user_obj->getAllUsersDetails($quote_details[0]['created_by']);
			if($user_info)
			$quote_details[0]['created_name'] = $user_info[0]['first_name']." ".$user_info[0]['last_name'];
			else
			$quote_details[0]['created_name'] = "";
			$quote_details[0]['created_time'] = time_ago($quote_details[0]['created_at']);
			$this->_view->quote_details = $quote_details[0];
			/* Comments from Quote, contract and while assigning */
			$comment = array();
			if($quote_details[0]['sales_comment'])
			{
				$comments['created_by'] = $quote_details[0]['created_by'];
				$comments['created_name'] = $quote_details[0]['created_name'];
				$comments['created_time'] = $quote_details[0]['created_time'];
				$comments['comment'] = $quote_details[0]['sales_comment'];
				$comments['created_at'] = $quote_details[0]['created_at'];
				$comment[] = $comments;
			}
			if($res[0]['comments'])
			{
				$comments['comment'] = $res[0]['comments'];
				$comments['created_by'] = $res[0]['created_by'];
				$user_info = $user_obj->getAllUsersDetails($res[0]['created_by']);
				if($user_info)
				$comments['created_name'] = $user_info[0]['first_name']." ".$user_info[0]['last_name'];
				else
				$comments['created_name'] = "";
				$comments['created_time'] = time_ago($res[0]['created_at']);
				$comments['created_at'] = $res[0]['created_at'];
				$comment[] = $comments;
			}
			$contractcomments = array();
			if($res[0]['contractcomment'])
			{
				$comments['comment'] = $res[0]['contractcomment'];
				$comments['created_by'] = $res[0]['sales_creator_id'];
				$user_info = $user_obj->getAllUsersDetails($res[0]['sales_creator_id']);
				if($user_info)
				$comments['created_name'] = $user_info[0]['first_name']." ".$user_info[0]['last_name'];
				else
				$comments['created_name'] = "";
				$comments['created_time'] = time_ago($res[0]['qctime']);
				$comments['created_at'] = $res[0]['qctime'];
				$comment[] = $comments;
			}
			$contractmissioncomments = array();
			if($res[0]['cmcomment'])
			{
				$comments['comment'] = $res[0]['cmcomment'];
				if($res[0]['updated_by'])
				{
					$comments['created_by'] = $res[0]['updated_by'];
					$user_info = $user_obj->getAllUsersDetails($res[0]['updated_by']);
				}
				else
				{
					$comments['created_by'] = $res[0]['assigned_by'];
					$user_info = $user_obj->getAllUsersDetails($res[0]['assigned_by']);
				}
				if($user_info)
				$comments['created_name'] = $user_info[0]['first_name']." ".$user_info[0]['last_name'];
				else
				$comments['created_name'] = "";
				$comments['created_time'] = time_ago($res[0]['updated_at']);
				$comments['created_at'] = $res[0]['updated_at'];
				$comment[] = $comments;
			}
			//$this->_view->contractmissioncomments = $contractmissioncomments;
			$this->_view->comments = $this->sortTimewise($comment);
			$this->_view->files = "";
			$this->_view->quotefiles = "";
			$files = "";
			$quotefiles = "";
			if($quote_details[0]['documents_path'])
			{
				$exploded_file_paths = explode("|",$quote_details[0]['documents_path']);
				$exploded_file_names = explode("|",$quote_details[0]['documents_name']);
				$k=0;
				foreach($exploded_file_paths as $row)
				{
					$file_path=$this->quote_documents_path.$row;
					if(file_exists($file_path) && !is_dir($file_path))
					{
						$fname = $exploded_file_names[$k];
						if($fname=="")
							$fname = basename($row);
						$ofilename = pathinfo($file_path);
						/* <span class="deletequote" rel="'.$k.'_'.$quote_details[0]['identifier'].'"> <i class="icon-adt_trash"></i></span> */
						$quotefiles .= '<tr><td width="30%">'.$fname.'</td><td width="35%">'.substr($ofilename['filename'],0,-3).".".$ofilename['extension'].'</td><td width="20%">'.formatSizeUnits(filesize($file_path)).'</td><td>Sales</td><td align="center" width="15%"><a href="/quote/download-document?type=quote&quote_id='.$quote_details[0]['identifier'].'&index='.$k.'"><i style="margin-right:5px" class="splashy-download"></i></a><td></tr>';	
					}
					$k++;
				}
			}
			$this->_view->quotefiles = $quotefiles;
			if($res[0]['documents_path'])
			{
				$exploded_file_paths = explode("|",$res[0]['documents_path']);
				$exploded_file_names = explode("|",$res[0]['documents_name']);
				$k=0;
				foreach($exploded_file_paths as $row)
				{
					$file_path=$this->mission_documents_path.$row;
					if(file_exists($file_path) && !is_dir($file_path))
					{
						$fname = $exploded_file_names[$k];
						if($fname=="")
							$fname = basename($row);
						$ofilename = pathinfo($file_path);
						$files .= '<tr><td width="30%">'.$fname.'</td><td width="35%">'.substr($ofilename['filename'],0,-3).".".$ofilename['extension'].'</td><td width="20%">'.formatSizeUnits(filesize($file_path)).'</td><td>Staff</td><td align="center" width="15%"><a href="/quote/download-document?type=staff_mission&mission_id='.$res[0]['staff_missionId'].'&index='.$k.'"><i style="margin-right:5px" class="splashy-download"></i></a><td></tr>';	
					}
					$k++;
				}
			}
			$this->_view->files = $files;
			$this->_view->viewarray = $view;
			//Fetching Logs
			$log_obj = new Ep_Quote_QuotesLog();
			$search = array('contract_id'=>$res[0]['quotecontractid'],'mission_id'=>$res[0]['staff_missionId'],'mission_type'=>'staff\',\'task_staff','time'=>'task_added\',\'new_staff_mission');
			$this->_view->logs = $log_obj->getLogs($search);
			//Fetching Tasks
			$task_obj = new Ep_Quote_Task();
			$this->_view->tasks = $task_obj->getTasks(array('cmid'=>$id));
			$this->render('staff-followup');
		}
		else
			$this->_redirect('contractmission/missions-list');
	}
	// Sales mission Followup
	function salesAction()
	{
		$request = $this->_request->getParams();
		$id = $request['cmid'];
		$contract_obj = new Ep_Quote_Quotecontract();
		$res = $contract_obj->getSalesMissions(array('cmid'=>$id));
		$client_obj = new Ep_Quote_Client();
		if($res)
		{
			$view = array();
			$view['title'] = $res[0]['sales_title'];
			$view['priority'] = 'non blocker';
			$view['turnover'] = 0;
			$view['production_cost'] = 0;
			$view['contract_name'] = $res[0]['contractname'];
			$view['contract_files'] = $res[0]['contractfilepaths'];
			$view['contract_id'] = $res[0]['contract_id'];
			$view['mission_id'] = $res[0]['contractmissionid'];
			$view['contract_date'] = date('d M Y',strtotime($res[0]['expected_launch_date']))." - ".date('d M Y',strtotime($res[0]['expected_end_date']));
			$view['staff_team'] = $res[0]['assigned_to'];
			$view['cm_status'] = $res[0]['cm_status'];
			if($res[0]['assigned_to'])
			{
			$userDetails = $client_obj->getQuoteUserDetails($res[0]['assigned_to']);
			$view['staff_name'] = $userDetails[0]['first_name']." ".$userDetails[0]['last_name'];
			}
			else
			$view['staff_name'] = "";
			$quote_obj = new Ep_Quote_Quotes();
			$quote_details = $quote_obj->getQuoteDetails($res[0]['quoteid']);
			$view['client_id'] = $quote_details[0]['client_id'];
			$view['cname'] = $quote_details[0]['company_name'];
			$view['cano'] = $quote_details[0]['ca_number'];
			$view['client_code'] = $quote_details[0]['client_code'];
			$view['category_name'] = $this->getCategoryName($quote_details[0]['category']);
			$view['cmid'] = $id;
			$userDetails = $client_obj->getQuoteUserDetails($quote_details[0]['created_by']);
			$view['sales_owner'] = $userDetails[0]['first_name']." ".$userDetails[0]['last_name'];
			$view['mailto'] = $userDetails[0]['email'];
			$view['sales_id'] = $quote_details[0]['created_by'];
			$view['telphone'] = $userDetails[0]['phone_number'];
			$view['city'] = $userDetails[0]['city'];
			$view['currency'] =  $quote_details[0]['sales_suggested_currency'];
			$view['assigned'] = $res[0]['assigned_to'];
			$view['quote_signed_at'] = date('d M Y',strtotime($quote_details[0]['signed_at']));
			$view['from_date'] = date('d M Y',strtotime($res[0]['assigned_at']));
			if($res[0]['final_mission_length_option']=='hours')
			$no_days = ceil($res[0]['final_mission_length']/24);
			else
			$no_days = $res[0]['final_mission_length'];
			$view['to_date'] = date('d M Y',strtotime($res[0]['assigned_at']."+ $no_days days"));
			$user_obj=new Ep_User_User();
			$user_info = $user_obj->getAllUsersDetails($quote_details[0]['created_by']);
			if($user_info)
			$quote_details[0]['created_name'] = $user_info[0]['first_name']." ".$user_info[0]['last_name'];
			else
			$quote_details[0]['created_name'] = "";
			$quote_details[0]['created_time'] = time_ago($quote_details[0]['created_at']);
			$this->_view->quote_details = $quote_details[0];
			/* Comments */
			$comment = array();
			if($quote_details[0]['sales_comment'])
			{
				$comments['created_by'] = $quote_details[0]['created_by'];
				$comments['created_name'] = $quote_details[0]['created_name'];
				$comments['created_time'] = $quote_details[0]['created_time'];
				$comments['comment'] = $quote_details[0]['sales_comment'];
				$comments['created_at'] = $quote_details[0]['created_at'];
				$comment[] = $comments;
			}
			if($res[0]['comments'])
			{
				$comments['comment'] = $res[0]['comments'];
				$comments['created_by'] = $res[0]['created_by'];
				$user_info = $user_obj->getAllUsersDetails($res[0]['created_by']);
				if($user_info)
				$comments['created_name'] = $user_info[0]['first_name']." ".$user_info[0]['last_name'];
				else
				$comments['created_name'] = "";
				$comments['created_time'] = time_ago($res[0]['created_at']);
				$comments['created_at'] = $res[0]['created_at'];
				$comment[] = $comments;
			}
			$contractcomments = array();
			if($res[0]['contractcomment'])
			{
				$comments['comment'] = $res[0]['contractcomment'];
				$comments['created_by'] = $res[0]['sales_creator_id'];
				$user_info = $user_obj->getAllUsersDetails($res[0]['sales_creator_id']);
				if($user_info)
				$comments['created_name'] = $user_info[0]['first_name']." ".$user_info[0]['last_name'];
				else
				$comments['created_name'] = "";
				$comments['created_time'] = time_ago($res[0]['qctime']);
				$comments['created_at'] = $res[0]['qctime'];
				$comment[] = $comments;
			}
			$contractmissioncomments = array();
			if($res[0]['cmcomment'])
			{
				$comments['comment'] = $res[0]['cmcomment'];
				if($res[0]['updated_by'])
				{
					$comments['created_by'] = $res[0]['updated_by'];
					$user_info = $user_obj->getAllUsersDetails($res[0]['updated_by']);
				}
				else
				{
					$comments['created_by'] = $res[0]['assigned_by'];
					$user_info = $user_obj->getAllUsersDetails($res[0]['assigned_by']);
				}
				if($user_info)
				$comments['created_name'] = $user_info[0]['first_name']." ".$user_info[0]['last_name'];
				else
				$comments['created_name'] = "";
				$comments['created_time'] = time_ago($res[0]['updated_at']);
				$comments['created_at'] = $res[0]['updated_at'];
				$comment[] = $comments;
			}
			//$this->_view->contractmissioncomments = $contractmissioncomments;
			$this->_view->comments = $this->sortTimewise($comment);
			$this->_view->files = "";
			$this->_view->quotefiles = "";
			$files = "";
			$quotefiles = "";
			if($quote_details[0]['documents_path'])
			{
				$exploded_file_paths = explode("|",$quote_details[0]['documents_path']);
				$exploded_file_names = explode("|",$quote_details[0]['documents_name']);
				$k=0;
				foreach($exploded_file_paths as $row)
				{
					$file_path=$this->quote_documents_path.$row;
					if(file_exists($file_path) && !is_dir($file_path))
					{
						$fname = $exploded_file_names[$k];
						if($fname=="")
							$fname = basename($row);
						$ofilename = pathinfo($file_path);
						/* <span class="deletequote" rel="'.$k.'_'.$quote_details[0]['identifier'].'"> <i class="icon-adt_trash"></i></span> */
						$quotefiles .= '<tr><td width="30%">'.$fname.'</td><td width="35%">'.substr($ofilename['filename'],0,-3).".".$ofilename['extension'].'</td><td width="20%">'.formatSizeUnits(filesize($file_path)).'</td><td>Sales</td><td align="center" width="15%"><a href="/quote/download-document?type=quote&quote_id='.$quote_details[0]['identifier'].'&index='.$k.'"><i style="margin-right:5px" class="splashy-download"></i></a><td></tr>';	
					}
					$k++;
				}
			}
			$this->_view->quotefiles = $quotefiles;
			/* if($res[0]['documents_path'])
			{
				$exploded_file_paths = explode("|",$res[0]['documents_path']);
				$exploded_file_names = explode("|",$res[0]['documents_name']);
				$k=0;
				foreach($exploded_file_paths as $row)
				{
					$file_path=$this->mission_documents_path.$row;
					if(file_exists($file_path) && !is_dir($file_path))
					{
						$fname = $exploded_file_names[$k];
						if($fname=="")
							$fname = basename($row);
						$ofilename = pathinfo($file_path);
						$files .= '<tr><td width="30%">'.$fname.'</td><td width="35%">'.substr($ofilename['filename'],0,-3).".".$ofilename['extension'].'</td><td width="20%">'.formatSizeUnits(filesize($file_path)).'</td><td>Sales Mission</td><td align="center" width="15%"><a href="/quote/download-document?type=staff_mission&mission_id='.$res[0]['contractmissionid'].'&index='.$k.'"><i style="margin-right:5px" class="splashy-download"></i></a><td></tr>';	
					}
					$k++;
				}
			} 
			$this->_view->files = $files; */
			$this->_view->viewarray = $view;
			//Fetching Logs
			$log_obj = new Ep_Quote_QuotesLog();
			$search = array('contract_id'=>$res[0]['quotecontractid'],'mission_id'=>$res[0]['contractmissionid'],'mission_type'=>'sales\',\'task_sales','time'=>'task_added\',\'new_sales_mission');
			$this->_view->logs = $log_obj->getLogs($search);
			//Fetching Tasks
			$task_obj = new Ep_Quote_Task();
			$this->_view->tasks = $task_obj->getTasks(array('cmid'=>$id));
			$this->render('sales-followup');
		}
		else
			$this->_redirect('contractmission/missions-list');
	}
	// Followup for seo missions 
	function seoAction()
	{
		$request = $this->_request->getParams();
		$id = $request['cmid'];
		
		$contract_obj = new Ep_Quote_Quotecontract();
		$res = $contract_obj->getSalesSeoMissionsContracts(array('cmid'=>$id));
		
		if($res)
		{
			$view = array();
			
			if($res[0]['product']=='translation')
			$view['title'] = $this->product_array[$res[0]['product']]." ".$this->producttype_array[$res[0]['product_type']]." ".$this->getCustomName("EP_LANGUAGES",$res[0]['language_source'])." au ".$this->getCustomName("EP_LANGUAGES",$res[0]['language_dest']);
			else
			$view['title'] = $this->product_array[$res[0]['product']]." ".$this->producttype_array[$res[0]['product_type']]." in ".$this->getCustomName("EP_LANGUAGES",$res[0]['language_source']);
			
                        if($res[0]['product_type']=="autre")
                        {
                            $view['title'] .= " ".$res[0]['product_type_other'];
                        }
                        /* $view['chargeble'] = 'chargable';
			$view['percentage'] = '50'; */
			if($res[0]['before_prod']=='yes')
				$view['priority'] = 'blocker';
			else
				$view['priority'] = 'non blocker';
			/* If free mission Turnover will be Zero and showed as Free */
			if($res[0]['free_mission']=="yes")
				$view['turnover'] = "Free";
			elseif($res[0]['package']=='team')
				$view['turnover'] = $res[0]['turnover'] + $res[0]['team_fee']*$res[0]['team_packs'];
			else
				$view['turnover'] = $res[0]['turnover'];
			$view['production_cost'] = $res[0]['cost'];
			$view['contract_id'] = $res[0]['quotecontractid'];
			$view['mission_id'] = $res[0]['qmid'];
			$view['cm_status'] = $res[0]['cm_status'];
			$view['contract_name'] = $res[0]['contractname'];
			$view['contract_files'] = $res[0]['contractfilepaths'];
			$view['contract_date'] = date('d M Y',strtotime($res[0]['expected_launch_date']))." - ".date('d M Y',strtotime($res[0]['expected_end_date']));
			$view['cmid'] = $id;
			$quote_obj = new Ep_Quote_Quotes();
			$quote_details = $quote_obj->getQuoteDetails($res[0]['quote_id']);
			$view['client_id'] = $quote_details[0]['client_id'];
			$view['cname'] = $quote_details[0]['company_name'];
			$view['cano'] = $quote_details[0]['ca_number'];
			$view['currency'] =  $res[0]['sales_suggested_currency'];
			$client_obj = new Ep_Quote_Client();
			$userDetails = $client_obj->getQuoteUserDetails($quote_details[0]['created_by']);
			$view['sales_owner'] = $userDetails[0]['first_name']." ".$userDetails[0]['last_name'];
			$view['mailto'] = $userDetails[0]['email'];
			$view['sales_id'] = $quote_details[0]['created_by'];
			$view['telphone'] = $userDetails[0]['phone_number'];
			$view['city'] = $userDetails[0]['city'];
			$view['client_code'] = $quote_details[0]['client_code'];
			$view['category_name'] = $this->getCategoryName($quote_details[0]['category']);
			$view['assigned'] = $res[0]['assigned_to'];
			if($res[0]['assigned_to'])
			{
			$userDetails = $client_obj->getQuoteUserDetails($res[0]['assigned_to']);
			$view['seo_name'] = $userDetails[0]['first_name']." ".$userDetails[0]['last_name'];
			}
			else
			$view['seo_name'] = "";
			$view['from_date'] = date('d M Y',strtotime($res[0]['assigned_at']));
			if($res[0]['mission_length_option']=='hours')
			$no_days = ceil($res[0]['mission_length']/24);
			else
			$no_days = $res[0]['mission_length'];
			
			$view['to_date'] = date('d M Y',strtotime($res[0]['assigned_at']."+ $no_days days"));
			$view['quote_signed_at'] = date('d M Y',strtotime($quote_details[0]['signed_at']));
			$user_obj=new Ep_User_User();
			
			$user_info = $user_obj->getAllUsersDetails($quote_details[0]['created_by']);
				
			if($user_info)
			$quote_details[0]['created_name'] = $user_info[0]['first_name']." ".$user_info[0]['last_name'];
			else
			$quote_details[0]['created_name'] = "";
			$quote_details[0]['created_time'] = time_ago($quote_details[0]['created_at']);
			
			$this->_view->quote_details = $quote_details[0];
			
			/* Comments */
			$comment = array();
			if($quote_details[0]['sales_comment'])
			{
				$comments['created_by'] = $quote_details[0]['created_by'];
				$comments['created_name'] = $quote_details[0]['created_name'];
				$comments['created_time'] = $quote_details[0]['created_time'];
				$comments['comment'] = $quote_details[0]['sales_comment'];
				$comments['created_at'] = $quote_details[0]['created_at'];
				$comment[] = $comments;
			}
			
			if($res[0]['comments'])
			{
				$comments['comment'] = $res[0]['comments'];
				$comments['created_by'] = $res[0]['created_by'];
				
				$user_info = $user_obj->getAllUsersDetails($res[0]['created_by']);
				
				if($user_info)
				$comments['created_name'] = $user_info[0]['first_name']." ".$user_info[0]['last_name'];
				else
				$comments['created_name'] = "";
				$comments['created_time'] = time_ago($res[0]['created_at']);
				$comments['created_at'] = $res[0]['created_at'];
				$comment[] = $comments;
			}
			
			$contractcomments = array();
			if($res[0]['contractcomment'])
			{
				$comments['comment'] = $res[0]['contractcomment'];
				$comments['created_by'] = $res[0]['sales_creator_id'];
				
				$user_info = $user_obj->getAllUsersDetails($res[0]['sales_creator_id']);
				
				if($user_info)
				$comments['created_name'] = $user_info[0]['first_name']." ".$user_info[0]['last_name'];
				else
				$comments['created_name'] = "";
				$comments['created_time'] = time_ago($res[0]['qctime']);
				$comments['created_at'] = $res[0]['qctime'];
				$comment[] = $comments;
			}
			
			$contractmissioncomments = array();
			if($res[0]['cmcomment'])
			{
				$comments['comment'] = $res[0]['cmcomment'];
				if($res[0]['updated_by'])
				{
					$comments['created_by'] = $res[0]['updated_by'];
					$user_info = $user_obj->getAllUsersDetails($res[0]['updated_by']);
				}
				else
				{
					$comments['created_by'] = $res[0]['assigned_by'];
					$user_info = $user_obj->getAllUsersDetails($res[0]['assigned_by']);
				}
				
				if($user_info)
				$comments['created_name'] = $user_info[0]['first_name']." ".$user_info[0]['last_name'];
				else
				$comments['created_name'] = "";
				$comments['created_time'] = time_ago($res[0]['updated_at']);
				$comments['created_at'] = $res[0]['updated_at'];
				$comment[] = $comments;
			}
			
			//$this->_view->contractmissioncomments = $contractmissioncomments;
			$this->_view->comments = $this->sortTimewise($comment);
			
			$this->_view->files = "";
			$this->_view->quotefiles = "";
			$files = "";
			$quotefiles = "";
			if($quote_details[0]['documents_path'])
			{
				$exploded_file_paths = explode("|",$quote_details[0]['documents_path']);
				$exploded_file_names = explode("|",$quote_details[0]['documents_name']);
				
				$k=0;
				foreach($exploded_file_paths as $row)
				{
					$file_path=$this->quote_documents_path.$row;
					if(file_exists($file_path) && !is_dir($file_path))
					{
						$fname = $exploded_file_names[$k];
						if($fname=="")
							$fname = basename($row);
						$ofilename = pathinfo($file_path);
						$quotefiles .= '<tr><td width="30%">'.$fname.'</td><td width="35%">'.substr($ofilename['filename'],0,-3).".".$ofilename['extension'].'</td><td width="20%">'.formatSizeUnits(filesize($file_path)).'</td><td>Sales</td><td align="center" width="15%"><a href="/quote/download-document?type=quote&quote_id='.$quote_details[0]['identifier'].'&index='.$k.'"><i style="margin-right:5px" class="splashy-download"></i></a><td></tr>';	
					}
					$k++;
				}
				
			}
			$this->_view->quotefiles = $quotefiles;
			if($res[0]['documents_path'])
			{
				$exploded_file_paths = explode("|",$res[0]['documents_path']);
				$exploded_file_names = explode("|",$res[0]['documents_name']);
				
				$k=0;
				foreach($exploded_file_paths as $row)
				{
					$file_path=$this->mission_documents_path.$row;
					if(file_exists($file_path) && !is_dir($file_path))
					{
						$fname = $exploded_file_names[$k];
						if($fname=="")
							$fname = basename($row);
						$ofilename = pathinfo($file_path);
						$files .= '<tr><td width="30%">'.$fname.'</td><td width="35%">'.substr($ofilename['filename'],0,-3).".".$ofilename['extension'].'</td><td width="20%">'.formatSizeUnits(filesize($file_path)).'</td><td>SEO</td><td align="center" width="15%"><a href="/quote/download-document?type=seo_mission&mission_id='.$res[0]['qmid'].'&index='.$k.'"><i style="margin-right:5px" class="splashy-download"></i></a><td></tr>';	
					}
					$k++;
				}
				
			}
			$this->_view->files = $files; 
			$this->_view->viewarray = $view;
			
			//Fetching Logs
			$log_obj = new Ep_Quote_QuotesLog();
			$search = array('contract_id'=>$res[0]['quotecontractid'],'mission_id'=>$res[0]['qmid'],'mission_type'=>'seo\',\'task_seo','time'=>'task_added');
			$this->_view->logs = $log_obj->getLogs($search);
			
			//Fetching Tasks
			$task_obj = new Ep_Quote_Task();
			$this->_view->tasks = $task_obj->getTasks(array('cmid'=>$id));
			
			$this->render('seo-followup');
		}
		else
		{
			$this->_redirect('contractmission/missions-list');
		}
		
	}
	// Followup for Prod missions
	function prodAction()
	{
		$request = $this->_request->getParams();
		$id = $request['cmid'];
		
		$contract_obj = new Ep_Quote_Quotecontract();
		/* Getting prod mission from contractmissions */
		$res = $contract_obj->getSalesSeoMissionsContracts(array('cmid'=>$id));
		if($res)
		{
			$view = array();
			$client_obj = new Ep_Quote_Client();
			if($res[0]['product']=='translation')
			$view['title'] = $this->product_array[$res[0]['product']]." ".$this->producttype_array[$res[0]['product_type']]." ".$this->getCustomName("EP_LANGUAGES",$res[0]['language_source'])." au ".$this->getCustomName("EP_LANGUAGES",$res[0]['language_dest']);
			else
			$view['title'] = $this->product_array[$res[0]['product']]." ".$this->producttype_array[$res[0]['product_type']]." in ".$this->getCustomName("EP_LANGUAGES",$res[0]['language_source']);
			
			if($res[0]['product_type']=="autre")
			{
				$view['title'] .= " ".$res[0]['product_type_other'];
			}
			$view['from_date'] = date('d M Y',strtotime($res[0]['assigned_at']));
			/* Calculating to date from when missions was assigned plus the mission length from quotemission */
			if($res[0]['mission_length_option']=='hours')
			$no_days = ceil($res[0]['mission_length']/24);
			else
			$no_days = $res[0]['mission_length'];
			
			$view['to_date'] = date('d M Y',strtotime($res[0]['assigned_at']."+ $no_days days"));
			if($res[0]['privatedelivery']=='yes')
			$view['mission_type'] = 'Private';
			else
			$view['mission_type'] = 'Not Private';
			$view['cmid'] = $id;
			if($res[0]['before_prod']=='yes')
			$view['priority'] = 'blocker';
			else
			$view['priority'] = 'non blocker';
			$view['chargeble'] = 'chargable';
			$view['volume'] = $res[0]['volume'];
			/* If Quote currency is not equal to Contractmission currency then conversion will taken from Contractmission else it will be one */
			if($res[0]['sales_suggested_currency']!=$res[0]['cmcurrency'])
				$conversion = $res[0]['cm_conversion'];
			else
				$conversion = 1;
			$view['cm_turnover'] =  $res[0]['cm_turnover'];
			if($res[0]['free_mission']=="yes")
				$view['turnover'] = "Free";
			elseif($res[0]['package']=='team')
				$view['turnover'] = $res[0]['turnover']+$res[0]['team_fee']*$res[0]['team_packs'];
			else
				$view['turnover'] = $res[0]['turnover'];
			if(!$view['cm_turnover'])
				$view['cm_turnover'] =  $view['turnover'];
			$view['currency'] =  $res[0]['sales_suggested_currency'];
			$view['contract_name'] = $res[0]['contractname'];
			$view['contract_files'] = $res[0]['contractfilepaths'];
			$view['contract_date'] = date('d M Y',strtotime($res[0]['expected_launch_date']))." - ".date('d M Y',strtotime($res[0]['expected_end_date']));
			if($res[0]['assigned_to'])
			{
			$userDetails = $client_obj->getQuoteUserDetails($res[0]['assigned_to']);
			$view['prod_name'] = $userDetails[0]['first_name']." ".$userDetails[0]['last_name'];
			}
			else
			$view['prod_name'] = "";
			$quote_obj = new Ep_Quote_Quotes();
			$quote_details = $quote_obj->getQuoteDetails($res[0]['quote_id']);
			$view['client_id'] = $quote_details[0]['client_id'];
			$view['cname'] = $quote_details[0]['company_name'];
			$view['cano'] = $quote_details[0]['ca_number'];
			$view['client_code'] = $quote_details[0]['client_code'];
			$view['category_name'] = $this->getCategoryName($quote_details[0]['category']);
			
			$userDetails = $client_obj->getQuoteUserDetails($quote_details[0]['created_by']);
			$view['sales_owner'] = $userDetails[0]['first_name']." ".$userDetails[0]['last_name'];
			$view['mailto'] = $userDetails[0]['email'];
			$view['sales_id'] = $quote_details[0]['created_by'];
			$view['telphone'] = $userDetails[0]['phone_number'];
			$view['city'] = $userDetails[0]['city'];
			$view['cid'] = $res[0]['quotecontractid'];
			$view['qmid'] = $res[0]['qmid'];
			$view['assigned'] = $res[0]['assigned_to'];
			$view['survey'] =  $res[0]['is_survey'];
			$view['recruitment'] = $res[0]['is_recruitment'] ;
			$view['cm_status'] = $res[0]['cm_status'];
			$view['quote_signed_at'] = date('d M Y',strtotime($quote_details[0]['signed_at']));
			$user_obj=new Ep_User_User();
			
			$user_info = $user_obj->getAllUsersDetails($quote_details[0]['created_by']);
				
			if($user_info)
			$quote_details[0]['created_name'] = $user_info[0]['first_name']." ".$user_info[0]['last_name'];
			else
			$quote_details[0]['created_name'] = "";
			$quote_details[0]['created_time'] = time_ago($quote_details[0]['created_at']);
			
			$this->_view->quote_details = $quote_details[0];
		
			/* Comments */
			$comment = array();
			if($quote_details[0]['sales_comment'])
			{
				$comments['created_by'] = $quote_details[0]['created_by'];
				$comments['created_name'] = $quote_details[0]['created_name'];
				$comments['created_time'] = $quote_details[0]['created_time'];
				$comments['comment'] = $quote_details[0]['sales_comment'];
				$comments['created_at'] = $quote_details[0]['created_at'];
				$comment[] = $comments;
			}
		
			if($res[0]['comments'])
			{
				$comments['comment'] = $res[0]['comments'];
				$comments['created_by'] = $res[0]['created_by'];
				
				$user_info = $user_obj->getAllUsersDetails($res[0]['created_by']);
				
				if($user_info)
					$comments['created_name'] = $user_info[0]['first_name']." ".$user_info[0]['last_name'];
				else
					$comments['created_name'] = "";
				$comments['created_time'] = time_ago($res[0]['created_at']);
				$comments['created_at'] = $res[0]['created_at'];
				$comment[] = $comments;
			}
			
			if($res[0]['contractcomment'])
			{
				$comments['comment'] = $res[0]['contractcomment'];
				$comments['created_by'] = $res[0]['sales_creator_id'];
				
				$user_info = $user_obj->getAllUsersDetails($res[0]['sales_creator_id']);
				
				if($user_info)
				$comments['created_name'] = $user_info[0]['first_name']." ".$user_info[0]['last_name'];
				else
				$comments['created_name'] = "";
				$comments['created_time'] = time_ago($res[0]['qctime']);
				$comments['created_at'] = $res[0]['qctime'];
			}
			
			if($res[0]['cmcomment'])
			{
				$comments['comment'] = $res[0]['cmcomment'];
				if($res[0]['updated_by'])
				{
					$comments['created_by'] = $res[0]['updated_by'];
					$user_info = $user_obj->getAllUsersDetails($res[0]['updated_by']);
				}
				else
				{
					$comments['created_by'] = $res[0]['assigned_by'];
					$user_info = $user_obj->getAllUsersDetails($res[0]['assigned_by']);
				}
				
				if($user_info)
					$comments['created_name'] = $user_info[0]['first_name']." ".$user_info[0]['last_name'];
				else
					$comments['created_name'] = "";
				$comments['created_time'] = time_ago($res[0]['updated_at']);
				$comments['created_at'] = $res[0]['updated_at'];
				$comment[] = $comments;
			}
			$this->_view->comments = $this->sortTimewise($comment);	
			
			$files = "";
			if($res[0]['cmdocuments_path'])
			{
				$exploded_file_paths = explode("|",$res[0]['cmdocuments_path']);
				$exploded_file_names = explode("|",$res[0]['cmdocuments_name']);
				
				$k=0;
				foreach($exploded_file_paths as $row)
				{
					$file_path=$this->mission_documents_path.$row;
					if(file_exists($file_path) && !is_dir($file_path))
					{
						$fname = $exploded_file_names[$k];
						if($fname=="")
							$fname = basename($row);
						$ofilename = pathinfo($file_path);
						$files .= '<tr><td width="30%">'.$fname.'</td><td width="35%">'.substr($ofilename['filename'],0,-3).".".$ofilename['extension'].'</td><td width="20%">'.formatSizeUnits(filesize($file_path)).'</td><td>Prod</td><td align="center" width="15%"><a href="/quote/download-document?type=prod_mission&mission_id='.$res[0]['contractmissionid'].'&index='.$k.'"><i style="margin-right:5px" class="splashy-download"></i></a><td></tr>';	
					}
					$k++;
				}
			}
			
			$this->_view->files = $this->getSeoTechFiles($res[0]['quote_id']).$files; 
			
			/* Fetching SEO and Tech Missions */
			$tech_obj=new Ep_Quote_TechMissions();
			$search = array();
			$search['quote_id']=$res[0]['quote_id'];
			$search['include_final']='yes';
			$techMissionDetails=$tech_obj->getTechMissionDetails($search);
			
			if($techMissionDetails):
			$i = 0;
			foreach($techMissionDetails as $row):
				$contractMissionDetails = $contract_obj->getContractMission($res[0]['quotecontractid'],'tech',$row['identifier']);
				if($contractMissionDetails)
				{
					if($row['delivery_option']=='hours')
					$no_days = ceil($row['delivery_time']/24);
					else
					$no_days = $row['delivery_time'];
					
					$techMissionDetails[$i]['to_date'] = date('d M Y',strtotime($contractMissionDetails[0]['assigned_at']."+ $no_days days"));
					$techMissionDetails[$i]['from_date'] = date('d M Y',strtotime($contractMissionDetails[0]['assigned_at']));
				}
				else
				{
					$techMissionDetails[$i]['to_date'] = date('d M Y',strtotime($res[0]['expected_launch_date']));
					$techMissionDetails[$i]['from_date'] = date('d M Y',strtotime($res[0]['expected_end_date']));
				}
				$i++;
			endforeach;
			endif;
			$this->_view->techmissions = $techMissionDetails;
						
			$seomission_obj=new Ep_Quote_QuoteMissions();
			$search = array();
			$search['quote_id']=$res[0]['quote_id'];
			$search['misson_user_type']='seo';
			$search['include_final']='yes';
			$search['product_type_seo']='IN';
			$seoMissionDetails = $seomission_obj->getMissionDetails($search);
			
			$inc = 0;
			if(count($seoMissionDetails)):
				foreach($seoMissionDetails as $row)
				{
					$contractMissionDetails = $contract_obj->getContractMission($res[0]['quotecontractid'],'seo',$row['identifier']);
					if($contractMissionDetails)
					{
						if($row['mission_length_option']=='hours')
							$no_days = ceil($row['mission_length']/24);
						else
							$no_days = $row['mission_length'];
						
						$seoMissionDetails[$inc]['to_date'] = date('d M Y',strtotime($contractMissionDetails[0]['assigned_at']."+ $no_days days"));
						$seoMissionDetails[$inc]['from_date'] = date('d M Y',strtotime($contractMissionDetails[0]['assigned_at']));
					}
					else
					{
						$seoMissionDetails[$inc]['to_date'] = date('d M Y',strtotime($res[0]['expected_launch_date']));
						$seoMissionDetails[$inc]['from_date'] = date('d M Y',strtotime($res[0]['expected_end_date']));
					}
					$seoMissionDetails[$inc++]['title'] = "SEO Proposal $inc ".$this->product_array[$row['product']];	
				}
			endif;
			$this->_view->seoMissionDetails = $seoMissionDetails;
			
			$delivery_obj = new Ep_Quote_Delivery();
			
			/* Stats */
			/* Getting sum of price of unpublished articles */
			$total_details = $delivery_obj->getProdStats(array('cmid'=>$id));
			/* Getting sum of price of published articles */
			$published_details = $delivery_obj->getProdStats(array('cmid'=>$id),true);
			$view['total_articles'] = $total_details[0]['total_art'];
			$view['total_price'] = $total_details[0]['total_price'];
			if($published_details)
			{
				$view['published_articles'] = $published_details[0]['total_art'];
				$view['published_price'] = $published_details[0]['total_price'];
			}
			else
			{
				$view['published_articles'] = 0;
				$view['published_price'] = 0;
			}
			if($view['published_price']>$view['total_price'])
				$view['published_price'] = $view['total_price'];
			if($view['published_articles']>$view['volume'])
				$view['published_articles'] = $view['volume'];
			$view['percentage'] = round(($view['published_articles']/$view['volume'])*100);
			$view['colorcode'] = $this->getColor($view['percentage']);
			/* if($view['percentage']==100)
			$cm_status = 'validated';
			else
			$cm_status = 'ongoing';
			$res1 = $contract_obj->updateContractMission(array('progress_percent'=>$view['percentage'],'cm_status'=>$cm_status),$id);  */
			/* Deliveries */
			$deliveries = $delivery_obj->getMissionDeliveries(array('cmid'=>$id,'mission_test'=>'no'));
	
			// Delivery End Date
			foreach($deliveries as $key => $value):
				$deliveries[$key]['publishdate'] = date('d M Y',$value['published_at']);
				/* End date is max proofreading time of article if found else sum of junior, senior and subjoiner time */
				if($value['proofread_end'])
				$deliveries[$key]['enddate'] = date('d M Y',strtotime($value['proofread_end']));
				else
				{
					$days = ceil($deliveries[$key]['total_time']/(60*24));
					$deliveries[$key]['enddate'] = date('d M Y',strtotime("+$days days",$value['published_at']));
				}
				$deliveries[$key]['proofread_enddate'] = date('d M Y',strtotime($value['proofread_end']));
				/* When last article is published for each delivery */
				if($value['max_date'] && $value['max_date']!="0000-00-00 00:00:00")
				$deliveries[$key]['max_date'] = date('d M Y',strtotime($value['max_date']));
				else
				$deliveries[$key]['max_date'] = "-";

				if( $value['delivered_test'] === NULL)
				$deliveries[$key]['max_delivered_updated_at'] = date('d-M-Y',strtotime($value['max_delivered_updated_at']));
				else
				$deliveries[$key]['max_delivered_updated_at'] = "-";

			endforeach; 
		
			$this->_view->deliveries = $deliveries;
			/* Not Allowing to create delivery if recruitment or survey is not finished */
			$view['create_delivery'] = 1;
			
			/* Recruitments */
			if($view['recruitment']=='yes'):
				$recruitments = $delivery_obj->getMissionDeliveries(array('cmid'=>$id,'mission_test'=>'yes'));
				foreach($recruitments as $key => $value):
					$recruitments[$key]['publishdate'] = date('d M Y',$value['published_at']);
					if($value['proofread_end'])
						$recruitments[$key]['enddate'] = date('d M Y',strtotime($value['proofread_end']));
					else
					{
						$days = ceil($recruitments[$key]['total_time']/(60*24));
						$recruitments[$key]['enddate'] = date('d M Y',strtotime("+$days days",$value['published_at']));
					}
				endforeach; 
			//echo "<pre>";print_r($recruitments);exit;
			$this->_view->recruitments = $recruitments;
			/* Restricting creation of delivery until one recruitment is closed */
			$recruitment_status = $delivery_obj->getRecruitmentStatus(array('cmid'=>$id,'mission_test'=>'yes'));
			if($recruitment_status[0]['dcount'])
			$view['create_delivery'] = 1;
			else
			$view['create_delivery'] = 0;
			endif;
			
			/* Histories and Logs */
			$history_obj = new Ep_Quote_ArtDelHistory();
			$history = $history_obj->getAOHistoryProd(array('cmid'=>$id));			
			//$this->_view->histories = $history;
			
			$log_obj = new Ep_Quote_QuotesLog();
			$search = array('contract_id'=>$res[0]['quotecontractid'],'mission_id'=>$res[0]['qmid'],
			'mission_type'=>'prod\',\'survey_prod\',\'recruitment_prod\',\'contrib_comment','time'=>'survey_creation\',\'validated_prod');
			$logs = $log_obj->getLogs($search);
			
			$myArray = array_merge((array)$history,(array)$logs);
			
			usort($myArray,function($a,$b)
			{
				return $a['action_at'] < $b['action_at'];
			});
			
			$this->_view->logs = $myArray;
			/* Surveys */
			if($view['survey']=='yes'):
			$survey_obj = new Ep_Quote_Survey();
			$surveys = $survey_obj->getPolls(array('cmid'=>$id));
			if($surveys):
			for($i=0;$i<count($surveys);$i++):
				$surveys[$i]['product_name'] = $this->product_array[$surveys[$i]['product']];
				$surveys[$i]['product_type_name'] = $this->producttype_array[$surveys[$i]['product_type']];
				$surveys[$i]['language_source_name'] = $this->getCustomName("EP_LANGUAGES",$surveys[$i]['language_source']);
				$surveys[$i]['language_dest_name'] =$this->getCustomName("EP_LANGUAGES",$surveys[$i]['language_dest']);
				$surveys[$i]['expires'] =strtotime($surveys[$i]['count_down_end']);
				$surveys[$i]['startdate'] =$surveys[$i]['publish_time'];
				$surveys[$i]['enddate'] =$surveys[$i]['count_down_end'];
			endfor;
			endif;
			/* Restricting creation of delivery until one survey is closed */
			$survey_status = $survey_obj->getSurveyStatus(array('cmid'=>$id));
			if($survey_status)
			$view['create_delivery'] = 1;
			else
			$view['create_delivery'] = 0;
			endif;
			$this->_view->surveys = $surveys;	
			/* Fetching oneshot content or tempo to display in title and freeze date to display in create delivery */
			$oneshots = $contract_obj->getOneshotTempos($res[0]['quotecontractid'],$res[0]['type_id']);
			if($oneshots)
			{
				$content = "<span class='content'>";
				foreach($oneshots as $row)
				{
					$content .= "Deliver ".$row['articles']." articles after ".$row['oneshot_length']." ".$this->duration_array[$row['oneshot_option']]."<br>";
				}
				$content .= "</span>";
				$html = '<span class="version-change pop_over" data-placement="bottom" data-original-title="Partial Deliveries" data-content="'.$content.'" data-html="true">+</span>';
			}
			else
				$html = "";
				// Tempo display
			if($res[0]['oneshot'] =="yes")
			{
				$view['tempo_text'] ="<h3>One shot mission : ". $res[0]['mission_length']." ".$this->duration_array[$res[0]['mission_length_option']]." ( $view[to_date] ) $html </h3>";	
			}
			elseif($res[0]['oneshot'] =="no")
			{
				//$volume=$res[0]['volume_max'] ? $res[0]['volume_max'] : $res[0]['volume'];
				$view['tempo_text'] = "<h3>Recurring : ".$res[0]['volume_max']." (".$this->_view->tempo_array[$res[0]['tempo']].") ".$this->producttype_array[$res[0]['product_type']]." ".$this->_view->volume_option_array[$res[0]['delivery_volume_option']].' '.$res[0]['tempo_length'].' '.$this->duration_array[$res[0]['tempo_length_option']]."</h3>";
			}
			else
			$view['tempo_text'] = "";
			
			$view['freeze_end_date'] = $res[0]['freeze_end_date'];
			/* Restricting creation of delivery until start and end of freeze date */
			if($res[0]['freeze_end_date']>=date('Y-m-d'))
				$view['freeze_delivery'] = 1;
			else
				$view['freeze_delivery'] = 0;
			
			$this->_view->viewarray = $view;
            /* *** added on 16.03.2016 *** */
            $bnp_obj = new Ep_Bnp_Bnp();
            $results = $bnp_obj->fecthBnpParibasXlsx($id);
            if($results !== false)
                $this->_view->bnpXlsx = 'yes';
            else
                $this->_view->bnpXlsx = 'no';
			$this->render('prod-followup');
		}
		else
		{
			$this->_redirect('contractmission/missions-list');
		}
	}
	/* To add tempo view in popup for old missions without tempo */
	function addTempoAction()
	{
		if($_SERVER['HTTP_X_REQUESTED_WITH']=='XMLHttpRequest')
		{
			$request = $this->_request->getParams();
			$quotecontract = new Ep_Quote_Quotecontract();
			$mission_details = $quotecontract->getSeoMission($request['qmid']);
			$this->_view->mission_details = $mission_details[0];
			if($mission_details[0]['oneshot']=="yes")
			{
				$this->_view->tempo_one_shots = $quotecontract->getOneshotTempos($request['cid'],$request['qmid']);
			}
			else
			{
				$this->_view->tempo_one_shots = "";
			}
			$this->_view->cmid = $request['cmid'];
			$this->_view->cid = $request['cid'];
			$this->render('add-tempo');
		}
	}
	/* To save the tempo */
	function saveTempoAction()
	{
		$request = $this->_request->getParams();
		if($this->_request->isPost() && $request['mid'])
		{
			$save = array();
			$save['mission_length'] = $request['mission_length'];
			$save['mission_length_option'] = $request['mission_length_option'];
			$save['oneshot'] = $request['oneshot'];
			$save['staff_time'] = $request['staff_time'];
			$quote_contract = new Ep_Quote_Quotecontract();
			if($save['oneshot']=="yes")
			{
				//$contract_mission_id = $request['cmid'];
				$articles = (array) $request['articles'];
				$oneshot_length = (array) $request['oneshot_length'];
				$oneshot_option = (array) $request['oneshot_option'];
				$quote_contract->deleteTempoOneshots($request['cid'],$request['mid']);
				for($i=0;$i<count($articles);$i++)
				{
					$tm = array();
					$tm['contract_id'] = $request['cid'];
					$tm['mission_id'] = $request['mid'];
					$tm['articles'] = $articles[$i];
					$tm['oneshot_length'] = $oneshot_length[$i];
					$tm['oneshot_option'] = $oneshot_option[$i];
					$quote_contract->insertTempoOneshots($tm);
				}
				$save['volume_max'] = NULL;
				$save['tempo'] = NULL;
				$save['delivery_volume_option'] = NULL;
				$save['tempo_length'] = NULL;
				$save['tempo_length_option'] = NULL;
			}
			else
			{
				$quote_contract->deleteTempoOneshots($request['cid'],$request['mid']);
				$save['volume_max'] = $request['volume_max'];
				$save['tempo'] = $request['tempo'];
				$save['delivery_volume_option'] = $request['delivery_volume_option'];
				$save['tempo_length'] = $request['tempo_length'];
				$save['tempo_length_option'] = $request['tempo_length_option'];
			}
			$mission_obj = new Ep_Quote_QuoteMissions();
			$mission_obj->updateQuoteMission($save,$request['mid']);
			$this->_helper->FlashMessenger('Updated tempo successfully');
			if($request['cmid'])
			$this->_redirect('/followup/prod?submenuId=ML13-SL4&cmid='.$request['cmid']);
			else	
			$this->_redirect('/contractmission/contract-edit?submenuId=ML13-SL3&contract_id='.$request['cid']);	
		}
	}
	/* For sorting array */
	function comparearray($a,$b)
	{
		return $a['action_at'] > $b['action_at'];
	}
	/* To get SEO and Tech Files related to quote */
	function getSeoTechFiles($quote_id)
	{
		$files = "";
		
		$quote_obj = new Ep_Quote_Quotes();
		$quote_details = $quote_obj->getQuoteDetails($quote_id);
		
		/* Quote files */
		if($quote_details[0]['documents_path'])
		{
			$exploded_file_paths = explode("|",$quote_details[0]['documents_path']);
			$exploded_file_names = explode("|",$quote_details[0]['documents_name']);
			
			$k=0;
			foreach($exploded_file_paths as $row)
			{
				$file_path=$this->quote_documents_path.$row;
				if(file_exists($file_path) && !is_dir($file_path))
				{
					$fname = $exploded_file_names[$k];
					if($fname=="")
						$fname = basename($row);
					$ofilename = pathinfo($file_path);
					/* <span class="deletequote" rel="'.$k.'_'.$quote_details[0]['identifier'].'"> <i class="icon-adt_trash"></i></span> */
					$files .= '<tr><td width="30%">'.$fname.'</td><td width="35%">'.substr($ofilename['filename'],0,-3).".".$ofilename['extension'].'</td><td width="20%">'.formatSizeUnits(filesize($file_path)).'</td><td>Sales</td><td align="center" width="15%"><a href="/quote/download-document?type=quote&quote_id='.$quote_details[0]['identifier'].'&index='.$k.'"><i style="margin-right:5px" class="splashy-download"></i></a><td></tr>';	
				}
				$k++;
			}
			
		}
		
		/* Tech Mission files */
		$tech_obj=new Ep_Quote_TechMissions();
		$searchParameters['quote_id']=$quote_id;
		$searchParameters['include_final']='yes';
		$techMissionDetails=$tech_obj->getTechMissionDetails($searchParameters);
		if($techMissionDetails):
		$index = 0;
		foreach($techMissionDetails as $row)
		{
			if($techMissionDetails[$index]['documents_path']):
			$exploded_file_paths = explode("|",$techMissionDetails[$index]['documents_path']);
			$exploded_file_names = explode("|",$techMissionDetails[$index]['documents_name']);
			
			$k=0;
			foreach($exploded_file_paths as $row)
			{
				$file_path=$this->mission_documents_path.$row;
				if(file_exists($file_path) && !is_dir($file_path))
				{
					$fname = $exploded_file_names[$k];
					if($fname=="")
						$fname = basename($row);
					$ofilename = pathinfo($file_path);
					$files .= '<tr><td width="30%">'.$fname.'</td><td width="35%">'.substr($ofilename['filename'],0,-3).".".$ofilename['extension'].'</td><td width="20%">'.formatSizeUnits(filesize($file_path)).'</td><td>Tech</td><td align="center" width="15%"><a href="/quote/download-document?type=tech_mission&mission_id='.$techMissionDetails[$index]['identifier'].'&index='.$k.'"><i style="margin-right:5px" class="splashy-download"></i></a><td></tr>';	
				}
				$k++;
			}
			endif;
			$index++;
		}
		endif;
		
		/* SEO Mission Files */
		$seomission_obj=new Ep_Quote_QuoteMissions();
		$searchParameters['quote_id']=$quote_id;
		$searchParameters['misson_user_type']='seo';
		$searchParameters['include_final']='yes';
		$searchParameters['product_type_seo']='IN';
		$seoMissionDetails = $seomission_obj->getMissionDetails($searchParameters);
		if($seoMissionDetails):
		$index = 0;
		foreach($seoMissionDetails as $row)
		{
			if($seoMissionDetails[$index]['documents_path'])
			{
				$exploded_file_paths = explode("|",$seoMissionDetails[$index]['documents_path']);
				$exploded_file_names = explode("|",$seoMissionDetails[$index]['documents_name']);
				$k=0;
				foreach($exploded_file_paths as $row)
				{
					$file_path=$this->mission_documents_path.$row;
					if(file_exists($file_path) && !is_dir($file_path))
					{
						$fname = $exploded_file_names[$k];
						if($fname=="")
							$fname = basename($row);
						$ofilename = pathinfo($file_path);
						$files .= '<tr><td width="30%">'.$fname.'</td><td width="35%">'.substr($ofilename['filename'],0,-3).".".$ofilename['extension'].'</td><td width="20%">'.formatSizeUnits(filesize($file_path)).'</td><td>SEO</td><td align="center" width="15%"><a href="/quote/download-document?type=seo_mission&mission_id='.$seoMissionDetails[$index]['identifier'].'&index='.$k.'"><i style="margin-right:5px" class="splashy-download"></i></a><td></tr>';	
					}
					$k++;
				}
			}
			$index++;
		}
		endif;
		return $files;
	}	
	
	/* To load the History of Deliveries*/
	function loadhistoryAction()
	{
		if($this->_request->isPost() && $_SERVER['HTTP_X_REQUESTED_WITH']=='XMLHttpRequest')
		{
			$request = $this->_request->getParams();
			$did = $request['did'];
			$cmid = $request['cmid'];
			$history_obj = new Ep_Quote_ArtDelHistory();
			$log_obj = new Ep_Quote_QuotesLog();
			
			if($did!='delay' && $did!="")
			{
				$history = $history_obj->getAOHistoryProd(array('did'=>$did));	
			}
			elseif($did=='delay')
			{
				$search = array('contract_id'=>$request['cid'],'delay'=>1,'mission_id'=>$request['qmid'],'mission_type'=>'prod\',\'survey_prod\',\'recruitment_prod\',\'contrib_comment','time'=>'survey_creation\',\'validated_prod');
				$logs = $log_obj->getLogs($search);
			}
			else
			{
				$history = $history_obj->getAOHistoryProd(array('cmid'=>$cmid));
				$search = array('contract_id'=>$request['cid'],'mission_id'=>$request['qmid'],'mission_type'=>'prod\',\'survey_prod\',\'recruitment_prod\',\'contrib_comment','time'=>'survey_creation\',\'validated_prod');
				$logs = $log_obj->getLogs($search);
			}
			
			$myArray = array_merge((array)$history,(array)$logs);
			usort($myArray,function($a,$b)
			{
				return $a['action_at'] < $b['action_at'];
			});
			
			$this->_view->histories = $myArray;
			$this->render('art-del-histories');
		}
	}
	
	/* Delivery Followup */
	
	public function deliveryAction()
    {
    	$aoParams=$this->_request->getParams();
    	$aoObject=new EP_Quote_Ongoing();
    	
    	$ao_id=$aoParams['ao_id'];
    	$client_id=$aoParams['client_id'];
    	$aoParams['sorttype']='all';
    	/* To send mail to the contributors */
		if($_POST['sendcontrib_mail']!="")
		{
			//print_r($_POST);
			for($c=0;$c<count($_POST['contributor_list']);$c++)
			{
				$this->sendMailEpMailBoxOngoing($_POST['contributor_list'][$c],$_POST['email_subject'],$_POST['email_content'],$_POST['cemail_from']);
			}
		}
		
		if($ao_id && $client_id)
    	{
			/* fetching delivery details */
			$aoDetails=$aoObject->getOngoingAODetails($aoParams,1);
			//echo "<PRE>";print_r($aoDetails);exit;
			if($aoDetails)	
			{
				// Getting title from Mission
				if($aoDetails[0]['product']=='translation')
				$aoDetails[0]['mission_title'] = $this->product_array[$aoDetails[0]['product']]." ".$this->producttype_array[$aoDetails[0]['product_type']]." ".$this->getCustomName("EP_LANGUAGES",$aoDetails[0]['language_source'])." au ".$this->getCustomName("EP_LANGUAGES",$aoDetails[0]['language_dest']);
				else
				$aoDetails[0]['mission_title'] = $this->product_array[$aoDetails[0]['product']]." ".$this->producttype_array[$aoDetails[0]['product_type']]." in ".$this->getCustomName("EP_LANGUAGES",$aoDetails[0]['language_source']);
				
                                if($aoDetails[0]['product_type']=="autre")
                                {
                                    $aoDetails[0]['mission_title'] .= " ".$aoDetails[0]['product_type_other'];
                                }
				$aoDetails[0]['publishdate'] = date('d M Y',$aoDetails[0]['published_at']);
				
				// Delivery End Date
				if($aoDetails[0]['proofread_end'])
				$aoDetails[0]['delivery_end'] = date('d M Y',strtotime($aoDetails[0]['proofread_end']));
				else
				{
					$days = ceil($aoDetails[0]['total_time']/(60*24));
					//$aoDetails[0]['delivery_end'] = date('d M Y',strtotime($aoDetails[0]['created_at']+ " $days days"));
					$aoDetails[0]['delivery_end'] = date('d M Y',strtotime("+$days days",$aoDetails[0]['published_at']));
				}
				//Progress Percentage
				$array = array(0=>'#ff0000', 15=>'#ff7200', 30=>'#ffa200', 45=>'#ffd21d', 65=>'#f2f43c', 85=>'#cbf43c', 100=>'#3fe805');
				$progresspercentage = round($aoDetails[0]['progress']/$aoDetails[0]['totalArticle']);
                foreach ($array as $k=>$v) {
                    if ($k >= $progresspercentage)
                    {
                        $colorcode = $array[$k];
                        break;
                    }
                }
				$aoDetails[0]['progresspercentage'] = $progresspercentage;
				$aoDetails[0]['colorcode'] = $colorcode;
				//getting All article details of AO
				$aoParams['missiontest']=$aoDetails[0]['missiontest'];
				$articleDetails=$aoObject->getOngoingArticleDetails($aoParams);
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
						$articleDetails[$bcnt]['writer_comment_count'] = $aoObject->checkCommentsCount($article['id'],'article',$articleDetails[$bcnt]['writer_bid_details'][0]['user_id']);
    				}
					if($article['correctorParticipation'])
					{
						$articleDetails[$bcnt]['corrector_bid_details']=$cParticipationObject->getBiddingDetails($article['correctorParticipation']);
						$articleDetails[$bcnt]['corrector_facturation_details']=$cParticipationObject->getFacturationDetails($article['correctorParticipation'],$article['id']);
                        $articleDetails[$bcnt]['corrector_artproc_details']=$aoObject->getLatestCorrectionArticle($article['id']);
						$articleDetails[$bcnt]['corrector_comment_count'] = $aoObject->checkCommentsCount($article['id'],'article',$articleDetails[$bcnt]['corrector_bid_details'][0]['corrector_id']);
                    }
					$articleDetails[$bcnt]['comment_count']=$aoObject->checkNewCommentsCount($article['id'],'article',$aoDetails[0]['incharge_id']);
					$bcnt++;					
				}
				//get repeat delivery Details
				$repeat_obj=new Ep_Quote_DeliveryRepeat();
				$repeat_delivery_details=$repeat_obj->RepeatDeliveryDetails($ao_id);
				if($repeat_delivery_details)
				{
					$this->_view->repeat_delivery='yes';
				}
				//echo "<pre>";print_r($articleDetails);exit;
				$this->_view->aoDetails=$aoDetails;
				$this->_view->articleDetails=$articleDetails;
			
				$this->_view->render("delivery-followup");	
			}
			else
    			$this->_redirect("/contractmission/missions-list?submenuId=ML13-SL4");
    	}
    	else
    		$this->_redirect("/contractmission/missions-list?submenuId=s-SL4");
    }
	
	// Pop up for Task for tech missions
	function techtaskAction()
	{
		$request = $this->_request->getParams();
		if($request['cmid'] || $request['tid'])
		{
			$id = $request['cmid'];
			$tid = $request['tid'];
			$contract_obj = new Ep_Quote_Quotecontract();
			$task_obj = new Ep_Quote_Task();
			$view = array();
			if($request['cmid'])
			{
				$res = $contract_obj->getContractTechMissions(array('cmid'=>$id));
				$view['tech_action'] = "new";
				$view['quote_id'] = $res[0]['qid'];
				$view['cmid'] = $id;
				$view['updated_by'] = $this->adminLogin->userId;
			}
			else
			{
				$res = $task_obj->getTechTaskMissions(array('tid'=>$tid));
				//echo "<pre>"; print_r($res); exit;
				$view['tech_action'] = $request['tech_action'];
				$view['task_id'] = $request['tid'];
				$view['task_title'] = $res[0]['task_title'];
				$view['comments'] = $res[0]['task_comments'];
				$view['task_volume'] = $res[0]['task_volume'];
				$view['cmid'] = $res[0]['contractmissionid'];
				$view['updated_by'] = $res[0]['task_updated_by'];
				$this->_view->task_files = $this->gettaskfiles($res[0]['task_documents_path'],$res[0]['task_documents_name'],$tid,$view['tech_action']);
			}
			$client_obj = new Ep_Quote_Client();
			if($res)
			{
				
				if($res[0]['title'])
				$view['title'] = $res[0]['title'];
				else
				$view['title'] = 'New Tech Mission';
				
				if($res[0]['before_prod']=='yes')
				$view['priority'] = 'blocker';
				else
				$view['priority'] = 'non blocker';
				/*
				$view['percentage'] = '50';
				if($res[0]['from_contract'])
				$view['chargeble'] = 'free';
				else
				$view['chargeble'] = 'chargable'; */
				/* If free mission Turnover will be Zero and showed as Free */
				if($res[0]['free_mission']=="yes")
					$view['turnover'] = "Free";
				elseif($res[0]['package']=='team')
					$view['turnover'] = $res[0]['turnover']+$res[0]['team_fee']*$res['team_packs'];
				else
					$view['turnover'] = $res[0]['turnover'];
				
				$view['production_cost'] = $res[0]['cost'];
				$view['contract_id'] = $res[0]['quotecontractid'];
				$view['mission_id'] = $res[0]['tmid'];
				
				$view['from_date'] = date('d M Y',strtotime($res[0]['assigned_at']));
				if($res[0]['delivery_option']=='hours')
				$no_days = ceil($res[0]['delivery_time']/24);
				else
				$no_days = $res[0]['delivery_time'];
				
				$view['to_date'] = date('d M Y',strtotime($res[0]['assigned_at']."+ $no_days days"));
				$view['currency'] = $res[0]['sales_suggested_currency'];
				$view['volume']=$res[0]['volume'];
				if($res[0]['oneshot'] =="yes")
				{
				$view['calculateVol']=$res[0]['volume'];
				$view['tempo_text'] ="<h3>One shot mission : ".$no_days." ".$this->duration_array[$res[0]['delivery_option']]." </h3>";	
				}
				elseif($res[0]['oneshot'] =="no")
				{
				//$volume=$res[0]['volume_max'] ? $res[0]['volume_max'] : $res[0]['volume'];
				$view['tempo_text'] = "<h3> Recurring : ".$res[0]['volume_max']." (".$this->_view->tempo_array[$res[0]['tempo']].") ".$this->producttype_array[$res[0]['product_type']]." ".$this->_view->volume_option_array[$res[0]['delivery_volume_option']].' '.$res[0]['tempo_length'].' '.$this->duration_array[$res[0]['tempo_length_option']]."</h3>";
				 $volumeMax=$res[0]['volume_max'];
				 $tempo_length_option=$res[0]['tempo_length_option'];
					 	if($tempo_length_option=='days')
					 	{
						 $tempo_length=$res[0]['tempo_length'];
						}elseif($tempo_length_option=='week')	{
						$tempo_length=$res[0]['tempo_length']*7;
						}
						elseif($tempo_length_option=='month')	{
						$tempo_length=$res[0]['tempo_length']*30;
						}
						elseif($tempo_length_option=='year')	{
						$tempo_length=$res[0]['tempo_length']*365;
						}
					$calculateVal=ceil(($no_days/$tempo_length)*$volumeMax);
					$view['calculateVol']=$calculateVal;
				}
				
				$this->_view->viewarray = $view;
				
				$this->render('tech-task');
			}
		}
	}
	
	// To Insert and Update Tech Task
	function submitTechtaskAction()
	{
		if($this->_request->isPost())
		{
			$request = $this->_request->getParams();
			$task_obj = new Ep_Quote_Task();
			$save = array();
			
			$save['task_title'] = $request['title'];
			$save['comments'] = $request['comment'];
			$save['volume'] = $request['volume'];
			$save['updated_by'] = $this->adminLogin->userId;
			$save['mission_type'] = 'tech';
			if(trim($request['task_id']))
			{
				$save['updated_at'] = date('Y-m-d H:i:s');
				$task_obj->updateTask($save,$request['task_id']);
				$task_id = $request['task_id'];
			}
			else
			{
				$save['created_by'] = $this->adminLogin->userId;
				$save['created_at'] = date('Y-m-d H:i:s');
				$save['contract_mission_id'] = $request['cmid'];
				$task_id = $task_obj->insertTask($save);
				
				//Insert Logs
				$log_obj=new Ep_Quote_QuotesLog();					
				$actionmessage = $log_obj->getActionSentence(17);
				$client_obj = new Ep_Quote_Client();
				$bo_user_details=$client_obj->getQuoteUserDetails($this->adminLogin->userId);
				if($bo_user_details!='NO')
					$tech_user="<strong>".$bo_user_details[0]['first_name'].' '.$bo_user_details[0]['last_name']."</strong>";
				else
					$tech_user="";
				$task_title = $save['task_title'];
				$actionmessage=strip_tags($actionmessage);
				eval("\$actionmessage= \"$actionmessage\";");
			
				$log_array['user_id']=$this->adminLogin->userId;
				$log_array['contract_id']=$request['contract_id'];
				$log_array['mission_id'] = $request['mission_id'];
				$log_array['quote_id'] = $request['quote_id'];
				$log_array['mission_type'] = 'task_tech';
				$log_array['comments'] = $request['comment'];
				$log_array['user_type']=$this->adminLogin->type;
				$log_array['action']='task_added';
				$log_array['action_at']=date("Y-m-d H:i:s");
				$log_array['action_sentence']=$actionmessage;
				
				$log_obj->insertLogs($log_array);
			}
			$this->uploadFiles($_FILES,$task_id,$task_obj,$request['document_name']);
			
			
		}
		$this->_redirect("/followup/tech?cmid=$request[cmid]&submenuId=ML13-SL4");
	}	
	
	// Pop up for staff Task
	function stafftaskAction()
	{
		$request = $this->_request->getParams();
		if($request['cmid'] || $request['tid'])
		{
			$id = $request['cmid'];
			$tid = $request['tid'];
			$contract_obj = new Ep_Quote_Quotecontract();
			$task_obj = new Ep_Quote_Task();
			$view = array();
			if($request['cmid'])
			{
				$res = $contract_obj->getStaffMissions(array('cmid'=>$id));
				$view['staff_action'] = "new";
				$view['quote_id'] = $res[0]['quoteid'];
				$view['cmid'] = $id;
				$view['updated_by'] = $this->adminLogin->userId;
			}
			else
			{
				$res = $task_obj->getTask($tid);
				$view['staff_action'] = $request['staff_action'];
				$view['task_id'] = $request['tid'];
				$view['task_title'] = $res[0]['task_title'];
				$view['comments'] = $res[0]['comments'];
				$view['cmid'] = $res[0]['contract_mission_id'];
				$view['updated_by'] = $res[0]['updated_by'];
				$this->_view->task_files = $this->gettaskfiles($res[0]['documents_path'],$res[0]['documents_name'],$tid,$view['staff_action']);
				$res = $contract_obj->getStaffMissions(array('cmid'=>$view['cmid']));
			}
			$client_obj = new Ep_Quote_Client();
			if($res)
			{
				if($res[0]['title'])
				$view['title'] = $res[0]['title'];
				else
				$view['title'] = 'New Staff Mission';
				if($res[0]['before_prod']=='yes')
				$view['priority'] = 'blocker';
				else
				$view['priority'] = 'non blocker';
				/*
				$view['percentage'] = '50';
				if($res[0]['from_contract'])
				$view['chargeble'] = 'free';
				else
				$view['chargeble'] = 'chargable';
				if($res[0]['package']=='team')
				$view['turnover'] = $res[0]['turnover']+$res[0]['team_fee']*$res['team_packs'];
				else */
				$view['turnover'] = $res[0]['turnover'];
				$view['production_cost'] = $res[0]['cost'];
				$view['contract_id'] = $res[0]['quotecontractid'];
				$view['mission_id'] = $res[0]['staff_missionId'];
				$view['from_date'] = date('d M Y',strtotime($res[0]['assigned_at']));
				if($res[0]['delivery_option']=='hours')
				$no_days = ceil($res[0]['delivery_time']/24);
				else
				$no_days = $res[0]['delivery_time'];
				$view['to_date'] = date('d M Y',strtotime($res[0]['assigned_at']."+ $no_days days"));
				$view['currency'] = $res[0]['currency'];
				$this->_view->viewarray = $view;
				$this->render('staff-task');
			}
		}
	}
	// To Insert or Update Staff Task
	function submitStafftaskAction()
	{
		if($this->_request->isPost())
		{
			$request = $this->_request->getParams();
			$task_obj = new Ep_Quote_Task();
			$save = array();
			$save['task_title'] = $request['title'];
			$save['comments'] = $request['comment'];
			$save['updated_by'] = $this->adminLogin->userId;
			$save['mission_type'] = 'staff';
			if(trim($request['task_id']))
			{
				$save['updated_at'] = date('Y-m-d H:i:s');
				$task_obj->updateTask($save,$request['task_id']);
				$task_id = $request['task_id'];
			}
			else
			{
				$save['created_by'] = $this->adminLogin->userId;
				$save['created_at'] = date('Y-m-d H:i:s');
				$save['contract_mission_id'] = $request['cmid'];
				$task_id = $task_obj->insertTask($save);
				//Insert Logs
				$log_obj=new Ep_Quote_QuotesLog();					
				$actionmessage = $log_obj->getActionSentence(17);
				$client_obj = new Ep_Quote_Client();
				$bo_user_details=$client_obj->getQuoteUserDetails($this->adminLogin->userId);
				if($bo_user_details!='NO')
					$tech_user="<strong>".$bo_user_details[0]['first_name'].' '.$bo_user_details[0]['last_name']."</strong>";
				else
					$tech_user="";
				$task_title = $save['task_title'];
				$actionmessage=strip_tags($actionmessage);
				eval("\$actionmessage= \"$actionmessage\";");
				$log_array['user_id']=$this->adminLogin->userId;
				$log_array['contract_id']=$request['contract_id'];
				$log_array['mission_id'] = $request['mission_id'];
				$log_array['quote_id'] = $request['quote_id'];
				$log_array['mission_type'] = 'task_staff';
				$log_array['comments'] = $request['comment'];
				$log_array['user_type']=$this->adminLogin->type;
				$log_array['action']='task_added';
				$log_array['action_at']=date("Y-m-d H:i:s");
				$log_array['action_sentence']=$actionmessage;
				$log_obj->insertLogs($log_array);
			}
			$this->uploadFiles($_FILES,$task_id,$task_obj,$request['document_name']);
		}
		$this->_redirect("/followup/staff?cmid=$request[cmid]&submenuId=ML13-SL4");
	}	
	// Pop up for Sales Task
	function salestaskAction()
	{
		$request = $this->_request->getParams();
		if($request['cmid'] || $request['tid'])
		{
			$id = $request['cmid'];
			$tid = $request['tid'];
			$contract_obj = new Ep_Quote_Quotecontract();
			$task_obj = new Ep_Quote_Task();
			$view = array();
			if($request['cmid'])
			{
				$res = $contract_obj->getSalesMissions(array('cmid'=>$id));
				$view['staff_action'] = "new";
				$view['quote_id'] = $res[0]['quoteid'];
				$view['cmid'] = $id;
				$view['updated_by'] = $this->adminLogin->userId;
			}
			else
			{
				$res = $task_obj->getTask($tid);
				$view['staff_action'] = $request['staff_action'];
				$view['task_id'] = $request['tid'];
				$view['task_title'] = $res[0]['task_title'];
				$view['comments'] = $res[0]['comments'];
				$view['cmid'] = $res[0]['contract_mission_id'];
				$view['updated_by'] = $res[0]['updated_by'];
				$this->_view->task_files = $this->gettaskfiles($res[0]['documents_path'],$res[0]['documents_name'],$tid,$view['staff_action']);
				$res = $contract_obj->getSalesMissions(array('cmid'=>$view['cmid']));
			}
			$client_obj = new Ep_Quote_Client();
			if($res)
			{
				$view['title'] = $res[0]['sales_title'];
				$view['priority'] = 'non blocker';
				$view['turnover'] = 0;
				$view['production_cost'] = 0;
				$view['contract_id'] = $res[0]['quotecontractid'];
				$view['mission_id'] = $res[0]['contractmissionid'];
				$view['from_date'] = date('d M Y',strtotime($res[0]['assigned_at']));
				$view['to_date'] = date('d M Y',strtotime($res[0]['assigned_at']));
				$view['currency'] = $res[0]['currency'];
				$this->_view->viewarray = $view;
				$this->render('sales-task');
			}
		}
	}
	// To Insert or Update Sales Task
	function submitSalestaskAction()
	{
		if($this->_request->isPost())
		{
			$request = $this->_request->getParams();
			$task_obj = new Ep_Quote_Task();
			$save = array();
			$save['task_title'] = $request['title'];
			$save['comments'] = $request['comment'];
			$save['updated_by'] = $this->adminLogin->userId;
			$save['mission_type'] = 'sales';
			if(trim($request['task_id']))
			{
				$save['updated_at'] = date('Y-m-d H:i:s');
				$task_obj->updateTask($save,$request['task_id']);
				$task_id = $request['task_id'];
			}
			else
			{
				$save['created_by'] = $this->adminLogin->userId;
				$save['created_at'] = date('Y-m-d H:i:s');
				$save['contract_mission_id'] = $request['cmid'];
				$task_id = $task_obj->insertTask($save);
				//Insert Logs
				$log_obj=new Ep_Quote_QuotesLog();					
				$actionmessage = $log_obj->getActionSentence(17);
				$client_obj = new Ep_Quote_Client();
				$bo_user_details=$client_obj->getQuoteUserDetails($this->adminLogin->userId);
				if($bo_user_details!='NO')
					$tech_user="<strong>".$bo_user_details[0]['first_name'].' '.$bo_user_details[0]['last_name']."</strong>";
				else
					$tech_user="";
				$task_title = $save['task_title'];
				$actionmessage=strip_tags($actionmessage);
				eval("\$actionmessage= \"$actionmessage\";");
				$log_array['user_id']=$this->adminLogin->userId;
				$log_array['contract_id']=$request['contract_id'];
				$log_array['mission_id'] = $request['mission_id'];
				$log_array['quote_id'] = $request['quote_id'];
				$log_array['mission_type'] = 'task_sales';
				$log_array['comments'] = $request['comment'];
				$log_array['user_type']=$this->adminLogin->type;
				$log_array['action']='task_added';
				$log_array['action_at']=date("Y-m-d H:i:s");
				$log_array['action_sentence']=$actionmessage;
				$log_obj->insertLogs($log_array);
			}
			$this->uploadFiles($_FILES,$task_id,$task_obj,$request['document_name']);
		}
		$this->_redirect("/followup/sales?cmid=$request[cmid]&submenuId=ML13-SL4");
	}	
	//Pop up for SEO task
	function seotaskAction()
	{
		$request = $this->_request->getParams();
		
		if($request['cmid'] || $request['tid'])
		{
			$id = $request['cmid'];
			$tid = $request['tid'];
			$contract_obj = new Ep_Quote_Quotecontract();
			$task_obj = new Ep_Quote_Task();
			$view = array();
			if($request['cmid'])
			{
				$res = $contract_obj->getSalesSeoMissionsContracts(array('cmid'=>$id));
				$view['seo_action'] = "new";
				$view['cmid'] = $id;
				$view['quote_id'] = $res[0]['quote_id'];
				$view['updated_by'] = $this->adminLogin->userId;
			}
			else
			{
				$res = $task_obj->getSeoTaskMissions(array('tid'=>$tid));
				$view['seo_action'] = $request['seo_action'];
				$view['task_id'] = $request['tid'];
				$view['task_title'] = $res[0]['task_title'];
				$view['comments'] = $res[0]['task_comments'];
				$view['cmid'] = $res[0]['contractmissionid'];
				$view['updated_by'] = $res[0]['task_updated_by'];
				$this->_view->task_files = $this->gettaskfiles($res[0]['task_documents_path'],$res[0]['task_documents_name'],$tid,$view['seo_action']);
			}
			$client_obj = new Ep_Quote_Client();
			if($res)
			{
				if($res[0]['product']=='translation')
				$view['title'] = $this->product_array[$res[0]['product']]." ".$this->producttype_array[$res[0]['product_type']]." ".$this->getCustomName("EP_LANGUAGES",$res[0]['language_source'])." au ".$this->getCustomName("EP_LANGUAGES",$res[0]['language_dest']);
				else
				$view['title'] = $this->product_array[$res[0]['product']]." ".$this->producttype_array[$res[0]['product_type']]." in ".$this->getCustomName("EP_LANGUAGES",$res[0]['language_source']);
				
                                if($res[0]['product_type']=="autre")
                                {
                                    $view['title'] .= " ".$res[0]['product_type_other'];
                                }
                                /* $view['chargeble'] = 'chargable';
				$view['percentage'] = '50'; */
				if($res[0]['before_prod']=='yes')
				$view['priority'] = 'blocker';
				else
				$view['priority'] = 'non blocker';
				/* If free mission Turnover will be Zero and showed as Free */
				if($res[0]['free_mission']=="yes")
					$view['turnover'] = "Free";
				elseif($res[0]['package']=='team')
				$view['turnover'] = $res[0]['turnover']+$res[0]['team_fee']*$res[0]['team_packs'];
				else
				$view['turnover'] = $res[0]['turnover'];
				$view['production_cost'] = $res[0]['cost'];
				$view['contract_id'] = $res[0]['quotecontractid'];
				$view['mission_id'] = $res[0]['qmid'];
				$view['from_date'] = date('d M Y',strtotime($res[0]['assigned_at']));
				if($res[0]['mission_length_option']=='hours')
				$no_days = ceil($res[0]['mission_length']/24);
				else
				$no_days = $res[0]['mission_length'];
				$view['to_date'] = date('d M Y',strtotime($res[0]['assigned_at']."+ $no_days days"));
				$view['currency'] = $res[0]['sales_suggested_currency'];
				if($view['seo_action']=="view")
				$disabled="disabled";
				else
				$disabled = "";
				$this->_view->viewarray = $view;
				
				$this->_view->seofiles = $this->getSEOFiles($res[0]['documents_path'],$res[0]['documents_name'],$res[0]['linked_to'],$disabled);
				
				$this->render('seo-task');
			}
		}
	}
	
	// To Insert and Update SEO Task
	function submitSeotaskAction()
	{
		if($this->_request->isPost())
		{
			$request = $this->_request->getParams();
			$task_obj = new Ep_Quote_Task();
			$save = array();
			$save['task_title'] = $request['title'];
			$save['comments'] = $request['comment'];
			$save['updated_by'] = $this->adminLogin->userId;
			$save['mission_type'] = 'seo';
			$save['linked_to'] = implode(",",$request['link_to']);
			if(trim($request['task_id']))
			{
				$save['updated_at'] = date('Y-m-d H:i:s');
				$task_obj->updateTask($save,$request['task_id']);
				$task_id = $request['task_id'];
			}
			else
			{
				$save['created_by'] = $this->adminLogin->userId;
				$save['created_at'] = date('Y-m-d H:i:s');
				$save['contract_mission_id'] = $request['cmid'];
				$task_id = $task_obj->insertTask($save);
				
				//Insert Logs
				$log_obj=new Ep_Quote_QuotesLog();					
				$actionmessage = $log_obj->getActionSentence(17);
				$client_obj = new Ep_Quote_Client();
				$bo_user_details=$client_obj->getQuoteUserDetails($this->adminLogin->userId);
				if($bo_user_details!='NO')
					$tech_user="<strong>".$bo_user_details[0]['first_name'].' '.$bo_user_details[0]['last_name']."</strong>";
				else
					$tech_user="";
				$task_title = $save['task_title'];
				$actionmessage=strip_tags($actionmessage);
				eval("\$actionmessage= \"$actionmessage\";");
			
				$log_array['user_id']=$this->adminLogin->userId;
				$log_array['contract_id']=$request['contract_id'];
				$log_array['mission_id'] = $request['mission_id'];
				$log_array['quote_id'] = $request['quote_id'];
				$log_array['mission_type'] = 'task_seo';
				$log_array['comments'] = $request['comment'];
				$log_array['user_type']=$this->adminLogin->type;
				$log_array['action']='task_added';
				$log_array['action_at']=date("Y-m-d H:i:s");
				$log_array['action_sentence']=$actionmessage;
				
				$log_obj->insertLogs($log_array);
			}
			$this->uploadFiles($_FILES,$task_id,$task_obj,$request['document_name']);
			
			
		}
		$this->_redirect("/followup/seo?cmid=$request[cmid]&submenuId=ML13-SL4");
	}	
	
	//To get SEO Files with Checkboxes for Task
	function getSEOFiles($documents_path,$documents_name,$linked="",$disabled)
	{
		$seofiles = "";
		if($documents_path)
		{
			$exploded_file_paths = explode("|",$documents_path);
			$exploded_file_names = explode("|",$documents_name);
			
			if($linked):
				$k=0;
				$explode = explode(",",$linked);
				foreach($exploded_file_paths as $row)
				{
					$file_path=$this->mission_documents_path.$row;
					if(file_exists($file_path) && !is_dir($file_path))
					{
						$fname = $exploded_file_names[$k];
						if($fname=="")
							$fname = basename($row);
						$ofilename = pathinfo($file_path);
						if(in_array($k,$explode))
						$checked = "checked='checked'";
						else
						$checked = "";
						$seofiles .= '<label class="uni-checkbox">
										<input type="checkbox" value="'.$k.'"  name="link_to[]" '.$checked.' class="uni_style" '.$disabled.' />
										'.$fname.'
										</label>';	
					}
					$k++;
				}
			else:
			$k=0;
			foreach($exploded_file_paths as $row)
			{
				$file_path=$this->mission_documents_path.$row;
				if(file_exists($file_path) && !is_dir($file_path))
				{
					$fname = $exploded_file_names[$k];
					if($fname=="")
						$fname = basename($row);
					$ofilename = pathinfo($file_path);
					$seofiles .= '<label class="uni-checkbox">
									<input type="checkbox" value="'.$k.'" '.$disabled.' name="link_to[]" class="uni_style" />
									'.$fname.'
									</label>';	
				}
				$k++;
			}
			endif;
		}
		return $seofiles;
	}
	
	//TO upload task files
	function uploadFiles($FILES,$task_id,$task_obj,$pdocument_name)
	{
		if(count($FILES['mulitupload']['name'])>0)	
		{
			$update = false;
			$documents_path=array();
			$documents_name=array();
			foreach($FILES['mulitupload']['name'] as $index=>$task_files)
			{
				if($FILES['mulitupload']['name'][$index]):
				//upload quote documents
				$quoteDir=$this->task_documents_path.$task_id."/";
				if(!is_dir($quoteDir))
					mkdir($quoteDir,TRUE);
				chmod($quoteDir,0777);
				$document_name=frenchCharsToEnglish($FILES['mulitupload']['name'][$index]);
				$pathinfo = pathinfo($document_name);
				$document_name =$pathinfo['filename'].rand(100,1000).".".$pathinfo['extension'];
				$document_name=str_replace(' ','_',$document_name);
				$document_path=$quoteDir.$document_name;
				if (move_uploaded_file($FILES['mulitupload']['tmp_name'][$index], $document_path))
					chmod($document_path,0777);
					$update = true;
					$documents_path[]=$task_id."/".$document_name;
					$documents_name[]=  str_replace('|',"_",$pdocument_name[$index]);
				endif;
			}
			if($update)
			{
				 $quotes_update_data = array();
				 $quoteDetails=$task_obj->getTask($task_id);
				 $uploaded_documents1 = explode("|",$quoteDetails[0]['documents_path']);
				 $documents_path =array_merge($documents_path,$uploaded_documents1);
				 $quotes_update_data['documents_path']=implode("|",$documents_path);
				 $document_names =explode("|",$quoteDetails[0]['documents_name']);
				 $documents_name =array_merge($documents_name,$document_names);
				 $quotes_update_data['documents_name']=implode("|",$documents_name);
				 $task_obj->updateTask($quotes_update_data,$task_id);
			}
		}
	}
	
	//To validate Tech, Staff, Sales or SEO Missions
	function validateMissionAction()
	{
		if($this->_request->isPost() && $_SERVER['HTTP_X_REQUESTED_WITH']=='XMLHttpRequest')
		{
			$request = $this->_request->getParams();
			$cmid = $request['cmid'];
			
		 	$update = array();
			$update['cm_status'] = 'validated'; 
			$update['validated_by'] = $this->_view->userId; 
			$update['progress_percent'] = 100; 
			$update['validated_at'] = date('Y-m-d H:i:s'); 
			$contract_obj = new Ep_Quote_Quotecontract();
			$res = $contract_obj->updateContractMission($update,$cmid);
			
			//Insert into Logs
			$log_obj=new Ep_Quote_QuotesLog();	 			
			
			if($request['type']!='prod')
			{
				if((strtotime(date('Y-m-d'))>strtotime($request['to_date'])) && $request['type']!='sales')
				{
					$actionmessage = $log_obj->getActionSentence(19);
					$no_of_seconds = strtotime(date('Y-m-d H:i:s')) - strtotime($request['to_date']);
					$days = $no_of_seconds/(3600*24);
					$log_array['ontime'] = 0;
					if($days<1)
					{
						$days = ceil($no_of_seconds/3600);
						$delay = $days." hrs";
					}
					else
						$delay = ceil($days)." days";	
				}
				else
					$actionmessage = $log_obj->getActionSentence(18);
				
				if($request['type']=='seo')
				{
					$log_array['action']='validated_seo';
				}
				elseif($request['type']=='staff')
				{
					$log_array['action']='validated_staff';
				}
				elseif($request['type']=='sales')
				{
					$log_array['action']='validated_sales';
				}
				else
				$log_array['action']='validated_tech';
			}
			else
			{
				$actionmessage = $log_obj->getActionSentence(29);
				$log_array['action']='validated_prod';
			}
			$client_obj=new Ep_Quote_Client();
			$bo_user_details=$client_obj->getQuoteUserDetails($this->adminLogin->userId);
			$tech_user="<strong>".$bo_user_details[0]['first_name'].' '.$bo_user_details[0]['last_name']."</strong>";
			
			$mission_title = $request['title'];
			$actionmessage=strip_tags($actionmessage);
			eval("\$actionmessage= \"$actionmessage\";");
		
			$log_array['user_id']=$this->adminLogin->userId;
			$log_array['contract_id']=$request['contract_id'];
			$log_array['mission_id'] = $request['mission_id'];
			$log_array['quote_id'] = $request['quote_id'];
			$log_array['mission_type'] = $request['type'];
			$log_array['user_type']=$this->adminLogin->type;
			$log_array['action_at']=date("Y-m-d H:i:s");
			$log_array['action_sentence']=utf8_decode($actionmessage);	
			$log_obj->insertLogs($log_array);
			
			/* Sending Mail */
			$mail_obj=new Ep_Message_AutoEmails();
			$mail_content = $mail_obj->getAutoEmail(161);
			$subject = $mail_content[0]['Object'];
			$orgmessage = $mail_content[0]['Message'];
			$mission_type = $request['type'];
			$users = array();
			if($mission_type=="seo" || $mission_type=="prod" || $mission_type=="staff")
			{
				$quoteMission_obj=new Ep_Quote_QuoteMissions();
				$qmdetails =$quoteMission_obj->getMissionDetails(array('mission_id'=>$request['mission_id']));
				if($qmdetails[0]['product']=='translation')
					$mission_name = $this->product_array[$qmdetails[0]['product']]." ".$this->producttype_array[$qmdetails[0]['product_type']]." ".$this->getCustomName("EP_LANGUAGES",$qmdetails[0]['language_source'])." au ".$this->getCustomName("EP_LANGUAGES",$qmdetails[0]['language_dest']);
				else
					$mission_name = $this->product_array[$qmdetails[0]['product']]." ".$this->producttype_array[$qmdetails[0]['product_type']]." in ".$this->getCustomName("EP_LANGUAGES",$qmdetails[0]['language_source']);
					
				if($mission_type=="seo")
				{
					//$users = $contract_obj->getUsers("seomanager");
					$users = $client_obj->getQuoteUserDetails($this->adminLogin->userId);
					$mission_link = "<a href='".$this->url."/followup/seo?submenuId=ML13-SL4&cmid=".$cmid."'>click here</a>";
				}
				else
				{
					$users = $contract_obj->getUsers("multilingue");
					$mission_link = "<a href='".$this->url."/followup/prod?submenuId=ML13-SL4&cmid=".$cmid."'>click here</a>";
				}
			}
			elseif($mission_type=="tech")
			{
				$tech_obj=new Ep_Quote_TechMissions();
				$techdetails = $tech_obj->getTechMissionDetails(array('identifier'=>$request['mission_id']));
				$mission_name = $techdetails[0]['title'];
				//$users = $contract_obj->getUsers("techmanager");
				$users = $client_obj->getQuoteUserDetails($this->adminLogin->userId);
				$mission_link = "<a href='".$this->url."/followup/tech?submenuId=ML13-SL4&cmid=".$cmid."'>click here</a>";
			}
			$bo_user = $bo_user_details[0]['first_name']." ".$bo_user_details[0]['last_name'];
			eval("\$subject= \"$subject\";");
			
			foreach($users as $user)
			{
				$name = $user['first_name']." ".$user['last_name'];
				eval("\$message= \"$orgmessage\";");
				$mail_obj->sendEMail($this->mail_from,$message,$user['email'],$subject);
			}
			
			// sending mail to Assigned User
			$client_obj=new Ep_Quote_Client();
			$bo_user_details=$client_obj->getQuoteUserDetails($request['assigned_to']);
			$name = $bo_user_details[0]['first_name']." ".$bo_user_details[0]['last_name'];
			eval("\$message= \"$orgmessage\";");
			$mail_obj->sendEMail($this->mail_from,$message,$bo_user_details[0]['email'],$subject);
		}
	}
	
	//To get Task files for view, edit and download
	function gettaskfiles($filepaths,$filenames,$task_id,$tech_action="")
	{
		$files  = "";
		$exploded_file_paths = explode("|",$filepaths);
		$exploded_file_names = explode("|",$filenames);
		$k=0;
		if($tech_action=='edit')
		{
			foreach($exploded_file_paths as $row)
			{
			$file_path=$this->task_documents_path.$row;
			if(file_exists($file_path) && !is_dir($file_path))
			{
				$fname = $exploded_file_names[$k];
				if($fname=="")
					$fname = basename($row);
				$ofilename = pathinfo($file_path);
				/* <span class="deletequote" rel="'.$k.'_'.$quote_details[0]['identifier'].'"> <i class="icon-adt_trash"></i></span> */
				$files .= '<div class="topset2"><a href="/followup/download-document?type=task&task_id='.$task_id.'&index='.$k.'">'.$fname.'</a><span class="deletetask edit" rel="'.$k.'_'.$task_id.'"> <i class="splashy-error_x"></i></span></div>';	
			}
			$k++;
			}
		}
		else
		{
		foreach($exploded_file_paths as $row)
		{
			$file_path=$this->task_documents_path.$row;
			if(file_exists($file_path) && !is_dir($file_path))
			{
				$fname = $exploded_file_names[$k];
				if($fname=="")
					$fname = basename($row);
				$ofilename = pathinfo($file_path);
				/* <span class="deletequote" rel="'.$k.'_'.$quote_details[0]['identifier'].'"> <i class="icon-adt_trash"></i></span> */
				$files .= '<tr><td width="30%">'.$fname.'</td><td width="35%">'.substr($ofilename['filename'],0,-3).".".$ofilename['extension'].'</td><td width="20%">'.formatSizeUnits(filesize($file_path)).'</td><td align="center" width="15%"><a href="/followup/download-document?type=task&task_id='.$task_id.'&index='.$k.'"><i style="margin-right:5px" class="splashy-download"></i></a><span class="deletetask" rel="'.$k.'_'.$task_id.'"> <i class="icon-adt_trash"></i><td></tr>';	
			}
			$k++;
		}
		}
		return $files;
	}
	
	//To download Task Files
	function downloadDocumentAction()
	{
		$request = $this->_request->getParams();
		$this->_redirect("/BO/download-quote.php?type=".$request['type']."&index=".$request['index']."&task_id=".$request['task_id']);
	}
	
	//To download PO / Contract Files
	function downloadPoAction()
	{
		$request = $this->_request->getParams();
		$this->_redirect("/BO/download-po.php?cid=".$request['cid']);
	}
	
	//Delete task documents through Ajax
	function deleteDocumentAction()
	{
		if($this->_request->isPost() && $_SERVER['HTTP_X_REQUESTED_WITH']=='XMLHttpRequest')
		{
			$parmas = $this->_request->getParams();
			$explode_identifier = explode("_",$parmas['identifier']);
			$offset = $explode_identifier[0];
			$identifier = $explode_identifier[1];
			$task_obj=new Ep_Quote_Task();
			$result = $task_obj->getTask($identifier);
			$documents_paths = explode("|",$result[0]['documents_path']);
			$documents_names = explode("|",$result[0]['documents_name']);
			unlink($this->task_documents_path.$documents_paths[$offset]);
			unset($documents_paths[$offset]);
			unset($documents_names[$offset]);
			$data['documents_path']	= implode("|",$documents_paths);
			$data['documents_name']	= implode("|",$documents_names);
			$task_obj->updateTask($data,$identifier);
			$documents_paths = array_values($documents_paths);
			$documents_names = array_values($documents_names);
			
			$files = "";
			if($request['edit'])
			{
				$k=0;
				foreach($documents_paths as $row)
				{
					if(file_exists($this->task_documents_path.$documents_paths[$k]) && !is_dir($this->task_documents_path.$documents_paths[$k]))
					{
						$fname = $documents_names[$k];
						if($fname=="")
							$fname = basename($row);
						$file_path=$this->task_documents_path.$row;
						$ofilename = pathinfo($file_path);
					/* <span class="deletequote" rel="'.$k.'_'.$quote_details[0]['identifier'].'"> <i class="icon-adt_trash"></i></span> */
					$files .= '<div class="topset2"><a href="/followup/download-document?type=task&task_id='.$identifier.'&index='.$k.'">'.$fname.'</a><span class="deletetask edit" rel="'.$k.'_'.$identifier.'"> <i class="splashy-error_x"></i></span></div>';	
					}
					$k++;
				}
			}
			else
			{
			$k=0;
			foreach($documents_paths as $row)
			{
				if(file_exists($this->task_documents_path.$documents_paths[$k]) && !is_dir($this->task_documents_path.$documents_paths[$k]))
				{
                    $fname = $documents_names[$k];
					if($fname=="")
						$fname = basename($row);
					$file_path=$this->task_documents_path.$row;
					$ofilename = pathinfo($file_path);
				/* <span class="deletequote" rel="'.$k.'_'.$quote_details[0]['identifier'].'"> <i class="icon-adt_trash"></i></span> */
				$files .= '<tr><td width="30%">'.$fname.'</td><td width="35%">'.substr($ofilename['filename'],0,-3).".".$ofilename['extension'].'</td><td width="20%">'.formatSizeUnits(filesize($file_path)).'</td><td align="center" width="15%"><a href="/followup/download-document?type=task&task_id='.$identifier.'&index='.$k.'"><i style="margin-right:5px" class="splashy-download"></i></a><span class="deletetask" rel="'.$k.'_'.$identifier.'"> <i class="icon-adt_trash"></i><td></tr>';	
				}
				$k++;
			}	
			}
			echo utf8_encode($files);
		}
	}
	
	// Get Colorcode to display in progress bar
	function getColor($percentage)
	{
		if($percentage>=0 && $percentage<15)
				$offset = '#ff0000';
		else if($percentage>=15 && $percentage<25)
			$offset = '#ff7200';
		else if($percentage>=25 && $percentage<35)
			$offset = '#ffa200';
		else if($percentage>=35 && $percentage<50)
			$offset = '#ffd21d';
		else if($percentage>=60 && $percentage<80)
			$offset = '#f2f43c';
		else if($percentage>=80 && $percentage<90)
			$offset = '#cbf43c';
		else 
			$offset = '#3fe805';
		return $offset;
	}
	# Deprecated
	//To get Category Name
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
	
	//To sort the array comments
	function sortTimewise($myArray)
	{
		usort($myArray,function($a,$b)
			{
				return $a['created_at'] > $b['created_at'];
			});
		return $myArray;
	}
	/* To send mail to contributors in delivery followup */
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
			$object="Vous avez re&ccedil;u un email-Edit-place";
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
    //repeat delivery form edit
    public function editRepeatDeliveryAction()
    {
    	$repeatParams=$this->_request->getParams();
    	$delivery_id=$repeatParams['delivery_id'];
    	//get repeat delivery Details
		$repeat_obj=new Ep_Quote_DeliveryRepeat();
		$repeat_delivery_details=$repeat_obj->RepeatDeliveryDetails($delivery_id);
		if($repeat_delivery_details)
		{
			foreach ($repeat_delivery_details as $key => $delivery)
			{
				$repeat_delivery['repeat_id']=$delivery['repeat_id'];
				$repeat_delivery['repeat_option']=$delivery['repeat_option'];
				$repeat_delivery['repeat_every']=$delivery['repeat_every'];
				$repeat_delivery['repeat_on']=explode(",",$delivery['repeat_days']);				 
				$repeat_delivery['repeat_start']=$delivery['repeat_start'];				
				$repeat_delivery['repeat_end']=$delivery['repeat_end'];
				$repeat_delivery['end_on']=$delivery['end_date'];
				$repeat_delivery['enabled']=$delivery['enabled'];
				
				$repeat_delivery['after_occurance']=$delivery['end_occurances'];
			}   	
			$this->_view->repeat_delivery=$repeat_delivery;		
			$this->render('repeat-delivery-popup-edit');	
		}
    }	
    //save repeat form data
    function saveRepeatDeliveryAction()
    {
    	if($this->_request->isPost())
		{
	    	$repeatParams=$this->_request->getParams();
	    	$repeat_id=$repeatParams['repeat_id'];
	    	//echo "<pre>";print_r($repeatParams);exit;
	    	if($repeat_id)
	    	{
		    	$enabled=$repeatParams['enabled'];
		    	$repeat_option=$repeatParams['repeat_option'];
		    	$repeat_every=$repeatParams['repeat_every'];
		    	if($repeat_option=='week' || $repeat_option=='week_b')
		    		$repeat_days=$repeatParams['repeat_on'];		    	
		    	
		    	$repeat_end=$repeatParams['repeat_end'];
		    	if($repeat_end=='after')
		    		$end_occurances=$repeatParams['after_occurance'];
		    	elseif($repeat_end=='on')
		    		$end_date=$repeatParams['end_on'];
		    	$repeat_obj=new Ep_Quote_DeliveryRepeat();
		    	$repeat_array['repeat_option']=$repeat_option;
		    	$repeat_array['repeat_every']=$repeat_every;
		    	$repeat_array['repeat_days']=implode(",",$repeat_days);		    	
		    	$repeat_array['repeat_end']=$repeat_end;
		    	$repeat_array['end_occurances']=$end_occurances;
		    	$repeat_array['end_date']=$end_date;
		    	$repeat_array['enabled']=$enabled;
		    	//echo "<pre>";print_r($repeat_array);exit;
		    	$repeat_obj->updateRepeatDelivery($repeat_array,$repeat_id);
		    }
	    	
	    }	
    	
    	
    }
	
	// To upload prod files
	function prodfilesuploadAction()
	{
		/* Uploading Files */
		$request = $this->_request->getParams();
		$contract_obj = new Ep_Quote_Quotecontract();
		if(!empty($_POST))
		{
			if(count($_FILES['seo_documents']['name'])>0)	
			{
				$update = false;
				$uploaded_documents = array();
				$uploaded_document_names = array();
				$k = 0;
				$missionIdentifier = $request['cmid'];
				
				foreach($_FILES['seo_documents']['name'] as $row):
				if($_FILES['seo_documents']['name'][$k])
				{
					$missionDir=$this->mission_documents_path.$missionIdentifier."/";
					if(!is_dir($missionDir))
						mkdir($missionDir,TRUE);
						chmod($missionDir,0777);
									 
					$document_name=frenchCharsToEnglish($_FILES['seo_documents']['name'][$k]);
					$document_name=str_replace(' ','_',$document_name);
					$pathinfo = pathinfo($document_name);
					$document_name =$pathinfo['filename'].rand(100,1000).".".$pathinfo['extension'];
					$document_path=$missionDir.$document_name;
									 
					if(move_uploaded_file($_FILES['seo_documents']['tmp_name'][$k],$document_path))
					{
						chmod($document_path,0777);
					}
					//$seo_mission_data['documents_path']=$missionIdentifier."/".$document_name;
					$uploaded_documents[] = $missionIdentifier."/".$document_name;
					$uploaded_document_names[] = str_replace('|',"_",$request['document_name'][$k]);
					$update = true;
				}
				$k++;
				endforeach;
				if($update)
				{
					$result =$contract_obj->getContractMission('','','',$missionIdentifier);
					$uploaded_documents1 = explode("|",$result[0]['documents_path']);
					$uploaded_documents =array_merge($uploaded_documents,$uploaded_documents1);
					$seo_mission_data['documents_path'] = implode("|",$uploaded_documents);
					$document_names =explode("|",$result[0]['documents_name']);
					$document_names =array_merge($uploaded_document_names,$document_names);
					$seo_mission_data['documents_name'] = implode("|",$document_names);
					$contract_obj->updateContractMission($seo_mission_data,$missionIdentifier); 
				}
			}
		}
		$this->_redirect('followup/prod?cmid='.$request['cmid'].'&submenuId=ML13-SL4');
	}
	/* To convert stencils with token to code */
	function convertStencilsAction()
	{
		$aoParams=$this->_request->getParams();
    	$aoObject=new EP_Quote_Ongoing();
    	$ao_id=$aoParams['ao_id'];
    	$client_id=$aoParams['client_id'];
    	$aoParams['sorttype']='all';
		if($ao_id && $client_id)
    	{
			$aoDetails=$aoObject->getOngoingAODetails($aoParams,1);
			//echo "<PRE>";print_r($aoDetails);exit;
			if($aoDetails)	
			{
				//if($aoDetails[0]['ebooker_cat_id'])
				/* {
					$ebooker_obj = new Ep_Ebookers_Stencils();
					$tokens = (array) $ebooker_obj->getTokens(array('cat_id'=>$aoDetails[0]['ebooker_cat_id']));
					$token_name = $token_code = $token_hidden = array();
					foreach($tokens as $token)
					{
						$token_name[] = $token['token_name'];
						$token_code[] = $token['token_code'];
						if($token['manditory']=='hidden')
							$token_hidden[] = 'yes';
						else
							$token_hidden[] = 'no';
					}
				} */
				// Getting title from Mission
				if($aoDetails[0]['product']=='translation')
				$aoDetails[0]['mission_title'] = $this->product_array[$aoDetails[0]['product']]." ".$this->producttype_array[$aoDetails[0]['product_type']]." ".$this->getCustomName("EP_LANGUAGES",$aoDetails[0]['language_source'])." au ".$this->getCustomName("EP_LANGUAGES",$aoDetails[0]['language_dest']);
				else
				$aoDetails[0]['mission_title'] = $this->product_array[$aoDetails[0]['product']]." ".$this->producttype_array[$aoDetails[0]['product_type']]." in ".$this->getCustomName("EP_LANGUAGES",$aoDetails[0]['language_source']);
				$aoDetails[0]['publishdate'] = date('d M Y',$aoDetails[0]['published_at']);
				// Delivery End Date
				if($aoDetails[0]['proofread_end'])
				$aoDetails[0]['delivery_end'] = date('d M Y',strtotime($aoDetails[0]['proofread_end']));
				else
				{
					$days = ceil($aoDetails[0]['total_time']/(60*24));
					//$aoDetails[0]['delivery_end'] = date('d M Y',strtotime($aoDetails[0]['created_at']+ " $days days"));
					$aoDetails[0]['delivery_end'] = date('d M Y',strtotime("+$days days",$aoDetails[0]['published_at']));
				}
				//getting All article details of AO
				$aoParams['missiontest']=$aoDetails[0]['missiontest'];
				$articleDetails=$aoObject->getOngoingArticleStencilDetails($aoParams);
				//echo "<pre>";print_r($articleDetails);exit;
				//getting writer and corrector Bidding Details
				$bcnt=0;
				$ebooker_obj = new Ep_Ebookers_Stencils();
				$article_obj = new Ep_Delivery_Article();
				foreach($articleDetails as $article)
				{
					if($article['writerParticipation']=='published')
					{
						$tokens = (array) $ebooker_obj->getTokens(array('cat_id'=>$article['ebooker_cat_id']));
						$token_name = $token_name_display =  $token_code = $token_hidden = array();
						$hidden_token = "";
						
						foreach($tokens as $token)
						{
							$token_name[] = "/\b".urlencode($token['token_name'])."\b/";
							/* $tok = urlencode($token['token_name']);
							$token_name[] = "/(\b".$tok."\b)|(".$tok."(?=,))/"; */
							$token_name_display[] = $token['token_name'];
							$token_code[] = ($token['token_code']);
							if($token['token_type']=='hidden')
							{
								$token_hidden[] = 'yes';
								$hidden_token = $token['token_code'];
								$hidden_token_name = $token['token_name'];
							}
							else
								$token_hidden[] = 'no';
						}
						$tokensinfo = $ebooker_obj->getStencils(array('cat_id'=>$article['ebooker_cat_id']));
						$articleDetails[$bcnt]['token_category'] = $tokensinfo[0]['category_name'];
						$articleDetails[$bcnt]['token_theme'] = $tokensinfo[0]['theme_name'];
						$articleDetails[$bcnt]['token_names'] = implode(",",$token_name_display);
						$res = $ebooker_obj->getTexts(array('pid'=>$article['writerParticipationId']));
						$texts = explode("###$$$###",$res[0]['article_doc_content']);
						$converted = $this->convertTokens($token_name,$token_code,$token_hidden,$texts,$hidden_token,$hidden_token_name);
						$update = array();
						$update['token_replacement'] = $converted['token_replacements'];
						$update['token_code_replacement'] = $converted['hidden_replacement'];
						unset($converted['token_replacements']);
						unset($converted['hidden_replacement']);
						$where = " id = $article[id]";
						$article_obj->updateArticle($update,$where);
						$articleDetails[$bcnt]['texts'] = $converted; 
					}
					else
						$articleDetails[$bcnt]['texts'] = array();
					$bcnt++;					
				}
				//echo "<PRE>";print_r($articleDetails);exit;
				$this->_view->aoDetails=$aoDetails;
				$this->_view->articleDetails=$articleDetails;
				$this->_view->render("convert-stencils");	
			}
			else
    			$this->_redirect("/contractmission/missions-list?submenuId=ML13-SL4");
    	}
    	else
    		$this->_redirect("/contractmission/missions-list?submenuId=s-SL4");
	}
	function convertTokens($token_name,$token_code,$token_hidden,$texts,$hidden_token="",$hidden_token_name="")
	{
		/* mb_internal_encoding("UTF-8");
		mb_regex_encoding("UTF-8"); */
		if(!count($texts)):
		$texts = array();
		$texts[] = "he bookwarms among you will love HOMEGOODSSTORE, a place where you can pour over the pages of a plethora of books, discover new authors, and may be, if you are lucky, HOMEGOODSSTORE find that first edition that HOMEGOODSSTORE you have dreamt of owning since you were a child. CITY NAME signatureé";
		$texts[] = "you will love HOMEGOODSSTORE, a place where you can pour over the pages of a plethora of books, discover new authors, and may be, if you are lucky, HOMEGOODSSTORE find that first edition that HOMEGOODSSTORE you have dreamt of owning since you were a child. CITY NAME signatureé";
		$texts[] = "Notre solution de paiement en ligne vous garantit une transaction en toute tranquillité HOMEGOODSSTORE  since you were a child. CITY NAME signatureé you have dreamt of owning since you were a child";
		$texts[] = "and may be, if you are lucky, HOMEGOODSSTORE find that first edition that HOMEGOODSSTORE  you were a child. CITY NAME signatureé edit-place est votre médiateur  En cas de contestation (délai de livraison, reprise darticles, remboursement HIDDEN.";
		$texts[] = "discover new authors, and may be, if you are lucky, HOMEGOODSSTORE find that first edition that HOMEGOODSSTORE you  were a child. CITY NAME signature é Hidden";
		endif;
		$converted = $token_replacements = $hidden_token_replacements = array();
		//$hidden_token = "poi.getTheme('poi')";
		$i = 0;
		foreach($texts as $row)
		{
			$converted[$i]['text'] = ($row);
			/* $token_replacement = $row;
			$i=0;
			foreach($token_name as $tokenName)
			{
				$token_replacement = preg_replace($tokenName,$token_code[$i++],($token_replacement));
			} */
			$token_replacement = urldecode(preg_replace($token_name,$token_code,urlencode(str_replace(array(",",".","!"),array(" , "," . "," ! "),$row))));
			$token_replacement = str_replace(array(" , "," . "," ! "),array(",",".","!"),$token_replacement);
			$converted[$i]['token_replacement'] = $token_replacement;
			$hidden_token_replacements[] = $token_replacements[] = $token_replacement;
			//if(strpos($row,$hidden_token_name)!==false)
			if($hidden_token)
			{
				$replacement = "<p>"."{if($hidden_token){'".$token_replacement."'}else{''}}"."</p>";
				$converted[$i++]['hidden_replacement'] = $replacement;
				$hidden_token_replacements[] = $replacement;
			}
			else
				$converted[$i++]['hidden_replacement'] = $token_replacement;
		}
		$converted['token_replacements'] = implode('###$$$###',$token_replacements);
		$converted['hidden_replacement'] = implode('###$$$###',$hidden_token_replacements);
		return $converted;
	}
	/* Cron function not in use */
	function updateMissionPercentageAction()
	{
		$delivery = new Ep_Quote_Delivery();
		$contract_obj = new Ep_Quote_Quotecontract();
		$res = $delivery->getDeliveries();
		$update = array();
		foreach($res as $missions)
		{
			if($missions['published_articles']>$missions['volume'])
			$missions['published_articles'] = $missions['volume'];
			$update['progress_percent'] = round(($missions['published_articles']/$missions['volume'])*100);
			/* if($update['progress_percent']==100)
			$update['cm_status'] = 'validated';
			else
			$update['cm_status'] = 'ongoing'; */
			$res1 = $contract_obj->updateContractMission($update,$missions['contract_mission_id']);
		}
	}
	
	function stencilsAction()
	{
		$ebooker_obj = new Ep_Ebookers_Stencils();
		$themes = (array) $ebooker_obj->getStencils(array('theme'=>true));
		$this->_view->tokens = $this->_view->categories = $sthemes = array();
		foreach($themes as $theme)
		{
			$sthemes[$theme['theme_id']] = $theme['theme_name'];
		}
		
		$language_array=$this->_arrayDb->loadArrayv2("EP_LANGUAGES", $this->_lang);			
		natsort($language_array);
		$this->_view->languages_array=$language_array;
		
		$this->_view->sthemes = $sthemes;
		$this->_view->stheme_selected = $this->_view->cat_selected = $this->_view->stencils = "";
		$this->_view->copval = false;
		if($this->_request->isPost())
		{
			$request = $this->_request->getParams();
			{
				$category = (array) $ebooker_obj->getStencils(array('category'=>true,'theme_id'=>$request['category']));
				/* $categories = array();
				foreach($category as $cat)
				{
					$categories[$cat['cat_id']] = $cat['category_name'];
				} */
				
				/* To get Missions */
				
				
				$this->_view->categories = $category;
				if(is_array($request['category_variation']))
				{
					$cat_ids = array_filter($request['category_variation']);
					$cat_ids = implode(",",$cat_ids);
				}
				else
					$cat_ids = "";
				
				if(is_array($request['tokens']))
				{
					$request_token_ids = array_filter($request['tokens']);
				}
				else
					$request_token_ids = "";
				/* To load default missions */
				$missions = $ebooker_obj->getMissions(array('token_ids'=>$request_token_ids));
				$missions_array = array();
				if($missions)
				{
					foreach($missions as $row)
					{
						if($row['product']=='translation')
						{
							$title = $this->product_array[$row['product']]." ".$this->producttype_array[$row['product_type']]." ".$this->getCustomName("EP_LANGUAGES",$row['language_source'])." au ".$this->getCustomName("EP_LANGUAGES",$row['language_dest']);
						}
						else
						{
							$title = $this->product_array[$row['product']]." ".$this->producttype_array[$row['product_type']]." in ".$this->getCustomName("EP_LANGUAGES",$row['language_source']);
						}
						
						$missions_array[$row['contract_mission_id']] = utf8_encode($title);
					}
				}
				$this->_view->missions = $missions_array;
				$this->_view->mission = $request['missions'];
					//array_filter($cat_ids);
				$tokens = $ebooker_obj->getTokens(array('cat_ids'=>$cat_ids,'join'=>true));
				
				foreach($tokens as $token)
				{
					$token_names=explode("##",$token['token_name']);
					foreach($token_names as $token_name) 
						$all_tokens[]=$token_name;
				}				
				//echo "<pre>";print_r($all_tokens);exit;
				$percentage_tokens = array('PERCENT1','PERCENT2','ALLPERCENTS');
				$token_name = $token_code = $token_desc = $tokens_notfound = array();
				$hidden_token = "";
				$tokens_view = $hidden_tokens = array();
				foreach($tokens as $token)
				{
					if (strpos($token['token_name'],'##') !== false) 
					{
						$hash_exploaded_name = explode("##",$token['token_name']);
						$hash_exploaded_code = explode("##",$token['token_code']);
						foreach($hash_exploaded_name as $key => $value)
						{
							 $token_name[] =  "/\b".urlencode(trim($value))."\b/";
							if(in_array($value,$percentage_tokens))
								$percentage = "%25";
							else
								$percentage = "";
								
							//$token_code[] = ('" %2B '.$hash_exploaded_code[$key].' %2B "');
							$condition_token=$value."_CONDITION";						
							if(in_array($condition_token,$all_tokens) && ($token['token_type']=="mandatory" || $token['token_type']=="optional"))
							{
								$token_code[] = ("' %2B ".$hash_exploaded_code[$key]." %2B '".$percentage);
							}
							/* elseif($token['token_type']=="optional")
							{
								$token_code[] = ("' %2B ".$hash_exploaded_code[$key]." %2B '");
							}  */
							else 
								$token_code[] = ('" %2B '.$hash_exploaded_code[$key].' %2B "'.$percentage);
							$token_desc[] = ("$%7B".$hash_exploaded_code[$key]."%7D");
						}
					}
					else
					{
						$token_name[] = "/\b".urlencode(trim($token['token_name']))."\b/";
						if(in_array($token['token_name'],$percentage_tokens))
								$percentage = "%25";
						else
								$percentage = "";
						//$token_code[] = ('" %2B '.$token['token_code'].' %2B "');
						$condition_token=$token['token_name']."_CONDITION";			
						if(in_array($condition_token,$all_tokens) && ($token['token_type']=="mandatory" || $token['token_type']=="optional"))
						{
							$token_code[] = ("' %2B ".$token['token_code']." %2B '".$percentage);
						}
						/* elseif($token['token_type']=="optional")
						{
							$token_code[] = ("' %2B ".$token['token_code']." %2B '");
						}  */
						else 
							$token_code[] = ('" %2B '.$token['token_code'].' %2B "'.$percentage);
						$token_desc[] = ("$%7B".$token['token_code']."%7D");
					}
					//if($token['token_type']=="mandatory" || $token['token_type']=="optional")
					//print_r($token);
					if($token['token_type']=="mandatory")
					{
						$tokens_view[] = $token;
						$tokens_notfound[$token['token_id']] = $token;
						/* $condition_token=$token['token_name']."_CONDITION";						
						 if(in_array($condition_token,$all_tokens))
							$token_code[] = ("' %2B ".$token['token_code']." %2B '");
						else */	
						/* $token_code[] = ('" %2B '.$token['token_code'].' %2B "');
					    $token_desc[] = ("$%7B".$token['token_code']."%7D"); */
					}
					else
					{
						/* $token_code[] = ('" %2B '.$token['token_code'].' %2B "');
						$token_desc[] = ("$%7B".$token['token_code']."%7D"); */
						$hidden_tokens[] = $token;
					}
				}
				$this->_view->tokens = $tokens_view;
				$this->_view->hidden_tokens = $hidden_tokens;
				$this->_view->tokens_selected = $tokens_selected = $request['tokens'];
				$this->_view->stheme_selected = $request['category'];
				$this->_view->cat_selected = $request['category_variation'];
				$this->_view->language_selected=$language_selected=$request['language'];
			
				/* cat description */
				$lang_desc = $ebooker_obj->getLanguageDesc(array('theme_id'=>$request['category'],'language'=>$request['language']));
			
				if(!empty($lang_desc[0]['title_condition']))
				{
					$this->_view->cat_desc = $lang_desc[0]['title_condition'];
				}
				/* else if(!empty($category[0]['title_condition']))
				{
					$this->_view->cat_desc = $category[0]['title_condition'];
				} 
				else if(!empty($category[0]['themedesc']))
				{
					$converted = $this->convertToken($token_name,$token_desc,"",addslashes(trim($category[0]['themedesc'])));
					/* echo "<PRE>";
					print_r($converted); 
					$this->_view->cat_desc = $converted[0]['token_replacement'];
				} */
				else if(!empty($lang_desc[0]['title']))
				{
					$converted = $this->convertToken($token_name,$token_desc,"",addslashes(trim($lang_desc[0]['title'])));
					/* echo "<PRE>";
					print_r($converted); */
					$this->_view->cat_desc = $converted[0]['token_replacement'];
				}
				/* Short Desc */
				$sn = $ebooker_obj->getStencilNumber($request['category']);
				$sn = count($sn)+1;
				$this->_view->short_desc = $this->adminLogin->loginName." - ".$this->getCustomName("EP_LANGUAGES",$language_selected)." - ".$category[0]['theme_name']." - Stencil ".$sn;
				/* Getting random stencils */
				$tokens_selected_comma = implode(",",$tokens_selected);
				$missions = "";
				if($request['missions'])
				{
					$missions = implode(",",$request['missions']);
				}
				
				$stencils = $ebooker_obj->getValidStencils(array('token_ids'=>$tokens_selected_comma,'user_id'=>$this->adminLogin->userId,"language"=>$language_selected,'missions'=>$missions));
				$req_stencils = $stencils_used = array();
				if($stencils)
				{
					foreach($tokens_selected as $token)
					{
						$req_stencil = $this->getReqStencils($token,$stencils,$stencils_used);
						if($req_stencil!=-1)
						{
							$converted = $this->convertToken($token_name,$token_code,"",addslashes($stencils[$req_stencil]['stencil_content']));
							$stencils[$req_stencil]['token_replace'] = $converted[0]['token_replacement'];
							$explde_name = explode("##",$stencils[$req_stencil]['token_name']);
							foreach($explde_name as $key => $tn)
							$explde_name[$key] = $tn."_CONDITION";
							$stencils[$req_stencil]['token_condition'] = implode("##",$explde_name);
							$req_stencils[$token] = $stencils[$req_stencil];
							$req_stencils[$token]['found'] = true;
							$stencils_used[] = $stencils[$req_stencil]['id'];
						}
						else
						{
							$req_stencils[$token]['found'] = false;
							$req_stencils[$token]['token'] = $tokens_notfound[$token];
						}
						//unset($stencils[$req_stencil]);
					}
				}
				$this->_view->stencils = $req_stencils;
			}
		}
		$this->render('stencils_follow_up_page');
	}
    /* *** created on 17.12.2015 *** */
    // updating stencils action so thatif token is  wheater then condition are created and updated //
    function stencilsV2Action()
    {
        $ebooker_obj = new Ep_Ebookers_Stencils();
        $themes = (array) $ebooker_obj->getStencils(array('theme'=>true));
        $this->_view->tokens = $this->_view->categories = $sthemes = array();
        foreach($themes as $theme)
        {
            $sthemes[$theme['theme_id']] = $theme['theme_name'];
        }
        $language_array=$this->_arrayDb->loadArrayv2("EP_LANGUAGES", $this->_lang);
        natsort($language_array);
        $this->_view->languages_array=$language_array;
        $this->_view->sthemes = $sthemes;
        $this->_view->stheme_selected = $this->_view->cat_selected = $this->_view->stencils = "";
        $this->_view->copval = false;
        if($this->_request->isPost())
        {
            $request = $this->_request->getParams();
            {
                $category = (array) $ebooker_obj->getStencils(array('category'=>true,'theme_id'=>$request['category']));
                /* $categories = array();
                foreach($category as $cat)
                {
                    $categories[$cat['cat_id']] = $cat['category_name'];
                } */
                /* To get Missions */
//echo "<pre>";print_r($category);exit;
                $this->_view->categories = $category;
                if(is_array($request['category_variation']))
                {
                    $cat_ids = array_filter($request['category_variation']);
                    $cat_ids = implode(",",$cat_ids);
                }
                else
                    $cat_ids = "";
                if(is_array($request['tokens']))
                {
                    $request_token_ids = array_filter($request['tokens']);
                }
                else
                    $request_token_ids = "";
                /* To load default missions */
                $missions = $ebooker_obj->getMissions(array('token_ids'=>$request_token_ids));
                $missions_array = array();
                if($missions)
                {
                    foreach($missions as $row)
                    {
                        if($row['product']=='translation')
                        {
                            $title = $this->product_array[$row['product']]." ".$this->producttype_array[$row['product_type']]." ".$this->getCustomName("EP_LANGUAGES",$row['language_source'])." au ".$this->getCustomName("EP_LANGUAGES",$row['language_dest']);
                        }
                        else
                        {
                            $title = $this->product_array[$row['product']]." ".$this->producttype_array[$row['product_type']]." in ".$this->getCustomName("EP_LANGUAGES",$row['language_source']);
                        }
                        $missions_array[$row['contract_mission_id']] = utf8_encode($title);
                    }
                }
                $this->_view->missions = $missions_array;
                $this->_view->mission = $request['missions'];
                //array_filter($cat_ids);
                $tokens = $ebooker_obj->getTokens(array('cat_ids'=>$cat_ids,'join'=>true));
//echo "<pre>";print_r($tokens);exit;
                foreach($tokens as $token)
                {
                    $token_names=explode("##",$token['token_name']);
                    foreach($token_names as $token_name)
                        $all_tokens[]=$token_name;
                }
                //echo "<pre>";print_r($all_tokens);exit;
                $percentage_tokens = array('PERCENT1','PERCENT2','ALLPERCENTS');
                $token_name = $token_code = $token_desc = $tokens_notfound = array();
                $hidden_token = "";
                $tokens_view = $hidden_tokens = array();
                $categoryName = $ebooker_obj->getcategoryName($request['category']);
                if( $categoryName === 'Weather' || $categoryName === 'weather') {
                    foreach ($tokens as $token) {
                        if (strpos($token['token_name'], '##') !== false) {
                            $hash_exploaded_name = explode("##", $token['token_name']);
                            $hash_exploaded_code = explode("##", $token['token_code']);
                            foreach ($hash_exploaded_name as $key => $value) {
                                $token_name[] = "/\b" . urlencode(trim($value)) . "\b/";
                                if (in_array($value, $percentage_tokens))
                                    $percentage = "%25";
                                else
                                    $percentage = "";
                                $condition_token = $value . "_CONDITION";
                                if (in_array($condition_token, $all_tokens) && ($token['token_type'] == "mandatory" || $token['token_type'] == "optional")) {
                                    $token_code[] = ("' %2B " . $hash_exploaded_code[$key] . " %2B '" . $percentage);
                                }
                                else
                                    $token_code[] = ("' %2B " . $hash_exploaded_code[$key] . " %2B '" . $percentage);
                                $token_desc[] = ("$%7B" . $hash_exploaded_code[$key] . "%7D");
                            }
                        } else {
                            $token_name[] = "/\b" . urlencode(trim($token['token_name'])) . "\b/";
                            if (in_array($token['token_name'], $percentage_tokens))
                                $percentage = "%25";
                            else
                                $percentage = "";
                            $condition_token = $token['token_name'] . "_CONDITION";
                            if (in_array($condition_token, $all_tokens) && ($token['token_type'] == "mandatory" || $token['token_type'] == "optional")) {
                                $token_code[] = ("' %2B " . $token['token_code'] . " %2B '" . $percentage);
                            }
                            else
                                $token_code[] = ("' %2B " . $token['token_code'] . " %2B '" . $percentage);
                            $token_desc[] = ("$%7B" . $token['token_code'] . "%7D");
                        }
                        if ($token['token_type'] == "mandatory") {
                            $tokens_view[] = $token;
                            $tokens_notfound[$token['token_id']] = $token;
                        } else {
                            $hidden_tokens[] = $token;
                        }
                    }
                }
                else {
                    foreach ($tokens as $token) {
                        if (strpos($token['token_name'], '##') !== false) {
                            $hash_exploaded_name = explode("##", $token['token_name']);
                            $hash_exploaded_code = explode("##", $token['token_code']);
                            foreach ($hash_exploaded_name as $key => $value) {
                                $token_name[] = "/\b" . urlencode(trim($value)) . "\b/";
                                if (in_array($value, $percentage_tokens))
                                    $percentage = "%25";
                                else
                                    $percentage = "";
                                //$token_code[] = ('" %2B '.$hash_exploaded_code[$key].' %2B "');
                                $condition_token = $value . "_CONDITION";
                                if (in_array($condition_token, $all_tokens) && ($token['token_type'] == "mandatory" || $token['token_type'] == "optional")) {
                                    $token_code[] = ("' %2B " . $hash_exploaded_code[$key] . " %2B '" . $percentage);
                                } /* elseif($token['token_type']=="optional")
                            {
                                $token_code[] = ("' %2B ".$hash_exploaded_code[$key]." %2B '");
                            }  */
                                else
                                    $token_code[] = ('" %2B ' . $hash_exploaded_code[$key] . ' %2B "' . $percentage);
                                $token_desc[] = ("$%7B" . $hash_exploaded_code[$key] . "%7D");
                            }
                        } else {
                            $token_name[] = "/\b" . urlencode(trim($token['token_name'])) . "\b/";
                            if (in_array($token['token_name'], $percentage_tokens))
                                $percentage = "%25";
                            else
                                $percentage = "";
                            //$token_code[] = ('" %2B '.$token['token_code'].' %2B "');
                            $condition_token = $token['token_name'] . "_CONDITION";
                            if (in_array($condition_token, $all_tokens) && ($token['token_type'] == "mandatory" || $token['token_type'] == "optional")) {
                                $token_code[] = ("' %2B " . $token['token_code'] . " %2B '" . $percentage);
                            } /* elseif($token['token_type']=="optional")
                        {
                            $token_code[] = ("' %2B ".$token['token_code']." %2B '");
                        }  */
                            else
                                $token_code[] = ('" %2B ' . $token['token_code'] . ' %2B "' . $percentage);
                            $token_desc[] = ("$%7B" . $token['token_code'] . "%7D");
                        }
                        //if($token['token_type']=="mandatory" || $token['token_type']=="optional")
                        //print_r($token);
                        if ($token['token_type'] == "mandatory") {
                            $tokens_view[] = $token;
                            $tokens_notfound[$token['token_id']] = $token;
                            /* $condition_token=$token['token_name']."_CONDITION";
                             if(in_array($condition_token,$all_tokens))
                                $token_code[] = ("' %2B ".$token['token_code']." %2B '");
                            else */
                            /* $token_code[] = ('" %2B '.$token['token_code'].' %2B "');
                            $token_desc[] = ("$%7B".$token['token_code']."%7D"); */
                        } else {
                            /* $token_code[] = ('" %2B '.$token['token_code'].' %2B "');
                            $token_desc[] = ("$%7B".$token['token_code']."%7D"); */
                            $hidden_tokens[] = $token;
                        }
                    }
                }
                $this->_view->tokens = $tokens_view;
                $this->_view->hidden_tokens = $hidden_tokens;
                $this->_view->tokens_selected = $tokens_selected = $request['tokens'];
                $this->_view->stheme_selected = $request['category'];
                $this->_view->cat_selected = $request['category_variation'];
                $this->_view->language_selected=$language_selected=$request['language'];
                /* cat description */
                $lang_desc = $ebooker_obj->getLanguageDesc(array('theme_id'=>$request['category'],'language'=>$request['language']));
                if(!empty($lang_desc[0]['title_condition']))
                {
                    $this->_view->cat_desc = $lang_desc[0]['title_condition'];
                }
                /* else if(!empty($category[0]['title_condition']))
                {
                    $this->_view->cat_desc = $category[0]['title_condition'];
                }
                else if(!empty($category[0]['themedesc']))
                {
                    $converted = $this->convertToken($token_name,$token_desc,"",addslashes(trim($category[0]['themedesc'])));
                    /* echo "<PRE>";
                    print_r($converted);
                    $this->_view->cat_desc = $converted[0]['token_replacement'];
                } */
                else if(!empty($lang_desc[0]['title']))
                {
                    $converted = $this->convertToken($token_name,$token_desc,"",addslashes(trim($lang_desc[0]['title'])));
                    /* echo "<PRE>";
                    print_r($converted); */
                    $this->_view->cat_desc = $converted[0]['token_replacement'];
                }
                /* Short Desc */
                $sn = $ebooker_obj->getStencilNumber($request['category']);
                $sn = count($sn)+1;
                $this->_view->short_desc = $this->adminLogin->loginName." - ".$this->getCustomName("EP_LANGUAGES",$language_selected)." - ".$category[0]['theme_name']." - Stencil ".$sn;
                /* Getting random stencils */
                $tokens_selected_comma = implode(",",$tokens_selected);
                $missions = "";
                if($request['missions'])
                {
                    $missions = implode(",",$request['missions']);
                }
                // *** added on 06-01-2016 *** //$request['category'];
                //check weather the category is weather if weather then load weather conditions and updated code else let the follow be default(old)//
                $categoryName = $ebooker_obj->getcategoryName($request['category']);
                if( $categoryName === 'Weather' || $categoryName === 'weather'){
                    $stencils = $ebooker_obj->getValidStencilsV2(array('token_ids' => $tokens_selected_comma, 'user_id' => $this->adminLogin->userId, "language" => $language_selected, 'missions' => $missions));
                }
                else {//folloe old codes//
                    $stencils = $ebooker_obj->getValidStencils(array('token_ids' => $tokens_selected_comma, 'user_id' => $this->adminLogin->userId, "language" => $language_selected, 'missions' => $missions));
                }
                $req_stencils = $stencils_used = array();
                if($stencils)
                {
                    foreach($tokens_selected as $token)
                    {
                        $req_stencil = $this->getReqStencils($token,$stencils,$stencils_used);
						//echo $request['language'];exit;
                        if($req_stencil!=-1)
                        {
                            if( $categoryName === 'Weather' || $categoryName === 'weather'){
                                $converted = $this->convertTokenV2($language_selected,$token_name, $token_code, "", addslashes($stencils[$req_stencil]['stencil_content']));
                            }
                            else {
                                $converted = $this->convertToken($token_name, $token_code, "", addslashes($stencils[$req_stencil]['stencil_content']));
                            }
                            $stencils[$req_stencil]['token_replace'] = $converted[0]['token_replacement'];
                            $explde_name = explode("##",$stencils[$req_stencil]['token_name']);
                            foreach($explde_name as $key => $tn)
                                $explde_name[$key] = $tn."_CONDITION";
                            $stencils[$req_stencil]['token_condition'] = implode("##",$explde_name);
                            $req_stencils[$token] = $stencils[$req_stencil];
                            $req_stencils[$token]['found'] = true;
                            $stencils_used[] = $stencils[$req_stencil]['id'];
                            /* *** added on 17.12.2015 *** */
                            // code to replace the hidden tokens if it is ebooker wheater stencils //
                            /*"\"<"+htype+">${if("+hidden_value+"){"+token_replaced_content+"'}else{''}}</"+htype+">\"";*/
                            $hidden_value = $ebooker_obj->getHiddenVAlue($req_stencils[$token]['alias_id']);
                            $req_stencils[$token]['final_stencil'] = (!is_null($hidden_value)) ? 'if('.$hidden_value.'){'.$stencils[$req_stencil]['token_replace'].'}' : $stencils[$req_stencil]['token_replace'];
                        }
                        else
                        {
                            $req_stencils[$token]['found'] = false;
                            $req_stencils[$token]['token'] = $tokens_notfound[$token];
                        }
                        //unset($stencils[$req_stencil]);
                        $_SESSION['language'] = $request['language']; //saving for later maniipulation use//
                        $_SESSION['req_stencils'] = $req_stencils; //saving for later maniipulation use//
                    }
                }
                //echo "<pre>";print_r($req_stencils);exit;
                $this->_view->stencils = $req_stencils;
            }
        }
        $this->render('stencils_follow_up_page_v2');
    }
    /* *** added on 17.12.2015 *** */
    //function called through ajax to generate stencils NOTE: only execute if the stencils is related to "Weather" //
    function stencilsAjaxAction(){
        $intro_array=$summer_array=$winter_array=$rain_array=array();
        $ebooker_obj = new Ep_Ebookers_Stencils();
        $stencils_array = $_SESSION['req_stencils']; //echo "<pre>";print_r($_SESSION['req_stencils']);exit;
        $language = $_SESSION['language'];
        //manually sorting in order as instructed 1.intro 2.summer 3.winter 4.rainy.//
        foreach($stencils_array as $level1){
            if(isset($level1['theme_id']) && $level1['theme_id'] != ""){
                if($level1['category_name'] == "Intro" || $level1['category_name'] == "intro" ){
                    $intro_array[$level1['token_id']] = $level1;
                }
                elseif($level1['category_name'] == "Summer" || $level1['category_name'] == "summer" ){
                    $summer_array[$level1['token_id']] = $level1;
                }
                elseif($level1['category_name'] == "Winter" || $level1['category_name'] == "winter" ){
                    $winter_array[$level1['token_id']] = $level1;
                }
                elseif($level1['category_name'] == "Rain" || $level1['category_name'] == "rain" ){
                    $rain_array[$level1['token_id']] = $level1;
                }
            }
        }
        //end of manually sorting in order as instructed 1.intro 2.summer 3.winter 4.rainy.//
        $stencils_array = array_merge($intro_array,$summer_array,$winter_array,$rain_array);// merge the sorted array in order intro,summer,winter then rain
        $temp = "";//to save category_name to manupulate and display proper output
        $temp2 = "";//to store category_type to manuplulate result
        $result = "";
        //forming stencils codes//
        foreach($stencils_array as $level1){
            if(isset($level1['theme_id']) && $level1['theme_id'] != ""){
                if($temp === ""){
                    $ThemeCondition = $ebooker_obj->getThemeCondition($level1['theme_id'],$language);
                    if(!is_null($ThemeCondition)) {
                        $result .= '"<p><b>' . $ThemeCondition[0]['title_condition'] . "</b></p>\"+";
                    }
                }
                if($level1['category_name'] !== $temp){
                    if($temp !== ""){
                        $result .='{\'\'}}"+';
                    }
                    $categoryCondition = $ebooker_obj->getCategoryCondition($level1['category_id'],$language);
                    if(!is_null($categoryCondition)) {
                        $result .= "\n".'"<p><br>' . $categoryCondition[0]['CT_title_condition'] . '</p>"+';
                    }
                    $result .= "\n".'"${' . $level1['final_stencil'] . 'else ';

                }
                elseif($level1['category_type'] !== $temp2){
                    if($temp2 !== ""){
                        $result .='{\'\'}}"+';
                    }
                    $result .= "\n".'"${' . $level1['final_stencil'] . 'else ';
                }
                else{
                    $result .= $level1['final_stencil']."else ";
                }
                $temp = $level1['category_name'];
                $temp2 = $level1['category_type'];
            }
        }
        $result .="{''}}</p>\"";
        // end of forming stencils codes//
        echo utf8_encode($result);//utf encode since there are some specail character passing over ajax.
        exit;
    }
    /* Loop through the stencils and get the stencil which are not used */
	function getReqStencils($token,$stencils,$stencils_used)
	{
		$i = 0;
		foreach($stencils as $stencil)
		{
			if($token==$stencil['token_id'] && !in_array($stencil['id'],$stencils_used))
			{
				return $i;
			}
			$i++;
		}
		return -1;
	}
    /* To convert Token with token code */
    function convertToken($token_name,$token_code,$token_hidden,$texts,$hidden_token="",$hidden_token_name="")
    {
        $converted = $token_replacements = $hidden_token_replacements = array();
        $i = 0;
        $converted[$i]['text'] = ($texts);
        $token_replacement = urldecode(preg_replace($token_name,$token_code,urlencode(str_replace(array(",",".","!"),array(" , "," . "," ! "),$texts))));
        $token_replacement = str_replace(array(" , "," . "," ! "),array(",",".","!"),$token_replacement);
        $converted[$i]['token_replacement'] = $token_replacement;
        $hidden_token_replacements[] = $token_replacements[] = $token_replacement;
        $converted[$i++]['hidden_replacement'] = $token_replacement;
        $converted['token_replacements'] = implode('###$$$###',$token_replacements);
        $converted['hidden_replacement'] = implode('###$$$###',$hidden_token_replacements);
        return $converted;
    }
    /*** added on 06.01.2016 ***/
    /* To convert Token with token code */
    //function to convert stencils to a proper code//
    function convertTokenV2($language,$token_name,$token_code,$token_hidden,$texts,$hidden_token="",$hidden_token_name="")
    {
        
		$converted = $token_replacements = $hidden_token_replacements = array();
        $i = 0;
        $converted[$i]['text'] = ($texts);
        $token_replacement = urldecode(preg_replace($token_name,$token_code,urlencode(str_replace(array(",",".","!"),array(" , "," . "," ! "),$texts))));
        $token_replacement = str_replace(array(" , "," . "," ! "),array(",",".","!"),"'".$token_replacement."'");
        // add  °C/mm if stencils contain Tempc and amountmm respectively // TempC
        $token_replacement =  preg_replace("%TempC\,\smax\:1\)\s\+\s\'%","TempC, max:1) + ' &deg;C",$token_replacement);//add  °C
        $token_replacement =  preg_replace("%AmountMm\,\smax\:1\)\s\+\s\'%","AmountMm, max:1) + ' mm",$token_replacement);//add  mm
        
		//$token_replacement =  preg_replace("%longDaylightHours\,\smax\:1\)\s*\+\s\'%","longDaylightHours, max:1) + ' hours",$token_replacement);//add  hours longDaylightHours
		if($language=="uk")
		{
		$token_replacement =  preg_replace("%longDaylightHours\,\smax\:1\)\s*\+\s\'%","longDaylightHours, max:1) + ' hours",$token_replacement);//add  hours longDaylightHours
		}
		else if($language=="fr")
		{
		$token_replacement =  preg_replace("%longDaylightHours\,\smax\:1\)\s*\+\s\'%","longDaylightHours, max:1) + ' heures",$token_replacement);//add  hours longDaylightHours
		}
		else if($language=="de")
		{
		$token_replacement =  preg_replace("%longDaylightHours\,\smax\:1\)\s*\+\s\'%","longDaylightHours, max:1) + ' stunden",$token_replacement);//add  hours longDaylightHours
		}
		else if($language=="fl")
		{
		$token_replacement =  preg_replace("%longDaylightHours\,\smax\:1\)\s*\+\s\'%","longDaylightHours, max:1) + ' tuntia",$token_replacement);//add  hours longDaylightHours
		}
		else if($language=="su")
		{
		$token_replacement =  preg_replace("%longDaylightHours\,\smax\:1\)\s*\+\s\'%","longDaylightHours, max:1) + ' timmar",$token_replacement);//add  hours longDaylightHours
		}


		
		
        $token_replacement =  preg_replace("%\+\s\'\'$%","",preg_replace("%^\'\'\s\+%","",$token_replacement));//to remove '+ at begining and  +' at end if any
        $token_replacement = html_entity_decode($token_replacement );
        $converted[$i]['token_replacement'] = $token_replacement;
        $hidden_token_replacements[] = $token_replacements[] = $token_replacement;
        $converted[$i++]['hidden_replacement'] = $token_replacement;
        $converted['token_replacements'] = implode('###$$$###',$token_replacements);
        $converted['hidden_replacement'] = implode('###$$$###',$hidden_token_replacements);
        return $converted;
    }
	// To lock stencils
	function lockstencilsAction()
	{
		if($this->_request->isPost() && $_SERVER['HTTP_X_REQUESTED_WITH']=="XMLHttpRequest")
		{
			$ebooker_obj =  new Ep_Ebookers_Stencils();
			$request = $this->_request->getParams();
			$stencils_id = $request['sids'];
			$update = array("locked"=>'yes','locked_by'=>$this->adminLogin->userId,"locked_at"=>date("Y-m-d H:i:s"));
			$ebooker_obj->updateValidStencils($update,$stencils_id);
		}
	}
	// To validate stencils
	function validateStencilsAction()
	{
		if($this->_request->isPost() && $_SERVER['HTTP_X_REQUESTED_WITH']=="XMLHttpRequest")
		{
			$ebooker_obj =  new Ep_Ebookers_Stencils();
			$request = $this->_request->getParams();
			$validstencils = implode(",",$request['validstencil']);
			$validstencil = $request['validstencil'];
			$validtokens = $request['validtokens'];
			$hiddenids = $request['hiddenids'];
			$type = $request['titletype'];
			$final_stencils = $request['final_stencils'];
			$userid = $this->adminLogin->userId;
			$ebooker_obj->updateValidStencilsInc($validstencils,$this->adminLogin->userId);
			$used_at = date("Y-m-d H:i:s");
			for($i=0;$i<count($request['validstencil']);$i++)
			{
				$data = array();
				$data['valid_stencil_id'] = $validstencil[$i];
				$data['token_id'] = $validtokens[$i];
				$data['used_by'] = $userid;
				$data['used_at'] = $used_at;
				$data['hidden_id'] = $hiddenids[$i];
				$data['type'] = $type[$i];
				$data['final_stencil'] = utf8_decode($final_stencils[$i]);
				$ebooker_obj->insertUsedstencils($data);
			}
		}	
	}
	/* To load Missions based on Tokens */
	function loadStencilMissionsAction()
	{
		if($_SERVER['HTTP_X_REQUESTED_WITH']=='XMLHttpRequest' && $this->_request->isPost())
		{
			$request = $this->_request->getParams();
			$token_ids = (array)($request['token_ids']);
			$cat_variations = (array)($request['cat_variation']);
			$language=$request['language'];
			if(count($token_ids))
			{
				$stencil_obj = new Ep_Ebookers_Stencils();
				$search = array();
				$token_ids = array_filter($token_ids);
				$search['token_ids'] = $token_ids;
				$cat_variations = array_filter($cat_variations);
				$search['cat_variations'] = implode(",",$cat_variations);
				$search['language'] = $language;
				$missions = $stencil_obj->getMissions($search);
				$json_missions = array();
				foreach($missions as $row)
				{
					if($row['product']=='translation')
					{
						$title = $this->product_array[$row['product']]." ".$this->producttype_array[$row['product_type']]." ".$this->getCustomName("EP_LANGUAGES",$row['language_source'])." au ".$this->getCustomName("EP_LANGUAGES",$row['language_dest']);
					}
					else
					{
						$title = $this->product_array[$row['product']]." ".$this->producttype_array[$row['product_type']]." in ".$this->getCustomName("EP_LANGUAGES",$row['language_source']);
					}
					
					$json_missions[$row['contract_mission_id']] = utf8_encode($title);
				}
				echo json_encode($json_missions);
			}
		}
	}
    /* *** added on 16.03.2016 *** */
    //function to create a xlsx file which contains all validated article content related to particular contract mission ID//
    function donwloadBnpXlsxAction(){
        $request = $this->_request->getParams();
        $cmid = $request['cmid'];
        $bnp_obj = new Ep_Bnp_Bnp();
        $results = $bnp_obj->fecthBnpParibasXlsx($cmid);
        if($results !== false) {
            //fetch city names text for later use
            $citys = (array) $bnp_obj->getCity();
            $scitys = array();
            foreach($citys as $city)
            {
                $scitys[$city['city_id']] = $city['city_name'];
            }
            //fetch sample text for later use
            $sample_texts = (array) $bnp_obj->getSampleTextIds();
            $ssample_texts = array();
            foreach($sample_texts as $sample_text)
            {
                $ssample_texts[$sample_text['sample_id']] = $sample_text['title'];
            }
            //html table later to be converted to xlsx//
            $htmltable = '
                    <table border="1">
			            <tr>
			                <th>Delivery Id</th>
			                <th>Article Id</th>
			                <th>Localit&eacute;</th>
			                <th>Bien (Bureaux/Entrepot/commerce)</th>
			                <th>Transaction (Location/Vente)	</th>
			                <th>Textes</th>
                        </tr>';
            foreach ($results as $result) {
                $bnp_city_id = explode("|",$result['bnp_city_id']);
                $bnp_sampletext_id = explode("|",$result['bnp_sampletext_id']);
                $bnp_no_of_articles= explode('|',$result['bnp_no_of_articles']);
                $bnp_text=explode("###$$$###",($result['article_doc_content']));
                $count_no_of_art = count($bnp_no_of_articles);
                $s=0;
                $int=0;
                $end=0;
                for($a=0;$a<$count_no_of_art;$a++){
                    $end+=$bnp_no_of_articles[$a];
                    for ($s=$int; $s < $end; $s++) {
                        //$bnpDetails[$a][$s]=$bnp_text[$s];
                        $value_2 = "";
                        $value_3 = "";
                        $str = $ssample_texts[$bnp_sampletext_id[$a]];
                        //search for 2nd column
                        if (preg_match('/bureau/', $str))
                            $value_2 = "Bureau";
                        elseif (preg_match('/Entrepot/', $str))
                            $value_2 = "Entrepot";
                        elseif (preg_match('/commerce/', $str))
                            $value_2 = "Commerce";
                        //search for 3rd columb
                        if (preg_match('/Location/', $str))
                            $value_3 = "Location";
                        elseif (preg_match('/Vente/', $str))
                            $value_3 = "Vente";


                        $htmltable .= "<tr>
                            <td>" . $result['delivery_id'] . "</td>
                            <td>" . $result['article_id'] . "</td>
                            <td>" . $scitys[$bnp_city_id[$a]] . "</td>
                            <td>" . $value_2 . "</td>
                            <td>" . $value_3 . "</td>
                            <td>" . str_replace(array('<', '>'), array("&lt;", '&gt;'),$bnp_text[$s]) . "</td>
                            </tr>";
                    }
                    $int+=$bnp_no_of_articles[$a];
                }

            }
            $htmltable .= "</table>";
            //end of html table later to be converted to xlsx//
            //codes to create a xslx file//
            $fullPath = $_SERVER['DOCUMENT_ROOT'] . "/BO/assets/donwloadBnpXlsx/BNP_Paribas_." . time() . ".xlsx";
            $this->convertHtmltableToXlsx($htmltable, $fullPath);
            // end of codes to create a xslx file//
            $this->_redirect("/BO/download-files.php?function=donwloadBnpXlsx&fullPath=$fullPath");
        }
        else{
            exit;
        }
    }
    function convertHtmltableToXlsx($htmltable,$fname,$extract=FALSE)
    {
        require_once APP_PATH_ROOT.'nlibrary/tools/PHPExcel.php';

        $htmltable = strip_tags($htmltable, "<table><tr><th><thead><tbody><tfoot><td><br><br /><b><span>");
        $htmltable = str_replace("<br />", "\n", $htmltable);
        $htmltable = str_replace("<br/>", "\n", $htmltable);
        $htmltable = str_replace("<br>", "\n", $htmltable);
        $htmltable = str_replace("&nbsp;", " ", $htmltable);
        $htmltable = str_replace("\n\n", "\n", $htmltable);

        $dom = new domDocument;
        $dom->loadHTML($htmltable);
        if(!$dom) {
            echo "<br />Invalid HTML DOM, nothing to Export.";
            exit;
        }
        $dom->preserveWhiteSpace = false;
        $tables = $dom->getElementsByTagName('table');
        if(!is_object($tables)) {
            echo "<br />Invalid HTML Table DOM, nothing to Export.";
            exit;
        }

        $tbcnt = $tables->length - 1;


        $username = "EditPlace";
        $usermail = "user@edit-place.com";
        $usercompany = "Edit Place";
        $debug = false;

        $objPHPExcel = new PHPExcel();
        $objPHPExcel->getDefaultStyle()->getFont()->setName('Verdana');
        $objPHPExcel->getDefaultStyle()->getFont()->setSize(10);
        $tm = date("YmdHis");
        $pos = strpos($usermail, "@");
        $user = substr($usermail, 0, $pos);
        $user = str_replace(".","",$user);


//        $explodefile=explode('/',$filename);
//        $count=count($explodefile)-2;
//        if($explodefile[$count]=='quotes_weekly_report'){
//            $fname =$filename;
//        }else{
//            $fname = $_SERVER['DOCUMENT_ROOT']."/BO/quotexls/$filename"; /* Filename */
//        }

//        if(!$extract)//image not required for extract XLS
//        {
//
//            $objDrawing = new PHPExcel_Worksheet_Drawing();
//            $objDrawing->setName('Logo');
//            $objDrawing->setDescription('Logo');
//            $objDrawing->setPath($_SERVER['DOCUMENT_ROOT'].'/BO/theme/gebo/img/edit-place.png');
//            $objDrawing->setCoordinates('A1');
//            $objDrawing->setWorksheet($objPHPExcel->getActiveSheet());
//
//            $objPHPExcel->getActiveSheet()->getRowDimension('1')->setRowHeight(40);
//        }

        $objPHPExcel->getProperties()->setCreator($username)
            ->setLastModifiedBy($username)
            ->setTitle("Sales Generation")
            ->setSubject("Sales Final Validation")
            ->setDescription("Sales Report")
            ->setKeywords("Sales")
            ->setCompany($usercompany)
            ->setCategory("Export");
        // Settings for A4 sheet
        $objPHPExcel->setActiveSheetIndex(0);
        $objPHPExcel->getActiveSheet()->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_PORTRAIT);
        $objPHPExcel->getActiveSheet()->getPageSetup()->setPaperSize(PHPExcel_Worksheet_PageSetup::PAPERSIZE_A4);
        $objPHPExcel->getActiveSheet()->getPageSetup()->setFitToPage(true);
        $objPHPExcel->getActiveSheet()->getPageSetup()->setFitToWidth(1);
        $objPHPExcel->getActiveSheet()->getPageSetup()->setFitToHeight(0);
        $xcol = '';
        $xrow = 1;
        $usedhdrows = 0;
        for($z=0;$z<=$tbcnt;$z++) {
            $headrows = array();
            $bodyrows = array();
            $r = 0;
            $h = 0;
            $maxcols = 0;
            $totrows = 0;
            $rows = $tables->item($z)->getElementsByTagName('tr');
            $totrows = $rows->length;

            foreach ($rows as $row) {
                $ths = $row->getElementsByTagName('th');
                if(is_object($ths)) {
                    if($ths->length > 0) {
                        $headrows[$h]['colcnt'] = $ths->length;
                        if($ths->length > $maxcols) {
                            $maxcols = $ths->length;
                        }
                        $nodes = $ths->length - 1;
                        for($x=0;$x<=$nodes;$x++) {
                            $thishdg = $ths->item($x)->nodeValue;
                            $headrows[$h]['th'][] = $thishdg;
                            $headrows[$h]['bold'][] = $this->findBoldText($this->innerHTML($ths->item($x)));
                            if($ths->item($x)->hasAttribute('style')) {
                                $style = $ths->item($x)->getAttribute('style');
                                $stylecolor = $this->findStyleColor($style);
                                if($stylecolor == '') {
                                    $headrows[$h]['color'][] = $this->findSpanColor($this->innerHTML($ths->item($x)));
                                }else{
                                    $headrows[$h]['color'][] = $stylecolor;
                                }
                                $fontsize = $this->findFontSize($style);
                                if($fontsize=='')
                                    $headrows[$h]['size'][] = 11;
                                else
                                    $headrows[$h]['size'][] = $fontsize;
                            }else{
                                $headrows[$h]['color'][] = $this->findSpanColor($this->innerHTML($ths->item($x)));
                                $headrows[$h]['size'][] = 11;
                            }
                            if($ths->item($x)->hasAttribute('colspan')) {
                                $headrows[$h]['colspan'][] = $ths->item($x)->getAttribute('colspan');
                            }else{
                                $headrows[$h]['colspan'][] = 1;
                            }
                            if($ths->item($x)->hasAttribute('align')) {
                                $headrows[$h]['align'][] = $ths->item($x)->getAttribute('align');
                            }else{
                                $headrows[$h]['align'][] = 'left';
                            }
                            if($ths->item($x)->hasAttribute('valign')) {
                                $headrows[$h]['valign'][] = $ths->item($x)->getAttribute('valign');
                            }else{
                                $headrows[$h]['valign'][] = 'top';
                            }
                            if($ths->item($x)->hasAttribute('bgcolor')) {
                                $headrows[$h]['bgcolor'][] = str_replace("#", "", $ths->item($x)->getAttribute('bgcolor'));
                            }else{
                                $headrows[$h]['bgcolor'][] = 'FFFFFF';
                            }
                        }
                        $h++;
                    }
                }
                /* Getting TD's */

                $tds = $row->getElementsByTagName('td');
                if(is_object($tds)) {
                    if($tds->length > 0) {
                        $bodyrows[$r]['colcnt'] = $tds->length;
                        if($tds->length > $maxcols) {
                            $maxcols = $tds->length;
                        }
                        $nodes = $tds->length - 1;
                        for($x=0;$x<=$nodes;$x++) {
                            $thistxt = $tds->item($x)->nodeValue;
                            $bodyrows[$r]['td'][] = $thistxt;
                            $bodyrows[$r]['bold'][] = $this->findBoldText($this->innerHTML($tds->item($x)));
                            if($tds->item($x)->hasAttribute('style')) {
                                $style = $tds->item($x)->getAttribute('style');
                                $stylecolor = $this->findStyleColor($style);
                                if($stylecolor == '') {
                                    $bodyrows[$r]['color'][] = $this->findSpanColor($this->innerHTML($tds->item($x)));
                                }else{
                                    $bodyrows[$r]['color'][] = $stylecolor;
                                }
                                $fontsize = $this->findFontSize($style);
                                if($fontsize=='')
                                    $bodyrows[$r]['size'][] = 10;
                                else
                                    $bodyrows[$r]['size'][] = $fontsize;
                            }else{
                                $bodyrows[$r]['color'][] = $this->findSpanColor($this->innerHTML($tds->item($x)));
                                $bodyrows[$r]['size'][] = 10;
                            }
                            if($tds->item($x)->hasAttribute('colspan')) {
                                $bodyrows[$r]['colspan'][] = $tds->item($x)->getAttribute('colspan');
                            }else{
                                $bodyrows[$r]['colspan'][] = 1;
                            }
                            if($tds->item($x)->hasAttribute('align')) {
                                $bodyrows[$r]['align'][] = $tds->item($x)->getAttribute('align');
                            }else{
                                $bodyrows[$r]['align'][] = 'left';
                            }
                            if($tds->item($x)->hasAttribute('valign')) {
                                $bodyrows[$r]['valign'][] = $tds->item($x)->getAttribute('valign');
                            }else{
                                $bodyrows[$r]['valign'][] = 'top';
                            }
                            if($tds->item($x)->hasAttribute('bgcolor')) {
                                $bodyrows[$r]['bgcolor'][] = str_replace("#", "", $tds->item($x)->getAttribute('bgcolor'));
                            }else{
                                $bodyrows[$r]['bgcolor'][] = 'FFFFFF';
                            }
                        }
                        $r++;
                    }
                }

                /* End of TD's */
            }

            $worksheet = $objPHPExcel->getActiveSheet();                // set worksheet we're working on
            $style_overlay = array('font' =>
                array('color' =>
                    array('rgb' => '000000'),'bold' => false,),
                'fill' 	=>
                    array('type' => PHPExcel_Style_Fill::FILL_SOLID, 'color' => array('rgb' => 'CCCCFF')),
                'alignment' =>
                    array('wrap' => true, 'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                        'vertical' => PHPExcel_Style_Alignment::VERTICAL_TOP),
                /*'borders' => array('top' => array('style' => PHPExcel_Style_Border::BORDER_THIN),
                                   'bottom' => array('style' => PHPExcel_Style_Border::BORDER_THIN),
                                   'left' => array('style' => PHPExcel_Style_Border::BORDER_THIN),
                                   'right' => array('style' => PHPExcel_Style_Border::BORDER_THIN)),*/
            );

            $heightvars = array(1=>'42', 2=>'42', 3=>'48', 4=>'52', 5=>'58', 6=>'64', 7=>'68', 8=>'76', 9=>'82');
            for($h=0;$h<count($headrows);$h++) {
                $th = $headrows[$h]['th'];
                $colspans = $headrows[$h]['colspan'];
                $aligns = $headrows[$h]['align'];
                $valigns = $headrows[$h]['valign'];
                $bgcolors = $headrows[$h]['bgcolor'];
                $colcnt = $headrows[$h]['colcnt'];
                $colors = $headrows[$h]['color'];
                $bolds = $headrows[$h]['bold'];
                $sizes = $headrows[$h]['size'];
                $usedhdrows++;
                $mergedcells = false;
                for($t=0;$t<count($th);$t++) {
                    if($xcol == '') {$xcol = 'A';}else{$xcol++;}
                    $thishdg = $th[$t];
                    $thisalign = $aligns[$t];
                    $thisvalign = $valigns[$t];
                    $thiscolspan = $colspans[$t];
                    $thiscolor = $colors[$t];
                    $thisbg = $bgcolors[$t];
                    $thisbold = $bolds[$t];
                    $thissize = $sizes[$t];
                    $strbold = ($thisbold==true) ? 'true' : 'false';
                    if($thisbg == 'FFFFFF') {
                        $style_overlay['fill']['type'] = PHPExcel_Style_Fill::FILL_NONE;
                    }else{
                        $style_overlay['fill']['type'] = PHPExcel_Style_Fill::FILL_SOLID;
                    }
                    $style_overlay['alignment']['vertical'] = $thisvalign;              // set styles for cell
                    $style_overlay['alignment']['horizontal'] = $thisalign;
                    $style_overlay['font']['color']['rgb'] = $thiscolor;
                    $style_overlay['font']['bold'] = $thisbold;
                    $style_overlay['font']['size'] = $thissize;
                    $style_overlay['fill']['color']['rgb'] = $thisbg;
                    $worksheet->setCellValue($xcol.$xrow, $thishdg);
                    $worksheet->getStyle($xcol.$xrow)->applyFromArray($style_overlay);

                    if($thiscolspan > 1) {                                                // spans more than 1 column
                        $mergedcells = true;
                        $lastxcol = $xcol;
                        for($j=1;$j<$thiscolspan;$j++) {
                            $lastxcol++;
                            $worksheet->setCellValue($lastxcol.$xrow, '');
                            $worksheet->getStyle($lastxcol.$xrow)->applyFromArray($style_overlay);
                        }
                        $cellRange = $xcol.$xrow.':'.$lastxcol.$xrow;

                        $worksheet->getStyle($cellRange)->applyFromArray($style_overlay);
                        $num_newlines = substr_count($thishdg, "\n");                       // count number of newline chars
                        if($num_newlines > 1) {
                            $rowheight = $heightvars[1];                                      // default to 35
                            if(array_key_exists($num_newlines, $heightvars)) {
                                $rowheight = $heightvars[$num_newlines];
                            }else{
                                $rowheight = 75;
                            }
                            $worksheet->getRowDimension($xrow)->setRowHeight($rowheight);     // adjust heading row height
                        }
                        $xcol = $lastxcol;
                    }
                }
                $xrow++;
                $xcol = '';
            }

            $usedhdrows++;

            for($b=0;$b<count($bodyrows);$b++) {
                $td = $bodyrows[$b]['td'];
                $colcnt = $bodyrows[$b]['colcnt'];
                $colspans = $bodyrows[$b]['colspan'];
                $aligns = $bodyrows[$b]['align'];
                $valigns = $bodyrows[$b]['valign'];
                $bgcolors = $bodyrows[$b]['bgcolor'];
                $colors = $bodyrows[$b]['color'];
                $bolds = $bodyrows[$b]['bold'];
                $sizes = $bodyrows[$b]['size'];
                for($t=0;$t<count($td);$t++) {
                    if($xcol == '') {$xcol = 'A';}else{$xcol++;}
                    $thistext = $td[$t];
                    $thisalign = $aligns[$t];
                    $thisvalign = $valigns[$t];
                    $thiscolspan = $colspans[$t];
                    $thiscolor = $colors[$t];
                    $thisbg = $bgcolors[$t];
                    $thisbold = $bolds[$t];
                    $thissize = $sizes[$t];
                    $strbold = ($thisbold==true) ? 'true' : 'false';
                    if($thisbg == 'FFFFFF') {
                        $style_overlay['fill']['type'] = PHPExcel_Style_Fill::FILL_NONE;
                    }else{
                        $style_overlay['fill']['type'] = PHPExcel_Style_Fill::FILL_SOLID;
                    }
                    $style_overlay['alignment']['vertical'] = $thisvalign;              // set styles for cell
                    $style_overlay['alignment']['horizontal'] = $thisalign;
                    $style_overlay['font']['color']['rgb'] = $thiscolor;
                    $style_overlay['font']['bold'] = $thisbold;
                    $style_overlay['font']['size'] = $thissize;
                    $style_overlay['fill']['color']['rgb'] = $thisbg;
                    if($thiscolspan == 1) {
                        $worksheet->getColumnDimension($xcol)->setWidth(20);
                    }
                    else
                    {
                        $worksheet->getColumnDimension($xcol)->setWidth($thiscolspan*5);
                    }
                    $worksheet->setCellValue($xcol.$xrow, $thistext);

                    $worksheet->getStyle($xcol.$xrow)->applyFromArray($style_overlay);
                    if($thiscolspan > 1) {                                                // spans more than 1 column
                        $lastxcol = $xcol;
                        for($j=1;$j<$thiscolspan;$j++) {
                            $lastxcol++;
                        }
                        $cellRange = $xcol.$xrow.':'.$lastxcol.$xrow;
                        $worksheet->mergeCells($cellRange);
                        $worksheet->getStyle($cellRange)->applyFromArray($style_overlay);
                        $xcol = $lastxcol;
                    }
                }
                $xrow++;
                $xcol = '';
            }

            $azcol = 'A';
            for($x=1;$x==$maxcols;$x++) {
                $worksheet->getColumnDimension($azcol)->setAutoSize(true);
                $azcol++;
            }

        }
        // $objPHPExcel->setActiveSheetIndex(0);
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');

        $objWriter->save($fname);
    }


    function innerHTML($node)
    {
        $doc = $node->ownerDocument;
        $frag = $doc->createDocumentFragment();
        foreach ($node->childNodes as $child) {
            $frag->appendChild($child->cloneNode(TRUE));
        }
        return $doc->saveXML($frag);
    }

    function findSpanColor($node)
    {
        $pos = stripos($node, "color:");
        if ($pos === false) {
            return '000000';
        }
        $node = substr($node, $pos);
        $start = "#";
        $end = ";";
        $node = " ".$node;
        $ini = stripos($node,$start);
        if ($ini === false) return "000000";
        $ini += strlen($start);
        $len = stripos($node,$end,$ini) - $ini;
        return substr($node,$ini,$len);
    }

    function findStyleColor($style)
    {
        $pos = stripos($style, "color:");
        if ($pos === false) {
            return '';
        }
        $style = substr($style, $pos);
        $start = "#";
        $end = ";";
        $style = " ".$style;
        $ini = stripos($style,$start);
        if ($ini === false) return "";
        $ini += strlen($start);
        $len = stripos($style,$end,$ini) - $ini;
        return substr($style,$ini,$len);
    }

    function findFontSize($style)
    {
        $pos = stripos($style, "font-size:");
        if ($pos === false) {
            return '';
        }
        $style = substr($style, $pos);
        return substr($style,stripos($style,":")+1,strlen(stripos($style,"px")));
    }

    function findBoldText($node)
    {
        $pos = stripos($node, "<b>");
        if ($pos === false) {
            return false;
        }
        return true;
    }

}
?>
