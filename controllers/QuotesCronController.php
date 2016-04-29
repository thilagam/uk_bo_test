<?php
/**
 * @package Controller
 * @version 1
 */
 
 class QuotesCronController extends Zend_Controller_Action
 {
	//var $url="http://".$_SERVER['HTTP_HOST'];
	var $mail_from = "work@edit-place.com";
	var $work_mail_from = "work@edit-place.com";
	protected $_arrayDb;
	public function init()
	{
	    parent::init();
		$this->producttype_array=array(
    							"article_de_blog"=>"Article de blog",
								"descriptif_produit"=>"Desc.Produit",
								"article_seo"=>"Article SEO",
								"guide"=>"Guide",
								"news"=>"News",
								"autre"=>"Autres"
        						);
		$this->seo_product_array=array(
        						"seo_audit"=>"SEO audit",
        						"smo_audit"=>"SMO audit",
    							"redaction"=>"R&eacute;daction",
								"translation"=>"Traduction",
								"proofreading"=>"Correction",
								"autre"=>"Autre"
        						);
       $this->closedreason = array(
								"too_expensive"=>'Trop cher',
								"no_reason_client"=>'Pas de r&eacute;ponse du client',
								"project_cancelled"=>'Projet annul&eacute;',
								"delivery_time_long"=>'D&eacute;lai livraison trop long',
								"test_art_prob"=>'Probl&egrave;me article test',
								"quote_permanently_lost"=>'Devis d&eacute;finitivement perdu'
							);
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
		$this->_view->duration_array=array(
							"days"=>"Days"
						);	
		$this->_view->volume_option_array=$this->volume_option_array=array(
							"every"=>"every",
							"within"=>"within"
						);		
		$this->_arrayDb = Zend_Registry::get('_arrayDb');
	}
 
	function getCustomName($type,$name)
	{
		$categories_array = $this->_arrayDb->loadArrayv2($type, $this->_lang);
		return $categories_array[$name];
	}
 
	function signedTimelineCloseAction()
	{
		$quotecron_obj = new Ep_Quote_Cron();
		
		$search = array('sales_status'=>'validated','sign_expire_timeline'=>time());
		$res = $quotecron_obj->getQuote($search);
		
		$data = array();
		$data['sales_review'] = 'closed';
		$data['closed_reason'] = 'no_reason_client';
		
		$quotecontract_obj = new Ep_Quote_Quotecontract();
		$users = (array) $quotecontract_obj->getUsers("superadmin");
		
		foreach($res as $row):
			$quotecron_obj->updateQuote($data,$row['identifier']);
			
			$log_obj=new Ep_Quote_QuotesLog();	
			
			$log_array = array();
			$log_array['id']=$log_obj->getIdentifier();
			$log_array['quote_id']=$row['identifier'];
			$log_array['user_id']=$users[0]['identifier'];
			$log_array['user_type']='superadmin';
			$log_array['action']='sales_cron_closed';
			$log_array['action_at']=date("Y-m-d H:i:s");
			$log_array['action_sentence']='Le syst&eacute;me a automatiquement closed';
			$log_array['comments']='Closed by Cron';
			$log_array['version']=$row['version'];

			$log_obj->logCron($log_array); 
		endforeach;
	}
	
	function techDelayAction()
	{
		$quotecron_obj = new Ep_Quote_Cron();
		
		$search = array('tech_review'=>true);
		$quotes = $quotecron_obj->getLateQuotes($search);
		
		$quotecontract_obj = new Ep_Quote_Quotecontract();
		$users = (array) $quotecontract_obj->getUsers("'techmanager' OR type='techuser'",'',true);
				
		$mail_obj=new Ep_Message_AutoEmails();
		$email_contents = $mail_obj->getAutoEmail(150);
		$subject = $email_contents[0]['Object'];
		$orgmsg = $msg=stripslashes($email_contents[0]['Message']);
		$contract_link = "<a href=/contractmission/contract-edit?submenuId=ML13-SL3&contract_id=$contractid&action=view target=_blank>click here</a>";
		$email_config = $quotecron_obj->getConfiguration('critsend');
	   
		foreach($quotes as $quote):
			$quote_name = "<strong>".$quote['title']."</strong>";
			$click_here = "<a href='".$this->url."/quote-new/client-brief?qaction=briefing&quote_id=".$quote['identifier']."&submenuId=ML13-SL2'>cliquer ici</a>";
			foreach($users as $user):
				$bo_user = $user['first_name']." ".$user['last_name'];
				eval("\$msg= \"$orgmsg\";");
				//$mail_obj->sendEmail($email_config,$this->mail_from,$user['email'],$subject,$msg);
				$mail_obj->sendEmail($this->mail_from,$msg,$user['email'],$subject);
			endforeach;
		endforeach;
	}
	
	function seoDelayAction()
	{
		$quotecron_obj = new Ep_Quote_Cron();
		
		$search = array('seo_review'=>true);
		$quotes = $quotecron_obj->getLateQuotes($search);
		
		$quotecontract_obj = new Ep_Quote_Quotecontract();
		$users = (array) $quotecontract_obj->getUsers("'seomanager' OR type='seouser'",'',true);
				
		$mail_obj=new Ep_Message_AutoEmails();
		$email_contents = $mail_obj->getAutoEmail(150);
		$subject = $email_contents[0]['Object'];
		$orgmsg = $msg=stripslashes($email_contents[0]['Message']);
       
		$email_config = $quotecron_obj->getConfiguration('critsend');
	   
		foreach($quotes as $quote):
			$quote_name = "<strong>".$quote['title']."</strong>";
			$click_here = "<a href='".$this->url."/quote-new/client-brief?qaction=briefing&quote_id=".$quote['identifier']."&submenuId=ML13-SL2'>cliquer ici</a>";
			foreach($users as $user):
				$bo_user = $user['first_name']." ".$user['last_name'];
				eval("\$msg= \"$orgmsg\";");
				//$mail_obj->sendEmail($email_config,$this->mail_from,$user['email'],$subject,$msg);
				$mail_obj->sendEmail($this->mail_from,$msg,$user['email'],$subject);
			endforeach;
		endforeach;
	}
	
	function prodDelayAction()
	{
		$quotecron_obj = new Ep_Quote_Cron();
		
		$search = array('prod_review'=>true);
		$quotes = $quotecron_obj->getLateQuotes($search);
		
		$quotecontract_obj = new Ep_Quote_Quotecontract();
		$users = (array) $quotecontract_obj->getUsers("'prodsubmanager'",'',true);
				
		$mail_obj=new Ep_Message_AutoEmails();
		$email_contents = $mail_obj->getAutoEmail(150);
		$subject = $email_contents[0]['Object'];
		$orgmsg = $msg=stripslashes($email_contents[0]['Message']);
       
		$email_config = $quotecron_obj->getConfiguration('critsend');
	   
		foreach($quotes as $quote):
			$quote_name = "<strong>".$quote['title']."</strong>";
			$click_here = "<a href='".$this->url."/quote/prod-quote-review?quote_id=".$quote['identifier']."&submenuId=ML13-SL2'>cliquer ici</a>";
			foreach($users as $user):
				$bo_user = $user['first_name']." ".$user['last_name'];
				eval("\$msg= \"$orgmsg\";");
				//$mail_obj->sendEmail($email_config,$this->mail_from,$user['email'],$subject,$msg);
				$mail_obj->sendEmail($this->mail_from,$msg,$user['email'],$subject);
			endforeach;
		endforeach;
	}
	
	function salesDelayAction()
	{
		$quotecron_obj = new Ep_Quote_Cron();
		
		$search = array('sales_review'=>true);
		$quotes = $quotecron_obj->getLateQuotes($search);
		
		$quotecontract_obj = new Ep_Quote_Quotecontract();
		
				
		$mail_obj=new Ep_Message_AutoEmails();
		$email_contents = $mail_obj->getAutoEmail(150);
		$subject = $email_contents[0]['Object'];
		$orgmsg = $msg=stripslashes($email_contents[0]['Message']);
       
		$email_config = $quotecron_obj->getConfiguration('critsend');
	   
		foreach($quotes as $quote):
			$user = (array) $quotecontract_obj->getUsers("'salesmanager' OR type='salesuser'",'',true,$quote['quote_by']);
			$quote_name = "<strong>".$quote['title']."</strong>";
			$click_here = "<a href='".$this->url."/quote/sales-final-validation?quote_id=".$quote['identifier']."&submenuId=ML13-SL2'>cliquer ici</a>";
			//foreach($users as $user):
				$bo_user = $user[0]['first_name']." ".$user[0]['last_name'];
				eval("\$msg= \"$orgmsg\";");
				//$mail_obj->sendEmail($email_config,$this->mail_from,$user['email'],$subject,$msg);
				$mail_obj->sendEmail($this->mail_from,$msg,$user[0]['email'],$subject);
			//endforeach;
		endforeach;
	}
	/*repeat deliveries function to create deliveries automatically based on conditions*/
	public function repeatDeliveryAction()
	{
		$quotecron_obj = new Ep_Quote_Cron();

		$delivery_id=$this->_request->getParam('delivery_id');

		$repeatDeliveries=$quotecron_obj->getRepeatDeliveries($delivery_id);

		//echo "<pre>";print_r($repeatDeliveries);exit;

		if($repeatDeliveries)
		{	
			$weekday[1]="monday";
			$weekday[2]="tuesday";
			$weekday[3]="wednesday";
			$weekday[4]="thursday";
			$weekday[5]="friday";
			$weekday[6]="saturday";
			$weekday[7]="sunday";
			
			foreach($repeatDeliveries as $delivery)
			{	
				$repeat_option=$delivery['repeat_option'];
		    	$repeat_every=$delivery['repeat_every'];
		    	$repeat_on=explode(",",$delivery['repeat_days']);    	
		    	$repeat_days=count($repeat_on);
		    	$repeat_start=$delivery['repeat_start'];
		    	$repeat_end=$delivery['repeat_end'];
		    	$after_occurance=$delivery['end_occurances'];
		    	$end_on=$delivery['end_date'];
		    	$previous_creation_dates=explode(",",$delivery['previous_creation_dates']);

		    	$created_count=$delivery['count_created'];//count of delivery already created through cron

		    	$delivery_identifier=$delivery['delivery_id'];
		    	$repeat_id=$delivery['repeat_id'];

		    	//echo date("Y-m-d",strtotime("2015-01-25 +1 week monday"));exit;

		    	if($repeat_option=='week' ||$repeat_option=='week_b')//weekly repeat
				{	
					if(count($repeat_on)>0)
					{
						
						foreach($repeat_on as $week_day)
						{																
							//$multiple=($created_count+1);
							$multiple=ceil(($created_count)/count($repeat_on));
							$repeat_week=$multiple*$repeat_every;					
							$current_date=date("Y-m-d", strtotime('+1 day')); 

							$cron_date=date("Y-m-d",strtotime("$repeat_start +$repeat_week week $weekday[$week_day]"));
							
							//echo $current_date."--".$cron_date."<br>";

							if($cron_date==$current_date && !in_array($cron_date,$previous_creation_dates))
							{									
								//echo $cron_date;
								//check repeat end date condition
								if($repeat_end=='on' && $end_on)
								{
									if($end_on >= $cron_date)
									{
										$this->createDelivery($repeat_id,$delivery_identifier,$previous_creation_dates);
									}
								}
								else if($repeat_end=='after')							
								{
									if($created_count<($after_occurance*$repeat_days))
									{
										$this->createDelivery($repeat_id,$delivery_identifier,$previous_creation_dates);
									}
								}
								else if($repeat_end=='never')
								{
									$this->createDelivery($repeat_id,$delivery_identifier,$previous_creation_dates);
								}
							}	
						}
					}
				}
				else if($repeat_option=='month') //monthly repeat
				{	
					$multiple=($created_count+1);
					$repeat_month=$multiple*$repeat_every;
										
					$current_date=date("Y-m-d", strtotime('+1 day'));
					$cron_date=date("Y-m-d",strtotime("$repeat_start +$repeat_month month"));

					if($cron_date==$current_date && !in_array($cron_date,$previous_creation_dates)) //checking current date is same as to be create date
					{						

						//check repeat end date condition
						if($repeat_end=='on' && $end_on)
						{
							if($end_on > $cron_date)
							{
								$this->createDelivery($repeat_id,$delivery_identifier,$previous_creation_dates);
							}
						}
						else if($repeat_end=='after')							
						{
							if($created_count<$after_occurance)
							{
								$this->createDelivery($repeat_id,$delivery_identifier,$previous_creation_dates);
							}
						}
						else if($repeat_end=='never')
						{
							$this->createDelivery($repeat_id,$delivery_identifier,$previous_creation_dates);
						}
					}
				}
				else if($repeat_option=='daily') //daily repeat
				{
					$multiple=($created_count+1);
					$repeat_day=$multiple*1;
										
					$current_date=date("Y-m-d", strtotime('+1 day'));
					$cron_date=date("Y-m-d",strtotime("$repeat_start +$repeat_day day"));

					//echo $current_date."--".$cron_date."<br>";
					if($cron_date==$current_date && !in_array($cron_date,$previous_creation_dates)) //checking current date is same as to be create date
					{						

						//check repeat end date condition
						if($repeat_end=='on' && $end_on)
						{
							if($end_on > $cron_date)
							{
								$this->createDelivery($repeat_id,$delivery_identifier,$previous_creation_dates);
							}
						}
						else if($repeat_end=='after')							
						{
							if($created_count<$after_occurance)
							{
								$this->createDelivery($repeat_id,$delivery_identifier,$previous_creation_dates);
							}
						}
						else if($repeat_end=='never')
						{
							$this->createDelivery($repeat_id,$delivery_identifier,$previous_creation_dates);
						}
					}


				}	
			}
		}
	}
	//re creating the delivery
	function createDelivery($repeat_id,$rdelivery_identifier,$previous_creation_dates=array())
	{
		//echo $repeat_id."--".$rdelivery_identifier;
		if($rdelivery_identifier)
		{
			$quotecron_obj = new Ep_Quote_Cron();
			$delivery_obj=new Ep_Quote_Delivery();
			$deliveryDetails=$quotecron_obj->getDeliveryDetails($rdelivery_identifier);

			//echo "<pre>";print_r($deliveryDetails);exit;

			if($deliveryDetails)
			{
				foreach($deliveryDetails as $delivery)
				{
					//Delivery insertion
					$delivery_array["contract_mission_id"]=$delivery['contract_mission_id'];

					$delivery_array["user_id"] = $delivery['user_id'];
					$delivery_array['title'] = 'Production delivery '.date("dmY", strtotime('+1 day'));
					$delivery_array["deli_anonymous"] =$delivery['deli_anonymous'];
					$delivery_array["total_article"] = $delivery['total_article'];
					$delivery_array["language"] = $delivery['language'];
					$delivery_array["type"] = $delivery['type'];
					$delivery_array["category"] = $delivery['category'];
					$delivery_array["signtype"] = $delivery['signtype'];

					$delivery_array["min_sign"]=$delivery['min_sign'];
					$delivery_array['max_sign']=$delivery['max_sign'];
					$delivery_array['price_min']=$delivery['price_min'];
					$delivery_array['price_max']=$delivery['price_max'];
					$delivery_array["view_to"] = $delivery['view_to'];

					$delivery_array["premium_option"] = $delivery['premium_option'];
					$delivery_array["premium_total"] = $delivery['premium_total'];

					$delivery_array["participation_time"] = $delivery['participation_time'];
					
					$delivery_array["selection_time"] = $delivery['selection_time']; //newly added


					$delivery_array['urlsexcluded']=$delivery['urlsexcluded'];

					//Submit time
					$delivery_array["submit_option"] = $delivery["submit_option"]; 
					$delivery_array['subjunior_time']=$delivery["subjunior_time"] ;
					$delivery_array['junior_time']=$delivery["junior_time"] ;
					$delivery_array['senior_time']=$delivery["senior_time"] ;

					//Resubmit time
					$delivery_array['resubmit_option']=$delivery['resubmit_option'];
					$delivery_array['jc0_resubmission']=$delivery["jc0_resubmission"];
					$delivery_array['jc_resubmission']=$delivery["jc_resubmission"];
					$delivery_array['sc_resubmission']=$delivery["sc_resubmission"];

					$delivery_array["file_name"] = $delivery['file_name'];
					$delivery_array["filepath"] = $delivery['filepath'];

					$delivery_array["created_by"]='AUTO';
					$delivery_array["created_user"]=$delivery['created_user'];
					$delivery_array["status_bo"] = "active";
					$delivery_array["updated_at"] = date('Y-m-d');
					$delivery_array["published_at"] =time();

					$delivery_array["AOtype"]=$delivery["AOtype"];
					$delivery_array["plagiarism_check"]=$delivery["plagiarism_check"];
					$delivery_array["writer_notify"]=$delivery["writer_notify"];

					//Correction
					$delivery_array["correction"]=$delivery['correction'];
					if($delivery['correction']=='external')
					{			
						$delivery_array["correction_type"]=$delivery['correction_type'];
						$delivery_array["correction_pricemin"]=$delivery['correction_pricemin'];
						$delivery_array["correction_pricemax"]=$delivery['correction_pricemax'];
						$delivery_array["correction_participation"]=$delivery['correction_participation'];
						$delivery_array["correction_selection_time"]=$delivery['correction_selection_time'];//newly added
						
						//corrector submission times
						$delivery_array['correction_jc_submission']=$delivery["correction_jc_submission"];
						$delivery_array['correction_sc_submission']=$delivery["correction_sc_submission"];
						$delivery_array['correction_submit_option']=$delivery['correction_submit_option'];

						//corrector resubmission times
						$delivery_array['correction_jc_resubmission']=$delivery['correction_jc_resubmission'];
						$delivery_array['correction_sc_resubmission']=$delivery['correction_sc_resubmission'];
						$delivery_array['correction_resubmit_option']=$delivery['correction_resubmit_option'];

						$delivery_array["correction_file"]=$delivery['correction_file'];
						$delivery_array["corrector_mail"]=$delivery['corrector_mail'];
						$delivery_array["correction_type"]=$delivery['correction_type'];
						$delivery_array['corrector_list']=$delivery['corrector_list'];
						$delivery_array["corrector_notify"]=$delivery['corrector_notify'];

						//Added new columns
						$delivery_array["corrector_pricedisplay"]=$delivery['corrector_pricedisplay'];
						$delivery_array["correction_launch"]=$delivery['correction_launch'];
						$delivery_array["launch_after_packs"]=$delivery['launch_after_packs'];
					}

					//publishtime and notifications
					$delivery_array["publishtime"]=strtotime(date("Y-m-d 10:00", strtotime('+1 day'))); 

					$delivery_array["mailsubject"]=$delivery['mailsubject'];
					$delivery_array["mailcontent"]=$this->getcontribmailcontentAction($delivery_array);
					$delivery_array["missioncomment"]=$delivery['missioncomment'];
					$delivery_array["fbcomment"]=$delivery['fbcomment'];

					$delivery_array["nltitle"]=$delivery['nltitle'];

					$delivery_array["mailnow"]=$delivery['mailnow'];
					$delivery_array["mail_send"]=$delivery['mail_send'];		


					$delivery_array["publish_language"]=$delivery["publish_language"];

					$delivery_array["missiontest"]="no";
					$delivery_array["pricedisplay"]=$delivery['pricedisplay'];
					//$delivery_array["pricedisplay"]="yes"; 

					$delivery_array["column_xls"]=$delivery['xls_columns'];
					//Added new columns
					$delivery_array["plag_xls"]=$delivery['plag_xls'];

					//send emails from Bo user or service
					$delivery_array["mail_send_from"]=$delivery['mail_from'];
					
					//echo "<pre>";print_r($delivery_array);exit;
					$delivery_identifier=$delivery_obj->insertDelivery($delivery_array);

					//Article insertion
					$all_article_details=$quotecron_obj->getArticleDetails($rdelivery_identifier);
					//echo "<pre>";print_r($all_article_details);exit;
					if($all_article_details && $delivery_identifier )
					{
						$total_amount=0;
						$final_array_contribs=array();
						
						foreach($all_article_details as $article_details)
						{
							$article_obj=new Ep_Quote_Delivery();
							
							//Insert Article
							$article_array = array(); 			
							$article_array["delivery_id"]= $delivery_identifier;
							$article_array["title"] 	 = $article_details['title'];
							$article_array["language"] 	 = $article_details['language'];
							$article_array["category"]   = $article_details['category'];
							$article_array["type"]       = $article_details['type'];
							$article_array["sign_type"]  = $article_details['sign_type'];
							$article_array["num_min"]	 = $article_details['num_min'];
							$article_array["num_max"] 	 = $article_details['num_max'];
							$article_array["price_min"]  = currencyToDecimal($article_details['price_min']);
							$article_array["price_max"]  = currencyToDecimal($article_details['price_max']);
							$article_array["price_final"]= currencyToDecimal($article_details['price_max']);
							$article_array["status"]	 = "new";  
							$article_array["created_by"] ='AUTO';
							$article_array["publish_language"]=$article_details['publish_language'];
							$article_array["contrib_percentage"]='100';
							$article_array["paid_status"]='paid';

							$total_amount+=$article_array["price_max"];

							//Contributors list			
							if($delivery_array['AOtype']=="private")
							{
							  $article_array["contribs_list"]=$article_details['contribs_list'];
							  $final_array_contribs[]=explode(",",$article_details['contribs_list']);
							}
								
							//Correction
							if($delivery_array['correction']=='external')
							{
								$article_array["correction"]="yes";
				                $article_array["correction_type"]='extern';
				                $article_array["nomoderation"]=$article_details['nomoderation'];



								$article_array["correction_pricemin"]=currencyToDecimal($article_details['correction_pricemin']);
								$article_array["correction_pricemax"]=currencyToDecimal($article_details['correction_pricemax']);
								$article_array["correction_participation"]=($delivery_array["correction_participation"]);
								
								
								//Submit time								
								$article_array['correction_jc_submission']=$article_details['correction_jc_submission'];
								$article_array['correction_sc_submission']=$article_details['correction_sc_submission'];
								$article_array['correction_submit_option']=$article_details['correction_submit_option'];
								
								//ReSubmit time
								$article_array["correction_jc_resubmission"]=$article_details['correction_jc_resubmission'];
								$article_array["correction_sc_resubmission"] = $article_details['correction_sc_resubmission'];
								$article_array["correction_resubmit_option"] = $article_details['correction_resubmit_option'];
								
								$article_array["corrector_privatelist"]=$article_details["corrector_privatelist"];
									
								//new fields
								$article_array["correction_selection_time"]=$article_details["correction_selection_time"];
								$article_array["corrector_list"]=$article_details['corrector_list'];								
								$article_array["proofread_start"]=$article_details["proofread_start"];
								$article_array["proofread_end"]=$article_details["proofread_end"];								
							}
							
							$publishtime=($delivery_array['publishtime']);
							
							$article_array["participation_expires"]=($publishtime + ($delivery_array["participation_time"]*60));
							
							$article_array["participation_time"] = $delivery_array["participation_time"];
							
							//Submit time												
							$article_array["submit_option"]=$article_details['submit_option'];
							$article_array["junior_time"] = $article_details['junior_time'];
							$article_array["senior_time"] = $article_details['senior_time'];
							$article_array["subjunior_time"] = $article_details['subjunior_time'];
								
							//Resubmit time							
							$article_array["resubmit_option"]=$article_details['resubmit_option'];
							$article_array["jc_resubmission"] = $article_details['jc_resubmission'];
							$article_array["sc_resubmission"] = $article_details['sc_resubmission'];
							$article_array["jc0_resubmission"] = $article_details['jc0_resubmission'];
							
							/* $article_array["estimated_worktime"] = ($article_details['estimated_worktime']*60)+$article_details['estimated_worktime_min'];
							if($article_details['estimated_worktime_option'])
							$article_array["estimated_workoption"] = $article_details['estimated_worktime_option'];
							else
							$article_array["estimated_workoption"] = 'min'; */
							
							$article_array["column_xls"]=$article_details['xls_columns'];
							
							//new fields
							$article_array["selection_time"]=($article_details["selection_time"]);
							$article_array["view_to"]=$article_details["view_to"];
							
							//echo "<pre>";print_r($article_array);exit;
							$article_obj->insertArticle($article_array);

						}

					}

					//update repeat delivery
					$repeat_obj=new Ep_Quote_DeliveryRepeat();
					$repeat_identifier=$repeat_id;
					$creation_dates=$previous_creation_dates;
					$creation_dates[]=date("Y-m-d", strtotime('+1 day'));
					$repeated_dates=implode(",",$creation_dates)	;
					$data_array = array("previous_creation_dates"=>$repeated_dates,"count_created"=>new Zend_Db_Expr('count_created+1'));
					$repeat_obj->updateRepeatDelivery($data_array,$repeat_identifier);


					//inserting into payment and payment article tables
					$pay_obj = new Ep_Payment_Payment();
					$payart_obj = new Ep_Payment_PaymentArticle();
					//Payment table
					$Pyarray = array();
					$Pyarray['delivery_id']=$delivery_identifier;				
					$Pyarray['amount_paid']=$total_amount;
					$Pyarray['status']='Paid';
					$pay_obj->insertPayment($Pyarray);
					
					//payment article table
					$data = array();
					$data['amount'] = $Pyarray['amount_paid'];
					$pay_amount = $Pyarray['amount_paid']*1.2;
					$pay_amount = number_format($pay_amount,2);
					$data['user_id']=$delivery['user_id'];
					$data['amount_paid']=$pay_amount;
					$data['type']='instant';
					$data['pay_type']='BO';
					$invoiceId=$payart_obj->insertPayment_article($data);
	                $delivery_obj->updatePaidarticle($delivery_identifier,$invoiceId);

	               
	                //ArticleHistory Insertion
					$hist_obj = new Ep_Delivery_ArticleHistory();
					$action_obj = new EP_Delivery_ArticleActions();
					$history1=array();
					$history1['user_id']='110823103540627';
					$history1['article_id']=$delivery_identifier;
					$sentence1=$action_obj->getActionSentence(1);					
							
						if($delivery_array['AOtype']=='public')
							$AO_type='<b>Public</b>';
						else
							$AO_type='<b>Private</b>';						
						
					$AO_name='<a href="/ongoing/ao-details?client_id='.$delivery['user_id'].'&ao_id='.$delivery_identifier.'&submenuId=ML2-SL4" target="_blank"><b>'.$this->delivery_creation->prod_step1['title'].'</b></a>';

					$user_obj=new Ep_User_Client();
					$detailsC=$user_obj->getClientName($delivery['user_id']);
					$client_name='<b>'.$detailsC[0]['company_name'].'</b>';
					
					$project_manager_name='<b>'.ucfirst('Auto System').'</b>';
					$actionmessage=strip_tags($sentence1[0]['Message']);
					eval("\$actionmessage= \"$actionmessage\";");
					
					$history1['stage']='creation';
					$history1['action_sentence']=$actionmessage;
					$hist_obj->insertHistory($history1);


					//Add mission comments for each article
	                if($delivery['missioncomment']!="")
					{
						$comm_obj=new Ep_User_AdComments();
						$article_obj=new Ep_Quote_Delivery();
						$artids=$article_obj->getArticles($delivery_identifier);
						for($a=0;$a<count($artids);$a++)
						{
							$commentarray=array();
							$commentarray['user_id']='110823103540627';
							$commentarray['type']="article";
							$commentarray['type_identifier']=$artids[$a]['id'];
							$commentarray['comments']=isodec($delivery['missioncomment']);
							$comm_obj->InsertComment($commentarray);
						}
					}

				}
			}
			echo $delivery_array['title']." created successfully"."<br>";
			
			//echo "<pre>";print_r($deliveryDetails);
		}
	}

	public function getcontribmailcontentAction($delivery_array)
	{		
		$automail=new Ep_Message_AutoEmails();
		$user_obj=new Ep_User_Client();		
		$mailid="";
		
			if($delivery_array['AOtype']=="private")
			{
				$mailid=87;
			}
			else
			{		
				if($delivery_array['publish_now']=='yes')
					$mailid=85;
				else
					$mailid=89;
					
			}
		
			
        $email=$automail->getAutoEmail($mailid);
		
		$participation_time=$delivery_array['participation_time'];
		
		if($delivery_array['publish_now']=='yes')
		{			
			$expires=time()+(60*$participation_time);
			$submitdate_bo="<b>".strftime("%d/%m/%Y &agrave; %H:%M",$expires)."</b>";
		}
		else
		{	
			
			$expires=($delivery_array['publishtime'])+($participation_time*60);
			$submitdate_bo="<b>".strftime("%d/%m/%Y &agrave; %H:%M",$expires)."</b>";
		}

		$aowithlink='<a href="/contrib/aosearch">'.stripslashes(utf8_encode($delivery_array['title'])).'</a>';
		//$sub=$email[0]['Object'];
		$Message=$email[0]['Message'];
		eval("\$Message= \"$Message\";");
		
		//$subcon=$sub."#".$Message;
		return (isodec($Message));
		//echo $subcon;
		//echo $mailid;
		
	}

	/**cron function to send auto Reminder mail to the Ao created user when article submission time expires for a contributor**/
    public function reminderMailEpSubmitExpiresAction()
    {
    	$paticipation_obj=new Ep_Quote_Cron();
		$participation_details=$paticipation_obj->getWriterArticleSubmissionExpires();
		//echo "<pre>";print_r($participation_details);exit;
		if($participation_details!="NO")
		{
			foreach($participation_details AS $paticipants)
			{			
				$contributor_id= $paticipants['user_id'];
				$contributor_details= $paticipation_obj->getContribUserDetails($contributor_id);
				
				$contributor_name=$contributor_details[0]['first_name']." ".$contributor_details[0]['last_name'];

				//Article History insertion
                $hist_obj = new Ep_Delivery_ArticleHistory();
                $action_obj = new EP_Delivery_ArticleActions();
                $history76=array();
                $history76['user_id']='110823108965627';//$contributor_id;
                $history76['article_id']=$paticipants['article_id'];
                $sentence76=$action_obj->getActionSentence(76);                            
                $article_name='<b>'.$paticipants['article'].'</b>';                              
                $user_name='<a class="writer" href="/user/contributor-edit?submenuId=ML2-SL7&tab=viewcontrib&userId='.$contributor_id.'" target="_blank"><b>'.$contributor_name.'</b></a>';
                $actionmessage=strip_tags($sentence76[0]['Message']);
                eval("\$actionmessage= \"$actionmessage\";");
                $history76['stage']='Auto';
                $history76['action']='article_not_sent';
                $history76['action_sentence']=$actionmessage;

                //echo "<pre>";print_r($history76);
                $hist_obj->insertHistory($history76);
			}
		}

		//correction article submit expires
		$cparticipation_details=$paticipation_obj->getCorrectorArticleSubmissionExpires();
		if($cparticipation_details!="NO")
		{
			foreach($cparticipation_details AS $cpaticipants)
			{			
				$contributor_id= $cpaticipants['corrector_id'];
				$contributor_details= $paticipation_obj->getContribUserDetails($contributor_id);
				
				$contributor_name=$contributor_details[0]['first_name']." ".$contributor_details[0]['last_name'];

				//Article History insertion
                $hist_obj = new Ep_Delivery_ArticleHistory();
                $action_obj = new EP_Delivery_ArticleActions();
                $history76=array();
                $history76['user_id']='110823108965627';//$contributor_id;
                $history76['article_id']=$cpaticipants['article_id'];
                $sentence76=$action_obj->getActionSentence(76);                            
                $article_name='<b>'.$cpaticipants['article'].'</b>';                              
                $user_name='<a class="corrector" href="/user/contributor-edit?submenuId=ML2-SL7&tab=viewcontrib&userId='.$contributor_id.'" target="_blank"><b>'.$contributor_name.'</b></a>';
                $actionmessage=strip_tags($sentence76[0]['Message']);
                eval("\$actionmessage= \"$actionmessage\";");
                $history76['stage']='Auto';
                $history76['action']='article_not_sent';
                $history76['action_sentence']=$actionmessage;

                //echo "<pre>";print_r($history76);
                $hist_obj->insertHistory($history76);
			}
		}
		
    }

    public function bidTimeExpiresAction()
    {
    	$paticipation_obj=new Ep_Quote_Cron();
		$participation_details=$paticipation_obj->getBidOverArticles();

		if($participation_details!="NO")
		{
			foreach($participation_details AS $paticipants)
			{			
				$user_id= '110823108965627';			

				//Article History insertion
                $hist_obj = new Ep_Delivery_ArticleHistory();
                $action_obj = new EP_Delivery_ArticleActions();
                $history78=array();
                $history78['user_id']=$user_id;
                $history78['article_id']=$paticipants['id'];
                $sentence78=$action_obj->getActionSentence(78);                            
                $article_name='<b>'.$paticipants['title'].'</b>';                
                $actionmessage=strip_tags($sentence78[0]['Message']);
                eval("\$actionmessage= \"$actionmessage\";");
                $history78['stage']='Auto';
                $history78['action']='bid_time_over';
                $history78['action_sentence']=$actionmessage;

                //echo "<pre>";print_r($history78);
                $hist_obj->insertHistory($history78);
			}
		}
    }
	
	/* Start of Invoices */
	
	// To generate Invoices on Monthly
	function monthlyAction()
	{
		$invoice_obj = new Ep_Quote_Invoice();
		$monthly_invoices = $invoice_obj->generateMonthly();
		
		if($monthly_invoices)
		{
			$prev_id = $monthly_invoices[0]['contractmissionid'];
			$total_turnover = 0;
			$last_invoice_id = "";
			$invoice_details = array(); 
			$invoice = array();
			$invoice_details = array();
			$length = count($monthly_invoices)-1;
			$i = $j = 0;
			foreach($monthly_invoices as $row)
			{
				$total_turnover += $row['unit_price'];
				$invoice_details[$i]['description'] = $row['title'];
				$invoice_details[$i]['volume'] = 1;
				$invoice_details[$i]['price_per_art'] = $row['unit_price'];
				$invoice_details[$i]['article_id'] = $row['artid'];
				if($prev_id !=  $row['contractmissionid'] || $length==$j)
				{
					$invoice['invoice_type'] = 'month';
					$invoice['invoice_number'] = $this->getInvoiceNumber($row['client_code'],$invoice_obj,$row['contract_id']);
					$invoice['total_turnover'] = $total_turnover;
					$invoice['contract_id'] = $row['contract_id'];
					$invoice['client_id'] = $row['user_id'];
					$invoice['cmid'] = $row['contractmissionid'];
					$invoice['created_at'] = date('Y-m-d H:i:s');
					$invoice['file_path'] = "/$row[contractmissionid]/".$invoice['invoice_number'].".xlsx";
					$last_invoice_id = $invoice_obj->insertInvoice($invoice);
					$total_turnover = 0;
					$i = -1;
					$k = 0;
					foreach($invoice_details as $invoices)
					{
						$invoice_details[$k]['invoice_id'] = $last_invoice_id;
						$invoice_obj->insertInvoiceDetails($invoice_details[$k++]);
					}
					$missionDir= $_SERVER['DOCUMENT_ROOT']."/BO/contract_mission_invoice/".$row['contractmissionid']."/";
					if(!is_dir($missionDir))
					mkdir($missionDir,TRUE);
					chmod($missionDir,0777);
					$filename = $_SERVER['DOCUMENT_ROOT']."/BO/contract_mission_invoice/$row[contractmissionid]/$invoice[invoice_number].xlsx"; 
					$invoice['invoice_display'] = 'Monthly';
					$invoice['cname'] = $row['first_name']." ".$row['last_name'];
					$invoice['currency'] = $row['currency'];
					$htmltable = $this->generateTable($invoice,$invoice_details);
					$this->convertHtmltableToXlsx($htmltable,$filename,FALSE);
					$invoice = array();
				}
				$i++;
				$j++;
				$prev_id = $row['contractmissionid'];
			}
		}
	}
	
	function getInvoiceNumber($client_number,$invoice_obj,$cid)
	{
		if($client_number)
		$format = "INV-CL-".$client_number;
		else
		$format = "INV-CL-";
		return $invoice_obj->getInvoiceNumber($format,$cid);
	} 
	
	
	/* To generate XLSX File */
	function convertHtmltableToXlsx($htmltable,$filename,$extract=FALSE)
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
		
		
		$objPHPExcel->getProperties()->setCreator($username)
							 ->setLastModifiedBy($username)
							 ->setTitle("Sales Generation")
							 ->setSubject("Sales Final Validation")
							 ->setDescription("Sales Report")
							 ->setKeywords("Sales")
							 ->setCompany($usercompany)
							 ->setCategory("Export");
		
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
					if($ths->item($x)->hasAttribute('class')) {
					  $headrows[$h]['class'][] = str_replace("#", "", $ths->item($x)->getAttribute('bgcolor'));
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
		
		$objWriter->save($filename);
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
	/* End of Generation of XLSX File */
	
	// Genrating HTML Table for XLS
	function generateTable($invoice,$invoice_details)
	{
		$html ='<table>';
		$html .= "<tr>";
		$html .= "<td bgcolor='#95B3D7' style='font-size:15px' colspan='3'><b>EP generated invoice</b></td>";
		$html .= "</tr>";
		$html  .= '<tr><td></td></tr><tr><td></td></tr>';
		$html  .= '<tr><td></td></tr><tr><td></td></tr>';
		$html  .= '<tr><td></td><td><b>Number</b></td><td bgcolor="#ffff00"><b>'.$invoice['invoice_number'].'</b></td></tr>';
		$html  .= '<tr><td></td><td><b>Invoice Type</b></td><td bgcolor="#ffff00"><b>'.$invoice['invoice_display'].'</b></td></tr>';
		$html  .= '<tr><td></td><td></td><td bgcolor="#ffff00"><b>'.$invoice['cname'].'</b></td></tr>';
		$html  .= '<tr><td></td></tr><tr><td></td></tr>';
		$html  .= "<tr><td bgcolor='#dadada'><b>DESCRIPTION</b></td><td bgcolor='#dadada'><b>VOLUME</b></td><td bgcolor='#dadada'><b>PRICE / ART</b></td></tr>";
		$i=0;
		foreach($invoice_details as $invoices)
		{
				$html  .="<tr>";
				$html .="<td>".$invoice_details[$i]['description']."</td>";
				$html .="<td>".$invoice_details[$i]['volume']."</td>";
				$html .="<td>".$invoice_details[$i++]['price_per_art']." &".$invoice['currency'].";</td>";
				$html .="</tr>";
		}
		$html .='</table>';
		return $html;
	}
	
	//To generate Invoices on Delivery
	function deliveryAction()
	{
		$invoice_obj = new Ep_Quote_Invoice();
		$delivery_invoices = $invoice_obj->generateDelivery();
		
		if($delivery_invoices)
		{
			$prev_id = $delivery_invoices[0]['contractmissionid'];
			$total_turnover = 0;
			$last_invoice_id = "";
			$invoice_details = array(); 
			$invoice = array();
			$invoice_details = array();
			$length = count($delivery_invoices)-1;
			$i = $j = 0;
			foreach($delivery_invoices as $row)
			{
				$total_turnover += $row['unit_price']*$row['volume'];
				$invoice_details[$i]['description'] = $row['title'];
				$invoice_details[$i]['volume'] = $row['volume'];
				$invoice_details[$i]['price_per_art'] = $row['unit_price'];
				$invoice_details[$i]['delivery_id'] = $row['did'];
				if($prev_id !=  $row['contractmissionid'] || $length==$j)
				{
					$invoice['invoice_type'] = 'delivery';
					$invoice['invoice_number'] = $this->getInvoiceNumber($row['client_code'],$invoice_obj,$row['contract_id']);
					$invoice['total_turnover'] = $total_turnover;
					$invoice['contract_id'] = $row['contract_id'];
					$invoice['client_id'] = $row['user_id'];
					$invoice['cmid'] = $row['contractmissionid'];
					$invoice['created_at'] = date('Y-m-d H:i:s');
					$invoice['file_path'] = "/$row[contractmissionid]/".$invoice['invoice_number'].".xlsx";
					$last_invoice_id = $invoice_obj->insertInvoice($invoice);
					$total_turnover = 0;
					$i = -1;
					$k = 0;
					foreach($invoice_details as $invoices)
					{
						$invoice_details[$k]['invoice_id'] = $last_invoice_id;
						$invoice_obj->insertInvoiceDetails($invoice_details[$k++]);
					}
					$missionDir= $_SERVER['DOCUMENT_ROOT']."/BO/contract_mission_invoice/".$row['contractmissionid']."/";
					if(!is_dir($missionDir))
					mkdir($missionDir,TRUE);
					chmod($missionDir,0777);
					$filename = $_SERVER['DOCUMENT_ROOT']."/BO/contract_mission_invoice/$row[contractmissionid]/$invoice[invoice_number].xlsx"; 
					$invoice['invoice_display'] = 'Delivery';
					$invoice['cname'] = $row['first_name']." ".$row['last_name'];
					$invoice['currency'] = $row['currency'];
					$htmltable = $this->generateTable($invoice,$invoice_details);
					$this->convertHtmltableToXlsx($htmltable,$filename,FALSE);
					$invoice = array();
				}
				$i++;
				$j++;
				$prev_id = $row['contractmissionid'];
			}
		}
	}
	
	//To generate Invoice Action based on Mission
	function missionAction()
	{
		$invoice_obj = new Ep_Quote_Invoice();
		$mission_invoices = $invoice_obj->generateMission();
		
		if($mission_invoices)
		{
			$prev_id = $mission_invoices[0]['contractmissionid'];
			$total_turnover = 0;
			$last_invoice_id = "";
			$invoice_details = array(); 
			$invoice = array();
			$invoice_details = array();
			$length = count($mission_invoices)-1;
			$i = $j = 0;
			foreach($mission_invoices as $row)
			{
				$total_turnover += $row['unit_price']*$row['volume'];
				$invoice_details[$i]['description'] = $row['title'];
				$invoice_details[$i]['volume'] = $row['volume'];
				$invoice_details[$i]['price_per_art'] = $row['unit_price'];
				$invoice_details[$i]['delivery_id'] = $row['did'];
				$invoice_details[$i]['mission_id'] = $row['contractmissionid'];
				if($prev_id !=  $row['contractmissionid'] || $length==$j)
				{
					$invoice['invoice_type'] = 'mission';
					$invoice['invoice_number'] = $this->getInvoiceNumber($row['client_code'],$invoice_obj,$row['contract_id']);
					$invoice['total_turnover'] = $total_turnover;
					$invoice['contract_id'] = $row['contract_id'];
					$invoice['client_id'] = $row['user_id'];
					$invoice['cmid'] = $row['contractmissionid'];
					$invoice['created_at'] = date('Y-m-d H:i:s');
					$invoice['file_path'] = "/$row[contractmissionid]/".$invoice['invoice_number'].".xlsx";
					$last_invoice_id = $invoice_obj->insertInvoice($invoice);
					$total_turnover = 0;
					$i = -1;
					$k = 0;
					foreach($invoice_details as $invoices)
					{
						$invoice_details[$k]['invoice_id'] = $last_invoice_id;
						$invoice_obj->insertInvoiceDetails($invoice_details[$k++]);
					}
					$missionDir= $_SERVER['DOCUMENT_ROOT']."/BO/contract_mission_invoice/".$row['contractmissionid']."/";
					if(!is_dir($missionDir))
					mkdir($missionDir,TRUE);
					chmod($missionDir,0777);
					$filename = $_SERVER['DOCUMENT_ROOT']."/BO/contract_mission_invoice/$row[contractmissionid]/$invoice[invoice_number].xlsx"; 
					$invoice['invoice_display'] = 'Mission';
					$invoice['cname'] = $row['first_name']." ".$row['last_name'];
					$invoice['currency'] = $row['currency'];
					$htmltable = $this->generateTable($invoice,$invoice_details);
					$this->convertHtmltableToXlsx($htmltable,$filename,FALSE);
					$invoice = array();
				}
				$i++;
				$j++;
				$prev_id = $row['contractmissionid'];
			}
		}
	}
	
	/* End of Invoices */
	
	/* Delivery Late Send Mail */
	function deliveryLateAction()
	{
		$quotecron_obj = new Ep_Quote_Cron();
		$user_obj = new Ep_User_User();
		$res = $quotecron_obj->deliveryLate();
		$email_config = $quotecron_obj->getConfiguration('critsend');
		$mail_obj=new Ep_Message_AutoEmails();
		$email_contents = $mail_obj->getAutoEmail(162);
		$subject = $orgsubject = $email_contents[0]['Object'];
		$msg = $orgmsg = $email_contents[0]['Message'];
		foreach($res as $row):
			if($row['proofread_end'])
			{
				$delivery_name = $row['title'];
				eval("\$subject= \"$orgsubject\";");
				$user_info = $user_obj->getAllUsersDetails($row['assigned_by']);
				$name = $user_info[0]['first_name']." ".$user_info[0]['last_name'];
				$delivery_link = "<a href='".$this->url."/followup/delivery?ao_id=".$row['id']."&client_id=".$row['client_id']."&submenuId=ML13-SL4'>click here</a>";
				eval("\$msg= \"$orgmsg\";");
				//$mail_obj->sendEmail($email_config,$this->work_mail_from,$user_info[0]['email'],$subject,$msg);
				$mail_obj->sendEmail($this->work_mail_from,$msg,$user_info[0]['email'],$subject);
				$user_info = $user_obj->getAllUsersDetails($row['assigned_to']);
				$name = $user_info[0]['first_name']." ".$user_info[0]['last_name'];
				eval("\$msg= \"$orgmsg\";");
				//$mail_obj->sendEmail($email_config,$this->work_mail_from,$user_info[0]['email'],$subject,$msg);
				$mail_obj->sendEmail($this->work_mail_from,$msg,$user_info[0]['email'],$subject);
			}
			else
			{
				$days = ceil($row['total_time']/(60*24));
				$end_timestamp = strtotime("+$days days",$row['published_at']);
				$end_date = date('Y-m-d',$end_timestamp);
				if(date('Y-m-d')==$end_date)
				{
					if($end_timestamp >= time() && $end_timestamp<(time()+3600))
					{
						$delivery_name = $row['title'];
						eval("\$subject= \"$orgsubject\";");
						$user_info = $user_obj->getAllUsersDetails($row['assigned_by']);
						$name = $user_info[0]['first_name']." ".$user_info[0]['last_name'];
						$delivery_link = "<a href='".$this->url."/followup/delivery?ao_id=".$row['id']."&client_id=".$row['client_id']."&submenuId=ML13-SL4'>click here</a>";
						eval("\$msg= \"$orgmsg\";");
						//$mail_obj->sendEmail($email_config,$this->work_mail_from,$user_info[0]['email'],$subject,$msg);
						$mail_obj->sendEmail($this->work_mail_from,$msg,$user_info[0]['email'],$subject);
						$user_info = $user_obj->getAllUsersDetails($row['assigned_to']);
						$name = $user_info[0]['first_name']." ".$user_info[0]['last_name'];
						eval("\$msg= \"$orgmsg\";");
						//$mail_obj->sendEmail($email_config,$this->work_mail_from,$user_info[0]['email'],$subject,$msg);
						$mail_obj->sendEmail($this->work_mail_from,$msg,$user_info[0]['email'],$subject);
					}
				}
			}
		endforeach;
	}
	
	/* Sending Mails after bidding is over */
	function biddingOverAction()
	{
		$cron_obj = new Ep_Quote_Cron();
		$user_obj = new Ep_User_User();
		$deliveries = $cron_obj->biddingOver();
		$mail_obj=new Ep_Message_AutoEmails();
		$email_contents = $mail_obj->getAutoEmail(163);
		$subject = $orgsubject = $email_contents[0]['Object'];
		$msg = $orgmsg = $email_contents[0]['Message'];
		foreach($deliveries as $row)
		{
			$user_info = $user_obj->getAllUsersDetails($row['assigned_by']);
			$name = $user_info[0]['first_name']." ".$user_info[0]['last_name'];
			if($row['particpation_count']>1)
			$participant = 'participants';
			else
			$participant = 'participant';
			$no_of_participant = $row['particpation_count'];
			$delivery_name = $row['title'];
			$delivery_link = "<a href='".$this->url."/followup/delivery?ao_id=".$row['id']."&client_id=".$row['client_id']."&submenuId=ML13-SL4'>click here</a>";
			eval("\$subject= \"$orgsubject\";");
			eval("\$msg= \"$orgmsg\";");
			//$mail_obj->sendEmail($email_config,$this->work_mail_from,$user_info[0]['email'],$subject,$msg);
			$mail_obj->sendEmail($this->work_mail_from,$msg,$user_info[0]['email'],$subject);
		}
	}
	
	/* Contributor Delay Article Submit */
	function contributorDelaySubmitAction()
	{
		$cron_obj = new Ep_Quote_Cron();
		$user_obj = new Ep_User_User();
		$articles = $cron_obj->getDelayArticle();
		$mail_obj=new Ep_Message_AutoEmails();
		$email_contents = $mail_obj->getAutoEmail(164);
		$subject = $orgsubject = $email_contents[0]['Object'];
		$msg = $orgmsg = $email_contents[0]['Message'];
		foreach($articles as $row)
		{
			$contributor_name = $row['first_name']." ".$row['last_name'];
			$delivery_name = $row['title'];
			$user_info = $user_obj->getAllUsersDetails($row['assigned_by']);
			$name = $user_info[0]['first_name']." ".$user_info[0]['last_name'];
			$delivery_link = "<a href='".$this->url."/followup/delivery?ao_id=".$row['id']."&client_id=".$row['client_id']."&submenuId=ML13-SL4'>click here</a>";
			eval("\$subject= \"$orgsubject\";");
			eval("\$msg= \"$orgmsg\";");
			//$mail_obj->sendEmail($email_config,$this->work_mail_from,$user_info[0]['email'],$subject,$msg);
			$mail_obj->sendEmail($this->work_mail_from,$msg,$user_info[0]['email'],$subject);
		}	
	}
	
	/* Contract running late set cron once in a day */
	function contractLateAction()
	{
		$cron_obj = new Ep_Quote_Cron();
		$user_obj = new Ep_User_User();
		$contracts = $cron_obj->getLateContracts();
		$mail_obj=new Ep_Message_AutoEmails();
		$email_contents = $mail_obj->getAutoEmail(170);
		$subject = $orgsubject = $email_contents[0]['Object'];
		$msg = $orgmsg = $email_contents[0]['Message'];
		$quote_contract=new Ep_Quote_Quotecontract();
		//$facturationUsers = $quote_contract->getUsers("facturation");
		foreach($contracts as $row)
		{
			$contract_name  = $row['contractname'];
			$user_info = $user_obj->getAllUsersDetails($row['quote_by']);
			$name = $user_info[0]['first_name']." ".$user_info[0]['last_name'];
			$contract_link = "<a href='".$this->url."/contractmission/contract-edit?submenuId=ML13-SL3&contract_id=".$row['quotecontractid']."&action=view'>click here</a>";
			eval("\$subject= \"$orgsubject\";");
			eval("\$msg= \"$orgmsg\";");
			//$mail_obj->sendEmail($email_config,$this->work_mail_from,$user_info[0]['email'],$subject,$msg);
			$mail_obj->sendEmail($this->work_mail_from,$msg,$user_info[0]['email'],$subject);
			$facturationUsers = $user_obj->getAllUsersDetails($row['finance_validator_id']);
			foreach($facturationUsers as $user)
			{
				$name = $user['first_name']." ".$user['last_name'];
				eval("\$message= \"$orgmsg\";");
				//$mail_obj->sendEmail($email_config,$this->work_mail_from,$user['email'],$subject,$msg);
				$mail_obj->sendEmail($this->work_mail_from,$msg,$user['email'],$subject);
			}
		}	
	}	
	
	/* Contract Close set cron once in a day */
	function contractCloseAction()
	{
		$cron_obj = new Ep_Quote_Cron();
		$user_obj = new Ep_User_User();
		$contracts = $cron_obj->getCloseContracts();
		$mail_obj=new Ep_Message_AutoEmails();
		$email_contents = $mail_obj->getAutoEmail(171);
		$subject = $orgsubject = $email_contents[0]['Object'];
		$msg = $orgmsg = $email_contents[0]['Message'];
		$quote_contract=new Ep_Quote_Quotecontract();
		//$facturationUsers = $quote_contract->getUsers("facturation");
		foreach($contracts as $row)
		{
			$contract_name  = $row['contractname'];
			$user_info = $user_obj->getAllUsersDetails($row['quote_by']);
			$name = $user_info[0]['first_name']." ".$user_info[0]['last_name'];
			$contract_link = "<a href='".$this->url."/contractmission/contract-edit?submenuId=ML13-SL3&contract_id=".$row['quotecontractid']."&action=view'>click here</a>";
			$bo_user_info = $user_obj->getAllUsersDetails($row['sales_creator_id']);
			$bo_user = $bo_user_info[0]['first_name']." ".$bo_user_info[0]['last_name'];
			eval("\$subject= \"$orgsubject\";");
			eval("\$msg= \"$orgmsg\";");
			//$mail_obj->sendEmail($email_config,$this->work_mail_from,$user_info[0]['email'],$subject,$msg);
			$mail_obj->sendEmail($this->work_mail_from,$msg,$user_info[0]['email'],$subject);
			$facturationUsers = $user_obj->getAllUsersDetails($row['finance_validator_id']);
			foreach($facturationUsers as $user)
			{
				$name = $user['first_name']." ".$user['last_name'];
				eval("\$message= \"$orgmsg\";");
				//$mail_obj->sendEmail($email_config,$this->work_mail_from,$user['email'],$subject,$msg);
				$mail_obj->sendEmail($this->work_mail_from,$msg,$user['email'],$subject);
			}
		}	
	}

	/* by Arun
	function to send email to quote created user when quote challenge running late
	*/
	function quoteChallengeDelayAction()
	{
		$cron_obj = new Ep_Quote_Cron();
		$mail_obj=new Ep_Message_AutoEmails();

		$lateChallengeQuotes=$cron_obj->getQuoteChallengeLate();
		if($lateChallengeQuotes)
		{
			foreach ($lateChallengeQuotes as $quote)
			{
				if($quote['tec_review']=='not_done')
				{
					//email to sales user
					$receiver_id=$quote['quote_by'];
					$mail_parameters['sales_user']=$quote['quote_by'];
					$mail_parameters['quote_title']=$quote['title'];
					$mail_parameters['challenge_type']='Tech';
					$mail_parameters['quote_title_link']='/quote/sales-quotes-list?submenuId=ML13-SL2';					
					$mail_obj->sendQuotePersonalEmail($receiver_id,175,$mail_parameters);


					//email to tech users
					$client_obj=new Ep_Quote_Client();
					$challenge_users=$client_obj->getEPContacts('"techuser","techmanager"');
					if(count($challenge_users)>0)
					{
					
						foreach($challenge_users as $user=>$name)
						{
							$receiver_id=$user;
							$mail_parameters['bo_user']=$user;
							$mail_parameters['quote_title']=$quote['title'];
							$mail_parameters['challenge_type']='Tech';
							$mail_parameters['quote_title_link']='/quote/sales-quotes-list?submenuId=ML13-SL2';
							$mail_parameters['followup_link_en']='/quote/tech-quote-review?quote_id='.$quote['identifier'].'&submenuId=ML13-SL2';							
							$mail_obj->sendQuotePersonalEmail($receiver_id,176,$mail_parameters);
						}
					}		

				}
				if($quote['seo_review']=='not_done')
				{
					$receiver_id=$quote['quote_by'];
					$mail_parameters['sales_user']=$quote['quote_by'];
					$mail_parameters['quote_title']=$quote['title'];
					$mail_parameters['challenge_type']='Seo';
					$mail_parameters['quote_title_link']='/quote/sales-quotes-list?submenuId=ML13-SL2';
					//$mail_parameters['bo_user']=$this->adminLogin->userId;
					$mail_obj->sendQuotePersonalEmail($receiver_id,175,$mail_parameters);

					//email to tech users
					$client_obj=new Ep_Quote_Client();
					$challenge_users=$client_obj->getEPContacts('"seouser","seomanager"');
					if(count($challenge_users)>0)
					{
					
						foreach($challenge_users as $user=>$name)
						{
							$receiver_id=$user;
							$mail_parameters['bo_user']=$user;
							$mail_parameters['quote_title']=$quote['title'];
							$mail_parameters['challenge_type']='Seo';
							$mail_parameters['quote_title_link']='/quote/sales-quotes-list?submenuId=ML13-SL2';
							$mail_parameters['followup_link_en']='/quote/seo-quote-review?quote_id='.$quote['identifier'].'&submenuId=ML13-SL2';							
							$mail_obj->sendQuotePersonalEmail($receiver_id,176,$mail_parameters);
						}
					}
				}
			}

			//echo "<pre>";print_r($lateChallengeQuotes);exit;	
		}
		
	}
	
	/* Tempo one shot emails */
	function getTemposAction()
	{
		$cron_obj = new Ep_Quote_Cron();
		$contractmissions = $cron_obj->getArticleCount();
		//print_r($contractmissions);exit;
		$email_config = $cron_obj->getConfiguration('critsend');
		foreach($contractmissions as $row)
		{
			$cmid =  $row['contract_mission_id'];
			$oneshot =  $row['oneshot'];
			$assigned_at = $row['assigned_date'];
			$mail_obj=new Ep_Message_AutoEmails();
			// Recurring
			if($oneshot=="no")
			{
				if($row['delivery_volume_option']=="every")
				{
					// Add days to assigned at by tempo_length
					if($row['tempo_length_option']=="days")
						$res = $this->checkEveryRecurring($assigned_at,$row['tempo_length'],'days');
					elseif($row['tempo_length_option']=="week")
						$res = $this->checkEveryRecurring($assigned_at,$row['tempo_length'],'weeks');
					elseif($row['tempo_length_option']=="month")
						$res = $this->checkEveryRecurring($assigned_at,$row['tempo_length'],'months');		
					elseif($row['tempo_length_option']=="year")
						$res = $this->checkEveryRecurring($assigned_at,$row['tempo_length'],'years');							
				}
				else
				{
					// Add days to assigned at by tempo_length
					if($row['tempo_length_option']=="days")
						$res = $this->checkWithinRecurring($assigned_at,$row['tempo_length'],'days');	
					elseif($row['tempo_length_option']=="week")
						$res = $this->checkWithinRecurring($assigned_at,$row['tempo_length'],'weeks');		
					elseif($row['tempo_length_option']=="month")
						$res = $this->checkWithinRecurring($assigned_at,$row['tempo_length'],'months');
					elseif($row['tempo_length_option']=="year")
						$res = $this->checkWithinRecurring($assigned_at,$row['tempo_length'],'years');
				}
				//echo "<pre>";print_r($res);exit;
				if($res)
				{
					// Send Mail
					//$mail_obj=new Ep_Message_AutoEmails();
					$email_contents = $mail_obj->getAutoEmail(195);
					$subject = $email_contents[0]['Object'];
					$orgmsg = $msg=stripslashes($email_contents[0]['Message']);
					if($row['company_name'])
						$client_name = $row['company_name'];
					else
						$client_name = $client_name;
					$dear_name = $bo_user = $row['first_name']." ".$row['last_name'];
					$volume_max = $row['volume_max'];
					$delivery_volume_option = $this->volume_option_array[$row['delivery_volume_option']];
					$tempo_length = $row['tempo_length'];
					$tempo_length_option = $this->duration_array[$row['tempo_length_option']];
					$product_name = $this->producttype_array[$row['product_type']];
					$delivered_artices = $row['article_count'];
					eval("\$subject= \"$subject\";");
					eval("\$msg= \"$orgmsg\";");
					//echo $msg;exit;
					//$user_emails=array('arunravuri@edit-place.com','ravuriarun@gmail.com',$row['email']);
					$user_emails=array($row['email']);
					//$mail_obj->sendEmail($email_config,$this->mail_from,$row['email'],$subject,$msg);
					if($delivered_artices < $volume_max)
					{
						foreach($user_emails as $user)
						{
							$mail_obj->sendEmail($this->mail_from,$msg,$user,$subject);
						}
					}
					/*inserting in to track email*/
					$tempo_email['contract_mission_id']=$cmid;
					$tempo_email['client_id']=$row['client_id'];
					$tempo_email['user_email']='astrinati@edit-place.com';
					$tempo_email['email_subject']=$subject;
					$tempo_email['email_content']=$msg;
					$tempo_email['created_at']=date("Y-m-d H:i:s");
					$tempo_email['frequency']='daily';
					$trackObj=new Ep_Quote_TempoEmailsTrack();					
					$trackObj->insertTemoEmail($tempo_email);
				}
			}
			elseif($oneshot=="yes")
			{
				$tempos = $cron_obj->getTempos($row['type_id'],$oneshot);
				if($tempos)
				{
					foreach($tempos as $tempo)
					{
						if($tempo['oneshot_option']=="week")
							$res = $this->checkWithinRecurring($assigned_at,$tempo['oneshot_length'],'weeks');
						elseif($tempo['oneshot_option']=="month")
							$res = $this->checkWithinRecurring($assigned_at,$tempo['oneshot_length'],'months');
						elseif($tempo['oneshot_option']=="year")
							$res = $this->checkWithinRecurring($assigned_at,$tempo['oneshot_length'],'years');
						else
							$res = $this->checkWithinRecurring($assigned_at,$tempo['oneshot_length'],'days');
						if($res)
						{
							// Send Mail
							//$mail_obj=new Ep_Message_AutoEmails();
							$email_contents = $mail_obj->getAutoEmail(194);
							$subject = $email_contents[0]['Object'];
							$orgmsg = $msg=stripslashes($email_contents[0]['Message']);
							if($row['company_name'])
								$client_name = $row['company_name'];
							else
								$client_name = $client_name;
							$dear_name = $bo_user = $row['first_name']." ".$row['last_name'];
							$one_shot_length = $tempo['oneshot_length'];
							$product_name = $this->producttype_array[$row['product_type']];
							$delivered_artices = $row['article_count'];
							eval("\$subject= \"$subject\";");
							eval("\$msg= \"$orgmsg\";");
							
							//echo $msg;exit;
							//$user_emails=array('arunravuri@edit-place.com','ravuriarun@gmail.com',$row['email']);
							$user_emails=array($row['email']);
							
							if($delivered_artices < $one_shot_length)
							{
								foreach($user_emails as $user)
								{
									$mail_obj->sendEmail($this->mail_from,$msg,$user,$subject);
								}
							}
							/*inserting in to track email*/
							$tempo_email['contract_mission_id']=$cmid;
							$tempo_email['client_id']=$row['client_id'];
							$tempo_email['user_email']='astrinati@edit-place.com';
							$tempo_email['email_subject']=$subject;
							$tempo_email['email_content']=$msg;
							$tempo_email['created_at']=date("Y-m-d H:i:s");
							$tempo_email['frequency']='daily';
							$trackObj=new Ep_Quote_TempoEmailsTrack();					
							$trackObj->insertTemoEmail($tempo_email);
						}
					}
				}
			}
		}
	}
	/**To get tempos weekly recap email */
	function getTemposWeeklyAction()
	{
		$mail_obj=new Ep_Message_AutoEmails();	
		$trackObj = new Ep_Quote_TempoEmailsTrack();
		$trackWeeklyEmails = $trackObj->getWeeklyEmails();
		$monday =strtotime('monday last week');
		$friday =strtotime('friday last week');
		//echo "<pre>";print_r($trackWeeklyEmails);exit;
		if($trackWeeklyEmails){
			$email_content='';
			foreach($trackWeeklyEmails as $email)
			{
				$contract_mission_id=$email['contract_mission_id'];
				$not_respected_count=$email['not_respected_count'];
				if($contract_mission_id && $not_respected_count)
				{
					$missionDetails=$trackObj->getMissionDetails($contract_mission_id);
					if($missionDetails['product']=='translation')
					{
						$mtitle = $this->product_array[$missionDetails['product']]." ".$this->producttype_array[$missionDetails['product_type']]." ".$this->getCustomName("EP_LANGUAGES",$missionDetails['language_source'])." au ".$this->getCustomName("EP_LANGUAGES",$missionDetails['language_dest']);
						$language = $this->getCustomName("EP_LANGUAGES",$missionDetails['language_source'])." -> ".$this->getCustomName("EP_LANGUAGES",$missionDetails['language_dest']);
					}
					else
					{
						$mtitle = $this->product_array[$missionDetails['product']]." ".$this->producttype_array[$missionDetails['product_type']]." in ".$this->getCustomName("EP_LANGUAGES",$missionDetails['language_source']);
						$language = $this->getCustomName("EP_LANGUAGES",$missionDetails['language_source']);
					}		
					$mtitle=$mtitle." ".$missionDetails['product_type_other']." (".$missionDetails['contractname'].")";	
					$followup_link="<a href='http://".$_SERVER['HTTP_HOST']."/followup/prod?submenuId=ML13-SL4&cmid=".$contract_mission_id."'><b>$mtitle</b></a>";
					$email_content.=" $not_respected_count time(s) for the mission ".$followup_link." <br>";
				}
			}
			if($email_content)
			{
				$email_content='Tempo has not been respected <br>'.$email_content;
				//$user_emails=array('Alessia Strinati'=>'astrinati@edit-place.com','atwist'=>' atwist@edit-place.com');
				$user_emails=array('Seb'=>'schateau@edit-place.com','Marie'=>'Mfouris@edit-place.com','Alessia Strinati'=>'astrinati@edit-place.com');				
				//$user_emails=array('Arun'=>'arunravuri@edit-place.com');
				$weekly_date=date("W", $monday).' - '.date('d',$monday).' to '.date('d',$friday).' '.date('F Y');
				$content_concadinate=$email_content;
				foreach ($user_emails as $bo_user => $email) 
				{
					$email_total = $mail_obj->getAutoEmail(210);
					$subject_sub = $email_total[0]['Object'];
					$orgmsge = stripslashes($email_total[0]['Message']);
					eval("\$subject= \"$subject_sub\";");
					eval("\$msge= \"$orgmsge\";");
					//echo $subject."--".$msge;exit;
					$mail_obj->sendEMail($email_config,$msge,$email,$subject);
				}
			}			
		}
	}
	/* To check every recurring */
	function checkEveryRecurring($assigned_at,$length,$type)
	{
		$current_date = strtotime(date('Y-m-d'));
		$i = 1;
		do
		{
			$callength = $length * $i++;
			$calculated_date = strtotime(date('Y-m-d',strtotime($assigned_at . "+$callength $type")));
			if($calculated_date==$current_date)
				return true;
		}	
		while($current_date>$calculated_date);
		return false;
	}
	/* To check every recurring */
	function checkWithinRecurring($assigned_at,$length,$type)
	{
		$current_date = strtotime(date('Y-m-d'));
		$callength = $length;
		$calculated_date = strtotime(date('Y-m-d',strtotime($assigned_at . "+$callength $type")));
		if($calculated_date==$current_date)
			return true;
		else
		return false;
	}
	
	
	//relancer Email 
	
	function relancerEmailAction(){
		
		$quotecron_obj = new Ep_Quote_Cron();
		$mail_obj=new Ep_Message_AutoEmails();
		$quotes = $quotecron_obj->getrelanceClient();
		
		if(count($quotes)>0)
					{
					
						foreach($quotes as $vales)
						{
							
							
							if($vales)
						{
																				
							$mail_parameters['client_name'] = $vales['company_name'];
							$user_obj = new Ep_User_User();
							$user_info = $user_obj->getAllUsersDetails($vales['quote_by']);
							$mail_parameters['bo_user'] = $vales['quote_by'];
							$mail_parameters['turn_over']=$vales['turnover'];	
							$mail_parameters['relance_comment']=$vales['relancer_commet'];
							$mail_parameters['followup_link']='/quote/quote-followup?quote_id='.$vales['identifier'];
							$mail_obj->sendQuotePersonalEmail($vales['quote_by'],197,$mail_parameters);
							
							//Email Sent Update
							/*$quote_obj=new Ep_Quote_Quotes();
							$update = array('relance_sent'=>'Yes');
							$quote_obj->updateQuote($update,$vales['identifier']);*/
							
							}
							
						}
					}
		
		}
		
		
		
		
		 
		 function weeklyQuotesAction(){
			 
			 
					
			 $quotecron_obj = new Ep_Quote_Cron();
			 $quotes=$quotecron_obj->getweeklyquotes();
			 $quoteDetails=array();
			 $quoteslern=array();
			 $quotesusertotal=array();
			 $date7day = date('Y-m-d H:i:s',time()-(7*86400));
						$html="<table class='table-bordered table-hover'>";
						if(count($quotes)>0){
										 $quotetotal=0;
										 $turnover=0;
										 $signature=0;
										 $i=0;
										 $usertotal=1;
								foreach($quotes as $quotescheck){
										
										if(($quotescheck['created_at']>=$date7day && $quotescheck['created_at']<=date('Y-m-d') )&& ($quotescheck['sales_review']=='not_done' || $quotescheck['sales_review']=='challenged' || $quotescheck['sales_review']=='to_be_approve') ){
															$quotetotal++;
															$turnover+=$quotescheck['turnover'];
															$signature+=$quotescheck['estimate_sign_percentage'];
															$quoteslern[$i]['identifier']=$quotescheck['identifier'];
															$mrgin=explode('.',$quotescheck['sales_margin_percentage']);
															$quoteslern[$i]['company_name']=$quotescheck['company_name'];
															$quoteslern[$i]['bosalesuser']=$quotescheck['bosalesuser'];
															$quoteslern[$i]['title']="<a href='http://".$_SERVER['HTTP_HOST']."/quote/quote-followup?quote_id=".$quotescheck['identifier']."&submenuId=ML13-SL2' target='_blank'>".stripslashes(utf8_encode($quotescheck['title']))."</a>";
															
															$quoteslern[$i]['estimate_sign_percentage']=$quotescheck['estimate_sign_percentage'];
															$quoteslern[$i]['turnover']=$quotescheck['turnover'];
															$quoteslern[$i]['saleinchange']=$quotescheck['bosalesuser'];
															$quoteslern[$i]['created_at']=date('Y-m-d',strtotime($quotescheck['created_at']));
															$quoteslern[$i]['status']='Ongoing';
																	if($quotescheck['seo_timeline']<time() && $quotescheck['seo_timeline']!='')
																	{
																		$team= 'Seo ';
																	}
																	elseif($quotescheck['tech_timeline']<time() && $quotescheck['tech_timeline']!='')
																	{
																		$team= 'Tech ';	
																	}
																	elseif($quotescheck['prod_timeline']<time() && $quotescheck['prod_timeline']!=0)
																	{
																		$team= 'Prod ';
																	}
															
															$quoteslern[$i]['team']=$team;
															if($quotescheck['response_time']>time()){
																$quoteslern[$i]['notiontime']='Late No';
															}else{
																$quoteslern[$i]['notiontime']='Late Yes';
															}
															 $quoteslern[$i]['quote_by']=$quotescheck['quote_by'];
															
															
															$i++;
										}
										elseif($quotescheck['sales_review']=='closed' && $quotescheck['closed_reason']!='quote_permanently_lost' && $quotescheck['releaceraction']!=''){
																$quotetotal++;
																$turnover+=$quotescheck['turnover'];
																$signature+=$quotescheck['estimate_sign_percentage'];
																$quoteslern[$i]['identifier']=$quotescheck['identifier'];
																$mrgin=explode('.',$quotescheck['sales_margin_percentage']);										
																$quoteslern[$i]['company_name']=$quotescheck['company_name'];
																$quoteslern[$i]['estimate_sign_percentage']=$quotescheck['estimate_sign_percentage'];
																$quoteslern[$i]['turnover']=$quotescheck['turnover'];
																$quoteslern[$i]['saleinchange']=$quotescheck['bosalesuser'];
																$quoteslern[$i]['title']="<a href='http://".$_SERVER['HTTP_HOST']."/quote/quote-followup?quote_id=".$quotescheck['identifier']."&submenuId=ML13-SL2' target='_blank'>".stripslashes(utf8_encode($quotescheck['title']))."</a>";
																$quoteslern[$i]['created_at']=date('Y-m-d',strtotime($quotescheck['created_at']));
																$quoteslern[$i]['status']='A relancer';
																$quoteslern[$i]['team']='Relance';
																
																/*$validate_date= new DateTime($quotecron_obj->getquotesvalidatelog($quotescheck['identifier'])[0]['action_at']);
																 $sent_days= $validate_date->diff(new DateTime(date("Y-m-d H:i:s")));*/
																 
																  $sent_days=dateDiff($quotecron_obj->getquotesvalidatelog($quotescheck['identifier'])[0]['action_at'],date("Y-m-d H:i:s"));
																 
																$quoteslern[$i]['notiontime']='Quote sent '.$sent_days.' on days ago';
																$quoteslern[$i]['comments']='closed on '.$quotescheck['releaceraction'];
																 $quoteslern[$i]['quote_by']=$quotescheck['quote_by'];
																
																
																$i++;
										}
										elseif($quotescheck['sales_review'] == 'validated' && $quotescheck['validateaction']!="" ){
																 $quotetotal++;
																 $turnover+=$quotescheck['turnover'];
																 $signature+=$quotescheck['estimate_sign_percentage'];
																 $quoteslern[$i]['identifier']=$quotescheck['identifier'];
																 $mrgin=explode('.',$quotescheck['sales_margin_percentage']);										
																 $quoteslern[$i]['company_name']=$quotescheck['company_name'];
																 $quoteslern[$i]['estimate_sign_percentage']=$quotescheck['estimate_sign_percentage'];
																 $quoteslern[$i]['turnover']=$quotescheck['turnover'];
																 $quoteslern[$i]['saleinchange']=$quotescheck['bosalesuser'];
																 $quoteslern[$i]['title']="<a href='http://".$_SERVER['HTTP_HOST']."/quote/quote-followup?quote_id=".$quotescheck['identifier']."&submenuId=ML13-SL2' target='_blank'>".stripslashes(utf8_encode($quotescheck['title']))."</a>";
																 $quoteslern[$i]['created_at']=date('Y-m-d',strtotime($quotescheck['created_at']));
																 $quoteslern[$i]['quote_by']=$quotescheck['quote_by'];
																 $quoteslern[$i]['status']='Sent';
																 
																/* $validate_date= new DateTime($quotescheck['validateaction']);
																 $sent_days= $validate_date->diff(new DateTime(date("Y-m-d H:i:s")));*/
																  $sent_days=dateDiff($quotescheck['validateaction'], date("Y-m-d H:i:s"));
																 
																 $quoteslern[$i]['notiontime']='Quote sent '.$sent_days.' on days ago';
																 $quoteslern[$i]['comments']='closed on '.$quotescheck['validateaction'];
																 $quoteslern[$i]['team']='/';
																  
																
																$i++;
										}
										elseif($quotescheck['sales_review'] == 'closed' && ($quotescheck['closed_reason']=='quote_permanently_lost' || $quotescheck['closeaction']!="" || $quotescheck['close5dayaction']!="" || $quotescheck['close20dayaction']!="" || $quotescheck['close30dayaction']!="") ){
																			 
																 $mrgin=explode('.',$quotescheck['sales_margin_percentage']);	
																 $quoteslern[$i]['identifier']=$quotescheck['identifier'];									
																 $quoteslern[$i]['company_name']=$quotescheck['company_name'];
																 $quoteslern[$i]['estimate_sign_percentage']=$quotescheck['estimate_sign_percentage'];
																 $quoteslern[$i]['turnover']=$quotescheck['turnover'];
																 $quoteslern[$i]['saleinchange']=$quotescheck['bosalesuser'];
																 $quoteslern[$i]['created_at']=date('Y-m-d',strtotime($quotescheck['created_at']));
																 $quoteslern[$i]['status']='Closed';
																
																 /*$validate_date= new DateTime($quotecron_obj->getquotesvalidatelog($quotescheck['identifier'])[0]['action_at']);
																 $sent_days= $validate_date->diff(new DateTime(date("Y-m-d H:i:s")));*/
																 
																 $sent_days=dateDiff($quotecron_obj->getquotesvalidatelog($quotescheck['identifier'])[0]['action_at'],date("Y-m-d H:i:s"));
																 
																  $quoteslern[$i]['notiontime']='Quote sent '.$sent_days.' on days ago';
																 $quoteslern[$i]['comments']= $quotescheck['closed_comments'].'<br> closed on '.$quotescheck['closeaction'];
																 $quoteslern[$i]['team']=$this->closedreason[$quotescheck['closed_reason']];
																 $quoteslern[$i]['title']="<a href='http://".$_SERVER['HTTP_HOST']."/quote/quote-followup?quote_id=".$quotescheck['identifier']."&submenuId=ML13-SL2' target='_blank'>".stripslashes(utf8_encode($quotescheck['title']))."</a>";
																$i++;
										}
										
								
								} // end foreach
								$quoteDetails['quotetotal']=$quotetotal;
								$quoteDetails['turnover']=$turnover;
								$quoteDetails['signature']=round($signature/$quotetotal);
					
									 $startdate = date('d',time()-(7*86400));
									 $enddate= date('d-M-Y',strtotime(date('Y-m-d')));
									$statusdir =$_SERVER['DOCUMENT_ROOT']."/BO/quotes_weekly_report/";
									if(!is_dir($statusdir))
									mkdir($statusdir,TRUE);
									chmod($statusdir,0777);
									$filename = $_SERVER['DOCUMENT_ROOT']."/BO/quotes_weekly_report/weekly-report-$startdate-to-$enddate.xlsx";
									$htmltable = $this->QuotesTable($quoteDetails,$quoteslern);
									
									$this->convertHtmltableToXlsx($htmltable,$filename,True);
										chmod($filename,0777);								
									$mail_obj=new Ep_Message_AutoEmails();
									$email_contents = $mail_obj->getAutoEmail(199);
									$subject = $email_contents[0]['Object'];
									$orgmsg = $msg=stripslashes($email_contents[0]['Message']);
									
									eval("\$subject= \"$subject\";");
									eval("\$msg= \"$orgmsg\";");
									$email_head_sale=array("141690656032222"=>"asherrard@edit-place.com",
													"139281941421499"=>"mfouris@edit-place.com",
													"139281963577076"=>"jwolff@edit-place.com"
													); // need to add thaibault
													
									if(count($email_head_sale)>0){
									   	foreach($email_head_sale as $user=>$emails){
									$this->sendMail($emails,$subject,$msg,$statusdir,"weekly-report-$startdate-to-$enddate.xlsx");
										}
									}
									
				 } //end id
			
			 
			}
			
		function QuotesTable($quoteDetails,$quotes){
		
						$user_check_array=array();	
						$html ='<table>';
						$html .= "<tr>";
						$html .= "<td bgcolor='#95B3D7' style='font-size:15px' colspan='8'><b>Quotes Weekly Report</b></td>";
						$html .= "</tr>";
						$html  .= '<tr><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>';
						$html  .= '<tr><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>';
						$html  .= '<tr><td><b>Total '.$quoteDetails['quotetotal'].' in FR</b></td>';
						$html  .= '<td bgcolor="#ffff00"><b>'.$quoteDetails['turnover'].' Euros</b></td><td bgcolor="#ffff00"><b>'.$quoteDetails['quotetotal'].'</b></td><td colspan=6><b>Average '.$quoteDetails['signature'].' % of signature</b></td></tr>';
						
						
						
						
						$html1  .="<tr><td><b>Client Name</b></td><td><b>Nom devis</b></td><td><b>Sales in charge</b></td><td><b>Creation date</b></td><td><b>% of signature</b></td><td><b>Turnover</b></td><td><b>Status</b></td><td><b>Status 2</b></td><td><b>Notions of timings</b></td><td><b>Comment</b></td></tr>";
						
		           $i=1;
		
			$j=1;
		
		foreach($quotes as $quotesloop){
			
			if($quotesloop['status']!='Closed'){
				
						if(array_key_exists($quotesloop['quote_by'],$user_check_array)){
				
							$user_check_array[$quotesloop['quote_by']]['quotesby']=$quotesloop['quote_by'];
							$user_check_array[$quotesloop['quote_by']]['saleinchange']=$quotesloop['saleinchange'];
							$user_check_array[$quotesloop['quote_by']]['trunover']+=$quotesloop['turnover'];
							$user_check_array[$quotesloop['quote_by']]['signature']+=$quotesloop['estimate_sign_percentage'];
							$user_check_array[$quotesloop['quote_by']]['count']++;
							
							}else{
							$user_check_array[$quotesloop['quote_by']]['quotesby']=$quotesloop['quote_by'];
							$user_check_array[$quotesloop['quote_by']]['saleinchange']=$quotesloop['saleinchange'];
							$user_check_array[$quotesloop['quote_by']]['trunover']=$quotesloop['turnover'];
							$user_check_array[$quotesloop['quote_by']]['signature']=$quotesloop['estimate_sign_percentage'];
							$user_check_array[$quotesloop['quote_by']]['count']=1;
						}
			
				}
			if(!$quotesloop['team'])    $quotesloop['team']='only sales';
		
					$html1  .="<tr><td>".$quotesloop['company_name']."</td><td>".$quotesloop['title']."</td>";
					$html1  .="<td>".$quotesloop['saleinchange']."</td><td>".date('d/m/Y',strtotime($quotesloop['created_at']))."</td>
					<td>".$quotesloop['estimate_sign_percentage']."% </td><td>".$quotesloop['turnover']."</td><td>".$quotesloop['status']."</td><td>".$quotesloop['team']."</td><td>".$quotesloop['notiontime']."</td><td>".$quotesloop['comments']."</td></tr>";
					$i++;
			}
			
			foreach($user_check_array as $usrkey=>$usrval){
					
					$avrsign =round($user_check_array[$usrkey]['signature']/$user_check_array[$usrkey]['count']);
					$html  .="<tr><td><b>".$user_check_array[$usrkey]['saleinchange']."</b></td><td><b>".$user_check_array[$usrkey]['trunover']." Euros</b></td>
					<td><b>".$user_check_array[$usrkey]['count']." Quotes</b></td><td><b>".$avrsign."</b></td></tr>";
				$j++;
			}
			$this->count_val=$j++;
				$html  .= '<tr><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>';
				$html  .= '<tr><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>';
			$show =$html.$html1.'</table>';
			
		//echo $show;
		return $show;
		}
		
			
		
		 function sendMail($to, $subj, $msg, $path, $attach,$cc="") { 
								$mail = new Zend_Mail; 
								$fullattched = $path.$attach; 
								
								//echo "file name  : " .$path.$fullattched. PHP_EOL; 
								
								$at = new Zend_Mime_Part(file_get_contents($fullattched)); //.xls is included in the filename 
								$at->type = 'application/octet-stream'; 
								$at->disposition = Zend_Mime::DISPOSITION_ATTACHMENT; 
								$at->encoding = Zend_Mime::ENCODING_BASE64; 
								$at->filename = basename($fullattched); //.xls is included in the filename 

								$mail->setFrom('work@edit-place.com'); 
								$mail->addTo($to);
								if($cc)
								{
									$mail->addCc($cc); 
								}								
								$mail->setSubject($subj); 
								$mail->setBodyHtml($msg); 
								$mail->addAttachment($at); 
								
								try { 
										$mail->send(); 
									//	echo "Mail Sent".PHP_EOL; 
								} catch (Zend_Mail_Exception $e) { 
										print_r($e->getMessage()); 
								} 
		}
		
		
		//closed quotes email send function
		function closedQuoteEmailAction(){
			
				$quotecron_obj = new Ep_Quote_Cron();
				$quotes=$quotecron_obj->getclosedquotes();
				$mail_obj=new Ep_Message_AutoEmails();
				$user_obj = new Ep_User_User();
				$days_5='+5 days';
				$days_20='+20 days';
				$days_30='+30 days';
				$currDate=date('Y-m-d');

				//echo "<pre>"; print_r($quotes); exit;
			
			if(count($quotes)>0){
				
			  	 foreach($quotes as $quotesloop){
						$mail_parameters=array();
						if(($quotesloop['sales_review']=='validated' && $currDate==date('Y-m-d',strtotime($quotesloop['validateddate']. "$days_5"))) || ($quotesloop['sales_review']=='closed' && $currDate==date('Y-m-d',strtotime($quotesloop['closedate']. "$days_5"))) )
							{
							//Email after 5 days closed/Validated
										
										$mail_parameters['client_name'] = $quotesloop['company_name'];
										$mail_parameters['bo_user'] = $quotesloop['quote_by'];
										$mail_parameters['turn_over']=$quotesloop['turnover'];	
										$mail_obj->sendQuotePersonalEmail($quotesloop['quote_by'],200,$mail_parameters);
							}
							elseif( ($quotesloop['sales_review']=='validated' && $currDate==date('Y-m-d',strtotime($quotesloop['validateddate']. "$days_20"))) || ($quotesloop['sales_review']=='closed' && $currDate==date('Y-m-d',strtotime($quotesloop['closedate']. "$days_20"))) )
							{
								//Email after 20 days closed/Validated
									
										$mail_parameters['client_name'] = $quotesloop['company_name'];
										$mail_parameters['bo_user'] = $quotesloop['quote_by'];
										$mail_parameters['turn_over']=$quotesloop['turnover'];	
										$mail_obj->sendQuotePersonalEmail($quotesloop['quote_by'],201,$mail_parameters);
							}
							elseif( ($quotesloop['sales_review']=='validated' && $currDate==date('Y-m-d',strtotime($quotesloop['validateddate']. "$days_30"))) ||  ($quotesloop['sales_review']=='closed' && $currDate==date('Y-m-d',strtotime($quotesloop['closedate']. "$days_30"))) )
							{
								//Email after 30 days closed/Validated
									  
									
										$mail_parameters['client_name'] = $quotesloop['company_name'];
										$mail_parameters['bo_user'] = $quotesloop['quote_by'];
										$mail_parameters['turn_over']=$quotesloop['turnover'];	
										//echo "<pre>"; print_r($mail_parameters); exit;
										$mail_obj->sendQuotePersonalEmail($quotesloop['quote_by'],202,$mail_parameters);
							}
			
			
					}
					
				}//end if
			}
     	
     	
     	function prodtechAnswerQuoteAction(){
			$html="";
			$quotecron_obj = new Ep_Quote_Cron();
			$quotesarray=$quotecron_obj->getquoteslastsixmonths();
			$i=1;
			if($quotesarray>0){
				
				$html ="<table border=1>";
				$html .="<tr><td><b>Client Name</b></td><td><b>Name of Quote</b></td>
				<td><b>Turnover</b></td><td><b>Time took Prod</b></td><td><b>Time took Tech</b></td><td><b>Time took Seo</b></td></tr>";
								
				foreach ($quotesarray as $quotesloop){
					$prod="";
					$tech="";
					$seo="";
					$days=0;
					$hours=0;
					$days_tech=0;
					$days_seo=0;
					$hours_tech=0;
					$hours_seo=0;
					if(($quotesloop['prodontime']!="" && $quotesloop['prod_timeline']!='') || 
					($quotesloop['techontime']!="" && $quotesloop['tech_timeline']!='') || 
					($quotesloop['seoontime']!="" && $quotesloop['seo_timeline']!='') ){
					
					if($quotesloop['prodontime']!="" && $quotesloop['prod_timeline']!=''){	
						
									if((date('Y-m-d H:m:s',$quotesloop['prod_timeline'])>$quotesloop['prodontime'])){
														 $prod_timeline= $quotesloop['prod_timeline']-strtotime($quotesloop['prodontime']);
														 $days=floor($prod_timeline/86400);
														 $hours= floor($prod_timeline/3600);
														 $delay='<span style="color:green">On time</span>';	
									}else{
														$prod_timeline=strtotime($quotesloop['prodontime'])-$quotesloop['prod_timeline'];
														$days= floor($prod_timeline/86400);
														$hours= floor($prod_timeline/3600);
														$delay='<span style="color:red">Delay</span>';
									}
											
					}
					
					if($quotesloop['techontime']!="" && $quotesloop['tech_timeline']!=''){
						
							if(strtotime($quotesloop['tech_timeline'])>strtotime($quotesloop['techontime'])) {
												$tech_timeline= strtotime($quotesloop['tech_timeline'])-strtotime($quotesloop['techontime']);
												$days_tech=floor($tech_timeline/86400);
												$hours_tech= floor($tech_timeline/3600);
												$delay_tech='<span style="color:green">On time</span>';
								}else{
												$tech_timeline=strtotime($quotesloop['techontime'])-strtotime($quotesloop['tech_timeline']);
												$days_tech= floor($tech_timeline/86400);
												$hours_tech= floor($tech_timeline/3600);
												$delay_tech='<span style="color:red">Delay</span>'; 
								}
									
					}
					
					if($quotesloop['seoontime']!="" && $quotesloop['seo_timeline']!=''){
								
							if(strtotime($quotesloop['seo_timeline'])>strtotime($quotesloop['seoontime'])){
											$seo_timeline= strtotime($quotesloop['seo_timeline'])-strtotime($quotesloop['seoontime']);
											$days_seo=floor($seo_timeline/86400);
											$hours_seo= floor($seo_timeline/3600);
											$delay_seo='<span style="color:green;">On time</span>';
								}else{
									     	$seo_timeline=strtotime($quotesloop['seoontime'])-strtotime($quotesloop['seo_timeline']);
											$days_seo= floor($seo_timeline/86400);
											$hours_seo= floor($seo_timeline/3600);
											$delay_seo='<span style="color:red;">Delay</span>';
								}	
					}
					
										if($days>0) $prod .= $days.' days '.$delay; 
										if($days==0 && $hours>0)$prod .=  $hours.' hours '.$delay; 
										if($days==0 && $hours==0 )$prod .='-';
										
										if($days_tech>0) $tech .= $days_tech.' days '.$delay_tech; 
										if($days_tech==0 && $hours_tech>0)$tech .=  $hours_tech.' hours '.$delay_tech; 
										if($days_tech==0 && $hours_tech==0 )$tech .='-';
										
										if($days_seo>0) $seo .= $days_seo.' days '.$delay_seo; 
										if($days_seo==0 && $hours_seo>0)$seo .=  $hours_seo.' hours '.$delay_seo; 
										if($days_seo==0 && $hours_seo==0 )$seo .='-';
										
									$html .="<tr>";
									$html .="<td>".$quotesloop['company_name']."</td>";
									$html .="<td>".$quotesloop['title']."</td>";
									$html .="<td>".$quotesloop['turnover']."</td>";
									$html .="<td>".$prod."</td>";
									$html .="<td>".$tech."</td>";
									$html .="<td>".$seo."</td>";
									$html .="</tr>";
									$i++;
					}
					
					
					
					}
				$html .="</table>";
				
				}
				$lastsixmonth=date('Y-m-d',strtotime("-6 month"));
			$filename = $_SERVER['DOCUMENT_ROOT']."/BO/quotes_weekly_report/prod-tech-seo-report-on-$lastsixmonth.xlsx";
									
			$this->convertHtmltableToXlsx($html,$filename,FALSE); 
			chmod($filename,0777);	
			$this->_redirect("/quote/download-document?type=weekly&filename=prod-tech-seo-report-on-$lastsixmonth.xlsx");
			//$this->_redirect("/BO/quotes_weekly_report/prod-tech-seo-report-on-$lastsixmonth.xlsx");
			
		}
		
		function salesFinalanswerTimeAction(){
			$html="";
			$quotecron_obj = new Ep_Quote_Cron();
			$quotesarray=$quotecron_obj->getquoteslastsixmonths();
			$i=1;
			if($quotesarray>0){
				
				$html ="<table border=1>";
				$html .="<tr><td><b>Client Name</b></td><td><b>Name of Quote</b></td>
				<td><b>Turnover</b></td><td><b>Sales Time Took</b></td></tr>";
				
				foreach ($quotesarray as $quotesloop){
			        
					$ontimedelay="";
					$days=0;
					$hours=0;
					$salestime="";
					$delay="";
					
					
					if($quotesloop['salevalidaetime']!="" && $quotesloop['sales_validation_expires']!="")
					{							
						if($quotesloop['salevalidaetime']> date('Y-m-d H:m:s',$quotesloop['sales_validation_expires']))
						{
							 $diffdate= strtotime($quotesloop['salevalidaetime'])-$quotesloop['sales_validation_expires'];
							 $delay='<span style="color:red;">Delay</span>';
						}else{
							 $diffdate= $quotesloop['sales_validation_expires']-strtotime($quotesloop['salevalidaetime']);
							 $delay='<span style="color:green;">On time</span>';
						}
			 
						$days= abs(floor($diffdate/86400));
						$hours= floor($diffdate/3600);
						if($days>0) $salestime .= $days.' days '.$delay;
						if($days==0 && $hours>0) $salestime .= $hours.' hours '.$delay;	
						 $html .="<tr>";
						 $html .="<td>".$quotesloop['company_name']."</td>";
						 $html .="<td>".$quotesloop['title']."</td>";
						 $html .="<td>".$quotesloop['turnover']." &".$quotesloop['sales_suggested_currency'].";</td>";
						 $html .="<td>".$salestime."</td>";
						 $html .="</tr>";
						 $i++;
					}
				}
				$html .="</table>";
				
			}
			$lastsixmonth=date('Y-m-d',strtotime("-6 month"));
			$filename = $_SERVER['DOCUMENT_ROOT']."/BO/quotes_weekly_report/sales-final-report-on-$lastsixmonth.xlsx";
			$file_arrached_name='sales-final-report-on-'.$lastsixmonth.'.xlsx';						
			$this->convertHtmltableToXlsx($html,$filename,FALSE); 
			chmod($filename,0777);	
										
			$this->_redirect("/quote/download-document?type=weekly&filename=$file_arrached_name");
			
			
		}
			
	/* To update Contract mIssion percentage */
	function updateMissionPercentageAction()
	{
		$cron = new Ep_Quote_Cron();
		$contract_obj = new Ep_Quote_Quotecontract();
		$res = $cron->getDeliveries();
		$update = array();
		foreach($res as $missions)
		{
			if($missions['published_articles']>$missions['volume'])
			$missions['published_articles'] = $missions['volume'];
			$update['progress_percent'] = round(($missions['published_articles']/$missions['volume'])*100);
			
			if($update['progress_percent']==100 && $missions['cm_status']!="closed")
			{
				$update['cm_status'] = 'closed';
				$update['updated_at'] = date("Y-m-d H:i:s");
				$update['updated_by'] = "110823108965627"; /* Cron user in test site */
				//$update['cm_status'] = 'validated';
			}
			$res1 = $contract_obj->updateContractMission($update,$missions['contract_mission_id']);
			unset($update);
		}
	}

     /* To send mail for Freeze missions */
     function sendFreezeMailAction()
     {
         $cron_obj = new Ep_Quote_Cron();
         $mail_obj = new Ep_Message_AutoEmails();
         $missions = $cron_obj->getFreezeMissions();
         foreach($missions as $row)
         {
             $mail_obj->sendEMail('',$row['freeze_email_content'],$row['email'],$row['freeze_subject']);
         }
     }
	
	/* To send mail at the end of every month */
	function getExtractAction()
	{
		$cron_obj = new Ep_Quote_Cron();
		$tech_obj = new Ep_Quote_TechMissions();
		$first_date = date('Y-m-d',strtotime('first day of last month'));
		$last_date = date('Y-m-d',strtotime('last day of last month'));
		$result = $cron_obj->getExtract($first_date,$last_date);
		$previous_month = date('M',strtotime(date('Y-m')." -1 month"));
		
		if($result)
		{
			$i = 0;
			$htmltable ="<table>
					<tr>
					<td><b>Owner</b></td>
					<td><b>Client name</b></td>
					<td><b>PO name</b></td>
					<td><b>Mission</b></td>
					<td><b>Language</b></td>
					<td><b>Volume to be invoiced</b></td>
					<td><b>selling price per unit</b></td>
					<td><b>Turnover</b></td>
					<td><b>writer cost</b></td>
					<td><b>proofreading cost</b></td>
					<td><b>SEO cost</b></td>
					<td><b>tech cost</b></td>
					<td><b>Delivered count</b></td>
					</tr>";
			
			foreach($result as $row)
			{
				if($row['product']=='translation')
				{
				$mtitle = $this->product_array[$row['product']]." ".$this->producttype_array[$row['product_type']]." ".$this->getCustomName("EP_LANGUAGES",$row['language_source'])." au ".$this->getCustomName("EP_LANGUAGES",$row['language_dest']);
				$language = $this->getCustomName("EP_LANGUAGES",$row['language_source'])." -> ".$this->getCustomName("EP_LANGUAGES",$row['language_dest']);
				}
				else
				{
				$mtitle = $this->product_array[$row['product']]." ".$this->producttype_array[$row['product_type']]." in ".$this->getCustomName("EP_LANGUAGES",$row['language_source']);
				$language = $this->getCustomName("EP_LANGUAGES",$row['language_source']);
				}
				$seoturnover = $cron_obj->getSeoTurnover($row['quote_id']);
				if($seoturnover)
					$seoturnover .= " &".$row['currency']."; ";
				$tech_missions = $tech_obj->getTechMissionDetails(array('quote_id'=>$row['quote_id'],'include_final'=>'yes'));
				$techturnover = "";
				if($tech_missions)
				{
					$i = 0;
					for($i=0;$i<count($tech_missions);$i++)
					{
						$techturnover = $tech_missions[$i]['turnover'];
						if($tech_missions[$i]['package']=='team')
							$techturnover += $tech_missions[$i]['team_fee'] * $tech_missions[$i]['team_packs'];
					}
					if($techturnover)
					$techturnover .= " &".$row['currency']."; ";
				}
				if($row['proofreader_cost'])
					$row['proofreader_cost'] .= " &".$row['currency']."; ";
				$htmltable .= "<tr>
								<td >".$row['owner']."</td>
								<td >".$row['client']."</td>
								<td >".$row['contractname']."</td>
								<td >".$mtitle."</td>
								<td >".$language."</td>
								<td >".$row['tot_pub_art']."</td>
								<td >".$row['unit_price']." &".$row['currency']."; </td>
								<td ></td>
								<td >".$row['writer_cost']." &".$row['currency']."; </td>
								<td >".$row['proofreader_cost']." </td>
								<td >".$seoturnover."</td>
								<td >".$techturnover."</td>
								<td >".$row['deliveredcount']."</td>
								
								";	
			
				$htmltable .="</tr>";
				$newmission = $row['qmid'];
				$newdelivery = $row['did'];
			}
			$htmltable .= '</table>';
			$path =$_SERVER['DOCUMENT_ROOT']."/BO/quotexls/";
			$year = date("Y",strtotime($first_date));
			$filename = "wp-UKTEST-extract-".$previous_month."-".$year.".xlsx";
			$cfilename = $path.$filename;
			$this->convertHtmltableToXlsx($htmltable,$cfilename,true);
			//$this->sendMail("comptabilite@edit-place.com", "Sales Report for the month of $previous_month $year at UKTEST PTF", "PFA", $path, $filename,"rakeshm@edit-place.com");
			$this->sendMail("rakeshm@edit-place.com", "Sales Report for the month of $previous_month $year at UKITEST TEST", "PFA", $path, $filename,"kavithashree.r@gmail.com");
			unlink($cfilename);
		}
	}
	/**To get tempos weekly */
	function getTemposWeeklyAction()
	{
		$cron_obj = new Ep_Quote_Cron();
		$contractmissions = $cron_obj->getArticleCount();
		//echo '<pre>'; print_r($contractmissions);//exit;
		$email_config = $cron_obj->getConfiguration('critsend');
		$i=0;
		$dup_content_text="";
		$monday =strtotime( 'monday last week' );
		$mail_obj=new Ep_Message_AutoEmails();
		foreach($contractmissions as $row)
		{

			$cmid =  $row['contract_mission_id'];
			$oneshot =  $row['oneshot'];
			$assigned_at = $row['assigned_date'];
			
			
			
			//echo date('Y-m-d',strtotime($assigned_at . "+".$row['tempo_length']." days"));
			// Recurring
			if($oneshot=="no")
			{
				if($row['delivery_volume_option']=="every")
				{
					// Add days to assigned at by tempo_length
					if($row['tempo_length_option']=="days")
						$res = $this->checkEveryWeekRecurring($assigned_at,$row['tempo_length'],'days',$monday);
					elseif($row['tempo_length_option']=="week")
						$res = $this->checkEveryWeekRecurring($assigned_at,$row['tempo_length'],'weeks',$monday);
					elseif($row['tempo_length_option']=="month")
						$res = $this->checkEveryWeekRecurring($assigned_at,$row['tempo_length'],'months',$monday);		
					elseif($row['tempo_length_option']=="year")
						$res = $this->checkEveryWeekRecurring($assigned_at,$row['tempo_length'],'years',$monday);							
				}
				else
				{
					// Add days to assigned at by tempo_length
					if($row['tempo_length_option']=="days")
						$res = $this->checkWithinWeekRecurring($assigned_at,$row['tempo_length'],'days',$monday);	
					elseif($row['tempo_length_option']=="week")
						$res = $this->checkWithinWeekRecurring($assigned_at,$row['tempo_length'],'weeks',$monday);		
					elseif($row['tempo_length_option']=="month")
						$res = $this->checkWithinWeekRecurring($assigned_at,$row['tempo_length'],'months',$monday);
					elseif($row['tempo_length_option']=="year")
						$res = $this->checkWithinWeekRecurring($assigned_at,$row['tempo_length'],'years',$monday);
				}
				//echo  $row['contract_mission_id']."---".$res."<br>";//exit;
				if($res)
				{
					// Send Mail
					//$mail_obj=new Ep_Message_AutoEmails();
										
					if($row['company_name'])
						$client_name= $row['company_name'];
					else
						$client_name = $row['client_name'];

					$oneshot=$row['oneshot'];
					$project_manager= $bo_user = $row['first_name']." ".$row['last_name'];
					$volume_max= $row['volume_max'];
					$delivery_volume_option= $this->volume_option_array[$row['delivery_volume_option']];
					$tempo_length= $row['tempo_length'];
					$tempo_length_option = $this->duration_array[$row['tempo_length_option']];
					$product_name= $this->producttype_array[$row['product_type']];
										
					
					$followup_link="<a href='http://".$_SERVER['HTTP_HOST']."/followup/prod?submenuId=ML13-SL4&cmid=".$row['contract_mission_id']."'> click here </a>";
					
					$delivered_artices = $row['article_count'];

						
						$email_contents = $mail_obj->getAutoEmail(209);
						$orgmsg = $msg=stripslashes($email_contents[0]['Message']);
						
						eval("\$msg= \"$orgmsg\";");
						//echo $subject.'<br>'.$msg;
						
						
						//$mail_obj->sendEmail($email_config,$this->mail_from,'naveen@edit-place.com',$subject,$msg);
						
						if($delivered_artices!=$volume_max)
						{
							$dup_content_text .=$msg.'<br>';
						}
				}
			}
			elseif($oneshot=="yes")
			{
				$tempos = $cron_obj->getTempos($row['type_id'],$oneshot);
				//echo "<pre>";print_r($tempos);
				if($tempos)
				{
					foreach($tempos as $tempo)
					{
						if($tempo['oneshot_option']=="week")
							$res = $this->checkWithinWeekRecurring($assigned_at,$tempo['oneshot_length'],'weeks',$monday);
						elseif($tempo['oneshot_option']=="month")
							$res = $this->checkWithinWeekRecurring($assigned_at,$tempo['oneshot_length'],'months',$monday);
						elseif($tempo['oneshot_option']=="year")
							$res = $this->checkWithinWeekRecurring($assigned_at,$tempo['oneshot_length'],'years',$monday);
						else
							$res = $this->checkWithinWeekRecurring($assigned_at,$tempo['oneshot_length'],'days',$monday);
						
						if($res)
						{
							// Send Mail
							//$mail_obj=new Ep_Message_AutoEmails();
							$finalparam[$cmid]['oneshot']=$row['oneshot'];
							
							if($row['company_name'])
								$client_name = $row['company_name'];
							else
								$client_name = $row['client_name'];

							
							$project_manager= $row['first_name']." ".$row['last_name'];
							$one_shot_length = $tempo['oneshot_length'];
							$tempo_length= '';
							$tempo_length_option = '';
							$product_name= $this->producttype_array[$row['product_type']];
							$delivery_volume_option= '';
							$volume_max= '';
							$delivered_artices= $row['article_count'];
							
							$followup_link="<a href='http://".$_SERVER['HTTP_HOST']."/followup/prod?submenuId=ML13-SL4&cmid=".$row['contract_mission_id']."'> click here </a>";
							$email_contents = $mail_obj->getAutoEmail(209);
							$orgmsg = $msg=stripslashes($email_contents[0]['Message']);
							eval("\$msg= \"$orgmsg\";");
							
							//	echo $subject.'<br>'.$msg;	
									
							if($delivered_artices!=$one_shot_length)
							{
								$dup_content_text .=$msg.'<br>';			
							}
						}
					}
				}
			}
		}
		//echo $dup_content_text;exit;
			if($dup_content_text!="")
			{
					
					$user_emails=array('Alessia Strinati'=>'astrinati@edit-place.com','atwist'=>' atwist@edit-place.com');
					$weekly_date=date("W", $monday).' - '.date('d',$monday).' to '.date('d').' '.date('F Y');
					$content_concadinate=$dup_content_text;
					foreach ($user_emails as $bo_user => $email) 
					{
						$email_total = $mail_obj->getAutoEmail(210);
						$subject_sub = $email_total[0]['Object'];
						$orgmsge = stripslashes($email_total[0]['Message']);
						eval("\$subject= \"$subject_sub\";");
						eval("\$msge= \"$orgmsge\";");
						//echo $msge;exit;
						$mail_obj->sendEMail($email_config,$msge,$email,$subject_sub);
					}
			}
	}

	/* To check every week recurring */
	function checkEveryWeekRecurring($assigned_at,$length,$type,$monday)
	{
		//$current_date = strtotime(date('Y-m-d'));
		$current_date =strtotime( 'friday last week' );
		
		if($monday==$current_date)
		{
			$i = 1;
			do
			{
			$callength = $length * $i++;
			$calculated_date = strtotime(date('Y-m-d',strtotime($assigned_at . "+$callength $type")));
				if($calculated_date==$current_date)
				{
					return true;
				}
				else 
				{
					return false;
				}
			}
			
			while($current_date>$calculated_date);
			return false;
		}
		else
		{
			$i = 1;
			do
			{
			$callength = $length * $i++;
			$calculated_date = strtotime(date('Y-m-d',strtotime($assigned_at . "+$callength $type")));
			//echo $monday."--".$calculated_date."--".$current_date."<br>";
			if($monday<=$calculated_date && $current_date>=$calculated_date)
			{
				return true;
			}
			else
			{
				return false;
			}
			}
			while($current_date<$calculated_date);
			return false;	

		}
		
			
		
	}


	/* To check every week recurring */
	function checkWithinWeekRecurring($assigned_at,$length,$type,$monday)
	{
		//$current_date = strtotime(date('Y-m-d'));
		$current_date =strtotime( 'friday last week');
		//echo date('Y-m-d').'crewithin'.date('Y-m-d',$monday).date('Y-m-d',strtotime($assigned_at . "+$length $type")).'<br>';
		$callength = $length;
		$calculated_date = strtotime(date('Y-m-d',strtotime($assigned_at . "+$callength $type")));
		if($monday==$current_date)
		{			
			if($calculated_date==$current_date)
				return true;
			else
			return false;
			
		}
		else
		{
			if(($monday<=$calculated_date && $current_date>=$calculated_date))
				return true;
			else return false;

		}
		
	}

	function onlySalesQuoteMissionsAction()
	{
		$cron_obj = new Ep_Quote_Cron();		
		$result = $cron_obj->getOnlySalesQuoteMissions();
		if($result)
		{
			$i = 0;
			$htmltable ="<table>
					<tr>
					<td><b>Mission id</b></td>
					<td><b>Mission</b></td>
					<td><b>Sales Owner</b></td>
					<td><b>Language</b></td>
					<td><b>Words</b></td>
					<td><b>Volume</b></td>
					<td><b>Currency</b></td>
					<td><b>selling price per unit</b></td>
					<td><b>Internal cost</b></td>
					<td><b>Writer staff</b></td>
					<td><b>Writer Cost</b></td>
					<td><b>Proffreader staff</b></td>
					<td><b>Proffreader cost</b></td>
					<td><b>auture staff</b></td>
					<td><b>auture cost</b></td>
					<td><b>Staff time(Days)</b></td>
					<td><b>PO name</b></td>	
					<td><b>PO URL</b></td>						
					</tr>";
					//<td><b>Delivered count</b></td>
			
			foreach($result as $row)
			{
				if($row['product']=='translation')
				{
					$mtitle = $this->product_array[$row['product']]." ".$this->producttype_array[$row['product_type']]." ".$this->getCustomName("EP_LANGUAGES",$row['language_source'])." au ".$this->getCustomName("EP_LANGUAGES",$row['language_dest']);
					$language = $this->getCustomName("EP_LANGUAGES",$row['language_source'])." -> ".$this->getCustomName("EP_LANGUAGES",$row['language_dest']);
				}
				else
				{
					$mtitle = $this->product_array[$row['product']]." ".$this->producttype_array[$row['product_type']]." in ".$this->getCustomName("EP_LANGUAGES",$row['language_source']);
					$language = $this->getCustomName("EP_LANGUAGES",$row['language_source']);
				}
				if($row['quotecontractid'])
					$contract_url="http://".$_SERVER['HTTP_HOST']."/contractmission/contract-edit?submenuId=ML13-SL3&contract_id=".$row['quotecontractid']."&action=view";
				else
					$contract_url="";
				
				$mission_id=$row['identifier'];
				$assigned_mission=$row['sales_suggested_missions'];

				//old mission history quotes
				$historyMisssinDetails=$this->oldHistoryMissionDetails($assigned_mission,$row);
				

				$htmltable .= "<tr>
								<td >".$mission_id."</td>
								<td >".$mtitle."</td>								
								<td >".$row['sales_owner']."</td>	
								<td >".$language."</td>
								<td >".$row['nb_words']."</td>								
								<td >".$row['volume']."</td>
								<td>".ucfirst($row['currency'])."</td>
								<td >".$row['unit_price']."</td>
								<td >".$row['internal_cost']."</td>
								<td >".$historyMisssinDetails['writing_staff']."</td>
								<td >".$historyMisssinDetails['writer_cost']."</td>
								<td >".$historyMisssinDetails['proofreading_staff']."</td>
								<td >".$historyMisssinDetails['proofreading_cost']."</td>
								<td >".$historyMisssinDetails['auture_staff']."</td>
								<td >".$historyMisssinDetails['auture_cost']."</td>
								<td >".$historyMisssinDetails['staff']."</td>
								<td >".$row['contractname']."</td>
								<td >$contract_url<td>
								</tr>";	
			}
			echo $htmltable;exit;
			
			$path =$_SERVER['DOCUMENT_ROOT']."/BO/quotexls/";			
			$filename = "OnlySalesQuoteMissions.xlsx";
			$cfilename = $path.$filename;
			$this->convertHtmltableToXlsx($htmltable,$cfilename,true);
		}	
	}

	//old mission history quotes
	function oldHistoryMissionDetails($assigned_mission_id,$mission_details)
	{
		
		$archmission_obj=new Ep_Quote_Mission();
		$archParameters['mission_id']=$assigned_mission_id;
		$suggested_mission_details=$archmission_obj->getMissionDetails($archParameters);
		
		if($suggested_mission_details)
			{										
					
					$nb_words=($mission_details['nb_words']/$suggested_mission_details[0]['article_length']);
					$redactionCost=$nb_words*($suggested_mission_details[0]['writing_cost_before_signature']);
					$correctionCost=$nb_words*($suggested_mission_details[0]['correction_cost_before_signature']);
					$otherCost=$nb_words*($suggested_mission_details[0]['other_cost_before_signature']);

					$internalcost=($redactionCost+$correctionCost+$otherCost);
					$internalcost=number_format($internalcost,2,'.','');

					$missonDetails['internal_cost']=number_format($internalcost,2,'.','');
				    //$missonDetails['writer_cost']=sprintf("%.3f",($redactionCost));
				    //$missonDetails['proofreading_cost']=sprintf("%.3f",($correctionCost));
					$missonDetails['writer_cost']=round($redactionCost,2);
				    $missonDetails['proofreading_cost']=round($correctionCost,2);
				    


				    //pre-fill staff calculations

					//total mission words
					$mission_volume=$mission_details['volume'];
					$mission_nb_words=$mission_details['nb_words'];
					$total_mission_words=($mission_volume*$mission_nb_words);
			
					//words that can write per writer with in delivery weeks
					$sales_delivery_time=$quote['sales_delivery_time_option']=='hours' ? ($mission_details['sales_delivery_time']/24) : $mission_details['sales_delivery_time'];
					$sales_delivery_week=ceil($sales_delivery_time/7);

					$mission_product=$mission_details['product_type'];
					if($mission_details['product_type']=='autre')
							$mission_product='article_seo';
					$articles_perweek=$this->configval['max_writer_'.$mission_product];
					$words_perweek_peruser=$articles_perweek*250;
					$words_peruser_perdelivery=$sales_delivery_week*$words_perweek_peruser;

					//wrting and proofreading staff calculations
					$writing_staff=round($total_mission_words/$words_peruser_perdelivery);
					if(!$writing_staff || $writing_staff <1)
						$writing_staff=1;	

					$proofreading_staff=round($total_mission_words/($words_peruser_perdelivery*5));
						if(!$proofreading_staff || $proofreading_staff <1)
							$proofreading_staff=1;

					$missonDetails['writing_staff']=$writing_staff;
					$missonDetails['proofreading_staff']=$proofreading_staff;

					if($otherCost)
				    {
				    	$missonDetails['auture_cost']=number_format($otherCost,2,'.','');
				    	$autre_staff=ceil($writing_staff/5);
				    	if(!$autre_staff || $autre_staff <1)
							$autre_staff=1;
						
				    	$missonDetails['auture_staff']=$autre_staff;
				    }


					$staff_setup_length=ceil(($mission_details['mission_length']*10)/100);
					$staff_setup_length=$staff_setup_length ? $staff_setup_length :1;
					
					$staff_setup_length=$staff_setup_length < 10 ? $staff_setup_length :10;
					
					
					$missonDetails['staff']=$staff_setup_length;//.' '.$mission_details['mission_length_option'];

			}	
			return $missonDetails;
	}



	/*import xlsx document into table*/
	function importQuoteMissionAction()
	{
		$userId='110823103540627';
			$archmission_obj=new Ep_Quote_QuoteMissions();
			//ini_set('max_execution_time', 300);
            $document_name = "Onlysales-QuoteMissions-UK.xlsx";
	    	$document_path= $_SERVER['DOCUMENT_ROOT']."/BO/quotexls/".$document_name;
            $data = xlsxread($document_path);
            $prod_array=array('redaction','translation','proofreading','autre');
            $quote_mission_details = $data[0][0];
            $prod_mission_obj=new Ep_Quote_ProdMissions();
           //	echo "<pre>"; print_r($quote_mission_details); exit;
	            if(count($quote_mission_details)>1)
	            {

	                for($i=1;$i<count($quote_mission_details);$i++)
	                {

	                	$searchParameters['mission_id']=$quote_mission_details[$i][1];
	                	$missonDetails=$archmission_obj->getMissionDetails($searchParameters);
	                	$prodmissionsearch['quote_mission_id']=$quote_mission_details[$i][1];
	                	$checkmissiondetails=$prod_mission_obj->getProdMissionDetails($prodmissionsearch);
	                	if(count($missonDetails)>0 && count($checkmissiondetails)==0)
	                	{	
		                	$deleivery_length=$missonDetails[0]['mission_length'];
		                	$product=$missonDetails[0]['product'];
								foreach($prod_array as $prodtypreval)
								{	
									if(($prodtypreval=='autre' && ($quote_mission_details[$i][14]!=="" || $quote_mission_details[$i][15]!="")) || ($prodtypreval=='proofreading') || (($prodtypreval=='redaction' || $prodtypreval=='translation') && $prodtypreval==$product ) )
									{

					                	
							            $prod_mission_data['quote_mission_id']=$searchParameters['mission_id'];
							            $prod_mission_data['product']= $prodtypreval;						    			
							            $prod_mission_data['delivery_time']= $deleivery_length;
							            $prod_mission_data['delivery_option']= "days";
							            $prod_mission_data['staff']= $quote_mission_details[$i][16];

								            if($prodtypreval=='redaction' || $prodtypreval=='translation')
								            {
									            $prod_mission_data['staff_time']= $quote_mission_details[$i][10];
									        	$prod_mission_data['cost']= $quote_mission_details[$i][11];
									        }
								        	elseif($prodtypreval=='proofreading')
								        	{
								        		$prod_mission_data['staff_time']= $quote_mission_details[$i][12];
									        	$prod_mission_data['cost']= $quote_mission_details[$i][13];
								        	}
								        	elseif($prodtypreval=='autre')
								        	{
								        		$prod_mission_data['staff_time']= $quote_mission_details[$i][14];
									        	$prod_mission_data['cost']= $quote_mission_details[$i][15];
								        	}

							            $prod_mission_data['staff_time_option']= "days";
							            
							            $prod_mission_data['currency']= lcfirst($quote_mission_details[$i][7]);
							            $prod_mission_data['comments']= "";
							            $prod_mission_data['created_by']=$userId;
							            $prod_mission_data['version']	= 1;
							            
							            $prod_mission_obj->insertProdMission($prod_mission_data);
							           // echo 'import true'.$i.$searchParameters['mission_id'].'<br>';
						        	}
						        	
					            }
				            	$quoteMission_data['staff_time']=$quote_mission_details[$i][16];
				           		$archmission_obj->updateQuoteMission($quoteMission_data,$searchParameters['mission_id']);
				           }
	                }

	            }

            

	}
	
 }
 ?>

