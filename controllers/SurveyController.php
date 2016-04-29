<?php
/**
 * Survey Controller
 * @version 1.0
 */
 require_once('ContractmissionController.php');
 
class SurveyController extends ContractmissionController
{
	function init()
	{
		parent::init();
		$this->fo_base_path_spec = $this->_config->path->fo_root_path.'poll_spec/';
		$this->fo_base_path = $this->_config->path->fo_base_path;
	}
	
	/* Create Survey */
	function createSurveyAction()
	{
		$step1Params=$this->_request->getParams();
		$mission_id=$step1Params['contract_missionid'];

		$deliveryObj=new Ep_Quote_Delivery();		

		if($mission_id)
		{			
			$misssionQuoteDetails=$deliveryObj->getMissionQuoteDetails($mission_id);
			
			if($misssionQuoteDetails)
			{	

				foreach($misssionQuoteDetails as $pkey=>$deliveryMission)
				{
					//modifying poll/quote/mission details
					$misssionQuoteDetails[$pkey]['quote_category']=$this->getCustomName("EP_ARTICLE_CATEGORY",$deliveryMission['quote_category']);
					
					$misssionQuoteDetails[$pkey]['poll_date_timestamp']=strtotime($deliveryMission['poll_date']);
					$misssionQuoteDetails[$pkey]['product_name']=$this->product_array[$deliveryMission['product']];			
					$misssionQuoteDetails[$pkey]['language_source_name']=$this->getCustomName("EP_LANGUAGES",$deliveryMission['language_source']);
					$misssionQuoteDetails[$pkey]['product_type_name']=$this->producttype_array[$deliveryMission['product_type']];
					if($deliveryMission['language_dest'])
						$misssionQuoteDetails[$pkey]['language_dest_name']=$this->getCustomName("EP_LANGUAGES",$deliveryMission['language_dest']);
						
					//sales/quotemission/contract mission comments
					$misssionQuoteDetails[$pkey]['sales_comment_time']=time_ago($deliveryMission['quote_created']);					
					$misssionQuoteDetails[$pkey]['cm_comment_time']=time_ago($deliveryMission['assigned_at']);
					$misssionQuoteDetails[$pkey]['qm_comment_time']=time_ago($deliveryMission['quoteMissionCreated_at']);
					
					
					
					$client_obj=new Ep_Quote_Client();
					
					//Quote mission user details
					if($deliveryMission['quoteMissionComments'])
					{
						$mission_created_by=$deliveryMission['missionCreated_by'];
						$mission_user_details=$client_obj->getQuoteUserDetails($mission_created_by);
						if($mission_user_details!='NO')
							$misssionQuoteDetails[$pkey]['mission_created_name']=$mission_user_details[0]['first_name'].' '.$mission_user_details[0]['last_name'];
					}		
						
					//contract mission updated user
					if($deliveryMission['contractMissionComments'])
					{
						$cmission_updated_by=$deliveryMission['updated_by'];
						$cmission_user_details=$client_obj->getQuoteUserDetails($cmission_updated_by);
						if($cmission_user_details!='NO')
							$misssionQuoteDetails[$pkey]['cm_assigned_name']=$cmission_user_details[0]['first_name'].' '.$cmission_user_details[0]['last_name'];
					}		
					
					//sales owner details
					$quote_by=$deliveryMission['quote_by'];			
					
					$bo_user_details=$client_obj->getQuoteUserDetails($quote_by);
					if($bo_user_details!='NO')
					{
						$misssionQuoteDetails[$pkey]['sales_owner']=$bo_user_details[0]['first_name'].' '.$bo_user_details[0]['last_name'];
						$misssionQuoteDetails[$pkey]['email']=$bo_user_details[0]['email'];
						$misssionQuoteDetails[$pkey]['sales_city']=$bo_user_details[0]['city'];
						$misssionQuoteDetails[$pkey]['sales_phone_number']=$bo_user_details[0]['phone_number'];
					}	
					
					
					//quote/mission files
					$quotefiles=$this->getSeoTechFiles($deliveryMission['quote_identifier']);
					$this->_view->quotefiles = $quotefiles;
				}
				
					$poll_config_obj = new Ep_Quote_Survey(); 
					$search = array();
					$search['type'] = "'price','bulk_price','timing'"; 
					$pquestionlist=$poll_config_obj->getPollquestions(NULL,$search);
						
					$this->_view->question_list = "";
					if(!empty($pquestionlist)):
						/* for($p=0;$p<count($pquestionlist);$p++)
						{
							if($pquestionlist[$p]['type']=='radio' || $pquestionlist[$p]['type']=='checkbox')
							{
								if($pquestionlist[$p]['option'] != "")
									$pquestionlist[$p]['optionlist'] = str_replace("|"," ,",$pquestionlist[$p]['option']);
							}
						} */
						$this->_view->question_list = $pquestionlist;
					endif;
				
				$this->_view->misssionQuoteDetails=$misssionQuoteDetails;
				$this->_view->details='no';
				$automail=new Ep_Message_AutoEmails();
				/* $email_content =$automail->getAutoEmail(11);
				
				$this->_view->email_content = $email_content[0]['Message'];
				$subject = strip_tags($email_content[0]['Object']);
				$pollcategory = trim($misssionQuoteDetails[0]['quote_category']);
				eval("\$subject= \"$subject\";"); */
			
				$this->_view->email_subject = $subject;
				$email_content =$automail->getAutoEmail(16);
				$subject = strip_tags($email_content[0]['Object']);
				$category = trim($misssionQuoteDetails[0]['quote_category']);
				eval("\$subject= \"$subject\";");
				$cemail_content = $email_content[0]['Message'];
				$poll_link = '$poll_link';
				eval("\$cemail_content= \"$cemail_content\";");
				$this->_view->cemail_content = $cemail_content;
				$this->_view->cemail_subject = $subject;
				$this->render('create-survey');
			}
			else
			$this->_redirect('contractmission/contract-list?submenuId=ML13-SL4');
		}
		else
			$this->_redirect('contractmission/contract-list?submenuId=ML13-SL4');
	}

