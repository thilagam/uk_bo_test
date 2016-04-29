<?php
/**
 * Contract Mission Controller for creation, validation of contract and Assigning missions.
 * @version 1.0
 */
class ContractmissionController extends Ep_Controller_Action
{

	var $mail_from = "work@edit-place.com";
	
	function init()
	{
		parent::init();
		$this->_view->fo_path = $this->_config->path->fo_path;
		$this->url = $this->_config->path->bo_base_path;
		//$this->_view->ebookerid = $this->ebookerid = $this->_config->wp->ebookerid;
		$this->_view->ebookerid = $this->ebookerid = $this->configval['ebooker_id'];
		$this->quote_documents_path=APP_PATH_ROOT.$this->_config->path->quote_documents;
		$this->mission_documents_path=APP_PATH_ROOT.$this->_config->path->mission_documents;
		$this->adminLogin = Zend_Registry::get('adminLogin');
		$this->_view->user_type= $this->adminLogin->type ;
		$this->_view->userId = $this->adminLogin->userId;
		$this->_view->loginuserId=$this->adminLogin->userId;
		$this->contract_documents_path = APP_PATH_ROOT.$this->_config->path->contractDocuments;
		$this->recruitment_documents_path = APP_PATH_ROOT.$this->_config->path->recruitmentDocuments;
		$this->pre_recruitment_documents_path = APP_PATH_ROOT.$this->_config->path->recruitmentDocuments;
		$this->product_array=array(
    
    							"redaction"=>"Writing",
								"translation"=>"Translation",
								"autre"=>"Other",
								"proofreading"=>"Correction",
								"seo_audit"=>"SEO Audit",
								"smo_audit"=>"SMO Audit"
        						);
		$this->_view->producttype_array=$this->producttype_array=array(
    							"article_de_blog"=>"Blog article",
								"descriptif_produit"=>"Product description",
								"article_seo"=>"SEO article",
								"guide"=>"Guide",
								"news"=>"News",
								"autre"=>"Others");
		$this->_view->tempo_duration_array=$this->duration_array=array(
							"days"=>"Days",
							"week"=>"Week",
							"month"=>"Month",
							"year"=>"Year"
						);					
		$this->_view->duration_array=array(
							"days"=>"Days"
						);	
		$this->_view->volume_option_array=$this->volume_option_array=array(
							"every"=>"Every",
							"within"=>"Within"
						);	
		$this->_view->tempo_array=$this->tempo_array=array(
							"fix"=>"Fix",
							"max"=>"Max"						
							);
        						
		$this->recruitment = Zend_Registry::get('recruitment');
		$this->fo_testart_path = '/home/sites/site5/web/FO/recruitmentTestArticles/';
	}
	/* To create a contract from signed Quote */
	function createContractAction()
	{
		$requestparmas = $this->_request->getParams();
		if($requestparmas['quote_id'])
		{
			$quote_obj = new Ep_Quote_Quotes();
			$status = $quote_obj->getQuoteDetails($requestparmas['quote_id']);
			
			if($status[0]['sales_review']=="signed" || $status[0]['sales_review']=="closed")
			{
				$status[0]['category_name'] = $this->getCustomName("EP_ARTICLE_CATEGORY",$status[0]['category']);
				/* If prod extra days from Quote add it to the signed date to get expected date */
				if($status[0]['prod_extra_launch_days'])
				{
					$no_days = $status[0]['prod_extra_launch_days'];
					$status[0]['exp_end_date'] = date('d/m/Y',strtotime($status[0]['signed_at']."+ $no_days days"));
					$status[0]['exp_end_stddate'] = date('Y-m-d',strtotime($status[0]['signed_at']."+ $no_days days"));
				}
				
				//$this->_view->quote_res = $status[0];
				$client_obj = new Ep_Quote_Client();		
				$client_details = $client_obj->getClientDetails($status[0]['client_id']);
				
				if($client_details!="NO")
				{
					//$categories_array = $this->_arrayDb->loadArrayv2("EP_ARTICLE_CATEGORY", $this->_lang);
					$client_details[0]['category'] = $this->getCustomName("EP_ARTICLE_CATEGORY",$client_details[0]['category']);
					$this->_view->client_info = $client_details[0];
				}
				else
				$this->_view->client_info = "no";
				
				$searchParameters['quote_id']=$requestparmas['quote_id'];
				$searchParameters['misson_user_type_prod_seo']='sales OR seo';
				$searchParameters['product_type_seo']='NOT IN';
				$searchParameters['include_final']='yes';
				$quoteMission_obj=new Ep_Quote_QuoteMissions();
				$missionDetails=$quoteMission_obj->getMissionDetails($searchParameters);	
				/* Formatting mission Details */
				if($missionDetails)
				{
					$i = 0;
					for($i=0;$i<count($missionDetails);$i++)
					{
						$missionDetails[$i]['product_type_converted'] = $this->product_array[$missionDetails[$i]['product']];
						$missionDetails[$i]['tempo_length_option_convert'] = $this->duration_array[$missionDetails[$i]['tempo_length_option']];
						$missionDetails[$i]['language_source_converted'] = $this->getCustomName("EP_LANGUAGES",$missionDetails[$i]['language_source']);
						$missionDetails[$i]['product_type_name']=$this->producttype_array[$missionDetails[$i]['product_type']];
						if($missionDetails[$i]['language_dest'] && $missionDetails[$i]['product']=="translation")
						$missionDetails[$i]['language_dest_converted'] = $this->getCustomName("EP_LANGUAGES",$missionDetails[$i]['language_dest']);
						/* If package is team in Quote mission then add product of team fee and team packs to turnover */
						if($missionDetails[$i]['package']=='team')
						$missionDetails[$i]['turnover'] +=  $missionDetails[$i]['team_fee'] * $missionDetails[$i]['team_packs'];
					}
				}	
				
				//$quote_obj = new Ep_Quote_Quotes();
				//$quoteDetails = $quote_obj->getQuoteDetails($requestparmas['quote_id']);
				$this->_view->missiondetails = $missionDetails;
				$this->_view->quotedetails = $status[0];
				$this->_view->csarray = array(''=>'Select','Edit Place'=>'Edit Place','Client'=>'Client');
				$this->_view->typeofpayment = array(''=>'Select','factor'=>'Factor','daily'=>'Dailly','direct'=>'Direct','others'=>'Others');
				$this->render('create-quote-contract');
			}
			else
			$this->_redirect("/quote/sales-quotes-list?submenuId=ML13-SL2");
		}
		else
			$this->_redirect("/quote/sales-quotes-list?submenuId=ML13-SL2");
	}	
	/* General function to get the name from XML file based on filetype */
	function getCustomName($type,$name)
	{
		$categories_array = $this->_arrayDb->loadArrayv2($type, $this->_lang);
		return $categories_array[$name];
	}
	/* Get Split values based on Launch date of contract and final mission length from Quote and Quotemissions */
	function getSplitMonthAction()
	{
		if($this->_request->isPost() && $_SERVER['HTTP_X_REQUESTED_WITH']=='XMLHttpRequest')
		{
			$request = $this->_request->getParams();
			
			$quote_id = $request['quote_id'];
			$explode = explode("/",$request['expected_launch_date']);
			$prod_extra_days = (int)$request['pdays'];
			if($request['peinfo']=='no')
			 $prod_extra_days = 0;
			/* Getting launch or start date from Quote and assigning it to date1 */
			$date1 = $orgdate1 = $explode[2]."-".$explode[1]."-".$explode[0];
			/* Adding prod extra days if any from Quote */
			if($request['salesdtimeo']=='days')
				$days = $request['salesdtime']+$prod_extra_days;
			else
			{
				//$days = 1; 
				$days = ceil($request['salesdtime']/24)+$prod_extra_days;
			}
			/* Adding final_mission_length and option from Quote and adding it to launch days to get end date and assinging it to date2 */
			$date2 = date('Y-m-d',strtotime($date1."+ $days days")); 
			//if($request['expected_end_date'])
			//$date2 = $request['expected_end_date'];
			$this->_view->explaunchdate = date("d/m/Y",strtotime($date2));
			$turnover = $request['turnover'];
			$scurrency = $request['scurrency'];
			$this->_view->currency = $scurrency;
			$this->_view->edit_status = $request['edit_status'];
			
			$contract_id = $request['contract_id'];
			$contract_obj = new Ep_Quote_Quotecontract();
			/* $splitvals = $contract_obj->getSplitTurnovers($contract_id);
			if($splitvals)
			{
				$splitvalues = array();
				$turnovers = array();
				$months = array();
				foreach($splitvals as $row)
				{
					//$dispmonthyear = date('M y', strtotime($row['month_year']));
					//$months[] = $dispmonthyear;
					$months[] = $row['month_year'];
					//$turnovers[$row['type']][$row['name']][$dispmonthyear] =  $row['turnover'];
					$turnovers[$row['type']][$row['name']][$row['month_year']] =  $row['turnover'];
				}
				$months = array_unique($months);
			}
			else */
			{
				$searchParameters = array();
				$searchParameters['quote_id']=$quote_id;
				$quotecontract_obj=new Ep_Quote_Quotecontract();
				/* Getting all the missions from the Quote */
				$missionDetails=$quotecontract_obj->getSplitMissions($searchParameters);	
				$splitvalues = array();
				$turnovers = array();
				$months = array();
				if($missionDetails)
				{
					$i = 0;
					for($i=0;$i<count($missionDetails);$i++)
					{
						$oneshot = true;
						$date1 = $orgdate1;
						$turnover = $missionDetails[$i]['calturnover'];
						/* Getting mission_length and mission_length_option from Quotemission to get number of days to calculate end date of mission */ 
						if($missionDetails[$i]['mission_length_option']=='days')
						{
							//$days = $missionDetails[$i]['maxlength']+$prod_extra_days;
							$days = $missionDetails[$i]['maxlength'];
						}
						else
						{
							//$days = ceil($missionDetails[$i]['maxlength']/24)+$prod_extra_days;
							$days = ceil($missionDetails[$i]['maxlength']/24);
						}
						/* For seo missions */
						if($missionDetails[$i]['product']=='seo_audit' || $missionDetails[$i]['product']=='smo_audit')
						{
							$type = $this->product_array[$missionDetails[$i]['product']];
							$product_type = $missionDetails[$i]['product'];
						}
						else
						{
							$type = $this->producttype_array[$missionDetails[$i]['pptype']]." ".$missionDetails[$i]['product_type_other'];
							$product_type = $missionDetails[$i]['pptype'];
							/* If oneshot calculate no. days by subtracting staff days and starting date will be added by staff days */
							if($missionDetails[$i]['oneshot']=='yes')
							{
								$staff_days = $missionDetails[$i]['staff_time_option']=="days"?$missionDetails[$i]['staff_time']:ceil($missionDetails[$i]['staff_time']/24);
								$days = $days-$staff_days;
								$date1 = date('Y-m-d',strtotime($date1."+ $staff_days days"));
							}
							elseif($missionDetails[$i]['oneshot']=='no')
							{
								$oneshot = false;
								/* If oneshot is no than getting turnover by day wise */
								if($missionDetails[$i]['tempo']=="fix")
								{
									$turnover = ($turnover/$missionDetails[$i]['volume'])*$missionDetails[$i]['volume_max'];
									$turnover = $this->getFinalTurnover($turnover,$missionDetails[$i]['tempo_length_option'],$missionDetails[$i]['tempo_length']);
								}
								else
								{
									/* $days1 = $missionDetails[$i]['staff_time_option']=="days"?$days-$missionDetails[$i]['staff_time']:$days-ceil($missionDetails[$i]['staff_time']/24); */
									$staff_days = $missionDetails[$i]['staff_time_option']=="days"?$missionDetails[$i]['staff_time']:ceil($missionDetails[$i]['staff_time']/24);
									$days = $days-$staff_days;
									$date1 = date('Y-m-d',strtotime($date1."+ $staff_days days"));
									$dayintern = $days;
									if($dayintern<=0)
										$dayintern = 1;
									$turnover = 
									($missionDetails[$i]['volume']/$dayintern)*($turnover/$missionDetails[$i]['volume']);
									//$turnover = $this->getFinalTurnover($turnover,$missionDetails[$i]['tempo_length_option'],$missionDetails[$i]['tempo_length']);
								}
							}
						}
						if(!$days || $days<0 )
							$days=1;
						/* Getting date2 as end date by adding mission length to contract launch date based on above conditions */
						$date2 = date('Y-m-d',strtotime($date1."+ $days days"));
						/* If oneshot get whole turnover and split according to days else get turnover per day and mulitply it by no. of days in a month */
						if($oneshot)
						$splitvalues = $this->getSplitValues($date1, $date2,$turnover,$days,$scurrency);
						else
						$splitvalues = $this->getSplitValues2($date1, $date2,$turnover,$days,$scurrency);
						if(count($splitvalues['months'])>count($months))
						$months = $splitvalues['months'];
						$turnovers[$missionDetails[$i]['identifier']][$type] = $splitvalues['turnover'];
					}
				}	
				/* Turnover for tech missions same as seo missions */
				$tech_obj = new Ep_Quote_TechMissions();
				$tech_missions = $tech_obj->getTechMissionDetails(array('quote_id'=>$quote_id,'include_final'=>'yes'));
				if($tech_missions)
				{
					$i = 0;
					for($i=0;$i<count($tech_missions);$i++)
					{
						if($tech_missions[$i]['title'])
						$type = $tech_missions[$i]['title'];
						else
							$type = "New Tech Mission";
						$turnover = $tech_missions[$i]['turnover'];
						if($tech_missions[$i]['package']=='team')
							$turnover += $tech_missions[$i]['team_fee'] * $tech_missions[$i]['team_packs'];
						if($tech_missions[$i]['delivery_option']=='days')
							$days = $tech_missions[$i]['delivery_time']+$prod_extra_days;
						else
						{
							$days = ceil($tech_missions[$i]['delivery_time']/24)+$prod_extra_days;
						}
						$date2 = date('Y-m-d',strtotime($date1."+ $days days"));
						$splitvalues = $this->getSplitValues($date1, $date2,$turnover,$days,$scurrency);
						if(count($splitvalues['months'])>count($months))
						$months = $splitvalues['months'];
						$turnovers[$tech_missions[$i]['identifier']][$type] = $splitvalues['turnover'];
					}
				}
			}
			
			$this->_view->months = $months;
			$this->_view->turnovers = $turnovers; 
			$this->_view->monthcount = count($months); 
			
	/* 		echo "<PRE>";
			print_r($months);
			echo "<PRE>";
			print_r($turnovers);
			exit;      */
			$this->render('split_month');
		}
	}
	/* To get turnover per day wise */
	function getFinalTurnover($turnover,$option,$per="")
	{
		if(!$per)
			$per = 1;
		if($option=="week")
		 $turnover /= (7*$per);
		elseif($option=="month")
		$turnover /= (30*$per);
		elseif($option=="year")
		$turnover /= (365*$per);
		else
		$turnover /= $per;	
		return $turnover;
	}
	/* Splitting total turnover by no. of days in month 
	$date1 is startdate, $date2 is enddate, $turnover is total turnover of mission, $days is number of mission days, $currency not in use */
	function getSplitValues($date1, $date2,$turnover,$days,$currency)
	{
		$time1 = strtotime($date1);
		$time2 = strtotime($date2);
		$my = date("mY", $time2);
	
		//$months = array(date('F Y', $time1));
		$months = array();
		$month1 = date("mY",$time1);
		$month2 = date("mY",$time2);
		$turnovers = array();
		//$month_name = date('M y', $time1);
		$month_name = date('Y-m', $time1);
		$mid_turnover = $turnover;
		if($month1 == $month2)
		{
			$last_date = date("t",$time1);
			$first_date = date("d",$time1);
			$turnovers[$month_name] = number_format(round($mid_turnover,2),2,'.','');
			$months[] = $month_name;
		}
		else
		{
			$last_date = date("t",$time1);
			$first_date = date("d",$time1);
			$turnover_cal = $turnover*($last_date-$first_date)/$days;
			if($turnover_cal!=0)
			{
				$turnovers[$month_name] = number_format(round($turnover_cal,2),2,'.','');
				$months[] = $month_name;
			}
			while($time1 < $time2) {
			$time1 = strtotime(date('Y-m-'.'01', $time1).' +1 month');
				if(date('mY', $time1) != $my && ($time1 < $time2))
				{
					//$month_name =  date('M y', $time1);
					$month_name =  date('Y-m', $time1);
					$mdays = cal_days_in_month(CAL_GREGORIAN, date('n',$time1),date('Y',$time1)); 
					$turnovers[$month_name] = number_format(round($turnover*$mdays/$days,2),2,'.','');
					//$months[] = date('F Y', $time1);
					$months[] = $month_name;
				}
			}
			$last_date = date("d",$time2);
			//$month2 = date('M y', $time2);
			$month2 = date('Y-m', $time2);
			$turnovers[$month2] = number_format(round($turnover*$last_date/$days,2),2,'.','');
			//$months[] = date('F Y', $time2);
			$months[] = $month2;
		}
		$array = array('months'=>$months,'turnover'=>$turnovers);
		return $array;
	}
	// To get split values of max and fix tempo mission by turnover per day
	function getSplitValues2($date1, $date2,$turnover,$days,$currency)
	{
		$time1 = strtotime($date1);
		$time2 = strtotime($date2);
		$my = date("mY", $time2);
		//$months = array(date('F Y', $time1));
		$months = array();
		$month1 = date("mY",$time1);
		$month2 = date("mY",$time2);
		$turnovers = array();
		//$month_name = date('M y', $time1);
		$month_name = date('Y-m', $time1);
		$mid_turnover = $turnover;
		if($month1 == $month2)
		{
			$last_date = date("t",$time1);
			$first_date = date("d",$time1);
			$turnovers[$month_name] = number_format(round($mid_turnover*$days,2),2,'.','');
			$months[] = $month_name;
		}
		else
		{
			$last_date = date("t",$time1);
			$first_date = date("d",$time1);
			$turnover_cal = $turnover*($last_date-$first_date);
			if($turnover_cal!=0)
			{
				$turnovers[$month_name] = number_format(round($turnover_cal,2),2,'.','');
				$months[] = $month_name;
			}
			while($time1 < $time2) {
			$time1 = strtotime(date('Y-m-'.'01', $time1).' +1 month');
				if(date('mY', $time1) != $my && ($time1 < $time2))
				{
					//$month_name =  date('M y', $time1);
					$month_name =  date('Y-m', $time1);
					$mdays = cal_days_in_month(CAL_GREGORIAN, date('n',$time1),date('Y',$time1)); 
					$turnovers[$month_name] = number_format(round($turnover*$mdays,2),2,'.','');
					//$months[] = date('F Y', $time1);
					$months[] = $month_name;
				}
			}
			$last_date = date("d",$time2);
			//$month2 = date('M y', $time2);
			$month2 = date('Y-m', $time2);
			$turnovers[$month2] = number_format(round($turnover*$last_date,2),2,'.','');
			//$months[] = date('F Y', $time2);
			$months[] = $month2;
		}
		$array = array('months'=>$months,'turnover'=>$turnovers);
		return $array;
	}
	/* Insert or update Contract in QuoteContract table */
	function saveContractAction()
	{
		if($this->_request-> isPost())            
        {  
			$params = $this->_request->getParams();
			$splitvals = (array)$params['splitval'];
			$splittype = (array)$params['splittype'];
			$save = array();
			$save['contractname'] = $params['contract_name'];
			$save['contractstatus'] = $params['status'];
			$save['sourceofcontract'] = $params['contractsource'];
			$signature_date = explode("/",$params['signature_date']);
			$save['signaturedate'] = $signature_date[2]."-".$signature_date[1]."-".$signature_date[0];
			$expected_launch_date = explode("/",$params['expected_launch_date']);
			$save['expected_launch_date'] = $expected_launch_date[2]."-".$expected_launch_date[1]."-".$expected_launch_date[0];
			$save['comment'] = $params['comment'];
			$save['type_of_payment'] = $params['paymenttype'];
			$save['indicative_turnover'] = $params['indicative_turnover'];
			$save['minimum_turnover'] = $params['minimum_turnover'];
			$save['mini_turnover_status'] = $params['mini_turn'];
			if($params['expected_end_date']):
			$expected_end_date = explode("/",$params['expected_end_date']);
			$expected_end = $expected_end_date[2]."-".$expected_end_date[1]."-".$expected_end_date[0];
			$save['expected_end_date'] = $expected_end;
			endif;
			
			$active = 'validate';
			$quote_contract = new Ep_Quote_Quotecontract();
			
			if($params['quote_id']):
			$quote_id = $save['quoteid'] = $params['quote_id'];
			$save['sales_creator_id'] = $this->_view->loginuserId;
			$save['turnover'] = $params['turnover'];
			$contractid = $quote_contract->insertQuotecontract($save);
			$active = 'validate';
			$this->_helper->FlashMessenger('Created Contract Successfully');
			
			/* Send Mail to facturation and quote creator */
			$mail_obj=new Ep_Message_AutoEmails();
			$mail_content = $mail_obj->getAutoEmail(157);
	
			$contract_name = $save['contractname'];
			$contract_link = "<a href='".$this->url."/contractmission/contract-edit?submenuId=ML13-SL3&contract_id=".$contractid."&action=view' target='_blank'>click here</a>";
			
			$quote_obj = new Ep_Quote_Quotes();
			$quote_res = $quote_obj->getQuoteDetails($params['quote_id']);
			$quote_name = $quote_res[0]['title'];
			$subject = $mail_content[0]['Object'];
			eval("\$subject= \"$subject\";");
			$message = $orgmessage =  $mail_content[0]['Message'];
			
			$client_obj=new Ep_Quote_Client();
			$bo_user_details=$client_obj->getQuoteUserDetails($quote_res[0]['created_by']);
			if($bo_user_details!='NO')
			{
				$name = $bo_user_details[0]['first_name']." ".$bo_user_details[0]['last_name'];
				eval("\$message= \"$message\";");
				$mail_obj->sendEMail('work@edit-place.com',$message,$bo_user_details[0]['email'],$subject);
			}
			
			$mail_content2 = $mail_obj->getAutoEmail(158);
			$reminder = $params['expected_launch_date'];
			$subject2 = $mail_content2[0]['Object'];
			$orgmessage2 = $mail_content2[0]['Message'];
			eval("\$subject2= \"$subject2\";");
			
			
			/*check and update client code added by arun*/
			$client_obj = new Ep_Quote_Client();		
			$client_details = $client_obj->getClientDetails($quote_res[0]['client_id']);
				
			if($client_details!="NO")
			{
				$client_id=$quote_res[0]['client_id'];
				$client_code=$client_details[0]['client_code'];
				if(stristr($client_code,'p'))
				{					
					$clientCodes=$client_obj->getAllClientCodes();
					if($clientCodes)
					{
						foreach(range(1,9999) as $val)
						{
							$code='C'.sprintf("%03d",$val);
							if(!preg_grep( "/$code/i" , $clientCodes ))
							{
								$new_client_code=$code;
								break;
							}
							
						}
					}
					else{
						$clientCodes=array();
					}	
					/*update new client code*/
					if($new_client_code)	
					{
						$update_client['client_code']=$new_client_code;
						$client_obj->updateClientProfile($update_client,$client_id);
						
					}
				}
			}
			//exit;
			
			
			
			$facturationUsers = $quote_contract->getUsers("facturation");
			foreach($facturationUsers as $row)
			{
				$name = $row['first_name']." ".$row['last_name'];
				eval("\$message= \"$orgmessage\";");
				eval("\$message2= \"$orgmessage2\";");
				$mail_obj->sendEMail('work@edit-place.com',$message,$row['email'],$subject);
				$mail_obj->sendEMail('work@edit-place.com',$message2,$row['email'],$subject2);
			}

			elseif($params['contract_id']):
			$contractid = $params['contract_id'];
			$contract_details = $quote_contract->getContract($contractid);
			$quote_id = $contract_details[0]['quoteid'];
			$active = $params['activetab'];
			if($this->_view->user_type=='superadmin' || $this->_view->user_type=='facturation')
			{
				$redirect = false;
				//$save = array();
			
				$save['finance_validator_id'] = $this->_view->loginuserId;
				if($params['validate']=="validate")
				{
					$save['status'] = "validated";
					$active = "";
					$this->_helper->FlashMessenger('Validated Contract Successfully');
					
					$log_obj=new Ep_Quote_QuotesLog();					
					$actionmessage = $log_obj->getActionSentence(11);
					$client_obj = new Ep_Quote_Client();
					$bo_user_details=$client_obj->getQuoteUserDetails($this->adminLogin->userId);
					if($bo_user_details!='NO')
						$finance_user="<strong>".$bo_user_details[0]['first_name'].' '.$bo_user_details[0]['last_name']."</strong>";
					else
						$finance_user="";
					$launch_date = date('d/m/Y',strtotime($save['expected_launch_date']));
					$actionmessage=strip_tags($actionmessage);
					eval("\$actionmessage= \"$actionmessage\";");
				
					$log_array['user_id']=$this->adminLogin->userId;
					$log_array['contract_id']=$params['contract_id'];
					$log_array['user_type']=$this->adminLogin->type;
					$log_array['action']='contract_validated';
					$log_array['quote_id']= $params['quote_logs_id'];
					$log_array['action_at']=date("Y-m-d H:i:s");
					$log_array['action_sentence']=$actionmessage;
					
					$log_obj->insertLogs($log_array);
					
					/* Send Mail to Managers to initimate assign User */
					$mail_obj=new Ep_Message_AutoEmails();
					$mail_content = $mail_obj->getAutoEmail(159);
					$message = $orgmessage =  $mail_content[0]['Message'];
					$contract_name = $contract_details[0]['contractname'];
					$client_obj = new Ep_Quote_Client();
					$bo_user_details=$client_obj->getQuoteUserDetails($this->adminLogin->userId);
					$validated_by = $bo_user_details[0]['first_name']." ".$bo_user_details[0]['last_name'];
					$mission_link = "<a href='".$this->url."/contractmission/missions-list?submenuId=ML13-SL4&contract_id=".$contractid."'>click here</a>";
					// Tech Missions
					$tech_obj = new Ep_Quote_TechMissions();
					$tech_missions = $tech_obj->getTechMissionDetails(array('quote_id'=>$quote_id,'include_final'=>'yes'));
					if($tech_missions)
						$tech_count = count(tech_missions);
					else
						$tech_count = 0;
					if($tech_count)
					{
						$no_of_missions = $tech_count;
						$subject = $mail_content[0]['Object'];
						eval("\$subject= \"$subject\";");
						$techmanagers = $quote_contract->getUsers('techmanager');
						foreach($techmanagers as $row)
						{
							$name = $row['first_name']." ".$row['last_name'];
							eval("\$message= \"$orgmessage\";");
							$mail_obj->sendEMail($this->mail_from,$message,$row['email'],$subject);
						}
					}
					// SEO Missions
					$mission_obj=new Ep_Quote_QuoteMissions();
					$searchParameters = array();
					$searchParameters['quote_id']=$quote_id;
					$searchParameters['misson_user_type_prod_seo']='sales OR seo';
					$searchParameters['include_final']='yes';
					$searchParameters['product_type_seo']='IN';
					$seoMissionDetails =  $mission_obj->getMissionDetails($searchParameters);
					if($seoMissionDetails)
						$seo_count = count($seoMissionDetails);
					else
						$seo_count = 0;
					if($seo_count)
					{
						$no_of_missions = $seo_count;
						$subject = $mail_content[0]['Object'];
						eval("\$subject= \"$subject\";");
						$seomanagers = $quote_contract->getUsers('seomanager');
						foreach($seomanagers as $row)
						{
							$name = $row['first_name']." ".$row['last_name'];
							eval("\$message= \"$orgmessage\";");
							$mail_obj->sendEMail($this->mail_from,$message,$row['email'],$subject);
						}
					}
					// Prod Missions
					$searchParameters['product_type_seo']='NOT IN';
					 $prodMissionDetails =  $mission_obj->getMissionDetails($searchParameters);
					if($prodMissionDetails)
						$prod_count = count($prodMissionDetails);
					else
						$prod_count = 0;
					if($prod_count)
					{
						$no_of_missions = $prod_count;
						$subject = $mail_content[0]['Object'];
						eval("\$subject= \"$subject\";");
						//$seomanagers = $quote_contract->getUsers('multilingue');
						$seomanagers = $quote_contract->getUsers('prodmanager');
						foreach($seomanagers as $row)
						{
							$name = $row['first_name']." ".$row['last_name'];
							eval("\$message= \"$orgmessage\";");
							$mail_obj->sendEMail($this->mail_from,$message,$row['email'],$subject);
						}
					}
					$no_of_missions = (int)$tech_count + (int)$seo_count + (int)$prod_count;
					$subject = $mail_content[0]['Object'];
					eval("\$subject= \"$subject\";");
					$quote_obj = new Ep_Quote_Quotes();
					$quote_res = $quote_obj->getQuoteDetails($quote_id);
					$client_obj=new Ep_Quote_Client();
					$bo_user_details=$client_obj->getQuoteUserDetails($quote_res[0]['created_by']);
					if($bo_user_details!='NO')
					{
						$name = $bo_user_details[0]['first_name']." ".$bo_user_details[0]['last_name'];
						eval("\$message= \"$orgmessage\";");
						$mail_obj->sendEMail($this->mail_from,$message,$bo_user_details[0]['email'],$subject);
					} 
				}
				else
				{
					$this->_helper->FlashMessenger('Updated Contract Successfully');
				}
				$save['type_of_payment'] = $params['paymenttype'];
				$save['sourceofcontract'] = $params['contractsource'];
				unset($save['contractname']);
				$quote_contract->updateContract($save,$params['contract_id']);
			}
			else
			{
			$this->_helper->FlashMessenger('Updated Contract Successfully');
			unset($save['contractname']);
			$quote_contract->updateContract($save,$contractid);
			}
			endif;
			
			/* Insert Split Month in ContractSplitValues table */
			$splitvals = (array)$params['splitval'];
			$splittype = (array)$params['splittype'];
			if(!empty($splitvals))
			{
				$quote_contract->deleteSplitTurnovers($contractid);
				foreach($splitvals as $mission_type => $producttype1)
				{
					foreach($producttype1 as $producttypekey => $producttype)
					{
					foreach($producttype as $monthname => $val)
					{
						$split = array();
						$split['contract_id'] = $contractid;
						$split['quote_id'] = $quote_id;
						//$split['type'] = $splittype[$mission_type][$monthname];
						$split['type'] = "";
						$split['mission_id'] = $mission_type;
						//$split['name'] = $mission_type;
						$split['name'] = $producttypekey;
						//$monthname .="-01";
						/* $dbmonthname = date("Y-m", strtotime($monthname));
						$split['month_year'] = $dbmonthname; */
						$split['month_year'] = $monthname;
						$split['turnover'] = $val;
						$quote_contract->insertSplitTurnovers($split);
					}
					}
				}
			}
			/* Update invoice type in Quotemissions if mission is not assigned else Contractmission */
			$quote_mission_obj = new Ep_Quote_QuoteMissions();
			foreach($params['invoice_per'] as $key => $value)
			{
				$qmsave['invoice_per'] = $value;
				$qmsave['indicative_turnover'] = $params['inductive'][$key];
				if($params['missiontable'][$key]=='qm')
				$quote_mission_obj->updateQuoteMission($qmsave,$key);
				else				
				$quote_contract->updateContractMission($qmsave,$key);		
			}	
			
			$this->uploadFiles($_FILES,$contractid,$quote_contract,$params['document_name']);
			
		}
		//$active = $params['activetab'];
		if($params['contract_id'])
		$this->_redirect("/contractmission/contract-edit?contract_id=$params[contract_id]&submenuId=ML13-SL3");
		else
		$this->_redirect("/contractmission/contract-list?active=$active&submenuId=ML13-SL3");
	}
	/* uploading contract files */
	function uploadFiles($FILES,$contractid,$quote_contract,$pdocument_name)
	{
		if(count($FILES['mulitupload']['name'])>0)	
		{
			$update = false;
			$documents_path=array();
			$documents_name=array();
			foreach($FILES['mulitupload']['name'] as $index=>$quote_files)
			{
				if($FILES['mulitupload']['name'][$index]):
				//upload quote documents
				$quoteDir=$this->contract_documents_path.$contractid."/";
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
					$documents_path[]=$contractid."/".$document_name;
					$documents_name[]=  str_replace('|',"_",$pdocument_name[$index]);
				endif;
			}
			if($update)
			{
				 $quotes_update_data = array();
				 $quoteDetails=$quote_contract->getContract($contractid);
				 $uploaded_documents1 = explode("|",$quoteDetails[0]['contractfilepaths']);
				 $documents_path =array_merge($documents_path,$uploaded_documents1);
				 $quotes_update_data['contractfilepaths']=implode("|",$documents_path);
				 $document_names =explode("|",$quoteDetails[0]['contractcustomfilenames']);
				 $documents_name =array_merge($documents_name,$document_names);
				 $quotes_update_data['contractcustomfilenames']=implode("|",$documents_name);
				 $quote_contract->updateContract($quotes_update_data,$contractid);
			}
		}
		
	}
	/* To list a contracts */
	function contractListAction()
	{
		$quote_contract = new Ep_Quote_Quotecontract();
		$this->_view->contracts_to_validate = $contracts_to_validate = $quote_contract->getContracts(array('status'=>'sales','percentage'=>10));
		$this->_view->contracts_opened = $contracts_opened = $quote_contract->getContracts(array('status'=>'validated','percentage'=>10));
		$this->_view->contracts_finished = $contracts_finished = $quote_contract->getContracts(array('status'=>'validated','percentage'=>100));
		$this->_view->contracts_closed = $contracts_closed = $quote_contract->getContracts(array('status'=>'closed','percentage'=>10));
		$this->_view->contracts_deleted = $contracts_deleted = $quote_contract->getContracts(array('status'=>'deleted','percentage'=>10));
		
		/* if($this->_view->user_type=='superadmin')
			$salesusers = $quote_contract->getUsers('salesuser',true);
		else 
			$salesusers = $quote_contract->getUsers('salesuser');
		
		$this->_view->salesusers = $salesusers;
		$this->_view->clients = $quote_contract->getUsers('client'); */
		/* Get users and clients based on tab listing in contract */
		$client_sales = $this->getSalesClients($contracts_to_validate);
		natcasesort($client_sales['clients']);
		$this->_view->tovalidate_clients = $client_sales['clients'];
		natcasesort($client_sales['sales_users']);
		$this->_view->tovalidate_sales_users = $client_sales['sales_users'];
		$client_sales = $this->getSalesClients($contracts_opened);
		natcasesort($client_sales['clients']);
		$this->_view->contracts_opened_clients = $client_sales['clients'];
		natcasesort($client_sales['sales_users']);
		$this->_view->contracts_opened_sales_users = $client_sales['sales_users'];
		$client_sales = $this->getSalesClients(array_merge($contracts_finished,$contracts_closed));
		natcasesort($client_sales['clients']);
		$this->_view->contracts_finished_clients = $client_sales['clients'];
		natcasesort($client_sales['sales_users']);
		$this->_view->contracts_finished_sales_users = $client_sales['sales_users'];
		$client_sales = $this->getSalesClients($contracts_deleted);
		natcasesort($client_sales['clients']);
		$this->_view->contracts_deleted_clients = $client_sales['clients'];
		natcasesort($client_sales['sales_users']);
		$this->_view->contracts_deleted_sales_users = $client_sales['sales_users'];
		$this->render('quotecontract-list');
	}
	