	function getCustomName($type,$name)
	{
		$categories_array = $this->_arrayDb->loadArrayv2($type, $this->_lang);
		return $categories_array[$name];
	}
	
	/* Insert Survey */
	function insertSurveyAction()
	{
		$request = $this->_request->getParams();
		if($request && $this->_request-> isPost())
		{
			
			$save = array();
			$survey_obj = new Ep_Quote_Survey();
			
			$filename = "";
			if($_FILES['uploadfile']['name'])
			{
				$realfilename=$_FILES['uploadfile']['name'];
				$ext=$this->findexts($realfilename);
				$uploaddir = $this->fo_base_path_spec;
				$fname=basename($realfilename,".".$ext)."_".uniqid().".".$ext;
				$file = $uploaddir . $fname;
				if (move_uploaded_file($_FILES['uploadfile']['tmp_name'], $file))
				{
					chmod($file,0777);
					$filename = $fname;
				}
			}
			$save['client'] = $request['client_id'];
			$save['title'] = ($request['title']);	
			$save['publish_time'] = date("Y-m-d H:i:s",strtotime($request['count_down_start']));
			$save['poll_date'] = date("Y-m-d H:i:s",strtotime($request['count_down_end']));
			$save['poll_anonymous'] = 0;
			$save['language'] = $request['language'];
			$save['type'] = $request['type'];
			$save['category'] = $request['category'];
			$save['signtype'] = 'words';
			$save['min_sign'] = '';
			$save['max_sign'] = '';
			$save['file_name'] = $filename; // To be uploaded
			$save['priority_hours'] = '24'; 
			$save['black_contrib'] = 'no'; 
			$save['contributors'] = 4; 
			$save['created_by'] = $this->_view->userId; 
			$save['send_mail'] = "no" ; 
			$save['poll_max'] = ''; 
			$save['valid_status'] = 'active' ; 
			$save['contrib_percentage'] = '100' ; 
			$save['contract_mission_id'] = $request['contract_mission_id'] ; 
			$save['comment'] = $request['editorial_chief_review'] ; 
			$save['email_content'] = $request['cemail_content'] ; 
			$save['client_email_from'] = $request['email_from'] ; 
			$save['writer_email_from'] = $request['cemail_from'] ; 
			$save['min_sign'] = $request['nb_words'] ; 
			$save['max_sign'] = $request['nb_words'] ; 

			$poll_id = $survey_obj->insertPoll($save);
			
			if($save['comment'])
			{
				$addcomments = array();
				$addcomments['user_id'] = $this->adminLogin->userId;
				$addcomments['type'] = 'poll';
				$addcomments['type_identifier'] = $poll_id;
				$addcomments['comments'] = $save['comment'];
				$survey_obj->insertAdcomment($addcomments);
			}
			
			$i=0;
			$question_title = $request['question_title'];
			foreach($question_title as $row):
				$pollquestion = array();
				$pollquestion['pollid'] = $poll_id;
				$pollquestion['title'] = stripslashes($row);
				$pollquestion['type'] = $request['questype_'.$i];
				if($pollquestion['type']=="timing")
				{
					$pollquestion['option']= $request['timingoption_'.$i];
					$pollquestion['maximum']= $request['maximum_'.$i];
				}
				elseif($pollquestion['type']=="price" || $pollquestion['type']=="range_price")
					$pollquestion['maximum']= $request['maximum_'.$i];

				if($pollquestion['type']=="range_price" || $pollquestion['type']=="timing")	
					$pollquestion['minimum']= $request['minimum_'.$i];
					
				$pollquestion['configureid']= $request['quesid_'.$i];
				$pollquestion['linkedid']= '';		
				$pollquestion['questionorder']= ++$i;
				
				$survey_obj = new Ep_Quote_Survey();
				$survey_obj->insertPollQuestions($pollquestion);
			endforeach;
			$this->_helper->FlashMessenger('Created Survey Successfully');
			
			$log_obj=new Ep_Quote_QuotesLog();					
			$actionmessage = $log_obj->getActionSentence(24);
			$client_obj = new Ep_Quote_Client();
			$bo_user_details=$client_obj->getQuoteUserDetails($this->adminLogin->userId);
			if($bo_user_details!='NO')
				$prod_user=$bo_user_details[0]['first_name'].' '.$bo_user_details[0]['last_name'];
			else
				$prod_user="";
			$survey_title = $save['title'];
			$end_date = date('d/m/Y',strtotime($save['poll_date']));
			$actionmessage=strip_tags($actionmessage);
			eval("\$actionmessage= \"$actionmessage\";");
		
			$log_array['user_id'] = $this->adminLogin->userId;
			$log_array['contract_id'] = $request['cid'];
			$log_array['mission_id'] = $request['mid'];
			$log_array['quote_id'] = $request['qid'];
			$log_array['mission_type'] = 'survey_prod';
			$log_array['user_type']=$this->adminLogin->type;
			$log_array['action']='survey_creation';
			$log_array['action_at']=date("Y-m-d H:i:s");
			$log_array['action_sentence']=$actionmessage;
			
			$log_obj->insertLogs($log_array);
			
			$parameters = array();
			/* Send Client Email */
			/*$parameters['Object'] = $request['email_subject'];
			$client_polllink='<a href="'.$this->fo_base_path.'/client/devispremium?id='.$poll_id.'">cliquant-ici</a>';
			$message = $request['email_content'];
			eval("\$message= \"$message\";");
			$parameters['Message'] = $message;
			$parameters['mail_from'] = $request['email_from'];
			if($parameters['mail_from'])
				$parameters['mail_from_pm'] = $parameters['mail_from'];
			else
				$parameters['mail_from_pm'] = NULL;
			$this->messageToEPMail($request['client_id'],11,$parameters); */
			//$this->messageToEPMail('140560467712470',11,$parameters);
			/* Send Email to contributers */
			$delivery_obj = new Ep_Quote_Delivery();
			$contributers = $delivery_obj->getContributorsList(array('language'=>$request['language']));
			$poll_link='<a href="'.$this->fo_base_path.'/contrib/aosearch">ici</a>';
			$message = $request['cemail_content'];
			eval("\$message= \"$message\";");
			$parameters['Object'] = $request['cemail_subject'];
			$parameters['Message'] = $message;
			$parameters['mail_from'] = $request['cemail_from'];
			//Added w.r.t sending email from PM a/c
			if($parameters['mail_from'])
				$parameters['mail_from_pm'] = $parameters['mail_from'];
			else
				$parameters['mail_from_pm'] = NULL;
			//$this->messageToEPMail('140560467712470',16,$parameters);
			foreach($contributers as $contrib => $value)
			{
				$this->messageToEPMail($contrib,16,$parameters);
			}
			//$this->_redirect('survey/edit-survey?survey_id='.$poll_id);
			$this->_redirect('followup/prod/?cmid='.$request['contract_mission_id'] .'&active=survey&submenuId=ML13-SL4');
		}
	}
	
	/* To send Mail */
	public function messageToEPMail($receiverId,$mailid,$parameters)
    {
        $automail=new Ep_Message_AutoEmails();
       	
		/**Inserting into EP mail Box**/
        $automail->sendMailEpMailBox($receiverId,$parameters['Object'],$parameters['Message'],$parameters['mail_from_pm']);
    }
	
	/* to find exts while uploading */
	public function findexts($filename="")
	{
		$filename = strtolower($filename) ;
		$exts = split("[/\\.]", $filename) ;
		$n = count($exts)-1;
		$exts = $exts[$n];
		return $exts;
	}

	/* Edit Survey */
	function editSurveyAction()
	{
		$request = $this->_request->getParams();
		if($request['survey_id'])
		{
			$survey_obj = new Ep_Quote_Survey();
				
			$search = array('survey_id'=>$request['survey_id']);
			$poll_details = $survey_obj->getPoll($search);
		
			$contract_obj = new Ep_Quote_Quotecontract();
			$search = array('contractmissionid'=>$poll_details[0]['contract_mission_id']);
			$contractmission_details = $contract_obj->getContractDetails($search);
			if($contractmission_details)
			{
				$cmdetails = $contractmission_details[0];
				
				if($cmdetails['type']=="prod" && $poll_details)
				{
					$prodMissionObj=new Ep_Quote_ProdMissions();
					$search = array();
					$search['quote_mission_id']=$cmdetails['type_id'];
					$prodMissionDetails=$prodMissionObj->getProdMissionDetails($search);
					$missionDetails=$contract_obj->getSeoMission($cmdetails['type_id']);
					if($prodMissionDetails)
					{
						foreach($prodMissionDetails as $key=>$details)
						{
							$client_obj=new Ep_Quote_Client();
							$bo_user_details=$client_obj->getQuoteUserDetails($details['created_by']);
							$prodMissionDetails[$key]['prod_user_name']=$bo_user_details[0]['first_name'].' '.$bo_user_details[0]['last_name'];

							$prodMissionDetails[$key]['comment_time']=time_ago($details['created_at']);
							
						}
					}
					
					$missionDetails[0]['product_name']=$this->product_array[$missionDetails[0]['product']];			
					$missionDetails[0]['language_source_name']=$this->getCustomName("EP_LANGUAGES",$missionDetails[0]['language_source']);
					$missionDetails[0]['product_type_name']=$this->producttype_array[$missionDetails[0]['product_type']];
					if($missionDetails[0]['language_dest'])
						$missionDetails[0]['language_dest_name']=$this->getCustomName("EP_LANGUAGES",$missionDetails[0]['language_dest']);
					$missionDetails[$index]['mission_title']= 'Mission '.$missionDetails[$index]['product_name'];
					$missionDetails[0]['comment_time']=time_ago($missionDetails[$index]['created_at']);
					$missionDetails[0]['prod_missions'] = $prodMissionDetails;
					
					$this->_view->prod_mission_details = $missionDetails[0];
					
					$user_obj=new Ep_User_User();
					
					$user_info = $user_obj->getAllUsersDetails($cmdetails['cmupdated_by']);
					 
					if($user_info)
					$cmdetails['created_name'] =  $user_info[0]['first_name']." ".$user_info[0]['last_name'];
					else
					$cmdetails['created_name'] =  "";
					
					$cmdetails['created_time'] = time_ago($cmdetails['cmupdated_at']);
					
					$this->_view->contractMissionDetails = $cmdetails;
					
					$quote_obj = new Ep_Quote_Quotes();
					$quote_details = $quote_obj->getQuoteDetails($cmdetails['quoteid']);
					
					$user_info = $user_obj->getAllUsersDetails($quote_details[0]['created_by']);
					
					if($user_info)
					$quote_details[0]['created_name'] = $user_info[0]['first_name']." ".$user_info[0]['last_name'];
					else
					$quote_details[0]['created_name'] = "";
					$quote_details[0]['created_time'] = time_ago($quote_details[0]['created_at']);
					
					$user_info = $user_obj->getAllUsersDetails($quote_details[0]['quote_by']);
					
					if($user_info)
					{
						$quote_details[0]['sales_owner'] = $user_info[0]['first_name']." ".$user_info[0]['last_name'];
						$quote_details[0]['sales_city'] = $user_info[0]['city'];
						$quote_details[0]['sales_phone_number'] = $user_info[0]['phone_number'];
					}
					else
					{
						$quote_details[0]['sales_owner'] = "";
						$quote_details[0]['sales_city'] = "";
						$quote_details[0]['sales_phone_number'] = "";
					}
					
					$quote_details[0]['category_name'] = $this->getCustomName("EP_ARTICLE_CATEGORY",$quote_details[0]['category']);
					$this->_view->quote_details = $quote_details[0];
					
					$this->_view->month_week = array('week'=>'Week','month'=>'Month');
					
					$this->_view->quotefiles = $this->getSeoTechFiles($cmdetails['quoteid']);
						
					/* Poll Questions */
					/* $poll_config_obj = new Ep_Quote_Survey(); 
					$search = array();
					$search['type'] = "'price','bulk_price','timing'"; 
					$pquestionlist=$poll_config_obj->getPollquestions(NULL,$search);
						
					$this->_view->question_list = "";
					if(!empty($pquestionlist)):
						/* for($p=0;$p<count($pquestionlist);$p++)
						{
							if($pquestionlist[$p]['type']=='radio' || $pquestionlist[$p]['type']=='checkbox')
							{
								if($pquestionlist[$p]['option'] != "")
									$pquestionlist[$p]['optionlist'] = str_replace("|"," ,",$pquestionlist[$p]['option']);
							}
						} */
					/*		$this->_view->question_list = $pquestionlist;
					endif; */
					$this->_view->poll_details = $poll_details;
					$this->_view->file = "";
					
					if(file_exists($this->fo_base_path_spec.$poll_details[0]['file_name']))
					{
						$this->_view->file = $poll_details[0]['file_name'];
						$pathinfo = pathinfo($this->_view->file);
						$this->_view->file_name = substr($pathinfo['filename'],0,strrpos($pathinfo['filename'],"_")).".".$pathinfo['extension'];
					}
					$this->render('edit-survey');
				}
				else
				$this->_redirect('contractmission/contract-list?submenuId=ML13-SL4');
			}
			else
			$this->_redirect('contractmission/contract-list?submenuId=ML13-SL4');
		}	
	}
	