	/* To get sales user and client based on type of contract for sort in Listing */
	function getSalesClients($contracts)
	{
		$client_sales = array();
		foreach($contracts as $row)
		{
			$client_sales['clients'][$row['clientid']] = $row['company_name'];
			$client_sales['sales_users'][$row['sales_creator_id']] = $row['first_name']." ".$row['last_name'];
		}
		return $client_sales;
	}
	/* Same as contract list thorugh Ajax */
	function loadcontractsAction()
	{
		if($this->_request->isPost() && $_SERVER['HTTP_X_REQUESTED_WITH']=='XMLHttpRequest')
		{
			$parmas = $this->_request->getParams();
			$quote_contract = new Ep_Quote_Quotecontract();
			if($parmas['opened']==1)
			$this->_view->contracts = $quote_contract->getContracts(array('status'=>'validated','percentage'=>10,'client_id'=>$parmas['client_id'],'sales_id'=>$parmas['sid']));
			elseif($parmas['opened']==2)
			{
			$this->_view->contracts = $quote_contract->getContracts(array('status'=>'validated','percentage'=>100,'client_id'=>$parmas['client_id'],'sales_id'=>$parmas['sid']));
				$this->_view->contracts_closed = $quote_contract->getContracts(array('status'=>'closed','percentage'=>10,'client_id'=>$parmas['client_id'],'sales_id'=>$parmas['sid']));
			}
			elseif($parmas['opened']==3)
			{
				$this->_view->contracts_deleted = $quote_contract->getContracts(array('status'=>'deleted','percentage'=>10,'client_id'=>$parmas['client_id'],'sales_id'=>$parmas['sid']));
			}
			else
			$this->_view->contracts = $quote_contract->getContracts(array('status'=>'sales','percentage'=>10,'client_id'=>$parmas['client_id'],'sales_id'=>$parmas['sid']));
			$this->_view->opened = $parmas['opened'];
			$this->render('quotecontract-list-ajax');
		}
	}
	/* To add comments for closed contract by finance user */
	function addCommentsAction()
	{
		$request = $this->_request->getParams();
		if($_SERVER['HTTP_X_REQUESTED_WITH']=='XMLHttpRequest' && $request['contract_id'])
		{
			$this->_view->request = $request;
			$log_obj=new Ep_Quote_QuotesLog();
			/* Fetching from Quoteslog table with status contract_closed_comments */
			$closed_comments = $log_obj->getLogs(array('contract_id'=>$request['contract_id'],'action'=>'contract_closed_comments'));
			$i=0;
			foreach($closed_comments as $row)
			{
				$closed_comments[$i++]['created_time'] = time_ago($row['action_at']);
			}
			$this->_view->closed_comments = $closed_comments;
			$this->render('closed-contract-comments');
		}
	}
	/* To save comments of closed contract in Quoteslog table */
	function saveClosedCommentsAction()
	{
		$request = $this->_request->getParams();
		if($request['contract_id'] && $request['quote_id'])
		{
			$log_array = array();
			$log_obj=new Ep_Quote_QuotesLog();		
			$log_array['user_id'] = $this->adminLogin->userId;
			$log_array['contract_id'] = $request['contract_id'];
			$log_array['quote_id'] = $request['quote_id'];
			$log_array['comments'] = $request['comments'];
			$log_array['user_type']=$this->adminLogin->type;
			$log_array['action']='contract_closed_comments';
			$log_array['action_at']=date("Y-m-d H:i:s");
			$log_array['action_sentence']="Closed comment added by ".$this->adminLogin->loginName;
	
			$log_obj->insertLogs($log_array);
			if(!$request['ccomment'])
			{
				$quote_contract = new Ep_Quote_Quotecontract();
				$quote_contract->updateContract(array('closed_comment'=>$request['comments']),$request['contract_id']);
			}
			$this->_helper->FlashMessenger('Added closed comment successfully');
		}
		$this->_redirect("/contractmission/contract-list?submenuId=ML13-SL3&active=finished");
	}
	/* Deprecated not in use */
	function contractFinanceEditAction()
	{
		$requestparmas = $this->_request->getParams();
		
		if($requestparmas['contract_id'])
		{
			$quote_contract = new Ep_Quote_Quotecontract();
			$contract = $quote_contract->getContract($requestparmas['contract_id']);
			
			if($contract)
			{
				
				$quote_obj = new Ep_Quote_Quotes();
				$quoteDetials = $quote_obj->getQuoteDetails($contract[0]['quoteid']);
				$client_obj = new Ep_Quote_Client();		
				$client_details = $client_obj->getClientDetails($quoteDetials[0]['client_id']);
				$related_files='';
				if($contract[0]['contractfilepaths'])
				{
					$documents_path=explode("|",$contract[0]['contractfilepaths']);
					$documents_name=explode("|",$contract[0]['contractcustomfilenames']);

					foreach($documents_path as $k=>$file)
					{
						if(file_exists($this->contract_documents_path.$documents_path[$k]) && !is_dir($this->contract_documents_path.$documents_path[$k]))
						{
							if($documents_name[$k])
								$file_name=$documents_name[$k];
							else
								$file_name=basename($file);

							$related_files.='
							<div class="topset2"><a href="/contractmission/download-document?type=contract&index='.$k.'&contract_id='.$requestparmas['contract_id'].'">'.$file_name.'</a><span class="delete" rel="'.$k.'_'.$requestparmas['contract_id'].'"> <i class="splashy-error_x"></i></span></div>';
						}
					}
				}
				$this->_view->contractDetials = $contract[0];
				$this->_view->related_files = $related_files;
				
				if($client_details!="NO")
				{
					$client_details[0]['category'] = $this->getCustomName("EP_ARTICLE_CATEGORY",$client_details[0]['category']);
					$this->_view->client_info = $client_details[0];
				}
				else
				$this->_view->client_info = "Client info unavailable";
				
				$searchParameters['quote_id']=$contract[0]['quoteid'];
				$searchParameters['misson_user_type']='sales';
				$quoteMission_obj=new Ep_Quote_QuoteMissions();
				$missionDetails=$quoteMission_obj->getMissionDetails($searchParameters);
				
				if($missionDetails)
				{
					$i = 0;
					for($i=0;$i<count($missionDetails);$i++)
					{
						$missionDetails[$i]['product_type_converted'] = $this->product_array[$missionDetails[$i]['product']];
						$missionDetails[$i]['language_source_converted'] = $this->getCustomName("EP_LANGUAGES",$missionDetails[$i]['language_source']);
						$missionDetails[$i]['product_type_name']=$this->producttype_array[$missionDetails[$i]['product_type']];
						if($missionDetails[$i]['language_dest'] && $missionDetails[$i]['product']=="translation")
						$missionDetails[$i]['language_dest_converted'] = $this->getCustomName("EP_LANGUAGES",$missionDetails[$i]['language_dest']);
						
					}
				}	
				
				$this->_view->missiondetails = $missionDetails;
				$quoteDetials[0]['category_name'] = $this->getCustomName("EP_ARTICLE_CATEGORY",$quoteDetials[0]['category']);
				$this->_view->quotedetails = $quoteDetials[0];
				$this->_view->csarray = array(''=>'Select','Edit Place'=>'Edit Place','Client'=>'Client');
				$this->_view->typeofpayment = array(''=>'Select','factor'=>'Factor','daily'=>'Dailly','direct'=>'Direct','others'=>'Others');
				
				$date1 = $contract[0]['expected_launch_date'];
				
				if($quoteDetials[0]['prod_extra_info']=='yes')
				$prod_extra_days = $quoteDetials[0]['prod_extra_launch_days'];
				else
				$prod_extra_days = 0;
				if($quoteDetials[0]['final_mission_length_option']=='days')
					$days = $quoteDetials[0]['final_mission_length']+$prod_extra_days;
				else
					$days = ceil($quoteDetials[0]['final_mission_length']/24)+$prod_extra_days; // needs to be done
					
				$date2 = date('Y-m-d',strtotime($date1."+ $days days"));
				$turnover = $quoteDetials[0]['turnover'];
				
				$splitvalues = $this->getSplitValues($date1, $date2,$turnover,$days,"");
				$this->_view->months = $splitvalues['months'];
				$this->_view->turnovers = $splitvalues['turnover'];
				$this->_view->expected_end_date = $date2;
				
				$this->render('edit-create-quote-contract');
			}
			else
			$this->_redirect("/contractmission/contract-list?submenuId=ML13-SL3");
		}
		else
		$this->_redirect("/contractmission/contract-list?submenuId=ML13-SL3");
	}
	// Deprecate and not in use
	function contractSalesEditAction()
	{
		$requestparmas = $this->_request->getParams();
		
		if($requestparmas['contract_id'])
		{
			$quote_contract = new Ep_Quote_Quotecontract();
			$contract = $quote_contract->getContract($requestparmas['contract_id']);
			
			if($contract)
			{
				$quote_obj = new Ep_Quote_Quotes();
				$quoteDetials = $quote_obj->getQuoteDetails($contract[0]['quoteid']);
				$client_obj = new Ep_Quote_Client();		
				$client_details = $client_obj->getClientDetails($quoteDetials[0]['client_id']);
				$related_files='';
				if($contract[0]['contractfilepaths'])
				{
					$documents_path=explode("|",$contract[0]['contractfilepaths']);
					$documents_name=explode("|",$contract[0]['contractcustomfilenames']);

					foreach($documents_path as $k=>$file)
					{
						if(file_exists($this->contract_documents_path.$documents_path[$k]) && !is_dir($this->contract_documents_path.$documents_path[$k]))
						{
							if($documents_name[$k])
								$file_name=$documents_name[$k];
							else
								$file_name=basename($file);

							$related_files.='
							<div class="topset2"><a href="/contractmission/download-document?type=contract&index='.$k.'&contract_id='.$requestparmas['contract_id'].'">'.$file_name.'</a><span class="delete" rel="'.$k.'_'.$requestparmas['contract_id'].'"> <i class="splashy-error_x"></i></span></div>';
						}
					}
				}
				$this->_view->contractDetials = $contract[0];
				$this->_view->related_files = $related_files;
				
				if($client_details!="NO")
				{
					$client_details[0]['category'] = $this->getCustomName("EP_ARTICLE_CATEGORY",$client_details[0]['category']);
					$this->_view->client_info = $client_details[0];
				}
				else
				$this->_view->client_info = "Client info unavailable";
				
				$searchParameters['quote_id']=$contract[0]['quoteid'];
				$searchParameters['misson_user_type_prod_seo']='sales OR seo';
				$searchParameters['product_type_seo']='NOT IN';
				$searchParameters['include_final']='yes';
				$searchParameters['contract_id']=$requestparmas['contract_id'];
				$quoteMission_obj=new Ep_Quote_QuoteMissions();
				$missionDetails=$quoteMission_obj->getMissionDetailsContract($searchParameters);
				
				if($missionDetails)
				{
					$i = 0;
					for($i=0;$i<count($missionDetails);$i++)
					{
						$missionDetails[$i]['product_type_converted'] = $this->product_array[$missionDetails[$i]['product']];
						$missionDetails[$i]['language_source_converted'] = $this->getCustomName("EP_LANGUAGES",$missionDetails[$i]['language_source']);
						$missionDetails[$i]['product_type_name']=$this->producttype_array[$missionDetails[$i]['product_type']];
						if($missionDetails[$i]['language_dest'] && $missionDetails[$i]['product']=="translation")
						$missionDetails[$i]['language_dest_converted'] = $this->getCustomName("EP_LANGUAGES",$missionDetails[$i]['language_dest']);
						
					}
				}	
				
				$this->_view->missiondetails = $missionDetails;
				$quoteDetials[0]['category_name'] = $this->getCustomName("EP_ARTICLE_CATEGORY",$quoteDetials[0]['category']);
				
				$this->_view->quotedetails = $quoteDetials[0];
				$this->_view->csarray = array(''=>'Select','Edit Place'=>'Edit Place','Client'=>'Client');
				$this->_view->typeofpayment = array(''=>'Select','factor'=>'Factor','daily'=>'Dailly','direct'=>'Direct','others'=>'Others');
				
				$date1 = $contract[0]['expected_launch_date'];
				if($quoteDetials[0]['prod_extra_info']=='yes')
				$prod_extra_days = $quoteDetials[0]['prod_extra_launch_days'];
				else
				$prod_extra_days = 0;
				if($quoteDetials[0]['final_mission_length_option']=='days')
					$days = $quoteDetials[0]['final_mission_length']+$prod_extra_days;
				else
					$days = ceil($quoteDetials[0]['final_mission_length']/24)+$prod_extra_days; // needs to be done
					
				$date2 = date('Y-m-d',strtotime($date1."+ $days days"));
				$turnover = $quoteDetials[0]['turnover'];
				
				$splitvalues = $this->getSplitValues($date1, $date2,$turnover,$days,"");
				$this->_view->months = $splitvalues['months'];
				$this->_view->turnovers = $splitvalues['turnover'];
				$this->_view->expected_end_date = $date2;
				
				//Fetching Invoices
				$invoice_obj = new Ep_Quote_Invoice();
				$search = array();
				$search['contract_id'] = $requestparmas['contract_id'];
				$search['final_invoice'] = 0;
				$invoices = $invoice_obj->getInvoices($search);
				$i = 0;
				foreach($invoices as $invoice):
					if($invoice['product']=='translation')
					$invoices[$i]['title'] = $this->product_array[$invoice['product']]." ".$this->producttype_array[$invoice['product_type']]." ".$this->getCustomName("EP_LANGUAGES",$invoice['language_source'])." au ".$this->getCustomName("EP_LANGUAGES",$invoice['language_dest']);
					else
					$invoices[$i]['title'] = $this->product_array[$invoice['product']]." ".$this->producttype_array[$invoice['product_type']]." in ".$this->getCustomName("EP_LANGUAGES",$invoice['language_source']);
					
                                        if($invoice['product_type']=="autre")
                                        {
                                            $invoices[$i]['title'] .= " ".$invoices[$i]['product_type_other'];
                                        }
                                        $i++;
				endforeach;
				$search['final_invoice'] = 1;
				$finalinvoices = $invoice_obj->getInvoices($search);
				$this->_view->invoices = $invoices;
				$this->_view->finalinvoices = $finalinvoices;
			
				//$this->render('edit-create-quote-contract-sales');
				$this->render('edit-contract');
			}
			else
			$this->_redirect("/contractmission/contract-list?submenuId=ML13-SL3");
		}
		else
		$this->_redirect("/contractmission/contract-list?submenuId=ML13-SL3");
	}
	/* To validate or update the contract */ 
	function contractEditAction()
	{
		$requestparmas = $this->_request->getParams();
		
		if($requestparmas['contract_id'])
		{
			$quote_contract = new Ep_Quote_Quotecontract();
			$contract = $quote_contract->getContract($requestparmas['contract_id']);
			//echo "<prE>";print_r($contract);exit;
			
			if($contract)
			{
				$quote_obj = new Ep_Quote_Quotes();
				$quoteDetials = $quote_obj->getQuoteDetails($contract[0]['quoteid']);
				$client_obj = new Ep_Quote_Client();		
				$client_details = $client_obj->getClientDetails($quoteDetials[0]['client_id']);

				$salesDetails = $client_obj->getQuoteUserDetails($contract[0]['sales_creator_id']);
				$contract[0]['sales_owner'] = $salesDetails[0]['first_name']." ".$salesDetails[0]['last_name'];
				$contract[0]['mailto'] = $salesDetails[0]['email'];

				$related_files='';
				if($contract[0]['contractfilepaths'])
				{


					$related_files = $this->getContractFiles(array('contractfilepaths'=>$contract[0]['contractfilepaths'],'contractcustomfilenames'=>$contract[0]['contractcustomfilenames'],'contract_id'=>$requestparmas['contract_id']));
				}
				$this->_view->contractDetials = $contract[0];				
				$this->_view->related_files = $related_files;
				
				if($client_details!="NO")
				{
					$client_details[0]['category'] = $this->getCustomName("EP_ARTICLE_CATEGORY",$client_details[0]['category']);
					$this->_view->client_info = $client_details[0];
				}
				else
				$this->_view->client_info = "Client info unavailable";
				
				$searchParameters['quote_id']=$contract[0]['quoteid'];
				$searchParameters['misson_user_type_prod_seo']='sales OR seo';
				$searchParameters['product_type_seo']='NOT IN';
				$searchParameters['include_final']='yes';
				$searchParameters['contract_id']=$requestparmas['contract_id'];
				$quoteMission_obj=new Ep_Quote_QuoteMissions();
				$missionDetails=$quoteMission_obj->getMissionDetailsContract($searchParameters);
				/* Fetching mission details */
				if($missionDetails)
				{
					$i = 0;
					for($i=0;$i<count($missionDetails);$i++)
					{
						$missionDetails[$i]['product_type_converted'] = $this->product_array[$missionDetails[$i]['product']];
						$missionDetails[$i]['tempo_length_option_convert'] = $this->duration_array[$missionDetails[$i]['tempo_length_option']];
						$missionDetails[$i]['language_source_converted'] = $this->getCustomName("EP_LANGUAGES",$missionDetails[$i]['language_source']);
						$missionDetails[$i]['product_type_name']=$this->producttype_array[$missionDetails[$i]['product_type']];
						if($missionDetails[$i]['language_dest'] && $missionDetails[$i]['product']=="translation")
						$missionDetails[$i]['language_dest_converted'] = $this->getCustomName("EP_LANGUAGES",$missionDetails[$i]['language_dest']);
						if($missionDetails[$i]['package']=='team')
						{
							$missionDetails[$i]['turnover'] +=  $missionDetails[$i]['team_fee'] * $missionDetails[$i]['team_packs'];
							if($missionDetails[$i]['cm_turnover'])
							{
								$missionDetails[$i]['cm_turnover'] +=  $missionDetails[$i]['team_fee'] * $missionDetails[$i]['team_packs']; 
							}
						}
                        /*added by naseer on 17-11-2015*/
                        if( strtotime($missionDetails[$i]['freeze_start_date']) < time() && strtotime($missionDetails[$i]['freeze_end_date']) > time() ){
                            $missionDetails[$i]['freeze'] = 'yes';
                            $missionDetails[$i]['freeze_end_date'] = date('d-m-Y',strtotime($missionDetails[$i]['freeze_end_date']) );
                        }
                        if( strlen($missionDetails[$i]['edited_by']) > 5  ){
                            $missionDetails[$i]['edited'] = 'yes';
                        }
					}
				}	
				
				$this->_view->missiondetails = $missionDetails;
				$quoteDetials[0]['category_name'] = $this->getCustomName("EP_ARTICLE_CATEGORY",$quoteDetials[0]['category']);
				
				$this->_view->quotedetails = $quoteDetials[0];
				$this->_view->csarray = array(''=>'Select','Edit Place'=>'Edit Place','Client'=>'Client');
				$this->_view->typeofpayment = array(''=>'Select','factor'=>'Factor','daily'=>'Dailly','direct'=>'Direct','others'=>'Others');
				
				$date1 = $contract[0]['expected_launch_date'];
				/* Add prod extra days if any from Quote */
				if($quoteDetials[0]['prod_extra_info']=='yes')
				$prod_extra_days = $quoteDetials[0]['prod_extra_launch_days'];
				else
				$prod_extra_days = 0;
				if($quoteDetials[0]['final_mission_length_option']=='days')
					$days = $quoteDetials[0]['final_mission_length']+$prod_extra_days;
				else
					$days = ceil($quoteDetials[0]['final_mission_length']/24)+$prod_extra_days; 
					
				$date2 = date('Y-m-d',strtotime($date1."+ $days days"));
				$turnover = $quoteDetials[0]['turnover'];
				
				$splitvalues = $this->getSplitValues($date1, $date2,$turnover,$days,"");
				$this->_view->months = $splitvalues['months'];
				$this->_view->turnovers = $splitvalues['turnover'];
				$date2 =  $contract[0]['expected_end_date'];
				$this->_view->expected_end_date = $date2;
				
				//Fetching Invoices from ContractMissionInvoice table  generated by cron based on mission, delivery and month
				$invoice_obj = new Ep_Quote_Invoice();
				$search = array();
				$search['contract_id'] = $requestparmas['contract_id'];
				$search['final_invoice'] = 0;
				$invoices = $invoice_obj->getInvoices($search);
				$i = 0;
				foreach($invoices as $invoice):
					if($invoice['product']=='translation')
					$invoices[$i]['title'] = $this->product_array[$invoice['product']]." ".$this->producttype_array[$invoice['product_type']]." ".$this->getCustomName("EP_LANGUAGES",$invoice['language_source'])." au ".$this->getCustomName("EP_LANGUAGES",$invoice['language_dest']);
					else
					$invoices[$i]['title'] = $this->product_array[$invoice['product']]." ".$this->producttype_array[$invoice['product_type']]." in ".$this->getCustomName("EP_LANGUAGES",$invoice['language_source']);
					
                                         if($invoice['product_type']=="autre")
                                        {
                                            $invoices[$i]['title'] .= " ".$invoices[$i]['product_type_other'];
                                        }
                                        $i++;
				endforeach;
				/* Untreated invoices */
				$this->_view->invoices = $invoices;
				/* Treated invoices */
				$search['final_invoice'] = 1;
				$finalinvoices = $invoice_obj->getInvoices($search);
				$this->_view->finalinvoices = $finalinvoices;
			
				//$this->render('edit-create-quote-contract-sales');
				$this->render('edit-contract');
			}
			else
			$this->_redirect("/contractmission/contract-list?submenuId=ML13-SL3");
		}
		else
		$this->_redirect("/contractmission/contract-list?submenuId=ML13-SL3");
	}
	// Deprecated not in use
	
	function saveFinanceEditAction()
	{
		if($this->_request-> isPost())            
        {  
			$params = $this->_request->getParams();
			if($params['contract_id']):
				$quote_contract=new Ep_Quote_Quotecontract();
				$this->uploadFiles($_FILES,$params['contract_id'],$quote_contract,$params['document_name']);
				$save = array();
				$active = "";
				$save['finance_validator_id'] = $this->_view->loginuserId;
				if($params['validate']=="validate")
				{
					$save['status'] = "validated";
					$log_obj=new Ep_Quote_QuotesLog();					
					$actionmessage = $log_obj->getActionSentence(11);
					$client_obj = new Ep_Quote_Client();
					$bo_user_details=$client_obj->getQuoteUserDetails($this->adminLogin->userId);
					if($bo_user_details!='NO')
						$finance_user="<strong>".$bo_user_details[0]['first_name'].' '.$bo_user_details[0]['last_name']."</strong>";
					else
						$finance_user="";
						
					$launch_date = date('d/m/Y',strtotime($save['expected_launch_date']));
					$actionmessage=strip_tags($actionmessage);
					eval("\$actionmessage= \"$actionmessage\";");
				
					$log_array['user_id']=$this->adminLogin->userId;
					$log_array['contract_id']=$params['contract_id'];
					$log_array['user_type']=$this->adminLogin->type;
					$log_array['action']='contract_validated';
					$log_array['action_at']=date("Y-m-d H:i:s");
					$log_array['action_sentence']=$actionmessage;
					
					$log_obj->insertLogs($log_array);
				}
				else
					$active = 'validate';
				$save['type_of_payment'] = $params['paymenttype'];
				$save['sourceofcontract'] = $params['contractsource'];
				$quote_contract->updateContract($save,$params['contract_id']);
			endif;
		}
		$this->_redirect("/contractmission/contract-list??submenuId=ML13-SL3&active=".$active);
	}	
	/* To download the document from external file based on type like seo, prod and tech */
	function downloadDocumentAction()
	{
		$request = $this->_request->getParams();
		$this->_redirect("/BO/download-quote.php?type=".$request['type']."&index=".$request['index']."&contract_id=".$request['contract_id']);
	}
	