	function updateSurveyAction()
	{
		$request = $this->_request->getParams();
		if($request && $this->_request-> isPost())
		{
			$save = array();
			$survey_obj = new Ep_Quote_Survey();
			
			$filename = "";
			if($_FILES['uploadfile']['name'])
			{
				$realfilename=$_FILES['uploadfile']['name'];
				$ext=$this->findexts($realfilename);
				$uploaddir = $this->fo_base_path_spec;
				$fname=basename($realfilename,".".$ext)."_".uniqid().".".$ext;
				$file = $uploaddir . $fname;
				if (move_uploaded_file($_FILES['uploadfile']['tmp_name'], $file))
				{
					chmod($file,0777);
					$filename = $fname;
					unlink($this->fo_base_path_spec.$request['filename']);
				}
			}
			$save['title'] = utf8_decode(stripslashes($request['title']));	
			$save['publish_time'] = date("Y-m-d H:i:s",strtotime($request['count_down_start']));
			$save['poll_date'] = date("Y-m-d H:i:s",strtotime($request['count_down_end']));
			if($filename)
			$save['file_name'] = $filename; // To be uploaded
			$save['comment'] = $request['editorial_chief_review'] ; 
			$save['email_content'] = $request['email_content'] ; 

			$poll_id = $survey_obj->insertPoll($save,$request['pollid']);
			
			$i=0;
			$question_title = $request['question_title'];
			foreach($question_title as $row):
				$pollquestion = array();
				$pollquestion['title'] = stripslashes($row);
				$pollquestion['type'] = $request['questype_'.$i];
				if($pollquestion['type']=="timing")
				{
					$pollquestion['option']= $request['timingoption_'.$i];
					$pollquestion['maximum']= $request['maximum_'.$i];
				}
				elseif($pollquestion['type']=="price" || $pollquestion['type']=="range_price")
					$pollquestion['maximum']= $request['maximum_'.$i];

				if($pollquestion['type']=="range_price" || $pollquestion['type']=="timing")	
					$pollquestion['minimum']= $request['minimum_'.$i];
				
				$survey_obj = new Ep_Quote_Survey();
				$survey_obj->insertPollQuestions($pollquestion,$request['quesid_'.$i++]);
			endforeach;
			$this->_helper->FlashMessenger('Updated Survey Successfully');
			//$this->_redirect('survey/edit-survey?survey_id='.$request['pollid']);
			$this->_redirect('contractmission/recruitment-survey-delivery-list?active=survey?submenuId=ML13-SL4');
		}
	}
	