	function downloadInvoiceAction()
	{
		$request = $this->_request->getParams();
		$this->_redirect("/BO/download-invoice.php?fname=".$request['fname']."&cid=".$request['cid']."&final=".$request['final']);
	}
	/* To delete a document through Ajax */
	function deleteDocumentAction()
	{
		if($this->_request->isPost() && $_SERVER['HTTP_X_REQUESTED_WITH']=='XMLHttpRequest')
		{
			$parmas = $this->_request->getParams();
			$explode_identifier = explode("_",$parmas['identifier']);
			$offset = $explode_identifier[0];
			$identifier = $explode_identifier[1];
			$quote_contract=new Ep_Quote_Quotecontract();
			$result = $quote_contract->getContract($identifier);
			$documents_paths = explode("|",$result[0]['contractfilepaths']);
			$documents_names = explode("|",$result[0]['contractcustomfilenames']);

			unlink($this->contract_documents_path.$documents_paths[$offset]);

			unset($documents_paths[$offset]);
			unset($documents_names[$offset]);

			$data['contractfilepaths']	= implode("|",$documents_paths);
			$data['contractcustomfilenames']	= implode("|",$documents_names);
			$quote_contract->updateContract($data,$identifier);

			$documents_path = array_values($documents_paths);
			$documents_name = array_values($documents_names);


			$files = "";

			$k=0;
					
			$files = '<table class="table">';
			foreach($documents_path as $k=>$file)
			{
				$file_path = $this->contract_documents_path.$documents_path[$k];
				if(file_exists($this->contract_documents_path.$documents_path[$k]) && !is_dir($this->contract_documents_path.$documents_path[$k]))
				{
					$zip = true;
					if($documents_name[$k])
						$file_name=$documents_name[$k];
					else
						$file_name=basename($file);
					$ofilename = pathinfo($file_path);
					$files .= '<tr><td width="30%">'.$file_name.'</td><td width="35%">'.substr($ofilename['filename'],0,-3).".".$ofilename['extension'].'</td><td width="20%">'.formatSizeUnits(filesize($file_path)).'</td><td>Contract</td><td align="center" width="15%"><a href="/contractmission/download-document?type=contract&contract_id='.$identifier.'&index='.$k.'"><i style="margin-right:5px" class="splashy-download"></i></a><span class="delete" rel="'.$k.'_'.$identifier.'"> <i class="icon-adt_trash"></i></span></td></tr>';	
					
				}
			}
			if($zip)
				$files .= '<thead><tr><td colspan="5"><a href="/contractmission/download-document?type=contract&index=-1&contract_id='.$identifier.'" class="btn btn-small pull-right">Download Zip</a></td></tr></thead>';
			$files .='</table>';
			echo $files;
		}
	}
	/* To get the mission details thorugh Ajax */
	function missionDetailsAction()
	{
		$params = $this->_request->getParams();
		$quote_id = $params['quote_id'];
		if($quote_id)
		{
			$searchParameters = array();
			$searchParameters['quote_id']=$quote_id;
			$searchParameters['misson_user_type']='sales';
			$quoteMission_obj=new Ep_Quote_QuoteMissions();
			$missionDetails=$quoteMission_obj->getMissionDetails($searchParameters);	
			if($missionDetails)
			{
				$i = 0;
				for($i=0;$i<count($missionDetails);$i++)
				{
					//$missonDetails[$i]['product_name']=$this->product_array[$mission['product']];	
					$missionDetails[$i]['product_type_converted'] = $this->product_array[$missionDetails[$i]['product']];
					$missionDetails[$i]['language_source_name'] = $this->getCustomName("EP_LANGUAGES",$missionDetails[$i]['language_source']);
					$missionDetails[$i]['product_type_name']=$this->producttype_array[$missionDetails[$i]['product_type']];
					if($missionDetails[$i]['language_dest'] && $missionDetails[$i]['product']=="translation")
					$missionDetails[$i]['language_dest_name'] = $this->getCustomName("EP_LANGUAGES",$missionDetails[$i]['language_dest']);
				}
			}	
			
			$this->_view->mission_details = $missionDetails;
		}
		$this->render('all-missiondetils');
	}
	/* To assign a respective missions like seo,prod,tech by respective managers or by superadmin to the respective users like seouser, produser, techuser */
	function assignMissionAction()
	{
		$params = $this->_request->getParams();
		if($params['contract_id'] && $this->_view->loginuserId):
		
			$quote_contract=new Ep_Quote_Quotecontract();
			$contract_details = $quote_contract->getContract($params['contract_id']);
			if($contract_details)
			{
				$user_obj=new Ep_User_User();
				/* Fetching sales user info who created the contract */
				$user_info = $user_obj->getAllUsersDetails($contract_details[0]['sales_creator_id']);
				
				if($user_info)
				$contract_details[0]['created_name'] = $user_info[0]['first_name']." ".$user_info[0]['last_name'];
				else
				$contract_details[0]['created_name'] = "";
				$contract_details[0]['created_time'] = time_ago($contract_details[0]['created_at']);
				
				$this->_view->contract_details = $contract_details[0];
			
				$this->_view->writing = 0;
				$this->_view->proofreading = 0;
				$this->_view->autre = 0;
				/** Fetching Quote Details **/
				$quote_id = $contract_details[0]['quoteid'];
				$quote_obj = new Ep_Quote_Quotes();
				$quote_details = $quote_obj->getQuoteDetails($contract_details[0]['quoteid']);
				
				//conversion
				$quote_details[0]['conversion']=currencyToDecimal(zero_cut($quote_details[0]['conversion'],4));
				
				$user_info = $user_obj->getAllUsersDetails($quote_details[0]['created_by']);
				
				if($user_info)
				$quote_details[0]['created_name'] = $user_info[0]['first_name']." ".$user_info[0]['last_name'];
				else
				$quote_details[0]['created_name'] = "";
				$quote_details[0]['created_time'] = time_ago($quote_details[0]['created_at']);
				$user_info = $user_obj->getAllUsersDetails($this->_view->loginuserId);
				
				if($user_info)
				$this->_view->flname = $user_info[0]['first_name']." ".$user_info[0]['last_name'];
				else
				$this->_view->flname = "";
				
				$quote_details[0]['category_name'] = $this->getCustomName("EP_ARTICLE_CATEGORY",$quote_details[0]['category']);
				$this->_view->quote_details = $quote_details[0];
				
				/** Fetching Client Details **/
				$client_obj = new Ep_Quote_Client();		
				$client_details = $client_obj->getClientDetails($quote_details[0]['client_id']);
				if($client_details!="NO")
				{
					$client_details[0]['category'] = $this->getCustomName("EP_ARTICLE_CATEGORY",$client_details[0]['category']);
					$websites = explode("|",$client_details[0]['website']);
					if($websites===false)
					$this->_view->websites = array();
					else
					$this->_view->websites = $websites;
					$this->_view->client_info = $client_details[0];
				}
				else
				$this->_view->client_info = "Client info unavailable";
				
				/* Comments from quote and contract */
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
				if($contract_details[0]['comment'])
				{
					$comments['created_by'] = $contract_details[0]['sales_creator_id'];
					$comments['created_name'] = $contract_details[0]['created_name'];
					$comments['created_time'] = $contract_details[0]['created_time'];
					$comments['comment'] = $contract_details[0]['comment'];
					$comments['created_at'] = $contract_details[0]['created_at'];
					$comment[] = $comments;
				}
				
				/** Assigning Missions **/
				$this->_view->contract_id = $contract_id =  $params['contract_id'];
				$this->_view->submit = true;
				$this->_view->count_text = "";
				/* Fetching contract mission details */
				if($params['cmid'])
				{
					$cmdetails = $quote_contract->getContractDetails(array('contractmissionid'=>$params['cmid']));
				}
				else
					$cmdetails = '';
				/* Tech mission assignment */
				if($params['type']=='tech' && ($this->_view->user_type=='techmanager' || $this->_view->user_type=='superadmin'))
				{
					$searchParameters = array();
					$searchParameters['quote_id']=$quote_id;
					$searchParameters['misson_user_type']='sales';
					$quoteMission_obj=new Ep_Quote_QuoteMissions();
					$missionDetails=$quoteMission_obj->getMissionDetails($searchParameters);	
					if($missionDetails)
					{
						$i = 0;
						for($i=0;$i<count($missionDetails);$i++)
						{
							//$missonDetails[$i]['product_name']=$this->product_array[$mission['product']];	
							$missionDetails[$i]['product_type_converted'] = $this->product_array[$missionDetails[$i]['product']];
							$missionDetails[$i]['language_source_name'] = $this->getCustomName("EP_LANGUAGES",$missionDetails[$i]['language_source']);
							$missionDetails[$i]['product_type_name']=$this->producttype_array[$missionDetails[$i]['product_type']];
							if($missionDetails[$i]['language_dest'] && $missionDetails[$i]['product']=="translation")
							$missionDetails[$i]['language_dest_name'] = $this->getCustomName("EP_LANGUAGES",$missionDetails[$i]['language_dest']);
							
							$bo_user_details=$client_obj->getQuoteUserDetails($missionDetails[$i]['created_by']);
							if($bo_user_details!='NO')
								$missionDetails[$i]['quote_user_name']=$bo_user_details[0]['first_name'].' '.$bo_user_details[0]['last_name'];
							else
								$missionDetails[$i]['quote_user_name']="";
						}
					}	
					
					$this->_view->mission_details = $missionDetails;
					/* check and assign index(no. of mission in the techmission list) */
					
					if(empty($params['index']))
					$index = 0;
					else
					$index = $params['index'];
					
					$tech_obj=new Ep_Quote_TechMissions();
					$searchParameters['quote_id']=$quote_id;
					$searchParameters['include_final']='yes';
					$techMissionDetails=$quote_contract->getTechMissionDetails($searchParameters);
					$this->_view->max_count = $this->_view->total_tech_mission_count = count($techMissionDetails);
					if($cmdetails)
					{
						$searchParameters['identifier'] = $cmdetails[0]['type_id'];
						$techMissionDetails=$quote_contract->getTechMissionDetails($searchParameters);
					}
					$this->_view->current_index = $index;
					/* To check mission is assigned or not and getting uploaded files */
					if(($index < $this->_view->total_tech_mission_count) || $params['cmid'])
					{
						if($techMissionDetails[$index]['created_by'])
						$user_info = $user_obj->getAllUsersDetails($techMissionDetails[$index]['created_by']);
						
						if($user_info)
						$techMissionDetails[$index]['created_name'] = $user_info[0]['first_name']." ".$user_info[0]['last_name'];
						else
						$techMissionDetails[$index]['created_name'] = "";
						
						$techMissionDetails[$index]['created_time'] = time_ago($techMissionDetails[$index]['created_at']);
						$this->_view->techmissiondetails = $techMissionDetails[$index];
						
						$this->_view->seotechprodid = $techMissionDetails[$index]['identifier'];
						$this->_view->type = 'tech';
						
						$this->_view->users = $quote_contract->getUsers('techuser');
						
						$totalcountmissionsassigned = $quote_contract->getCountContractMission($params['contract_id'],'tech');
						
						$remaining_missions = count($techMissionDetails) - $totalcountmissionsassigned;
						if($remaining_missions)
						$this->_view->count_text = "You have $remaining_missions missions to assign";
						
						
						$contractMissionDetails = $quote_contract->getContractMission($params['contract_id'],'tech',$techMissionDetails[$index]['identifier']);
						
						$this->_view->contractDetails = false;
						
						if($contractMissionDetails):
							$user_info = $user_obj->getAllUsersDetails($contractMissionDetails[0]['updated_by']);
						
							if($user_info)
							$contractMissionDetails[0]['created_name'] = $user_info[0]['first_name']." ".$user_info[0]['last_name'];
							else
							$contractMissionDetails[0]['created_name'] = "";
							
							$contractMissionDetails[0]['created_time'] = time_ago($contractMissionDetails[0]['updated_at']);
							$this->_view->contractMissionDetails = $contractMissionDetails[0];
							$this->_view->contractDetails = true;
							
						endif;
						
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
						if($techMissionDetails[$index]['documents_path'])
						{
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
									$files .= '<tr><td width="30%">'.$fname.'</td><td width="35%">'.substr($ofilename['filename'],0,-3).".".$ofilename['extension'].'</td><td width="20%">'.formatSizeUnits(filesize($file_path)).'</td><td>Tech</td><td align="center" width="15%"><a href="/quote/download-document?type=tech_mission&mission_id='.$techMissionDetails[$index]['identifier'].'&index='.$k.'"><i style="margin-right:5px" class="splashy-download"></i></a><span class="deletetech" rel="'.$k.'_'.$techMissionDetails[$index]['identifier'].'"> <i class="icon-adt_trash"></i></span><td></tr>';	
								}
								$k++;
							}
						}
						$this->_view->files = $files;
						
						if($techMissionDetails[$index]['comments'])
						{
							$comments['created_by'] = $techMissionDetails[$index]['created_by'];
							$comments['created_name'] = $techMissionDetails[$index]['created_name'];
							$comments['created_time'] = $techMissionDetails[$index]['created_time'];
							$comments['comment'] = $techMissionDetails[$index]['comments'];
							$comments['created_at'] = $techMissionDetails[$index]['created_at'];
							$comment[] = $comments;
						}
						$this->_view->comments = $this->sortTimewise($comment);
					}
					else
					$this->_redirect("/contractmission/contract-list?submenuId=ML13-SL3");
					
				}/* Staff mission assignment */
				elseif($params['type']=='staff' && ($this->_view->user_type=='prodmanager' || $this->_view->user_type=='superadmin'))
				{
					$searchParameters = array();
					$searchParameters['quote_id']=$quote_id;
					$searchParameters['misson_user_type']='sales';
					$quoteMission_obj=new Ep_Quote_QuoteMissions();
					$missionDetails=$quoteMission_obj->getMissionDetails($searchParameters);	
					/* Fetching missions to display */
					if($missionDetails)
					{
						$i = 0;
						for($i=0;$i<count($missionDetails);$i++)
						{
							//$missonDetails[$i]['product_name']=$this->product_array[$mission['product']];	
							$missionDetails[$i]['product_type_converted'] = $this->product_array[$missionDetails[$i]['product']];
							$missionDetails[$i]['language_source_name'] = $this->getCustomName("EP_LANGUAGES",$missionDetails[$i]['language_source']);
							$missionDetails[$i]['product_type_name']=$this->producttype_array[$missionDetails[$i]['product_type']];
							if($missionDetails[$i]['language_dest'])
							$missionDetails[$i]['language_dest_name'] = $this->getCustomName("EP_LANGUAGES",$missionDetails[$i]['language_dest']);
							$bo_user_details=$client_obj->getQuoteUserDetails($missionDetails[$i]['created_by']);
							if($bo_user_details!='NO')
								$missionDetails[$i]['quote_user_name']=$bo_user_details[0]['first_name'].' '.$bo_user_details[0]['last_name'];
							else
								$missionDetails[$i]['quote_user_name']="";
						}
					}	
					$this->_view->mission_details = $missionDetails;
					/* check index(no. of a staff mission in list) */
					if(empty($params['index']))
					$index = 0;
					else
					$index = $params['index'];
					$staffMissionDetails=$quote_contract->getStaffMissionDetails(array('contract_id'=>$contract_id));
					$this->_view->max_count = $this->_view->total_staff_mission_count = count($staffMissionDetails);
					if($cmdetails)
					{
						/* $searchParameters['identifier'] = $cmdetails[0]['type_id'];
						$techMissionDetails=$quote_contract->getTechMissionDetails($searchParameters); */
					}
					$this->_view->current_index = $index;
					/* check if assigned or not and fetching related files */
					if(($index < $this->_view->total_staff_mission_count) || $params['cmid'])
					{
						$user_info = $user_obj->getAllUsersDetails($staffMissionDetails[$index]['created_by']);
						if($user_info)
						$staffMissionDetails[$index]['created_name'] = $user_info[0]['first_name']." ".$user_info[0]['last_name'];
						else
						$staffMissionDetails[$index]['created_name'] = "";
						$staffMissionDetails[$index]['created_time'] = time_ago($staffMissionDetails[$index]['created_at']);
						$this->_view->staffMissionDetails = $staffMissionDetails[$index];
						$this->_view->seotechprodid = $staffMissionDetails[$index]['staff_missionId'];
						$this->_view->type = 'staff';
						$this->_view->users = $quote_contract->getUsers("'prodsubmanager' OR type='multilingue'",'',true);
						$totalcountmissionsassigned = $quote_contract->getCountContractMission($params['contract_id'],'staff');
						$remaining_missions = count($staffMissionDetails) - $totalcountmissionsassigned;
						if($remaining_missions)
						$this->_view->count_text = "Vous avez $remaining_missions missions a assigner";
						$contractMissionDetails = $quote_contract->getContractMission($params['contract_id'],'staff',$staffMissionDetails[$index]['staff_missionId']);
						$this->_view->contractDetails = false;
						if($contractMissionDetails):
							$user_info = $user_obj->getAllUsersDetails($contractMissionDetails[0]['updated_by']);
							if($user_info)
							$contractMissionDetails[0]['created_name'] = $user_info[0]['first_name']." ".$user_info[0]['last_name'];
							else
							$contractMissionDetails[0]['created_name'] = "";
							$contractMissionDetails[0]['created_time'] = time_ago($contractMissionDetails[0]['updated_at']);
							$this->_view->contractMissionDetails = $contractMissionDetails[0];
							$this->_view->contractDetails = true;
						endif;
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
						if($staffMissionDetails[$index]['documents_path'])
						{
							$exploded_file_paths = explode("|",$staffMissionDetails[$index]['documents_path']);
							$exploded_file_names = explode("|",$staffMissionDetails[$index]['documents_name']);
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
									$files .= '<tr><td width="30%">'.$fname.'</td><td width="35%">'.substr($ofilename['filename'],0,-3).".".$ofilename['extension'].'</td><td width="20%">'.formatSizeUnits(filesize($file_path)).'</td><td>Staff</td><td align="center" width="15%"><a href="/quote/download-document?type=staff_mission&mission_id='.$staffMissionDetails[$index]['staff_missionId'].'&index='.$k.'"><i style="margin-right:5px" class="splashy-download"></i></a><span class="deletestaff" rel="'.$k.'_'.$staffMissionDetails[$index]['staff_missionId'].'"> <i class="icon-adt_trash"></i></span><td></tr>';	
								}
								$k++;
							}
						}
						$this->_view->files = $files;
						if($staffMissionDetails[$index]['comments'])
						{
							$comments['created_by'] = $staffMissionDetails[$index]['created_by'];
							$comments['created_name'] = $staffMissionDetails[$index]['created_name'];
							$comments['created_time'] = $staffMissionDetails[$index]['created_time'];
							$comments['comment'] = $staffMissionDetails[$index]['comments'];
							$comments['created_at'] = $staffMissionDetails[$index]['created_at'];
							$comment[] = $comments;
						}
						