	function downloadFileAction()
	{
		$request = $this->_request->getParams();
		$this->_redirect("/BO/download-survey.php?filename=".$request['filename']."&testart=".$request['testart']);
	}
	
	//survey Followup
	public function followupAction()
	{
		$followupParams= $this->_request->getParams();
		
		$survey_id=$followupParams['survey_id'];
		
		if($survey_id)
		{
			$surveyObj=new Ep_Quote_Survey();	
			
			$pollMisssionQuoteDetails=$surveyObj->getPollMissionQuoteDetails($survey_id);//get all poll/mission/quote/contract details w.r.t Poll id
			
			if($pollMisssionQuoteDetails)
			{
				
				foreach($pollMisssionQuoteDetails as $pkey=>$surveyMission)
				{
					//modifying poll/quote/mission details
					$pollMisssionQuoteDetails[$pkey]['quote_category']=$this->getCustomName("EP_ARTICLE_CATEGORY",$surveyMission['quote_category']);
					
					$pollMisssionQuoteDetails[$pkey]['poll_date_timestamp']=strtotime($surveyMission['poll_date']);
					$pollMisssionQuoteDetails[$pkey]['product_name']=$this->product_array[$surveyMission['product']];			
					$pollMisssionQuoteDetails[$pkey]['language_source_name']=$this->getCustomName("EP_LANGUAGES",$surveyMission['language_source']);
					$pollMisssionQuoteDetails[$pkey]['product_type_name']=$this->producttype_array[$surveyMission['product_type']];
					if($surveyMission['language_dest'])
						$pollMisssionQuoteDetails[$pkey]['language_dest_name']=$this->getCustomName("EP_LANGUAGES",$surveyMission['language_dest']);
						
					//sales/quotemission/contract mission comments
					$pollMisssionQuoteDetails[$pkey]['sales_comment_time']=time_ago($surveyMission['quote_created']);					
					$pollMisssionQuoteDetails[$pkey]['cm_comment_time']=time_ago($surveyMission['assigned_at']);
					$pollMisssionQuoteDetails[$pkey]['qm_comment_time']=time_ago($surveyMission['quoteMissionCreated_at']);
					
					
					
					$client_obj=new Ep_Quote_Client();
					
					//Quote mission user details
					if($surveyMission['quoteMissionComments'])
					{
						$mission_created_by=$surveyMission['missionCreated_by'];
						$mission_user_details=$client_obj->getQuoteUserDetails($mission_created_by);
						if($mission_user_details!='NO')
							$pollMisssionQuoteDetails[$pkey]['mission_created_name']=$mission_user_details[0]['first_name'].' '.$mission_user_details[0]['last_name'];
					}		
						
					//contract mission updated user
					if($surveyMission['contractMissionComments'])
					{
						$cmission_updated_by=$surveyMission['updated_by'];
						$cmission_user_details=$client_obj->getQuoteUserDetails($cmission_updated_by);
						if($cmission_user_details!='NO')
							$pollMisssionQuoteDetails[$pkey]['cm_assigned_name']=$cmission_user_details[0]['first_name'].' '.$cmission_user_details[0]['last_name'];
					}		
					
					//sales owner details
					$quote_by=$surveyMission['quote_by'];			
					
					$bo_user_details=$client_obj->getQuoteUserDetails($quote_by);
					if($bo_user_details!='NO')
					{
						$pollMisssionQuoteDetails[$pkey]['sales_owner']=$bo_user_details[0]['first_name'].' '.$bo_user_details[0]['last_name'];
						$pollMisssionQuoteDetails[$pkey]['email']=$bo_user_details[0]['email'];
						$pollMisssionQuoteDetails[$pkey]['sales_city']=$bo_user_details[0]['city'];
						$pollMisssionQuoteDetails[$pkey]['sales_phone_number']=$bo_user_details[0]['phone_number'];
					}	
					
					
					//quote/mission files
					$quotefiles=$this->getSeoTechFiles($surveyMission['quote_identifier']);
					$this->_view->quotefiles = $quotefiles;
					
					
					
					$surveyParticipants=$surveyObj->getSurveyPartcipants($surveyMission['id']); //getting all participants of a survey
					//echo "<pre>";print_r($surveyParticipants);	
					if($surveyParticipants)
					{
						$SMICvalue=$surveyObj->getSMICvalue($surveyMission['id']);
						foreach($surveyParticipants as $key=>$participate)
						{
							$surveyResponses=$surveyObj->getUserResponses($participate['poll_id'],$participate['user_id']);					
							
							if($surveyResponses)	
								$surveyParticipants[$key]['response_details']=$surveyResponses;
							
							//user profile image
							$surveyParticipants[$key]['image'] = $this->getProfilePic($participate['user_id']);
							
							if($participate['price_user']<$SMICvalue)
								$surveyParticipants[$key]['under_smic']='yes';
							else	
								$surveyParticipants[$key]['under_smic']='no';
							
							
						}
					}
					
					$pollMisssionQuoteDetails[$pkey]['surveyParticipants']=$surveyParticipants;		
					
					$pollMisssionQuoteDetails[$pkey]['avg_price']=$surveyObj->getAvgPriceSurvey($surveyMission['id']);
					$pollMisssionQuoteDetails[$pkey]['avg_bulk_price']=$surveyObj->getAvgBulkPriceSurvey($surveyMission['id']);
										
					
				}	
				$this->_view->pollMisssionQuoteDetails=$pollMisssionQuoteDetails;
				$this->_view->details='yes';
				$this->_view->cmid=$followupParams['cmid'];
				//echo "<pre>";print_r($pollMisssionQuoteDetails);exit;
				
				$this->render('survey-follow-up');
			}	
			
		}	
	}
	//close survey or save survey
	public function saveSurveyAction()
	{
		if($this->_request-> isPost() && $this->adminLogin->userId)           
        {
			$surveyParams= $this->_request->getParams();
			
			if($surveyParams['survey_id'])
			{
				$surveyObj = new Ep_Quote_Survey();
				$this->_helper->FlashMessenger('Updated Survey Successfully');
				if($surveyParams['close']):
					$save = array();
					$save['status'] = 'closed';
					$save['closed_at'] = date('Y-m-d H:i:s');
					$surveyObj->insertPoll($save,$surveyParams['survey_id']);
					$this->_helper->FlashMessenger('Closed Survey Successfully');
				endif;
				
				$update = array();
				$update['status'] = 'inactive';
				$surveyObj->updateSurveyPartcipation($update,$surveyParams['survey_id'],true);
				
				$update['status'] = 'active';
				foreach($surveyParams['status'] as $row)
				{					
					$surveyObj->updateSurveyPartcipation($update,$row);
				}
			}	
			//$this->_redirect('/survey/followup?survey_id='.$surveyParams['survey_id']);
			$this->_redirect('followup/prod/?cmid='.$surveyParams['cmid'] .'&submenuId=ML13-SL4');
		}
	}	
	