						$this->_view->comments = $this->sortTimewise($comment);
					}
					else
					$this->_redirect("/contractmission/contract-list?submenuId=ML13-SL3");
					
				}/* seo mission assignment */
				elseif($params['type']=='seo' && ($this->_view->user_type=='seomanager' || $this->_view->user_type=='superadmin'))
				{
					
					$searchParameters = array();
					$searchParameters['quote_id']=$quote_id;
					$searchParameters['misson_user_type']='sales';
					$quoteMission_obj=new Ep_Quote_QuoteMissions();
					$missionDetails=$quoteMission_obj->getMissionDetails($searchParameters);	
					if($missionDetails)
					{
						$i = 0;
						for($i=0;$i<count($missionDetails);$i++)
						{
							//$missonDetails[$i]['product_name']=$this->product_array[$mission['product']];	
							$missionDetails[$i]['product_type_converted'] = $this->product_array[$missionDetails[$i]['product']];
							$missionDetails[$i]['language_source_name'] = $this->getCustomName("EP_LANGUAGES",$missionDetails[$i]['language_source']);
							$missionDetails[$i]['product_type_name']=$this->producttype_array[$missionDetails[$i]['product_type']];
							if($missionDetails[$i]['language_dest'] && $missionDetails[$i]['product']=="translation")
							$missionDetails[$i]['language_dest_name'] = $this->getCustomName("EP_LANGUAGES",$missionDetails[$i]['language_dest']);
							
							$bo_user_details=$client_obj->getQuoteUserDetails($missionDetails[$i]['created_by']);
							if($bo_user_details!='NO')
								$missionDetails[$i]['quote_user_name']=$bo_user_details[0]['first_name'].' '.$bo_user_details[0]['last_name'];
							else
								$missionDetails[$i]['quote_user_name']="";
						}
					}	
					
					$this->_view->mission_details = $missionDetails;
					
					if(empty($params['index']))
					$index = 0;
					else
					$index = $params['index'];	
					
					$seomission_obj=new Ep_Quote_QuoteMissions();
					$searchParameters['quote_id']=$quote_id;
					$searchParameters['misson_user_type']='seo';
					$searchParameters['include_final']='yes';
					$searchParameters['product_type_seo']='IN';
					$seoMissionDetails = $seomission_obj->getMissionDetails($searchParameters);
					$this->_view->max_count = $this->_view->total_seo_mission_count = count($seoMissionDetails);
					if($cmdetails)
					{
						$searchParameters['mission_id']=$cmdetails[0]['type_id'];
						$seoMissionDetails = $seomission_obj->getMissionDetails($searchParameters);
					}
					$this->_view->current_index = $index;
					
					if(($index < $this->_view->total_seo_mission_count) || $params['cmid'])
					{
						$seoMissionDetails[$index]['productc'] = $this->product_array[$seoMissionDetails[$index]['product']];
						$seoMissionDetails[$index]['typec'] = $this->producttype_array[$seoMissionDetails[$index]['product_type']];
						$seoMissionDetails[$index]['language_source_converted'] = $this->getCustomName("EP_LANGUAGES",$seoMissionDetails[$index]['language_source']);
						if($seoMissionDetails[$index]['language_dest'])
						$seoMissionDetails[$index]['language_dest_converted'] = $this->getCustomName("EP_LANGUAGES",$seoMissionDetails[$index]['language_dest']);
						
						$user_info = $user_obj->getAllUsersDetails($seoMissionDetails[$index]['created_by']);
						
						if($user_info)
						$seoMissionDetails[$index]['created_name'] = $user_info[0]['first_name']." ".$user_info[0]['last_name'];
						else
						$seoMissionDetails[$index]['created_name'] = "";
						
						$seoMissionDetails[$index]['created_time'] = time_ago($seoMissionDetails[$index]['created_at']);
						$this->_view->seoMissionDetails = $seoMissionDetails[$index];
						$this->_view->seotechprodid = $seoMissionDetails[$index]['identifier'];
						$this->_view->type = 'seo';
						
						$this->_view->users = $quote_contract->getUsers('seouser');
						
						$contractMissionDetails = $quote_contract->getContractMission($params['contract_id'],'seo',$seoMissionDetails[$index]['identifier']);
						
						$totalcountmissionsassigned = $quote_contract->getCountContractMission($params['contract_id'],'seo');
						
						$remaining_missions = $this->_view->total_seo_mission_count - $totalcountmissionsassigned;
						
						if($remaining_missions)
						$this->_view->count_text = "You have $remaining_missions missions to assign";
						
						$this->_view->contractDetails = false;
						
						if($contractMissionDetails):
							$user_info = $user_obj->getAllUsersDetails($contractMissionDetails[0]['updated_by']);
						
							if($user_info)
							$contractMissionDetails[0]['created_name'] = $user_info[0]['first_name']." ".$user_info[0]['last_name'];
							else
							$contractMissionDetails[0]['created_name'] = "";
							
							$contractMissionDetails[0]['created_time'] = time_ago($contractMissionDetails[0]['updated_at']);
							$this->_view->contractMissionDetails = $contractMissionDetails[0];
							$this->_view->contractDetails = true;
							
						endif;
						
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
						
						$this->_view->files = "";
						
						if($seoMissionDetails[$index]['documents_path'])
						{
							$exploded_file_paths = explode("|",$seoMissionDetails[$index]['documents_path']);
							$exploded_file_names = explode("|",$seoMissionDetails[$index]['documents_name']);
							$files = "";
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
									$files .= '<tr><td width="30%">'.$fname.'</td><td width="35%">'.substr($ofilename['filename'],0,-3).".".$ofilename['extension'].'</td><td width="20%">'.formatSizeUnits(filesize($file_path)).'</td><td>SEO</td><td align="center" width="15%"><a href="/quote/download-document?type=seo_mission&mission_id='.$seoMissionDetails[$index]['identifier'].'&index='.$k.'"><i style="margin-right:5px" class="splashy-download"></i></a><span class="delete" rel="'.$k.'_'.$seoMissionDetails[$index]['identifier'].'"> <i class="icon-adt_trash"></i></span><td></tr>';	
								}
								$k++;
							}
							$this->_view->files = $files;
						}
						
						if($seoMissionDetails[$index]['comments'])
						{
							$comments['created_by'] = $seoMissionDetails[$index]['created_by'];
							$comments['created_name'] = $seoMissionDetails[$index]['created_name'];
							$comments['created_time'] = $seoMissionDetails[$index]['created_time'];
							$comments['comment'] = $seoMissionDetails[$index]['comments'];
							$comments['created_at'] = $seoMissionDetails[$index]['created_at'];
							$comment[] = $comments;
						}
						
						$this->_view->comments = $this->sortTimewise($comment);
					}
					else
					$this->_redirect("/contractmission/contract-list?submenuId=ML13-SL3");
				}
				elseif($params['type']=='prod' && ($this->_view->user_type=='prodmanager' || $this->_view->user_type=='superadmin'))
				{
					if(empty($params['index']))
					$index = 0;
					else
					$index = $params['index'];	
					$searchParameters = array();
					$searchParameters['quote_id']=$quote_id;
					//$searchParameters['misson_user_type']='sales';
					$searchParameters['misson_user_type_prod_seo']='sales OR seo';
					$searchParameters['product_type_seo']='NOT IN';
					$searchParameters['include_final']='yes';
					//$searchParameters['mission_id']= $cmdetails[0]['type_id'];
					$quoteMission_obj=new Ep_Quote_QuoteMissions();
					$missionDetails=$quoteMission_obj->getMissionDetails($searchParameters);	
					$this->_view->max_count = $this->_view->total_prod_mission_count = count($missionDetails);
					/* if($cmdetails)
					{
						$searchParameters['mission_id']= $cmdetails[0]['type_id'];
						$missionDetails=$quoteMission_obj->getMissionDetails($searchParameters);
					} */
					$this->_view->current_index = $index;
					$this->_view->type = 'prod';
					
					if(($index < $this->_view->total_prod_mission_count) || $params['cmid'])
					{
						$missionDetails[$index]['product_name']=$this->product_array[$missionDetails[$index]['product']];			
						$missionDetails[$index]['language_source_name']=$this->getCustomName("EP_LANGUAGES",$missionDetails[$index]['language_source']);
						$missionDetails[$index]['product_type_name']=$this->producttype_array[$missionDetails[$index]['product_type']];
						if($missionDetails[$index]['language_dest'])
							$missionDetails[$index]['language_dest_name']=$this->getCustomName("EP_LANGUAGES",$missionDetails[$index]['language_dest']);
						$missionDetails[$index]['mission_title']= 'Mission '.($index+1).' - '.$missionDetails[$index]['product_name'];
						$missionDetails[$index]['comment_time']=time_ago($missionDetails[$index]['created_at']);
						$bo_user_details=$client_obj->getQuoteUserDetails($missionDetails[$index]['created_by']);
						$missionDetails[$index]['created_name']=$bo_user_details[0]['first_name'].' '.$bo_user_details[0]['last_name'];
						$this->_view->seotechprodid = $missionDetails[$index]['identifier'];
						$missionDetails[$index]['mission_length_option_convert'] = $this->duration_array[$missionDetails[$index]['mission_length_option']];
						$missionDetails[$index]['tempo_length_option_convert'] = $this->duration_array[$missionDetails[$index]['tempo_length_option']];
						$prodMissionObj=new Ep_Quote_ProdMissions();
						$search = array();
						$search['quote_mission_id']=$missionDetails[$index]['identifier'];
						
						$prodMissionDetails=$prodMissionObj->getProdMissionDetails($search);
						$missionDetails[$index]['prod_missions']= array();
						$max_cost = 0;
						$writing = 0;
						$proofreading = 0;
						$this->_view->onlysales = false;
						$this->_view->autre = 0;
						$this->_view->merge_writing_cost = "";
						$this->_view->merge_proofreading_cost = "";
						$this->_view->merge = "";
						if($prodMissionDetails)
						{
							/* calculate writing, prooffreading and other cost from Prodmissions table */
							foreach($prodMissionDetails as $key=>$details)
							{
								$client_obj=new Ep_Quote_Client();
								$bo_user_details=$client_obj->getQuoteUserDetails($details['created_by']);
								$prodMissionDetails[$key]['prod_user_name']=$bo_user_details[0]['first_name'].' '.$bo_user_details[0]['last_name'];

								$prodMissionDetails[$key]['comment_time']=time_ago($details['created_at']);
								$max_cost += $details['cost'];
								if($details['product']=='redaction')
								$writing += $details['cost'];
								if($details['product']=='translation')
								$writing += $details['cost'];
								if($details['product']=='proofreading')
								$proofreading = $details['cost'];
								if($details['product']=='autre')
								$this->_view->autre = $details['cost'];
							}

							$missionDetails[$index]['prod_missions']=$prodMissionDetails;	

						}
						else
						{
							$this->_view->onlysales = true;
							
							$archmission_obj=new Ep_Quote_Mission();
							$archParameters['mission_id']=$missionDetails[$index]['sales_suggested_missions'];
							$suggested_mission_details=$archmission_obj->getMissionDetails($archParameters);
							/* calculate writing, prooffreading and other cost from missions archive table */
							if($suggested_mission_details)
							{										
								$nb_words=($missionDetails[$index]['nb_words']/$suggested_mission_details[0]['article_length']);
								$writing=number_format($nb_words*($suggested_mission_details[0]['writing_cost_before_signature']),2,'.','');
								$proofreading=number_format($nb_words*($suggested_mission_details[0]['correction_cost_before_signature']),2,'.','');
								$this->_view->autre=number_format($nb_words*($suggested_mission_details[0]['other_cost_before_signature']),2,'.','');
								$max_cost = $writing+$proofreading+$this->_view->autre;
							}
							
						}
						
						$totalcountmissionsassigned = $quote_contract->getCountContractMission($params['contract_id'],'prod');
						
						$remaining_missions = $this->_view->total_prod_mission_count - $totalcountmissionsassigned;
						
						if($remaining_missions)
						$this->_view->count_text = "You have $remaining_missions missions to assign";
						
						
						$this->_view->writing = $writing;
						$this->_view->proofreading = $proofreading;
						$this->_view->prod_mission_details = $missionDetails[$index];
						//$this->_view->users = $quote_contract->getUsers('produser');
						$this->_view->users = $quote_contract->getUsers("'prodsubmanager' OR type='multilingue'",'',true);
						
						$contractMissionDetails = $quote_contract->getContractMission($params['contract_id'],'prod',$missionDetails[$index]['identifier']);
						
						$prodMissionObj=new Ep_Quote_ProdMissions();
						
						/* $prodmissions = $prodMissionObj->getProdCostDetails($missionDetails[$index]['identifier']);
						$max_cost = 0;
						foreach($prodmissions as $row):
							$max_cost += $row['cost'];
						endforeach; */
						
						$this->_view->contractDetails = false;
						$this->_view->min_cost = "";
						$this->_view->max_cost = $max_cost;
						$this->_view->pcorrection = "internal";
						$this->_view->files_pack = "";
						$this->_view->privatedelivery = "";
						$this->_view->launch_recuritement = "no";
						$this->_view->launch_survey = "no";
						$this->_view->stencils = "yes";
						$this->_view->cmcurrency = $quote_details[0]['sales_suggested_currency'];
						$this->_view->oneshots = $quote_contract->getOneshotTempos($params['contract_id'],$missionDetails[$index]['identifier']);
						if($contractMissionDetails):
							$user_info = $user_obj->getAllUsersDetails($contractMissionDetails[0]['updated_by']);
						
							if($user_info)
							$contractMissionDetails[0]['created_name'] = $user_info[0]['first_name']." ".$user_info[0]['last_name'];
							else
							$contractMissionDetails[0]['created_name'] = "";
							
							$contractMissionDetails[0]['created_time'] = time_ago($contractMissionDetails[0]['updated_at']);
							$this->_view->contractMissionDetails = $contractMissionDetails[0];
							$this->_view->contractDetails = true;
							$this->_view->min_cost = $contractMissionDetails[0]['min_cost'];
							$this->_view->max_cost = $contractMissionDetails[0]['max_cost'];
							$this->_view->pcorrection = $contractMissionDetails[0]['correction'];
							$this->_view->files_pack = $contractMissionDetails[0]['files_pack'];
							$this->_view->privatedelivery = $contractMissionDetails[0]['privatedelivery'];
							$this->_view->launch_recuritement = $contractMissionDetails[0]['is_recruitment'];
							$this->_view->launch_survey = $contractMissionDetails[0]['is_survey'];
							$this->_view->writing = $contractMissionDetails[0]['writing'];
							$this->_view->proofreading = $contractMissionDetails[0]['proofreading'];
							$this->_view->autre = $contractMissionDetails[0]['other'];
							$this->_view->merge_writing_cost = $contractMissionDetails[0]['merge_writing_cost'];
							$this->_view->merge_proofreading_cost = $contractMissionDetails[0]['merge_proofreading_cost'];
							$this->_view->merge = $contractMissionDetails[0]['merge'];
							$this->_view->stencils = $contractMissionDetails[0]['stencils_ebooker'];
							$this->_view->cmcurrency = $contractMissionDetails[0]['currency'];
							$this->_view->cmconversion =currencyToDecimal(zero_cut($contractMissionDetails[0]['conversion'],4));
							$files = "";
							if($contractMissionDetails[0]['documents_path'])
							{
								$exploded_file_paths = explode("|",$contractMissionDetails[0]['documents_path']);
								$exploded_file_names = explode("|",$contractMissionDetails[0]['documents_name']);
								
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
										$files .= '<tr><td width="30%">'.$fname.'</td><td width="35%">'.substr($ofilename['filename'],0,-3).".".$ofilename['extension'].'</td><td width="20%">'.formatSizeUnits(filesize($file_path)).'</td><td>Prod</td><td align="center" width="15%"><a href="/quote/download-document?type=prod_mission&mission_id='.$contractMissionDetails[0]['contractmissionid'].'&index='.$k.'"><i style="margin-right:5px" class="splashy-download"></i></a><td></tr>';	
									}
									$k++;
								}
							}
							$this->_view->alertemail = $contractMissionDetails[0]['alertemail'];
						endif;
						/* Setting external correction for ebooker client */
						if($this->ebookerid == $quote_details[0]['client_id'])
						$this->_view->correction = array(''=>"Select","external"=>"External");
						else
						$this->_view->correction = array(''=>"Select","internal"=>"Internal","external"=>"External");
						
						$tech_obj=new Ep_Quote_TechMissions();
						$search = array();
						$search['quote_id']=$quote_id;
						$search['include_final']='yes';
						$techMissionDetails=$tech_obj->getTechMissionDetails($search);
						
						$expected_techmissions = array();
						$extra_techmissions = array();
						if($techMissionDetails):
						foreach($techMissionDetails as $row):
						$contractMissionDetails = $quote_contract->getContractMission($params['contract_id'],'tech',$row['identifier']);
						if($contractMissionDetails)
						{
							$row['assigned'] = "Yes";
							$row['cmid'] = $contractMissionDetails[0]['contractmissionid'];
							$row['cmstatus'] = $contractMissionDetails[0]['cm_status'];
						}
						else
						{
						$row['assigned'] = "No";
						$row['cmstatus'] = '-';
						}
						/* Checking if there are any blocker missions and blocking to assign user */
						if($row['assigned']=="No" && $row['before_prod']=="yes")
							$this->_view->submit = false;
							
							/* Separating extra and expected missions */
							if($row['from_contract']==1)
							$extra_techmissions[] = $row;
							else
							$expected_techmissions[] = $row;
						endforeach;
						endif;
						$this->_view->extra_techmissions = $extra_techmissions;
						$this->_view->expected_techmissions = $expected_techmissions;
												
						/* getting staff missions */
						$extra_staff  = $quote_contract->getStaffMissionDetails(array('contract_id'=>$contract_id));
						$extra_staffs = array();
						if($extra_staff)
						{
							foreach($extra_staff as $row)
							{
								$contractMissionDetails = $quote_contract->getContractMission($params['contract_id'],'staff',$row['staff_missionId']);
								if($contractMissionDetails)
								{
									$row['assigned'] = "Yes";
									$row['cmid'] = $contractMissionDetails[0]['contractmissionid'];
									$row['cmstatus'] = $contractMissionDetails[0]['cm_status'];
								}
								else
								{
									$row['assigned'] = "No";
									$row['cmstatus'] = '-';
								}
								/* if($row['assigned']=="No" && $row['before_prod']=="yes")
									$this->_view->submit = false; */
								$extra_staffs[] = $row;
							}
						}
						$this->_view->extra_staff = $extra_staffs;				
						$seomission_obj=new Ep_Quote_QuoteMissions();
						$search = array();
						$search['quote_id']=$quote_id;
						$search['misson_user_type']='seo';
						$search['include_final']='yes';
						$search['product_type_seo']='IN';
						$seoMissionDetails = $seomission_obj->getMissionDetails($search);
						
						$inc = 0;
						if(count($seoMissionDetails)):
						foreach($seoMissionDetails as $row)
						{
							$contractMissionDetails = $quote_contract->getContractMission($params['contract_id'],'seo',$row['identifier']);
							if($contractMissionDetails)
							{
								$seoMissionDetails[$inc]['assigned'] = "Yes";
								$seoMissionDetails[$inc]['cmid'] = $contractMissionDetails[0]['contractmissionid'];
								$seoMissionDetails[$inc]['cmstatus'] = $contractMissionDetails[0]['cm_status'];
							}
							else
							{
								$seoMissionDetails[$inc]['assigned'] = "No";
								$seoMissionDetails[$inc]['cmstatus'] = '-';
							}
							/* Checking if there are any blocker missions and blocking to assign user */
							if($seoMissionDetails[$inc]['assigned']=="No" && $seoMissionDetails[$inc]['before_prod']=='yes')
							$this->_view->submit = false;
							
							$seoMissionDetails[$inc++]['title'] = "SEO Proposal $inc ".$this->product_array[$row['product']];	
						}
						endif;
						$this->_view->seoMissionDetails = $seoMissionDetails;
							
						$quotefiles = "";
												
						$quotefiles .= $this->getSeoTechFiles($quote_id);
	
						$quotefiles .= $files;
						$this->_view->quotefiles = $quotefiles;
						
						
						
						if($missionDetails[$index]['comments'])
						{
							$comments['created_by'] = $missionDetails[$index]['created_by'];
							$comments['created_name'] = $missionDetails[$index]['created_name'];
							$comments['created_time'] = $missionDetails[$index]['comment_time'];
							$comments['comment'] = $missionDetails[$index]['comments'];
							$comments['created_at'] = $missionDetails[$index]['created_at'];
							$comment[] = $comments;
						}
						
						$this->_view->comments = $this->sortTimewise($comment);
						
					}
					else
					$this->_redirect("/contractmission/contract-list?submenuId=ML13-SL3");
				}
				else
				$this->_redirect("/contractmission/contract-list?submenuId=ML13-SL3");
				
				$this->render('assign-missions');
			}
			else
				$this->_redirect("/contractmission/contract-list?submenuId=ML13-SL3");
		else:
			$this->_redirect("/contractmission/contract-list?submenuId=ML13-SL3");
		endif;
	}
	/* To assign or update the assigned user in ContractMission table */	
	function assignUserAction()
	{
		if($this->_request-> isPost())            
        {  
			$params = $this->_request->getParams();

			//echo  "<pre>";print_r($params);exit;

			$client_obj=new Ep_Quote_Client();
			$save = array();
			if($params['contractid'] && $params['seotechprodid'] && $params['missiontype']):
			$quote_contract = new Ep_Quote_Quotecontract();
			$contract = $quote_contract->getContract($params['contractid']);
			$expected_end_date = $contract[0]['expected_end_date'];
				
			$params['max_cost'] = str_replace(",",".",$params['max_cost']);
			$params['autre'] = str_replace(",",".",$params['autre']);
			$params['proofreading'] = str_replace(",",".",$params['proofreading']);
			$params['writing'] = str_replace(",",".",$params['writing']);
				if($params['contractmissionid']):
					$save['assigned_to'] = $params['assignuser'];
					$save['updated_by'] = $this->_view->loginuserId;
					$save['updated_at'] = date('Y-m-d H:i:s');
					$save['comment'] = $params['comment'];
					if($params['missiontype']=='prod')
					{
						//$save['min_cost'] = $params['min_cost'];
						$save['max_cost'] = $params['max_cost'];
						$save['correction'] = $params['correction'];
						$save['files_pack'] = $params['files_pack'];
						if($params['stencils'])
						$save['stencils_ebooker'] = $params['stencils'];
						if($params['privatedelivery'])
						$save['privatedelivery'] = $params['privatedelivery'];
						else
						$save['privatedelivery'] = "no";
						
						
						if($params['launch']=="survey")
						{
							$save['is_survey'] = "yes";
							$save['is_recruitment'] = "no";
						}
						elseif($params['launch']=="recruitment")
						{
							$save['is_survey'] = "no";
							$save['is_recruitment'] = "yes";
						}
						else
						{
							$save['is_survey'] = "no";
							$save['is_recruitment'] = "no";
						}
						
						if($params['merge_proofreading_cost'])
						$save['merge_proofreading_cost'] = $params['merge_proofreading_cost'];
						else
						$save['merge_proofreading_cost'] = 'no';
						if($params['merge_writing_cost'])
						$save['merge_writing_cost'] = $params['merge_writing_cost'];
						else
						$save['merge_writing_cost'] = 'no';
						$save['merge'] = $params['merge'];
						$save['writing'] = $params['writing'];
						$save['proofreading'] = $params['proofreading'];
						$save['other'] = $params['autre'];
						$save['alertemail'] = $params['alert_email'];
					}
					
					if($params['assigned_user']!=$params['assignuser'])
					{
						$insert_logs = true;
						$bo_user_details=$client_obj->getQuoteUserDetails($params['assignuser']);
						$assigned_to=$bo_user_details[0]['first_name']." ".$bo_user_details[0]['last_name'];
					}
					else
						$insert_logs = false;
					$res = $quote_contract->updateContractMission($save,$params['contractmissionid']);
					$contract_mission_id = $params['contractmissionid'];
					$this->_helper->FlashMessenger('Updated Successfully');
				else:
					$save['contract_id'] = $params['contractid'];
					$save['type'] = $params['missiontype'];
					$save['type_id'] = $params['seotechprodid'];
					$save['comment'] = $params['comment'];
					$save['assigned_to'] = $params['assignuser'];
					$save['assigned_by'] = $this->_view->loginuserId;
					$save['updated_by'] = $this->_view->loginuserId;
					$save['updated_at'] = date('Y-m-d H:i:s');
					/* Saving currency with conversion in ContractMission */
					if($params['missiontype']=='prod')
					{
						//$save['min_cost'] = $params['min_cost'];
						$save['max_cost'] = $params['max_cost'];
						$save['correction'] = $params['correction'];
						$save['files_pack'] = $params['files_pack'];
						if($params['stencils'])
						$save['stencils_ebooker'] = $params['stencils'];
						if($params['privatedelivery'])
						$save['privatedelivery'] = $params['privatedelivery'];
						/* if($params['launch_survey'])
						$save['is_survey'] = $params['launch_survey'];
						if($params['launch_recuritement'])
						$save['is_recruitment'] = $params['launch_recuritement']; */
						if($params['launch']=="survey")
						{
							$save['is_survey'] = "yes";
							$save['is_recruitment'] = "no";
						}
						elseif($params['launch']=="recruitment")
						{
							$save['is_survey'] = "no";
							$save['is_recruitment'] = "yes";
						}
						else
						{
							$save['is_survey'] = "no";
							$save['is_recruitment'] = "no";
						}
						$save['writing'] = $params['writing'];
						$save['proofreading'] = $params['proofreading'];
						$save['other'] = $params['autre'];
						if($params['merge_proofreading_cost'])
						$save['merge_proofreading_cost'] = $params['merge_proofreading_cost'];
						if($params['merge_writing_cost'])
						$save['merge_writing_cost'] = $params['merge_writing_cost'];
						$save['alertemail'] = $params['alert_email'];
						$save['merge'] = $params['merge'];
						$save['currency'] = $params['currency'];
						$save['conversion'] = $params['conversionprod'];
					}
					else
					{
						$save['currency'] = $params['sales_suggested_currency'];
						$save['conversion'] = $params['conversion'];
					}
					$quoteMission_obj=new Ep_Quote_QuoteMissions();
					$qmdetails =$quoteMission_obj->getMissionDetails(array('mission_id'=>$save['type_id']));
					$save['invoice_per'] = $qmdetails[0]['invoice_per'];
					$save['indicative_turnover'] = $qmdetails[0]['indicative_turnover'];
					$save['unit_price'] = $qmdetails[0]['unit_price'];
					$save['volume'] = $qmdetails[0]['volume'];
					
					$contract_mission_id = $res = $quote_contract->insertContractMission($save);
					
					$bo_user_details=$client_obj->getQuoteUserDetails($params['assignuser']);
					$assigned_to=$bo_user_details[0]['first_name']." ".$bo_user_details[0]['last_name'];
					$insert_logs = true;
					$mail_obj=new Ep_Message_AutoEmails();
					$emailparameters = array('missiontype'=>$save['type'],'assigned_by'=>$this->_view->loginName,'missionname'=>'','comments'=>$params['comment'],'assigned_to'=>$assigned_to);
					$mail_obj->sendContractEmail($bo_user_details[0]['email'],148,$emailparameters);
					
					/* sending mail to managers */
					$mail_content = $mail_obj->getAutoEmail(160);
					$subject = $mail_content[0]['Object'];
					$orgmessage = $mail_content[0]['Message'];
					if($params['missiontype']=='prod')
					{
						if($qmdetails[0]['product']=='translation')
							$mission_name = $this->product_array[$qmdetails[0]['product']]." ".$this->producttype_array[$qmdetails[0]['product_type']]." ".$this->getCustomName("EP_LANGUAGES",$qmdetails[0]['language_source'])." au ".$this->getCustomName("EP_LANGUAGES",$qmdetails[0]['language_dest']);
						else
							$mission_name = $this->product_array[$qmdetails[0]['product']]." ".$this->producttype_array[$qmdetails[0]['product_type']]." in ".$this->getCustomName("EP_LANGUAGES",$qmdetails[0]['language_source']);
						
						$bo_user = $assigned_to;
						eval("\$subject= \"$subject\";");
						$mission_link = "<a href='".$this->url."/followup/prod?submenuId=ML13-SL4&cmid=".$contract_mission_id."'>click here</a>";
						//$users = $quote_contract->getUsers("multilingue");
						$users = $client_obj->getQuoteUserDetails($this->adminLogin->userId);
						foreach($users as $user)
						{
							$name = $user['first_name']." ".$user['last_name'];
							eval("\$message= \"$orgmessage\";");
							$mail_obj->sendEMail($this->mail_from,$message,$user['email'],$subject);
						}
					}
					elseif($params['missiontype']=='seo')
					{
						if($qmdetails[0]['product']=='translation')
							$mission_name = $this->product_array[$qmdetails[0]['product']]." ".$this->producttype_array[$qmdetails[0]['product_type']]." ".$this->getCustomName("EP_LANGUAGES",$qmdetails[0]['language_source'])." au ".$this->getCustomName("EP_LANGUAGES",$qmdetails[0]['language_dest']);
						else
							$mission_name = $this->product_array[$qmdetails[0]['product']]." ".$this->producttype_array[$qmdetails[0]['product_type']]." in ".$this->getCustomName("EP_LANGUAGES",$qmdetails[0]['language_source']);
						
						$bo_user = $assigned_to;
						eval("\$subject= \"$subject\";");
						$mission_link = "<a href='".$this->url."/followup/seo?submenuId=ML13-SL4&cmid=".$contract_mission_id."'>click here</a>";
						//$users = $quote_contract->getUsers("seomanager");
						$users = $client_obj->getQuoteUserDetails($this->adminLogin->userId);
						foreach($users as $user)
						{
							$name = $user['first_name']." ".$user['last_name'];
							eval("\$message= \"$orgmessage\";");
							$mail_obj->sendEMail($this->mail_from,$message,$user['email'],$subject);
						}
					}
					elseif($params['missiontype']=='tech')
					{
						$tech_obj=new Ep_Quote_TechMissions();
						$techdetails = $tech_obj->getTechMissionDetails(array('identifier'=>$params['seotechprodid']));
						if($params['tech_title'])
						$mission_name = $params['tech_title'];
						else
						$mission_name = $techdetails[0]['title'];
						$bo_user = $assigned_to;
						eval("\$subject= \"$subject\";");
						$mission_link = "<a href='".$this->url."/followup/tech?submenuId=ML13-SL4&cmid=".$contract_mission_id."'>click here</a>";
						//$users = $quote_contract->getUsers("techmanager");
						$users = $client_obj->getQuoteUserDetails($this->adminLogin->userId);
						foreach($users as $user)
						{
							$name = $user['first_name']." ".$user['last_name'];
							eval("\$message= \"$orgmessage\";");
							$mail_obj->sendEMail($this->mail_from,$message,$user['email'],$subject);
						}
					}
					$this->_helper->FlashMessenger('Assigned User Successfully');

					//sending email to sales incharge
					$contract_created_user=$client_obj->getQuoteUserDetails($contract[0]['sales_creator_id']);					
					$created_user=$contract_created_user[0]['first_name']." ".$contract_created_user[0]['last_name'];
					$emailparameters['created_user'] =$created_user;
					//$emailparameters['Cliquezici'] ='/followup/'.$params['missiontype'].'?submenuId=ML13-SL4&cmid='.$contract_mission_id;
					$emailparameters['Cliquezici'] ='/contractmission/contract-edit?submenuId=ML13-SL3&contract_id='.$params['contractid'].'&action=view';
					$emailparameters['missionname']=$mission_name;
					//client details
					$cleintDetails=$client_obj->getClientDetails($qmdetails[0]['client_id']);					
					$client_name=$cleintDetails[0]['company_name'];
					$emailparameters['client_name']=$client_name;

					$mail_obj->sendContractEmail($contract_created_user[0]['email'],179,$emailparameters);
					//exit;


				endif;

				/* Updating tempo info */
				if($params['missiontype']=="prod")
				{
					$quote_contract->deleteTempoOneshots($params['contractid'],$params['seotechprodid']);
					$articles = (array) $params['articles'];
					$oneshot_length = (array) $params['oneshot_length'];
					$oneshot_option = (array) $params['oneshot_option'];
					for($i=0;$i<count($articles);$i++)
					{
						$save = array();
						$save['contract_id'] = $params['contractid'];
						$save['mission_id'] = $params['seotechprodid'];
						$save['articles'] = $articles[$i];
						$save['oneshot_length'] = $oneshot_length[$i];
						$save['oneshot_option'] = $oneshot_option[$i];
						$quote_contract->insertTempoOneshots($save);
					}
				}
				$tech_obj=new Ep_Quote_TechMissions();
				$quoteMission_obj=new Ep_Quote_QuoteMissions();
				// Insert title, cost, splitvalues and update turnover if new tech mission added in prod assignment page
				if($params['missiontype']=='tech' && $params['update_tech']==1)
				{
					$tech_update = array();
					$params['tech_cost'] = str_replace(",",".",$params['tech_cost']);
					$tech_update['title'] = $params['tech_title'];
					$tech_update['delivery_time'] = $params['delivery_time'];
					$tech_update['delivery_option'] = $params['delivery_option'];
					$tech_update['cost'] = $params['tech_cost'];
					$tech_update['updated_at'] = date("Y-m-d H:i:s");
					
					$tech_obj->updateTechMission($tech_update,$params['seotechprodid']);
				}						
				/* Updating staff mission */
				if($params['missiontype']=='staff' && $params['update_staff']==1)
				{
					$tech_update = array();
					$params['tech_cost'] = str_replace(",",".",$params['staff_cost']);
					$tech_update['title'] = $params['staff_title'];
					$tech_update['delivery_time'] = $params['delivery_time'];
					$tech_update['delivery_option'] = $params['delivery_option'];
					$tech_update['cost'] = $params['staff_cost'];
					$tech_update['updated_at'] = date("Y-m-d H:i:s");
					$quote_contract->updateStaffMission($tech_update,$params['seotechprodid']);
				}						
				
				
				//if($params['missiontype']=='seo' || $params['missiontype']=='tech' || $params['missiontype']=='prod')
				{
					if(count($_FILES['seo_documents']['name'])>0)	
					{
						$update = false;
						$uploaded_documents = array();
						$uploaded_document_names = array();
						$k = 0;
						$missionIdentifier = $params['seotechprodid'];
						if($params['missiontype']=='prod')
						{
							$missionIdentifier = $contract_mission_id;
						}
						
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
							$uploaded_document_names[] = str_replace('|',"_",$params['document_name'][$k]);
							$update = true;
						}
						$k++;
						endforeach;

						if($update)
						{
							if($params['missiontype']=='tech')
							{
								$result =$tech_obj->getTechMissionDetails(array('identifier'=>$missionIdentifier));
								$uploaded_documents1 = explode("|",$result[0]['documents_path']);
								$uploaded_documents =array_merge($uploaded_documents,$uploaded_documents1);
								$seo_mission_data['documents_path'] = implode("|",$uploaded_documents);
								$document_names =explode("|",$result[0]['documents_name']);
								$document_names =array_merge($uploaded_document_names,$document_names);
								$seo_mission_data['documents_name'] = implode("|",$document_names);
								$tech_obj->updateTechMission($seo_mission_data,$missionIdentifier);
							}
							elseif($params['missiontype']=='prod')
							{
								$result =$quote_contract->getContractMission('','','',$missionIdentifier);
								$uploaded_documents1 = explode("|",$result[0]['documents_path']);
								$uploaded_documents =array_merge($uploaded_documents,$uploaded_documents1);
								$seo_mission_data['documents_path'] = implode("|",$uploaded_documents);
								$document_names =explode("|",$result[0]['documents_name']);
								$document_names =array_merge($uploaded_document_names,$document_names);
								$seo_mission_data['documents_name'] = implode("|",$document_names);
								$quote_contract->updateContractMission($seo_mission_data,$missionIdentifier); 
							}
							elseif($params['missiontype']=='staff')
							{
								$result =$quote_contract->getStaffMissionDetails(array('staff_missionId'=>$missionIdentifier));
								$uploaded_documents1 = explode("|",$result[0]['documents_path']);
								$uploaded_documents =array_merge($uploaded_documents,$uploaded_documents1);
								$seo_mission_data['documents_path'] = implode("|",$uploaded_documents);
								$document_names =explode("|",$result[0]['documents_name']);
								$document_names =array_merge($uploaded_document_names,$document_names);
								$seo_mission_data['documents_name'] = implode("|",$document_names);
								$quote_contract->updateStaffMission($seo_mission_data,$missionIdentifier);
							}
							else
							{
								$result =$quoteMission_obj->getQuoteMission($missionIdentifier);
								$uploaded_documents1 = explode("|",$result[0]['documents_path']);
								$uploaded_documents =array_merge($uploaded_documents,$uploaded_documents1);
								$seo_mission_data['documents_path'] = implode("|",$uploaded_documents);
								$document_names =explode("|",$result[0]['documents_name']);
								$document_names =array_merge($uploaded_document_names,$document_names);
								$seo_mission_data['documents_name'] = implode("|",$document_names);
								$quoteMission_obj->updateQuoteMission($seo_mission_data,$missionIdentifier);
							}
						}
					}
				}			
				
				// Inserting in Logs
				if($insert_logs)
				{
				$log_obj=new Ep_Quote_QuotesLog();	
				if(strtotime(date('Y-m-d'))>strtotime($expected_end_date))
				{
					$actionmessage = $log_obj->getActionSentence(13);
					$no_of_seconds = strtotime(date('Y-m-d H:i:s')) - strtotime($expected_end_date);
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
					$actionmessage = $log_obj->getActionSentence(12);
					
				$bo_user_details=$client_obj->getQuoteUserDetails($this->adminLogin->userId);
				$prod_manager="<strong>".$bo_user_details[0]['first_name'].' '.$bo_user_details[0]['last_name']."</strong>";
				
				//$bo_user_details=$client_obj->getQuoteUserDetails($this->adminLogin->userId);	
				$prod_user = "<strong>".$assigned_to."</strong>";
				
				$actionmessage=strip_tags($actionmessage);
				eval("\$actionmessage= \"$actionmessage\";");
				
				$log_array['user_id'] = $this->adminLogin->userId;
				$log_array['contract_id'] = $params['contractid'];
				$log_array['mission_id'] = $params['seotechprodid'];
				$log_array['mission_type'] = $params['missiontype'];
				$log_array['quote_id'] = $params['quoteid'];
				$log_array['comments'] = $params['comment'];
				$log_array['user_type']=$this->adminLogin->type;
				$log_array['action']='assigned_user';
				$log_array['action_at']=date("Y-m-d H:i:s");
				$log_array['action_sentence']=$actionmessage;
				
				$log_obj->insertLogs($log_array);
				}
				//$index = $params['currentindex']+1;
				$index = $params['currentindex'];
				if($params['currentindex']+1 == $params['maxindex'])
				{
					$this->_redirect("/contractmission/missions-list?submenuId=ML13-SL4&contract_id=".$params['contractid']);
				}
				else
				{
					/* $this->_redirect("/contractmission/assign-mission?submenuId=ML13-SL4&contract_id=".$params['contractid']."&type=".$params['missiontype']."&index=".$index); */
					$this->_redirect("/contractmission/missions-list?submenuId=ML13-SL4&contract_id=".$params['contractid']);
				}
			else:
				$this->_redirect("/contractmission/contract-list?submenuId=ML13-SL3");
			endif;
		}
	}
	/* To get Split values of new tech mission added in prod assignment page */
	function getTechSplitValues($turnover,$days,$launch_date)
	{
		$date2 = date('Y-m-d',strtotime($launch_date."+ $days days"));
		$splitvalues = $this->getSplitValues($launch_date, $date2,$turnover,$days,'euro');
		foreach($splitvalues['months'] as $month)
		{
			if(!in_array($month,$months))
				$months[] = $month;
		}
		return $splitvalues;
	}
	/* To render mission details through Ajax */
	function missionSeoTechAction()
	{
		$request = $this->_request->getParams();
		
		if($request['type']=="tech" && $request['id'])
		{	
			$quote_contract = new Ep_Quote_Quotecontract();
			$res = $quote_contract->getTechMission($request['id']);
			$this->_view->mission_type = "tech";
			if($res)
			{
				$client_obj=new Ep_Quote_Client();
				$bo_user_details=$client_obj->getQuoteUserDetails($res[0]['created_by']);
				$res[0]['tech_user_name']=$bo_user_details[0]['first_name'].' '.$bo_user_details[0]['last_name'];
				$res[0]['comment_time']=time_ago($res[0]['created_at']);
				$res[0]['files'] = "";
				
				if($res[0]['documents_path'])
				{
					$exploded_file_paths = explode("|",$res[0]['documents_path']);
					$exploded_file_names = explode("|",$res[0]['documents_name']);
					$fccount = 0;
					$k=0;
					foreach($exploded_file_paths as $row)
					{
						$file_path=$this->mission_documents_path.$row;
						if(file_exists($file_path) && !is_dir($file_path))
						{
								$fname = $exploded_file_names[$k];
								if($fname=="")
									$fname = basename($row);
								$files .= '<div class="topset2"><a href="/quote/download-document?type=tech_mission&mission_id='.$request['id'].'&index='.$k.'">'.$fname.'</a></div>';
								
						}
						$k++;
					}
				}
				$res[0]['files'] = $files;
				$this->_view->techMissionDetails = $res;
				$this->render('mission-followup-details');
			}
		}
		elseif($request['type']=="seo" && $request['id'])
		{
			$quote_contract = new Ep_Quote_Quotecontract();
			$seoMissionDetails = $quote_contract->getSeoMission($request['id']);
			$this->_view->mission_type = "seo";
			if($seoMissionDetails)
			{
				$s=0;
				foreach($seoMissionDetails as $mission)
				{
					$seoMissionDetails[$s]['product_name'] = $this->product_array[$mission['product']];

					$seoMissionDetails[$s]['language_source_name'] = $this->getCustomName("EP_LANGUAGES",$mission['language_source']);
					$seoMissionDetails[$s]['language_dest_name'] = $this->getCustomName("EP_LANGUAGES",$mission['language_dest']);
					$seoMissionDetails[$s]['product_type_name']=$this->producttype_array[$mission['product_type']];

					$client_obj=new Ep_Quote_Client();
					$bo_user_details=$client_obj->getQuoteUserDetails($mission['created_by']);
					$seoMissionDetails[$s]['seo_user_name']=$bo_user_details[0]['first_name'].' '.$bo_user_details[0]['last_name'];
					$seoMissionDetails[$s]['comment_time']=time_ago($mission['created_at']);
					$seoMissionDetails[$s]['files'] = "";
					if($mission['documents_path'])
					{
						/* $exploded_file_paths = explode("|",$mission['documents_path']);
						$exploded_file_names = explode("|",$mission['documents_name']);
						$fccount = 0;
						foreach($exploded_file_paths as $row):
							$file_path=$this->mission_documents_path.$row;
							if(file_exists($file_path) && !is_dir($file_path))
							{
								$seoMissionDetails[$s]['files'][]=$row;
								$seoMissionDetails[$s]['files_base'][]=basename($row);
								$seoMissionDetails[$s]['filenames'][]=$exploded_file_names[$fccount];
							}
							$fccount++;
						endforeach; */
						$filesarray = array('documents_path'=>$mission['documents_path'],'documents_name'=>$mission['documents_name'],'id'=>$request['id'],'delete'=>false);
						$files = $this->getSeoFiles($filesarray);
						$seoMissionDetails[$s]['files'] = $files;
					}
				}
				$this->_view->seoMissionDetails=$seoMissionDetails;
				$this->render('mission-followup-details');
			}
		}
		
	}
	/* To add new tech mission in prod assignment */
	function addNewTechmissionAction()
	{
		$request = $this->_request->getParams();
		
		if($this->_request->isPost() && $request['quoteid']):
			
			$contract_obj = new Ep_Quote_Quotecontract();
			$contractdetails = $contract_obj->getContract($request['contractid']);
			$quote_obj = new Ep_Quote_Quotes();
			$status = $quote_obj->getQuoteDetails($request['quoteid']);			
		
			if($status):
				$save = array();
				if($request['before_prod'])
				$save['before_prod'] = 'yes';
				if($request['price']!='new')
				$save['cost'] = $request['price'];
				$save['created_by'] = $this->_view->loginuserId;
				$save['from_contract'] = 1;
				$save['currency'] = $status[0]['sales_suggested_currency'];
				$save['comments'] = $request['comment'];
				$quote_contract = new Ep_Quote_Quotecontract();
				$res = $quote_contract->insertTechMission($save);
			
				/* Updating Quote table with techmission ids */
				$techmissions_assigned = array();
				if($status[0]['techmissions_assigned'])
				{
					$techmissions_assigned = explode(",",$status[0]['techmissions_assigned']);
					$techmissions_assigned[] = $res;
				}
				else
				{
					$techmissions_assigned = array();
					$techmissions_assigned[] = $res;
				}
				$update = array('techmissions_assigned'=>implode(",",$techmissions_assigned));
				$quote_obj->updateQuote($update,$request['quoteid']);
				
				/* Updating Files */
				if(count($_FILES['seo_documents']['name'])>0)	
				{
					$update = false;
					
					$uploaded_documents = array();
					$uploaded_document_names = array();
					$k = 0;
					$missionIdentifier = $res;
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
						$tech_obj=new Ep_Quote_TechMissions();
						$result =$tech_obj->getTechMissionDetails(array('identifier'=>$missionIdentifier));
						$uploaded_documents1 = explode("|",$result[0]['documents_path']);
						$uploaded_documents =array_merge($uploaded_documents,$uploaded_documents1);
						$seo_mission_data['documents_path'] = implode("|",$uploaded_documents);
						$document_names =explode("|",$result[0]['documents_name']);
						$document_names =array_merge($uploaded_document_names,$document_names);
						$seo_mission_data['documents_name'] = implode("|",$document_names);
						$tech_obj->updateTechMission($seo_mission_data,$missionIdentifier);
					}
				}
				$this->_helper->FlashMessenger('Added new Tech Mission Successfully');
				
				$client_obj = new Ep_Quote_Client();
				$bo_user_details=$client_obj->getQuoteUserDetails($this->adminLogin->userId);
				
				$mail_obj=new Ep_Message_AutoEmails();
				$emailparameters = array('contract_name'=>$contractdetails[0]['contractname'],'comments'=>$request['comment']);
				$techmanagers = $quote_contract->getUsers('techmanager');
				foreach($techmanagers as $techmanager):
				$mail_obj->sendContractEmail($techmanager['email'],146,$emailparameters);
				endforeach;
				//Inserting in Logs
				$log_obj=new Ep_Quote_QuotesLog();					
				$actionmessage = $log_obj->getActionSentence(16);
				
				if($bo_user_details!='NO')
					$prod_manager= "<strong>".$bo_user_details[0]['first_name'].' '.$bo_user_details[0]['last_name']."</strong>";
				else
					$prod_manager="";
					
				$actionmessage=strip_tags($actionmessage);
				eval("\$actionmessage= \"$actionmessage\";");
			
				$log_array['user_id'] = $this->adminLogin->userId;
				$log_array['contract_id'] = $request['contractid'];
				$log_array['mission_id'] = $res;
				$log_array['mission_type'] = 'tech';
				$log_array['quote_id'] = $request['quoteid'];
				$log_array['user_type']=$this->adminLogin->type;
				$log_array['action']='new_tech_mission';
				$log_array['action_at']=date("Y-m-d H:i:s");
				$log_array['comments'] = $request['comment'];
				$log_array['action_sentence']=$actionmessage;
				$log_obj->insertLogs($log_array);
			endif;
		endif;
		$this->_redirect("/contractmission/assign-mission?submenuId=ML13-SL4&contract_id=$request[contractid]&type=prod&index=$request[index]");
	}
	/* Added to upload old contracts */
	function readContractMissionExcelAction()
	{
		if($this->_request->isPost() && $_FILES['oldmissionfile']['name']!="")
		{
			ini_set('max_execution_time', 300);
		
			$document_name=frenchCharsToEnglish($_FILES['oldmissionfile']['name']);
			$pathinfo = pathinfo($document_name);
			$document_name =$pathinfo['filename'].rand(100,1000).".".$pathinfo['extension'];
			$document_name=str_replace(' ','_',$document_name);
			$document_path= $_SERVER['DOCUMENT_ROOT'].'/BO/oldmissionsupload/'.$document_name;
			if (move_uploaded_file($_FILES['oldmissionfile']['tmp_name'], $document_path))
				chmod($document_path,0777);
				
			$file_type = $pathinfo['extension'];	
			
			$data = array();			
			
			$file= $document_path;
			if(strtolower(trim($file_type)) == "xlsx")
			$data = $this->xlsxread($file);
			elseif(strtolower(trim($file_type)) == "xls")
			$data = $this->xlsread($file);
			
		$type=array(
					"R&eacute;daction"=>"redaction",
					"Traduction"=>"translation",
					"Autre"=>"autre");
			
		$language_details = $this->getXlsSheet3Details($data,$file_type);
		$category_details = $this->getXlsSheet2Details($data,$file_type);
		
		$contractcount = 0;
		$missioncount = 0;
			
		if(count($language_details) && count($category_details))
		{
			$count = true;
			$k=0;
			
			for($i=0;$i<count($data);$i++)
			{
				for($k=0;$k<count($data[$i]);$k++)
				{
					 //if($k==0): /* Sheet Number */
					 $start = false;
					 if(strtolower(trim($file_type)) == "xlsx")
					 {
						$j=1;
						$count =count($data[$i][$k]); 
					 }
					 else
					 {
						$j=2;
						$count =count($data[$i][$k])+1; 
					 }
						for($j=$j;$j<$count;$j++)
						{
							$quote_contract = new Ep_Quote_Quotecontract();
							if(trim($data[$i][$k][$j][1])!=="")
							{
								$contract = array();
								
								$contract['client_name'] = $data[$i][$k][$j][6];
								
								//$dateformat1 = date_create_from_format('d-m-y', $data[$i][$k][$j][16]);
								//$reqdateformat = date_format($dateformat1, 'm/y');
								//$reqdateformat = date('m/y',strtotime($data[$i][$k][$j][16]));
								
								$date1 = $this->getDate($data[$i][$k][$j][16],$file_type,"m/y");
								$contract['contract_name'] = $data[$i][$k][$j][5]." ".$data[$i][$k][$j][1]." ".$date1;
								$contract['code'] = $data[$i][$k][$j][8];
															
								$email = trim($data[$i][$k][$j][4]);
								$comp_name = trim($data[$i][$k][$j][5]);
								$fname = $cname = $data[$i][$k][$j][6];
								if($email=="")	
								{
									$cname = str_replace(" ","",strtolower(frenchCharsToEnglish(trim($data[$i][$k][$j][6]))));
									$email = $cname."@test.com";
								}
								
								$client_id = $quote_contract->getClientId($email,$cname,$comp_name,$fname);
								
								$contract['client_id'] = $client_id; 
								
								if($data[$i][$k][$j][9]=="dailly")
									$payment_type = "daily";
								else if($data[$i][$k][$j][9]=="autres")
									$payment_type = "other";
								else 
									$payment_type = $data[$i][$k][$j][9];
									
								$contract['payment_type'] = $payment_type;
								$contract['turnover'] = number_format($data[$i][$k][$j][10],2,'.','');
								$date = $this->getDate($data[$i][$k][$j][12],$file_type,"Y-m-d");
								$contract['date_of_signature'] = $date;
								$date = $this->getDate($data[$i][$k][$j][13],$file_type,"Y-m-d");
								$contract['end_date'] = $date;
								$contract['created_at'] = date('Y-m-d H:i:s');
								$contract['turnover_currency'] = "euro";
								
								$contractcount++;
								
								$contract_id = $quote_contract->insertuploadContract($contract);
								
								$mission = array();
								
								if(frenchCharsToEnglish(utf8_encode(trim($data[$i][$k][$j][1])))==frenchCharsToEnglish("Rédaction"))
									$mission_type = "redaction";
								elseif(trim($data[$i][$k][$j][1])=="Traduction")
								$mission_type = "translation";
								else
								$mission_type= "autre";
								
								$mission['type'] = $mission_type;
								
								if($mission['type']=="translation")
								{
									$explode = explode('-',$data[$i][$k][$j][2]);
									$source_language = $language_details[strtolower(trim($explode[0]))]['language_name'];
									$dest_language = $language_details[strtolower(trim($explode[1]))]['language_name'];
									$language_name = $source_language." > ".$dest_language;
									$mission['language2'] = strtolower(trim($explode[1]));
									$mission['language1'] = strtolower(trim($explode[0]));
								}
								else
								{
									$source_language = $language_name = $language_details[strtolower(trim($data[$i][$k][$j][2]))]['language_name'];
									$mission['language1'] = strtolower(trim($data[$i][$k][$j][2]));
								}
								$mission_lang = "Mission ".$data[$i][$k][$j][6]." ".$data[$i][$k][$j][1];
								$mission_date = $date1;
								$mission['title'] = "Mission ".$data[$i][$k][$j][6]." ".$data[$i][$k][$j][1]." ".$language_name." ".$date1;
								$mission['contract_id'] = $contract_id;
								$mission['mission_length'] = $data[$i][$k][$j][15];
								$date = $this->getDate($data[$i][$k][$j][16],$file_type,"Y-m-d");
								$mission['starting_date'] = $date;
								$article_type = $this->getTypeofArticle($data[$i][$k][$j][3]);
								
								if($article_type=="")
									$article_type = "article_seo";
									
								$mission['type_of_article'] = $article_type;
								$mission['num_of_articles'] = $data[$i][$k][$j][17];
												
								$category = $category_details[frenchCharsToEnglish(utf8_encode(trim($data[$i][$k][$j][18])))];
								if($category=="")
									$category = "autre";
																
								$mission['category'] = $category;
								
								if($data[$i][$k][$j][20]>$data[$i][$k][$j][19])
								{
									$min_cost = round($data[$i][$k][$j][19]);
									$max_cost = round($data[$i][$k][$j][20]);
								}
								else
								{
									$min_cost = round($data[$i][$k][$j][20]);
									$max_cost = round($data[$i][$k][$j][19]);
								}
								$mission['logo'] =1;
								$mission['min_cost_mission'] = number_format($min_cost,0,'.','');
								$mission['max_cost_mission'] = number_format($max_cost,0,'.','');
								
								$selling_price = $data[$i][$k][$j][11]/$data[$i][$k][$j][17];
								
								$mission['selling_price'] = number_format($selling_price,2,'.','');
								
								$mission['margin_before_signature'] = number_format(floatval($data[$i][$k][$j][25]),2,'.','');
								
								$margin_after_signature = (1-((trim($data[$i][$k][$j][20])+trim($data[$i][$k][$j][24])+trim($data[$i][$k][$j][22]))/$selling_price))*100;
													
								$mission['margin_after_signature'] = number_format($margin_after_signature,2,'.','');
								$mission['article_length'] = $data[$i][$k][$j][26];
								$mission['comments'] = $data[$i][$k][$j][27];
								$mission['bo_incharge'] = $quote_contract->getEPContactsMaster($data[$i][$k][$j][28]); /* Get id from table */
								$mission['mission_users_count'] = $data[$i][$k][$j][29]; 
								$mission['fo_display'] = 1; 
								$mission['min_cost_mission_currency'] = 'euro'; 
								$mission['max_cost_mission_currency'] = 'euro'; 
								$mission['selling_price_currency'] = 'euro'; 
								$mission['category_other'] = "";
								$mission['writing_cost_before_signature_currency'] = "euro";
								$mission['correction_cost_before_signature'] = number_format($data[$i][$k][$j][21],2,'.','');
								$mission['other_cost_before_signature'] = number_format($data[$i][$k][$j][23],2,'.','');
								$mission['writing_cost_after_signature'] = number_format($data[$i][$k][$j][20],2,'.','');
								$mission['correction_cost_after_signature'] = number_format($data[$i][$k][$j][22],2,'.','');
								$mission['other_cost_after_signature'] = number_format($data[$i][$k][$j][24],2,'.','');
								$mission['writing_cost_before_signature'] = number_format($data[$i][$k][$j][19],2,'.','');
								$mission['correction_cost_before_signature_currency'] = "euro";
								$mission['other_cost_before_signature_currency'] = "euro";
								$mission['writing_cost_after_signature_currency'] = "euro";
								$mission['correction_cost_after_signature_currency'] = "euro";
								$mission['other_cost_after_signature_currency'] = "euro";
								$mission['creation_date'] = date("Y-m-d H:i:s");
							
								$count_array = $this->insertMissions($mission,$contract,$mission_type,$language_details,$mission_lang,$mission_date,$contractcount,$missioncount);
								$contractcount = $count_array[0];
								$missioncount = $count_array[1];
							}
							$start = true;
						}
					// endif;
					break;
				}
			}
		}
		$this->_helper->FlashMessenger('Inserted '.$contractcount." Contracts and ".$missioncount." Missions");
		unlink($file);
		}
		
		$this->_redirect("/contractmission/import-old-missions?submenuId=ML13-SL3");
	}
	/* used in old contract upload */
	function getDate($date,$file_type,$date_type)
	{	
		$date = str_replace("/","-",$date);
		$explode = explode("-",$date);
		if($file_type=="xlsx")
		{
			$explode[2] = "20".$explode[2];
		}
		$date = implode("-",$explode);
		return date($date_type,strtotime($date));
	}
	
	function xlsxread($file)
	{
		require_once APP_PATH_ROOT.'nlibrary/tools/PHPExcel.php';

		$objReader = PHPExcel_IOFactory::createReader('Excel2007');
        $objReader->setReadDataOnly(false);
        $objPHPExcel = $objReader->load($file);
        $sheetname = $objPHPExcel->getSheetNames();
		$xlsArr1 = array();
		
        foreach ($objPHPExcel->getWorksheetIterator() as $objWorksheet) {
            $xlsArr1[] = $objWorksheet->toArray(null,true,true,false);
        }
		
		$xls_array = array();
		
		 for ($i = 0; $i < sizeof($xlsArr1); $i++) {
            if (sizeof($xlsArr1[$i])>0) {
                $x = 0;
                while ($x < sizeof($xlsArr1[$i])) {
                    $y = 1;
                    while ($y <= sizeof($xlsArr1[$i][$x])) {
                        $xls_array[$i][$x][$y] = str_replace('–', '-', $xlsArr1[$i][$x][$y-1]);
						                        
                        $xls_array[$i][$x][$y] = isset($xls_array[$i][$x][$y]) ? ((mb_detect_encoding($xls_array[$i][$x][$y]) == "ISO-8859-1") ? iconv("ISO-8859-1", "UTF-8", $xls_array[$i][$x][$y]) : $xls_array[$i][$x][$y]) : '';
                        
                        if(strlen($xls_array[$i][$x][$y])>strlen(utf8_decode($xls_array[$i][$x][$y])))
                            $xls_array[$i][$x][$y] = isset($xls_array[$i][$x][$y]) ? html_entity_decode($xls_array[$i][$x][$y],ENT_QUOTES,"UTF-8") : '';
                        else
                            $xls_array[$i][$x][$y] = isset($xls_array[$i][$x][$y]) ? utf8_encode($xls_array[$i][$x][$y]) : '';
                        $xls_array[$i][$x][$y] = utf8_decode($xls_array[$i][$x][$y]) ;
                        
                        $y++;
                    }
                    $x++;
                }
            }
        }
		
        return array($xls_array,$sheetname);
	}
	
	function xlsread($file)
	{
		require_once APP_PATH_ROOT.'nlibrary/tools/reader.php';
        
        $data = new Spreadsheet_Excel_Reader();
        $data->setOutputEncoding('Windows-1252') ;
        $data->read($file);
        $bound_sheets=$data->boundsheets;
        $sheets = sizeof($data->sheets);

        for ($i = 0; $i < $sheets; $i++)
        {
            $sheetname[$i]=$bound_sheets[$i]['name'];
            if (sizeof($data->sheets[$i]['cells'])>0)
            {
                $x = 1;
                while ($x <= sizeof($data->sheets[$i]['cells']))
                {
                    $y = 1;
                    while ($y <= $data->sheets[$i]['numCols'])
                    {
                        $data->sheets[$i]['cells'][$x][$y] = $data->sheets[$i]['cells'][$x][$y] ;

                        $xls_array[$i][$x][$y] = isset($data->sheets[$i]['cells'][$x][$y]) ? ((mb_detect_encoding($data->sheets[$i]['cells'][$x][$y]) == "ISO-8859-1") ? iconv("ISO-8859-1", "UTF-8", $data->sheets[$i]['cells'][$x][$y]) : $data->sheets[$i]['cells'][$x][$y]) : '';

                        if(strlen($xls_array[$i][$x][$y])>strlen(utf8_decode($xls_array[$i][$x][$y])))
                            $xls_array[$i][$x][$y] = isset($xls_array[$i][$x][$y]) ? html_entity_decode($xls_array[$i][$x][$y],ENT_QUOTES,"UTF-8") : '';
                        else
                            $xls_array[$i][$x][$y] = isset($xls_array[$i][$x][$y]) ? utf8_encode($xls_array[$i][$x][$y]) : '';
                        $xls_array[$i][$x][$y] = utf8_decode($xls_array[$i][$x][$y]) ;
                        $y++;
                    }
                    $x++;
                }
            }
        }
        return array($xls_array, $sheetname) ;
	}
	
	function getXlsSheet3Details($data,$file_type)
	{
		$k=2;
		$i=0;
		$language = array();
		if(strtolower(trim($file_type)) == "xlsx")
		 {
			$j=1;
			$count =count($data[$i][$k]); 
		 }
		 else
		 {
			$j=2;
			$count =count($data[$i][$k])+1; 
		 }
		 
			for($j=0;$j<$count;$j++)
			{
				if(trim($data[$i][$k][$j][3])!="" && trim($data[$i][$k][$j][4])!="" && trim($data[$i][$k][$j][4])!="Source" && trim($data[$i][$k][$j][4])!="Destination")
				{
					$language[$data[$i][$k][$j][1]]['language_name'] = $data[$i][$k][$j][2];
					$language[$data[$i][$k][$j][1]]['source'] = $data[$i][$k][$j][3];
					$language[$data[$i][$k][$j][1]]['destination'] = $data[$i][$k][$j][4];
					$language[$data[$i][$k][$j][1]]['lang_index'] = $data[$i][$k][$j][1];
				}
			}
		return $language;
	}
	
	function getTypeofArticle($name)
	{
		$array = array('Article seo'=>'article_seo','Descriptif produit'=>'descriptif_produit','Article de blog'=>'article_de_blog','News'=>'news','Guide'=>'guide','Desc. produit'=>'descriptif_produit','Article blog'=>'article_de_blog','Autre'=>'autre');
		return $array[trim($name)];
	}
	
	function getXlsSheet2Details($data,$file_type)
	{
		$k=1;
		$i=0;
		$category = array();
		
		if(strtolower(trim($file_type)) == "xlsx")
		 {
			$j=1;
			$count =count($data[$i][$k]); 
		 }
		 else
		 {
			$j=2;
			$count =count($data[$i][$k])+1; 
		 }
		
			for($j=0;$j<$count;$j++)
			{
				if(trim($data[$i][$k][$j][1])!="" && trim($data[$i][$k][$j][2])!="" && trim($data[$i][$k][$j][1])!="Index" && frenchCharsToEnglish(utf8_encode(trim($data[$i][$k][$j][2])))!=frenchCharsToEnglish("Catégories"))
				{
					$category[frenchCharsToEnglish(utf8_encode(trim($data[$i][$k][$j][2])))] = $data[$i][$k][$j][1];
				}
			}
		return $category;
	}
	/* To upload old missions through xls/xlsx */
	function insertMissions($mission,$contract,$mission_type,$language_details,$mission_lang,$mission_date,$contractcount,$missioncount)
	{
	
		$original_amission = $mission;
		$ocontract = $contract;
		if($mission_type=="redaction" || $mission_type=="autre")
		{
			if(strtolower($mission['language1'])=='uk')
			{
				foreach($language_details as $key => $value)
				{
					$missioncount++;
					if($original_amission['language1']==$key)
					{
						$contract_obj = new Ep_Quote_Quotecontract();
						$contract_obj->insertMissions($original_amission);
					}
					else
					{
						$contract_obj = new Ep_Quote_Quotecontract();
						$new_value = floatval($language_details[$key]['source'])/100;
						$contract['turnover'] = number_format(($ocontract['turnover']+($ocontract['turnover']*$new_value)),2,'.','');
						$contractcount++;
						$mission['contract_id'] = $contract_obj->insertuploadContract($contract);
						$mission['simulation'] = "yes";
						$mission['language1'] = $key;
						$mission['title'] = $mission_lang." ".$language_details[$key]['language_name']." ".$mission_date;
						$mission['min_cost_mission'] = number_format(round($original_amission['min_cost_mission']+$original_amission['min_cost_mission']*$new_value),0,'.','');
						$mission['max_cost_mission'] = number_format(round($original_amission['max_cost_mission']+$original_amission['max_cost_mission']*$new_value),0,'.','');
						$mission['correction_cost_before_signature'] = number_format($original_amission['correction_cost_before_signature']+$original_amission['correction_cost_before_signature']*$new_value,2,'.','');
						$mission['other_cost_before_signature'] = number_format($original_amission['other_cost_before_signature']+$original_amission['other_cost_before_signature']*$new_value,2,'.','');
						$mission['writing_cost_after_signature'] = number_format($original_amission['writing_cost_after_signature']+$original_amission['writing_cost_after_signature']*$new_value,2,'.','');
						$mission['correction_cost_after_signature'] = number_format($original_amission['correction_cost_after_signature']+$original_amission['correction_cost_after_signature']*$new_value,2,'.','');
						$mission['other_cost_after_signature'] = number_format($original_amission['other_cost_after_signature']+$original_amission['other_cost_after_signature'] *$new_value,2,'.','');
						$mission['writing_cost_before_signature'] = number_format($original_amission['writing_cost_before_signature']+$original_amission['writing_cost_before_signature']*$new_value,2,'.','');
						$mission['selling_price'] = number_format(($original_amission['selling_price']*$new_value+$original_amission['selling_price']),2,'.','');
						$contract_obj->insertMissions($mission);
					}
				} 
			}
			else
			{
				$missioncount++;
				$contract_obj = new Ep_Quote_Quotecontract();
				$contract_obj->insertMissions($original_amission);
			}
		}
		else if($mission_type=="translation")
		{
			foreach($language_details as $key1 => $value1)
			{
				foreach($language_details as $key => $value)
				{
					
					if($key1 != $key):
					$missioncount++;
					if($original_amission['language1']==$key1 && $original_amission['language2']==$key)
					{
						$contract_obj = new Ep_Quote_Quotecontract();
						$contract_obj->insertMissions($original_amission);
					}
					else
					{
						$contract_obj = new Ep_Quote_Quotecontract();
						$new_value = floatval($language_details[$key]['destination'])/100;
						$contract['turnover'] = number_format(($ocontract['turnover']+($ocontract['turnover']*$new_value)),2,'.','');
						$contractcount++;
						$mission['contract_id'] = $contract_obj->insertuploadContract($contract);
						$mission['language1'] = $key1;
						$mission['language2'] = $key;
						$mission['title'] = $mission_lang." ".$language_details[$key]['language_name']." ".$mission_date;
						$mission['min_cost_mission'] = number_format(round($original_amission['min_cost_mission']+$original_amission['min_cost_mission']*$new_value),0,'.','');
						$mission['max_cost_mission'] = number_format(round($original_amission['max_cost_mission']+$original_amission['max_cost_mission']*$new_value),0,'.','');
						$mission['selling_price'] = number_format($original_amission['selling_price']+$original_amission['selling_price']*$new_value,2,'.','');
						$mission['correction_cost_before_signature'] = number_format($original_amission['correction_cost_before_signature']+ $original_amission['correction_cost_before_signature']*$new_value,2,'.','');
						$mission['other_cost_before_signature'] = number_format($original_amission['other_cost_before_signature']+ $original_amission['other_cost_before_signature']*$new_value,2,'.','');
						$mission['writing_cost_after_signature'] = number_format($original_amission['writing_cost_after_signature']+$original_amission['writing_cost_after_signature']*$new_value,2,'.','');
						$mission['correction_cost_after_signature'] = number_format($original_amission['correction_cost_after_signature']+$original_amission['correction_cost_after_signature']*$new_value,2,'.','');
						$mission['other_cost_after_signature'] = number_format($original_amission['other_cost_after_signature']+$original_amission['other_cost_after_signature']*$new_value,2,'.','');
						$mission['writing_cost_before_signature'] = number_format($original_amission['writing_cost_before_signature']+$original_amission['writing_cost_before_signature']*$new_value,2,'.','');
						$contract_obj = new Ep_Quote_Quotecontract();
						
						$contract_obj->insertMissions($mission);
					}
					endif;
				}
			}
		}
		return array($contractcount,$missioncount);
	}
	
	function importOldMissionsAction()
	{
		$this->render('import-old-missions');
	}
	
	
			
				

							
					
					
					
					
					 
					
					
					
					
					
					
					
					
					
					
					
						
						
	
			
			
				

							
					
					
					
					
					 
					
					
					
					
					
					
					
					
					
					
					
					
					
					
					
					
					
					
					
						
					
	
		
		
	
	
				
	
	
		
					
				



				
				
				
				
					
					
				


					
	
	
			
			
			
			
			
			
			
			
			
			
			
				
				
				
				
		
	



	
			
			



	
	
	
	
	
			
			
			
			
	
			
			
				

							
					
					
					
					
					 
					
					
					
					
					
					
					
					
					
					
					
					
					
					
					
	
	
	
	
			
			

	
	/* To get Quote, SEO and Tech Files related to quote */
	function getSeoTechFiles($quote_id)
	{
		$files = "";
		
		$quote_obj = new Ep_Quote_Quotes();
		$quote_details = $quote_obj->getQuoteDetails($quote_id);
		$zip = "";
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
					$zip = true;
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
					$zip = true;
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
						$zip = true;
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
		//if($zip)
		//	$files .=  '<thead><tr><td colspan="5"><a href="/quote/download-document?type=cm&index=-1&quote_id='.$quote_id.'" class="btn btn-small pull-right">Download Zip</a></td></tr></thead>';
		return $files;
	}	
	
			
	
				

				
			
			
			
	
				

				
					
	
	/* To download recruitment test art files */
	function downloadFileAction()
	{
		$request = $this->_request->getParams();
		$this->_redirect("/BO/download-survey.php?filename=".$request['filename']."&recruitmenttestartid=".$request['recruitmenttestartid']."&recruitmenttestart=".$request['recruitmenttestart']);
	}
	
	/* Deprecated not in use Mission Followup page */
	function missionListAction()
	{
		$con_mis_obj = new Ep_Quote_Quotecontract();
		$res = $con_mis_obj->getContractMissions(array('type'=>'prod'));
		
		$survey_obj = new Ep_Quote_Survey();
		$recruitment_obj = new Ep_Quote_Recruitment();

		for($i=0;$i<count($res);$i++):
			$res[$i]['product_name'] = $this->product_array[$res[$i]['product']];
			$res[$i]['product_type_name'] = $this->producttype_array[$res[$i]['product_type']];
			$res[$i]['language_source_name'] = $this->getCustomName("EP_LANGUAGES",$res[$i]['language_source']);
			$res[$i]['language_dest_name'] =$this->getCustomName("EP_LANGUAGES",$res[$i]['language_dest']);
			$res[$i]['create_delivery'] = true;
			if($res[$i]['is_survey']=='yes')
			{
				if($survey_res = $survey_obj->getPoll(array('contract_mission_id'=>$res[$i]['contractmissionid'])))
				{
					$res[$i]['survey_status'] = $survey_res[0]['status'];
					$res[$i]['survey_id'] = $survey_res[0]['pid'];
					if($res[$i]['survey_status']=="closed")
					$res[$i]['create_delivery'] = true;
					else
					$res[$i]['create_delivery'] = false;
				}
				else
				{
					$res[$i]['survey_status'] = 'create';
					$res[$i]['create_delivery'] = false;
				}
			}
			if($res[$i]['is_recruitment']=='yes')
			{
				if($recruitment_res = $recruitment_obj->getRecruitmentContractMission($res[$i]['contractmissionid']))
				{
					$res[$i]['recruitment_status'] = $recruitment_res[0]['status'];
					$res[$i]['recruitment_id'] = $recruitment_res[0]['recruitment_id'];
					if($res[$i]['recruitment_status']=="closed")
					$res[$i]['create_delivery'] = true;
					else
					$res[$i]['create_delivery'] = false;
				}
				else
				{
					$res[$i]['recruitment_status'] = 'create';
					$res[$i]['create_delivery'] = false;
				}
			}
		endfor;
		$this->_view->contractmissionsopened = $res;
		
		$this->render('mission-list');
	}
	
	/* Missions List with opened, to validate and finished */
	function missionsListAction()
	{
		$con_mis_obj = new Ep_Quote_Quotecontract();
		$client_obj = new Ep_Quote_Client();		
		
		$request = $this->_request->getParams();
		$contract_id = $request['contract_id'];
		
		$search = array();
		$search['contract_id'] = $contract_id;
		//$search['assigned_to'] = $request['pmid'];
		$pmid = $request['pmid'];
			
		$prod_seo_tech = array();
		/* load mission based on user type */
		if($this->_view->user_type=='techuser' || $this->_view->user_type=='seouser' || $this->_view->user_type=='prodsubmanager' || $this->_view->user_type=='multilingue' || $this->_view->user_type=='salesuser' || $this->_view->user_type=='salesmanager' || $this->_view->user_type=='ceouser' || $this->_view->user_type=='facturation')
		{
			$prod_seo_tech = $this->missionslistUsers($search);
		}
		elseif($this->_view->user_type=='techmanager' || $this->_view->user_type=='seomanager' || $this->_view->user_type=='prodmanager')
		{
			$prod_seo_tech = $this->missionslistManagers($search);
		}
		elseif($this->_view->user_type=='superadmin')
		{
			$contract_missions = $con_mis_obj->getContractTechMissions($search);
			/* Get all tech missions which are assigned */
			$contractMA  = array();
			foreach($contract_missions as $row)
			{
				$contractMA[$row['contract_id']][] = array('type_id'=>$row['type_id'],'cmid'=>$row['contractmissionid'],'type'=>$row['type'],'assigned_to'=>$row['assigned_to'],'assigned_at'=>$row['assigned_at'],'progress_percent'=>$row['progress_percent'],'cm_status'=>$row['cm_status'],'client_name'=>$row['client_name'],'client_id'=>$row['client_id']); 
			}
			/* Get all seo and prod missions */
			$sales_seo_missions = $con_mis_obj->getSalesSeoMissionsContracts($search);
			
			$prev_quote = "";
			
			$prod_seo_tech = array();
					
			$prodIndex = $seoIndex = $techIndex = 0;
			
			foreach($sales_seo_missions as $row)
			{
				if(($prev_quote !="") && ($prev_quote != $row['quotecontractid']))
				{
					$prodIndex = $seoIndex = 0;
				}
				
				if($row['misson_user_type']=='sales' || ($row['misson_user_type']=='seo' && $row['product']!='seo_audit' && $row['product']!='smo_audit'))
				{
					$row['type'] = 'prod';
					$row['index'] = $prodIndex++;
				}
				else
				{
					$row['type'] = 'seo';
					$row['index'] = $seoIndex++;
				}
			
				if($row['contractmissionid'])
				{
					if($row['assigned_to'])
					{
					$userDetails = $client_obj->getQuoteUserDetails($row['assigned_to']);
					$row['pm'] = $userDetails[0]['first_name']." ".$userDetails[0]['last_name'];
					}
					else
					$row['pm'] = "";
					$formatted_row = $this->formatMission($row);
					//echo $pmid." ".$row['assigned_to']."<br>";
					if(!empty($pmid))
					{
						if($pmid==$row['assigned_to'])
						{
							if($row['cm_status']=='ongoing')
							{
								if($row['assigned_to'])
								$prod_seo_tech['assigned_users'][$row['assigned_to']] = $row['pm'];
								$prod_seo_tech['ongoing_languages'][$row['language_source']] = $formatted_row[$row['language_source']];
								if($row['language_dest'])
									$prod_seo_tech['ongoing_languages'][$row['language_dest']] = $formatted_row[$row['language_dest']];
								$prod_seo_tech['opened'][$row['quotecontractid']][] = $formatted_row;
							}	
							elseif($row['cm_status']=='deleted')
							{
								if($row['assigned_to'])
								$prod_seo_tech['deleted_users'][$row['assigned_to']] = $row['pm'];
								$prod_seo_tech['deleted_languages'][$row['language_source']] = $formatted_row[$row['language_source']];
								if($row['language_dest'])
									$prod_seo_tech['deleted_languages'][$row['language_dest']] = $formatted_row[$row['language_dest']];
								$prod_seo_tech['deleted'][$row['quotecontractid']][] = $formatted_row;
							}
							else
							{
								if($row['assigned_to'])
								$prod_seo_tech['finished_users'][$row['assigned_to']] = $row['pm'];
								$prod_seo_tech['finished_languages'][$row['language_source']] = $formatted_row[$row['language_source']];
								if($row['language_dest'])
									$prod_seo_tech['finished_languages'][$row['language_dest']] = $formatted_row[$row['language_dest']];
								$prod_seo_tech['finished'][$row['quotecontractid']][] = $formatted_row;
							}
						}						
					}
					else
					{
						if($row['cm_status']=='ongoing')
						{
							if($row['assigned_to'])
								$prod_seo_tech['assigned_users'][$row['assigned_to']] = $row['pm'];
							$prod_seo_tech['ongoing_languages'][$row['language_source']] = $formatted_row[$row['language_source']];
							if($row['language_dest'])
								$prod_seo_tech['ongoing_languages'][$row['language_dest']] = $formatted_row[$row['language_dest']];
							$prod_seo_tech['opened'][$row['quotecontractid']][] = $formatted_row;
						}
						elseif($row['cm_status']=='deleted')
						{
							if($row['assigned_to'])
							$prod_seo_tech['deleted_users'][$row['assigned_to']] = $row['pm'];
							$prod_seo_tech['deleted_languages'][$row['language_source']] = $formatted_row[$row['language_source']];
							$prod_seo_tech['deleted'][$row['quotecontractid']][] = $formatted_row;
						}
						else
						{
							if($row['assigned_to'])
							$prod_seo_tech['finished_users'][$row['assigned_to']] = $row['pm'];
							$prod_seo_tech['finished_languages'][$row['language_source']] = $formatted_row[$row['language_source']];
							if($row['language_dest'])
								$prod_seo_tech['finished_languages'][$row['language_dest']] = $formatted_row[$row['language_dest']];
							$prod_seo_tech['finished'][$row['quotecontractid']][] = $formatted_row;
						}
					}
				}
				else
				{
					$row['pm'] =  "";
					$formatted_row = $this->formatMission($row);
					$prod_seo_tech['to_assign'][$row['quotecontractid']][] = $formatted_row;
					$prod_seo_tech['toassign_languages'][$row['language_source']] = $formatted_row[$row['language_source']];
					if($row['language_dest'])
						$prod_seo_tech['toassign_languages'][$row['language_dest']] = $formatted_row[$row['language_dest']];
				}
				
				$prev_quote = $row['quotecontractid'];
			}
			/* get all contracts and check tech missions status */
			$contracts = $con_mis_obj->getContracts(array('mulitple_status'=>"'validated','closed','deleted'",'cid'=>$search['contract_id']));
			foreach($contracts as $row)
			{
				$searchParameters = array();
				$searchParameters['quote_id']=$row['quoteid'];
				$searchParameters['include_final']='yes';
				$techMissionDetails=$con_mis_obj->getTechMissionDetails($searchParameters);
				$techIndex = 0;
				
				foreach($techMissionDetails as $tech_row)
				{
					$row['index'] = $techIndex++;
					$res = $this->checkContractMissionAssigned($contractMA,'tech',$row['quotecontractid'],$tech_row['identifier']);
				
					if($res)
					{
					$explode = explode('|',$res);
					if($explode[0])
					{
					$userDetails = $client_obj->getQuoteUserDetails($explode[0]);
					$row['pm'] = $userDetails[0]['first_name']." ".$userDetails[0]['last_name'];
					}
					else
					$row['pm'] = "";
					$row['cmid'] = $explode[1];
					$row['assigned_to'] = $explode[0];
					$tech_row['assigned_at'] = $explode[2];
					$tech_row['client_name'] = $explode[5];
					$tech_row['client_id'] = $explode[6];
					//echo $pmid." ".$explode[0]."<br>";
					$formatted_row = $this->formatTechMission($tech_row,$row);
						if(!empty($pmid))
						{
							if($pmid==$explode[0])
							{
								if($explode[4]=='ongoing')
								{
									if($row['assigned_to'])
									$prod_seo_tech['assigned_users'][$row['assigned_to']] = $row['pm'];
									$prod_seo_tech['opened'][$row['quotecontractid']][] = $formatted_row;
								}
								elseif($explode[4]=='deleted')
								{
									if($row['assigned_to'])
									$prod_seo_tech['deleted_users'][$row['assigned_to']] = $row['pm'];
									$prod_seo_tech['deleted'][$row['quotecontractid']][] = $formatted_row;
								}	
								else
								{
									if($row['assigned_to'])
									$prod_seo_tech['finished_users'][$row['assigned_to']] = $row['pm'];
									$prod_seo_tech['finished'][$row['quotecontractid']][] = $formatted_row;
								}
							}
						}
						else
						{
						if($explode[4]=='ongoing')
							{
								if($row['assigned_to'])
									$prod_seo_tech['assigned_users'][$row['assigned_to']] = $row['pm'];
								$prod_seo_tech['opened'][$row['quotecontractid']][] = $formatted_row;
							}
							elseif($explode[4]=='deleted')
							{
								if($row['assigned_to'])
									$prod_seo_tech['deleted_users'][$row['assigned_to']] = $row['pm'];
								$prod_seo_tech['deleted'][$row['quotecontractid']][] = $formatted_row;
							}
						else
							{
								if($row['assigned_to'])
									$prod_seo_tech['finished_users'][$row['assigned_to']] = $row['pm'];
								$prod_seo_tech['finished'][$row['quotecontractid']][] = $formatted_row;
							}
						}
					}
					else
					{
					$row['pm'] = "";
					$row['cmid'] = "";
					$tech_row['assigned_at'] = "";
					if($row['clfname'] || $row['cllname'])
					$tech_row['client_name'] = $row['clfname'] ." ". $row['cllname'];
					else
					$tech_row['client_name'] = $row['clemail'];
					$tech_row['client_id'] = $row['clientid'];
					$prod_seo_tech['to_assign'][$row['quotecontractid']][] = $this->formatTechMission($tech_row,$row); 
					}
				}
			}
			
			/* get staffing missions */
			$staffing_missions = $con_mis_obj->getStaffMissions(array('contract_id'=>$contract_id));
			if($staffing_missions)
			{
				$staffindex = 0;
				$prev_contract_id = "";
				foreach($staffing_missions as $row)
				{
					if($prev_contract_id == "" || $row['contract_id']!==$prev_contract_id)
						$staffindex = 0;
					else
						$staffindex++;
					$row['index'] = $staffindex;
					if($row['cmid'])
					{
						$row['pm'] = $row['first_name']." ".$row['last_name'];
						$formatted_row = $this->formatStaffMission($row);
						if($row['cm_status']=='ongoing')
						{
							if($row['assigned_to'])
							$prod_seo_tech['assigned_users'][$row['assigned_to']] = $row['pm'];
							$prod_seo_tech['opened'][$row['contract_id']][] = $formatted_row;
						}
						elseif($row['cm_status']=='deleted')
						{
							$prod_seo_tech['deleted'][$row['contract_id']][] = $formatted_row;
							if($row['assigned_to'])
								$prod_seo_tech['deleted_users'][$row['assigned_to']] = $row['pm'];
						}
						else
						{
							$prod_seo_tech['finished'][$row['contract_id']][] = $formatted_row;
							if($row['assigned_to'])
								$prod_seo_tech['finished_users'][$row['assigned_to']] = $row['pm'];
						}
					}
					else
					{
						$row['pm'] = "";
						$row['cmid'] = "";
						$formatted_row = $this->formatStaffMission($row);
						$prod_seo_tech['to_assign'][$row['contract_id']][] = $formatted_row;
					}
					$prev_contract_id = $row['contract_id'];
				}
			}
			/* getting sales mission */
			$sales_mission = $con_mis_obj->getSalesMissions(array('contract_id'=>$contract_id,'type'=>'sales'));
			foreach($sales_mission as $row)
			{
				$formatted_row = $this->formatSalesMission($row);
				if($row['cm_status']=='ongoing')
				{
					$prod_seo_tech['opened'][$row['contract_id']][] = $formatted_row;
					if($row['assigned_to'])
					$prod_seo_tech['assigned_users'][$row['assigned_to']] = $row['first_name']." ".$row['last_name'];
				}
				elseif($row['cm_status']=='deleted')
				{
					$prod_seo_tech['deleted'][$row['contract_id']][] = $formatted_row;
					if($row['assigned_to'])
					$prod_seo_tech['deleted_users'][$row['assigned_to']] = $row['first_name']." ".$row['last_name'];
				}
				else
				{
					$prod_seo_tech['finished'][$row['contract_id']][] = $formatted_row;
					if($row['assigned_to'])
					$prod_seo_tech['finished_users'][$row['assigned_to']] = $row['first_name']." ".$row['last_name'];
				}
				/* if($row['assigned_to'])
				$prod_seo_tech['users'][$row['assigned_to']] = $row['first_name']." ".$row['last_name']; */
			}
		}
	
		/* if($this->_view->user_type=='superadmin')
			$this->_view->pms = $con_mis_obj->getUsers("'techuser' OR type='seouser' OR type='prodsubmanager' OR type='multilingue' OR type='salesuser' OR type='salesmanager'",'',true);
		elseif($this->_view->user_type=='techmanager')
			$this->_view->pms = $con_mis_obj->getUsers("techuser");
		elseif($this->_view->user_type=='seomanager')
			$this->_view->pms = $con_mis_obj->getUsers("seouser");
		elseif($this->_view->user_type=='prodmanager')
			$this->_view->pms = $con_mis_obj->getUsers("'prodsubmanager' OR type='multilingue'",true,true);  */
		natcasesort($prod_seo_tech['assigned_users']); 
		$this->_view->assigned_pms = $prod_seo_tech['assigned_users'];
		natcasesort($prod_seo_tech['finished_users']); 
		$this->_view->finished_pms = $prod_seo_tech['finished_users'];
		natcasesort($prod_seo_tech['deleted_users']); 
		$this->_view->deleted_pms  = $prod_seo_tech['deleted_users'];
		$this->_view->contractmissionsopened = $prod_seo_tech['opened'];
		$this->_view->contractmissionstoassign = $prod_seo_tech['to_assign'];
		$this->_view->contractmissionsfinished = $prod_seo_tech['finished'];
		$this->_view->contractmissionsdeleted = $prod_seo_tech['deleted'];
		/* $this->_view->languages = $this->_arrayDb->loadArrayv2("EP_LANGUAGES", $this->_lang);
		$language_array=$this->_arrayDb->loadArrayv2("EP_LANGUAGES", $this->_lang);
		natsort($language_array);
		//print_r($language_array);
		$this->_view->languages =$language_array;// $this->_arrayDb->loadArrayv2("EP_LANGUAGES", $this->_lang);
		*/
		$this->_view->contractlist = $con_mis_obj->getContracts(array('mulitple_status'=>"'validated','closed','deleted'"));
		natsort($prod_seo_tech['ongoing_languages']); 
		$this->_view->ongoing_languages = $prod_seo_tech['ongoing_languages'];
		natsort($prod_seo_tech['deleted_languages']); 
		natsort($prod_seo_tech['finished_languages']); 
		natsort($prod_seo_tech['toassign_languages']); 
		$this->_view->deleted_languages = $prod_seo_tech['deleted_languages'];
		$this->_view->finished_languages = $prod_seo_tech['finished_languages'];
		$this->_view->toassign_languages = $prod_seo_tech['toassign_languages'];
		
		$this->render('missions-list');
	}
	/* Closing of mission maintaining closed as status in ContractMissions */
	function closeMissionAction()
	{
		if($this->_request->isPost() && $_SERVER['HTTP_X_REQUESTED_WITH']=='XMLHttpRequest')
		{
				$request = $this->_request->getParams();
				$quote_contract_obj = new Ep_Quote_Quotecontract();
				$insert = array();
				$insert['is_deleted'] = 1;
				$insert['cm_status'] = 'closed';
				$insert['updated_at'] = date("Y-m-d H:i:s");
				$insert['updated_by'] = $this->adminLogin->userId;
				if($request['cmid'])
					$quote_contract_obj->updateContractMission($insert,$request['cmid']);
				else
				{
				$insert['contract_id'] = $request['contract_id'];
				$insert['type'] = $request['type'];
				if($request['type']=="tech")
				$insert['type_id'] = $request['tid'];
				elseif($request['type']=="staff")
				$insert['type_id'] = $request['sid'];
				else
				$insert['type_id'] = $request['qmid'];
				$quote_contract_obj->insertContractMission($insert);
				}
		}
	}
	/* To close bulk mission from missions list */
	function closeBulkMissionAction()
	{
		$request = $this->_request->getParams();
		if($request['cmclose'] && $request['bulkstatus'] && $request['closemission'])
		{
			$quote_contract_obj = new Ep_Quote_Quotecontract();
			$cmids = implode(",",$request['closemission']);
			$insert = array();
			$insert['is_deleted'] = 1;
			$insert['cm_status'] = $request['bulkstatus'];
			$insert['updated_at'] = date("Y-m-d H:i:s");
			$insert['updated_by'] = $this->adminLogin->userId;
			$quote_contract_obj->updateBulkContractMission($insert,$cmids);
			$this->_helper->FlashMessenger(ucwords($request['bulkstatus'])." mission successfully");
		}
		elseif($request['cmclosebulk'] && $request['closemission'])
		{
			$quote_contract_obj = new Ep_Quote_Quotecontract();
			foreach($request['closemission'] as $key => $value)
			{
				$insert = array();
				$insert['is_deleted'] = 1;
				$insert['cm_status'] = $request['bulkstatus'];
				$type_contract = explode("_",$value);
				$insert['contract_id'] = $type_contract[1];
				$insert['type'] = $type_contract[0];
				$insert['type_id'] = $key;
				$insert['updated_at'] = date("Y-m-d H:i:s");
				$insert['updated_by'] = $this->adminLogin->userId;
				$quote_contract_obj->insertContractMission($insert);
				$this->_helper->FlashMessenger(ucwords($request['bulkstatus'])." mission successfully");
			}
		}
		if($request['bulkstatus']=="closed")
			$active = 'finished';
		else
			$active = 'deleted';
		$this->_redirect('/contractmission/missions-list?submenuId=ML13-SL4&active='.$active);
	}
	/* To close and delete contract in the Contract list */
	function closeBulkContractAction()
	{
		$request = $this->_request->getParams();
		if($request['cmclose'] && $request['bulkstatus'])
		{
			$contracts = $request['closecontract'];
			$contract_obj = new Ep_Quote_Quotecontract();
			$search = array();
			$update = array('status'=>$request['bulkstatus'],'closed_at'=>date("Y-m-d H:i:s"),'closed_by'=>$this->adminLogin->userId);
			/* Update comment if only one contract is closing */
			if(count($contracts)==1 && $request['bulkstatus']=="closed")
			{
				$update['closed_comment'] = $request['comment'];
				if(trim($request['comment']))
				{
					foreach($contracts as $contract => $quote_id)
					{
						$log_array = array();
						$log_obj=new Ep_Quote_QuotesLog();		
						$log_array['user_id'] = $this->adminLogin->userId;
						$log_array['contract_id'] = $contract;
						$log_array['quote_id'] = $quote_id;
						$log_array['comments'] = $request['comment'];
						$log_array['user_type']=$this->adminLogin->type;
						$log_array['action']='contract_closed_comments';
						$log_array['action_at']=date("Y-m-d H:i:s");
						$log_array['action_sentence']="Closed comment added by ".$this->adminLogin->loginName;
						$log_obj->insertLogs($log_array);
					}
				}
			}
			foreach($contracts as $contract => $quote_id)
			{
				$contract_obj->updateContract($update,$contract);
				$search['contract_id'] = $contract;
				$tech_missions = $contract_obj->getContractTechMissions($search);
				$tech_missions_assigned = array();
				foreach($tech_missions as $tech)
				{
					$tech_missions_assigned[$tech['type_id']] = $tech['contractmissionid'];
				}
				$searchParameters = array();
				$searchParameters['quote_id']=$quote_id;
				$searchParameters['include_final']='yes';
				$techMissionDetails=$contract_obj->getTechMissionDetails($searchParameters);
				foreach($techMissionDetails as $tech_row)
				{
					if(array_key_exists($tech_row['identifier'],$tech_missions_assigned))
					{
						$insert = array();
						$insert['is_deleted'] = 1;
						$insert['cm_status'] = $request['bulkstatus'];
						$contract_obj->updateContractMission($insert,$tech_missions_assigned[$tech_row['identifier']]);
					}
					else
					{
						$insert = array();
						$insert['is_deleted'] = 1;
						$insert['cm_status'] = $request['bulkstatus'];
						$insert['contract_id'] = $contract;
						$insert['type'] = 'tech';
						$insert['type_id'] = $tech_row['identifier'];
						$contract_obj->insertContractMission($insert);
					}
				}
				$prod_seo_missions = $contract_obj->getSalesSeoMissionsContracts($search);
				foreach($prod_seo_missions as $prod_seo)
				{
					if($prod_seo['contractmissionid'])
					{
						$insert = array();
						$insert['is_deleted'] = 1;
						$insert['cm_status'] = $request['bulkstatus'];
						$contract_obj->updateContractMission($insert,$prod_seo['contractmissionid']);
					}
					else
					{
						$insert = array();
						$insert['is_deleted'] = 1;
						$insert['cm_status'] = $request['bulkstatus'];
						$insert['contract_id'] = $contract;
						if($prod_seo['misson_user_type']=='sales' || ($prod_seo['misson_user_type']=='seo' && $prod_seo['product']!='seo_audit' && $prod_seo['product']!='smo_audit'))
						{
							$insert['type'] = 'prod';
						}
						else
						{
							$insert['type'] = 'seo';
						}
						$insert['type_id'] = $prod_seo['qmid'];
						$contract_obj->insertContractMission($insert);
					}
				}
			}
			if($request['bulkstatus']=="closed")
				$active = 'finished';
			else
				$active = 'deleted';
			$this->_helper->FlashMessenger(ucwords($request['bulkstatus'])." contract successfully");
			$this->_redirect('/contractmission/contract-list?submenuId=ML13-SL3&active='.$active);
		}
	}
	/* To Load Missions based on the user type like seouser, techuser, multilingue and soon */
	function missionslistUsers($search=array(),$lang="")
	{
		$prod_seo_tech = array();
		if($this->_view->user_type=='techuser')
		{
			$search['assigned_to'] = $this->_view->userId;
			$contract_obj = new Ep_Quote_Quotecontract();
			
			$assignedMissions = $contract_obj->getContractTechMissions($search);
		
			$user_obj=new Ep_User_User();
			$user_info = $user_obj->getAllUsersDetails($this->_view->userId);
			
			if($user_info)
			$name = $user_info[0]['first_name']." ".$user_info[0]['last_name'];
			else
			$name = $this->_view->loginName ;
			
			foreach($assignedMissions as $row)
			{
				$row['pm'] = $name; 
				$row['cmid'] = $row['contractmissionid']; 
				if($row['cm_status']=='ongoing')
				$prod_seo_tech['opened'][$row['quotecontractid']][] = $this->formatTechMission($row,$row);
				elseif($row['cm_status']=='deleted')
				$prod_seo_tech['deleted'][$row['quotecontractid']][] = $this->formatTechMission($row,$row);
				else
				$prod_seo_tech['finished'][$row['quotecontractid']][] = $this->formatTechMission($row,$row);
			}
		}
		else if($this->_view->user_type=='seouser')
		{
			$search['assigned_to'] = $this->_view->userId;
			$search['type'] = 'seo';
			$contract_obj = new Ep_Quote_Quotecontract();
			
			$assignedMissions = $contract_obj->getSalesSeoMissionsContracts($search);
		
			$user_obj=new Ep_User_User();
			$user_info = $user_obj->getAllUsersDetails($this->_view->userId);
			
			if($user_info)
			$name = $user_info[0]['first_name']." ".$user_info[0]['last_name'];
			else
			$name = $this->_view->loginName;
			
			foreach($assignedMissions as $row)
			{
				$row['pm'] = $name; 
				$row['cmid'] = $row['contractmissionid']; 
				$row['type'] = 'seo'; 
				$formatted_row = $this->formatMission($row,$row);
				$prod_seo_tech['languages'][$row['language_source']] = $formatted_row[$row['language_source']];
				if($row['language_dest'])
					$prod_seo_tech['languages'][$row['language_dest']] = $formatted_row[$row['language_dest']];
				if($row['cm_status']=='ongoing')
				{
					if($lang=="" || $lang==$row['language_source'])
					{
						$prod_seo_tech['opened'][$row['quotecontractid']][] = $formatted_row;
					}
				}
				elseif($row['cm_status']=='deleted')
				{
					if($lang=="" || $lang==$row['language_source'])
					{
						$prod_seo_tech['deleted'][$row['quotecontractid']][] = $formatted_row;
					}
				}
				else
				{
					if($lang=="" || $lang==$row['language_source'])
					$prod_seo_tech['finished'][$row['quotecontractid']][] = $formatted_row;
				}
			}
		}
		else if($this->_view->user_type=='prodsubmanager' || $this->_view->user_type=='multilingue')
		{
			$search['assigned_to'] = $this->_view->userId;
			$search['type'] = 'prod';
			$contract_obj = new Ep_Quote_Quotecontract();
			
			$assignedMissions = $contract_obj->getSalesSeoMissionsContracts($search);
		
			$user_obj=new Ep_User_User();
			$user_info = $user_obj->getAllUsersDetails($this->_view->userId);
			
			if($user_info)
			$name = $user_info[0]['first_name']." ".$user_info[0]['last_name'];
			else
			$name = $this->_view->loginName;
			
			foreach($assignedMissions as $row)
			{
				$row['pm'] = $name; 
				$row['cmid'] = $row['contractmissionid']; 
				$row['type'] = 'prod'; 
				$formatted_row = $this->formatMission($row,$row);
				$prod_seo_tech['languages'][$row['language_source']] = $formatted_row[$row['language_source']];
				if($row['language_dest'])
					$prod_seo_tech['languages'][$row['language_dest']] = $formatted_row[$row['language_dest']];
				if($row['cm_status']=="ongoing")
				{
					if($lang=="" || $lang==$row['language_source'])
					$prod_seo_tech['opened'][$row['quotecontractid']][] = $formatted_row;
				}
				if($row['cm_status']=="deleted")
				{
					if($lang=="" || $lang==$row['language_source'])
					$prod_seo_tech['deleted'][$row['quotecontractid']][] = $formatted_row;
				}
				else
				{
					if($lang=="" || $lang==$row['language_source'])
					$prod_seo_tech['finished'][$row['quotecontractid']][] = $formatted_row;
				}
			}
			$staffing_missions = $contract_obj->getStaffMissions(array('contract_id'=>$search['contract_id'],'assigned_to'=>$this->_view->userId));
			if($staffing_missions)
			{
				$staffindex = 0;
				$prev_contract_id = "";
				foreach($staffing_missions as $row)
				{
					if($prev_contract_id == "" || $row['contract_id']!==$prev_contract_id)
						$staffindex = 0;
					else
						$staffindex++;
					$row['index'] = $staffindex;
					if($row['cmid'])
					{
						$row['pm'] = $row['first_name']." ".$row['last_name'];
						if($row['cm_status']=='ongoing')
							$prod_seo_tech['opened'][$row['contract_id']][] = $this->formatStaffMission($row);
						if($row['cm_status']=='deleted')
							$prod_seo_tech['deleted'][$row['contract_id']][] = $this->formatStaffMission($row);
						else
							$prod_seo_tech['finished'][$row['contract_id']][] = $this->formatStaffMission($row);
					}
					else
					{
						$row['pm'] = "";
						$row['cmid'] = "";
						$prod_seo_tech['to_assign'][$row['contract_id']][] = $this->formatStaffMission($row);
					}
					$prev_contract_id = $row['contract_id'];
				}
			}
		}
		else if($this->_view->user_type=='facturation')
		{
			$contract_obj = new Ep_Quote_Quotecontract();
			/* getting sales mission */
			$sales_mission = $contract_obj->getSalesMissions(array('contract_id'=>$search['contract_id'],'type'=>'sales'));
			foreach($sales_mission as $row)
			{
				if($row['cm_status']=='ongoing')
				$prod_seo_tech['opened'][$row['contract_id']][] = $this->formatSalesMission($row);
				elseif($row['cm_status']=='deleted')
				$prod_seo_tech['deleted'][$row['contract_id']][] = $this->formatSalesMission($row);
				else
				$prod_seo_tech['finished'][$row['contract_id']][] = $this->formatSalesMission($row);
			}
		}
		else
		{
			$contract_obj = new Ep_Quote_Quotecontract();
			/* getting sales mission */
			$sales_mission = $contract_obj->getSalesMissions(array('contract_id'=>$contract_id,'type'=>'sales','assigned_to'=>$this->_view->userId));
			foreach($sales_mission as $row)
			{
				if($row['cm_status']=='ongoing')
				$prod_seo_tech['opened'][$row['contract_id']][] = $this->formatSalesMission($row);
				elseif($row['cm_status']=='deleted')
				$prod_seo_tech['opened'][$row['contract_id']][] = $this->formatSalesMission($row);
				else
				$prod_seo_tech['finished'][$row['contract_id']][] = $this->formatSalesMission($row);
			}
		}
		return $prod_seo_tech;
	}
	
	/* To load Missions based on the user type like seomangers, techmangers, prodmanagers and soon */
	function missionslistManagers($search=array(),$lang="")
	{
		$con_mis_obj = new Ep_Quote_Quotecontract();
		$client_obj = new Ep_Quote_Client();
		
		$prod_seo_tech = array();
		
		$prodIndex = $seoIndex = $techIndex = 0;
		if($this->_view->user_type=='techmanager')
		{
			$contract_missions = $con_mis_obj->getContractTechMissions($search);
			
			$contractMA  = array();
			foreach($contract_missions as $row)
			{
				$contractMA[$row['contract_id']][] = array('type_id'=>$row['type_id'],'cmid'=>$row['contractmissionid'],'type'=>$row['type'],'assigned_to'=>$row['assigned_to'],'assigned_at'=>$row['assigned_at'],'progress_percent'=>$row['progress_percent'],'cm_status'=>$row['cm_status'],'client_name'=>$row['client_name'],'client_id'=>$row['client_id']); 
			}
					
			$prev_quote = "";
					
			$contracts = $con_mis_obj->getContracts(array('mulitple_status'=>"'validated','closed','deleted'",'cid'=>$search['contract_id']));
			foreach($contracts as $row)
			{
				$searchParameters = array();
				$searchParameters['quote_id']=$row['quoteid'];
				$searchParameters['include_final']='yes';
				$techMissionDetails=$con_mis_obj->getTechMissionDetails($searchParameters);
				$techIndex = 0;
				
				foreach($techMissionDetails as $tech_row)
				{
					$row['index'] = $techIndex++;
					$res = $this->checkContractMissionAssigned($contractMA,'tech',$row['quotecontractid'],$tech_row['identifier']);
				
					if($res)
					{
					$explode = explode('|',$res);
					if($explode[0])
					{
					$userDetails = $client_obj->getQuoteUserDetails($explode[0]);
					$row['pm'] = $userDetails[0]['first_name']." ".$userDetails[0]['last_name'];
					}
					else
					$row['pm'] = "";
					$row['cmid'] = $explode[1];
					$row['assigned_to'] = $explode[0];
					$tech_row['assigned_at'] = $explode[2];
					$tech_row['client_name'] = $explode[5];
					$tech_row['client_id'] = $explode[6];
					if($explode[4]=='ongoing')
						{
							if($row['assigned_to'])
							$prod_seo_tech['assigned_users'][$row['assigned_to']] = $row['pm'];
						$prod_seo_tech['opened'][$row['quotecontractid']][] = $this->formatTechMission($tech_row,$row);
						}
						elseif($explode[4]=='deleted')
						{
							if($row['assigned_to'])
								$prod_seo_tech['deleted_users'][$row['assigned_to']] = $row['pm'];
							$prod_seo_tech['deleted'][$row['quotecontractid']][] = $this->formatTechMission($tech_row,$row);
						}
					else
						{
							if($row['assigned_to'])
							$prod_seo_tech['finished_users'][$row['assigned_to']] = $row['pm'];
						$prod_seo_tech['finished'][$row['quotecontractid']][] = $this->formatTechMission($tech_row,$row);
						}
					}
					else
					{
					$row['pm'] = "";
					$row['cmid'] = "";
					$tech_row['assigned_at'] = "";
					if($row['clfname'] || $row['cllname'])
					$tech_row['client_name'] = $row['clfname'] ." ". $row['cllname'];
					else
					$tech_row['client_name'] = $row['clemail'];
					$tech_row['client_id'] = $row['clientid'];
					$prod_seo_tech['to_assign'][$row['quotecontractid']][] = $this->formatTechMission($tech_row,$row); 
					}
				}
			}
			
		}
		else
		{
			if($this->_view->user_type=='seomanager')
			$search['mission_type'] = " AND qm.product IN('seo_audit','smo_audit')";
			elseif($this->_view->user_type=='prodmanager')
			$search['mission_type'] = " AND qm.product NOT IN('seo_audit','smo_audit')";
			 
			$sales_seo_missions = $con_mis_obj->getSalesSeoMissionsContracts($search);
						
			$prev_quote = "";
			
			$prod_seo_tech = array();
					
			$prodIndex = $seoIndex = $techIndex = 0;
			
			foreach($sales_seo_missions as $row)
			{
				if(($prev_quote !="") && ($prev_quote != $row['quotecontractid']))
				{
					$prodIndex = $seoIndex = 0;
				}
				
				if($row['misson_user_type']=='sales' || ($row['misson_user_type']=='seo' && $row['product']!='seo_audit' && $row['product']!='smo_audit'))
				{
					$row['type'] = 'prod';
					$row['index'] = $prodIndex++;
				}
				else
				{
					$row['type'] = 'seo';
					$row['index'] = $seoIndex++;
				}
			
				if($row['contractmissionid'])
				{
					if($row['assigned_to'])
					{
					$userDetails = $client_obj->getQuoteUserDetails($row['assigned_to']);
					$row['pm'] = $userDetails[0]['first_name']." ".$userDetails[0]['last_name'];
					}
					else
					$row['pm'] = "";
					$formatted_row = $this->formatMission($row);
					$prod_seo_tech['languages'][$row['language_source']] = $formatted_row[$row['language_source']];
					if($row['language_dest'])
						$prod_seo_tech['languages'][$row['language_dest']] = $formatted_row[$row['language_dest']];
					if($row['cm_status']=='ongoing')
					{
						if($lang=="" || $lang==$row['language_source'])
						{
						if($row['assigned_to'])
						$prod_seo_tech['assigned_users'][$row['assigned_to']] = $row['pm'];
						$prod_seo_tech['ongoing_languages'][$row['language_source']] = $formatted_row[$row['language_source']];
						if($row['language_dest'])
							$prod_seo_tech['ongoing_languages'][$row['language_dest']] = $formatted_row[$row['language_dest']];
						$prod_seo_tech['opened'][$row['quotecontractid']][] = $formatted_row;
						}
					}
					elseif($row['cm_status']=='deleted')
					{
						if($lang=="" || $lang==$row['language_source'] || $lang==$row['language_dest'])
						{
							if($row['assigned_to'])
							$prod_seo_tech['deleted_users'][$row['assigned_to']] = $row['pm'];
							$prod_seo_tech['deleted_languages'][$row['language_source']] = $formatted_row[$row['language_source']];
							if($row['language_dest'])
								$prod_seo_tech['deleted_languages'][$row['language_dest']] = $formatted_row[$row['language_dest']];
							$prod_seo_tech['deleted'][$row['quotecontractid']][] = $formatted_row;
						}
					}
					else
					{
						if($lang=="" || $lang==$row['language_source'] || $lang==$row['language_dest'])
						{
							if($row['assigned_to'])
							$prod_seo_tech['finished_users'][$row['assigned_to']] = $row['pm'];
							$prod_seo_tech['finished_languages'][$row['language_source']] = $formatted_row[$row['language_source']];
							if($row['language_dest'])
								$prod_seo_tech['finished_languages'][$row['language_dest']] = $formatted_row[$row['language_dest']];
							$prod_seo_tech['finished'][$row['quotecontractid']][] = $formatted_row;
						}
					}
				}
				else
				{
					$row['pm'] =  "";
					$formatted_row = $this->formatMission($row);
					if($lang=="" || $lang==$row['language_source'] || $lang==$row['language_dest'])
					{
						$prod_seo_tech['toassign_languages'][$row['language_source']] = $formatted_row[$row['language_source']];
						if($row['language_dest'])
							$prod_seo_tech['toassign_languages'][$row['language_dest']] = $formatted_row[$row['language_dest']];
						$prod_seo_tech['to_assign'][$row['quotecontractid']][] = $formatted_row;
					}
				}
				
				$prev_quote = $row['quotecontractid'];
			}
			/* get staffing missions */
			if($this->_view->user_type=='prodmanager')
			{
					$staffing_missions = $con_mis_obj->getStaffMissions(array('contract_id'=>$search['contract_id'],'assigned_to'=>$search['assigned_to']));
					if($staffing_missions)
					{
						$staffindex = 0;
						$prev_contract_id = "";
						foreach($staffing_missions as $row)
						{
							if($prev_contract_id == "" || $row['contract_id']!==$prev_contract_id)
								$staffindex = 0;
							else
								$staffindex++;
							$row['index'] = $staffindex;
							if($row['cmid'])
							{
								$row['pm'] = $row['first_name']." ".$row['last_name'];
								if($row['cm_status']=='ongoing')
								{
									if($row['assigned_to'])
									$prod_seo_tech['assigned_users'][$row['assigned_to']] = $row['pm'];
									$prod_seo_tech['opened'][$row['contract_id']][] = $this->formatStaffMission($row);
								}
								elseif($row['cm_status']=='deleted')
								{
									if($row['assigned_to'])
										$prod_seo_tech['deleted_users'][$row['assigned_to']] = $row['pm'];
									$prod_seo_tech['deleted'][$row['contract_id']][] = $this->formatStaffMission($row);
								}
								else
								{
									if($row['assigned_to'])
									$prod_seo_tech['finished_users'][$row['assigned_to']] = $row['pm'];
									$prod_seo_tech['finished'][$row['contract_id']][] = $this->formatStaffMission($row);
								}
							}
							else
							{
								$row['pm'] = "";
								$row['cmid'] = "";
								$prod_seo_tech['to_assign'][$row['contract_id']][] = $this->formatStaffMission($row);
							}
							$prev_contract_id = $row['contract_id'];
						}
					}
			}
		}
		return $prod_seo_tech;
	}
	
	/* To Check Mission assigned or not */
	function checkContractMissionAssigned($contractMA,$type,$qcid,$qmid)
	{
		if(array_key_exists($qcid,$contractMA))
		{
			foreach($contractMA[$qcid] as $row)
			{
				if($row['type']==$type && $row['type_id']==$qmid)
				{
					return $row['assigned_to']."|".$row['cmid']."|".$row['assigned_at']."|".$row['progress_percent']."|".$row['cm_status']."|".$row['client_name']."|".$row["client_id"];
				}
			}
			return false;
		}
		else
		return false;
	}
	
	/* Format row according to view */
	function formatMission($row)
	{
		$array = array();
		$lang_source = $this->getCustomName("EP_LANGUAGES",$row['language_source']);
		$array[$row['language_source']] = $lang_source;
		if($row['product']=='translation')
		{
			$lang_dest = $this->getCustomName("EP_LANGUAGES",$row['language_dest']);
			$array['title'] = $this->product_array[$row['product']]." ".$this->producttype_array[$row['product_type']]." ".$lang_source." au ".$lang_dest;
			$array['lang'] = $lang_source." - ".$lang_dest;
			$array['destination_language'] = $lang_dest;
			$array[$row['language_dest']] = $lang_dest;
		}
		else
		{
			$array['title'] = $this->product_array[$row['product']]." ".$this->producttype_array[$row['product_type']]." in ".$lang_source;
			$array['lang'] = $lang_source;
                
			$array['destination_language'] = "-";
                }
                if($row['product_type']=="autre")
                {
                       $array['title'] .= " ".$row['product_type_other'];
                }
		/* $array['edate'] = $row['expected_end_date'];
		$array['edispdate'] = date('d/m/Y',strtotime($row['expected_launch_date']));
		$array['ldate'] = $row['expected_launch_date']; */
	 	$array['source_language'] = $lang_source;
		if($row['assigned_at'])
		{
			if($row['mission_length_option']!='days')
			$days = ceil($row['mission_length']/24);
			else
			$days = $row['mission_length'];
			
			if($days == '')
				$days = 0;
			$array['edate'] = date('Y-m-d',strtotime($row['assigned_at']." + $days days"));
			
			$array['edispdate'] = date('d/m/Y',strtotime($row['assigned_at']." + $days days"));
			$array['ldate'] = $row['assigned_at'];
		}
		else
		{
			$array['edate'] = $row['expected_end_date'];
			$array['edispdate'] = date('d/m/Y',strtotime($row['expected_launch_date']));
			$array['ldate'] = $row['expected_launch_date'];
		}  
		
		$array['qmid'] = $row['qmid'];
		if($row['free_mission']=="yes")
			$array['turnover'] = "Free";
		else
			$array['turnover'] = $row['turnover'];
		$array['currency'] = $row['sales_suggested_currency'];
		$array['pm'] = $row['first_name']." ".$row['last_name'];
		$array['pmnew'] = $row['pm'];
		$array['pmid'] = $row['assigned_to'];
		$array['type'] = $row['type'];
		$array['cid'] = $row['quotecontractid'];
		$array['cmid'] = $row['contractmissionid'];
		$array['company_name'] = $row['company_name'];
		$array['index'] = $row['index'];
		$array['client_name'] = $row['client_name'];
		$array['client_id'] = $row['client_id'];
		$array['percentage'] = $row['progress_percent']."%";
		$array['assigned_at'] = $row['assigned_at'];
		return $array;
	}
	
	/* Format Tech Mission */
	function formatTechMission($tech_row,$row)
	{
		if($tech_row['title'])
		$array['title'] = $tech_row['title'];
		else
		$array['title'] = "New Tech Mission";
		$array['lang'] = '-';
		$array['source_language'] = '-';
		$array['destination_language'] = '-';
		if($tech_row['assigned_at'])
		{
			if($tech_row['delivery_option']!='days')
			$days = ceil($tech_row['delivery_time']/24);
			else
			$days = $tech_row['delivery_time'];
			
			if($days == '')
				$days = 0;
	
			$array['edate'] = date('Y-m-d',strtotime($tech_row['assigned_at']." + $days days"));
	
			$array['edispdate'] = date('d/m/Y',strtotime($tech_row['assigned_at']." + $days days"));
			$array['ldate'] = $tech_row['assigned_at'];
		}
		else 
		{
			$array['edate'] = $row['expected_end_date'];
			$array['edispdate'] = date('d/m/Y',strtotime($row['expected_launch_date']));
			$array['ldate'] = $row['expected_launch_date'];
		}
		$array['qmid'] = "-";
		if($tech_row['free_mission']=="yes")
		$array['turnover'] = "Free";
		else
		$array['turnover'] = $tech_row['turnover'];
		$array['percentage'] = $tech_row['progress_percent']."%";
		$array['currency'] = $row['sales_suggested_currency'];
		$array['pm'] = $row['first_name']." ".$row['last_name'];
		$array['pmid'] = $row['assigned_to'];
		$array['pmnew'] = $row['pm'];
		$array['type'] = 'tech';
		$array['cid'] = $row['quotecontractid'];
		$array['cmid'] = $row['cmid'];
		$array['index'] = $row['index'];
		$array['company_name'] = $row['company_name'];
		$array['client_name'] = $tech_row['client_name'];
		$array['client_id'] = $tech_row['client_id'];
		$array['assigned_at'] = $tech_row['assigned_at'];
		$array['tech_id'] = $tech_row['identifier'];
		return $array;
	}
	
	/* To format staff mission */
	function formatStaffMission($staff_row)
	{
		if($staff_row['title'])
		$array['title'] = $staff_row['title'];
		else
		$array['title'] = "New Staff Mission";
		$array['lang'] = '-';
		$array['source_language'] = '-';
		$array['destination_language'] = '-';
		if($staff_row['assigned_at'])
		{
			if($staff_row['delivery_option']!='days')
			$days = ceil($staff_row['delivery_time']/24);
			else
			$days = $staff_row['delivery_time'];
			if($days == '')
				$days = 0;
			$array['edate'] = date('Y-m-d',strtotime($staff_row['assigned_at']." + $days days"));
			$array['edispdate'] = date('d/m/Y',strtotime($staff_row['assigned_at']." + $days days"));
			$array['ldate'] = $staff_row['assigned_at'];
		}
		else 
		{
			$array['edate'] = $staff_row['expected_end_date'];
			$array['edispdate'] = date('d/m/Y',strtotime($staff_row['expected_launch_date']));
			$array['ldate'] = $staff_row['expected_launch_date'];
		}
		$array['qmid'] = "-";
		$array['turnover'] = $staff_row['turnover'];
		$array['percentage'] = $staff_row['progress_percent']."%";
		$array['currency'] = $staff_row['currency'];
		$array['pm'] = $staff_row['first_name']." ".$row['last_name'];
		$array['pmid'] = $staff_row['assigned_to'];
		$array['pmnew'] = $staff_row['pm'];
		$array['type'] = 'staff';
		$array['cid'] = $staff_row['contract_id'];
		$array['cmid'] = $staff_row['cmid'];
		$array['index'] = $staff_row['index'];
		$array['company_name'] = $staff_row['company_name'];
		$array['client_name'] = $staff_row['client_name'];
		$array['client_id'] = $staff_row['client_id'];
		$array['assigned_at'] = $staff_row['assigned_at'];
		$array['sid'] = $staff_row['staff_missionId'];
		return $array;
	}
	/* To format Sales Mission */
	function formatSalesMission($sales)
	{
		$array = array();
		$array['title'] = $sales['sales_title'];
		$array['lang'] = '-';
		$array['source_language'] = '-';
		$array['destination_language'] = '-';
		$days = 0;
		if($sales['final_mission_length_option']!='days')
		$days = ceil($sales['final_mission_length']/24);
		else
		$days = $sales['final_mission_length'];
		if($days == '')
			$days = 0;
		$array['edate'] = date('Y-m-d',strtotime($sales['assigned_at']." + $days days"));
		$array['edispdate'] = date('d/m/Y',strtotime($sales['assigned_at']." + $days days"));
		$array['ldate'] = $sales['assigned_at'];
		$array['qmid'] = "-";
		$array['turnover'] = "0";
		$array['percentage'] = $sales['progress_percent']."%";
		$array['currency'] = $sales['currency'];
		$array['pm'] = $sales['first_name']." ".$sales['last_name'];
		$array['pmid'] = $sales['assigned_to'];
		$array['pmnew'] = $array['pm'];
		$array['type'] = 'sales';
		$array['cid'] = $sales['contract_id'];
		$array['cmid'] = $sales['contractmissionid'];
		$array['index'] = "-";
		$array['company_name'] = $sales['company_name'];
		$array['client_name'] = $sales['client_name'];
		$array['client_id'] = $sales['client_id'];
		$array['assigned_at'] = $sales['assigned_at'];
		return $array;
	}
	/* To load Missions through Ajax */
	function loadmissionsAction()
	{
		if($this->_request->isPost() && $_SERVER['HTTP_X_REQUESTED_WITH']=='XMLHttpRequest')
		{
			$request = $this->_request->getParams();
			$con_mis_obj = new Ep_Quote_Quotecontract();
			$client_obj = new Ep_Quote_Client();	
			$prev_quote = "";
			//if($request['opened'])
			{
				$search = array();
				$search['assigned_to'] = $request['pmid'];
				$search['contract_id'] = $request['cid'];
				
				if($this->_view->user_type=='techuser' || $this->_view->user_type=='seouser' || $this->_view->user_type=='prodsubmanager' || $this->_view->user_type=='multilingue' || $this->_view->user_type=='salesuser' || $this->_view->user_type=='salesmanager' || $this->_view->user_type=='ceouser' || $this->_view->user_type=='facturation')
				{
					$prod_seo_tech = $this->missionslistUsers($search,$request['lang']);
				}
				elseif($this->_view->user_type=='techmanager' || $this->_view->user_type=='seomanager' || $this->_view->user_type=='prodmanager')
				{
					$prod_seo_tech = $this->missionslistManagers($search,$request['lang']);
				}
				elseif($this->_view->user_type=='superadmin')
				{
					$contract_missions = $con_mis_obj->getContractTechMissions($search);
			
					$contractMA  = array();
					foreach($contract_missions as $row)
					{
						$contractMA[$row['contract_id']][] = array('type_id'=>$row['type_id'],'cmid'=>$row['contractmissionid'],'type'=>$row['type'],'assigned_to'=>$row['assigned_to'],'assigned_at'=>$row['assigned_at'],'progress_percent'=>$row['progress_percent'],'cm_status'=>$row['cm_status'],'client_name'=>$row['client_name'],'client_id'=>$row['client_id']); 
					}
				
					$sales_seo_missions = $con_mis_obj->getSalesSeoMissionsContracts($search);
					
					$prev_quote = "";
					$techEntered = true;
					$prod_seo_tech = array();
							
					$prodIndex = $seoIndex = $techIndex = 0;
					
					foreach($sales_seo_missions as $row)
					{
						if(($prev_quote !="") && ($prev_quote != $row['quotecontractid']))
						{
							$prodIndex = $seoIndex = 0;
						}
						
						if($row['misson_user_type']=='sales' || ($row['misson_user_type']=='seo' && $row['product']!='seo_audit' && $row['product']!='smo_audit'))
						{
							$row['type'] = 'prod';
							$row['index'] = $prodIndex++;
						}
						else
						{
							$row['type'] = 'seo';
							$row['index'] = $seoIndex++;
						}
					
						if($row['contractmissionid'])
						{
							if($row['assigned_to'])
							{
							$userDetails = $client_obj->getQuoteUserDetails($row['assigned_to']);
							$row['pm'] = $userDetails[0]['first_name']." ".$userDetails[0]['last_name'];
							}
							else
							$row['pm'] = "";
							$formatted_row = $this->formatMission($row);
							if($request['lang']=="" || $request['lang']==$row['language_source'] || $request['lang']==$row['language_dest'])
							{
								if($row['cm_status']=='ongoing')
								$prod_seo_tech['opened'][$row['quotecontractid']][] = $formatted_row;
								elseif($row['cm_status']=='deleted')
								$prod_seo_tech['deleted'][$row['quotecontractid']][] = $formatted_row;
								else
								$prod_seo_tech['finished'][$row['quotecontractid']][] = $formatted_row;
							}
							if($row['assigned_to'])
							$prod_seo_tech['users'][$row['assigned_to']] = $row['pm'];
						}
						else
						{
							$row['pm'] =  "";
							$formatted_row = $this->formatMission($row);
							if($request['lang']=="" || $request['lang']==$row['language_source'] || $request['lang']==$row['language_dest'])
							$prod_seo_tech['to_assign'][$row['quotecontractid']][] = $formatted_row;
						}
						$prod_seo_tech['languages'][$row['language_source']] = $formatted_row[$row['language_source']];
						
						if($row['language_dest'])
							$prod_seo_tech['languages'][$row['language_dest']] = $formatted_row[$row['language_dest']];
						$prev_quote = $row['quotecontractid'];
					}
					
					$contracts = $con_mis_obj->getContracts(array('mulitple_status'=>"'validated','closed','deleted'",'cid'=>$search['contract_id']));
					foreach($contracts as $row)
					{
						$searchParameters = array();
						$searchParameters['quote_id']=$row['quoteid'];
						$searchParameters['include_final']='yes';
						$techMissionDetails=$con_mis_obj->getTechMissionDetails($searchParameters);
						$techIndex = 0;
						
						foreach($techMissionDetails as $tech_row)
						{
							$row['index'] = $techIndex++;
							$res = $this->checkContractMissionAssigned($contractMA,'tech',$row['quotecontractid'],$tech_row['identifier']);
						
							if($res)
							{
							$explode = explode('|',$res);
							if($explode[0])
							{
							$userDetails = $client_obj->getQuoteUserDetails($explode[0]);
							$row['pm'] = $userDetails[0]['first_name']." ".$userDetails[0]['last_name'];
							}
							else
							$row['pm'] = "";
							$row['cmid'] = $explode[1];
							$row['assigned_to'] = $explode[0];
							$tech_row['assigned_at'] = $explode[2];
							$tech_row['client_name'] = $explode[5];
							$tech_row['client_id'] = $explode[6];
							if($explode[4]=='ongoing')
								$prod_seo_tech['opened'][$row['quotecontractid']][] = $this->formatTechMission($tech_row,$row);
							elseif($explode[4]=='deleted')
								$prod_seo_tech['deleted'][$row['quotecontractid']][] = $this->formatTechMission($tech_row,$row);
							else
								$prod_seo_tech['finished'][$row['quotecontractid']][] = $this->formatTechMission($tech_row,$row);
								if($row['assigned_to'])
								$prod_seo_tech['users'][$row['assigned_to']] = $row['pm'];
							}
							else
							{
							$row['pm'] = "";
							$row['cmid'] = "";
							$tech_row['assigned_at'] = "";
							if($row['clfname'] || $row['cllname'])
							$tech_row['client_name'] = $row['clfname'] ." ". $row['cllname'];
							else
							$tech_row['client_name'] = $row['clemail'];
							$tech_row['client_id'] = $row['clientid'];
							$prod_seo_tech['to_assign'][$row['quotecontractid']][] = $this->formatTechMission($tech_row,$row); 
							}
						}
					}
					/* get staffing missions */
					$staffing_missions = $con_mis_obj->getStaffMissions(array('contract_id'=>$request['cid'],'assigned_to'=>$request['pmid']));
					if($staffing_missions)
					{
						$staffindex = 0;
						$prev_contract_id = "";
						foreach($staffing_missions as $row)
						{
							if($prev_contract_id == "" || $row['contract_id']!==$prev_contract_id)
								$staffindex = 0;
							else
								$staffindex++;
							$row['index'] = $staffindex;
							if($row['cmid'])
							{
								$row['pm'] = $row['first_name']." ".$row['last_name'];
								if($row['cm_status']=='ongoing')
									$prod_seo_tech['opened'][$row['contract_id']][] = $this->formatStaffMission($row);
								elseif($row['cm_status']=='deleted')
									$prod_seo_tech['deleted'][$row['contract_id']][] = $this->formatStaffMission($row);
								else
									$prod_seo_tech['finished'][$row['contract_id']][] = $this->formatStaffMission($row);
								if($row['assigned_to'])
								$prod_seo_tech['users'][$row['assigned_to']] = $row['pm'];
							}
							else
							{
								$row['pm'] = "";
								$row['cmid'] = "";
								$prod_seo_tech['to_assign'][$row['contract_id']][] = $this->formatStaffMission($row);
							}
							$prev_contract_id = $row['contract_id'];
						}
					}
					/* getting sales mission */
					$sales_mission = $con_mis_obj->getSalesMissions(array('contract_id'=>$request['cid'],'type'=>'sales','assigned_to'=>$request['pmid']));
					foreach($sales_mission as $row)
					{
						if($row['cm_status']=='ongoing')
						$prod_seo_tech['opened'][$row['contract_id']][] = $this->formatSalesMission($row);
						if($row['cm_status']=='deleted')
						$prod_seo_tech['deleted'][$row['contract_id']][] = $this->formatSalesMission($row);
						else
						$prod_seo_tech['finished'][$row['contract_id']][] = $this->formatSalesMission($row);
						if($row['assigned_to'])
						$prod_seo_tech['users'][$row['assigned_to']] = $row['first_name']." ".$row['last_name'];
					}
					
				}
				$this->_view->contractmissionsopened = (array)$prod_seo_tech['opened'];
				$this->_view->contractmissionstoassign = (array)$prod_seo_tech['to_assign'];
				$this->_view->contractmissionsdeleted = (array)$prod_seo_tech['deleted'];
				$this->_view->contractmissionsfinished = (array)$prod_seo_tech['finished'];
				$this->_view->opened = $request['opened'];
				
				if($request['opened']==3)
				{
					/* if($this->_view->user_type=='superadmin')
						$this->_view->pms = $con_mis_obj->getUsers("'techuser' OR type='seouser' OR type='prodsubmanager' OR type='multilingue'",'',true);
					elseif($this->_view->user_type=='techmanager')
						$this->_view->pms = $con_mis_obj->getUsers("techuser");
					elseif($this->_view->user_type=='seomanager')
						$this->_view->pms = $con_mis_obj->getUsers("seouser");
					elseif($this->_view->user_type=='prodmanager')
						$this->_view->pms = $con_mis_obj->getUsers("'prodsubmanager' OR type='multilingue'",'',true); */
						
					$this->_view->pms = $prod_seo_tech['users'];
					/* $this->_view->languages = $this->_arrayDb->loadArrayv2("EP_LANGUAGES", $this->_lang); */
					natsort($prod_seo_tech['languages']); 
					$this->_view->languages = $prod_seo_tech['languages'];
					$this->_view->contractlist = $con_mis_obj->getContracts(array('mulitple_status'=>"'validated','closed','deleted'"));
				}
				
				$this->render('missions-list-ajax');
			}
		}
	
	
		
	
		
	}
	
	//To update the contract name through Ajax
	function updateContractnameAction()
	{
		if($this->_request->isPost() && $_SERVER['HTTP_X_REQUESTED_WITH']=='XMLHttpRequest')
		{
			$request = $this->_request->getParams();
			$update = array();
			$update['contractname'] = utf8_decode($request['value']);
			$quote_contract = new Ep_Quote_Quotecontract();
			$quote_contract->updateContract($update,$request['pk']);
 		}
	}
	
	//To mark treated invoice files through Ajax
	function markTreatedAction()
	{
		if($this->_request->isPost() && $_SERVER['HTTP_X_REQUESTED_WITH']=='XMLHttpRequest')
		{
			$request = $this->_request->getParams();
			$invoice_ids = $request['ids'];
			$update = array();
			$update['is_treated'] = 1;
			$invoice_obj = new Ep_Quote_Invoice();
			foreach($invoice_ids as $key => $value)
			{
				$invoice_obj->updateInvoice($update,$value);
			}
		}
	}
	
	//To mark un treated invoice files through Ajax
	function markUntreatedAction()
	{
		if($this->_request->isPost() && $_SERVER['HTTP_X_REQUESTED_WITH']=='XMLHttpRequest')
		{
			$request = $this->_request->getParams();
			$invoice_ids = $request['ids'];
			$update = array();
			$update['is_treated'] = 0;
			$invoice_obj = new Ep_Quote_Invoice();
			foreach($invoice_ids as $key => $value)
			{
				$invoice_obj->updateInvoice($update,$value);
			}
		}
	}
	// To upload view final invoices by finance user
	function finalInvoiceAction()
	{
		$request = $this->_request->getParams();
		if($request['cid'])
		{
			$this->_view->cid = $request['cid'];
			$this->render('final-invoice');
		}
	}
	
	//Insert Final Invoice
	function insertFinalInvoiceAction()
	{
		if($this->_request->isPost())
		{
			$request = $this->_request->getParams();
			$invoice = array();
			$dir= $_SERVER['DOCUMENT_ROOT']."/BO/contract_mission_invoice/final_invoice/".$request['contract_id'];
			if(!is_dir($dir))
				mkdir($dir,TRUE);
			chmod($dir,0777);
			$document_name = $this->checkdocname(frenchCharsToEnglish($_FILES['finalfile']['name'],$dir));
			if(move_uploaded_file($_FILES['finalfile']['tmp_name'],$dir."/".$document_name))
				$invoice['file_path'] = 'final_invoice/'.$request['contract_id'].'/'.$document_name;

			$invoice['invoice_type'] = 'final';
			$invoice['invoice_number'] = $request['invoice_number'];
			$invoice['total_turnover'] = $request['turnover'];
			$invoice['contract_id'] = $request['contract_id'];
			//$invoice['client_id'] = $row['user_id'];
			//$invoice['cmid'] = $row['contractmissionid'];
			$invoice['created_at'] = date('Y-m-d H:i:s');
			$invoice['created_by'] = $this->adminLogin->userId;
			$invoice['comment'] = $request['comment'];
			$invoice['is_final'] = 1;
			
			
			$invoice_obj = new Ep_Quote_Invoice();
			$invoice_obj->insertInvoice($invoice);
			$this->_helper->FlashMessenger("Inserted final invoice successfully");
		}
		$this->_redirect('/contractmission/contract-sales-edit?submenuId=ML13-SL3&contract_id='.$invoice['contract_id']);
	}
	
	// To check already file exists or not in the document used in invoice 
	function checkdocname($document_name,$path)
	{
		$pathinfo = pathinfo($document_name);
		do
		{
			$document_name =$pathinfo['filename'].rand(1000,9999).".".$pathinfo['extension'];
			$document_name=str_replace(' ','_',$document_name);
			if(file_exists($path."/".$document_name))
			continue;
			else
			break;
		}
		while(true);
		return $document_name;
	}
	
	// To delete invoice 
	function deleteInvoiceAction()
	{
		if($this->_request->isPost() && $_SERVER['HTTP_X_REQUESTED_WITH']=='XMLHttpRequest')
		{
			$request = $this->_request->getParams();
			$invoice_ids = $request['ids'];
			$update = array();
			$update['archive'] = 1;
			$invoice_obj = new Ep_Quote_Invoice();
			foreach($invoice_ids as $key => $value)
			{
				$invoice_obj->updateInvoice($update,$value);
			}
		}
	}
	
	//To sort the array
	function sortTimewise($myArray)
	{
		usort($myArray,function($a,$b)
			{
				return $a['created_at'] > $b['created_at'];
			});
		return $myArray;
	}
	
	/* To get Contract Files */
	function getContractFiles($contract = array())
	{
		$documents_path=explode("|",$contract['contractfilepaths']);
		$documents_name=explode("|",$contract['contractcustomfilenames']);
	
		$files = '<table class="table">';
		foreach($documents_path as $k=>$file)
		{
			$file_path = $this->contract_documents_path.$documents_path[$k];
			if(file_exists($this->contract_documents_path.$documents_path[$k]) && !is_dir($this->contract_documents_path.$documents_path[$k]))
			{
				$zip = true;
				if($documents_name[$k])
					$file_name=$documents_name[$k];
				else
					$file_name=basename($file);
				$ofilename = pathinfo($file_path);
				$files .= '<tr><td width="30%">'.$file_name.'</td><td width="35%">'.substr($ofilename['filename'],0,-3).".".$ofilename['extension'].'</td><td width="20%">'.formatSizeUnits(filesize($file_path)).'</td><td>Contract</td><td align="center" width="15%"><a href="/contractmission/download-document?type=contract&contract_id='.$contract['contract_id'].'&index='.$k.'"><i style="margin-right:5px" class="splashy-download"></i></a><span class="delete" rel="'.$k.'_'.$contract['contract_id'].'"> <i class="icon-adt_trash"></i></span></td></tr>';	
				
			}
		}
		if($zip)
			$files .= '<thead><tr><td colspan="5"><a href="/contractmission/download-document?type=contract&index=-1&contract_id='.$contract['contract_id'].'" class="btn btn-small pull-right">Download Zip</a></td></tr></thead>';
		$files .='</table>';
		return $files;
	}
	
	/* To get Tech Files */
	function getTechFiles($mission = array())
	{
		$exploded_file_paths = array_filter(explode("|",$mission['documents_path']));
		$exploded_file_names = explode("|",$mission['documents_name']);
		$zip = "";
		
		$files = '<table class="table">';
		$k=0;
		if($mission['delete']):
		foreach($exploded_file_paths as $row)
		{
			$file_path=$this->mission_documents_path.$row;
			if(file_exists($file_path) && !is_dir($file_path))
			{
				$zip = true;
				$fname = $exploded_file_names[$k];
				if($fname=="")
					$fname = basename($row);
				$ofilename = pathinfo($file_path);
				$files .= '<tr><td width="30%">'.$fname.'</td><td width="35%">'.substr($ofilename['filename'],0,-3).".".$ofilename['extension'].'</td><td width="20%">'.formatSizeUnits(filesize($file_path)).'</td><td>Tech</td><td align="center" width="15%"><a href="/quote/download-document?type=tech_mission&mission_id='.$mission['id'].'&index='.$k.'"><i style="margin-right:5px" class="splashy-download"></i></a><span class="deletetech" rel="'.$k.'_'.$mission['id'].'"> <i class="icon-adt_trash"></i></span></td></tr>';	
			}
			$k++;
		}
		else:
		foreach($exploded_file_paths as $row)
		{
			$file_path=$this->mission_documents_path.$row;
			if(file_exists($file_path) && !is_dir($file_path))
			{
				$zip = true;
				$fname = $exploded_file_names[$k];
				if($fname=="")
					$fname = basename($row);
				$ofilename = pathinfo($file_path);
				$files .= '<tr><td width="30%">'.$fname.'</td><td width="35%">'.substr($ofilename['filename'],0,-3).".".$ofilename['extension'].'</td><td width="20%">'.formatSizeUnits(filesize($file_path)).'</td><td>Tech</td><td align="center" width="15%"><a href="/quote/download-document?type=tech_mission&mission_id='.$mission['id'].'&index='.$k.'"><i style="margin-right:5px" class="splashy-download"></i></a></td></tr>';	
			}
			$k++;
		}
		endif;			
		if($zip)
			$zip = '<thead><tr><td colspan="5"><a href="/quote/download-document?type=tech_mission&index=-1&mission_id='.$mission['id'].'" class="btn btn-small pull-right">Download Zip</a></td></tr></thead>';
		$files .=$zip."</table>";
		return $files;
	}
	
	/* To get SEO files */
	function getSeoFiles($mission=array())
	{
		$exploded_file_paths = array_filter(explode("|",$mission['documents_path']));
		$exploded_file_names = explode("|",$mission['documents_name']);
		$zip = "";
		
		
		
		$files = '<table class="table">'.$zip;
		$k=0;
		if($mission['delete']):
		foreach($exploded_file_paths as $row)
		{
			$file_path=$this->mission_documents_path.$row;
			if(file_exists($file_path) && !is_dir($file_path))
			{
					$zip = true;
					$fname = $exploded_file_names[$k];
					if($fname=="")
						$fname = basename($row);
					$ofilename = pathinfo($file_path);
					$files .= '<tr><td width="30%">'.$fname.'</td><td width="35%">'.substr($ofilename['filename'],0,-3).".".$ofilename['extension'].'</td><td width="20%">'.formatSizeUnits(filesize($file_path)).'</td><td>SEO</td><td align="center" width="15%"><a href="/quote/download-document?type=seo_mission&mission_id='.$mission['id'].'&index='.$k.'"><i style="margin-right:5px" class="splashy-download"></i></a><span class="delete" rel="'.$k.'_'.$mission['id'].'"> <i class="icon-adt_trash"></i></span></td></tr>';	
					
			}
			$k++;
		} 
		
		else:
		foreach($exploded_file_paths as $row)
		{
			$file_path=$this->mission_documents_path.$row;
			if(file_exists($file_path) && !is_dir($file_path))
			{
					$zip = true;
					$fname = $exploded_file_names[$k];
					if($fname=="")
						$fname = basename($row);
					$ofilename = pathinfo($file_path);
					$files .= '<tr><td width="30%">'.$fname.'</td><td width="35%">'.substr($ofilename['filename'],0,-3).".".$ofilename['extension'].'</td><td width="20%">'.formatSizeUnits(filesize($file_path)).'</td><td>SEO</td><td align="center" width="15%"><a href="/quote/download-document?type=seo_mission&mission_id='.$mission['id'].'&index='.$k.'"><i style="margin-right:5px" class="splashy-download"></i></a></td></tr>';	
					
			}
			$k++;
		} 
		
		endif;
		if($zip)
			$zip = '<thead><tr><td colspan="5"><a href="/quote/download-document?type=seo_mission&index=-1&mission_id='.$mission['id'].'" class="btn btn-small pull-right">Download Zip</a></td></tr></thead>';
		$files .= $zip.'</table>';
		return $files;
	}
	/* Add new staffing mission */
	function addNewStaffmissionAction()
	{
		$request = $this->_request->getParams();
		if($this->_request->isPost() && $request['quoteid']):
			$contract_obj = new Ep_Quote_Quotecontract();
			$contractdetails = $contract_obj->getContract($request['contractid']);
			$quote_obj = new Ep_Quote_Quotes();
			$status = $quote_obj->getQuoteDetails($request['quoteid']);			
			if($status):
				$save = array();
				if($request['before_prod'])
				$save['before_prod'] = 'yes';
				if($request['price']!='new')
				$save['cost'] = $request['price'];
				$save['created_by'] = $this->_view->loginuserId;
				$save['currency'] = $status[0]['sales_suggested_currency'];
				//$save['comments'] = $request['comment'];
				$save['contract_id'] = $request['contractid'];
				$save['mission_id'] = $request['missionid'];
				$save['created_at'] = date('Y-m-d H:i:s');
				$save['delivery_time'] = $request['staff_time'];
				$save['delivery_option'] = $request['staff_time_option'];
				$res = $contract_obj->insertStaffMission($save);
				/* Updating Files */
				if(count($_FILES['staff_documents']['name'])>0)	
				{
					$update = false;
					$uploaded_documents = array();
					$uploaded_document_names = array();
					$k = 0;
					$missionIdentifier = $res;
					foreach($_FILES['staff_documents']['name'] as $row):
					if($_FILES['staff_documents']['name'][$k])
					{
						$missionDir=$this->mission_documents_path.$missionIdentifier."/";
						if(!is_dir($missionDir))
							mkdir($missionDir,TRUE);
							chmod($missionDir,0777);
						$document_name=frenchCharsToEnglish($_FILES['staff_documents']['name'][$k]);
						$document_name=str_replace(' ','_',$document_name);
						$pathinfo = pathinfo($document_name);
						$document_name =$pathinfo['filename'].rand(100,1000).".".$pathinfo['extension'];
						$document_path=$missionDir.$document_name;
						if(move_uploaded_file($_FILES['staff_documents']['tmp_name'][$k],$document_path))
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
						$result =$contract_obj->getStaffMissionDetails(array('staff_missionId'=>$missionIdentifier));
						$uploaded_documents1 = explode("|",$result[0]['documents_path']);
						$uploaded_documents =array_merge($uploaded_documents,$uploaded_documents1);
						$seo_mission_data['documents_path'] = implode("|",$uploaded_documents);
						$document_names =explode("|",$result[0]['documents_name']);
						$document_names =array_merge($uploaded_document_names,$document_names);
						$seo_mission_data['documents_name'] = implode("|",$document_names);
						$contract_obj->updateStaffMission($seo_mission_data,$missionIdentifier);
					}
				}
				$this->_helper->FlashMessenger('Added new Staffing Mission Successfully');
			endif;
		endif;
	//	$this->_redirect("/contractmission/assign-mission?submenuId=ML13-SL4&contract_id=$request[contractid]&type=prod&index=$request[index]");
		$this->_redirect("/contractmission/missions-list?submenuId=ML13-SL4&contract_id=$request[contractid]&active=validate");
	}
	/* To delete staff documents */
	function deleteDocumentStaffAction()
	{
		if($this->_request->isPost() && $_SERVER['HTTP_X_REQUESTED_WITH']=='XMLHttpRequest')
		{
			$parmas = $this->_request->getParams();
			$explode_identifier = explode("_",$parmas['identifier']);
			$offset = $explode_identifier[0];
			$identifier = $explode_identifier[1];
			$contract_obj=new Ep_Quote_Quotecontract();
			$result = $contract_obj->getStaffMissionDetails(array('staff_missionId'=>$missionIdentifier));
			$documents_paths = explode("|",$result[0]['documents_path']);
			$documents_names = explode("|",$result[0]['documents_name']);
			unlink($this->mission_documents_path.$documents_paths[$offset]);
			unset($documents_paths[$offset]);
			unset($documents_names[$offset]);
			$data['documents_path']	= implode("|",$documents_paths);
			$data['documents_name']	= implode("|",$documents_names);
			$contract_obj->updateStaffMission($data,$identifier);
			$documents_paths = array_filter(array_values($documents_paths));
			$documents_names = array_values($documents_names);
			$zip = "";
			$files = '<table class="table">';
			$zip_req = $parmas['zip_req'];
			$k=0;
			foreach($documents_paths as $row)
			{
				$file_path=$this->mission_documents_path.$row;
				if(file_exists($file_path) && !is_dir($file_path))
				{
					$zip = true;
                    $fname = $documents_names[$k];
					if($fname=="")
						$fname = basename($row);
					$ofilename = pathinfo($file_path);
					//$files .= '<div class="topset2"><a href="/quote/download-document?type=tech_mission&mission_id='.$identifier.'&index='.$k.'">'.$fname.'</a><span class="deletetech" rel="'.$k.'_'.$identifier.'"> <i class="splashy-error_x"></i></span></div>';
					$files .= '<tr><td width="30%">'.$fname.'</td><td width="35%">'.substr($ofilename['filename'],0,-3).".".$ofilename['extension'].'</td><td width="20%">'.formatSizeUnits(filesize($file_path)).'</td><td>Staff</td><td align="center" width="15%"><a href="/quote/download-document?type=staff_mission&mission_id='.$identifier.'&index='.$k.'"><i style="margin-right:5px" class="splashy-download"></i></a><span class="deletestaff" rel="'.$k.'_'.$identifier.'"> <i class="icon-adt_trash"></i></span></td></tr>';	
				}
				$k++;
			}	
			if($zip && !$zip_req)
				$zip = '<thead><tr><td colspan="5"><a href="/quote/download-document?type=cm_staff&index=-1&mission_id='.$identifier.'" class="btn btn-small pull-right">Download Zip</a></th></tr></thead>';
			$files .=$zip.'</table>';
			echo $files;
		}
	}
	/* To add Sales mission */
	function addNewSalesmissionAction()
	{
		$params = $this->_request->getParams();
		if($this->_request->isPost() && $params['contractid'] && $params['quote_id'])
		{
			$save['contract_id'] = $params['contractid'];
			$save['type'] = 'sales';
			$save['comment'] = "";
			$save['assigned_by'] = $this->_view->loginuserId;
			$save['updated_by'] = $this->_view->loginuserId;
			$save['updated_at'] = date('Y-m-d H:i:s');
			$quote_obj = new Ep_Quote_Quotes();
			$quote_contract = new Ep_Quote_Quotecontract();
			$quoteres = $quote_obj->getQuoteDetails($params['quote_id']);
			$quoteDetails = $quoteres[0];
			//$save['assigned_to'] = $params['sales_creator_id'];
			$save['assigned_to'] = $quoteDetails['quote_by'];
			$save['sales_title'] = "Sales $params[sales_owner] mission";
			$save['currency'] = $quoteDetails['sales_suggested_currency'];
			$cmid = $quote_contract->insertContractMission($save);
			$this->_helper->FlashMessenger('Added sales mission successfully');
		}
		$this->_redirect('/contractmission/contract-edit?contract_id='.$params['contractid'].'&submenuId=ML13-SL3');
	}

    /* To add a new mission in Contract edit */
    function addMissionAction()
    {
        $request = $this->_request->getParams();
        //if($this->_request->isPost && $_SERVER['HTTP_X_REQUESTED_WITH']=='XMLHttpRequest')
        {
            //ALL language list
            $language_array=$this->_arrayDb->loadArrayv2("EP_LANGUAGES", $this->_lang);
            natsort($language_array);
            $this->_view->ep_language_list=$language_array;
            $quote_mission_obj = new Ep_Quote_QuoteMissions();
            $quote_contract = new Ep_Quote_Quotecontract();
            $prod_mission = new Ep_Quote_ProdMissions();
            //$mission_res = $quote_mission_obj->getMissionDetails(array('mission_id'=>$request['mid']));
            $mission_res = $quote_mission_obj->getMissionDetailsContract(array('mission_id'=>$request['mid'],'contract_id'=>$request['contract_id']));
            $this->_view->mission_res = $mission_res[0];

            $prod_mission_details = $prod_mission->getProdMissionDetails(array('quote_mission_id'=>$request['mid']));
            $contract_details = $quote_contract->getContract($request['contract_id']);
            $this->_view->contract_details = $contract_details[0];
            $this->_view->prod_mission_writing = $prod_mission_details[0];
            $this->_view->prod_mission_correction = $prod_mission_details[1];
            $this->_view->cid = $request['contract_id'];
            $this->_view->mid = $request['mid'];
            $this->_view->cmid = $request['cmid'];
            $this->_view->reqaction = $request['reqaction'];
            $this->render("add-mission");
        }
    }
    /* To delete mission in Contract edit */
    function deleteMissionAction()
    {
        if($this->_request->isPost() && $_SERVER['HTTP_X_REQUESTED_WITH']=='XMLHttpRequest')
        {
            $params = $this->_request->getParams();
            if($params['cmid'])
            {
                if($params['maction']=="delete")
                {
                    $update = array();
                    $update['is_deleted'] = 1;
                    $update['cm_status'] = 'deleted';
                    $update['updated_at'] = date("Y-m-d H:i:s");
                    $update['updated_by'] = $this->adminLogin->userId;
                    $quote_contract = new Ep_Quote_Quotecontract();
                    echo $quote_contract->updateContractMission($update,$params['cmid']);
                    $this->_helper->FlashMessenger('Deleted mission Successfully');
                }
            }
        }
    }
    /* To freeze mission in contract edit invoice area */
    function freezeMissionAction()
    {
        if($this->_request->isPost() && $_SERVER['HTTP_X_REQUESTED_WITH']=='XMLHttpRequest')
        {
            $params = $this->_request->getParams();
            if($params['cmid'] || $params['mid'])
            {
                $cmdetails = array();
                if($params['cmid'])
                {
                    $quoteContract_obj = new Ep_Quote_Quotecontract();
                    $cmdetails = $quoteContract_obj->getContractMission('','','',$params['cmid']);
                    $cmdetails = $cmdetails[0];
                    $searchParameters['mission_id']= $cmdetails['type_id'];
                }
                else
                {
                    $searchParameters['mission_id']= $params['mid'];
                }
                //getting mission details
                $searchParameters['misson_user_type']='sales';

                $quoteMission_obj=new Ep_Quote_QuoteMissions();

                $missonDetails=$quoteMission_obj->getMissionDetails($searchParameters);

                if($missonDetails)
                {
                    $m=0;
                    foreach($missonDetails as $mission)
                    {
                        $missonDetails[$m]['product_name']=$this->product_array[$mission['product']];
                        $missonDetails[$m]['language_source_name']=$this->getCustomName("EP_LANGUAGES",$mission['language_source']);
                        $missonDetails[$m]['product_type_name']=$this->producttype_array[$mission['product_type']];
                        if($mission['language_dest'])
                            $missonDetails[$m]['language_dest_name']=$this->getCustomName("EP_LANGUAGES",$mission['language_dest']);

                        $quoteDetails[$q]['missions_list'][$mission['identifier']]='Mission '.($m+1).' - '.$missonDetails[$m]['product_name'];

                        $missonDetails[$m]['comment_time']=time_ago($mission['created_at']);


                        //Get seo missions related to a mission
                        $searchParameters['quote_id']=$quote_id;
                        $searchParameters['misson_user_type']='seo';
                        $searchParameters['related_to']=$mission['identifier'];
                        $searchParameters['product']=$mission['product'];
                        //echo "<pre>";print_r($searchParameters);
                        $quoteMission_obj=new Ep_Quote_QuoteMissions();

                        $prodMissionObj=new Ep_Quote_ProdMissions();

                        $searchParameters['quote_mission_id']=$mission['identifier'];
                        $prodMissionDetails=$prodMissionObj->getProdMissionDetails($searchParameters);
                        //echo "<pre>";print_r($prodMissionDetails);exit;

                        if($prodMissionDetails)
                        {
                            foreach($prodMissionDetails as $key=>$details)
                            {
                                $client_obj=new Ep_Quote_Client();
                                $bo_user_details=$client_obj->getQuoteUserDetails($details['created_by']);
                                $prodMissionDetails[$key]['prod_user_name']=$bo_user_details[0]['first_name'].' '.$bo_user_details[0]['last_name'];
                                $prodMissionDetails[$key]['comment_time']=time_ago($details['created_at']);
                            }
                            $missonDetails[$m]['prod_mission_details']=$prodMissionDetails;
                        }
                        $m++;
                    }
                    $this->_view->prodMissionDetails=$missonDetails;
                    $this->_view->mission_type='prod';
                    $cmdetails['freeze_start_date'] = $cmdetails['freeze_start_date']?date('d/m/Y',strtotime($cmdetails['freeze_start_date'])):date('d/m/Y');
                    //$cmdetails['freeze_end_date'] = $cmdetails['freeze_end_date']?date('d/m/Y',strtotime($cmdetails['freeze_end_date'])):date('d/m/Y',strtotime('+1 day'));
                    $cmdetails['freeze_end_date'] = $cmdetails['freeze_end_date']?date('d/m/Y',strtotime($cmdetails['freeze_end_date'])):"";
                    $cmdetails['freeze_email_date'] = $cmdetails['freeze_email_date']?date('d/m/Y H:i:s',strtotime($cmdetails['freeze_email_date'])):"";
                    $this->_view->cmdetails = $cmdetails;
                    $this->_view->freezeaction = $params['freeze_action'];
                    //echo "<pre>";print_r($this->_view->prodMissionDetails);
                }
                $this->render("freeze-mission");
            }
        }
    }
    /* Save freeze mission */
    function freezeSaveAction()
    {
        $request = $this->_request->getParams();
        if($this->_request->isPost() && $request['cmid'])
        {
            $update = array();
            $start_date = explode('/',$request['freeze_start']);
            $end_date = explode('/',$request['freeze_end']);
            $freeze_email_date_time = explode(' ',$request['freeze_email_date']);
            $freeze_email_dates = explode('/',$freeze_email_date_time[0]);
            $update['freeze_start_date'] = $start_date[2]."-".$start_date[1]."-".$start_date[0];
            $update['freeze_end_date'] = $end_date[2]."-".$end_date[1]."-".$end_date[0];
            $update['freeze_email_date'] = $freeze_email_dates[2]."-".$freeze_email_dates[1]."-".$freeze_email_dates[0]." ".$freeze_email_date_time[1];
            $update['freeze_subject'] = $request['freeze_subject'];
            $update['freeze_email_content'] = $request['freeze_email_content'];
            $quote_contract = new Ep_Quote_Quotecontract();
            $quote_contract->updateContractMission($update,$request['cmid']);
            $this->_helper->FlashMessenger('Freezed mission Successfully');
        }
        $this->_redirect('/contractmission/contract-edit?submenuId=ML13-SL3&contract_id='.$request['cid'].'&action=view');
    }
    /* To send freeze mail through cron currently not in use should be kept in cron */
    function sendFreezeMailAction()
    {
        $quote_contract_obj = new Ep_Quote_Quotecontract();
        $mail_obj = new Ep_Message_AutoEmails();
        $missions = $quote_contract_obj->getFreezeMissions();
        foreach($missions as $row)
        {
            $mail_obj->sendEMail('',$row['freeze_email_content'],$row['email'],$row['freeze_subject']);
        }
    }
    // Insert new mission from contract edit
    function saveMissionAction()
    {
        if($this->_request->isPost())
        {
            $request = $this->_request->getParams();
            $save = array();
            $save['quote_id'] = $request['quote_id'];
            $save['product'] = $request['product'];
            $save['product_type'] = $request['producttype'];
            if(!empty($request['producttypeother']))
                $save['product_type_other'] = $request['producttypeother'];
            $save['language_source'] = $request['language'];
            if($save['product']=="translation")
                $save['language_dest'] = $request['languagedest'];
            $save['sales_suggested_missions'] = $request['sales_suggested_missions'];
            $save['category'] = $request['category'];
            $save['nb_words'] = $request['nb_words'];
            $save['volume'] = $request['volume'];
            $save['comments'] = $request['comments'];
            $save['misson_user_type'] = 'sales';
            $save['created_by'] = $this->adminLogin->userId;
            $save['created_at'] = date("Y-m-d H:i:s");
            $save['mission_length'] = $request['mission_length'];
            $save['mission_length_option'] = $request['mission_length_option'];
            $save['unit_price'] = $request['unit_price'];
            $save['margin_percentage'] = $request['margin_percentage'];
            $save['internal_cost'] = $request['internalcost'];
            $save['include_final'] = 'yes';
            $save['package'] = 'lead';
            $save['volume_max'] = $request['volume_max'];
            $save['tempo'] = $request['tempo'];
            $save['delivery_volume_option'] = $request['delivery_volume_option'];
            $save['tempo_length'] = $request['tempo_length'];
            $save['tempo_length_option'] = $request['tempo_length_option'];
            $save['oneshot'] = $request['oneshot'];
            $save['demande_client'] = $request['demande_client'];
            $save['duration_dont_know'] = $request['duration_dont_know'];
            $save['staff_time'] = $request['staff_time'];
            $save['staff_time_option'] = $request['staff_time_option'];
            $save['free_mission'] = $request['free_mission'];

            $quoteMission_obj=new Ep_Quote_QuoteMissions();
            $contract_obj = new Ep_Quote_Quotecontract();
            $prod_mission_obj = new Ep_Quote_ProdMissions();
            if($request['reqaction']=="edit")
            {
                if($request['package']=="team")
                {
                    $save['team_fee'] = $request['team_fee'];
                    $save['team_packs'] = $request['team_packs'];
                }
                else if($request['package']=="user")
                {
                    $save['user_fee'] = $request['user_fee'];
                }
                $save['package'] = $request['package'];
                $save['is_edited'] = 1;
                $save['updated_at'] = date("Y-m-d H:i:s");
                $quoteMission_obj->updateQuoteMission($save,$request['mid']);
                $prod_mission = array('staff'=>$request['prod_mission_writing_staff'],'cost'=>$request['prod_mission_writing_cost']);
                $prod_mission_obj->updateProdMission($prod_mission,$request['prod_mission_writing']);
                $prod_mission = array('staff'=>$request['prod_mission_correction_staff'],'cost'=>$request['prod_mission_correction_cost']);
                $prod_mission_obj->updateProdMission($prod_mission,$request['prod_mission_correction']);
                /*added by naseer on 17-11-2015*/
                $cm_data = array('cm_turnover'=> $request['mission_turnover'],'edited_at' => date("Y-m-d H:i:s"),'edited_by'=>$this->adminLogin->userId);
                $contract_obj->updateContractMission($cm_data,$request['cmid']);
                $this->_helper->FlashMessenger('Updated mission successfully');
            }
            else
            {
                $save['turnover'] = $request['mission_turnover'];
                $save['from_contract'] = 1;
                $save['reference_mission_id'] = $request['mid'];
                $save['created_at'] = date("Y-m-d H:i:s");
                $save['created_by'] = $this->adminLogin->userId;
                $quoteMission_obj->insertQuoteMission($save);
                $prod_mission_details = $prod_mission_obj->getProdMissionDetails(array('quote_mission_id'=>$request['mid']));
                $prod_mission_details[1]['quote_mission_id'] = $prod_mission_details[0]['quote_mission_id'] = $quoteMission_obj->missionIdentifier;
                $prod_mission_details[0]['staff'] = $request['prod_mission_writing_staff'];
                $prod_mission_details[1]['staff'] = $request['prod_mission_correction_staff'];
                $prod_mission_details[0]['cost'] = $request['prod_mission_writing_cost'];
                $prod_mission_details[1]['cost'] = $request['prod_mission_correction_cost'];
                $prod_mission_details[0]['created_at'] = $prod_mission_details[1]['created_at'] = date("Y-m-d H:i:s");
                $prod_mission_details[0]['created_by'] = $prod_mission_details[1]['created_by'] = $this->adminLogin->userId;
                $prod_mission_obj->insertProdMission($prod_mission_details[0]);
                $prod_mission_obj->insertProdMission($prod_mission_details[1]);
                $this->_helper->FlashMessenger('Inserted mission successfully');
            }
            if($request['total_turnover']!=$request['total_turnover_org'])
            {
                $update = array();
                $update['turnover'] = $request['total_turnover'];
                $update['old_turnover'] = $request['total_turnover_org'];
                $contract_obj->updateContract($update,$request['cid']);
            }
            $this->_redirect('/contractmission/contract-edit?submenuId=ML13-SL3&contract_id='.$request['cid'].'&action=view');
        }
    }
    /* Import Quote and Quotemissions through xls */
        
        function importQuoteAction()
        {
            ini_set('max_execution_time', 300);
            $document_name = "Quote-Quotemissions-FR14.10.xlsx";
	    $document_path= $_SERVER['DOCUMENT_ROOT'].'/BO/oldmissionsupload/'.$document_name;
            $data = $this->xlsxread($document_path);
            $quote_details = $data[0][0];
            //echo "<PRE>";print_r($quote_details);exit;
			
            $quote_obj=new Ep_Quote_Quotes();
            $category = $quoteIdentifier = $currency = "";
            if(count($data[0][0])>1)
            {
                for($i=1;$i<count($quote_details);$i++)
                {
                   $quotes = array();
                   $quotes['title'] = $quote_details[$i][2];
                   $quotes['client_id'] = $quote_details[$i][3];
                   $category = $quotes['category'] = $quote_details[$i][4];
                   $quotes['websites']=$quote_details[$i][5];
                   $quotes['quote_by']= $this->adminLogin->userId;
                   $quotes['created_by']= $this->adminLogin->userId;
                   $quotes['sales_suggested_price']= $quote_details[$i][6];
                   $currency = $quotes['sales_suggested_currency']= $quote_details[$i][7];
                   if(trim($quotes['sales_suggested_currency'])=="euro")
                       $quotes['conversion'] = $quote_details[$i][8];
                   $quotes['sales_comment']= $quote_details[$i][9];
                   $quotes['client_email_text']= $quote_details[$i][10];
                   $quotes['client_know']= $quote_details[$i][11];
                   $quotes['urgent']= $quote_details[$i][12];
                   $quotes['urgent_comments']= $quote_details[$i][13];
                   $quotes['client_aims']= strtolower($quote_details[$i][14]);
                   if($quote_details[$i][15])
                   $quotes['client_prio']= $quote_details[$i][15];
                   else
                   $quotes['client_prio']= "1";
                   $quotes['client_aims_comments']= $quote_details[$i][16];
                   if(trim($quote_details[$i][17])=='yes')
                    {
                         $quotes['agency']='';
                         $quotes['agency_name']= $quote_details[$i][18];
                    }
                    
                    $quotes['client_internal_team']= $quote_details[$i][19];

                   $quotes['estimate_sign_percentage']= $quote_details[$i][20];
                    $explode = explode("-",$quote_details[$i][21]);
                    
                   $date = "20".$explode[2]."-".$explode[0]."-".$explode[1];
                   $quotes['estimate_sign_date']= $date;
                   $quotes['estimate_sign_comments']= $quote_details[$i][22];
                   $quotes['version']= 1;
                    if(trim($quote_details[$i][23]) == 'dont_know')
                    {
                            $quotes['budget_marketing']= 'dont_know';
                            $quotes['budget']='';
                            $quotes['budget_currency']='';
                    }
                    else
                    {
                            $quotes['budget_marketing']= '';
                             $explode = explode("/",$quote_details[$i][23]);
                            
                            $quotes['budget']= $explode[0];
                            $quotes['budget_currency']= $explode[1];
                    }
                   $quotes['quote_send_team'] = $quote_details[$i][24];
                   //if($quotes['quote_send_team']=="send_sales_team")
                   {
                       $quotes["sales_validation_expires"]=time()+($this->configval['sales_validation_timeline']*60*60);
                       $quotes['tec_review']='auto_skipped';	
                       $quotes['seo_review']='auto_skipped';
                       $quotes['prod_review']='validated';
                       $quotes['sales_review']='not_done';
                       $quotes['created_at']=date("Y-m-d H:i:s");
                       $quote_obj->insertQuote($quotes);
                       //echo "<PRE>";
                       //print_r($quotes);
                       $quoteIdentifier=$quote_obj->getIdentifier();	
                   }
                }
            }
            $quote_mission_details = $data[0][1];
            //print_r($quote_mission_details);
            if(count($data[0][0])>1)
            {
                for($i=1;$i<count($quote_mission_details);$i++)
                {
                    $quoteMission_data = array();
                    $quoteMission_data['quote_id']=$quoteIdentifier;
                    $quoteMission_data['product']=$quote_mission_details[$i][2];
					if($quoteMission_data['product'])
					{
						$quoteMission_data['product_type']=$quote_mission_details[$i][3];
						if($quoteMission_data['product_type']=='autre')
							$quoteMission_data['product_type_other']=$quote_mission_details[$i][4];
						else
							$quoteMission_data['product_type_other']=NULL;
						$quoteMission_data['category']= $category;
						if(trim($quoteMission_data['product'])=='translation')
								$quoteMission_data['language_dest']= $quote_mission_details[$i][6];
						if(trim($quoteMission_data['product'])!='auture')
						{
								$quoteMission_data['language_source']= $quote_mission_details[$i][5];
								$quoteMission_data['nb_words']= $quote_mission_details[$i][7];
								$quoteMission_data['volume']= $quote_mission_details[$i][8];
						}	
						$quoteMission_data['comments'] = $quote_mission_details[$i][9];
						$quoteMission_data['misson_user_type'] = 'sales';
						$quoteMission_data['created_by'] = $this->adminLogin->userId;
						/*added w.r.t tempo*/
						$quoteMission_data['mission_length']=  $quote_mission_details[$i][10];
						$quoteMission_data['mission_length_option']= "days";						
						$quoteMission_data['volume_max']=  $quote_mission_details[$i][12];
						$quoteMission_data['delivery_volume_option']=  $quote_mission_details[$i][14];
						$quoteMission_data['tempo']=  $quote_mission_details[$i][13];
						$quoteMission_data['tempo_length']=  $quote_mission_details[$i][15];
						$quoteMission_data['tempo_length_option']=  $quote_mission_details[$i][16];
						$quoteMission_data['oneshot']=  $quote_mission_details[$i][11];
						$quoteMission_data['unit_price']= $quote_mission_details[$i][18];					
						$explode = explode(",",$quote_mission_details[$i][20]);
						$quoteMission_data['created_at']=date("Y-m-d H:i:s");
						$quoteMission_data['internal_cost'] = (float)$explode[0] + (float)$explode[1];
						
						$quoteMission_data['margin_percentage']= number_format((100*(1-$quoteMission_data['internal_cost']/$quoteMission_data['unit_price'])),2,'.','');
						
						$quoteMission_data['staff_time']= $quote_mission_details[$i][22];
						$quoteMission_data['staff_time_option']= "days";
						
						
						$quoteMission_obj=new Ep_Quote_QuoteMissions();
						//echo "<PRE>";
						//print_r($quoteMission_data);exit;
						$quoteMission_obj->insertQuoteMission($quoteMission_data);
						$quote_mission_id = $quoteMission_obj->missionIdentifier;
						$quote_mission_details[$i][21] = str_replace(".", ",", $quote_mission_details[$i][21]);
						$nbwriterproff = explode(",",$quote_mission_details[$i][21]);
						for($k=0;$k<count($explode);$k++)
						{
							if($k==0)
							{
								$this->formatProdmission($quote_mission_id,$explode[$k],"redaction",$quote_mission_details[$i],$nbwriterproff[$k],$currency);
							}
							else
							{
								$this->formatProdmission($quote_mission_id,$explode[$k],"proofreading",$quote_mission_details[$i],$nbwriterproff[$k],$currency);
								break;
							}
						}
					}	
                    //print_r($quote_mission_details[$i]);
                }
            }
           // print_r($quote_mission_details);
        }
        
        function formatProdmission($quote_mission_id,$cost,$type,$quote_mission_details,$staff,$currency)
        {
            $prod_mission_obj=new Ep_Quote_ProdMissions();
            $prod_mission_data['quote_mission_id']= $quote_mission_id;
            $prod_mission_data['product']= $type;						    			
            $prod_mission_data['delivery_time']= "";
            $prod_mission_data['delivery_option']= "days";
            $prod_mission_data['staff']= $staff;
            $prod_mission_data['staff_time']= $quote_mission_details[22];
            $prod_mission_data['staff_time_option']= "days";
            $prod_mission_data['cost']= $cost;
            $prod_mission_data['currency']= $currency;
            $prod_mission_data['comments']= "";
            $prod_mission_data['created_by']=$this->adminLogin->userId;
            $prod_mission_data['version']	= 1;
            $prod_mission_obj->insertProdMission($prod_mission_data);
        }
}

?>