	function getProfilePic($user_id)
	{
		$filename = $this->_view->fo_path."/profiles/contrib/pictures/$user_id/".$user_id."_p.jpg";
		$file_headers = @get_headers($filename);
		if(stripos($file_headers[0],"404 Not Found") >0  || (stripos($file_headers[0], "302 Found") > 0 && stripos($file_headers[7],"404 Not Found") > 0)) 
		return "/images/editor-noimage_60x60.png";
		else
		return "/profiles/contrib/pictures/$user_id/".$user_id."_p.jpg";
	}
	
	/* Load Partcipation through Ajax */
	function loadparticipationAction()
	{
		if($this->_request->isPost() && $_SERVER['HTTP_X_REQUESTED_WITH']=='XMLHttpRequest')
		{
			$request = $this->_request->getParams();
			$survey_id =  $request['sid'];
			$checked_values = $request['checked_types'];
			$where = "";
			if($checked_values)
			{
			//	$where = " ( ";
				$explode = explode(',',$checked_values);
				if(in_array('sc',$explode))
				$where .= " OR us.profile_type='senior'";
				if(in_array('jc',$explode))
				$where .= " OR us.profile_type='junior'";
				if(in_array('jco',$explode))
				$where .= " OR us.profile_type='sub-junior'";
				//$where .= " )";
			}
			$surveyObj=new Ep_Quote_Survey();	
			$surveyParticipants = $surveyObj->getSurveyPartcipants($survey_id,$where);
			if($surveyParticipants)
			{
				$SMICvalue=$surveyObj->getSMICvalue($survey_id);
				foreach($surveyParticipants as $key=>$participate)
				{
					$surveyResponses=$surveyObj->getUserResponses($participate['poll_id'],$participate['user_id']);					
					
					if($surveyResponses)	
						$surveyParticipants[$key]['response_details']=$surveyResponses;
					
					//user profile image
					$surveyParticipants[$key]['image'] = $this->getProfilePic($participate['user_id']);
					
					if($participate['price_user']<$SMICvalue)
						$surveyParticipants[$key]['under_smic']='yes';
					else	
						$surveyParticipants[$key]['under_smic']='no';
					
				}
			}
			$this->_view->surveyParticipants = $surveyParticipants;		
			
			$this->_view->avg_price = $surveyObj->getAvgPriceSurvey($survey_id);
			$this->_view->avg_bulk_price = $surveyObj->getAvgBulkPriceSurvey($survey_id);
			$this->_view->currency = $request['currency'];
			$this->render('load-survey-participation');
		}
	}
	
}    
?>