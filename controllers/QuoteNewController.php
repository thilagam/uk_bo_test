<?php
/**
 * Quote Controller for Client create/Edit and Quote Create/Edit
 * @author Arun
 * @version 1.0
 */
class QuoteNewController extends Ep_Controller_Action
{	

	public function init()
	{
		parent::init();		
		$this->_view->lang = $this->_lang;
		$this->adminLogin = Zend_Registry::get('adminLogin');
        $this->_view->userId = $this->adminLogin->userId;
        $this->_view->user_type= $this->adminLogin->type ;
        $this->sid = session_id();	
        $this->email_from = 'work@edit-place.com';
		$this->url = $this->_config->path->bo_base_path;
        $this->quote_documents_path=APP_PATH_ROOT.$this->_config->path->quote_documents;
        $this->mission_documents_path=APP_PATH_ROOT.$this->_config->path->mission_documents;
		$this->_view->fo_path=$this->fo_path=$this->_config->path->fo_path;
		$this->_view->fo_root_path=$this->fo_root_path=$this->_config->path->fo_root_path;
		
		/*webservice links*/
		$this->_view->crossDomain=$this->crossDomain='http://admin-ep.edit-place.com';
		$this->web_service_hoq_prices_link=$this->crossDomain.'/webservice/get-hoq-prices';
		$this->web_service_history_details_link=$this->crossDomain.'/webservice/history-mission-details';
		$this->web_service_prod_details_link=$this->crossDomain.'/webservice/get-prod-mission-details';	
		$this->currency_exchange_link='http://api.fixer.io/latest?symbols=USD,GBP';
		
        
        $this->quote_creation = Zend_Registry::get('Quote_creation_new');

        $this->product_array=array(
    							"redaction"=>"Writing",
								"translation"=>"Translation",
								"autre"=>"Other",
								"proofreading"=>"Correction",
								"content_strategy"=>"Content Strategy"
        						);
        $this->seo_product_array=array(
        						"seo_audit"=>"SEO audit",
        						"smo_audit"=>"SMO audit",
    							"redaction"=>"Writing",
								"translation"=>"Translation",
								"proofreading"=>"Correction",
								"autre"=>"Other",
								"content_strategy"=>"Content Strategy"
        						);
		$this->_view->seo_producttype_array=$this->seo_producttype_array=array(
    							"analyse_content_seo"=>"Analyse SEO"
    							);					

        $this->_view->producttype_array=$this->producttype_array=array(
    							"article_de_blog"=>"Blog article",
								"descriptif_produit"=>"Product description",
								"article_seo"=>"SEO article",
								"guide"=>"Guide",
								"news"=>"News",
								"autre"=>"Others"
        						);

       $this->status_array=array(
    							"auto_skipped"=>"Auto skipped",
								"skipped"=>"Skipped",
								"challenged"=>"Challenged",
								"not_done"=>"Not reviewed",
								"validated"=>"Validated",
								"closed"=>"Closed"
        						);        

		$this->closedreason = array(
								"too_expensive"=>'Too expensive',
								"no_reason_client"=>'No answers from client',
								"project_cancelled"=>'Project cancelled',
								"delivery_time_long"=>'Delivery timings too long',
								"test_art_prob"=>'Issue with test article',
								"quote_permanently_lost"=>'Permanently lost'
								
							);
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

		$this->_view->origin_contact = array(
							"ancient_client"=>"Ancient client",
							"cold_hunting" =>"Chasse  (LinkedIn, Actu, etc.)",
							"site_plateform"	=>"Edit-Place (Site/Plateforme)",
							"event"=>"Event/Salon",
							"jwmf"=>"JW/MF",
							"sponsorship"=>"Partenaire (Agence, Apporteur d'affaires)", 
							"network"=>"R&#233;seau perso",
							"business_news"=>"Salari&#233; EP"											
							);			
		$this->_view->current_year=date('Y');
							
        //get Ep contacts by type
    	$client_obj=new Ep_Quote_Client();	
		$get_EP_contacts=$client_obj->getEPContacts('"salesuser","superadmin"');
		$this->_view->assign_contacts=$get_EP_contacts;
		
		/*get package staffing config*/
		$hobj=new Ep_Quote_HistoryQuoteMissions();
		$packageStaffConfig=$hobj->getPackageStaffingConfig();
		if($packageStaffConfig)
			$this->packageStaffConfig=$packageStaffConfig;
		//echo "<pre>";print_r($packageStaffConfig);
		

		
        if($this->_helper->FlashMessenger->getMessages()) {
	            $this->_view->actionmessages=$this->_helper->FlashMessenger->getMessages();
	            //echo "<pre>";print_r($this->_view->actionmessages); 
	    }

	    $this->_view->salesquotelistlimitvar=$this->salesquotelistlimitvar=10;
		
	}
	/*quote creation step1 action*/
	public function createStep1Action()
	{
		$quote_params=$this->_request->getParams();
		if(!$quote_params['qaction'])
		{
			$quote_params['qaction']='duplicate';
		}

		if(($quote_params['qaction']=='edit' || $quote_params['qaction']=='duplicate') && $quote_params['quote_id'] )
		{
				$this->autoQuoteSession($quote_params['quote_id'],$quote_params['qaction']);
		}
		else if($quote_params['qaction']=='new')
		{		

			unset($this->quote_creation->create_step1);			
            unset($this->quote_creation->create_mission);
            unset($this->quote_creation->select_missions);
            unset($this->quote_creation->tech_mission);
            unset($this->quote_creation->custom);
			unset($this->quote_creation->send_quote);

			if($quote_params['client_id'])
				$this->quote_creation->create_step1['client_id']=$quote_params['client_id'];
			else if(!$quote_params['client_id'] && $this->adminLogin->type=='superadmin')
				$this->quote_creation->create_step1['client_id']=$this->configval["test_client_id"];
			
		}

		if(!is_array($this->quote_creation->create_step1['client_type']))
		{
			$this->quote_creation->create_step1['client_type']=array('new');
		}

		//echo "<pre>"; print_r($this->quote_creation->custom); exit;


		//All companies list
		$client_obj=new Ep_Quote_Client();
		if($quote_params['qaction']!='edit')
		{
			$searchparams['client_type'][]='new';
		}
		if(count($this->quote_creation->create_step1['client_type'])>0)
			$searchparams['client_type']=$this->quote_creation->create_step1['client_type'];
		
		$company_list=$client_obj->getAllCompanyList($searchparams);
		

		if($company_list!='NO')
			$this->_view->company_list=$company_list;
		
		if(!is_array($this->quote_creation->create_step1['client_websites']))
			$this->quote_creation->create_step1['client_websites']=array();



		//All categories list
		$categories_array=$this->_arrayDb->loadArrayv2("EP_ARTICLE_CATEGORY", $this->_lang);
        //natsort($categories_array);        
        $this->_view->ep_categories_list=$categories_array;

        //echo "<pre>";print_r($this->quote_creation->create_step1);


        $this->_view->create_step1=$this->quote_creation->create_step1;
        $this->_view->custom=$this->quote_creation->custom;
		 
		$this->render('create-step1');
	}
	/*Get all websites of a client*/
	public function getClientWebsitesAction()
	{
		$clientParameters=$this->_request->getParams();
		$client_obj=new Ep_Quote_Client();
		$client_id=$clientParameters['client_id'];

		if($client_id)
		{
			$site_list=$client_obj->getClientWebsites($client_id);
			if($site_list!='NO')
			{
				$websites_list=$site_list[0]['website'];
				$websites_list=explode("|",$websites_list);

				/*if($this->quote_creation->custom['action']=='edit' && $this->quote_creation->custom['create_new_version']!='yes')
					$disabled=' disabled';
				else
					$disabled='';*/

				if(is_array($websites_list) && count($websites_list)>0)
				{

					foreach($websites_list as $i=>$site)
					{
						if($site)
						{
							if(in_array($site,$this->quote_creation->create_step1['client_websites']))
								$checked=' checked';
							else		
								$checked='';
							$web_sites.='<div class="checkbox">
										<label><input type="checkbox" '.$disabled.' value="'.$site.'" class="icheck validate[required]" name="client_websites[]" '.$checked.' style="opacity: 0;" data-toggle="checkbox" id="websites">  <a href="'.$site.'" target="_blank">'.$site.'</a>
										</label>
									</div> 
								</label>';
						}		
					}	
					echo $web_sites;	
				}				
			}
			exit;
		}
	}
	//ajax function to get client list based on type
	public function getClientTypeListAction()
	{
		$clientParameters=$this->_request->getParams();
		$client_types=$clientParameters['client_type'];	

		$client_obj=new Ep_Quote_Client();
		
		$searchparams['client_type']=explode(",",$client_types);		
		$company_list=$client_obj->getAllCompanyList($searchparams);

		$options='<option value="">Select Company</option>';

		if($company_list!='NO')
		{
			foreach($company_list as $id=>$email)
			{
				$options.='<option value="'.$id.'"">'.$email.'</option>';
			}
		}
		echo $options;

	}
	
	/* check whether all clients details exists to create a quote or not*/
	function checkClientMandatoryDetailsAction()
	{
		$parameters=$this->_request->getParams();
		$client_id=$parameters['client_id'];
		if($client_id)
		{
			$contact_obj=new Ep_Quote_ClientContacts();
			$contactDetails=$contact_obj->getClientMainContacts($client_id);
			if($contactDetails=='NO')
			{
				$exists='NotExists';
			}
			else
			{
				$client_obj=new Ep_Quote_Client();
				$clientDetails=$client_obj->getClientDetails($client_id);
				if($clientDetails!='NO')
				{
					$client_info=$clientDetails[0];	
					
					$company_name=$client_info['company_name'];
					$address=$client_info['address'];
					$zipcode=$client_info['zipcode'];
					$city=$client_info['city'];
					$country=$client_info['country'];					
					$siret_applicable=$client_info['siret_applicable'];
					$siret=($siret_applicable=='yes'?$client_info['siret'] : 'no');
					
					//echo "$company_name -- $address -- $zipcode -- $city -- $country -- $siret ";
					
					if($company_name && $address && $zipcode && $city && $country!='')
					{
						$exists='Exists';
					}
					else
					{
						$exists='NotExists';
					}
				}
				else
				{
					$exists='NotExists';
				}
			}
			
			echo json_encode(array('status'=>$exists));
			
		}
		
	}
	
	public function saveQuoteStep1Action()
	{
		//echo "<pre>";	print_r($this->quote_creation->custom); exit;
		if($this->_request-> isPost()  && $this->adminLogin->userId)            
        {
			$step1_params=$this->_request->getParams();
			//echo "<pre>";print_r($step1_params);exit;

						

			$client_id=$step1_params['client_id'];
			$category=$step1_params['category'];
			$category_other=$step1_params['category_other'];
			$currency='euro';//$step1_params['currency'];
			$title=$step1_params['title'];
			if($currency=='euro')
				$conversion=1;
			else	
				$conversion=$step1_params['conversion'];
			

			$this->quote_creation->create_step1['client_type']=$step1_params['client_type']; //client type filter_list(oid)

			//added w.r.t disabling client/category/website in edit mode
			if(!$client_id)
				$client_id=$this->quote_creation->create_step1['client_id'];
			
			if(!$category)
				$category=$this->quote_creation->create_step1['category'];
			if(!is_array($step1_params['client_websites']))
				$step1_params['client_websites']=$this->quote_creation->create_step1['client_websites'];

			$client_websites=is_array($step1_params['client_websites']) ? $step1_params['client_websites'] : array();
			$add_websites=is_array($step1_params['urls'])? $step1_params['urls'] : array();

			if($client_id && $category && ((is_array($client_websites)&& count($client_websites)>0)|| (is_array($add_websites)&& count($add_websites)>0)))
			{
				
				$this->quote_creation->create_step1['client_id']=$client_id;
				$this->quote_creation->create_step1['category']=$category;
				$this->quote_creation->create_step1['currency']=$currency;
				$this->quote_creation->create_step1['conversion']=$conversion;
				$this->quote_creation->create_step1['title']=$title;			
				
				
				$client_obj=new Ep_Quote_Client();
				$client_details=$client_obj->getClientDetails($client_id);
				if($client_details!='NO')
				{
					$this->quote_creation->create_step1['company_name']=$client_details[0]['company_name'];
					$this->quote_creation->create_step1['ca_number']=$client_details[0]['ca_number'];
					$this->quote_creation->create_step1['client_code']=$client_details[0]['client_code'];
					$this->quote_creation->create_step1['twitter_screen_name']=$client_details[0]['twitter_screen_name'];
					$this->quote_creation->create_step1['client_id']=$client_details[0]['identifier'];
				}		
                
				
				/**Create title for the quote**/
				if($this->quote_creation->custom['version'])
					$version='v'.$this->quote_creation->custom['version'];
				else
					$version='v1';

					
				
				if($this->quote_creation->create_step1['client_id']==$this->configval["test_client_id"])
						$this->quote_creation->create_step1['quote_title']=$this->quote_creation->create_step1['company_name'].' '.$title." - ".$version;
				else	
					$this->quote_creation->create_step1['quote_title']=$this->quote_creation->create_step1['company_name'].' '.$title." - ".$version;

				/*version*/
				if($this->quote_creation->custom['create_new_version']=='yes' && $this->quote_creation->custom['quote_id'])
				{
					$old_version='v'.($this->quote_creation->custom['version']-1);
					$new_version='v'.($this->quote_creation->custom['version']);
					$old_title=$this->quote_creation->create_step1['quote_title'];
					$this->quote_creation->create_step1['quote_title']=str_replace($old_version, $new_version, $old_title);
				}
				//echo $this->quote_creation->create_step1['quote_title'];exit;

				if($category=='other')
					$this->quote_creation->create_step1['category_other']=$category_other;

				//$this->quote_creation->create_step1['client_websites']=array_values(array_unique(array_merge($client_websites,$add_websites)));
				$this->quote_creation->create_step1['client_websites']=array_merge($client_websites,$add_websites);
				if(!$this->quote_creation->create_step1['quote_by'])
					$this->quote_creation->create_step1['quote_by']=$this->adminLogin->userId;
				

				//update new website of client
				if((is_array($add_websites)&& count($add_websites)>0))
				{
					$client_obj=new Ep_Quote_Client();
					$current_websites=array();
					$websites_info=$client_obj->getClientWebsites($client_id);
					if($websites_info!='NO')
					{
						$current_websites=$websites_info[0]['website'];
						$current_websites=explode("|",$current_websites);
					}

					$update_websites=array_values(array_unique(array_merge($current_websites,$add_websites)));

					$client_data['website']=implode("|",$update_websites);
					$client_obj->updateClientProfile($client_data,$client_id);

					//echo "<pre>";print_r($update_websites);	exit;
				}
				//echo "<pre>";print_r($this->quote_creation->create_step1);exit;
				$missionCount=count($this->quote_creation->create_mission['product']);
				$tmissionCount=count($this->quote_creation->tech_mission['product']);
				
				
				/*updating quote step1 info to DB*/
				if($this->quote_creation->custom['quote_id'])
				{
					$quote_obj=new Ep_Quote_Quotes();
					
					$quoteIdentifier=$this->quote_creation->custom['quote_id'];					
					$quoteReview=$quote_obj->getQuoteDetails($quoteIdentifier);
					if(count($quoteReview)>0)
					{						
						$quotes_data['client_id']=$this->quote_creation->create_step1['client_id'];	
						$quotes_data['category']=$this->quote_creation->create_step1['category'];
						$quotes_data['sales_suggested_currency']=$this->quote_creation->create_step1['currency'];
						$quotes_data['conversion']=$this->quote_creation->create_step1['conversion'];		
						$quotes_data['title']=$this->quote_creation->create_step1['quote_title'];	
						
						$quote_obj->updateQuote($quotes_data,$quoteIdentifier);
					}
					$this->_redirect("/quote-new/client-brief?qction=briefing&quote_id=".$quoteIdentifier);
				}	
				else{
					$this->_redirect("/quote-new/client-brief");
				}				
			}
			else
				$this->_redirect("/quote-new/create-step1");

		}	
	}
	/*client details and brief creation page*/
	function clientBriefAction()
	{		
		
		$clientBriefParams=$this->_request->getParams();
		
		if(($clientBriefParams['qaction']=='briefing') && $clientBriefParams['quote_id'] )
		{
				$this->autoQuoteSession($clientBriefParams['quote_id'],$clientBriefParams['qaction']);
		}
			
		//echo "<pre>"; print_r($this->quote_creation->create_step1);	exit;
		if($this->quote_creation->create_step1['client_id'])
		{
			$user_identifier=$this->adminLogin->userId;
			$quotelog_obj=new Ep_Quote_QuotesLog();
			/*calculate total turnover for client per year*/
			$contract_obj=new Ep_Quote_Quotecontract();
		 	$clientdetails=$contract_obj->clientContractCurrentYear(date('Y'),$this->quote_creation->create_step1['client_id']);
		 	$turnoverval=explode('.',$clientdetails[0]['ca_year']);
		 	//echo '<pre>'; print_r($turnoverval); exit;
		 	if($turnoverval[0])	 	$this->quote_creation->create_step1['turnover']=$turnoverval[0];
		 	else $this->quote_creation->create_step1['turnover']=0;

		 	if($turnoverval[1])	$this->quote_creation->create_step1['turnover1']=$turnoverval[1];
		 	else $this->quote_creation->create_step1['turnover1']=.0;

		 	if($clientdetails[0]['sales_suggested_currency'])
				$this->quote_creation->create_step1['sales_suggested_currency']=$clientdetails[0]['sales_suggested_currency'];
		 	else 
				$this->quote_creation->create_step1['sales_suggested_currency']='euro';
			
			
			
			//get client contact details
			$clientcontact_obj = new Ep_Quote_ClientContacts();
			$clientContacts = $clientcontact_obj->getClientContactsDetails($this->quote_creation->create_step1['client_id']);
			if($clientContacts)
			{
				if(!$this->quote_creation->create_step1['contact_client'])
				{
					foreach($clientContacts as $contact)
					{
						if($contact['main_contact']=='yes')
						{
							$this->quote_creation->create_step1['contact_client']=$contact['identifier'];
						}
					}
				}
			
				$this->quote_creation->create_step1['clientContacts'] = $clientContacts;	
			}	
			
		
			$this->_view->create_step1=$this->quote_creation->create_step1;
			$this->_view->custom=$this->quote_creation->custom;
			$this->_view->send_quote=$this->quote_creation->send_quote;
			$this->_view->user_id=$this->adminLogin->userId;
			 if($this->quote_creation->custom['quote_id']){
			 	$this->_view->quote_id=$this->quote_creation->custom['quote_id'];
			 	$commentDetails=$quotelog_obj->getquotescomments($this->quote_creation->custom['quote_id'],'quote_forum');
			 	if(count($commentDetails)>0)
				$commentDetails=$this->formatCommentDetails($commentDetails);
				
			$this->_view->commentDetails=$commentDetails;
			 	//echo "<pre>"; print_r($commentDetails);
			 }
			 

			 if($this->quote_creation->custom['quote_id'])
			 {
			 	$userDetail_obj=new Ep_Quote_Quotes();
				$manageDetails=$userDetail_obj->getManagersList();

				foreach($manageDetails as $managers)
				{
					if($managers['first_name']=="")
					{
					$managersDetails[$managers['identifier']]= $managers['email'];
					}
					else
					{
					$managersDetails[$managers['identifier']]= frenchCharsToEnglish($managers['first_name']).' '.frenchCharsToEnglish($managers['last_name']);
					}
				}

				$this->_view->fo_path=$this->_config->path->fo_path;
				$this->_view->managersDetails=$managersDetails;

				if($this->quote_creation->send_quote['brief_email_notify'] !="")
			 	{
			 	$briefmail=explode(",",$this->quote_creation->send_quote['brief_email_notify']);
			 	$this->_view->brief_mail=$briefmail;
			 	}
			 }
			 
			 
			if((is_array($this->quote_creation->create_mission['product']) && count($this->quote_creation->create_mission['product'])>0) || count($this->quote_creation->tech_mission['product'])>0 )
			{
				$this->_view->missions_exists='yes';
			}			
			
			$this->render('quote-client-brief');
		}
		else{
			$this->_redirect("/quote-new/create-step1?qaction=new");
		}	
	}	
	/*save all client brief details in send quote session*/
	function saveClientBriefAction()
	{
		$clientBriefParams=$this->_request->getParams();
		//echo "<pre>"; print_r($_FILES);print_r($clientBriefParams);exit;
		if($clientBriefParams)
		{
					/*$this->quote_creation->send_quote['sales_comment']=$clientBriefParams['bo_comments'];*/
					$this->quote_creation->send_quote['client_overview']=$clientBriefParams['client_overview'];
					$this->quote_creation->send_quote['client_email_text']=$clientBriefParams['client_email'];
					//$this->quote_creation->send_quote['documents_path']='path';
					$this->quote_creation->send_quote['conversion']=$this->quote_creation->create_step1['conversion'];		
					/*$this->quote_creation->send_quote['client_know']=$clientBriefParams['client_know']? 'no':'yes';
					$this->quote_creation->send_quote['urgent']=$clientBriefParams['urgent']? 'yes':'no';					$this->quote_creation->send_quote['urgent_comments']=$clientBriefParams['urgent_comments']?$clientBriefParams['urgent_comments']:NULL;*/
					
					//NEW QUOTE FILEDS 
					$client_aims=$clientBriefParams['client_aims'];
					$this->quote_creation->send_quote['client_aims']=$client_aims;//implode(",",$client_aims);

					foreach($client_aims as $aim)
					{
						$client_prio[]=$clientBriefParams['priority_'.$aim];
						$this->quote_creation->send_quote['client_prio_'.$aim]=$clientBriefParams['priority_'.$aim];
					}
					//$this->quote_creation->send_quote['client_prio']=$client_prio;//implode(",",$client_prio);

					$client_aims_comments=$clientBriefParams['client_aims_comments'];
					$this->quote_creation->send_quote['client_aims_comments']=$client_aims_comments;


					$content_ordered_agency=$clientBriefParams['content_ordered_agency'];
					$this->quote_creation->send_quote['content_ordered_agency']=$content_ordered_agency;
					if($content_ordered_agency=='yes')
					{
						$this->quote_creation->send_quote['agency_name']=$clientBriefParams['agency_name'];
					}						
					$this->quote_creation->send_quote['budget']=$clientBriefParams['budget'];
					$this->quote_creation->send_quote['budget_currency']=$clientBriefParams['budget_currency'];
					


					$this->quote_creation->send_quote['estimate_sign_percentage']=$clientBriefParams['estimate_sign_percentage'];
					//$this->quote_creation->send_quote['estimate_sign_date']=$clientBriefParams['estimate_sign_date'];
					$this->quote_creation->send_quote['estimate_sign_comments']=$clientBriefParams['estimate_sign_comments'];	
					
					/*New Fields */
					$this->quote_creation->send_quote['do_signer']=$clientBriefParams['do_signer'];
					$this->quote_creation->send_quote['origin_contact']=$clientBriefParams['origin_contact'];
					$this->quote_creation->send_quote['client_appoinment']=$clientBriefParams['client_appoinment'];
					$quote_obj=new Ep_Quote_Quotes();
			        	$techObj=new Ep_Quote_TechMissions();
			        	$client_id=$this->quote_creation->create_step1['client_id'];
						$quote_monthly_cnt=$quote_obj->getMonthlyCount($client_id);
						$quote_monthly_cnt+=1;					
			        	
			        	
			        	 /*Insert quotes data */


						$quotes_data['client_id']=$this->quote_creation->create_step1['client_id'];
						$quotes_data['category']=$this->quote_creation->create_step1['category'];
						if($quotes_data['category']=='other')
								$quotes_data['category_other']=isodec($this->quote_creation->create_step1['category_other']);
						if($this->quote_creation->create_step1['client_websites'])
						$quotes_data['websites']=implode("|",$this->quote_creation->create_step1['client_websites']);
						$quotes_data['quote_by']=$this->quote_creation->create_step1['quote_by'];
						$quotes_data['created_by']=$this->adminLogin->userId;
						//$quotes_data['sales_suggested_price']=$this->quote_creation->create_mission['final_turnover'];
						$quotes_data['sales_suggested_currency']=$this->quote_creation->create_step1['currency'];
						//$quotes_data['sales_comment']=$this->quote_creation->send_quote['sales_comment'];
						$quotes_data['client_overview']=$this->quote_creation->send_quote['client_overview'];
						$quotes_data['client_email_text']=$this->quote_creation->send_quote['client_email_text'];
						
						$quotes_data['conversion']=$this->quote_creation->send_quote['conversion'];

						/*$quotes_data['client_know']=$this->quote_creation->send_quote['client_know']? 'no':'yes';
						$quotes_data['urgent']=$this->quote_creation->send_quote['urgent']? 'yes':'no';*/

						$client_aims=$this->quote_creation->send_quote['client_aims'];
						$quotes_data['client_aims']=implode(",",$client_aims);

						foreach($client_aims as $aim)
						{
							$client_prio[]=$this->quote_creation->send_quote['client_prio_'.$aim];
						}
						$quotes_data['client_prio']=implode(",",$client_prio);

						$client_aims_comments=$this->quote_creation->send_quote['client_aims_comments'];
						$quotes_data['client_aims_comments']=$client_aims_comments;


						$content_ordered_agency=$this->quote_creation->send_quote['content_ordered_agency'];
						$quotes_data['content_ordered_agency']=$content_ordered_agency;
						if($content_ordered_agency=='yes')
						{
							$quotes_data['agency_name']=$this->quote_creation->send_quote['agency_name'];
						}	
						$quotes_data['budget']=$this->quote_creation->send_quote['budget'];
						$quotes_data['budget_currency']=$this->quote_creation->send_quote['budget_currency'];
						

						$quotes_data['estimate_sign_percentage']=$this->quote_creation->send_quote['estimate_sign_percentage'];
						//$quotes_data['estimate_sign_date']=$this->quote_creation->create_mission['estimate_sign_date'];
						$quotes_data['estimate_sign_comments']=$this->quote_creation->send_quote['estimate_sign_comments'];

						$quotes_data['do_signer']=$this->quote_creation->send_quote['do_signer'];
						$quotes_data['origin_contact']=$this->quote_creation->send_quote['origin_contact'];
						$quotes_data['client_appoinment']=$this->quote_creation->send_quote['client_appoinment'];

						$quotes_data['tec_review']='auto_skipped';	
						$quotes_data['seo_review']='auto_skipped';
						$quotes_data['prod_review']='auto_skipped';	
							if($this->quote_creation->custom['create_new_version']=='yes' && $this->quote_creation->custom['quote_id'])
							{
								$version=$this->quote_creation->custom['version'];
								$quotes_data['sales_review']='not_done';
								$quotes_data['version']=$this->quote_creation->custom['version'];
								$old_version='v'.($this->quote_creation->custom['version']-1);
								$new_version='v'.($this->quote_creation->custom['version']);
								$old_title=$this->quote_creation->create_step1['quote_title'];

								$this->quote_creation->create_step1['quote_title']=str_replace($old_version, $new_version, $old_title);

			        			$quotes_data['title']=$this->quote_creation->create_step1['quote_title'];
								$quoteIdentifier=$this->quote_creation->custom['quote_id'];
									/*inserting quote version*/
								if($quoteIdentifier)
								{
									$qversion=($this->quote_creation->custom['version']-1);
									$checkquoteVersion=$quote_obj->getQuoteVersionDetails($quoteIdentifier,$qversion);

			        				if($checkquoteVersion==0)
			        				{
										$quote_obj->insertQuoteVersion($quoteIdentifier);	
										$quoteversion=true;
			        				}
								}
							}
							else
							{
								$quotes_data['sales_review']='briefing';							
								$quotes_data['title']=$this->quote_creation->create_step1['quote_title'];
							}
						$quotes_data['created_at']=date("Y-m-d H:i:s");	
						$quotes_data['updated_at']=NULL;
						$quotes_data['closed_comments']='';
						$quotes_data['signed_comments']=NULL;
						$quotes_data['signed_at']=NULL;
						$quotes_data['sign_expire_timeline']=time()+(21*24*60*60);
						$quotes_data['closed_reason']=NULL;				
						$quotes_data['boot_customer']=NULL;
						$quotes_data['contact_client']=$this->quote_creation->create_step1['contact_client'];//client contact selected in header
						
						

							/**New quotes data from new design**/
						$quotes_data['is_new_quote']=1;	
						if($this->quote_creation->custom['quote_id'])
						{
							$quoteIdentifier=$this->quote_creation->custom['quote_id'];
							$quotes_data['updated_at']=date("Y-m-d H:i:s");	
							$quoteReview=$quote_obj->getQuoteDetails($quoteIdentifier);
							if(count($quoteReview)>0)
							{
								$quotes_data['tec_review']=$quoteReview[0]['tec_review'];	
								$quotes_data['seo_review']=$quoteReview[0]['seo_review'];
								$quotes_data['prod_review']=$quoteReview[0]['prod_review'];		
							}
							
							$quote_obj->updateQuote($quotes_data,$quoteIdentifier);
							$edited=true;
						}
						else
						{
							$quotes_data["response_time"]=time()+($this->configval['quote_sent_timeline']*60*60);
							$quote_obj->insertQuote($quotes_data);							
							$quoteIdentifier=$quote_obj->getIdentifier();
							$newquote=true;
						}
						
						if(count($_FILES['quote_documents']['name'])>0)	
						{
							$update = false;
							$documents_path=array();
							$documents_name=array();
							foreach($_FILES['quote_documents']['name'] as $index=>$quote_files)
							{
								if($_FILES['quote_documents']['name'][$index]!='')
								{	
									//upload quote documents
								
									$quoteDir=$this->quote_documents_path.$quoteIdentifier."/";
									if(!is_dir($quoteDir))
										mkdir($quoteDir,TRUE);
									chmod($quoteDir,0777);
									$document_name=frenchCharsToEnglish($_FILES['quote_documents']['name'][$index]);
									$pathinfo = pathinfo($document_name);
									$document_name =$pathinfo['filename'].rand(100,1000).".".$pathinfo['extension'];
									$document_name=str_replace(' ','_',$document_name);
									$document_path=$quoteDir.$document_name;
									if (move_uploaded_file($_FILES['quote_documents']['tmp_name'][$index], $document_path))
										chmod($document_path,0777);
				
									$update = true;
									$documents_path[]=$quoteIdentifier."/".$document_name;
									$documents_name[]= $document_name;
									// str_replace('|',"_",$clientBriefParams['document_name'][$index]);
								}

							}
							if($update)
							{
								 $quotes_update_data = array();
								 $quoteDetails=$quote_obj->getQuoteDetails($quoteIdentifier);
								 $uploaded_documents1 = explode("|",$quoteDetails[0]['documents_path']);
								 $documents_path =array_merge($documents_path,$uploaded_documents1);
								 $quotes_update_data['documents_path']=implode("|",$documents_path);
								 $document_names =array_filter(explode("|",$quoteDetails[0]['documents_name']));
								 $documents_name =array_merge($documents_name,$document_names);
								 $quotes_update_data['documents_name']=implode("|",$documents_name);
								 $quote_obj->updateQuote($quotes_update_data,$quoteIdentifier);
							}
							//echo "<pre>";print_r($quotes_update_data);print_r($documents_name);
							
						}	

						//intimation email to seb,alessia and yannick
						/*if(!$edited)
						{

							$quoteEditDetails=$quote_obj->getQuoteDetails($quoteIdentifier);

					        $client_obj=new Ep_Quote_Client();
					        //$intimate_users=$client_obj->getEPContacts('"facturation"');
					        //$intimate_users[$this->adminLogin->userId]=$this->adminLogin->userId;
					        $intimate_users=array('120913114641236'=>'astrinati@edit-place.com',
					        					  '130424101813622'=>'ymichellod@edit-place.com',
					        					  '141127175625894'=>'schateau@edit-place.com'
					        					  );//astrinati@edit-place.com, ymichellod@edit-place.com and schateau@edit-place.com
					        if(count($intimate_users)>0)
							{
								
								foreach($intimate_users as $user=>$name)
								{
									$mail_obj=new Ep_Message_AutoEmails();
									$receiver_id=$user;
									$mail_parameters['bo_user']=$user;
									$mail_parameters['sales_user']=$this->adminLogin->userId;
									//$mail_parameters['quote_title']=$quotes_data['title'];
									//$mail_parameters['sales_suggested_price']=$quotes_data['sales_suggested_price']." ".$quotes_data['sales_suggested_currency']."s";
									//$mail_parameters['followup_link_en']='/quote/sales-quotes-list?submenuId=ML13-SL2';
									$mail_parameters['validate_link']='/quote/quote-followup?quote_id='.$quoteIdentifier.'&submenuId=ML13-SL2';

									$mail_parameters['client_name']=$quoteEditDetails[0]['company_name'];
									$mail_obj->sendQuotePersonalEmail($receiver_id,174,$mail_parameters);        	
					        	}
					        }    
					        
					        
					        
					        $newquote_head=array('120206112651459'=>'cleguille@edit-place.com',
					                             '110920152530186'=>'mfouris@edit-place.com');
							
								//new quote created send Email to head sales need to add Thaibault
								
								
								
											foreach($newquote_head as $userhead=>$emailshead){
												$receiverhead_id=$userhead;
												$mailhead_parameters['sales_user']=$quoteEditDetails[0]['quote_by'];
												$mailhead_parameters['bo_user']=$receiverhead_id;
												$mailhead_parameters['turn_over']=$quoteEditDetails[0]['turnover'];
												$mailhead_parameters['challenge_link']='/quote/sales-quotes-list?submenuId=ML13-SL2';
												
												$mail_obj=new Ep_Message_AutoEmails();
												
												$mail_obj->sendQuotePersonalEmail($receiverhead_id,203,$mailhead_parameters);
												
										}
					        
					   	 } */

					 //if new version created email send to sales manager
					    $quoteEditDetails=$quote_obj->getQuoteDetails($quoteIdentifier);
					    
					    if($quoteEditDetails[0]['version']>1 && $quoteversion)
					    {
					    	$client_obj=new Ep_Quote_Client();
					   
							$email_head_sale=array('120206112651459'=>'cleguille@edit-place.com',
					                             '110920152530186'=>'mfouris@edit-place.com'); // need to add thaibault
								if(count($email_head_sale)>0 ){
												   
											foreach($email_head_sale as $user=>$emails){
												$receiver_id=$user;
												//$headmail_obj=new Ep_Message_AutoEmails();
												$headmail_parameters['version']=true;
												$headmail_parameters['subject']='A new quote V'.$quoteEditDetails[0]['version'].' has been created on Workplace';
												$bouser=$client_obj->getQuoteUserDetails($receiver_id);
												$headmail_parameters['bo_user_name']=$bouser[0]['first_name'].' '.$bouser[0]['last_name'];
												$headmail_parameters['emailid']=$emails;
												$headmail_parameters['bo_user']=$this->adminLogin->userId;
												$quoteuser=$client_obj->getQuoteUserDetails($this->adminLogin->userId);
												$headmail_parameters['quote_user']= $quoteuser[0]['first_name'].' '.$quoteuser[0]['last_name'];
												$headmail_parameters['quote_title']=$quoteEditDetails[0]['title'];
												$headmail_parameters['estimate_sign_percentage']=$quoteEditDetails[0]['estimate_sign_percentage'];
												$headmail_parameters['sales_user']=$this->adminLogin->userId;
												$headmail_parameters['turn_over']=$quoteEditDetails[0]['final_turnover'];
												$headmail_parameters['client_id']=$quoteEditDetails[0]['client_id'];
												$headmail_parameters['client_name']=$quoteEditDetails[0]['company_name'];
												$headmail_parameters['quote_version']=$quoteEditDetails[0]['version'];
												$headmail_parameters['agency_name']=$quoteEditDetails[0]['agency_name'];	
												$headmail_parameters['followup_link']='http://'.$_SERVER['HTTP_HOST'].'/quote-new/client-brief?qaction=briefing&quote_id='.$quoteEditDetails[0]['identifier'];
												//$headmail_parameters['followup_link']='/quote/quote-followup?quote_id='.$quoteEditDetails[0]['identifier'];	
												//print_r($headmail_parameters); exit;
												$this->getNewQuoteEmails($headmail_parameters);
												//$headmail_obj->sendQuotePersonalEmail($receiver_id,204,$headmail_parameters);
										}
								}
						}	
							
							
						$quoteForumComments=$clientBriefParams['quotes_forum_comments'];
						if($quoteForumComments && $quoteIdentifier)
						{
							$quotelog_obj=new Ep_Quote_QuotesLog();
							$quote_log['user_id']=$this->adminLogin->userId;
							$quote_log['bo_user']=$this->adminLogin->userId;
							$quote_log['quote_id']=$quoteIdentifier;
							$quote_log['action']="quote_forum";
							$quote_log['comments']=$quoteForumComments;
							
							$version=$quote_obj->getQuoteVersion($quoteIdentifier);
							$quote_log['version']=$version ;
							$quote_log['action_at']	= date("Y-m-d H:i:s");
							$actionId=31;
							$quotelog_obj->insertLog($actionId,$quote_log);									
						}		

					//echo "<pre>";print_r($this->quote_creation->send_quote);exit;
						if($newquote)
						{
							$this->_redirect("quote-new/client-brief?qaction=briefing&quote_id=".$quoteIdentifier."&brief=share");		
						}
						else
							$this->_redirect("quote-new/client-brief?qaction=briefing&quote_id=".$quoteIdentifier);
					
		}
	}
	/*save quote forum comments*/
	function saveQuoteCommentsAction()
	{
		if($this->_request-> isPost())            
        {
        	$user_identifier=$this->adminLogin->userId;
        	$quotes_obj=new Ep_Quote_Quotes();	
        	$quotelog_obj=new Ep_Quote_QuotesLog();
        	$commentsParams=$this->_request->getParams();

        	$quote_log['user_id']=$commentsParams['user_id'];
        	$quote_log['bo_user']=$commentsParams['user_id'];
        	$quote_log['quote_id']=$commentsParams['quote_id'];
        	$quote_log['action']="quote_forum";
			$quote_log['comments']=utf8_decode($commentsParams['quotes_forum_comments']);
        	
        	
        	$version=$quotes_obj->getQuoteVersion($quote_log['quote_id']);
        	$quote_log['version']=$version ;
        	$actionId=31;

        	if($commentsParams['comment_action']=='update')
        	{
        		//$quote_update_log['action_at']	= date("Y-m-d H:i:s");  
				$quote_update_log['comments']=utf8_decode($commentsParams['quotes_forum_comments']);				
        		$id=$commentsParams['comment_id'];
				if($quote_update_log['comments'] && $id)
					$quotelog_obj->updateQuoteLog($quote_update_log,$id);
        	}
        	elseif($commentsParams['comment_action']=='delete')
        	{
        		$id=$commentsParams['comment_id'];
        		if($id)
        		$quotelog_obj->deleteQuoteLog($id);
        	}
        	else
        	{
        		$quote_log['action_at']	= date("Y-m-d H:i:s");				
				if($quote_log['comments'])
				{
					$quotelog_obj->insertLog($actionId,$quote_log);	
					$quoteDetails=$quotes_obj->getQuoteDetails($quote_log['quote_id']);
					/*notify manager Email*/
					$email_usersprod=$email_usersseo=$email_userstech= array();
					if(count($quoteDetails)>0 && $quoteDetails[0]['brief_email_notify']!='')
					{
						$client_obj=new Ep_Quote_Client();

						$notifyEmail=explode(',',$quoteDetails[0]['brief_email_notify']);

						if($quoteDetails[0]['prod_review']=='challenged')
							$email_usersprod=$client_obj->getEPContacts('"prodmanager","prodsubmanager"');
						if($quoteDetails[0]['tec_review']=='challenged')
							$email_userstech=$client_obj->getEPContacts('"techmanager"');
						if($quoteDetails[0]['seo_review']=='challenged')
							$email_usersseo=$client_obj->getEPContacts('"seomanager"');
						foreach ($email_usersprod as $prod => $value) {
							if(!in_array($prod,$notifyEmail))
							array_push($notifyEmail,$prod);
						}
						foreach ($email_userstech as $tech => $value) {
							if(!in_array($tech,$notifyEmail))
							array_push($notifyEmail,$tech);
						}
						foreach ($email_usersseo as $seo => $value) {
							if(!in_array($seo,$notifyEmail))
							array_push($notifyEmail,$seo);
						}
						array_unique($notifyEmail);

						foreach($notifyEmail as $recever_id)
						{
							/*$nmail_obj=new Ep_Message_AutoEmails();
							$nmail_parameters['bo_user']=$recever_id;
							$nmail_parameters['sales_user']=$quoteDetails[0]['created_by'];
							$nmail_parameters['comment_user']=$this->adminLogin->userId;
							$nmail_parameters['photo']=$this->_config->path->fo_path.'/profiles/bo/'.$this->adminLogin->userId.'/logo.jpg';
							$nmail_parameters['date_time']=date('h:i a F d, Y',strtotime($quote_log['action_at']));
							$nmail_parameters['followup_link']='/quote-new/client-brief?qaction=briefing&quote_id='.$quote_log['quote_id'];
							//$nmail_parameters['followup_link']=$_SERVER['SERVER_NAME'].'/quote-new/client-brief?qaction=briefing&quote_id='.$quote_log['quote_id'];
							$nmail_parameters['quote_title']=$quoteDetails[0]['title'];
							$nmail_parameters['comment']=$quote_log['comments'];
							$nmail_obj->sendQuotePersonalEmail($receiver_id,211,$nmail_parameters);*/


							
							$nmail_parameters['bo_user']=$recever_id;
							$quoteuser=$client_obj->getQuoteUserDetails($nmail_parameters['bo_user']);
							$nmail_parameters['bo_user_name']=$quoteuser[0]['first_name'].' '.$quoteuser[0]['last_name'];
							$nmail_parameters['emailid']=$quoteuser[0]['email'];
							$nmail_parameters['comment_user']=$this->adminLogin->userId;
							$commentusername=$client_obj->getQuoteUserDetails($nmail_parameters['comment_user']);
							$nmail_parameters['comment_user_name']=$commentusername[0]['first_name'].' '.$commentusername[0]['last_name'];
							$nmail_parameters['photo']=$this->_config->path->fo_path.'/profiles/bo/'.$this->adminLogin->userId.'/logo.jpg';
							$nmail_parameters['date_time']=date('h:i a F d, Y',strtotime($quote_log['action_at']));
							$nmail_parameters['quote_title']=$quoteDetails[0]['title'];
							$nmail_parameters['turn_over']=$quoteDetails[0]['final_turnover'];
							$nmail_parameters['estimate_sign_percentage']=$quoteDetails[0]['estimate_sign_percentage'];
							$nmail_parameters['client_name']=$quoteDetails[0]['company_name'];
							$nmail_parameters['client_id']=$quoteDetails[0]['client_id'];
							$nmail_parameters['comment']=$quote_log['comments'];
							$nmail_parameters['agency_name']=$quoteDetails[0]['agency_name'];
							$nmail_parameters['followup_link']='http://'.$_SERVER['HTTP_HOST'].'/quote-new/client-brief?qaction=briefing&quote_id='.$quote_log['quote_id'];
							if($commentsParams['brief']=='shared')
							{
								$nmail_parameters['share']=ture;
								$nmail_parameters['subject']=$nmail_parameters['comment_user_name'].' has shared the quote '.$quoteDetails[0]['title'].' with you at '.$nmail_parameters['date_time'];
							}
							else
							{
								$nmail_parameters['commentonly']=ture;
								$nmail_parameters['subject']=$nmail_parameters['comment_user_name'].' has commented the quote '.$quoteDetails[0]['title'].' on Workplace at '.$nmail_parameters['date_time'];
							}
							$this->getNewQuoteEmails($nmail_parameters);
						}
					}
				}	
					
        	}


        	$commentDetails=$quotelog_obj->getquotescomments($quote_log['quote_id'],$quote_log['action']);
        	$commentsData='';
             $cmtCount=count($commentDetails);
            if($cmtCount>0)
            {
            	$commentDetails=$this->formatCommentDetails($commentDetails);

            	$commentsData='';
                $cnt=0;
                foreach($commentDetails as $comment)  
                {
					/* $commentsData.=
					'<li class="media" id="comment_'.$comment['id'].'">';
									
					if($comment['user_id']==$user_identifier)
						  $commentsData.='<a  class="close hint--left" data-hint="Edit Comment" type="button" id="edit_comment_'.$comment['id'].'"><span class="glyphicon glyphicon-pencil"></span></a>';

					$commentsData.='<a  class="close hint--left" data-hint="Hide Comment" type="button" id="delete_comment_'.$comment['id'].'" rel="'.$quote_log['quote_id'].'">&times;</a>';   	  


					$commentsData.='<a class="pull-left imgframe" href="#" role="button" data-toggle="modal" data-target="#viewProfile-ajax">
						<img alt="Topito" class="media-object img-circle" width="60px" src="'.$comment['profile_pic'].'">
					  </a>
					  <div class="media-body">
						<h4 class="media-heading">
						  <a href="#" role="button" data-toggle="modal" data-target="#viewProfile-ajax">'.utf8_encode($comment['profile_name']).'</a></h4>
						  <span id="user_comment_'.$comment['id'].'">'.utf8_encode(stripslashes($comment['comments'])).'</span>
						  
						  <span id="edit_user_comment_'.$comment['id'].'" style="display:none">
							<textarea class="col-md-10" name="quote_comments_'.$comment['id'].'" id="quote_comments_'.$comment['id'].'">'.utf8_encode(stripslashes($comment['comments'])).'</textarea>
							<button type="button" id="update_submit_'.$comment['id'].'" name="update_submit_'.$comment['id'].'" class="btn">Mettre &agrave; jour</button>
						</span>
						<p class="muted">'.$comment['time'].'</p>
					  </div>			  
					</li>';*/ 
					
					$commentsData.='<div class="phenom phenom-action u-clearfix phenom-comment">
										<div class="creator member js-show-mem-menu">
											<img width="30" height="30" title="'.utf8_encode($comment['profile_name']).'" alt="'.utf8_encode($comment['profile_name']).'" src="'.utf8_encode($comment['profile_pic']).'" class="member-avatar">		
										</div>
										<div class="phenom-desc">
											<span idmember="'.$comment['user_id'].'" class="inline-member js-show-mem-menu">
												<span class="u-font-weight-bold">'.utf8_encode($comment['profile_name']).'</span>
											</span> 
							<span>';				
								if($comment['user_id']==$user_identifier)
								{
									$commentsData.='<span>
										<a  class="close hint--left" type="button" id="edit_comment_'.$comment['id'].'" data-hint="Edit Comment" rel="'.$comment['quote_id'].'"><i class="glyphicon glyphicon-pencil"></i></a>
										<a  class="close hint--left" rel="'.$comment['quote_id'].'" data-hint="Hide Comment" type="button" id="delete_comment_'.$comment['id'].'">&times;</a>		
									</span>';
								}						
													
						$commentsData.='	
							<div class="action-comment markeddown js-comment">
								<div class="current-comment js-friendly-links" id="user_comment_'.$comment['id'].'">
									'.stripslashes(utf8_encode($comment['comments'])).'
								</div>
								<div class="comment-box" id="edit_user_comment_'.$comment['id'].'" style="display:none">
									<textarea tabindex="1" class="comment-box-input js-text" name="quotes_forum_comments_'.$comment['id'].'" id="quotes_forum_comments_'.$comment['id'].'" rel="'.$comment['quote_id'].'">'.stripslashes(utf8_encode($comment['comments'])).'</textarea>
									<div class="comment-box-options">										
										<a title="Mention a member…" href="#" class="comment-box-options-item js-comment-mention-member"><span class="glyphicon glyphicon-user"></span></a>
									</div>									
								</div>
							</div>
							<div class="hide embedly js-embedly"></div>
						</div>
						<div class="edit-controls u-clearfix" id="control_comment_'.$comment['id'].'" style="display:none">
							<button type="button" id="update_submit_'.$comment['id'].'" name="update_submit_'.$comment['id'].'" class="btn">Mettre &agrave; jour</button>
						</div>
						<p class="phenom-meta quiet">
							<span>'.$comment['time'].'</span>
						</p>
					</div>
					';
									
					
				}

            }
        	echo  json_encode(array('comments'=>$commentsData,'count'=>$cmtCount));

        }

	}
	
	/*create mission popup*/
	function createQuoteMissionPopupAction()
	{

		$params=$this->_request->getParams();
		$language_array=$this->_arrayDb->loadArrayv2("EP_LANGUAGES", $this->_lang);
		natsort($language_array);
		$quotes_obj=new Ep_Quote_Quotes();	
		$tech_title=$quotes_obj->techtitles();

		/*configure values tempos*/
		 	$this->_view->tempo_fix=$this->configval['tempo_fix'];
			$this->_view->tempo_fix_days=$this->configval['tempo_fix_days'];
			$this->_view->tempo_max=$this->configval['tempo_max'];
			$this->_view->tempo_max_days=$this->configval['tempo_max_days'];
			$this->_view->oneshot_max_writers=$this->configval['oneshot_max_writers'];
			$this->_view->seo_mission_length=$this->configval['seo_mission_length'];
			$this->_view->analyse_content_seo=($this->configval['analyse_content_seo'])*($this->configval['analyse_content_seo_days']);
			/*config product array*/
			$oneshot_product['descriptif_produit']=$this->configval['oneshot_max_words_descriptif_produit'];
			$oneshot_product['article_de_blog']=$this->configval['oneshot_max_words_article_de_blog'];
			$oneshot_product['news']=$this->configval['oneshot_max_words_news'];
			$oneshot_product['guide']=$this->configval['oneshot_max_words_guide'];
			$oneshot_product['article_seo']=$this->configval['oneshot_max_words_article_seo'];
			
			$this->_view->oneshot_product=$oneshot_product;

        $this->_view->ep_language_list=$language_array;
        if($params['mission']=='edit' || $params['mission']=='duplicate')
        {
        	$mid=array_search($params['mid'],$this->quote_creation->create_mission['mission_identifier']);
        		if($this->quote_creation->create_mission['product'][$mid]=='content_strategy')
				{
					$mission_details['product']=$this->quote_creation->create_mission['product'][$mid];
					$mission_details['product_name']=$this->product_array[$this->quote_creation->create_mission['product'][$mid]];
					$mission_details['language']=$this->quote_creation->create_mission['language'][$mid];
					$mission_details['language_name']=$this->getLanguageName($this->quote_creation->create_mission['language'][$mid]);
					$mission_details['internal_cost']=$this->quote_creation->create_mission['strategy_mission_cost'][$mid];
					$mission_details['mission_length']=$this->quote_creation->create_mission['mission_length'][$mid];
					$mission_details['mission_length_option']=$this->quote_creation->create_mission['mission_length'][$mid];

				}
				else
				{
					$mission_details['product']=$this->quote_creation->create_mission['product'][$mid];
					$mission_details['product_name']=$this->product_array[$this->quote_creation->create_mission['product'][$mid]];
					$mission_details['language']=$this->quote_creation->create_mission['language'][$mid];
					$mission_details['language_name']=$this->getLanguageName($this->quote_creation->create_mission['language'][$mid]);
					$mission_details['languagedest']=$this->quote_creation->create_mission['languagedest'][$mid];
					$mission_details['languagedest_name']=$this->getLanguageName($this->quote_creation->create_mission['languagedest'][$mid]);

					$mission_details['producttype']=$this->quote_creation->create_mission['producttype'][$mid];
					$mission_details['producttypeother']=$this->quote_creation->create_mission['producttypeother'][$mid];
					$mission_details['producttype_name']=$this->producttype_array[$this->quote_creation->create_mission['producttype'][$mid]];
					$mission_details['nb_words']=$this->quote_creation->create_mission['nb_words'][$mid];
					$mission_details['volume']=$this->quote_creation->create_mission['volume'][$mid];
					
					/*added w.r.t Tempo*/
					$mission_details['volume_max']=$this->quote_creation->create_mission['volume_max'][$mid];
					$mission_details['mission_length']=$this->quote_creation->create_mission['mission_length'][$mid];
					$mission_details['mission_length_option']=$this->quote_creation->create_mission['mission_length_option'][$mid];
					$mission_details['delivery_volume_option']=$this->quote_creation->create_mission['delivery_volume_option'][$mid];
					$mission_details['tempo_type']=$this->quote_creation->create_mission['tempo_type'][$mid];
					$mission_details['tempo_length']=$this->quote_creation->create_mission['tempo_length'][$mid];
					$mission_details['tempo_length_option']=$this->quote_creation->create_mission['tempo_length_option'][$mid];
					$mission_details['oneshot']=$this->quote_creation->create_mission['oneshot'][$mid];
					$mission_details['demande_client']=$this->quote_creation->create_mission['demande_client'][$mid];
					$mission_details['duration_dont_know']=$this->quote_creation->create_mission['duration_dont_know'][$mid];

					
				}
				//echo "<pre>"; print_r($mission_details);  exit;

				$this->_view->mission=$mission_details; 
        }
        elseif($params['techmission']=='edit' || $params['techmission']=='duplicate')
        {
        	//echo "<pre>"; print_r($this->quote_creation->tech_mission);  exit;
        	$tid=array_search($params['tid'],$this->quote_creation->tech_mission['mission_identifier']);
        	
        	$techmission_details['product']=$this->quote_creation->tech_mission['product'][$tid];
        	$techmission_details['language']=$this->quote_creation->tech_mission['language'][$tid];
        	$techmission_details['tech_oneshot']=$this->quote_creation->tech_mission['tech_oneshot'][$tid];
			$techmission_details['tech_mission_length']=$this->quote_creation->tech_mission['tech_mission_length'][$tid];
			$techmission_details['tech_mission_length_option']='days';
			if($techmission_details['tech_oneshot']=='no')
			{
				$techmission_details['volume_max']=$this->quote_creation->tech_mission['volume_max'][$tid];
				$techmission_details['delivery_volume_option']=$this->quote_creation->tech_mission['delivery_volume_option'][$tid];
				$techmission_details['tempo_type']=$this->quote_creation->tech_mission['tempo_type'][$tid];
				$techmission_details['tempo_length']=$this->quote_creation->tech_mission['tempo_length'][$tid];
				$techmission_details['tempo_length_option']=$this->quote_creation->tech_mission['tempo_length_option'][$tid];
				$techmission_details['tech_oneshot']=$this->quote_creation->tech_mission['tech_oneshot'][$tid];
			
			}
			$techmission_details['volume']=$this->quote_creation->tech_mission['volume'][$tid];
			$techmission_details['prod_mission_selected']=$this->quote_creation->tech_mission['prod_mission_selected'][$tid];
			$techmission_details['linked_to_prod']=$this->quote_creation->tech_mission['linked_to_prod'][$tid];
			$techmission_details['mission_cost']=$this->quote_creation->tech_mission['mission_cost'][$tid];
			$techmission_details['tech_type']=$this->quote_creation->tech_mission['tech_type'][$tid];
			$techmission_details['tech_title_id']=$this->quote_creation->tech_mission['tech_title_id'][$tid];
			$techmission_details['to_perform']=$this->quote_creation->tech_mission['to_perform'][$tid];
			//echo "<pre>"; print_r($techmission_details); exit;
			$this->_view->techmission=$techmission_details;
        }
        if(count($this->quote_creation->create_mission['product'])>0)
        {
        $this->_view->prod_mission_count=count($this->quote_creation->create_mission['product']);
        $pm=count($this->quote_creation->create_mission['product']);
        
		        for($i=0;$i<$pm;$i++)
		        {
		        	if($this->quote_creation->create_mission['product'][$i]!='content_strategy' && $this->quote_creation->create_mission['product'][$i]!='')
		        	{
		        		$prodMission_title['all_prod']="select all mission";
			        	if($this->quote_creation->create_mission['languagedest'][$i]=="")
			        	       	$prodMission_title[$this->quote_creation->create_mission['mission_identifier'][$i]]=$this->product_array[$this->quote_creation->create_mission['product'][$i]].' / '.$this->producttype_array[$this->quote_creation->create_mission['producttype'][$i]].' '.$this->quote_creation->create_mission['language'][$i].' / '.$this->quote_creation->create_mission['nb_words'][$i].' words';
			        	else
			        		   	$prodMission_title[$this->quote_creation->create_mission['mission_identifier'][$i]]=$this->product_array[$this->quote_creation->create_mission['product'][$i]].' / '.$this->producttype_array[$this->quote_creation->create_mission['producttype'][$i]].' '.$this->quote_creation->create_mission['language'][$i].' > '.$this->quote_creation->create_mission['languagedest'][$i].' / '.$this->quote_creation->create_mission['nb_words'][$i].' words';

			        		   $prodMission_title['volume'][$this->quote_creation->create_mission['mission_identifier'][$i]]=$this->quote_creation->create_mission['volume'][$i];

			        		  
		        	}
		        }
		        //echo "<pre>"; print_r($prodMission_title); exit;
		   $this->_view->prod_missions=$prodMission_title; 
        }
        
        $this->_view->tech_mission_title=$tech_title;
		$this->render('create-quote-mission-popup');
	}

	/*Duplicate Mission popup*/
	function duplicateQuoteMissionPopupAction()
	{
		$techObj=new Ep_Quote_TechMissions();
		$params=$this->_request->getParams();
		
		if($params['tid']!="")
		{
			$searchParameters['identifier']=$params['tid'];
			$techmissDetails=$techObj->getTechMissionDetails($searchParameters);
			//echo "<pre>"; print_r($techmissDetails); exit;
			if($techmissDetails[0]['prod_linked'])
				$this->_view->prodmissionDetails='Yes';
			else
				$this->_view->prodmissionDetails='No';
		}
		
		$language_array=$this->_arrayDb->loadArrayv2("EP_LANGUAGES", $this->_lang);
		natsort($language_array);
		$this->_view->ep_language_list=$language_array;

		$quotes_obj=new Ep_Quote_Quotes();	
		$tech_title=$quotes_obj->techtitles();
		$this->_view->tech_mission_title=$tech_title;
		$this->render('duplicate-quote-mission-popup');
	}
	//save Quote mission
	public function saveQuoteMissionAction()
	{
		$quotes_obj=new Ep_Quote_Quotes();	
		$quoteMission_obj=new Ep_Quote_QuoteMissions();
		$techObj=new Ep_Quote_TechMissions();
		if($this->_request-> isPost()  && $this->adminLogin->userId)            
        {
			$mission_params=$this->_request->getParams();
			//echo "<pre>";	print_r($mission_params);exit;
			//unset($this->quote_creation->create_mission['product']);
			$i=count($this->quote_creation->create_mission['product']);
			$t=count($this->quote_creation->tech_mission['product']);

			if($mission_params['parameter']!='duplicate' && $mission_params['parameter']!='duplicatenew' && $mission_params['edit']!='edit' && $mission_params['product']!="tech" && $mission_params['techduplicate']!='duplicate')
				{	
					if($mission_params['product']=='content_strategy')
					{
						$this->quote_creation->create_mission['product'][$i]=$mission_params['product'];
						$this->quote_creation->create_mission['producttype'][$i]=$mission_params['seo_product'];
						$this->quote_creation->create_mission['language'][$i]=$mission_params['language'];	
						$this->quote_creation->create_mission['mission_length'][$i]=$mission_params['strategy_mission_length'];
						$this->quote_creation->create_mission['mission_length_option'][$i]=$mission_params['strategy_mission_length_option'];
						$this->quote_creation->create_mission['strategy_mission_cost'][$i]=($this->quote_creation->create_mission['mission_length'][$i])*($this->configval['analyse_content_seo'])*($this->configval['analyse_content_seo_days']);
						$this->quote_creation->create_mission['margin_percentage'][$i]=0;
						$this->quote_creation->create_mission['volume'][$i]=1;
						$this->quote_creation->create_mission['internal_cost'][$i]=($this->quote_creation->create_mission['mission_length'][$i])*($this->configval['analyse_content_seo'])*($this->configval['analyse_content_seo_days']);
						$this->quote_creation->create_mission['unit_price'][$i]=($this->quote_creation->create_mission['internal_cost'][$i]/(1-$this->quote_creation->create_mission['margin_percentage'][$i]/100));
						$this->quote_creation->create_mission['turnover'][$i]=($this->quote_creation->create_mission['unit_price'][$i]*$this->quote_creation->create_mission['volume'][$i]);
					}
					else
					{

						$this->quote_creation->create_mission['product'][$i]=$mission_params['product'];
						$this->quote_creation->create_mission['language'][$i]=$mission_params['language'];
						$this->quote_creation->create_mission['languagedest'][$i]=$mission_params['languagedest'];
						$this->quote_creation->create_mission['producttype'][$i]=$mission_params['producttype'];
						if($this->quote_creation->create_mission['producttype'][$i]=='autre')
							$this->quote_creation->create_mission['producttypeautre']=True;
						$this->quote_creation->create_mission['producttypeother'][$i]=$mission_params['producttypeother'];
						$this->quote_creation->create_mission['nb_words'][$i]=$mission_params['nb_words'];
						$this->quote_creation->create_mission['volume'][$i]=$mission_params['volume'];
						$this->quote_creation->create_mission['comments'][$i]=$mission_params['comments'];
						
						/*added w.r.t Tempo*/
						$this->quote_creation->create_mission['mission_length'][$i]=$mission_params['mission_length'];
						$this->quote_creation->create_mission['mission_length_option'][$i]=$mission_params['mission_length_option'];
						$this->quote_creation->create_mission['volume_max'][$i]=$mission_params['volume_max'];
						$this->quote_creation->create_mission['delivery_volume_option'][$i]=$mission_params['delivery_volume_option'];
						$this->quote_creation->create_mission['tempo_type'][$i]=$mission_params['tempo_type'];
						$this->quote_creation->create_mission['tempo_length'][$i]=$mission_params['tempo_length'];
						$this->quote_creation->create_mission['tempo_length_option'][$i]=$mission_params['tempo_length_option'];
						
						
							$this->quote_creation->create_mission['oneshot'][$i]=$mission_params['oneshot'];
							$this->quote_creation->create_mission['duration_dont_know'][$i]=$mission_params['duration_dont_know_'.$mindex];
							
							if($this->quote_creation->create_mission['duration_dont_know'][$i]=='yes')
							{
								$this->quote_creation->create_mission['mission_length'][$i]=0;
								$this->quote_creation->create_mission['mission_length_option'][$i]='days';
							}
					
						//flag set if autre
						
							if($this->quote_creation->create_mission['producttype'][$i]=='autre'){
								$this->quote_creation->create_mission['producttypeautre']=TRUE;
								}
								$this->quote_creation->create_mission['turnover'][$i]=0.00;
								$this->quote_creation->create_mission['strategy_mission_cost'][$i]=0.00;
								$this->quote_creation->create_mission['internal_cost'][$i]=0.00;
								$this->quote_creation->create_mission['unit_price'][$i]=0.00;
								$this->quote_creation->create_mission['turnover'][$i]=0.00;
								$this->quote_creation->create_mission['margin_percentage'][$i]=0.00;
						}

						/*insserting Mission into mission table*/

						$this->quoteMissoinUpdate("",$i);

							$this->_redirect("/quote-new/create-quote-mission-view");	
					
				}	//duplicate mission save
				elseif($mission_params['parameter']=='duplicatenew' && $mission_params['product']!="tech")
				{
					//echo "<pre>"; print_r($mission_params); 
					$p=$i;

					if(count($mission_params['duplicatelang'])>0)
					{
							foreach($mission_params['duplicatelang'] as $duplicatelanguage)
							{
								

								$this->quote_creation->create_mission['product'][$p]=$mission_params['product'];
								if($this->quote_creation->create_mission['product'][$p]=='content_strategy')
								{
									$this->quote_creation->create_mission['language'][$p]=$duplicatelanguage;
									$this->quote_creation->create_mission['producttype'][$p]=$mission_params['seo_product'];
									$this->quote_creation->create_mission['margin_percentage'][$p]=0;
									$this->quote_creation->create_mission['volume'][$p]=1;
									$this->quote_creation->create_mission['mission_length'][$p]=$mission_params['strategy_mission_length'];
									$this->quote_creation->create_mission['mission_length_option'][$p]=$mission_params['strategy_mission_length_option'];
									$this->quote_creation->create_mission['internal_cost'][$p]=($this->quote_creation->create_mission['mission_length'][$p])*($this->configval['analyse_content_seo'])*($this->configval['analyse_content_seo_days']);
									$this->quote_creation->create_mission['unit_price'][$p]=($this->quote_creation->create_mission['internal_cost'][$i]/(1-$this->quote_creation->create_mission['margin_percentage'][$p]/100));
									$this->quote_creation->create_mission['turnover'][$p]=($this->quote_creation->create_mission['unit_price'][$i]*$this->quote_creation->create_mission['volume'][$p]);
									
								}
								else
								{
									if($this->quote_creation->create_mission['product'][$p]=='translation')
										{
											$this->quote_creation->create_mission['language'][$p]=$mission_params['language'];
											$this->quote_creation->create_mission['languagedest'][$p]=$duplicatelanguage;
										}
										else
										{
										$this->quote_creation->create_mission['language'][$p]=$duplicatelanguage;
										$this->quote_creation->create_mission['languagedest'][$p]=$mission_params['languagedest'];
										}

									//$this->quote_creation->create_mission['languagedest'][$p]=$mission_params['languagedest'];
									$this->quote_creation->create_mission['producttype'][$p]=$mission_params['producttype'];
									$this->quote_creation->create_mission['producttypeother'][$p]=$mission_params['producttypeother'];
									$this->quote_creation->create_mission['nb_words'][$p]=$mission_params['nb_words'];
									$this->quote_creation->create_mission['volume'][$p]=$mission_params['volume'];
									$this->quote_creation->create_mission['comments'][$p]=$mission_params['comments'];
									
									/*added w.r.t Tempo*/
									$this->quote_creation->create_mission['mission_length'][$p]=$mission_params['mission_length'];
									$this->quote_creation->create_mission['mission_length_option'][$p]=$mission_params['mission_length_option'];
									$this->quote_creation->create_mission['volume_max'][$p]=$mission_params['volume_max'];
									$this->quote_creation->create_mission['delivery_volume_option'][$p]=$mission_params['delivery_volume_option'];
									$this->quote_creation->create_mission['tempo_type'][$p]=$mission_params['tempo_type'];
									$this->quote_creation->create_mission['tempo_length'][$p]=$mission_params['tempo_length'];
									$this->quote_creation->create_mission['tempo_length_option'][$p]=$mission_params['tempo_length_option'];
									
								
									$this->quote_creation->create_mission['oneshot'][$p]=$mission_params['oneshot'];
									$this->quote_creation->create_mission['duration_dont_know'][$p]=$mission_params['duration_dont_know_'.$mindex];
									
									if($this->quote_creation->create_mission['duration_dont_know'][$p]=='yes')
									{
										$this->quote_creation->create_mission['mission_length'][$p]=0;
										$this->quote_creation->create_mission['mission_length_option'][$p]='days';
									}
									
						
							//flag set if autre
								if($this->quote_creation->create_mission['producttype'][$p]=='autre'){
									$this->quote_creation->create_mission['producttypeautre']=TRUE;
									}
								}
									//echo $p.'test';
									/*inserting duplicate mission*/
									$this->quoteMissoinUpdate("",$p);
						
								$p++;
							}
						$this->_redirect("/quote-new/create-quote-mission-view");		
					}
					else
					{
						if($mission_params['duplicate_words']!="") 
						{
							$this->quote_creation->create_mission['nb_words'][$p]=$mission_params['duplicate_words'];
							$this->quote_creation->create_mission['producttype'][$p]=$mission_params['producttype'];
							$this->quote_creation->create_mission['producttypeother'][$p]=$mission_params['producttypeother'];
							//flag set if autre
								if($this->quote_creation->create_mission['producttype'][$p]=='autre'){
									$this->quote_creation->create_mission['producttypeautre']=TRUE;
									}
							$this->quote_creation->create_mission['volume'][$p]=$mission_params['volume'];
	
						}
						elseif($mission_params['duplicate_producttype']!="")
						{

							$this->quote_creation->create_mission['producttype'][$p]=$mission_params['duplicate_producttype'];
							$this->quote_creation->create_mission['producttypeother'][$p]=$mission_params['duplicate_producttypeother'];
							//flag set if autre
								if($this->quote_creation->create_mission['duplicate_producttype'][$p]=='autre'){
									$this->quote_creation->create_mission['duplicate_producttypeautre']=TRUE;
									}
							$this->quote_creation->create_mission['nb_words'][$p]=$mission_params['nb_words'];
							$this->quote_creation->create_mission['volume'][$p]=$mission_params['volume'];
						}
						elseif($mission_params['duplicate_volume']!="")
						{
							$this->quote_creation->create_mission['volume'][$p]=$mission_params['duplicate_volume'];
							$this->quote_creation->create_mission['nb_words'][$p]=$mission_params['nb_words'];
							$this->quote_creation->create_mission['producttype'][$p]=$mission_params['producttype'];
							$this->quote_creation->create_mission['producttypeother'][$p]=$mission_params['producttypeother'];
							//flag set if autre
								if($this->quote_creation->create_mission['producttype'][$p]=='autre'){
									$this->quote_creation->create_mission['producttypeautre']=TRUE;
									}
						}
								$this->quote_creation->create_mission['language'][$p]=$mission_params['language'];
								$this->quote_creation->create_mission['product'][$p]=$mission_params['product'];
								$this->quote_creation->create_mission['languagedest'][$p]=$mission_params['languagedest'];
															
								
								$this->quote_creation->create_mission['comments'][$p]=$mission_params['comments'];
								
								/*added w.r.t Tempo*/
								$this->quote_creation->create_mission['mission_length'][$p]=$mission_params['mission_length'];
								$this->quote_creation->create_mission['mission_length_option'][$p]=$mission_params['mission_length_option'];
								$this->quote_creation->create_mission['volume_max'][$p]=$mission_params['volume_max'];
								$this->quote_creation->create_mission['delivery_volume_option'][$p]=$mission_params['delivery_volume_option'];
								$this->quote_creation->create_mission['tempo_type'][$p]=$mission_params['tempo_type'];
								$this->quote_creation->create_mission['tempo_length'][$p]=$mission_params['tempo_length'];
								$this->quote_creation->create_mission['tempo_length_option'][$p]=$mission_params['tempo_length_option'];
								
							
								$this->quote_creation->create_mission['oneshot'][$p]=$mission_params['oneshot'];
								$this->quote_creation->create_mission['duration_dont_know'][$p]=$mission_params['duration_dont_know_'.$mindex];
								
								if($this->quote_creation->create_mission['duration_dont_know'][$p]=='yes')
								{
									$this->quote_creation->create_mission['mission_length'][$p]=0;
									$this->quote_creation->create_mission['mission_length_option'][$p]='days';
								}

								if($this->quote_creation->create_mission['product'][$p]=='content_strategy')
								{

									$this->quote_creation->create_mission['margin_percentage'][$p]=0;
									$this->quote_creation->create_mission['volume'][$p]=1;
									$this->quote_creation->create_mission['internal_cost'][$p]=($this->configval['analyse_content_seo'])*($this->configval['analyse_content_seo_days']);
									$this->quote_creation->create_mission['unit_price'][$p]=($this->quote_creation->create_mission['internal_cost'][$i]/(1-$this->quote_creation->create_mission['margin_percentage'][$p]/100));
									$this->quote_creation->create_mission['turnover'][$p]=($this->quote_creation->create_mission['unit_price'][$i]*$this->quote_creation->create_mission['volume'][$p]);
								}

								/*inserting duplicate mission*/
									$this->quoteMissoinUpdate("",$p);


					}	
					$this->_redirect("/quote-new/create-quote-mission-view");	

									
				}/*Edit Mission*/
				elseif ($mission_params['edit']=='edit' && $mission_params['product']!="tech") 
				{
					//echo "<pre>";print_r($mission_params); print_r($this->quote_creation->create_mission); exit;
					
					
					$id=array_search($mission_params['mid'],$this->quote_creation->create_mission['mission_identifier']);
					if($mission_params['product']=='content_strategy')
					{
						$this->quote_creation->create_mission['product'][$id]=$mission_params['product'];
						$this->quote_creation->create_mission['producttype'][$id]=$mission_params['seo_product'];
						$this->quote_creation->create_mission['language'][$id]=$mission_params['language'];	
						$this->quote_creation->create_mission['mission_length'][$id]=$mission_params['strategy_mission_length'];
						$this->quote_creation->create_mission['mission_length_option'][$i]=$mission_params['strategy_mission_length_option'];
						$this->quote_creation->create_mission['strategy_mission_cost'][$id]=($this->quote_creation->create_mission['mission_length'][$id])*($this->configval['analyse_content_seo'])*($this->configval['analyse_content_seo_days']);
						$this->quote_creation->create_mission['margin_percentage'][$id]=0;
						$this->quote_creation->create_mission['volume'][$id]=1;
						$this->quote_creation->create_mission['internal_cost'][$id]=($this->quote_creation->create_mission['mission_length'][$id])*($this->configval['analyse_content_seo'])*($this->configval['analyse_content_seo_days']);
						$this->quote_creation->create_mission['unit_price'][$id]=($this->quote_creation->create_mission['internal_cost'][$id]/(1-$this->quote_creation->create_mission['margin_percentage'][$id]/100));
						$this->quote_creation->create_mission['turnover'][$id]=$this->quote_creation->create_mission['unit_price'][$id]*$this->quote_creation->create_mission['volume'][$id];
					}
					else
					{
						$this->quote_creation->create_mission['product'][$id]=$mission_params['product'];
						$this->quote_creation->create_mission['language'][$id]=$mission_params['language'];
						$this->quote_creation->create_mission['languagedest'][$id]=$mission_params['languagedest'];
						$this->quote_creation->create_mission['producttype'][$id]=$mission_params['producttype'];
						$this->quote_creation->create_mission['producttypeother'][$id]=$mission_params['producttypeother'];
						$this->quote_creation->create_mission['nb_words'][$id]=$mission_params['nb_words'];
						$this->quote_creation->create_mission['volume'][$id]=$mission_params['volume'];
						$this->quote_creation->create_mission['comments'][$id]=$mission_params['comments'];
						if($this->quote_creation->create_mission['producttype'][$id]=='autre'){
									$this->quote_creation->create_mission['producttypeautre']=TRUE;
									}
						/*added w.r.t Tempo*/
						$this->quote_creation->create_mission['mission_length'][$id]=$mission_params['mission_length'];
						$this->quote_creation->create_mission['mission_length_option'][$id]=$mission_params['mission_length_option'];
						$this->quote_creation->create_mission['volume_max'][$id]=$mission_params['volume_max'];
						$this->quote_creation->create_mission['delivery_volume_option'][$id]=$mission_params['delivery_volume_option'];
						$this->quote_creation->create_mission['tempo_type'][$id]=$mission_params['tempo_type'];
						$this->quote_creation->create_mission['tempo_length'][$id]=$mission_params['tempo_length'];
						$this->quote_creation->create_mission['tempo_length_option'][$id]=$mission_params['tempo_length_option'];
					
					
						$this->quote_creation->create_mission['oneshot'][$id]=$mission_params['oneshot'];
						$this->quote_creation->create_mission['duration_dont_know'][$id]=$mission_params['duration_dont_know_'.$mindex];
						
						if($this->quote_creation->create_mission['duration_dont_know'][$id]=='yes')
						{
							$this->quote_creation->create_mission['mission_length'][$id]=0;
							$this->quote_creation->create_mission['mission_length_option'][$id]='days';
						}
				
					//flag set if autre
					
						if($this->quote_creation->create_mission['producttype'][$id]=='autre'){
							$this->quote_creation->create_mission['producttypeautre']=TRUE;
							}

							$this->quote_creation->create_mission['turnover'][$id]=0.00;
							$this->quote_creation->create_mission['strategy_mission_cost'][$id]=0.00;
							$this->quote_creation->create_mission['internal_cost'][$id]=0.00;
							$this->quote_creation->create_mission['unit_price'][$id]=0.00;
							$this->quote_creation->create_mission['turnover'][$id]=0.00;
							$this->quote_creation->create_mission['margin_percentage'][$id]=0.00;
					}

						$quoteMission['identifier']=$this->quote_creation->create_mission['mission_identifier'][$id];
						/*updating Quote mission*/
						if($quoteMission['identifier'])
						$this->quoteMissoinUpdate($quoteMission['identifier'],$id);
						$this->_redirect("/quote-new/create-quote-mission-view");	
				}
				/*duplicate Edit Misssion*/
				elseif($mission_params['parameter']=='duplicate' && $mission_params['misssion']=='create' && $mission_params['product']!="tech" )
				{
					//echo "<pre>";		print_r($mission_params); exit;
					$mid=array_search($mission_params['mid'],$this->quote_creation->create_mission['mission_identifier']);
					
					$tcount=$i;
					if(count($mission_params['duplicatelang'])>0)
					{
							foreach($mission_params['duplicatelang'] as $duplicatelanguage)
							{
								
								$this->quote_creation->create_mission['product'][$tcount]=$this->quote_creation->create_mission['product'][$mid];

								if($this->quote_creation->create_mission['product'][$tcount]=='content_strategy')
								{
									$this->quote_creation->create_mission['language'][$tcount]=$duplicatelanguage;
									$this->quote_creation->create_mission['turnover'][$tcount]=$this->quote_creation->create_mission['turnover'][$mid];
									$this->quote_creation->create_mission['volume'][$tcount]=1;
									$this->quote_creation->create_mission['strategy_mission_cost'][$tcount]=$this->quote_creation->create_mission['internal_cost'][$mid];
									$this->quote_creation->create_mission['producttype'][$tcount]=$this->quote_creation->create_mission['producttype'][$mid];
									$this->quote_creation->create_mission['internal_cost'][$tcount]=$this->quote_creation->create_mission['internal_cost'][$mid];
									$this->quote_creation->create_mission['unit_price'][$tcount]=$this->quote_creation->create_mission['unit_price'][$mid];
									$this->quote_creation->create_mission['margin_percentage'][$tcount]=$this->quote_creation->create_mission['margin_percentage'][$mid];
									$this->quote_creation->create_mission['mission_length'][$tcount]=$this->quote_creation->create_mission['mission_length'][$mid];
									$this->quote_creation->create_mission['mission_length_option'][$tcount]=$this->quote_creation->create_mission['mission_length_option'][$mid];
								}
								else
								{
									if($this->quote_creation->create_mission['product'][$tcount]=='translation')
										{
											$this->quote_creation->create_mission['language'][$tcount]=$this->quote_creation->create_mission['language'][$mid];
											$this->quote_creation->create_mission['languagedest'][$tcount]=$duplicatelanguage;
										}
										else
										{
										$this->quote_creation->create_mission['language'][$tcount]=$duplicatelanguage;
										$this->quote_creation->create_mission['languagedest'][$tcount]=$this->quote_creation->create_mission['languagedest'][$mid];
										}
									
									$this->quote_creation->create_mission['producttype'][$tcount]=$this->quote_creation->create_mission['producttype'][$mid];
									$this->quote_creation->create_mission['producttypeother'][$tcount]=$this->quote_creation->create_mission['producttypeother'][$mid];
									$this->quote_creation->create_mission['nb_words'][$tcount]=$this->quote_creation->create_mission['nb_words'][$mid];
									$this->quote_creation->create_mission['volume'][$tcount]=$this->quote_creation->create_mission['volume'][$mid];
									$this->quote_creation->create_mission['comments'][$tcount]=$this->quote_creation->create_mission['comments'][$mid];
									
									/*added w.r.t Tempo*/
									$this->quote_creation->create_mission['mission_length'][$tcount]=$this->quote_creation->create_mission['mission_length'][$mid];
									$this->quote_creation->create_mission['mission_length_option'][$tcount]=$this->quote_creation->create_mission['mission_length_option'][$mid];
									$this->quote_creation->create_mission['volume_max'][$tcount]=$this->quote_creation->create_mission['volume_max'][$mid];
									$this->quote_creation->create_mission['delivery_volume_option'][$tcount]=$this->quote_creation->create_mission['delivery_volume_option'][$mid];
									$this->quote_creation->create_mission['tempo_type'][$tcount]=$this->quote_creation->create_mission['tempo_type'][$mid];
									$this->quote_creation->create_mission['tempo_length'][$tcount]=$this->quote_creation->create_mission['tempo_length'][$mid];
									$this->quote_creation->create_mission['tempo_length_option'][$tcount]=$this->quote_creation->create_mission['tempo_length_option'][$mid];
									
								
									$this->quote_creation->create_mission['oneshot'][$tcount]=$this->quote_creation->create_mission['oneshot'][$mid];
									$this->quote_creation->create_mission['duration_dont_know'][$tcount]=$this->quote_creation->create_mission['duration_dont_know'][$mid];
									
									if($this->quote_creation->create_mission['duration_dont_know'][$tcount]=='yes')
									{
										$this->quote_creation->create_mission['mission_length'][$tcount]=0;
										$this->quote_creation->create_mission['mission_length_option'][$tcount]='days';
									}
							
								//flag set if autre
									if($this->quote_creation->create_mission['producttype'][$tcount]=='autre'){
										$this->quote_creation->create_mission['producttypeautre']=TRUE;
										}
								}
									/*duplicate mission after create*/
									$this->quoteMissoinUpdate("",$tcount);

								$tcount++;
							}
						$this->_redirect("/quote-new/create-quote-mission-view");

					}
					else
					{
						if($mission_params['duplicate_words']!="") 
						{
							$this->quote_creation->create_mission['nb_words'][$tcount]=$mission_params['duplicate_words'];
							$this->quote_creation->create_mission['producttype'][$tcount]=$this->quote_creation->create_mission['producttype'][$mid];
							$this->quote_creation->create_mission['producttypeother'][$tcount]=$this->quote_creation->create_mission['producttypeother'][$mid];
							//flag set if autre
								if($this->quote_creation->create_mission['producttype'][$tcount]=='autre'){
									$this->quote_creation->create_mission['producttypeautre']=TRUE;
									}
							$this->quote_creation->create_mission['volume'][$tcount]=$this->quote_creation->create_mission['volume'][$mid];
	
						}
						elseif($mission_params['duplicate_producttype']!="")
						{

							$this->quote_creation->create_mission['producttype'][$tcount]=$mission_params['duplicate_producttype'];
							$this->quote_creation->create_mission['producttypeother'][$tcount]=$mission_params['duplicate_producttypeother'];
							//flag set if autre
								if($this->quote_creation->create_mission['producttypeother'][$tcount]=='autre'){
									$this->quote_creation->create_mission['producttypeother']=TRUE;
									}
							$this->quote_creation->create_mission['nb_words'][$tcount]=$this->quote_creation->create_mission['nb_words'][$mid];
							$this->quote_creation->create_mission['volume'][$tcount]=$this->quote_creation->create_mission['volume'][$mid];
						}
						elseif($mission_params['duplicate_volume']!="")
						{
							$this->quote_creation->create_mission['volume'][$tcount]=$mission_params['duplicate_volume'];
							$this->quote_creation->create_mission['nb_words'][$tcount]=$this->quote_creation->create_mission['nb_words'][$mid];
							$this->quote_creation->create_mission['producttype'][$tcount]=$this->quote_creation->create_mission['producttype'][$mid];
							$this->quote_creation->create_mission['producttypeother'][$tcount]=$this->quote_creation->create_mission['producttypeother'][$mid];
							//flag set if autre
								if($this->quote_creation->create_mission['producttype'][$tcount]=='autre'){
									$this->quote_creation->create_mission['producttypeautre']=TRUE;
									}
						}
								$this->quote_creation->create_mission['language'][$tcount]=$this->quote_creation->create_mission['language'][$mid];
								$this->quote_creation->create_mission['product'][$tcount]=$this->quote_creation->create_mission['product'][$mid];
								$this->quote_creation->create_mission['languagedest'][$tcount]=$this->quote_creation->create_mission['languagedest'][$mid];
															
								
								$this->quote_creation->create_mission['comments'][$tcount]=$this->quote_creation->create_mission['comments'][$mid];
								
								/*added w.r.t Tempo*/
								$this->quote_creation->create_mission['mission_length'][$tcount]=$this->quote_creation->create_mission['mission_length'][$mid];
								$this->quote_creation->create_mission['mission_length_option'][$tcount]=$this->quote_creation->create_mission['mission_length_option'][$mid];
								$this->quote_creation->create_mission['volume_max'][$tcount]=$this->quote_creation->create_mission['volume_max'][$mid];
								$this->quote_creation->create_mission['delivery_volume_option'][$tcount]=$this->quote_creation->create_mission['delivery_volume_option'][$mid];
								$this->quote_creation->create_mission['tempo_type'][$tcount]=$this->quote_creation->create_mission['tempo_type'][$mid];
								$this->quote_creation->create_mission['tempo_length'][$tcount]=$this->quote_creation->create_mission['tempo_length'][$mid];
								$this->quote_creation->create_mission['tempo_length_option'][$tcount]=$this->quote_creation->create_mission['tempo_length_option'][$mid];
								
							
								$this->quote_creation->create_mission['oneshot'][$tcount]=$this->quote_creation->create_mission['oneshot'][$mid];
								$this->quote_creation->create_mission['duration_dont_know'][$tcount]=$this->quote_creation->create_mission['duration_dont_know'][$mid];
								
								if($this->quote_creation->create_mission['duration_dont_know'][$tcount]=='yes')
								{
									$this->quote_creation->create_mission['mission_length'][$tcount]=0;
									$this->quote_creation->create_mission['mission_length_option'][$tcount]='days';
								}
								if($this->quote_creation->create_mission['product'][$tcount]=='content_strategy')
									{
									$this->quote_creation->create_mission['turnover'][$tcount]=$this->quote_creation->create_mission['turnover'][$mid];
									$this->quote_creation->create_mission['strategy_mission_cost'][$tcount]=$this->quote_creation->create_mission['internal_cost'][$mid];
									$this->quote_creation->create_mission['producttype'][$tcount]=$this->quote_creation->create_mission['producttype'][$mid];
									$this->quote_creation->create_mission['internal_cost'][$tcount]=$this->quote_creation->create_mission['internal_cost'][$mid];
									$this->quote_creation->create_mission['unit_price'][$tcount]=$this->quote_creation->create_mission['unit_price'][$mid];
									$this->quote_creation->create_mission['margin_percentage'][$tcount]=$this->quote_creation->create_mission['margin_percentage'][$mid];
									}
									/*duplicate mission after create*/
								$this->quoteMissoinUpdate("",$tcount);
					}
				}
				/**Tech Mission save */
				elseif ($mission_params['product']=="tech" && $mission_params['techedit']=='' && $mission_params['techduplicate']!='duplicate' && $mission_params['parameter']!="techduplicatenew") 
				{
					//echo "<pre>"; print_r($mission_params); exit;
					$quote_obj=new Ep_Quote_Quotes();
					$id=count($this->quote_creation->tech_mission['product']);
					$this->quote_creation->tech_mission['product'][$id]=$mission_params['product'];
					$this->quote_creation->tech_mission['language'][$id]=$mission_params['language'];
					$this->quote_creation->tech_mission['tech_oneshot'][$id]=$mission_params['tech_oneshot'];
					$this->quote_creation->tech_mission['tech_mission_length'][$id]=$mission_params['tech_mission_length'];
					$this->quote_creation->tech_mission['tech_mission_length_option'][$id]='days';
					$this->quote_creation->tech_mission['tech_title_id'][$id]=$mission_params['tech_title'];
					$this->quote_creation->tech_mission['tech_type'][$id]=$mission_params['tech_type'];
					$this->quote_creation->tech_mission['volume'][$id]=$mission_params['tech_volume'];
					if($mission_params['tech_oneshot']=='no')
					{
					$this->quote_creation->tech_mission['volume_max'][$id]=$mission_params['tech_volume_max'];
					$this->quote_creation->tech_mission['delivery_volume_option'][$id]=$mission_params['tech_delivery_volume_option'];
					$this->quote_creation->tech_mission['tempo_type'][$id]=$mission_params['tech_tempo_type'];
					$this->quote_creation->tech_mission['tempo_length'][$id]=$mission_params['tech_tempo_length'];
					$this->quote_creation->tech_mission['tempo_length_option'][$id]=$mission_params['tech_tempo_length_option'];

						if($this->quote_creation->tech_mission['tech_title_id'][$id])
							{
							$tecch_details=$quotes_obj->techtitleDetails($this->quote_creation->tech_mission['tech_title_id'][$id]);
							$this->quote_creation->tech_mission['mission_cost'][$id]=$tecch_details[0]['cost'];
							$this->quote_creation->tech_mission['internal_cost'][$id]=$this->quote_creation->tech_mission['mission_cost'][$id];
							}
					}
					else
					{
						if($this->quote_creation->tech_mission['tech_title_id'][$id])
						{
						$tecch_details=$quotes_obj->techtitleDetails($this->quote_creation->tech_mission['tech_title_id'][$id]);
						$this->quote_creation->tech_mission['mission_cost'][$id]=$tecch_details[0]['cost'];
						$this->quote_creation->tech_mission['internal_cost'][$id]=$this->quote_creation->tech_mission['mission_cost'][$id];
						}
					}
					
					$this->quote_creation->tech_mission['prod_mission_selected'][$id]=$mission_params['prod_mission_selected'];
					if($mission_params['prodmissionslist'])
						$this->quote_creation->tech_mission['linked_to_prod'][$id]=$mission_params['prodmissionslist'];
					else
						$this->quote_creation->tech_mission['linked_to_prod'][$id]='';

					
					
					$this->quote_creation->tech_mission['to_perform'][$id]=$mission_params['to_perform'];

					/*insert tech mission*/
						$this->techMissionUpdate("",$id);
															
									
						$this->_redirect("/quote-new/create-quote-mission-view");	


				}
				elseif ($mission_params['techedit']=='edit' && $mission_params['product']=="tech" && $mission_params['tid']!="") 
				{
					
					$id=array_search($mission_params['tid'],$this->quote_creation->tech_mission['mission_identifier']);
					
					$this->quote_creation->tech_mission['product'][$id]=$mission_params['product'];
					$this->quote_creation->tech_mission['language'][$id]=$mission_params['language'];
					$this->quote_creation->tech_mission['tech_oneshot'][$id]=$mission_params['tech_oneshot'];
					$this->quote_creation->tech_mission['tech_mission_length'][$id]=$mission_params['tech_mission_length'];
					$this->quote_creation->tech_mission['tech_mission_length_option'][$id]='days';
					$this->quote_creation->tech_mission['tech_title_id'][$id]=$mission_params['tech_title'];
					$this->quote_creation->tech_mission['tech_type'][$id]=$mission_params['tech_type'];
					$this->quote_creation->tech_mission['volume'][$id]=$mission_params['tech_volume'];
					if($mission_params['tech_oneshot']=='no')
					{
					$this->quote_creation->tech_mission['volume_max'][$id]=$mission_params['tech_volume_max'];
					$this->quote_creation->tech_mission['delivery_volume_option'][$id]=$mission_params['tech_delivery_volume_option'];
					$this->quote_creation->tech_mission['tempo_type'][$id]=$mission_params['tech_tempo_type'];
					$this->quote_creation->tech_mission['tempo_length'][$id]=$mission_params['tech_tempo_length'];
					$this->quote_creation->tech_mission['tempo_length_option'][$id]=$mission_params['tech_tempo_length_option'];
						if($this->quote_creation->tech_mission['tech_title_id'][$id])
						{
						$tecch_details=$quotes_obj->techtitleDetails($this->quote_creation->tech_mission['tech_title_id'][$id]);
						$this->quote_creation->tech_mission['mission_cost'][$id]=$tecch_details[0]['cost'];
						$this->quote_creation->tech_mission['internal_cost'][$id]=$this->quote_creation->tech_mission['mission_cost'][$id];
						}
					}
					else
					{
						if($this->quote_creation->tech_mission['tech_title_id'][$id])
						{
						$tecch_details=$quotes_obj->techtitleDetails($this->quote_creation->tech_mission['tech_title_id'][$id]);
						$this->quote_creation->tech_mission['mission_cost'][$id]=$tecch_details[0]['cost'];
						$this->quote_creation->tech_mission['internal_cost'][$id]=$this->quote_creation->tech_mission['mission_cost'][$id];
						}
					}
					
					$this->quote_creation->tech_mission['prod_mission_selected'][$id]=$mission_params['prod_mission_selected'];
					if($mission_params['prod_mission_selected']=='Yes')
						$this->quote_creation->tech_mission['linked_to_prod'][$id]=$mission_params['prodmissionslist'];
					else
						$this->quote_creation->tech_mission['linked_to_prod'][$id]='';

					
					
					$this->quote_creation->tech_mission['to_perform'][$id]=$mission_params['to_perform'];
					/*update tech mission*/
					$this->techMissionUpdate($mission_params['tid'],$id);

					$this->_redirect("/quote-new/create-quote-mission-view");


				}
				elseif ($mission_params['techduplicate']=='duplicate' && $mission_params['duplicatetitle'] && $mission_params['tid']!="" &&  $mission_params['parameter']!="techduplicatenew") 
				{	
					
					
					$id=array_search($mission_params['tid'],$this->quote_creation->tech_mission['mission_identifier']);;
					$tcount=count($this->quote_creation->tech_mission['product']);
					if(count($mission_params['duplicatetitle'])>0)
					{
							foreach($mission_params['duplicatetitle'] as $duplicatetitle)
							{
								//echo "<pre>".$mission_params['tid'].$id; print_r($this->quote_creation->tech_mission['mission_identifier']); exit;
								$tech_title=$quotes_obj->techtitleDetails($duplicatetitle);
								if($tech_title[0]['delivery_option']=='days')
								$deliverytimeval=$tech_title[0]['delivery_time'];
								else
								$deliverytimeval=ceil($tech_title[0]['delivery_time']/8);

								$this->quote_creation->tech_mission['tech_mission_length'][$tcount]=$deliverytimeval;
								$this->quote_creation->tech_mission['tech_mission_length_option'][$tcount]='days';			
								$this->quote_creation->tech_mission['tech_title_id'][$tcount]=$duplicatetitle;
								$this->quote_creation->tech_mission['tech_type'][$tcount]=$tech_title[0]['tech_title'];
								
								$this->quote_creation->tech_mission['product'][$tcount]=$this->quote_creation->tech_mission['product'][$id];
								
								$this->quote_creation->tech_mission['language'][$tcount]=$this->quote_creation->tech_mission['language'][$id];
								$this->quote_creation->tech_mission['tech_oneshot'][$tcount]=$this->quote_creation->tech_mission['tech_oneshot'][$id];
								$this->quote_creation->tech_mission['volume'][$tcount]=$this->quote_creation->tech_mission['volume'][$id];
								if($this->quote_creation->tech_mission['tech_oneshot'][$tcount]=='no')
								{
								$this->quote_creation->tech_mission['volume_max'][$tcount]=$this->quote_creation->tech_mission['volume_max'][$id];
								$this->quote_creation->tech_mission['delivery_volume_option'][$tcount]=$this->quote_creation->tech_mission['delivery_volume_option'][$id];
								$this->quote_creation->tech_mission['tempo_type'][$tcount]=$this->quote_creation->tech_mission['tempo_type'][$id];
								$this->quote_creation->tech_mission['tempo_length'][$tcount]=$this->quote_creation->tech_mission['tempo_length'][$id];
								$this->quote_creation->tech_mission['tempo_length_option'][$tcount]=$this->quote_creation->tech_mission['tempo_length_option'][$id];
									if($this->quote_creation->tech_mission['tech_title_id'][$tcount])
									{
									
									$this->quote_creation->tech_mission['mission_cost'][$tcount]=$tech_title[0]['cost'];
									$this->quote_creation->tech_mission['internal_cost'][$tcount]=$this->quote_creation->tech_mission['mission_cost'][$tcount];
									}
								}
								else
								{
									if($this->quote_creation->tech_mission['tech_title_id'][$tcount])
									{
									$this->quote_creation->tech_mission['mission_cost'][$tcount]=$tech_title[0]['cost'];
									$this->quote_creation->tech_mission['internal_cost'][$tcount]=$this->quote_creation->tech_mission['mission_cost'][$tcount];
									}
								}
								
								$this->quote_creation->tech_mission['to_perform'][$tcount]=$this->quote_creation->tech_mission['to_perform'][$id];
								$this->quote_creation->tech_mission['prod_mission_selected'][$tcount]=$this->quote_creation->tech_mission['prod_mission_selected'][$id];
								if($this->quote_creation->tech_mission['prod_mission_selected'][$tcount]=='Yes')
									$this->quote_creation->tech_mission['linked_to_prod'][$tcount]=$this->quote_creation->tech_mission['linked_to_prod'][$id];
								else
									$this->quote_creation->tech_mission['linked_to_prod'][$tcount]='';
							
								/*insert tech mission*/
								$this->techMissionUpdate("",$tcount);

								$tcount++;
							}
						$this->_redirect("/quote-new/create-quote-mission-view");
					}
				}
				elseif ($mission_params['techduplicate']=='duplicate' && $mission_params['duplicatetitle'] && $mission_params['parameter']=="techduplicatenew") 
				{
					//echo "<pre>"; print_r($mission_params); exit;
									
					$tcount=$t;
					if(count($mission_params['duplicatetitle'])>0)
					{
							foreach($mission_params['duplicatetitle'] as $duplicatetitleval)
							{
							$tech_title=$quotes_obj->techtitleDetails($duplicatetitleval);
							$this->quote_creation->tech_mission['tech_title_id'][$tcount]=$duplicatetitleval;
							$this->quote_creation->tech_mission['tech_type'][$tcount]=$tech_title[0]['tech_title'];
							$this->quote_creation->tech_mission['product'][$tcount]=$mission_params['product'];

							if($tech_title[0]['delivery_option']=='days')
								$deliverytimeval=$tech_title[0]['delivery_time'];
								else
								$deliverytimeval=ceil($tech_title[0]['delivery_time']/8);

							$this->quote_creation->tech_mission['tech_mission_length'][$tcount]=$deliverytimeval;
							$this->quote_creation->tech_mission['tech_mission_length_option'][$tcount]='days';
							
							
							$this->quote_creation->tech_mission['language'][$tcount]=$mission_params['language'];
							$this->quote_creation->tech_mission['tech_oneshot'][$tcount]=$mission_params['tech_oneshot'];
							$this->quote_creation->tech_mission['volume'][$tcount]=$mission_params['tech_volume'];
							if($mission_params['tech_oneshot']=='no')
							{
							$this->quote_creation->tech_mission['volume_max'][$tcount]=$mission_params['tech_volume_max'];
							$this->quote_creation->tech_mission['delivery_volume_option'][$tcount]=$mission_params['tech_delivery_volume_option'];
							$this->quote_creation->tech_mission['tempo_type'][$tcount]=$mission_params['tech_tempo_type'];
							$this->quote_creation->tech_mission['tempo_length'][$tcount]=$mission_params['tech_tempo_length'];
							$this->quote_creation->tech_mission['tempo_length_option'][$tcount]=$mission_params['tech_tempo_length_option'];
							if($this->quote_creation->tech_mission['tech_title_id'][$tcount])
								{
								$tecch_details=$quotes_obj->techtitleDetails($this->quote_creation->tech_mission['tech_title_id'][$tcount]);
								$this->quote_creation->tech_mission['mission_cost'][$tcount]=$tech_title[0]['cost'];
								$this->quote_creation->tech_mission['internal_cost'][$tcount]=$this->quote_creation->tech_mission['mission_cost'][$tcount];
								}
							}
							else
							{
								if($this->quote_creation->tech_mission['tech_title_id'][$tcount])
								{
								$tecch_details=$quotes_obj->techtitleDetails($this->quote_creation->tech_mission['tech_title_id'][$tcount]);
								$this->quote_creation->tech_mission['mission_cost'][$tcount]=$tech_title[0]['cost'];
								$this->quote_creation->tech_mission['internal_cost'][$tcount]=$this->quote_creation->tech_mission['mission_cost'][$tcount];
								}
							}
							
							$this->quote_creation->tech_mission['prod_mission_selected'][$tcount]=$mission_params['prod_mission_selected'];
							if($mission_params['prodmissionslist'])
								$this->quote_creation->tech_mission['linked_to_prod'][$tcount]=$mission_params['prodmissionslist'];
							else
								$this->quote_creation->tech_mission['linked_to_prod'][$tcount]='';

							$this->quote_creation->tech_mission['to_perform'][$tcount]=$mission_params['to_perform'];
							
							$this->techMissionUpdate("",$tcount);

							$tcount++;
							}
						$this->_redirect("/quote-new/create-quote-mission-view");	
					}
				}

			//Added w.r.t Edit
			unset($this->quote_creation->create_mission['mission_identifier']);

			$this->quote_creation->create_mission['identifier']=$mission_params['mission_id'];

			//added w.r.t edit quote changes when new mission added
			//if($this->quote_creation->custom['action']=='edit' && $this->quote_creation->custom['create_new_version']!='yes')
			if($this->quote_creation->custom['action']=='edit')
			{
				$mission_params['mission_id']=array_filter($mission_params['mission_id']);
				if(count($mission_params['product'])!=count($mission_params['mission_id']))
				{
					$this->quote_creation->custom['mission_added']='yes';
				}
				else{
					unset($this->quote_creation->custom['mission_added']);
				}

				//check whether any changes done in existing mission	
				if(!$this->quote_creation->custom['mission_added'])
				{
							
					foreach($this->quote_creation->create_mission['product'] as $key=>$product)
					{
						$mission_id=$this->quote_creation->create_mission['identifier'][$key];
						if($mission_id)
						{
							$updated=$this->checkMissionUpdate($key,$mission_id);	
							if($updated)
								$this->quote_creation->custom['mission_added']='yes';								
							else
								unset($this->quote_creation->custom['mission_added']);

						}
						if($this->quote_creation->custom['mission_added']=='yes')
							break;				
					}
					//exit;
				}
			}		
			
			//echo "<pre>";print_r($this->quote_creation->create_mission);exit;

			$this->_redirect("/quote-new/create-quote-mission-view");

		}	
	}
	/*delete Quote Mission*/
	public function deleteQuoteMissionAction()
	{

		$quote_obj=new Ep_Quote_Quotes();	
		$mission_params=$this->_request->getParams();
		$quoteMission_obj=new Ep_Quote_QuoteMissions();
		$techMission_obj=new Ep_Quote_TechMissions();
		$m_id=array_search($mission_params['mid'],$this->quote_creation->create_mission['mission_identifier']);

		if($mission_params['mid'])
		{			
			$this->custom_unset($this->quote_creation->create_mission['turnover'],$m_id);	
			$this->custom_unset($this->quote_creation->create_mission['strategy_mission_cost'],$m_id);	
			
			$this->custom_unset($this->quote_creation->create_mission['product'],$m_id);
			$this->custom_unset($this->quote_creation->create_mission['product_name'],$m_id);
			$this->custom_unset($this->quote_creation->create_mission['language'],$m_id);
			$this->custom_unset($this->quote_creation->create_mission['language_name'],$m_id);
			$this->custom_unset($this->quote_creation->create_mission['languagedest'],$m_id);
			$this->custom_unset($this->quote_creation->create_mission['languagedest_name'],$m_id);

			$this->custom_unset($this->quote_creation->create_mission['producttype'],$m_id);
			$this->custom_unset($this->quote_creation->create_mission['producttypeother'],$m_id);
			$this->custom_unset($this->quote_creation->create_mission['producttype_name'],$m_id);
			$this->custom_unset($this->quote_creation->create_mission['nb_words'],$m_id);
			$this->custom_unset($this->quote_creation->create_mission['volume'],$m_id);
				
				
			$this->custom_unset($this->quote_creation->create_mission['volume_max'],$m_id);
			$this->custom_unset($this->quote_creation->create_mission['mission_length'],$m_id);
			$this->custom_unset($this->quote_creation->create_mission['mission_length_option'],$m_id);
			$this->custom_unset($this->quote_creation->create_mission['delivery_volume_option'],$m_id);
			$this->custom_unset($this->quote_creation->create_mission['tempo_type'],$m_id);
			$this->custom_unset($this->quote_creation->create_mission['tempo_length'],$m_id);
			$this->custom_unset($this->quote_creation->create_mission['tempo_length_option'],$m_id);
			$this->custom_unset($this->quote_creation->create_mission['oneshot'],$m_id);
			$this->custom_unset($this->quote_creation->create_mission['demande_client'],$m_id);
			$this->custom_unset($this->quote_creation->create_mission['duration_dont_know'],$m_id);
			$this->custom_unset($this->quote_creation->create_mission['selected_mission'],$m_id);
			$this->custom_unset($this->quote_creation->create_mission['unit_price'],$m_id);
			$this->custom_unset($this->quote_creation->create_mission['internal_cost'],$m_id);
			$this->custom_unset($this->quote_creation->create_mission['margin_percentage'],$m_id);
			$this->custom_unset($this->quote_creation->create_mission['mission_identifier'],$m_id);

			$this->custom_unset($this->quote_creation->create_mission['quote_missions'],$m_id);

			if($mission_params['mid'])
			$quoteMission_obj->deleteQuoteMission($mission_params['mid']);

			if(in_array($mission_params['mid'],$this->quote_creation->tech_mission['linked_to_prod']))
			{
				
				foreach($this->quote_creation->tech_mission['linked_to_prod'] as $tkey=>$val)
				{
					if($mission_params['mid']==$val)
					{
						
						$tech['identifier']=$this->quote_creation->tech_mission['mission_identifier'][$tkey];
						$this->custom_unset($this->quote_creation->tech_mission['mission_identifier'],$tkey);
						$this->custom_unset($this->quote_creation->tech_mission['language'],$tkey);
						$this->custom_unset($this->quote_creation->tech_mission['product'],$tkey);
						$this->custom_unset($this->quote_creation->tech_mission['tech_mission_length'],$tkey);
						$this->custom_unset($this->quote_creation->tech_mission['tech_mission_length_option'],$tkey);
						$this->custom_unset($this->quote_creation->tech_mission['mission_cost'],$tkey);
						$this->custom_unset($this->quote_creation->tech_mission['internal_cost'],$tkey);
						$this->custom_unset($this->quote_creation->tech_mission['tech_title_id'],$tkey);
						$this->custom_unset($this->quote_creation->tech_mission['tech_type'],$tkey);
						$this->custom_unset($this->quote_creation->tech_mission['tech_missions'],$tkey);
						$this->custom_unset($this->quote_creation->tech_mission['tech_missions']['linked_to_prod'],$tkey);
						$this->custom_unset($this->quote_creation->tech_mission['tech_missions']['prod_mission_selected'],$tkey);
						$this->custom_unset($this->quote_creation->tech_mission['tempo_type'],$tkey);
						$this->custom_unset($this->quote_creation->tech_mission['tempo_length'],$tkey);
						$this->custom_unset($this->quote_creation->tech_mission['tempo_length_option'],$tkey);
						$this->custom_unset($this->quote_creation->tech_mission['tech_oneshot'],$tkey);
						$this->custom_unset($this->quote_creation->tech_mission['volume'],$tkey);
						$this->custom_unset($this->quote_creation->tech_mission['volume_max'],$tkey);
						$this->custom_unset($this->quote_creation->tech_mission['prod_mission_selected'],$tkey);
						$this->custom_unset($this->quote_creation->tech_mission['linked_to_prod'],$tkey);

						$this->custom_unset($this->quote_creation->create_mission['tech_missions']['quote_missions']['tech'],$tkey);
						$this->custom_unset($this->quote_creation->create_mission['tech_missions']['quote_missions']['tech']['linked_to_prod'],$tkey);
						$this->custom_unset($this->quote_creation->create_mission['tech_missions']['quote_missions']['tech']['prod_mission_selected'],$tkey);
						 
						
						if($tech['identifier'])
							$techMission_obj->deleteTechMission($tech['identifier']);

							if($this->quote_creation->custom['quote_id'])
							{
													
								$quoteDetails=$quote_obj->getQuoteDetails($this->quote_creation->custom['quote_id']);
								if($quoteDetails)
								{
									$assigned_tech_missions=explode(",",$quoteDetails[0]['techmissions_assigned']);

									$key = array_search($tech['identifier'], $assigned_tech_missions);
									unset($assigned_tech_missions[$key]);

									$update_quote['techmissions_assigned']=implode(",",$assigned_tech_missions);
									$quote_obj->updateQuote($update_quote,$this->quote_creation->custom['quote_id']);
									$this->autoQuoteSession($this->quote_creation->custom['quote_id'],'briefing');
								}	
							}
					}
					

				}
					
							
							
						

			}
			

			$this->_redirect("/quote-new/create-quote-mission-view");
			
		}
		elseif($mission_params['tid']!="")
		{
			
			$tid=array_search($mission_params['tid'],$this->quote_creation->tech_mission['mission_identifier']);
			$this->custom_unset($this->quote_creation->tech_mission['language'],$tid);
			$this->custom_unset($this->quote_creation->tech_mission['product'],$tid);
			$this->custom_unset($this->quote_creation->tech_mission['tech_mission_length'],$tid);
			$this->custom_unset($this->quote_creation->tech_mission['tech_mission_length_option'],$tid);
			$this->custom_unset($this->quote_creation->tech_mission['mission_cost'],$tid);
			$this->custom_unset($this->quote_creation->tech_mission['tech_title_id'],$tid);
			$this->custom_unset($this->quote_creation->tech_mission['tech_type'],$tid);
			$this->custom_unset($this->quote_creation->tech_mission['tech_missions'],$tid);
			$this->custom_unset($this->quote_creation->tech_mission['tech_missions']['linked_to_prod'],$tid);
			$this->custom_unset($this->quote_creation->tech_mission['tech_missions']['prod_mission_selected'],$tid);
			$this->custom_unset($this->quote_creation->tech_mission['tempo_type'],$tid);
			$this->custom_unset($this->quote_creation->tech_mission['tempo_length'],$tid);
			$this->custom_unset($this->quote_creation->tech_mission['tempo_length_option'],$tid);
			$this->custom_unset($this->quote_creation->tech_mission['tech_oneshot'],$tid);
			$this->custom_unset($this->quote_creation->tech_mission['volume'],$tid);
			$this->custom_unset($this->quote_creation->tech_mission['volume_max'],$tid);
			$this->custom_unset($this->quote_creation->tech_mission['prod_mission_selected'],$tid);
			$this->custom_unset($this->quote_creation->tech_mission['linked_to_prod'],$tid);
			$this->custom_unset($this->quote_creation->tech_mission['mission_identifier'],$tid);
			
			/*delete tech mission*/
			if($mission_params['tid'])
				$techMission_obj->deleteTechMission($mission_params['tid']);

			if($this->quote_creation->custom['quote_id'])
						{
							
							$tk=0;
							$techmissupdate="";
							foreach($this->quote_creation->tech_mission['mission_identifier'] as $tkey=>$tval)
							{
								if($tk==0)
								$techmissupdate.=$tval;
								else
								$techmissupdate.=','.$tval;
							$tk++;
							}
							$quote_data['techmissions_assigned']=$techmissupdate;
							$quote_obj->updateQuote($quote_data,$this->quote_creation->custom['quote_id']);
						}

			$this->_redirect("/quote-new/create-quote-mission-view");
			
			
		}

		

	}


	function createQuoteMissionViewAction()
	{	
		$mission_params=$this->_request->getParams();
			if($mission_params['quote_id'])
			{
				//$this->_redirect("/quote-new/client-brief?qaction=briefing&quote_id=".$mission_params['quote_id']."");
				$this->autoQuoteSession($mission_params['quote_id'],'briefing');
			}

			$hmission_obj=new Ep_Quote_HistoryQuoteMissions();

			
			/**Quote Version**/
			if($this->quote_creation->custom['create_new_version']=='yes' && $this->quote_creation->custom['quote_id'])
				{
				$quoteIdentifier=$this->quote_creation->custom['quote_id'];
					/*inserting quote version*/
					if($quoteIdentifier)
					{
						$version=$this->quote_creation->custom['version'];

						$old_version='v'.($this->quote_creation->custom['version']-1);
						$new_version='v'.($version);
						$old_title=$this->quote_creation->create_step1['quote_title'];

						$this->quote_creation->create_step1['quote_title']=str_replace($old_version, $new_version, $old_title);

			        	$quotes_data['title']=$this->quote_creation->create_step1['quote_title'];

						$quote_obj=new Ep_Quote_Quotes();	
						$qiversion=($this->quote_creation->custom['version']-1);
						$checkquoteVersion=$quote_obj->getQuoteVersionDetails($quoteIdentifier,$qiversion);

        				if($checkquoteVersion==0)
        				{
							$quote_obj->insertQuoteVersion($quoteIdentifier);
								/*sending Email when new version create*/
								$quoteEditDetails=$quote_obj->getQuoteDetails($quoteIdentifier);
					    
								    if($quoteEditDetails[0]['version']>1)
								    {
								    	$client_obj=new Ep_Quote_Client();
								   
										$email_head_sale=array('120206112651459'=>'cleguille@edit-place.com',
								                             '110920152530186'=>'mfouris@edit-place.com'); // need to add thaibault
											if(count($email_head_sale)>0 ){
															   
														foreach($email_head_sale as $user=>$emails){
															$receiver_id=$user;
															//$headmail_obj=new Ep_Message_AutoEmails();
															$headmail_parameters['version']=true;
															$headmail_parameters['subject']='A new quote V'.$quoteEditDetails[0]['version'].' has been created on Workplace';
															$bouser=$client_obj->getQuoteUserDetails($receiver_id);
															$headmail_parameters['bo_user_name']=$bouser[0]['first_name'].' '.$bouser[0]['last_name'];
															$headmail_parameters['bo_user']=$this->adminLogin->userId;
															$quoteuser=$client_obj->getQuoteUserDetails($this->adminLogin->userId);
															$headmail_parameters['quote_user']= $quoteuser[0]['first_name'].' '.$quoteuser[0]['last_name'];
															$headmail_parameters['quote_title']=$quoteEditDetails[0]['title'];
															$headmail_parameters['estimate_sign_percentage']=$quoteEditDetails[0]['estimate_sign_percentage'];
															$headmail_parameters['sales_user']=$this->adminLogin->userId;
															$headmail_parameters['turn_over']=$quoteEditDetails[0]['final_turnover'];
															$headmail_parameters['client_id']=$quoteEditDetails[0]['client_id'];
															$headmail_parameters['client_name']=$quoteEditDetails[0]['company_name'];
															$headmail_parameters['quote_version']=$quoteEditDetails[0]['version'];
															$headmail_parameters['agency_name']=$quoteEditDetails[0]['agency_name'];	
															$headmail_parameters['followup_link']='http://'.$_SERVER['HTTP_HOST'].'/quote-new/client-brief?qaction=briefing&quote_id='.$quoteEditDetails[0]['identifier'];
															//$headmail_parameters['followup_link']='/quote/quote-followup?quote_id='.$quoteEditDetails[0]['identifier'];	
															//print_r($headmail_parameters); exit;
															$this->getNewQuoteEmails($headmail_parameters);
															//$headmail_obj->sendQuotePersonalEmail($receiver_id,204,$headmail_parameters);
													}
											}
									}	
							
        				}

						$quotes_data['sales_review']='not_done';
						$quotes_data['version']=$this->quote_creation->custom['version'];
						$quotes_data['updated_at']=date("Y-m-d H:i:s");
						$quote_obj->updateQuote($quotes_data,$quoteIdentifier);

						/*quote mission version*/
						$qmission_obj=new Ep_Quote_QuoteMissions();
						$searchmissionparam['quote_id']=$quoteIdentifier;
						$mssionDetails=$qmission_obj->getMissionDetails($searchmissionparam);
						foreach($mssionDetails as $missionval)
						{
							$quoteMissionId=$missionval['identifier'];
							$qversion=($this->quote_creation->custom['version']-1);
								$checkexsist=$qmission_obj->getMissionVersionDetails($quoteMissionId,$qversion);
								if($checkexsist==0)
								{
									$this->quotesMissionVersion($quoteMissionId,'quotemission');
									$quoteMission_data['version']=$this->quote_creation->custom['version'];
									$qmission_obj->updateQuoteMission($quoteMission_data,$quoteMissionId);
								}
	
						}

						/*techmission mission version*/
						$tqmission_obj=new Ep_Quote_TechMissions();
						$searchmissionparam['quote_id']=$quoteIdentifier;
						$techMissDetails=$tqmission_obj->getTechMissionDetails($searchmissionparam);
						foreach($techMissDetails as $tmi)
						{
							$techMissionId=$tmi['identifier'];
							$tversion=$this->quote_creation->custom['version']-1;
							$checktechmission=$tqmission_obj->getQuoteVersionDetails($quoteIdentifier,$tversion);
							if($checktechmission==0)
								$this->quotesMissionVersion($techMissionId,'techmission');

							$techMission_data['version']=$this->quote_creation->custom['version'];
							$tqmission_obj->updateTechMission($techMission_data,$techMissionId);
						}
						
					}

			}

		//echo "<pre>"; print_r($this->quote_creation->tech_mission); exit;
		//echo count($this->quote_creation->tech_mission['product'])."<pre>";print_r($this->quote_creation->tech_mission);exit;

		if((is_array($this->quote_creation->create_mission['product']) && count($this->quote_creation->create_mission['product'])>0) || count($this->quote_creation->tech_mission['product'])>0 )
		{
			//echo "<pre>"; print_r($this->quote_creation->create_mission);exit;
			$i=0;
			foreach($this->quote_creation->create_mission['product'] as $mission)
			{
				/*Added w.r.t Autre mission*/
				if($this->quote_creation->create_mission['product'][$i]=='autre')
				{
					$this->quote_creation->create_mission['language'][$i]='fr';
					$this->quote_creation->create_mission['volume'][$i]=1;
					$this->quote_creation->create_mission['nb_words'][$i]=1;
					$this->quote_creation->create_mission['producttype'][$i]='autre';
				}

				if($this->quote_creation->create_mission['product'][$i]=='content_strategy')
				{
					$quote_missions[$i]['product']=$this->quote_creation->create_mission['product'][$i];
					$quote_missions[$i]['product_name']=$this->product_array[$this->quote_creation->create_mission['product'][$i]];
					$quote_missions[$i]['language']=$this->quote_creation->create_mission['language'][$i];
					$quote_missions[$i]['language_name']=$this->getLanguageName($this->quote_creation->create_mission['language'][$i]);
					$quote_missions[$i]['producttype']=$this->quote_creation->create_mission['producttype'][$i];
					$quote_missions[$i]['producttype_name']=$this->seo_producttype_array[$this->quote_creation->create_mission['producttype'][$i]];
					$quote_missions[$i]['internal_cost']=$this->quote_creation->create_mission['internal_cost'][$i];
					$quote_missions[$i]['mission_length']=$this->quote_creation->create_mission['mission_length'][$i];
					$quote_missions[$i]['mission_length_option']=$this->quote_creation->create_mission['mission_length_option'][$i];
					$quote_missions[$i]['unit_price']=$this->quote_creation->create_mission['unit_price'][$i];
					$quote_missions[$i]['margin_percentage']=$this->quote_creation->create_mission['margin_percentage'][$i];
					$quote_missions[$i]['volume']=$this->quote_creation->create_mission['volume'][$i];
					$quote_missions[$i]['turnover']=$this->quote_creation->create_mission['turnover'][$i];
					$quote_missions[$i]['identifier']=$this->quote_creation->create_mission['mission_identifier'][$i];	
				}
				else
				{
					$quote_missions[$i]['product']=$this->quote_creation->create_mission['product'][$i];
					$quote_missions[$i]['product_name']=$this->product_array[$this->quote_creation->create_mission['product'][$i]];
					$quote_missions[$i]['language']=$this->quote_creation->create_mission['language'][$i];
					$quote_missions[$i]['language_name']=$this->getLanguageName($this->quote_creation->create_mission['language'][$i]);
					$quote_missions[$i]['languagedest']=$this->quote_creation->create_mission['languagedest'][$i];
					$quote_missions[$i]['languagedest_name']=$this->getLanguageName($this->quote_creation->create_mission['languagedest'][$i]);

					$quote_missions[$i]['producttype']=$this->quote_creation->create_mission['producttype'][$i];
					$quote_missions[$i]['producttypeother']=$this->quote_creation->create_mission['producttypeother'][$i];
					$quote_missions[$i]['producttype_name']=$this->producttype_array[$this->quote_creation->create_mission['producttype'][$i]];
					$quote_missions[$i]['nb_words']=$this->quote_creation->create_mission['nb_words'][$i];
					$quote_missions[$i]['volume']=$this->quote_creation->create_mission['volume'][$i];
					
					/*added w.r.t Tempo*/
					$quote_missions[$i]['volume_max']=$this->quote_creation->create_mission['volume_max'][$i];
					$quote_missions[$i]['mission_length']=$this->quote_creation->create_mission['mission_length'][$i];
					$quote_missions[$i]['mission_length_option']=$this->quote_creation->create_mission['mission_length_option'][$i];
					$quote_missions[$i]['delivery_volume_option']=$this->quote_creation->create_mission['delivery_volume_option'][$i];
					$quote_missions[$i]['tempo_type']=$this->quote_creation->create_mission['tempo_type'][$i];
					$quote_missions[$i]['tempo_length']=$this->quote_creation->create_mission['tempo_length'][$i];
					$quote_missions[$i]['tempo_length_option']=$this->quote_creation->create_mission['tempo_length_option'][$i];
					$quote_missions[$i]['oneshot']=$this->quote_creation->create_mission['oneshot'][$i];
					$quote_missions[$i]['demande_client']=$this->quote_creation->create_mission['demande_client'][$i];
					$quote_missions[$i]['duration_dont_know']=$this->quote_creation->create_mission['duration_dont_know'][$i];
					//flag retrive
					$quote_missions[$i]['producttypeautre']=$this->quote_creation->create_mission['producttypeautre'][$i];
					
					
					$quote_missions[$i]['comments']=$this->quote_creation->create_mission['comments'][$i];

					$quote_missions[$i]['identifier']=$this->quote_creation->create_mission['mission_identifier'][$i];				

					/*newly added*/
					$quote_missions[$i]['unit_price']=$this->quote_creation->create_mission['unit_price'][$i];
						if(!$quote_missions[$i]['unit_price'])$quote_missions[$i]['unit_price']=0;
					
					$quote_missions[$i]['margin_percentage']=$this->quote_creation->create_mission['margin_percentage'][$i];
						if(!$quote_missions[$i]['margin_percentage'])$quote_missions[$i]['margin_percentage']=0;
						
					$quote_missions[$i]['internal_cost']=$this->quote_creation->create_mission['internal_cost'][$i];
						if(!$quote_missions[$i]['internal_cost'])$quote_missions[$i]['internal_cost']=0;	
					$quote_missions[$i]['turnover']=$quote_missions[$i]['unit_price']*$quote_missions[$i]['volume'];
					
					$quote_missions[$i]['selected_mission']=$this->quote_creation->create_mission['selected_mission'][$i];

					//$quote_missions[$i]['selected_mission']=$this->quote_creation->create_mission['selected_mission'][$i];
					
					
					//mission object
					
					$searchParameters=array();
					

					/*dont change the order of this array*/
					$searchParameters['product']=$this->quote_creation->create_mission['product'][$i];
					$searchParameters['language_source']=$this->quote_creation->create_mission['language'][$i];
					$searchParameters['language_dest']=$this->quote_creation->create_mission['languagedest'][$i];
					$searchParameters['product_type']=$this->quote_creation->create_mission['producttype'][$i];
					$searchParameters['selected_mission']=$quote_missions[$i]['selected_mission'];
					$searchParameters['nb_words']=$this->quote_creation->create_mission['nb_words'][$i]; 
					$searchParameters['mcurrency']=$this->quote_creation->create_step1['currency'];
					$searchParameters['client_id']=$this->quote_creation->create_step1['client_id'];
					
					//$searchParameters['volume']=$this->quote_creation->create_mission['volume'][$i];
					
					$missionDetails=array();
					$crossDomainMissionDetails=array();
					$missionDetails=$hmission_obj->getMissionDetails($searchParameters,3);	
										
					
					if((is_array($missionDetails) && count($missionDetails) < 3)|| (!$missionDetails))
					{
						if(!$missionDetails)
							$missionDetails=array();
						
						/*build service link to get HOQ prices from FR*/
						$service_link=$this->web_service_hoq_prices_link;
						//get details from FR with service link
						$crossDomainMissionDetails=$this->webHttpClient($service_link,$searchParameters);
						//echo "<pre>";print_r($crossDomainMissionDetails);
						
						if(count($crossDomainMissionDetails)>0)
						{							
							$missionDetails=array_merge($missionDetails,$crossDomainMissionDetails);
							//echo "<pre>";print_r($missionDetails);exit;
						}	
						$missionDetails=array_slice($missionDetails,0,3);
						//echo "<pre>";print_r($missionDetails);//exit;
					}
					

					
					if($missionDetails)
					{
						//$m=0;
						//foreach($missionDetails as $misson)
						for($m=0;$m<3;$m++)
						{
							$misson=$missionDetails[$m];
							//$missionDetails[$m]=$mission;
							/* if(!$misson['real_cost'])
								$missionDetails[$m]['real_cost']=0;
							if(!$misson['real_unit_price'])
								$missionDetails[$m]['real_unit_price']=0; */
							
							
							
							$missionDetails[$m]['category_name']=$this->getCategoryName($misson['category']);
							$missionDetails[$m]['product']=$this->product_array[$misson['product']];
							$missionDetails[$m]['language_source']=$this->getLanguageName($misson['language_source']);
							$missionDetails[$m]['language_dest']=$this->getLanguageName($misson['language_dest']);
							$missionDetails[$m]['product_type']=$this->producttype_array[$misson['product_type']];

							/**adding conversion*/
							$conversion=$this->quote_creation->create_step1['conversion'];

							if($this->quote_creation->create_step1['currency']!=$missionDetails[$m]['currency'])
							$missionDetails[$m]['unit_price']=$missionDetails[$m]['unit_price']*$conversion;
							
							if(!$missionDetails[$m]['company_name'])
							{
								$client_obj=new Ep_Quote_Client();
								$clientDetails=$client_obj->getClientDetails($misson['client_id']);
								if($clientDetails!='NO')
								{
									$client_info=$clientDetails[0];	
									
									$company_name=$client_info['company_name'];
									$missionDetails[$m]['company_name']=$company_name;
								}
							}	
							
						}

						$quote_missions[$i]['missionDetails']=$missionDetails;
					}

				}
				
				$i++;
			}		
		 	

			 /***Tech mission integration***/
			 	if(is_array($this->quote_creation->tech_mission['product']) && count($this->quote_creation->tech_mission['product'])>0)
				{
					//echo "<pre>";print_r($this->quote_creation->tech_mission); exit;
					$t=0;
					foreach($this->quote_creation->tech_mission['product'] as $tmission)
					{
						
						$tech_missions[$t]['product']=$this->quote_creation->tech_mission['product'][$t];
						if($this->quote_creation->tech_mission['margin_percentage'][$t])
							$tech_missions[$t]['margin_percentage']=$this->quote_creation->tech_mission['margin_percentage'][$t];
						else
							$tech_missions[$t]['margin_percentage']=60.00;

						$tech_missions[$t]['language']=$this->getLanguageName($this->quote_creation->tech_mission['language'][$t]);
						$tech_missions[$t]['volume']=$this->quote_creation->tech_mission['volume'][$t];
						$tech_missions[$t]['tech_mission_length']=$this->quote_creation->tech_mission['tech_mission_length'][$t];
						$tech_missions[$t]['tech_mission_length_option']=$this->quote_creation->tech_mission['tech_mission_length_option'][$t];
						$tech_missions[$t]['internal_cost']=$this->quote_creation->tech_mission['mission_cost'][$t];
						$tech_missions[$t]['unit_price']=($tech_missions[$t]['internal_cost']/(1-$tech_missions[$t]['margin_percentage']/100));
						$tech_missions[$t]['turnover']=$tech_missions[$t]['unit_price']*$tech_missions[$t]['volume'];
						$tech_missions[$t]['tech_title_id']=$this->quote_creation->tech_mission['tech_title_id'][$t];

						$tech_missions[$t]['identifier']=$this->quote_creation->tech_mission['mission_identifier'][$t];
						$tech_missions[$t]['tech_type']=$this->quote_creation->tech_mission['tech_type'][$t];
						$tech_missions['linked_to_prod'][$t]=$this->quote_creation->tech_mission['linked_to_prod'][$t];
						$tech_missions['prod_mission_selected'][$t]=$this->quote_creation->tech_mission['prod_mission_selected'][$t];
						$tech_missions[$t]['to_perform']=$this->quote_creation->tech_mission['to_perform'][$t];


						/*mission selected
						$searchParameters['include_final']='yes';
						$tmissionDetails=$hmission_obj->getTechMissionDetails($searchParameters,3);
						
							if($tmissionDetails)
							{
							$tm=0;
								foreach($tmissionDetails as $tmisson)
								{
									//$missionDetails[$m]=$mission;
									if(!$tmisson['real_cost'])
										$tmissionDetails[$tm]['real_cost']=0;
									if(!$tmisson['real_unit_price'])
										$tmissionDetails[$tm]['real_unit_price']=0;
									
									$tmissionDetails[$tm]['mission_id']=$tmisson['identifier'];
									$tmissionDetails[$tm]['title']=$tmisson['title'];
									$tmissionDetails[$tm]['internal_cost']=$tmisson['cost'];
									

									$conversion=$this->quote_creation->create_step1['conversion'];
									
									if($this->quote_creation->create_step1['sales_suggested_currency']!=$tmisson['currency'])
											$tmissionDetails[$tm]['unit_price']=$tmisson['unit_price']*$conversion;
									
									
									$tm++;
								}
									$tech_missions[$t]['tmissionDetails']=$tmissionDetails;
							}*/	

						$t++;
					}	

					$this->quote_creation->tech_mission['tech_missions']=$tech_missions;
					$quote_missions['tech']=$tech_missions;
					//$this->_view->tech_missions=$tech_missions;

				}	
				//echo "<pre>";	print_r($quote_missions); exit;
 			$this->quote_creation->create_mission['quote_missions']=$quote_missions;
			 $this->_view->custom=$this->quote_creation->custom;
			$this->_view->quote_missions=$quote_missions;
			$this->_view->create_mission=$this->quote_creation->create_mission;
	        $this->_view->create_step1=$this->quote_creation->create_step1;
	        $this->_view->select_missions=$this->quote_creation->select_missions;
			$this->_view->missionCount=count($quote_missions);
			
		}
		
		else
		{
			if(!$this->quote_creation->create_step1['client_id'])
			{
				$this->_redirect("/quote-new/create-step1");
			}
		}
		
		$this->render('create-quote-mission-view');
	}

	/*Mission Details popup*/
	public function createQuoteMissionDetailsPopupAction()
	{

		$mission_params=$this->_request->getParams();

		$m_id=array_search($mission_params['m_index'],$this->quote_creation->create_mission['mission_identifier']);
		
		$tid=array_search($mission_params['t_index'],$this->quote_creation->tech_mission['mission_identifier']);
		
		if(isset($m_id) && count($this->quote_creation->create_mission['product'][$m_id])>0)
		{
			
				if($this->quote_creation->create_mission['product'][$m_id]=='content_strategy')
				{
						$mission_details['product']=$this->quote_creation->create_mission['product'][$m_id];
						$mission_details['product_name']=$this->product_array[$this->quote_creation->create_mission['product'][$m_id]];
						$mission_details['producttype']=$this->quote_creation->create_mission['producttype'][$m_id];
						$mission_details['producttype_name']=$this->seo_producttype_array[$this->quote_creation->create_mission['producttype'][$m_id]];
					
						$mission_details['language']=$this->quote_creation->create_mission['language'][$m_id];
						$mission_details['language_name']=$this->getLanguageName($this->quote_creation->create_mission['language'][$m_id]);
						$mission_details['internal_cost']=$this->quote_creation->create_mission['strategy_mission_cost'][$m_id];
						$mission_details['mission_length']=$this->quote_creation->create_mission['mission_length'][$m_id];
						$mission_details['mission_length_option']=$this->quote_creation->create_mission['mission_length_option'][$m_id];

				}
				else
				{	
						$mission_details['product']=$this->quote_creation->create_mission['product'][$m_id];
						$mission_details['product_name']=$this->product_array[$this->quote_creation->create_mission['product'][$m_id]];
						$mission_details['language']=$this->quote_creation->create_mission['language'][$m_id];
						$mission_details['language_name']=$this->getLanguageName($this->quote_creation->create_mission['language'][$m_id]);
						$mission_details['languagedest']=$this->quote_creation->create_mission['languagedest'][$m_id];
						$mission_details['languagedest_name']=$this->getLanguageName($this->quote_creation->create_mission['languagedest'][$m_id]);

						$mission_details['producttype']=$this->quote_creation->create_mission['producttype'][$m_id];
						$mission_details['producttypeother']=$this->quote_creation->create_mission['producttypeother'][$m_id];
						$mission_details['producttype_name']=$this->producttype_array[$this->quote_creation->create_mission['producttype'][$m_id]];
						$mission_details['nb_words']=$this->quote_creation->create_mission['nb_words'][$m_id];
						$mission_details['volume']=$this->quote_creation->create_mission['volume'][$m_id];
						
						/*added w.r.t Tempo*/
						$mission_details['volume_max']=$this->quote_creation->create_mission['volume_max'][$m_id];
						$mission_details['mission_length']=$this->quote_creation->create_mission['mission_length'][$m_id];
						$mission_details['mission_length_option']=$this->quote_creation->create_mission['mission_length_option'][$m_id];
						$mission_details['delivery_volume_option']=$this->quote_creation->create_mission['delivery_volume_option'][$m_id];
						$mission_details['tempo_type']=$this->quote_creation->create_mission['tempo_type'][$m_id];
						$mission_details['tempo_length']=$this->quote_creation->create_mission['tempo_length'][$m_id];
						$mission_details['tempo_length_option']=$this->quote_creation->create_mission['tempo_length_option'][$m_id];
						$mission_details['oneshot']=$this->quote_creation->create_mission['oneshot'][$m_id];
						$mission_details['demande_client']=$this->quote_creation->create_mission['demande_client'][$m_id];
						$mission_details['duration_dont_know']=$this->quote_creation->create_mission['duration_dont_know'][$m_id];

						foreach($this->quote_creation->tech_mission['linked_to_prod'] as $techid=>$techval)
						{
							if($this->quote_creation->create_mission['mission_identifier'][$m_id]==$techval)
							{
							$linked_prod[]=$this->quote_creation->tech_mission['mission_identifier'][$techid];
							}
						}
						if(count($linked_prod)>0)
						{
							$mission_details['linked_product']=implode(',',$linked_prod);
						}
				}
				//echo "<pre>"; print_r($mission_details); exit;
				$this->_view->missions_details=$mission_details;
		}
		if(isset($tid) && count($this->quote_creation->tech_mission['product'][$tid])>0)
		{
			
				$techmission_details['product']=$this->quote_creation->tech_mission['product'][$tid];
				$techmission_details['language']=$this->getLanguageName($this->quote_creation->tech_mission['language'][$tid]);
				$techmission_details['tech_type']=$this->quote_creation->tech_mission['tech_missions'][$tid]['tech_type'];
				$techmission_details['tech_mission_length']=$this->quote_creation->tech_mission['tech_missions'][$tid]['tech_mission_length'];
				$techmission_details['tech_mission_length_option']=$this->quote_creation->tech_mission['tech_missions'][$tid]['tech_mission_length_option'];
				$techmission_details['internal_cost']=$this->quote_creation->tech_mission['tech_missions'][$tid]['internal_cost'];
				$techmission_details['unit_price']=$this->quote_creation->tech_mission['tech_missions'][$tid]['unit_price'];
				$techmission_details['turnover']=$this->quote_creation->tech_mission['tech_missions'][$tid]['turnover'];
				$techmission_details['volume']=$this->quote_creation->tech_mission['volume'][$tid];
				$techmission_details['volume_max']=$this->quote_creation->tech_mission['volume_max'][$tid];
				$techmission_details['delivery_volume_option']=$this->quote_creation->tech_mission['delivery_volume_option'][$tid];
				$techmission_details['tempo_type']=$this->quote_creation->tech_mission['tempo_type'][$tid];
				$techmission_details['tempo_length']=$this->quote_creation->tech_mission['tempo_length'][$tid];
				$techmission_details['tempo_length_option']=$this->quote_creation->tech_mission['tempo_length_option'][$tid];
				$techmission_details['tech_oneshot']=$this->quote_creation->tech_mission['tech_oneshot'][$tid];
				$this->_view->techmission_details=$techmission_details;
				
		}

		$this->render('create-quote-mission-details-popup');
	}

	function techDeliveryMissionAction()
	{
		$quotes_obj=new Ep_Quote_Quotes();	
		$techParams=$this->_request->getParams();
		if($techParams['title_id'])
		{
		$tech_detailsoption=$quotes_obj->techtitleDetails($techParams['title_id']);
		if($tech_detailsoption[0]['delivery_option']=='hours')
		{
		  $duration_time=ceil($tech_detailsoption[0]['delivery_time']/8);
		}
		else
		{
		  $duration_time=$tech_detailsoption[0]['delivery_time'];
		}
		$tech_details=$duration_time.'-'.$tech_detailsoption[0]['delivery_option'];
		echo $tech_details;
		exit;
		}

	}

	/*calculate total turnover for client per year*/
	function clientTurnoverAction()
	{
		$turnoverParams=$this->_request->getParams();
		
			$contract_obj=new Ep_Quote_Quotecontract();
		 	$clientdetails=$contract_obj->clientContractCurrentYear($turnoverParams['year'],$turnoverParams['client_id']);
		 	$turnoverval=explode('.',$clientdetails[0]['ca_year']);
		 	if($turnoverval[0])	 	$this->quote_creation->create_step1['turnover']=$turnoverval[0];
		 	else $this->quote_creation->create_step1['turnover']=0;

		 	if($turnoverval[1])	$this->quote_creation->create_step1['turnover1']=$turnoverval[1];
		 	else $this->quote_creation->create_step1['turnover1']=.0;

		 	if($clientdetails[0]['sales_suggested_currency'])
		 	$this->quote_creation->create_step1['sales_suggested_currency']=$clientdetails[0]['sales_suggested_currency'];
		 	else 
		 	$this->quote_creation->create_step1['sales_suggested_currency']='euro';
		 	//echo "<pre>"; 	print_r($this->quote_creation->create_step1); 	exit;
		 	echo "<div style='width:100%;float:left;font-size: 27px;'>".$this->quote_creation->create_step1['turnover']."<sup>.".$this->quote_creation->create_step1['turnover1']." &".$this->quote_creation->create_step1['sales_suggested_currency'].";</sup></div>";
		 	
	}	 	

	/**Update Theritical price**/

	public function updateTheoricalPriceAction(){

		$updateParams=$this->_request->getParams();
		$showtheorical=$updateParams['showtheorical'];
		$this->quote_creation->create_mission['showtheorical']=$showtheorical;
	}
	/*update client contact session*/
	public function updateContactClientAction(){

		$updateParams=$this->_request->getParams();
		$contact_client=$updateParams['contact_client'];
		$this->quote_creation->create_step1['contact_client']=$contact_client;
	}

	/*update unit price of a mission when edited in session*/
	public function updateUnitPriceAction()
	{

		$updateParams=$this->_request->getParams();
		$unit_price=$updateParams['unit_price'];
		$margin_percentage=$updateParams['margin_percentage'];
		$internal_cost=$updateParams['internal_cost'];
		$mindex=$updateParams['index'];
		$selected_mission=$updateParams['selected_mission'];
		$showtheorical=$updateParams['showtheorical'];
		if(is_numeric($mindex))
		{
			//echo $mindex-1; exit;
			if($updateParams['updateby']!='sales')
			{
		$this->quote_creation->create_mission['showtheorical']=$showtheorical;
			$this->quote_creation->create_mission['selected_mission'][$mindex-1]=$selected_mission;
			}
		$this->quote_creation->create_mission['unit_price'][$mindex-1]=$unit_price;
		$this->quote_creation->create_mission['margin_percentage'][$mindex-1]=$margin_percentage;
		$this->quote_creation->create_mission['internal_cost'][$mindex-1]=$internal_cost;
		//echo $this->quote_creation->create_mission['margin_percentage'][$mindex-1]; exit;
		}
		else
		{
		$id=$mindex;
		$this->quote_creation->tech_mission['unit_price'][$id[1]-1]=$unit_price;
		$this->quote_creation->tech_mission['margin_percentage'][$id[1]-1]=$margin_percentage;
		$this->quote_creation->tech_mission['internal_cost'][$id[1]-1]=$internal_cost;
		}
		
		//echo $this->quote_creation->create_mission['internal_cost'][$mindex-1]; exit;
	}
    

	/* missions prices and turnovers*/
	function saveMissionViewAction()
	{
		if($this->_request-> isPost())
		{
			$missionParams=$this->_request->getParams();
			$quoteMission_obj=new Ep_Quote_QuoteMissions();
			$techObj=new Ep_Quote_TechMissions();
			$quote_obj=new Ep_Quote_Quotes();
			$user_type=$this->_view->user_type;
			//echo "<pre>";print_r($missionParams);exit;
			
			if(is_array($this->quote_creation->create_mission['product']) && count($this->quote_creation->create_mission['product'])>0 || is_array($this->quote_creation->tech_mission['product']))
			{
				
				if($this->quote_creation->custom['quote_id'] && ($user_type == 'superadmin'  || $user_type == 'salesmanager' || $user_type == 'salesuser' || $user_type == 'multilingue' || $user_type == 'prodsubmanager' || $user_type == 'prodmanager'))
				{
					if(isset($missionParams['showtheorical']))
					$showtheorical='yes';
					else
					$showtheorical='no';

					$this->quote_creation->create_mission['showtheorical']=$showtheorical;

					$quoteIdentifier=$this->quote_creation->custom['quote_id'];
						if($this->quote_creation->custom['create_new_version']=='yes')
						{
						
							/*inserting quote version*/
							if($quoteIdentifier)
							{
								$qiversion=($this->quote_creation->custom['version']-1);
								$checkquoteVersion=$quote_obj->getQuoteVersionDetails($quoteIdentifier,$qiversion);

		        				if($checkquoteVersion==0)
									$quote_obj->insertQuoteVersion($quoteIdentifier);	
							}
						
						}
					$this->quote_creation->create_mission['final_turnover']=$missionParams['total_suggested_price'];
					$quotes_data['final_turnover']=$this->quote_creation->create_mission['final_turnover'];
					$quotes_data['showtheorical']=$this->quote_creation->create_mission['showtheorical'];
					//client contact selected in header
					$quotes_data['contact_client']=$this->quote_creation->create_step1['contact_client'];
					$quotes_data['updated_at']=date("Y-m-d H:i:s");
					//echo "<pre>"; print_r($quotes_data); exit;
					$quote_obj->updateQuote($quotes_data,$quoteIdentifier);
				}
				
				if(count($this->quote_creation->create_mission['product'])>0 && ($user_type == 'superadmin'  || $user_type == 'salesmanager' || $user_type == 'salesuser' || $user_type == 'multilingue' || $user_type == 'prodsubmanager' || $user_type == 'prodmanager') )
				{	
					$i=0;
					foreach($this->quote_creation->create_mission['product'] as $mission)
					{
						$prodid= array();
						$pindex=$this->quote_creation->create_mission['mission_identifier'][$i];
						if($showtheorical=='yes')
							$this->quote_creation->create_mission['selected_mission'][$i]=$missionParams['overview_missions_'.$pindex];
						else
							$this->quote_creation->create_mission['selected_mission'][$i]=$missionParams['roverview_missions_'.$pindex];
						
						$this->quote_creation->create_mission['unit_price'][$i]=$missionParams['unit_price_'.$pindex];
						$this->quote_creation->create_mission['internal_cost'][$i]=$missionParams['internal_cost_'.$pindex];
						$this->quote_creation->create_mission['margin_percentage'][$i]=$missionParams['margin_percentage_'.$pindex];
						$this->quote_creation->create_mission['turnover'][$i]=$this->quote_creation->create_mission['unit_price'][$i]*$this->quote_creation->create_mission['volume'][$i];

						$quoteMissionId=$this->quote_creation->create_mission['mission_identifier'][$i];
							/*updating Quote mission*/
							if($quoteMissionId)
							{
								
								/*Quote Mission Version*/
								if($this->quote_creation->custom['create_new_version']=='yes' && $this->quote_creation->custom['quote_id'] )
								{
									$qversion=($this->quote_creation->custom['version']-1);
									$checkexsist=$quoteMission_obj->getMissionVersionDetails($quoteMissionId,$qversion);
									if($checkexsist==0)
									{
										$this->quotesMissionVersion($quoteMissionId,'quotemission');
										$quoteMission_data['version']=$this->quote_creation->custom['version'];
									}
								}
								$quoteMission_data['unit_price']=$this->quote_creation->create_mission['unit_price'][$i];
								$quoteMission_data['margin_percentage']=$this->quote_creation->create_mission['margin_percentage'][$i];
								$quoteMission_data['internal_cost']=$this->quote_creation->create_mission['internal_cost'][$i];
								$quoteMission_data['sales_suggested_missions']=$this->quote_creation->create_mission['selected_mission'][$i];

								/* updating Package from previous mission*/
								if($this->quote_creation->create_mission['product'][$i]=='translation')
									$staff_language=$this->quote_creation->create_mission['languagedest'][$i];
								else
									$staff_language=$this->quote_creation->create_mission['language'][$i];

								if($quoteMission_data['sales_suggested_missions'])
								{
									$Missionid['mission_id']=$quoteMission_data['sales_suggested_missions'];
									$missonDetails=$quoteMission_obj->getMissionDetails($Missionid);
									if($missonDetails[0]['package'])
										$this->quote_creation->create_mission['package'][$i]=$missonDetails[0]['package'];
									else
										$this->quote_creation->create_mission['package'][$i]='lead';
																		
								}
								else
								{
									$this->quote_creation->create_mission['package'][$i]='lead';
									
								}
								$quoteMission_data['staff_time']=$this->packageStaffConfig[$staff_language][$this->quote_creation->create_mission['package'][$i]];
								if(!$quoteMission_data['staff_time'])
								{
									$quoteMission_data['staff_time']=$this->packageStaffConfig[$staff_language]['lead'];
								}
								
								$quoteMission_data['staff_time_option']='days';	
								$quoteMission_data['package']=$this->quote_creation->create_mission['package'][$i];
								
								$this->quote_creation->create_mission['staff_time'][$i]=$quoteMission_data['staff_time'];
								$this->quote_creation->create_mission['staff_time_option'][$i]=$quoteMission_data['staff_time_option'];

								$quoteMission_data['updated_at']=date("Y-m-d H:i:s");	
								//$quoteMission_obj->updateQuoteMission($quoteMission_data,$quoteMissionId);								
								

								if($quoteMission_data['sales_suggested_missions'] && $quoteMissionId)
								{
									//echo $i;
									$prod_mission_obj=new Ep_Quote_ProdMissions();
									$searchexsistParameters['quote_mission_id']=$quoteMissionId;
									$prod_detailsexsist=$prod_mission_obj->getProdMissionDetails($searchexsistParameters);
									if(count($prod_detailsexsist)>0)
								    {
					    				foreach($prod_detailsexsist as $prodexsistval)
					    				{				    					
					    						$prodid[]=$prodexsistval['identifier'];		
					    				}
								    				
								    }
													
										$searchParameters['quote_mission_id']=$quoteMission_data['sales_suggested_missions'];
										$prod_details=$prod_mission_obj->getProdMissionDetails($searchParameters);
										
										/*getting Prod mission details from FR*/
										if(count($prod_details)==0 && !is_array($prod_details))
										{
											/*build service link to get prod mission prices from FR*/
											$service_link=$this->web_service_prod_details_link;
											$Parameters['mission_id']=$quoteMission_data['sales_suggested_missions'];
											//get details from FR with service link
											$prod_details=$this->webHttpClient($service_link,$Parameters);
																
											//updating suggestion in Quote mission table
											$quoteMission_data['suggested_mission_from']='fr';
										}
										
										
									if(count($prod_details)>0 && is_array($prod_details))
									{	
										$j=0;
										foreach($prod_details as $prodall_details)
										{
											$prod_mission_data['quote_mission_id']=$quoteMissionId;
							    			$prod_mission_data['product']=$prodall_details['product'];						    			
							    			$prod_mission_data['delivery_time']=$prodall_details['delivery_time'];
							    			$prod_mission_data['delivery_option']=$prodall_details['delivery_option'];
							    			if($prod_mission_data['product']=='proofreading')
							    			{
							    				$prod_mission_data['staff']=($this->configval['oneshot_max_writers']/5);
							    			}
							    			else
							    			{
							    				$prod_mission_data['staff']=$this->configval['oneshot_max_writers'];	
							    			}
							    			
							    			$prod_mission_data['staff_time']=$this->quote_creation->create_mission['staff_time'][$i];
							    			$prod_mission_data['staff_time_option']=$this->quote_creation->create_mission['staff_time_option'][$i];
							    			$prod_mission_data['cost']=$prodall_details['cost'];
							    			$prod_mission_data['currency']=$prodall_details['currency'];
							    			$prod_mission_data['created_by']=$this->adminLogin->userId;
							    			$prod_mission_data['version']	= $this->quote_creation->custom['version'];

							    			//new fields added
							    			$prod_mission_data['delivery_volume']=$prodall_details['delivery_volume'];
							    			$prod_mission_data['delivery_volume_option']=$prodall_details['delivery_volume_option'];
							    			$prod_mission_data['delivery_volume_time']=$prodall_details['delivery_volume_time'];
							    			$prod_mission_data['delivery_volume_time_option']=$prodall_details['delivery_volume_time_option'];
											
												if(count($prod_detailsexsist)>0)
								    			{
								    				$prod_mission_id=$prodid[$j];
								    			}
								    			if($prod_mission_id)
								    			{
								    				$prod_mission_data['updated_at']=date('Y-m-d H:i:s');
								    				$prod_mission_obj->updateProdMission($prod_mission_data,$prod_mission_id);
								    			}
								    			else
								    			{												
								    				$prod_mission_obj->insertProdMission($prod_mission_data);	
								    			}
							    			
							    			$j++;
								    	}
						    		}
						    		else 
						    		{

						    			//echo $this->quote_creation->create_mission['identifier'][$i];
						    			if(count($prodid)==0 && $quoteMissionId)
						    			{
						    				$pz=0;
						    				for($pz=1;$pz<=2;$pz++)
											{
												$prod_insert_data['quote_mission_id']=$quoteMissionId;
							    					
												if($pz==1)
												$prod_insert_data['product']='redaction';
												else
												$prod_insert_data['product']='proofreading';

												$prod_insert_data['currency']=$this->quote_creation->create_step1['currency'];
												$prod_insert_data['created_by']=$this->adminLogin->userId;

												$prod_mission_obj->insertProdMission($prod_insert_data);	
												
											}
						    			}

						    		}
								}
								elseif($quoteMissionId && ($this->quote_creation->create_mission['product'][$i]=='redaction' || $this->quote_creation->create_mission['product'][$i]=='translation'))
								{
									$prod_mission_obj=new Ep_Quote_ProdMissions();
										$searchexsistParameters['quote_mission_id']=$quoteMissionId;
										$prod_detailsexsist=$prod_mission_obj->getProdMissionDetails($searchexsistParameters);
										if(count($prod_detailsexsist)>0)
									    {
						    				foreach($prod_detailsexsist as $prodexsistval)
						    				{				    					
						    						$prodid[]=$prodexsistval['identifier'];		
						    				}
									    				
									    }
									if(count($prodid)==0 && $quoteMissionId)
						    			{
						    				$pz=0;

						    				for($pz=1;$pz<=2;$pz++)
											{
												$prod_insert_data['quote_mission_id']=$quoteMissionId;
							    					
												if($pz==1)
												$prod_insert_data['product']='redaction';
												else
												$prod_insert_data['product']='proofreading';

												$prod_insert_data['currency']=$this->quote_creation->create_step1['currency'];

												$prod_insert_data['created_by']=$this->adminLogin->userId;
												
												$prod_mission_obj->insertProdMission($prod_insert_data);	
												
											}
						    			}
								}
								
								$quoteMission_obj->updateQuoteMission($quoteMission_data,$quoteMissionId);
								
							}
							$i++;

						}	
					}
				//exit;
				/*tech mission details*/
				if(is_array($this->quote_creation->tech_mission['product']) && ($user_type == 'superadmin'  || $user_type == 'salesmanager' || $user_type == 'salesuser' || $user_type == 'techmanager' || $user_type == 'techuser') )
				{
					$t=0;
					foreach($this->quote_creation->tech_mission['product'] as $tmission)
					{
					$techMissionId=$this->quote_creation->tech_mission['mission_identifier'][$t];
					$this->quote_creation->tech_mission['unit_price'][$t]=$missionParams['unit_price_'.$techMissionId];
					$this->quote_creation->tech_mission['internal_cost'][$t]=$missionParams['internal_cost_'.$techMissionId];
					$this->quote_creation->tech_mission['margin_percentage'][$t]=$missionParams['margin_percentage_'.$techMissionId];

					$this->quote_creation->tech_mission['turnover'][$t]=$this->quote_creation->tech_mission['unit_price'][$t]*$this->quote_creation->tech_mission['volume'][$i];
					
					if($techMissionId)
					{
						if($this->quote_creation->custom['create_new_version']=='yes' && $this->quote_creation->custom['quote_id'] )
						{
							$tversion=$this->quote_creation->custom['version']-1;
							$checktechmission=$techObj->getQuoteVersionDetails($quoteIdentifier,$tversion);
							if($checktechmission==0)
								$this->quotesMissionVersion($techMissionId,'techmission');

							$techMission_data['version']=$this->quote_creation->custom['version'];
						}

						$techMission_data['unit_price']=$this->quote_creation->tech_mission['unit_price'][$t];
						$techMission_data['margin_percentage']=$this->quote_creation->tech_mission['margin_percentage'][$t];
						$techMission_data['cost']=$this->quote_creation->tech_mission['internal_cost'][$t];
						$techMission_data['updated_at']=date("Y-m-d H:i:s");
						$techObj->updateTechMission($techMission_data,$techMissionId);
					}
					$t++;
					}
				}
			}
			//echo "<pre>";print_r($this->quote_creation->create_mission);exit;
			$this->_redirect("/quote-new/sales-final-validation?qaction=briefing&quote_id=".$this->quote_creation->custom['quote_id']);	
			
		}
		else{
			$this->_redirect("/quote-new/create-quote-mission-view");
		}
	}

	/* sales final validation */
	public function salesFinalValidationAction()
	{
		//echo "<pre>";	print_r($this->quote_creation->create_mission); exit;

		$mission_params=$this->_request->getParams();
		if($mission_params['quote_id'])
		{
				//$this->_redirect("/quote-new/client-brief?qaction=briefing&quote_id=".$mission_params['quote_id']."");
				$this->autoQuoteSession($mission_params['quote_id'],'briefing');
		}

		
		//echo "test";print_r($this->quote_creation->tech_mission); exit;
		if(is_array($this->quote_creation->create_mission['product']) && count($this->quote_creation->create_mission['product'])>0 || count($this->quote_creation->tech_mission['product'])>0)
		{
				$i=0;
				$total_turnover=0;
			foreach($this->quote_creation->create_mission['product'] as $mission)
			{
				/*Added w.r.t Autre mission*/
				if($this->quote_creation->create_mission['product'][$i]=='autre')
				{
					$this->quote_creation->create_mission['language'][$i]='fr';
					$this->quote_creation->create_mission['volume'][$i]=1;
					$this->quote_creation->create_mission['nb_words'][$i]=1;
					$this->quote_creation->create_mission['producttype'][$i]='autre';
				}


				$quote_missions[$i]['product']=$this->quote_creation->create_mission['product'][$i];
				$quote_missions[$i]['product_name']=$this->product_array[$this->quote_creation->create_mission['product'][$i]];
				$quote_missions[$i]['language']=$this->quote_creation->create_mission['language'][$i];
				$quote_missions[$i]['language_name']=$this->getLanguageName($this->quote_creation->create_mission['language'][$i]);
				$quote_missions[$i]['languagedest']=$this->quote_creation->create_mission['languagedest'][$i];
				$quote_missions[$i]['languagedest_name']=$this->getLanguageName($this->quote_creation->create_mission['languagedest'][$i]);

				$quote_missions[$i]['producttype']=$this->quote_creation->create_mission['producttype'][$i];
				$quote_missions[$i]['producttypeother']=$this->quote_creation->create_mission['producttypeother'][$i];
				if($quote_missions[$i]['product']!='content_strategy')
				$quote_missions[$i]['producttype_name']=$this->producttype_array[$this->quote_creation->create_mission['producttype'][$i]];
				else
				$quote_missions[$i]['producttype_name']=$this->seo_producttype_array[$this->quote_creation->create_mission['producttype'][$i]];	
				$quote_missions[$i]['nb_words']=$this->quote_creation->create_mission['nb_words'][$i];
				$quote_missions[$i]['volume']=$this->quote_creation->create_mission['volume'][$i];
				
				/*added w.r.t Tempo*/
				$quote_missions[$i]['volume_max']=$this->quote_creation->create_mission['volume_max'][$i];

				/*adding staff mission in prodmission*/
				if($this->quote_creation->create_mission['staff_time'][$i]!=0 && $quote_missions[$i]['product']!='content_strategy' && $this->quote_creation->create_mission['oneshot'][$i]=='yes')
				{
					$quote_missions[$i]['mission_staff']=$this->quote_creation->create_mission['mission_length'][$i]+$this->quote_creation->create_mission['staff_time'][$i];
					$quote_missions[$i]['mission_length']=$this->quote_creation->create_mission['mission_length'][$i];
					$quote_missions[$i]['mission_length_option']=$this->quote_creation->create_mission['mission_length_option'][$i];
				}
				else
				{
					$quote_missions[$i]['mission_staff']=$this->quote_creation->create_mission['mission_length'][$i];
					$quote_missions[$i]['mission_length']=$this->quote_creation->create_mission['mission_length'][$i];
					$quote_missions[$i]['mission_length_option']=$this->quote_creation->create_mission['mission_length_option'][$i];	
				}
				/*define total mission length array*/
				$mission_length_array[]=$quote_missions[$i]['mission_staff'];

				$quote_missions[$i]['delivery_volume_option']=$this->quote_creation->create_mission['delivery_volume_option'][$i];
				$quote_missions[$i]['delivery_volume_option_text']=$this->volume_option_array[$quote_missions[$i]['delivery_volume_option']];
				$quote_missions[$i]['tempo_type']=$this->quote_creation->create_mission['tempo_type'][$i];
				$quote_missions[$i]['tempo_type_text']=$this->tempo_array[$quote_missions[$i]['tempo_type']];
				$quote_missions[$i]['tempo_length']=$this->quote_creation->create_mission['tempo_length'][$i];
				$quote_missions[$i]['tempo_length_option']=$this->quote_creation->create_mission['tempo_length_option'][$i];
				$quote_missions[$i]['tempo_length_option_text']=$this->duration_array[$quote_missions[$i]['tempo_length_option']];
				$quote_missions[$i]['oneshot']=$this->quote_creation->create_mission['oneshot'][$i];
				$quote_missions[$i]['demande_client']=$this->quote_creation->create_mission['demande_client'][$i];
				$quote_missions[$i]['duration_dont_know']=$this->quote_creation->create_mission['duration_dont_know'][$i];
								//flag retrive
				$quote_missions[$i]['producttypeautre']=$this->quote_creation->create_mission['producttypeautre'][$i];
				
				if($this->quote_creation->create_mission['free_mission'][$i]){
				$quote_missions[$i]['free_mission']=$this->quote_creation->create_mission['free_mission'][$i];	
				}

				if($this->quote_creation->create_mission['package'][$i]){
				$quote_missions[$i]['package']=$this->quote_creation->create_mission['package'][$i];	
				}
				
				$quote_missions[$i]['comments']=$this->quote_creation->create_mission['comments'][$i];

				$quote_missions[$i]['identifier']=$this->quote_creation->create_mission['mission_identifier'][$i];				

				/*newly added*/
				$quote_missions[$i]['unit_price']=$this->quote_creation->create_mission['unit_price'][$i];
					if(!$quote_missions[$i]['unit_price'])$quote_missions[$i]['unit_price']=0;
				
				$quote_missions[$i]['margin_percentage']=$this->quote_creation->create_mission['margin_percentage'][$i];
					if(!$quote_missions[$i]['margin_percentage'])$quote_missions[$i]['margin_percentage']=0;
					
				$quote_missions[$i]['internal_cost']=$this->quote_creation->create_mission['internal_cost'][$i];
					if(!$quote_missions[$i]['internal_cost'])$quote_missions[$i]['internal_cost']=0;	
				$quote_missions[$i]['turnover']=$quote_missions[$i]['unit_price']*$quote_missions[$i]['volume'];
				if($this->quote_creation->create_mission['team_fee'][$i]){
				$quote_missions[$i]['team_fee']=$this->quote_creation->create_mission['team_fee'][$i];
				}
				else $quote_missions[$i]['team_fee']=350.00;
				if($this->quote_creation->create_mission['user_fee'][$i]){
				$quote_missions[$i]['user_fee']=$this->quote_creation->create_mission['user_fee'][$i];
				}
				else $quote_missions[$i]['user_fee']=350.00;
				$quote_missions[$i]['selected_mission']=$this->quote_creation->create_mission['selected_mission'][$i];

				/*send button set flag*/
				//echo $quote_missions[$i]['internal_cost'].'test';
				if($quote_missions[$i]['product']!='content_strategy' && $quote_missions[$i]['internal_cost']==0 && $quote_missions[$i]['product_name'])
				{
					$this->_view->sent_button='hide';
				}
				//versioning
							//mission versionings if version is gt 1
							if($this->quote_creation->custom['version']>1)
							{
								$previousVersion=($this->quote_creation->custom['version']-1);

								$quoteMissionObj=new Ep_Quote_QuoteMissions();
								$previousMissionDetails=$quoteMissionObj->getMissionVersionDetails($this->quote_creation->create_mission['mission_identifier'][$i],$previousVersion);
								//echo "<pre>";print_r($previousMissionDetails);exit;
								if($previousMissionDetails)
								{
									foreach($previousMissionDetails as $key=>$vmission)
									{
										$previousMissionDetails[$key]['product_name']=$this->seo_product_array[$vmission['product']];			
										$previousMissionDetails[$key]['language_source_name']=$this->getLanguageName($vmission['language_source']);
										$previousMissionDetails[$key]['product_type_name']=$this->producttype_array[$vmission['product_type']];
										if($vmission['language_dest'])
											$previousMissionDetails[$key]['language_dest_name']=$this->getLanguageName($vmission['language_dest']);

									}	

									//Get All version details of a mission									
									$allVersionMissionDetails=$quoteMissionObj->getMissionVersionDetails($this->quote_creation->create_mission['mission_identifier'][$i]);
									if($allVersionMissionDetails)
									{
										$table_start='<table class="table quote-history table-striped">';
										$table_end='</table>';
										$language_versions=$product_type_versions=$volume_versions=$nb_words_versions='';
										$margin_versions=$internal_cost_versions=$turnover_versions=$price_versions=$mission_length_versions='';

										foreach($allVersionMissionDetails as $versions)
										{
										 	if($versions['product']=='translation')
										  		$language= $this->getLanguageName($versions['language_source'])." > ".$this->getLanguageName($vmission['language_dest']);
										  	else
										  		$language= $this->getLanguageName($versions['language_source']);
										  	
										  	$created_at=date("d/m/Y", strtotime($versions['created_at']));

										  	$version_text='v'.$versions['version'];

										  	$language_versions.="<tr><td>$language</td><td>$created_at</td><td>$version_text</td></tr>";
										  	$product_type_versions.="<tr><td>".$this->producttype_array[$versions['product_type']]."</td><td>$created_at</td><td>$version_text</td></tr>";
										  	$volume_versions.="<tr><td>".$versions['volume']."</td><td>$created_at</td><td>$version_text</td></tr>";
										  	$nb_words_versions.="<tr><td>".$versions['nb_words']."</td><td>$created_at</td><td>$version_text</td></tr>";
										  	$price_versions.="<tr><td>".zero_cut($versions['unit_price'],2)." &". $versions['sales_suggested_currency'].";</td><td>$created_at</td><td>$version_text</td></tr>";

										  	$mission_length_option=$this->duration_array[$versions['mission_length_option']];//$versions['mission_length_option']=='days' ? ' Jours' : ' Hours';

										  	$mission_length_versions.="<tr><td>".$versions['mission_length']." $mission_length_option</td><td>$created_at</td><td>$version_text</td></tr>";

										  	$turnover_versions.="<tr><td>".zero_cut($versions['turnover'],2)." &". $versions['sales_suggested_currency'].";</td><td>$created_at</td><td>$version_text</td></tr>";

										  	$internal_cost_versions.="<tr><td>".zero_cut($versions['internal_cost'],2)." &". $versions['sales_suggested_currency'].";</td><td>$created_at</td><td>$version_text</td></tr>";
										  	
										  	$margin_versions.="<tr><td>".$versions['margin_percentage']."</td><td>$created_at</td><td>$version_text</td></tr>";
										}										
									}


									//checking the version differences
									if($quote_missions[$i]['language_source'] !=$previousMissionDetails[0]['language_source'])
									{
										$quote_missions[$i]['language_difference']='yes';
										$quote_missions[$i]['language_versions']=$table_start.$language_versions.$table_end;
									}

									if($quote_missions[$i]['language_dest'] !=$previousMissionDetails[0]['language_dest'])
									{
										$missonDetails[$m]['language_difference']='yes';
										$missonDetails[$m]['language_versions']=$table_start.$language_versions.$table_end;
									}

									if($quote_missions[$i]['product_type'] !=$previousMissionDetails[0]['product_type'])
									{
										$quote_missions[$i]['product_type_difference']='yes';
										$quote_missions[$i]['product_type_versions']=$table_start.$product_type_versions.$table_end;
									
									}

									if($quote_missions[$i]['turnover'] !=$previousMissionDetails[0]['turnover'])
									{
										$quote_missions[$i]['turnover_difference']='yes';
										$quote_missions[$i]['turnover_versions']=$table_start.$turnover_versions.$table_end;
									}	

									$current_internal_cost=number_format($missonDetails[$m]['internal_cost'],2);
									$prev_internal_cost=number_format($previousMissionDetails[0]['internal_cost'],2);

									if($current_internal_cost != $prev_internal_cost)
									{
										//echo $current_internal_cost."---".$prev_internal_cost."<br>";
										$quote_missions[$i]['internal_cost_difference']='yes';
										$quote_missions[$i]['internal_cost_versions']=$table_start.$internal_cost_versions.$table_end;
									}	

									if($quote_missions[$i]['margin_percentage'] !=$previousMissionDetails[0]['margin_percentage'])
									{
										$quote_missions[$i]['margin_difference']='yes';
										$quote_missions[$i]['margin_versions']=$table_start.$margin_versions.$table_end;
									}

									if($quote_missions[$i]['volume'] !=$previousMissionDetails[0]['volume'])
									{
										$quote_missions[$i]['volume_difference']='yes';
										$quote_missions[$i]['volume_versions']=$table_start.$volume_versions.$table_end;
									}
									
									if($quote_missions[$i]['nb_words'] !=$previousMissionDetails[0]['nb_words'])
									{
										$quote_missions[$i]['nb_words_difference']='yes';
										$quote_missions[$i]['nb_words_versions']=$table_start.$nb_words_versions.$table_end;
									}
									
									//echo $missonDetails[$m]['unit_price']."--".$previousMissionDetails[0]['unit_price']."<br>";
									if($quote_missions[$i]['unit_price'] !=$previousMissionDetails[0]['unit_price'])
									{
										$quote_missions[$i]['unit_price_difference']='yes';
										$quote_missions[$i]['price_versions']=$table_start.$price_versions.$table_end;
									}

									$current_mission_lenght=$quote_missions[$i]['mission_length_option']=='hours' ? ($$quote_missions[$i]['mission_length']/24) : $missonDetails[$m]['mission_length'];
									$previous_mission_lenght=$previousMissionDetails[0]['mission_length_option']=='hours' ? ($previousMissionDetails[0]['mission_length']/24) : $previousMissionDetails[0]['mission_length'];
									//echo $current_mission_lenght."--".$previous_mission_lenght."<br>";
									if($current_mission_lenght !=$previous_mission_lenght)
									{
										$quote_missions[$i]['mission_length_difference']='yes';	
										$quote_missions[$i]['mission_length_versions']=$table_start.$mission_length_versions.$table_end;
									}

									$quote_missions[$i]['previousMissionDetails']=$previousMissionDetails;
								}	

						}

				$i++;
			}
			//exit;
			/*Tech Missoin */	
			if(count($this->quote_creation->tech_mission['product'])>0)
			{
				//echo "<pre>";print_r($this->quote_creation->tech_mission); exit;
				$t=0;
				foreach($this->quote_creation->tech_mission['product'] as $tmission)
				{
					$quote_missions['tech'][$t]['volume']=$this->quote_creation->tech_mission['tech_missions'][$t]['volume'];
					$quote_missions['tech'][$t]['product']=$this->quote_creation->tech_mission['product'][$t];
					$quote_missions['tech'][$t]['language']=$this->quote_creation->tech_mission['language'][$t];
					$quote_missions['tech'][$t]['language_name']=$this->getLanguageName($this->quote_creation->tech_mission['language'][$t]);

					/*define before prod tech mission*/
					if($this->quote_creation->tech_mission['to_perform'][$t]!='During')
								$prior_length_array[]=$this->quote_creation->tech_mission['tech_mission_length'][$t];

					$quote_missions['tech'][$t]['tech_mission_length']=$this->quote_creation->tech_mission['tech_mission_length'][$t];
					$quote_missions['tech'][$t]['tech_mission_length_option']=$this->quote_creation->tech_mission['tech_mission_length_option'][$t];
					$quote_missions['tech'][$t]['volume']=$this->quote_creation->tech_mission['volume'][$t];
					$quote_missions['tech'][$t]['tech_type']=$this->quote_creation->tech_mission['tech_type'][$t];
					$quote_missions['tech'][$t]['margin_percentage']=$this->quote_creation->tech_mission['margin_percentage'][$t];
					$quote_missions['tech'][$t]['internal_cost']=$this->quote_creation->tech_mission['internal_cost'][$t];
					$quote_missions['tech'][$t]['unit_price']=$this->quote_creation->tech_mission['unit_price'][$t];
					if($this->quote_creation->tech_mission['free_mission'][$t]){
					$quote_missions['tech'][$t]['free_mission']=$this->quote_creation->tech_mission['free_mission'][$t];	
					}
					
					$quote_missions['tech']['linked_to_prod'][$t]=$this->quote_creation->tech_mission['linked_to_prod'][$t];	
					$quote_missions['tech'][$t]['to_perform']=$this->quote_creation->tech_mission['to_perform'][$t];	
					
					if($this->quote_creation->tech_mission['package'][$t]){
					$quote_missions['tech'][$t]['package']=$this->quote_creation->tech_mission['package'][$t];	
					}

					/*tech tempo*/
					$quote_missions['tech'][$t]['identifier']=$this->quote_creation->tech_mission['mission_identifier'][$t];
					$quote_missions['tech'][$t]['volume_max']=$this->quote_creation->tech_mission['volume_max'][$t];
					$quote_missions['tech'][$t]['delivery_volume_option']=$this->quote_creation->tech_mission['delivery_volume_option'][$t];
					$quote_missions['tech'][$t]['tempo_type']=$this->quote_creation->tech_mission['tempo_type'][$t];
					$quote_missions['tech'][$t]['tempo_length']=$this->quote_creation->tech_mission['tempo_length'][$t];
					$quote_missions['tech'][$t]['tempo_length_option']=$this->quote_creation->tech_mission['tempo_length_option'][$t];
					$quote_missions['tech'][$t]['tech_oneshot']=$this->quote_creation->tech_mission['tech_oneshot'][$t];

					$quote_missions['tech'][$t]['turnover']=$quote_missions['tech'][$t]['unit_price']*$quote_missions['tech'][$t]['volume'];
					$quote_missions['tech'][$t]['team_fee']=350.00;
					$quote_missions['tech'][$t]['user_fee']=350.00;
					$quote_missions['tech'][$t]['required_writer']=1;

					
						//tech mission versionings if version is gt 1
							if($this->quote_creation->custom['version']>1)
							{
								$previousVersion=($this->quote_creation->custom['version']-1);

								$techMissionObj=new Ep_Quote_TechMissions();
								$previousMissionDetails=$techMissionObj->getMissionVersionDetails($quote_missions['tech'][$t]['identifier'],$this->quote_creation->custom['quote_id'],$previousVersion);
								
								if($previousMissionDetails)
								{						
									//Get All version details of a mission									
									$allVersionMissionDetails=$techMissionObj->getMissionVersionDetails($quote_missions['tech'][$t]['identifier'],$this->quote_creation->custom['quote_id']);
									if($allVersionMissionDetails)
									{
										$table_start='<table class="table quote-history table-striped" style="color:#34495e">';
										$table_end='</table>';								
										$price_versions=$mission_length_versions='';
										$title_versions='';

										foreach($allVersionMissionDetails as $versions)
										{
										 	
										  	
										  	$created_at=date("d/m/Y", strtotime($versions['created_at']));

										  	$version_text='v'.$versions['version'];
										  	
										  	$title_versions.="<tr><td>".$versions['title']."</td><td>$created_at</td><td>$version_text</td></tr>";

										  	$price_versions.="<tr><td>".zero_cut($versions['cost'],2)." &". $versions['currency'].";</td><td>$created_at</td><td>$version_text</td></tr>";

										  	$mission_length_option=$versions['delivery_option']=='days' ? ' Jours' : ' Hours';

										  	$mission_length_versions.="<tr><td>".$versions['delivery_time']." $mission_length_option</td><td>$created_at</td><td>$version_text</td></tr>";
										}										
									}


									//checking the version differences
									if($quote_missions['tech'][$t]['tech_type'] !=$previousMissionDetails[0]['title'])
									{
										$quote_missions['tech'][$t]['title_difference']='yes';
										$quote_missions['tech'][$t]['title_versions']=$table_start.$title_versions.$table_end;
									}


									if($quote_missions['tech'][$t]['cost'] !=$previousMissionDetails[0]['cost'])
									{
										$quote_missions['tech'][$t]['cost_difference']='yes';
										$quote_missions['tech'][$t]['price_versions']=$table_start.$price_versions.$table_end;
									}

									$current_mission_lenght=$quote_missions['tech'][$t]['delivery_option']=='hours' ? ($quote_missions['tech'][$t]['delivery_time']/24) : $quote_missions['tech'][$t]['delivery_time'];
									$previous_mission_lenght=$previousMissionDetails[0]['delivery_option']=='hours' ? ($previousMissionDetails[0]['delivery_time']/24) : $previousMissionDetails[0]['delivery_time'];
									if($current_mission_lenght !=$previous_mission_lenght)
									{
										$quote_missions['tech'][$t]['mission_length_difference']='yes';	
										$quote_missions['tech'][$t]['mission_length_versions']=$table_start.$mission_length_versions.$table_end;
									}



									$quote_missions['tech'][$t]['previousMissionDetails']=$previousMissionDetails;
								}	

							}


					$t++;
				}
				
				//$this->_view->tech_missions=$tech_missions;
			}
				//echo "<pre>"; print_r($quote_missions); exit;
				/*if($this->quote_creation->create_mission['final_mission_length'])
				{
					$quotesDetails['final_mission_length']=$this->quote_creation->create_mission['final_mission_length'];	
				}
				else
				{*/
					if(count($prior_length_array)>0)
						$quotesDetails['final_mission_length']=max($mission_length_array)+max($prior_length_array);
					else
						$quotesDetails['final_mission_length']=max($mission_length_array);
				//}
				$quotesDetails['quote_id']=$this->quote_creation->custom['quote_id'];

				$quotesDetails['currency']=$this->quote_creation->create_step1['currency'];
				$quotesDetails['final_turnover']=$this->quote_creation->create_mission['final_turnover'];
				$quotesDetails['final_margin']=$this->quote_creation->create_mission['final_margin'];
				
				$quotesDetails['prod_extra_launch_days']=$this->quote_creation->create_mission['prod_extra_launch_days'];
				$quotesDetails['final_mission_length_option']=$this->quote_creation->create_mission['final_mission_length_option'];
				$quotesDetails['estimate_sign_percentage']=$this->quote_creation->send_quote['estimate_sign_percentage'];										
				$quotesDetails['estimate_sign_date']=$this->quote_creation->create_mission['estimate_sign_date'];


				$quoteObj=new Ep_Quote_Quotes();

				if($quotesDetails['quote_id'])
				{
					$quotevalidatedDetails=$quoteObj->getQuoteDetails($quotesDetails['quote_id']);
					if(count($quotevalidatedDetails)>0)
					{
						$quotesDetails['sales_review']=$quotevalidatedDetails[0]['sales_review'];
						$quotesDetails['tech_challenge_comments']=$quotevalidatedDetails[0]['tech_challenge_comments'];
						$quotesDetails['seo_comments']=$quotevalidatedDetails[0]['seo_comments'];
						$quotesDetails['prod_challege_comments']=$quotevalidatedDetails[0]['prod_challege_comments']; 
						if($quotevalidatedDetails[0]['seo_review']=='challenged')
						{
							$quotesDetails['send_team_seo']=true;
						}
						if($quotevalidatedDetails[0]['tec_review']=='challenged')
						{
							$quotesDetails['send_team_tech']=true;
						}
						if($quotevalidatedDetails[0]['prod_review']=='challenged')
						{
							$quotesDetails['send_team_prod']=true;
						}
					}
				}
				//Quote versioning	
					if($this->quote_creation->custom['version']>1)
					{
						$previousVersion=($this->quote_creation->custom['version']-1);
						//echo $previousVersion.'test';
						
						$previousQuoteDetails=$quoteObj->getQuoteVersionDetails($this->quote_creation->custom['quote_id'],$previousVersion);
						//echo "<pre>"; print_r($previousQuoteDetails); exit;
						if($previousQuoteDetails)
						{
							//Get All Quote version Details
							$allVersionQuoteDetails=$quoteObj->getQuoteVersionDetails($this->quote_creation->custom['quote_id']);

							if($allVersionQuoteDetails)
							{
								$table_start='<table class="table quote-history table-striped" style="color:#34495e">';
								$table_end='</table>';								
								$final_margin_versions=$final_turnover_versions=$final_mission_length_versions='';

								foreach($allVersionQuoteDetails as $versions)
								{
								 	
								  	$created_at=date("d/m/Y", strtotime($versions['created_at']));

								  	$version_text='v'.$versions['version'];								  	

								  	$mission_length_option=$versions['final_mission_length_option']=='days' ? ' Jours' : ' Hours';

								  	$final_mission_length_versions.="<tr><td style='color:#34495e'>".$versions['final_mission_length']." $mission_length_option</td><td style='color:#34495e'>$created_at</td><td>$version_text</td></tr>";

								  	$final_turnover_versions.="<tr><td style='color:#34495e'>".zero_cut($versions['final_turnover'],2)." &". $versions['sales_suggested_currency'].";</td><td style='color:#34495e'>$created_at</td><td style='color:#34495e'>$version_text</td></tr>";								  	
								  	
								  	$final_margin_versions.="<tr><td style='color:#34495e'>".$versions['final_margin']."</td><td style='color:#34495e'>$created_at</td><td style='color:#34495e'>$version_text</td></tr>";
								}

								//echo $quotesDetails['final_turnover']."--".$previousQuoteDetails[0]['final_turnover'];
								
								if($quotesDetails['final_turnover'] !=$previousQuoteDetails[0]['final_turnover'])
								{
									$quotesDetails['final_turnover_difference']='yes';
									$quotesDetails['final_turnover_versions']=$table_start.$final_turnover_versions.$table_end;
								}


								if($quotesDetails['final_margin'] !=$previousQuoteDetails[0]['final_margin'])
								{
									$quotesDetails['final_margin_difference']='yes';
									$quotesDetails['final_margin_versions']=$table_start.$final_margin_versions.$table_end;
								}

								$current_quote_lenght=$quotesDetails['final_mission_length_option']=='hours' ? ($quotesDetails['final_mission_length']/24) : $quotesDetails['final_mission_length'];
								$previous_quote_lenght=$previousQuoteDetails[0]['final_mission_length_option']=='hours' ? ($previousQuoteDetails[0]['final_mission_length']/24) : $previousQuoteDetails[0]['final_mission_length'];								
								if($current_quote_lenght !=$previous_quote_lenght)
								{
									$quotesDetails['final_mission_length_difference']='yes';	
									$quotesDetails['final_mission_length_versions']=$table_start.$final_mission_length_versions.$table_end;
								}


							}
						}

						//Deleted mission version details
						
						$previousVersion=($this->quote_creation->custom['version']-1);
						$deletedMissionVersions=$this->deletedMissionVersions($this->quote_creation->custom['quote_id'],$previousVersion);
						if($deletedMissionVersions)
							$quotesDetails['deletedMissionVersions']=$deletedMissionVersions;

						
						
					}		
				//echo $this->_view->sent_button.'teer'; exit;
				$this->_view->quotes=$quotesDetails;
				$this->_view->quote_missions=$quote_missions;
				$this->_view->create_step1=$this->quote_creation->create_step1;
		     	$this->render('sales-final-validation-list');
		}
		else
		{
			if(!$this->quote_creation->create_step1['client_id'])
			{
				$this->_redirect("/quote-new/create-step1");
			}
		}
	
	}

	/**save final validation*/
	public function saveFinalValidationAction()
	{
		//echo "<pre> test"; print_r($this->quote_creation->create_mission['producttypeautre']); exit;

		if($this->_request-> isPost()  && $this->adminLogin->userId)            
        {  
        	$user_type=$this->adminLogin->type;

        	$finalParameters=$this->_request->getParams();

        	//echo "<pre>"; print_r($finalParameters); exit;

        	$total_turnover=0;
        	//get all selected missions and added to mission array
			foreach($_POST as $key => $missions)
			{
				
			    if (strpos($key, 'unit_price_') === 0)
			    {
			    	$mission_id=str_replace('unit_price_','',$key);

			    	if(is_numeric($mission_id))
			    	{
			    			/*allow only sales,superadmin and prod*/
			    		if($this->quote_creation->create_mission['product'][$mission_id-1]!='content_strategy' && ($user_type!='techmanager' && $user_type!='techuser' && $user_type!='seomanager' && $user_type!='seouser'))
			    		{
			    			$this->quote_creation->create_mission['unit_price'][$mission_id-1]=currencyToDecimal($finalParameters['unit_price_'.$mission_id]);
							$this->quote_creation->create_mission['margin_percentage'][$mission_id-1]=currencyToDecimal($finalParameters['margin_percentage_'.$mission_id]);
							$this->quote_creation->create_mission['internal_cost'][$mission_id-1]=currencyToDecimal($finalParameters['internal_cost_'.$mission_id]);
							$this->quote_creation->create_mission['cost_diminish'][$mission_id-1]=currencyToDecimal($finalParameters['cost_diminish_'.$mission_id]);
						

							$this->quote_creation->create_mission['package'][$mission_id-1]=($finalParameters['package_'.$mission_id]);

					    	if($this->quote_creation->create_mission['package'][$mission_id-1]=='team')
					    	{
					    		$this->quote_creation->create_mission['team_fee'][$mission_id-1]=currencyToDecimal($finalParameters['team_fee_'.$mission_id]);
					    		$this->quote_creation->create_mission['team_packs'][$mission_id-1]=currencyToDecimal($finalParameters['team_packs_'.$mission_id]);
					    		$this->quote_creation->create_mission['turnover'][$mission_id-1]=currencyToDecimal($finalParameters['turnover_'.$mission_id]);
					    	}

					    	else if($this->quote_creation->create_mission['package'][$mission_id-1]=='user')
					    	{
					    		$this->quote_creation->create_mission['user_fee'][$mission_id-1]=currencyToDecimal($finalParameters['user_fee_'.$mission_id]);
					    		$this->quote_creation->create_mission['turnover'][$mission_id-1]=currencyToDecimal($finalParameters['user_ca_'.$mission_id]);
					    	}
					    	else
					    	{
					    		$this->quote_creation->create_mission['turnover'][$mission_id-1]=currencyToDecimal($finalParameters['turnover_'.$mission_id]);
					    	}		    	
				    	
					    	//free mission  	
					    	if($finalParameters['free_mission_'.$mission_id]=='yes')
					    	{
		                    	$this->quote_creation->create_mission['free_mission'][$mission_id-1]=$finalParameters['free_mission_'.$mission_id];
		                    }
		                    else
		                    {
		                    	$this->quote_creation->create_mission['free_mission'][$mission_id-1]='no';	
		                    }

		                    if($this->quote_creation->create_mission['package'][$mission_id-1]=='team')
					    	{
					    		//echo $mission_update['turnover']+$mission_update['team_fee'];exit;
					    		if($this->quote_creation->create_mission['free_mission'][$mission_id-1]=='yes')
									$team_turnover=0;
								else	
									$team_turnover=currencyToDecimal($finalParameters['team_ca_'.$mission_id]);

					    		
								$total_turnover+=$team_turnover;//$mission_update['turnover']+$mission_update['team_fee'];
					    	}
					    	else
							{
					    		if($this->quote_creation->create_mission['free_mission'][$mission_id-1]=='yes')
									$turnover=0;
								else
									$turnover=$this->quote_creation->create_mission['turnover'][$mission_id-1];
									
								$total_turnover+=$turnover;
							}
			    		}

			    			/*allow only seo manager and seouser, sales,superadmin*/
		    			if($this->quote_creation->create_mission['product'][$mission_id-1]=='content_strategy' && ($user_type!='techmanager' && $user_type!='techuser' && $user_type!='prodmanager' && $user_type!= 'multilingue' && $user_type!= 'prodsubmanager'))
		    			{
			    			$this->quote_creation->create_mission['unit_price'][$mission_id-1]=currencyToDecimal($finalParameters['unit_price_'.$mission_id]);
							$this->quote_creation->create_mission['margin_percentage'][$mission_id-1]=currencyToDecimal($finalParameters['margin_percentage_'.$mission_id]);
							$this->quote_creation->create_mission['internal_cost'][$mission_id-1]=currencyToDecimal($finalParameters['internal_cost_'.$mission_id]);
							$this->quote_creation->create_mission['cost_diminish'][$mission_id-1]=currencyToDecimal($finalParameters['cost_diminish_'.$mission_id]);
						

							$this->quote_creation->create_mission['package'][$mission_id-1]=($finalParameters['package_'.$mission_id]);

					    	if($this->quote_creation->create_mission['package'][$mission_id-1]=='team')
					    	{
					    		$this->quote_creation->create_mission['team_fee'][$mission_id-1]=currencyToDecimal($finalParameters['team_fee_'.$mission_id]);
					    		$this->quote_creation->create_mission['team_packs'][$mission_id-1]=currencyToDecimal($finalParameters['team_packs_'.$mission_id]);
					    		$this->quote_creation->create_mission['turnover'][$mission_id-1]=currencyToDecimal($finalParameters['team_ca_'.$mission_id]);
					    	}

					    	else if($this->quote_creation->create_mission['package'][$mission_id-1]=='user')
					    	{
					    		$this->quote_creation->create_mission['user_fee'][$mission_id-1]=currencyToDecimal($finalParameters['user_fee_'.$mission_id]);
					    		$this->quote_creation->create_mission['turnover'][$mission_id-1]=currencyToDecimal($finalParameters['user_ca_'.$mission_id]);
					    	}
					    	else
					    	{
					    		$this->quote_creation->create_mission['turnover'][$mission_id-1]=currencyToDecimal($finalParameters['turnover_'.$mission_id]);
					    	}		    	
				    	
					    	//free mission  	
					    	if($finalParameters['free_mission_'.$mission_id]=='yes')
					    	{
		                    	$this->quote_creation->create_mission['free_mission'][$mission_id-1]=$finalParameters['free_mission_'.$mission_id];
		                    }
		                    else
		                    {
		                    	$this->quote_creation->create_mission['free_mission'][$mission_id-1]='no';	
		                    }

		                    if($this->quote_creation->create_mission['package'][$mission_id-1]=='team')
					    	{
					    		//echo $mission_update['turnover']+$mission_update['team_fee'];exit;
					    		if($this->quote_creation->create_mission['free_mission'][$mission_id-1]=='yes')
									$team_turnover=0;
								else	
									$team_turnover=currencyToDecimal($finalParameters['team_ca_'.$mission_id]);

					    		
								$total_turnover+=$team_turnover;//$mission_update['turnover']+$mission_update['team_fee'];
					    	}
					    	else
							{
					    		if($this->quote_creation->create_mission['free_mission'][$mission_id-1]=='yes')
									$turnover=0;
								else
									$turnover=$this->quote_creation->create_mission['turnover'][$mission_id-1];
									
								$total_turnover+=$turnover;
							}
						}
				    }

					else
					{
						if($user_type!='seomanager' && $user_type!='seouser' && $user_type!='prodmanager' && $user_type!= 'multilingue' && $user_type!= 'prodsubmanager')
			    		{
							/*tech mission*/
							$tid=(int)$mission_id[1]; 
							
							$this->quote_creation->tech_mission['unit_price'][$tid-1]=currencyToDecimal($finalParameters['unit_price_'.$mission_id]);
							$this->quote_creation->tech_mission['margin_percentage'][$tid-1]=currencyToDecimal($finalParameters['margin_percentage_'.$mission_id]);
							$this->quote_creation->tech_mission['internal_cost'][$tid-1]=currencyToDecimal($finalParameters['internal_cost_'.$mission_id]);
							$this->quote_creation->tech_mission['package'][$tid-1]=($finalParameters['package_'.$mission_id]);

					    	if($this->quote_creation->tech_mission['package'][$tid-1]=='team')
					    	{
					    		$this->quote_creation->tech_mission['team_fee'][$tid-1]=currencyToDecimal($finalParameters['team_fee_'.$mission_id]);
					    		$this->quote_creation->tech_mission['team_packs'][$tid-1]=currencyToDecimal($finalParameters['team_packs_'.$mission_id]);
					    		$this->quote_creation->tech_mission['turnover'][$tid-1]=currencyToDecimal($finalParameters['team_ca_'.$mission_id]);
					    	}

					    	else if($this->quote_creation->tech_mission['package'][$tid-1]=='user')
					    	{
					    		$this->quote_creation->tech_mission['user_fee'][$tid-1]=currencyToDecimal($finalParameters['user_fee_'.$mission_id]);
					    		$this->quote_creation->tech_mission['turnover'][$tid-1]=currencyToDecimal($finalParameters['user_ca_'.$mission_id]);
					    	}
					    	else
					    	{
					    		$this->quote_creation->tech_mission['turnover'][$tid-1]=currencyToDecimal($finalParameters['turnover_'.$mission_id]);
					    	}		    	
					    	
					    	//free mission  	
		                   
		                   if($finalParameters['free_mission_'.$mission_id]=='yes')
					    	{
		                    	$this->quote_creation->tech_mission['free_mission'][$tid-1]=$finalParameters['free_mission_'.$mission_id];
		                    }
		                    else
		                    {
		                    	$this->quote_creation->create_mission['free_mission'][$tid-1]='no';	
		                    } 


		                    if($this->quote_creation->tech_mission['package'][$tid-1]=='team')
					    	{
					    		//echo $mission_update['turnover']+$mission_update['team_fee'];exit;
					    		if($this->quote_creation->create_mission['free_mission'][$tid-1]=='yes')
									$team_turnover=0;
								else	
									$team_turnover=currencyToDecimal($finalParameters['team_ca_'.$mission_id]);

					    		
								$total_turnover+=$team_turnover;//$mission_update['turnover']+$mission_update['team_fee'];
					    	}
					    	else
							{
					    		if($this->quote_creation->create_mission['free_mission'][$mission_id-1]=='yes')
									$turnover=0;
								else
									$turnover=$this->quote_creation->tech_mission['turnover'][$tid-1];
									
								$total_turnover+=$turnover;
							}
						}
					}
			    	
					
				}
			}

			//echo "<pre>"; print_r($this->quote_creation->create_mission); exit;
			
			$this->quote_creation->create_mission['final_turnover']=$total_turnover;
			$this->quote_creation->create_mission['final_margin']=currencyToDecimal($finalParameters['total_margin']);
			$this->quote_creation->create_mission['final_mission_length']=$finalParameters['total_mission_length'];
			$this->quote_creation->create_mission['final_mission_length_option']=$finalParameters['total_time_option'];
			$this->quote_creation->create_mission['sales_review']='validated';
			$this->quote_creation->create_mission['prod_extra_launch_days']=$finalParameters['prod_extra_launch_days'];
			//time to sign the quote
			$this->quote_creation->create_mission['sign_expire_timeline']=time()+(21*24*60*60);
			$this->quote_creation->send_quote['estimate_sign_percentage']=$finalParameters['estimate_sign_percentage'];
			$this->quote_creation->create_mission['estimate_sign_date']=$finalParameters['estimate_sign_date'];
			


			//$this->_redirect('/quote-new/save-send-quote');

			/*insert Quotes Missions Final */


			if($this->quote_creation->send_quote)
			{
				

				$quote_obj=new Ep_Quote_Quotes();	
				$techObj=new Ep_Quote_TechMissions();
	        	//$sendQuoteParameters=$this->_request->getParams();
				/*Update quote Mission*/
	        	

				/*based on the user type update the status*/
					if( ($user_type == 'superadmin'  || $user_type == 'salesmanager' || $user_type == 'salesuser') && ($finalParameters['validate']!='save' && !isset($finalParameters['validate'])))
						{
							$quotes_data['sales_review']=$this->quote_creation->create_mission['sales_review'];	
							$quotes_data['sign_expire_timeline']=$this->quote_creation->create_mission['sign_expire_timeline'];	
						}
						elseif($user_type == 'multilingue' || $user_type == 'prodsubmanager' || $user_type == 'prodmanager')
						{
							$quotes_data['sales_review']='not_done';
							$quotes_data['prod_review']=$this->quote_creation->create_mission['sales_review'];	
							$quotes_data["sales_validation_expires"]=time()+($this->configval['sales_validation_timeline']*60*60);
							$prod_validated=true;
						}
						elseif($user_type == 'seouser' || $user_type == 'seomanager')
						{
							$quotes_data['sales_review']='not_done';
							$quotes_data['seo_review']=$this->quote_creation->create_mission['sales_review'];	
							$seo_validated=true;
						}
						elseif($user_type == 'techuser' || $user_type == 'techmanager')
						{
							$quotes_data['sales_review']='not_done';
							$quotes_data['tec_review']=$this->quote_creation->create_mission['sales_review'];	
							$tec_validated=true;
						}

				
				if($user_type == 'superadmin'  || $user_type == 'salesmanager' || $user_type == 'salesuser')
				{
					$quotes_data['prod_extra_launch_days']=$this->quote_creation->create_mission['prod_extra_launch_days'];
					$quotes_data['final_turnover']=$this->quote_creation->create_mission['final_turnover'];
					$quotes_data['final_margin']=$this->quote_creation->create_mission['final_margin'];
					$quotes_data['final_mission_length']=$this->quote_creation->create_mission['final_mission_length'];

					$quotes_data['estimate_sign_percentage']=$this->quote_creation->send_quote['estimate_sign_percentage'];
					$quotes_data['estimate_sign_date']=$this->quote_creation->create_mission['estimate_sign_date'];
					$quotes_data['estimate_sign_comments']=$this->quote_creation->send_quote['estimate_sign_comments'];
					/**therical/real */
					$quotes_data['showtheorical']=$this->quote_creation->create_mission['showtheorical'];
				}

				//Quote current version
					if($this->quote_creation->custom['create_new_version']=='yes' && $this->quote_creation->custom['quote_id'])
					{
						$version=$this->quote_creation->custom['version'];

						$old_version='v'.($this->quote_creation->custom['version']-1);
						$new_version='v'.($version);
						$old_title=$this->quote_creation->create_step1['quote_title'];

						$this->quote_creation->create_step1['quote_title']=str_replace($old_version, $new_version, $old_title);

			        	$quotes_data['title']=$this->quote_creation->create_step1['quote_title'];

			        	$quoteIdentifier=$this->quote_creation->custom['quote_id'];
			        	
			        	if($quoteIdentifier && $version>1)
			        	{
			        		$version_new=$version-1;
			        		$checkquoteVersion=$quote_obj->getQuoteVersionDetails($quoteIdentifier,$version_new);

			        				if($checkquoteVersion==0)
										$quote_obj->insertQuoteVersion($quoteIdentifier);
						}
					}
					else
					{
						$version=1;
						$quotes_data['title']=$this->quote_creation->create_step1['quote_title'];
					}
					$quotes_data['version']=$version;

					$quotes_data["response_time"]=time()+($this->configval['quote_sent_timeline']*60*60);	
				
				/*Ask to update*/	
				$quoteDetails=$quote_obj->getQuoteDetails($this->quote_creation->custom['quote_id']);
				$shareduser=explode(',',$quoteDetails[0]['brief_email_notify']);
					if($finalParameters['send_team_prod']=='prod')
					{
					
						$quotes_data['prod_review']='challenged';
						$quotes_data["prod_timeline"]=time()+($this->configval['prod_timeline']*60*60);
						$quotes_data['prod_challege_comments']=$finalParameters['quote_updated_comments'];
						$quotes_data['sales_review']='not_done';							

						
						//Insert Quote log
						$log_params['quote_id']	= $this->quote_creation->custom['quote_id'];
						$log_params['bo_user']	= $this->adminLogin->userId;					
						$log_params['version']	= $version;
						$log_params['comments']	=$finalParameters['quote_updated_comments'];
						$log_params['action']= 'sales_prod_challenged';
												
								//$quiteActionId=32;
						$quiteActionId=4;
						$challenge_hours=dateDiffHours(time(),strtotime($quotes_data['prod_timeline']));
						$mail_obj=new Ep_Message_AutoEmails();
							/*Email Notify for */
							$client_obj=new Ep_Quote_Client();
								$email_users=$client_obj->getEPContacts('"prodmanager","prodsubmanager"');

									if(count($email_users)>0)
									{
										//echo "<pre>"; print_r($shareduser);
										foreach($email_users as $user=>$name)
										{
											if(!in_array($user,$shareduser))
											{
											    array_push($shareduser,$user);
											}
											$mail_parameters['bo_user']=$user;
											$quoteuser=$client_obj->getQuoteUserDetails($mail_parameters['bo_user']);
											$mail_parameters['bo_user_name']=$quoteuser[0]['first_name'].' '.$quoteuser[0]['last_name'];
											$mail_parameters['emailid']=$quoteuser[0]['email'];
											$mail_parameters['comment_user']=$this->adminLogin->userId;
											$commentusername=$client_obj->getQuoteUserDetails($mail_parameters['comment_user']);
											$mail_parameters['comment_user_name']=$commentusername[0]['first_name'].' '.$commentusername[0]['last_name'];
											$mail_parameters['photo']=$this->_config->path->fo_path.'/profiles/bo/'.$this->adminLogin->userId.'/logo.jpg';
											$mail_parameters['date_time']=date('h:i a F d, Y',time());
											$mail_parameters['quote_title']=$quoteDetails[0]['title'];
											$mail_parameters['turn_over']=$quoteDetails[0]['final_turnover'];
											$mail_parameters['estimate_sign_percentage']=$quoteDetails[0]['estimate_sign_percentage'];
											$mail_parameters['client_name']=$quoteDetails[0]['company_name'];
											$mail_parameters['client_id']=$quoteDetails[0]['client_id'];
											$mail_parameters['comment']=$log_params['comments'];
											$mail_parameters['agency_name']=$quoteDetails[0]['agency_name'];
											$mail_parameters['followup_link']='http://'.$_SERVER['HTTP_HOST'].'/quote-new/sales-final-validation?qaction=briefing&quote_id='.$quoteDetails[0]['identifier'];
											$mail_parameters['challenged']=ture;
											$mail_parameters['subject']=$mail_parameters['comment_user_name'].' is challenging you on '.$quoteDetails[0]['title'];
											$this->getNewQuoteEmails($mail_parameters);
											/*$receiver_id=$user;
											$mail_parameters['bo_user']=$user;
											$mail_parameters['sales_user']=$this->adminLogin->userId;						
											$mail_parameters['followup_link']='/quote/seo-quote-review?quote_id='.$quote_id.'&submenuId=ML13-SL2';

											$mail_obj->sendQuotePersonalEmail($receiver_id,152,$mail_parameters); */       	
							        	}

							        }	
							 
							 
							$briefUpdate=implode(',',$shareduser);
							$quotes_data['brief_email_notify']=$briefUpdate;								
							
						
						
					/*	$receiver_id=$quoteDetails[0]['quote_by'];
						$mail_parameters['sales_user']=$quoteDetails[0]['quote_by'];
						$mail_parameters['bo_user']=$this->adminLogin->userId;
						$mail_parameters['bo_user_comments']=$finalParameters['prod_comments'];
						$mail_parameters['challenge_hours']=$challenge_hours;
						
						$mail_obj->sendQuotePersonalEmail($receiver_id,141,$mail_parameters); */

					
					}
					if($finalParameters['send_team_seo']=='seo')
					{
						$quotes_data['seo_review']='challenged';
						$quotes_data['sales_review']='not_done';
						$seo_params['seo_timeline']=$quotes_data["response_time"];
								
						$quotes_data['seo_timeline']=date("Y-m-d H:i:s",$seo_params['seo_timeline']);
						$quotes_data['seo_comments']=$finalParameters['quote_updated_comments'];
						$quotes_data['seo_challenge']='no';
						
						//Insert Quote log
						$log_params['quote_id']	= $this->quote_creation->custom['quote_id'];
						$log_params['bo_user']	= $this->adminLogin->userId;					
						$log_params['version']	= $version;
						$log_params['action']	= 'sales_seo_challenged';
						$challenge_hours=round(dateDiffHours(time(),$seo_params['seo_timeline']));
						$log_params['comments']=$quotes_data['seo_comments'];
							$quiteActionId=4;

						$quotes_data['quote_delivery_hours'] = new Zend_Db_Expr('quote_delivery_hours+'.$challenge_hours);

											
						//sending email to seo managers
						//if($this->configval["seo_manager_holiday"]=='no')
						//	{
								//echo 'tsst';
								$client_obj=new Ep_Quote_Client();
								$email_users=$client_obj->getEPContacts('"seomanager"');
								//print_r($email_users);
								//exit;
									if(count($email_users)>0)
									{
											
										foreach($email_users as $user=>$name)
										{
											if(!in_array($user,$shareduser))
											{
											    array_push($shareduser,$user);
											}

											$mail_parameters['bo_user']=$user;
											$quoteuser=$client_obj->getQuoteUserDetails($mail_parameters['bo_user']);
											$mail_parameters['bo_user_name']=$quoteuser[0]['first_name'].' '.$quoteuser[0]['last_name'];
											$mail_parameters['emailid']=$quoteuser[0]['email'];
											$mail_parameters['comment_user']=$this->adminLogin->userId;
											$commentusername=$client_obj->getQuoteUserDetails($mail_parameters['comment_user']);
											$mail_parameters['comment_user_name']=$commentusername[0]['first_name'].' '.$commentusername[0]['last_name'];
											$mail_parameters['photo']=$this->_config->path->fo_path.'/profiles/bo/'.$this->adminLogin->userId.'/logo.jpg';
											$mail_parameters['date_time']=date('h:i a F d, Y',time());
											$mail_parameters['quote_title']=$quoteDetails[0]['title'];
											$mail_parameters['turn_over']=$quoteDetails[0]['final_turnover'];
											$mail_parameters['estimate_sign_percentage']=$quoteDetails[0]['estimate_sign_percentage'];
											$mail_parameters['client_name']=$quoteDetails[0]['company_name'];
											$mail_parameters['client_id']=$quoteDetails[0]['client_id'];
											$mail_parameters['comment']=$log_params['comments'];
											$mail_parameters['agency_name']=$quoteDetails[0]['agency_name'];
											$mail_parameters['followup_link']='http://'.$_SERVER['HTTP_HOST'].'/quote-new/sales-final-validation?qaction=briefing&quote_id='.$quoteDetails[0]['identifier'];
											$mail_parameters['challenged']=ture;
											$mail_parameters['subject']=$mail_parameters['comment_user_name'].' is challenging you on '.$quoteDetails[0]['title'];
											$this->getNewQuoteEmails($mail_parameters);
											/*$mail_obj=new Ep_Message_AutoEmails();
											$receiver_id=$user;
											$mail_parameters['bo_user']=$user;
											$mail_parameters['sales_user']=$this->adminLogin->userId;						
											$mail_parameters['followup_link']='/quote/seo-quote-review?quote_id='.$quote_id.'&submenuId=ML13-SL2';

											$mail_obj->sendQuotePersonalEmail($receiver_id,152,$mail_parameters);*/        	
							        	}
							        }								
							//}
						
						$briefUpdate=implode(',',$shareduser);
							$quotes_data['brief_email_notify']=$briefUpdate;
						
						/*$mail_obj=new Ep_Message_AutoEmails();
						$receiver_id=$quoteDetails[0]['quote_by'];
						$mail_parameters['sales_user']=$quoteDetails[0]['quote_by'];
						$mail_parameters['bo_user']=$this->adminLogin->userId;
						$mail_parameters['quote_title']=$quoteDetails[0]['title'];
						//$mail_parameters['challenge_time']=$update_quote['seo_timeline'];						
						$mail_parameters['followup_link_en']='/quote/quote-followup?quote_id='.$quoteDetails[0]['identifier'];
						$mail_obj->sendQuotePersonalEmail($receiver_id,136,$mail_parameters);*/

					}
					if($finalParameters['send_team_tech']=='tech')
					{

						$tech_params['tech_timeline']=$quotes_data["response_time"];
						$quotes_data['tech_timeline']=date("Y-m-d H:i:s",$tech_params['tech_timeline']);
						$quotes_data['tech_challenge_comments']=$finalParameters['quote_updated_comments'];
						$quotes_data['tech_challenge']='no';
						$quotes_data['tec_review']='challenged';
						$quotes_data['sales_review']='not_done';
						
						//Insert Quote log
						$log_params['quote_id']	= $this->quote_creation->custom['quote_id'];
						$log_params['bo_user']	= $this->adminLogin->userId;					
						$log_params['version']	= $version;
						$log_params['action']	= 'sales_tech_challenged';
						$challenge_hours=round(dateDiffHours(time(),$tech_params['tech_timeline']));
						$log_params['comments']=$quotes_data['tech_challenge_comments'];
						$quiteActionId=4;	
						$quotes_data['quote_delivery_hours'] = new Zend_Db_Expr('quote_delivery_hours+'.$challenge_hours);

						/*notify Email to tech user*/
						$techManager_holiday=$this->configval["tech_manager_holiday"];
						//if($techManager_holiday=='no')
						//{
							
								$client_obj=new Ep_Quote_Client();
								$email_users=$client_obj->getEPContacts('"techmanager"');

								if(count($email_users)>0)
								{
									
									foreach($email_users as $user=>$name)
									{
										$mail_obj=new Ep_Message_AutoEmails();
										if(!in_array($user,$shareduser))
											{
											    array_push($shareduser,$user);
											}

											$mail_parameters['bo_user']=$user;
											$quoteuser=$client_obj->getQuoteUserDetails($nmail_parameters['bo_user']);
											$mail_parameters['bo_user_name']=$quoteuser[0]['first_name'].' '.$quoteuser[0]['last_name'];
											$mail_parameters['emailid']=$quoteuser[0]['email'];
											$mail_parameters['comment_user']=$this->adminLogin->userId;
											$commentusername=$client_obj->getQuoteUserDetails($mail_parameters['comment_user']);
											$mail_parameters['comment_user_name']=$commentusername[0]['first_name'].' '.$commentusername[0]['last_name'];
											$mail_parameters['photo']=$this->_config->path->fo_path.'/profiles/bo/'.$this->adminLogin->userId.'/logo.jpg';
											$mail_parameters['date_time']=date('h:i a F d, Y',time());
											$mail_parameters['quote_title']=$quoteDetails[0]['title'];
											$mail_parameters['turn_over']=$quoteDetails[0]['final_turnover'];
											$mail_parameters['estimate_sign_percentage']=$quoteDetails[0]['estimate_sign_percentage'];
											$mail_parameters['client_name']=$quoteDetails[0]['company_name'];
											$mail_parameters['client_id']=$quoteDetails[0]['client_id'];
											$mail_parameters['comment']=$log_params['comments'];
											$mail_parameters['agency_name']=$quoteDetails[0]['agency_name'];
											$mail_parameters['followup_link']='http://'.$_SERVER['HTTP_HOST'].'/quote-new/sales-final-validation?qaction=briefing&quote_id='.$quoteDetails[0]['identifier'];
											$mail_parameters['challenged']=ture;
											$mail_parameters['subject']=$mail_parameters['comment_user_name'].' is challenging you on '.$quoteDetails[0]['title'];
											$this->getNewQuoteEmails($mail_parameters);
										/*$receiver_id=$user;
										$mail_parameters['bo_user']=$user;
										$mail_parameters['sales_user']=$this->adminLogin->userId;						
										$mail_parameters['followup_link']='/quote/tech-quote-review?quote_id='.$quote_id.'&submenuId=ML13-SL2';

										$mail_obj->sendQuotePersonalEmail($receiver_id,152,$mail_parameters);*/        	

						        	}
						        }								
							
							$briefUpdate=implode(',',$shareduser);
							$quotes_data['brief_email_notify']=$briefUpdate;
							
						//}
						
						/*notify creater quotes sales*/
						/*$mail_obj=new Ep_Message_AutoEmails();
						$receiver_id=$quoteDetails[0]['quote_by'];
						$mail_parameters['sales_user']=$quoteDetails[0]['quote_by'];
						$mail_parameters['bo_user']=$this->adminLogin->userId;
						$mail_parameters['bo_user_type']='tech';
						$mail_parameters['quote_title']=$quoteDetails[0]['title'];
						$mail_parameters['followup_link']='/quote/quote-followup?quote_id='.$quoteDetails[0]['identifier'];
						$mail_obj->sendQuotePersonalEmail($receiver_id,134,$mail_parameters);*/


					}

					$quotes_data['contact_client']=$this->quote_creation->create_step1['contact_client'];//client contact selected in header	

					if($this->quote_creation->custom['quote_id'])
					{
						$quoteIdentifier=$this->quote_creation->custom['quote_id'];
						$quotes_data['updated_at']=date("Y-m-d H:i:s");
						$quote_obj->updateQuote($quotes_data,$quoteIdentifier);
						$edited=TRUE;
					}
					else
					{
					$quote_obj->insertQuote($quotes_data);
					$quoteIdentifier=$quote_obj->getIdentifier();
					}

							
					

		            //Quote missions insertion
					if(count($this->quote_creation->create_mission['product'])>0 || count($this->quote_creation->tech_mission['product']))
					{
						$sales_margin_percentage=0;
						$margin=0;
						//echo "<pre>";print_r($this->quote_creation->create_mission['quote_missions']);exit;
						$i=0;
						foreach($this->quote_creation->create_mission['product'] as $missions)
						{

							$quoteMission_obj=new Ep_Quote_QuoteMissions();
							
							$quoteMission_data['quote_id']=$quoteIdentifier;
							$quoteMission_data['product']=$this->quote_creation->create_mission['product'][$i];
							$quoteMission_data['product_type']=$this->quote_creation->create_mission['producttype'][$i];
							if($quoteMission_data['producttype']=='autre')
								$quoteMission_data['product_type_other']=$this->quote_creation->create_mission['producttypeother'][$i];
							else
								$quoteMission_data['product_type_other']=NULL;


							$quoteMission_data['category']=$this->quote_creation->create_step1['category'];
							if($quoteMission_data['product']=='translation')
								$quoteMission_data['language_dest']=$this->quote_creation->create_mission['languagedest'][$i];
							if($quoteMission['product']!='auture')
							{
								$quoteMission_data['language_source']=$this->quote_creation->create_mission['language'][$i];
								$quoteMission_data['nb_words']=$this->quote_creation->create_mission['nb_words'][$i];
								$quoteMission_data['volume']=$this->quote_creation->create_mission['volume'][$i];
							}	
							$quoteMission_data['comments']=$this->quote_creation->create_mission['comments'][$i];
							
							$quoteMission_data['created_by']=$this->quote_creation->create_step1['quote_by'];
							
							/*added w.r.t tempo*/
							$quoteMission_data['mission_length']=$this->quote_creation->create_mission['mission_length'][$i];
							$quoteMission_data['mission_length_option']=$this->quote_creation->create_mission['mission_length_option'][$i];						
							$quoteMission_data['volume_max']=$this->quote_creation->create_mission['volume_max'][$i];
							$quoteMission_data['delivery_volume_option']=$this->quote_creation->create_mission['delivery_volume_option'][$i];
							$quoteMission_data['tempo']=$this->quote_creation->create_mission['tempo_type'][$i];
							$quoteMission_data['tempo_length']=$this->quote_creation->create_mission['tempo_length'][$i];
							$quoteMission_data['tempo_length_option']=$this->quote_creation->create_mission['tempo_length_option'][$i];
							$quoteMission_data['oneshot']=$this->quote_creation->create_mission['oneshot'][$i];
							$quoteMission_data['demande_client']=$this->quote_creation->create_mission['demande_client'][$i];
							$quoteMission_data['duration_dont_know']=$this->quote_creation->create_mission['duration_dont_know'][$i];
							
							if($quoteMission_data['product']=='content_strategy')
							{
								$quoteMission_data['cost']=$this->quote_creation->create_mission['internal_cost'][$i];
								$quoteMission_data['unit_price']=$this->quote_creation->create_mission['unit_price'][$i];
								$quoteMission_data['margin_percentage']=$this->quote_creation->create_mission['margin_percentage'][$i];							
								$quoteMission_data['internal_cost']=$this->quote_creation->create_mission['internal_cost'][$i];	
								$quoteMission_data['product_type']=$this->quote_creation->create_mission['producttype'][$i];
								$quoteMission_data['nb_words']=0;
								$quoteMission_data['misson_user_type']='seo';
							}
							else
							{
								$quoteMission_data['misson_user_type']='sales';
								$quoteMission_data['cost']=$this->quote_creation->create_mission['internal_cost'][$i];
							}
						

							$quoteMission_data['package']=$this->quote_creation->create_mission['package'][$i];

					    	if($quoteMission_data['package']=='team')
					    	{
					    		$quoteMission_data['team_fee']=$this->quote_creation->create_mission['team_fee'][$i];
					    		$quoteMission_data['team_packs']=$this->quote_creation->create_mission['team_packs'][$i];
					    		$quoteMission_data['turnover']=$this->quote_creation->create_mission['turnover'][$i];
					    	}
					    	else if($quoteMission_data['package']=='user')
					    	{
					    		$quoteMission_data['user_fee']=$this->quote_creation->create_mission['user_fee'][$i];
					    		$quoteMission_data['turnover']=$this->quote_creation->create_mission['turnover'][$i];
					    	}
					    	else
					    	{
					    		$quoteMission_data['turnover']=$this->quote_creation->create_mission['turnover'][$i];
					    	}		    	
					    	
					    	//free mission  	
		                    $quoteMission_data['free_mission']=$this->quote_creation->create_mission['free_mission'][$i];


		                    if($quoteMission_data['package']=='team')
					    	{
					    		//echo $mission_update['turnover']+$mission_update['team_fee'];exit;
					    		if($quoteMission_data['free_mission']=='yes')
									$team_turnover=0;
								else	
									$team_turnover=$this->quote_creation->create_mission['turnover'][$i];

					    		
								$total_turnover+=$team_turnover;//$mission_update['turnover']+$mission_update['team_fee'];
					    	}
					    	else
							{
					    		if($quoteMission_data['free_mission']=='yes')
									$turnover=0;
								else
									$turnover=$this->quote_creation->create_mission['turnover'][$i];
									
								$total_turnover+=$turnover;
							}

							if($this->quote_creation->create_mission['mission_identifier'][$i])
								$quoteMission['identifier']=$this->quote_creation->create_mission['mission_identifier'][$i];
				    	
							
							/*$suggested_missions=array();

							//echo "<pre>"; print_r($this->quote_creation->create_mission['quote_missions'][$i]['missionDetails']);
							//exit;

							if(count($this->quote_creation->create_mission['quote_missions'][$i]['missionDetails'])>0)
							{
								foreach($this->quote_creation->create_mission['quote_missions'][$i]['missionDetails'] as $missions_archived)
								{
									
									//if(in_array($missions_archived['id'],$this->quote_creation->select_missions['missions_selected']))
									if(in_array($missions_archived['mission_id'],$this->quote_creation->create_mission['selected_mission']))								
									{
										$suggested_missions[]=$missions_archived['mission_id'];
									}
								}
							}*/
							if($this->quote_creation->create_mission['selected_mission'][$i])
							$quoteMission_data['sales_suggested_missions']=$this->quote_creation->create_mission['selected_mission'][$i];

							$mission_obj=new Ep_Quote_QuoteMissions();
							$historyParameters['mission_id']=$quoteMission_data['sales_suggested_missions'];
							

								$quoteMission_data['unit_price']=$this->quote_creation->create_mission['unit_price'][$i];
								$quoteMission_data['margin_percentage']=$this->quote_creation->create_mission['margin_percentage'][$i];							
								$quoteMission_data['internal_cost']=$this->quote_creation->create_mission['internal_cost'][$i];	
								/*$quoteMission_data['staff_time']=$suggested_mission_details[0]['staff_time'];
								$quoteMission_data['staff_time_option']=$suggested_mission_details[0]['staff_time_option'];**/
									
							
							$quoteMission_data['version']=$version;
							$quoteMission_data['is_new_quote']=1;	

						//	echo "<pre>";	print_r($quoteMission_data); exit;
														
							if($user_type != 'techuser' && $user_type != 'techmanager')
							{	
								if($quoteMission['identifier'])
								{
									if(!$this->quote_creation->custom['create_new_version'])
										$quoteMission_data['updated_at']=date("Y-m-d H:i:s");
									$quoteMission_obj->updateQuoteMission($quoteMission_data,$quoteMission['identifier']);
									$quoteMissionIdentifier=$quoteMission['identifier'];
									$prodchecking[$i]=$quoteMissionIdentifier;
									$newmissionAdded=false;
								}
								else
								{
									if(!$this->quote_creation->custom['create_new_version'])
										$quoteMission_data['created_at']=date("Y-m-d H:i:s");
									$quoteMission_obj->insertQuoteMission($quoteMission_data);
									$quoteMissionIdentifier=$quoteMission_obj->getIdentifier();
									$prodchecking[$i]=$quoteMissionIdentifier;
									//updating prod status if new mission added in edit mode
									if(!$this->quote_creation->custom['create_new_version'] && $this->quote_creation->custom['action']=='edit')
										$newmissionAdded=true;								
								}	
							}
							
							

							//quote sales margin;
							$sales_margin_percentage+=$quoteMission_data['margin_percentage'];
							$margin++;
							$i++;
						}
						$t=0;
						foreach ($this->quote_creation->tech_mission['product'] as $tmission) 
						{
							
								$tech_data['title']=$this->quote_creation->tech_mission['tech_type'][$t];
								$tech_data['delivery_time']=$this->quote_creation->tech_mission['tech_mission_length'][$t];
								$tech_data['delivery_option']=$this->quote_creation->tech_mission['tech_mission_length_option'][$t];
								$tech_data['volume_max']=$this->quote_creation->tech_mission['volume_max'][$t];
								$tech_data['tempo']=$this->quote_creation->tech_mission['tempo_type'][$t];
								$tech_data['delivery_volume_option']=$this->quote_creation->tech_mission['delivery_volume_option'][$t];
								$tech_data['tempo_length']=$this->quote_creation->tech_mission['tempo_length'][$t];
								$tech_data['tempo_length_option']=$this->quote_creation->tech_mission['tempo_length_option'][$t];
								if($this->quote_creation->tech_mission['tech_oneshot'][$t])
								$tech_data['oneshot']=$this->quote_creation->tech_mission['tech_oneshot'][$t];

								$tech_data['cost']=$this->quote_creation->tech_mission['internal_cost'][$t];
								//$tech_data['comments']=$this->quote_creation->tech_mission['comments'][$t];
								$tech_data['currency']=$this->quote_creation->create_step1['currency'];
								$tech_data['margin_percentage']=$this->quote_creation->tech_mission['margin_percentage'][$t];
								$tech_data['unit_price']=$this->quote_creation->tech_mission['unit_price'][$t];
								$tech_data['tech_type_id']=$this->quote_creation->tech_mission['tech_title_id'][$t];
								$tech_data['before_prod']=$this->quote_creation->tech_mission['to_perform'][$t];
								$tech_data['volume']=$this->quote_creation->tech_mission['volume'][$t];
								$tech_data['version']=$version;
								$tech_data['is_new_quote']=1;	
								$tech_data['include_final']='yes';
								$tech_data['package']=$this->quote_creation->tech_mission['package'][$t];

								/* Prod mission link */
								$tech_data['prod_linked']=$this->quote_creation->tech_mission['linked_to_prod'][$t];
							    	if($tech_data['package']=='team')
							    	{
							    		$tech_data['team_fee']=$this->quote_creation->tech_mission['team_fee'][$t];
							    		$tech_data['team_packs']=$this->quote_creation->tech_mission['team_packs'][$t];
							    		$tech_data['turnover']=$this->quote_creation->tech_mission['turnover'][$t];
							    	}
							    	else if($tech_data['package']=='user')
							    	{
							    		$tech_data['user_fee']=$this->quote_creation->tech_mission['user_fee'][$t];
							    		$tech_data['turnover']=$this->quote_creation->tech_mission['turnover'][$t];
							    	}
							    	else
							    	{
							    		$tech_data['turnover']=$this->quote_creation->tech_mission['turnover'][$t];
							    	}		    	
							    	
							    	//free mission  	
				                    $tech_data['free_mission']=$this->quote_creation->tech_mission['free_mission'][$t];


				                    if($tech_data['package']=='team')
							    	{
							    		//echo $mission_update['turnover']+$mission_update['team_fee'];exit;
							    		if($tech_data['free_mission']=='yes')
											$team_turnover=0;
										else	
											$team_turnover=$this->quote_creation->tech_mission['turnover'][$t];

							    		
										$total_turnover+=$team_turnover;//$mission_update['turnover']+$mission_update['team_fee'];
							    	}
							    	else
									{
							    		if($tech_data['free_mission']=='yes')
											$turnover=0;
										else
											$turnover=$this->quote_creation->tech_mission['turnover'][$t];
											
										$total_turnover+=$turnover;
									}

									if($this->quote_creation->tech_mission['mission_identifier'][$t])
											$techMission['identifier']=$this->quote_creation->tech_mission['mission_identifier'][$t];	
									//echo "<pre>"; print_r($tech_data); exit;
										/*allow only tech,sales and superadmin*/
									if($user_type == 'superadmin'  || $user_type == 'salesmanager' || $user_type == 'salesuser' || $user_type=='techmanager' || $user_type=='techuser')
									{
										if($techMission['identifier'])
										{
											if(!$this->quote_creation->custom['create_new_version'])
											$tech_data['updated_at']=date("Y-m-d H:i:s");
											$techObj->updateTechMission($tech_data,$techMission['identifier']);
										}
										else
										{
											if(!$this->quote_creation->custom['create_new_version'])
												$tech_data['created_at']=date("Y-m-d H:i:s");
												$tech_data['created_by']=$this->adminLogin->userId;
												$techObj->insertTechMission($tech_data);
												$techmissions_assigned[]=$techObj->getIdentifier();
										}	
									}
								$sales_margin_percentage+=$tech_data['margin_percentage'];
								$margin++;
								$t++;
						}
							//updating sales margin in Quote table
							if($user_type == 'superadmin'  || $user_type == 'salesmanager' || $user_type == 'salesuser')
							{
							$avg_sales_margin_percentage=($sales_margin_percentage/$margin);
							$margin_data['sales_margin_percentage']=round($avg_sales_margin_percentage,2);	
							$margin_data['sales_suggested_price']=round($total_turnover,2);
							$margin_data['sales_suggested_currency']=$this->quote_creation->create_step1['currency'];
							}
							if(count($techmissions_assigned)>0)
							{
								$margin_data['techmissions_assigned']=implode(",",$techmissions_assigned);	
							}			
							if(count($margin_data)>0)
								$quote_obj->updateQuote($margin_data,$quoteIdentifier);

							
					}
					$log_obj=new Ep_Quote_QuotesLog();
					if($finalParameters['send_team_seo'] || $finalParameters['send_team_tech'] || $finalParameters['send_team_prod'])
					{
					$log_obj->insertLog($quiteActionId,$log_params);
					}
					else
					{
						$log_paramsfnal['quote_id']	= $quoteIdentifier;
						$log_paramsfnal['bo_user']	= $this->adminLogin->userId;
						$log_paramsfnal['quote_size']=$quotes_data['sales_suggested_price'] < 5000 ? "small" :"big";
						$log_paramsfnal['urgent']	= $final_parameters['urgent']? 'urgent':'';
						$log_paramsfnal['version']	= $version;					
						$log_paramsfnal['created_date']	= date("Y-m-d H:i:s");

					
							if($edited)
							{
								$log_paramsfnal['action']	= 'quote_updated';
								$actionId=9;

								if($this->quote_creation->send_quote['quote_updated_comments'])
								{
									$log_paramsfnal['comments']=$this->quote_creation->send_quote['quote_updated_comments'];
								}
							}
							else
							{
								$log_paramsfnal['action']	= 'quote_created';
								$actionId=1;	

								if($this->quote_creation->send_quote['sales_comment'])
								{
									$log_paramsfnal['comments']=$this->quote_creation->send_quote['sales_comment'];
								}
							}

						$log_obj->insertLog($actionId,$log_paramsfnal);	

					}
		           	
					//Email Sending

					 $client_obj=new Ep_Quote_Client();
					 $quoteDetails=$quote_obj->getQuoteDetails($quoteIdentifier);
			        //$intimate_users=$client_obj->getEPContacts('"facturation"');
			        //$intimate_users[$this->adminLogin->userId]=$this->adminLogin->userId;
			        

			        

						if($this->quote_creation->create_mission['producttypeautre']==TRUE &&   $user_type=='salesuser' && ($quoteDetails[0]['sales_review']=='briefing' || $quoteDetails[0]['sales_review']=='not_done') )
						{
							$email_users=array('120206112651459'=>'cleguille@edit-place.com');
								
								//$email_users=$get_head_prods=$client_obj->getEPContacts('"salesmanager"');

								/*if(count($email_users)>0)
								{
									
									foreach($email_users as $user=>$name)
									{
										$mail_obj=new Ep_Message_AutoEmails();
										$receiver_id=$user;
										$mail_parameters['bo_user']=$user;
										$mail_parameters['sales_user']=$this->adminLogin->userId;						
										$mail_parameters['challenge_link']='/quote/sales-quotes-list?submenuId=ML13-SL2';

										$mail_obj->sendQuotePersonalEmail($receiver_id,151,$mail_parameters);        	
						        	}
						        }*/
						        
						        
									//if mission product value autre email send to sales manager need to add thaibault
							        //$email_usershead=array('120206112651459'=>'cleguille@edit-place.com');
								//$email_usershead=$client_obj->getEPContacts('"salesmanager"');
									$email_usershead=array('120206112651459'=>'cleguille@edit-place.com');
									
									$quoteEditDetails=$quote_obj->getQuoteDetails($quoteIdentifier);
									
												foreach($email_usershead as $userhead=>$emailshead)
												{

													/*$receiverhead_id=$userhead;
													$mailhead_parameters['sales_user']=$quoteEditDetails[0]['quote_by'];
													$mailhead_parameters['bo_user']=$receiverhead_id;
													$mailhead_parameters['quote_title']=$quotes_data['title'];
													$mailhead_parameters['followup_link']='/quote/quote-followup?quote_id='.$quoteIdentifier."&submenuId=ML13-SL2";
													$mail_obj=new Ep_Message_AutoEmails();
													$mail_obj->sendQuotePersonalEmail($receiverhead_id,198,$mailhead_parameters);
													*/
													$mailhead_parameters['auture']=ture;
							
													$mailhead_parameters['bo_user']=$userhead;
													$mailhead_parameters['sales_user']=$this->adminLogin->userId;
													$saleuser=$client_obj->getQuoteUserDetails($this->adminLogin->userId);
													$mailhead_parameters['sales_user_name']=$saleuser[0]['first_name'].' '.$saleuser[0]['last_name'];
													$quoteuser=$client_obj->getQuoteUserDetails($mailhead_parameters['bo_user']);
													$mailhead_parameters['bo_user_name']=$quoteuser[0]['first_name'].' '.$quoteuser[0]['last_name'];
													$mailhead_parameters['emailid']=$emailshead;
													$mailhead_parameters['quote_title']=$quoteDetails[0]['title'];
													$mailhead_parameters['turn_over']=$quoteDetails[0]['final_turnover'];

													$mailhead_parameters['estimate_sign_percentage']=$quoteDetails[0]['estimate_sign_percentage'];
													$mailhead_parameters['client_name']=$quoteDetails[0]['company_name'];
													$mailhead_parameters['client_id']=$quoteDetails[0]['client_id'];
													$mailhead_parameters['followup_link']='http://'.$_SERVER['HTTP_HOST'].'/quote-new/client-brief?qaction=briefing&quote_id='.$quoteDetails[0]['identifier'];
													$mailhead_parameters['agency_name']=$quoteDetails[0]['agency_name'];
													$mailhead_parameters['subject']='A new quote '.$mailhead_parameters['quote_title'].' must be validated on Workplace';
													$this->getNewQuoteEmails($mailhead_parameters);
												}
								
						} 	

						
					/*prod validated Email notify*/
					if($prod_validated)
					{
						
							if($quoteDetails[0]['prod_timeline']>0)
								$prod_time_line=$quoteDetails[0]['prod_timeline'];

							if($prod_time_line>time())
							{
								$log_params['action']= 'prod_validated_ontime';
								$quiteActionId=5;								
								
							}
							else
							{
								$delay_hours=dateDiffHours($prod_time_line,time());

								$log_params['action']= 'prod_validated_delay';
								$log_params['delay_hours']=$delay_hours;
								$quiteActionId=6;							
							}

							$log_obj=new Ep_Quote_QuotesLog();
							$log_obj->insertLog($quiteActionId,$log_params);

							//sending email to sales user
							$mail_obj=new Ep_Message_AutoEmails();
							$receiver_id=$quoteDetails[0]['quote_by'];
							$mail_parameters['prodvalidate']=true;
							
							
							$mail_parameters['bo_user']=$receiver_id;
							$mail_parameters['sales_user']=$this->adminLogin->userId;
							$bousername=$client_obj->getQuoteUserDetails($receiver_id);
							$mail_parameters['bo_user_name']= $bousername[0]['first_name'].' '.$bousername[0]['last_name'];
							$mail_parameters['emailid']=$bousername[0]['email'];
							$quoteuser=$client_obj->getQuoteUserDetails($this->adminLogin->userId);
							$mail_parameters['manageruser']= $quoteuser[0]['first_name'].' '.$quoteuser[0]['last_name'];
							$mail_parameters['quote_title']=$quoteDetails[0]['title'];
							$mail_parameters['estimate_sign_percentage']=$quoteDetails[0]['estimate_sign_percentage'];
							$mail_parameters['turn_over']=$quoteDetails[0]['final_turnover'];
							$mail_parameters['agency_name']=$quoteDetails[0]['agency_name'];
							$mail_parameters['client_id']=$quoteDetails[0]['client_id'];
							$mail_parameters['date_time']=date('h:i a F d, Y',time());
							$mail_parameters['client_name']=$quoteDetails[0]['company_name'];
							$mail_parameters['subject']=$mail_parameters['manageruser'].' has replied to the quote '.$quoteDetails[0]['title'];
							$mail_parameters['followup_link']='http://'.$_SERVER['HTTP_HOST'].'/quote-new/sales-final-validation?qaction=briefing&quote_id='.$quoteDetails[0]['identifier'];
							$this->getNewQuoteEmails($mail_parameters);
							
							/*$mail_parameters['bo_user']=$this->adminLogin->userId;
							$mail_parameters['bo_user_type']='prod';
							$mail_parameters['quote_title']=$quoteDetails[0]['title'];
							$mail_parameters['followup_link']='/quote/quote-followup?quote_id='.$quoteDetails[0]['identifier'];
							$mail_obj->sendQuotePersonalEmail($receiver_id,134,$mail_parameters);*/

							
													
							//head sales notify email in sales final stage
							$email_head_sale=array('120206112651459'=>'cleguille@edit-place.com',
		                             '110920152530186'=>'mfouris@edit-place.com'); // need add thaibault
								
										
								
										if(count($email_head_sale)>0){
									   
											foreach($email_head_sale as $user=>$emails){

												$headmail_parameters['sales_ready']=true;
												$headmail_parameters['subject']=$quoteDetails[0]['title'].' is ready to be validated';
												$headmail_parameters['emailid']=$emails;
												$headmail_parameters['bo_user']=$user;
												$bousername=$client_obj->getQuoteUserDetails($user);
												$headmail_parameters['bo_user_name']= $bousername[0]['first_name'].' '.$bousername[0]['last_name'];
												$quoteuser=$client_obj->getQuoteUserDetails($this->adminLogin->userId);
												$headmail_parameters['prod_user']= $quoteuser[0]['first_name'].' '.$quoteuser[0]['last_name'];
												$headmail_parameters['quote_title']=$quoteDetails[0]['title'];
												$headmail_parameters['estimate_sign_percentage']=$quoteDetails[0]['estimate_sign_percentage'];
												$headmail_parameters['turn_over']=$quoteDetails[0]['final_turnover'];
												$headmail_parameters['agency_name']=$quoteDetails[0]['agency_name'];
												$headmail_parameters['client_id']=$quoteDetails[0]['client_id'];
												$headmail_parameters['client_name']=$quoteDetails[0]['company_name'];
												$headmail_parameters['followup_link']='http://'.$_SERVER['HTTP_HOST'].'/quote-new/sales-final-validation?qaction=briefing&quote_id='.$quoteDetails[0]['identifier'];
												$this->getNewQuoteEmails($headmail_parameters);
												/*$receiver_id=$user;
												$headmail_parameters['bo_user']=$user;
												$headmail_parameters['sales_user']=$quoteDetails[0]['quote_by'];
												$headmail_parameters['turn_over']=$quoteDetails[0]['turnover'];
												$headmail_parameters['client_name']=$quoteDetails[0]['company_name'];
												$headmail_parameters['followup_link']='/quote/quote-followup?quote_id='.$quoteDetails[0]['identifier'];
												$headmail_obj=new Ep_Message_AutoEmails();
												$headmail_obj->sendQuotePersonalEmail($receiver_id,205,$headmail_parameters);
												*/

												
										}
									}
					}
					/*tech notify email with */
					if($tec_validated)
					{

						if($quoteDetails[0]['tech_timeline'])
							$tech_time_line=strtotime($quoteDetails[0]['tech_timeline']);

						if($tech_time_line>time())
						{
							$log_params['action']= 'tech_validated_ontime';
							$quiteActionId=5;								
							
						}
						else
						{
							$delay_hours=dateDiffHours($tech_time_line,time());

							$log_params['action']= 'tech_validated_delay';
							$log_params['delay_hours']=$delay_hours;
							$quiteActionId=6;							
						}

												
						$log_obj=new Ep_Quote_QuotesLog();
						$log_obj->insertLog($quiteActionId,$log_params);
						

						//sending email to sales user(Quote is finalized )
						$mail_obj=new Ep_Message_AutoEmails();
						$receiver_id=$quoteDetails[0]['quote_by'];
						$mail_parameters['tecvalidate']=true;
							
							
							$mail_parameters['bo_user']=$receiver_id;
							$bousername=$client_obj->getQuoteUserDetails($receiver_id);
							$mail_parameters['bo_user_name']= $bousername[0]['first_name'].' '.$bousername[0]['last_name'];
							$mail_parameters['emailid']=$bousername[0]['email'];
							$quoteuser=$client_obj->getQuoteUserDetails($this->adminLogin->userId);
							$mail_parameters['sales_user']=$this->adminLogin->userId;
							$mail_parameters['manageruser']= $quoteuser[0]['first_name'].' '.$quoteuser[0]['last_name'];
							$mail_parameters['quote_title']=$quoteDetails[0]['title'];
							$mail_parameters['estimate_sign_percentage']=$quoteDetails[0]['estimate_sign_percentage'];
							$mail_parameters['turn_over']=$quoteDetails[0]['final_turnover'];
							$mail_parameters['agency_name']=$quoteDetails[0]['agency_name'];
							$mail_parameters['client_id']=$quoteDetails[0]['client_id'];
							$mail_parameters['date_time']=date('h:i a F d, Y',time());
							$mail_parameters['client_name']=$quoteDetails[0]['company_name'];
							$mail_parameters['subject']=$mail_parameters['manageruser'].' has replied to the quote '.$quoteDetails[0]['title'];
							$mail_parameters['followup_link']='http://'.$_SERVER['HTTP_HOST'].'/quote-new/sales-final-validation?qaction=briefing&quote_id='.$quoteDetails[0]['identifier'];
							$this->getNewQuoteEmails($mail_parameters);
						/*$mail_parameters['sales_user']=$quoteDetails[0]['quote_by'];
						$mail_parameters['bo_user']=$this->adminLogin->userId;
						$mail_parameters['bo_user_type']='tech';
						$mail_parameters['quote_title']=$quoteDetails[0]['title'];
						$mail_parameters['followup_link']='/quote/quote-followup?quote_id='.$quoteDetails[0]['identifier'];
						$mail_obj->sendQuotePersonalEmail($receiver_id,134,$mail_parameters);*/


						
						//send notifcation email to sales (Quote arrives to prod)						
							
						/*	if(($quoteDetails[0]['tec_review']=='skipped' || $quoteDetails[0]['tec_review']=='auto_skipped' ||$quoteDetails[0]['tec_review']=='validated') 
								&& ($quoteDetails[0]['seo_review']=='skipped' || $quoteDetails[0]['seo_review']=='auto_skipped' ||$quoteDetails[0]['seo_review']=='validated') && $quoteDetails[0]['prod_review']!='auto_skipped')
							{								
								$mail_obj=new Ep_Message_AutoEmails();
								$receiver_id=$quoteDetails[0]['quote_by'];
								$mail_parameters['sales_user']=$quoteDetails[0]['quote_by'];
								$mail_parameters['bo_user']=$this->adminLogin->userId;
								$mail_parameters['quote_title']=$quoteDetails[0]['title'];
								$mail_parameters['challenge_time']=date("Y-m-d H:i:s",$update_quote_tech["prod_timeline"]);
								$mail_parameters['followup_link']='/quote/quote-followup?quote_id='.$quoteDetails[0]['identifier'];
								$mail_obj->sendQuotePersonalEmail($receiver_id,137,$mail_parameters);
							}	*/

					}
					if($seo_validated)
					{
						if($quoteDetails[0]['seo_timeline'])
								$seo_timeline=strtotime($quoteDetails[0]['seo_timeline']);

							if($seo_timeline>time())
							{
								$log_params['action']= 'seo_validated_ontime';
								$quiteActionId=5;								
								
							}
							else
							{
								$delay_hours=dateDiffHours($seo_timeline,time());

								$log_params['action']= 'seo_validated_delay';
								$log_params['delay_hours']=$delay_hours;
								$quiteActionId=6;							
							}

														
							$log_obj=new Ep_Quote_QuotesLog();
							$log_obj->insertLog($quiteActionId,$log_params);
														
							
							//sending email to sales user (Quote is finalized)
							$mail_obj=new Ep_Message_AutoEmails();

							$receiver_id=$quoteDetails[0]['quote_by'];
							$mail_parameters['seovalidate']=true;
							
							
							$mail_parameters['bo_user']=$receiver_id;
							$bousername=$client_obj->getQuoteUserDetails($receiver_id);
							$mail_parameters['bo_user_name']= $bousername[0]['first_name'].' '.$bousername[0]['last_name'];
							
							$mail_parameters['emailid']=$bousername[0]['email'];
							$quoteuser=$client_obj->getQuoteUserDetails($this->adminLogin->userId);
							$mail_parameters['sales_user']=$this->adminLogin->userId;
							$mail_parameters['manageruser']= $quoteuser[0]['first_name'].' '.$quoteuser[0]['last_name'];
							$mail_parameters['quote_title']=$quoteDetails[0]['title'];
							$mail_parameters['estimate_sign_percentage']=$quoteDetails[0]['estimate_sign_percentage'];
							$mail_parameters['turn_over']=$quoteDetails[0]['final_turnover'];
							$mail_parameters['agency_name']=$quoteDetails[0]['agency_name'];
							$mail_parameters['client_id']=$quoteDetails[0]['client_id'];
							$mail_parameters['date_time']=date('h:i a F d, Y',time());
							$mail_parameters['client_name']=$quoteDetails[0]['company_name'];
							$mail_parameters['subject']=$mail_parameters['manageruser'].' has replied to the quote '.$quoteDetails[0]['title'];
							$mail_parameters['followup_link']='http://'.$_SERVER['HTTP_HOST'].'/quote-new/sales-final-validation?qaction=briefing&quote_id='.$quoteDetails[0]['identifier'];
							$this->getNewQuoteEmails($mail_parameters);
							/*$mail_parameters['sales_user']=$quoteDetails[0]['quote_by'];
							$mail_parameters['bo_user']=$this->adminLogin->userId;
							$mail_parameters['bo_user_type']='seo';
							$mail_parameters['quote_title']=$quoteDetails[0]['title'];
							$mail_parameters['followup_link']='/quote/quote-followup?quote_id='.$quoteDetails[0]['identifier'];
							$mail_obj->sendQuotePersonalEmail($receiver_id,134,$mail_parameters);*/


							//send notifcation email to sales (Quote arrives to prod)						
						/*	if(($quoteDetails[0]['tec_review']=='skipped' || $quoteDetails[0]['tec_review']=='auto_skipped' ||$quoteDetails[0]['tec_review']=='validated') 
								&& ($quoteDetails[0]['seo_review']=='skipped' || $quoteDetails[0]['seo_review']=='auto_skipped' ||$quoteDetails[0]['seo_review']=='validated') && $quoteDetails[0]['prod_review']!='auto_skipped')
							{
								$mail_obj=new Ep_Message_AutoEmails();
								$receiver_id=$quoteDetails[0]['quote_by'];
								$mail_parameters['sales_user']=$quoteDetails[0]['quote_by'];
								$mail_parameters['bo_user']=$this->adminLogin->userId;
								$mail_parameters['quote_title']=$quoteDetails[0]['title'];
								$mail_parameters['challenge_time']=date("y-m-d H:i:s",$update_quote_seo["prod_timeline"]);						
								$mail_parameters['followup_link']='/quote/quote-followup?quote_id='.$quoteDetails[0]['identifier'];
								$mail_obj->sendQuotePersonalEmail($receiver_id,137,$mail_parameters);
							}*/


					}

				if($finalParameters['validate']!='save')
				{	
				    unset($this->quote_creation->create_step1);
		            unset($this->quote_creation->create_mission);
		            unset($this->quote_creation->tech_mission);
		            unset($this->quote_creation->select_missions);
		            unset($this->quote_creation->custom);
					unset($this->quote_creation->send_quote);		
				}
				if(($finalParameters['send_team_seo'] || $finalParameters['send_team_tech'] || $finalParameters['send_team_prod'] )||($prod_validated || $tec_validated || $seo_validated))
				{
					$this->_redirect("/quote-new/sales-quotes-list");	
				}	
				elseif($finalParameters['validate']=='save')
				{
					$this->_redirect("/quote-new/sales-final-validation");		
				}
				else
				{
				 $this->_redirect("/quote-new/sales-quotes-list?active=validated");	
				}
				
	       } 


			    	
        }
	}


	/*send quote*/
	public function sendQuoteAction()
	{
		//echo "<pre>";print_r($this->quote_creation->tech_mission);exit;
		
		if(is_array($this->quote_creation->create_mission['selected_mission']))
		{
			//$this->quote_creation->custom['mission_added'];
			//getting Quote user details of selected Bo user
			$client_obj=new Ep_Quote_Client();
			$quote_by=$this->quote_creation->create_step1['quote_by'];
			$bo_user_details=$client_obj->getQuoteUserDetails($quote_by);
			if($bo_user_details!='NO')
			{
				$this->quote_creation->create_mission['quote_user_name']=$bo_user_details[0]['first_name'].' '.$bo_user_details[0]['last_name'];
				$this->quote_creation->create_mission['email']=$bo_user_details[0]['email'];
				$this->quote_creation->create_mission['phone_number']=$bo_user_details[0]['phone_number'];
								
			}
			
			$this->_view->create_mission=$this->quote_creation->create_mission;
			$this->_view->create_step1=$this->quote_creation->create_step1;
		    $this->_view->selected_mission=$this->quote_creation->create_mission['selected_mission'];
		    $this->_view->quote_missions=$this->quote_creation->create_mission['quote_missions'];
		    
		    $this->_view->sales_manager_holiday=$this->configval["sales_manager_holiday"];
			$this->_view->user_type=$this->adminLogin->type;

			$this->render('send-quote-new');
		}
		else
			$this->_redirect("/quote-new/create-step1?submenuId=ML13-SL2");	
		
		
	}


	/**Tech mission creation**/

	public function createTechMissionAction()
	{

		$quotes_obj=new Ep_Quote_Quotes();	
		$tech_title=$quotes_obj->techtitles();
		$this->_view->tech_mission_title=$tech_title;
		$this->render('create-tech-mission');
	}

	/**Tech mission title**/
	public function techTitleSelectAction()
	{
		$tech_params=$this->_request->getParams();
		//echo "<pre>"; print_r($tech_params); exit;
		if($tech_params['prod_mission_val']=='No' && $tech_params['duplicatenew']!='yes')
		{
			$option="<option></option>";
			$quotes_obj=new Ep_Quote_Quotes();	
			$tech_title=$quotes_obj->techtitles();
			foreach($tech_title as $titleval){
				if($titleval['integrated']!='yes')
				{
					if($tech_params['typeid']==$titleval['tid'])
						$option.="<option value=".$titleval['tid']." selected>".htmlentities($titleval['tech_title'],ENT_COMPAT, "UTF-8")."</option>";	
						else
						$option.="<option value=".$titleval['tid']." >".htmlentities($titleval['tech_title'],ENT_COMPAT, "UTF-8")."</option>";	
				}
			}
			
			echo $option; exit;
		}
		elseif($tech_params['prod_mission_val']=='Yes' && $tech_params['duplicatenew']!='yes')
		{
			$option="<option></option>";
			$quotes_obj=new Ep_Quote_Quotes();	
			$tech_title=$quotes_obj->techtitles();
			foreach($tech_title as $titleval){
				if($tech_params['typeid']==$titleval['tid'])
					$option.="<option value=".$titleval['tid']." selected>".htmlentities($titleval['tech_title'],ENT_COMPAT, "UTF-8")."</option>";
				else
					$option.="<option value=".$titleval['tid']." >".htmlentities($titleval['tech_title'],ENT_COMPAT, "UTF-8")."</option>";
			}
			
			echo $option; exit;
		}
		elseif($tech_params['prod_mission_val']=='Yes' && $tech_params['duplicatenew']=='yes')
		{
			$option="";
			$quotes_obj=new Ep_Quote_Quotes();	
			$tech_title=$quotes_obj->techtitles();
			foreach($tech_title as $titleval){
				if($tech_params['typeid']==$titleval['tid'])
					$option.="<div class='form-group check-duplicate col-md-3 pull-left'><input type='checkbox' name='duplicatetitle[]' id='duplicatetitle[".$titleval['tid']."]' class='form-control icheck-input-radio' value=".$titleval['tid']." style='position: absolute; opacity: 0;' checked='checked' >".htmlentities($titleval['tech_title'],ENT_COMPAT, "UTF-8")."</div>";
				else
					$option.="<div class='form-group check-duplicate col-md-3 pull-left'><input type='checkbox' name='duplicatetitle[]' id='duplicatetitle[".$titleval['tid']."]' class='form-control icheck-input-radio' value=".$titleval['tid']." style='position: absolute; opacity: 0;'>".htmlentities($titleval['tech_title'],ENT_COMPAT, "UTF-8")."</div>";
			}
			
			echo $option; exit;

		}
		elseif($tech_params['prod_mission_val']=='No' && $tech_params['duplicatenew']=='yes')
		{
			$option="";
			$quotes_obj=new Ep_Quote_Quotes();	
			$tech_title=$quotes_obj->techtitles();
			foreach($tech_title as $titleval){
				if($titleval['integrated']!='yes')
				{
					if($tech_params['typeid']==$titleval['tid'])
						$option.="<div class='form-group check-duplicate col-md-3 pull-left'><input type='checkbox' name='duplicatetitle[]' id='duplicatetitle[".$titleval['tid']."]' class='form-control icheck-input-radio' value=".$titleval['tid']." style='position: absolute; opacity: 0;' checked='checked' >".htmlentities($titleval['tech_title'],ENT_COMPAT, "UTF-8")."</div>";
					else
						$option.="<div class='form-group check-duplicate col-md-3 pull-left'><input type='checkbox' name='duplicatetitle[]' id='duplicatetitle[".$titleval['tid']."]' class='form-control icheck-input-radio' value=".$titleval['tid']." style='position: absolute; opacity: 0;'>".htmlentities($titleval['tech_title'],ENT_COMPAT, "UTF-8")."</div>";
				}
			}
			
			echo $option; exit;

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
	
	 /**function to get the language type name**/
    public function getLanguageName($lang_value)
    {
        $language_array=$this->_arrayDb->loadArrayv2("EP_LANGUAGES", $this->_lang);
        return $language_array[$lang_value];
    }
    /**function to get the country name**/
    public function getCountryName($country_value)
    {
        $country_array=$this->_arrayDb->loadArrayv2("countryList", $this->_lang);
        return $country_array[$country_value];
    }

    /*custom unset session*/
    public function custom_unset(&$array=array(), $key=0) 
	{
		    if(isset($array[$key]))
		    {

		        // remove item at index
		        unset($array[$key]);

		        // 'reindex' array
		        $array = array_values($array);

		        }
    	return $array;
	}


	//Auto quote session for edit/duplicate
	public function autoQuoteSession($quote_id,$action)
	{
		unset($this->quote_creation->create_mission);
		unset($this->quote_creation->tech_mission);
        unset($this->quote_creation->select_missions);
        unset($this->quote_creation->custom);
		unset($this->quote_creation->send_quote);

		$quoteObj=new Ep_Quote_Quotes();
		$techmissionObj=new Ep_Quote_TechMissions();
		$quoteDetails=$quoteObj->getQuoteDetails($quote_id);
		
		
		if($quoteDetails)
		{
			foreach($quoteDetails as $quote)
			{				
				$this->quote_creation->custom['action']=$action;
				if($action=='edit' || $action=='briefing')
				{					
					$this->quote_creation->custom['quote_id']=$quote['identifier'];
					if($quote['sales_review']=='validated' || $quote['sales_review']=='closed')
					{
						$this->quote_creation->custom['create_new_version']='yes';
						$this->quote_creation->custom['version']=($quote['version']+1);
					}
					else if($quote['sales_review']=='not_done')
					{
						$this->quote_creation->custom['version']=$quote['version'];
						$this->quote_creation->custom['create_new_version']='yes';
					}
					else
					{
						$this->quote_creation->custom['version']=($quote['version']);
						unset($this->quote_creation->custom['create_new_version']);	
					}

				}
				else if($action=='duplicate')
				{
					unset($this->quote_creation->custom['quote_id']);
					unset($this->quote_creation->custom['create_new_version']);
					unset($this->quote_creation->custom['version']);
				}
				//step1 session
				$this->quote_creation->create_step1['client_id']=$quote['client_id'];
				
				$client_obj=new Ep_Quote_Client();
				$client_details=$client_obj->getClientDetails($quote['client_id']);
				if($client_details!='NO')
				{
					$this->quote_creation->create_step1['company_name']=$client_details[0]['company_name'];
					$this->quote_creation->create_step1['ca_number']=$client_details[0]['ca_number'];
					$this->quote_creation->create_step1['client_code']=$client_details[0]['client_code'];
					$this->quote_creation->create_step1['twitter_screen_name']=$client_details[0]['twitter_screen_name'];
					$this->quote_creation->create_step1['client_id']=$client_details[0]['identifier'];
				}
				//echo $this->quote_creation->custom['version'].'test';
				/* //get client contact details
				$contact_obj=new Ep_Quote_ClientContacts();
				$contactDetails=$contact_obj->getClientMainContacts($quote['client_id']);
				if($contactDetails!='NO')
				{
					$this->quote_creation->create_step1['client_contact_id']=$contactDetails[0]['identifier'];
					$this->quote_creation->create_step1['client_contact_name']=$contactDetails[0]['first_name'];
					$this->quote_creation->create_step1['client_contact_email']=$contactDetails[0]['email'];
					$this->quote_creation->create_step1['client_contact_phone']=$contactDetails[0]['office_phone'];
					$this->quote_creation->create_step1['job_title']=$contactDetails[0]['job_title'];
				} */			
				
				$this->quote_creation->create_step1['category']=$quote['category'];
				if($quote['category_other'])
					$this->quote_creation->create_step1['category_other']=$quote['category_other'];

				if($quote['websites'])
					$this->quote_creation->create_step1['client_websites']=explode("|",$quote['websites']);
				$this->quote_creation->create_step1['quote_by']=$quote['quote_by'];

				$this->quote_creation->create_step1['currency']=$quote['sales_suggested_currency'];
				$this->quote_creation->create_step1['conversion']=$quote['conversion'];
				$this->quote_creation->create_step1['quote_type']=$quote['quote_type'];
				$this->quote_creation->create_step1['title']=trim(str_replace($this->quote_creation->create_step1['company_name'], "", $quote['title']));	
				$this->quote_creation->create_step1['title']=trim(str_replace(' - v'.$quote['version'], "", $this->quote_creation->create_step1['title']));	
				//step2 session	
				$this->quote_creation->create_step1['quote_title']=$quote['title'];	
				$this->quote_creation->create_step1['contact_client']=$quote['contact_client'];	
				
				
				/*get client contact details*/
				$clientcontact_obj = new Ep_Quote_ClientContacts();
				$clientContacts = $clientcontact_obj->getClientContactsDetails($this->quote_creation->create_step1['client_id']);
				if($clientContacts)
				{
					if(!$this->quote_creation->create_step1['contact_client'])
					{
						foreach($clientContacts as $contact)
						{
							if($contact['main_contact']=='yes')
							{
								$this->quote_creation->create_step1['contact_client']=$contact['identifier'];
							}
						}
					}
				
					$this->quote_creation->create_step1['clientContacts'] = $clientContacts;	
				}
				
				//echo "<pre>";print_r($this->quote_creation->create_step1);exit;
				
				
				/*sales final validation page*/
				$this->quote_creation->create_mission['final_turnover']=$quote['final_turnover'];
				$this->quote_creation->create_mission['final_margin']=$quote['final_margin'];
				$this->quote_creation->create_mission['final_mission_length']=$quote['final_mission_length'];
				$this->quote_creation->create_mission['prod_extra_launch_days']=$quote['prod_extra_launch_days'];
				$this->quote_creation->create_mission['final_mission_length_option']=$quote['final_mission_length_option'];
				$this->quote_creation->send_quote['estimate_sign_percentage']=$quote['estimate_sign_percentage'];										
				$this->quote_creation->create_mission['estimate_sign_date']=$quote['estimate_sign_date'];		
				//getting mission details
				$searchParameters['quote_id']=$quote_id;
				$searchParameters['misson_user_type']='sales';
				$quoteMission_obj=new Ep_Quote_QuoteMissions();
				$missonDetails=$quoteMission_obj->getMissionDetails($searchParameters);

				if($missonDetails)
				{
					$i=0;
					foreach($missonDetails as $quoteMmission)
					{
						$this->quote_creation->create_mission['product'][$i]=$quoteMmission['product'];
						if($quoteMmission['product']=='content_strategy')
						{
						$this->quote_creation->create_mission['unit_price'][$i]=$quoteMmission['unit_price'];
						$this->quote_creation->create_mission['turnover'][$i]=$quoteMmission['turnover'];
						$this->quote_creation->create_mission['margin_percentage'][$i]=$quoteMmission['margin_percentage'];
						$this->quote_creation->create_mission['internal_cost'][$i]=$quoteMmission['cost'];
						$this->quote_creation->create_mission['mission_cost'][$i]=$quoteMmission['cost'];
						$this->quote_creation->create_mission['producttype'][$i]=$quoteMmission['product_type'];
						}
						$this->quote_creation->create_mission['language'][$i]=$quoteMmission['language_source'];
						$this->quote_creation->create_mission['languagedest'][$i]=$quoteMmission['language_dest'];
						$this->quote_creation->create_mission['producttype'][$i]=$quoteMmission['product_type'];
						if($this->quote_creation->create_mission['producttype'][$i]=='autre')
								$this->quote_creation->create_mission['producttypeautre']=TRUE;
						$this->quote_creation->create_mission['producttypeother'][$i]=$quoteMmission['product_type_other'];
						$this->quote_creation->create_mission['nb_words'][$i]=$quoteMmission['nb_words'];
						$this->quote_creation->create_mission['volume'][$i]=$quoteMmission['volume'];

						$this->quote_creation->create_mission['staff_time'][$i]=$quoteMmission['staff_time'];
						$this->quote_creation->create_mission['mission_length'][$i]=$quoteMmission['mission_length'];
						$this->quote_creation->create_mission['mission_length_option'][$i]=$quoteMmission['mission_length_option'];
						$this->quote_creation->create_mission['volume_max'][$i]=$quoteMmission['volume_max'];
						$this->quote_creation->create_mission['delivery_volume_option'][$i]=$quoteMmission['delivery_volume_option'];
						$this->quote_creation->create_mission['tempo_type'][$i]=$quoteMmission['tempo'];
						$this->quote_creation->create_mission['tempo_length'][$i]=$quoteMmission['tempo_length'];
						$this->quote_creation->create_mission['tempo_length_option'][$i]=$quoteMmission['tempo_length_option'];
						
						
						if(!$quoteMmission['oneshot'])
							$quoteMmission['oneshot']='yes';
						
						$this->quote_creation->create_mission['oneshot'][$i]=$quoteMmission['oneshot'];
						$this->quote_creation->create_mission['demande_client'][$i]=$quoteMmission['demande_client'];
						$this->quote_creation->create_mission['duration_dont_know'][$i]=$quoteMmission['duration_dont_know'];
						
						$this->quote_creation->create_mission['comments'][$i]=$quoteMmission['comments'];
						$this->quote_creation->create_mission['mission_identifier'][$i]=$quoteMmission['identifier'];

						$this->quote_creation->create_mission['selected_mission'][$i]=$quoteMmission['sales_suggested_missions'];
						$this->quote_creation->create_mission['free_mission'][$i]=$quoteMmission['free_mission'];
						$this->quote_creation->create_mission['package'][$i]=$quoteMmission['package'];
						$this->quote_creation->create_mission['user_fee'][$i]=$quoteMmission['user_fee'];
						$this->quote_creation->create_mission['team_fee'][$i]=$quoteMmission['team_fee'];
						
						if($quoteMmission['margin_percentage'] && $quoteMmission['product']!='content_strategy')
						{

						$this->quote_creation->create_mission['unit_price'][$i]=$quoteMmission['unit_price'];
						$this->quote_creation->create_mission['turnover'][$i]=$quoteMmission['turnover'];
						$this->quote_creation->create_mission['margin_percentage'][$i]=$quoteMmission['margin_percentage'];
						$this->quote_creation->create_mission['internal_cost'][$i]=$quoteMmission['internal_cost'];
						
						}
						//step3 details
						if($action=='edit')
						{
							$this->quote_creation->create_mission['mission_identifier'][$i]=$quoteMmission['identifier'];							
						}
						else if($action=='duplicate')
						{
							unset($this->quote_creation->create_mission['mission_identifier']);
						}


						$i++;
					}
				}	

				//echo "<pre>"; print_r($this->quote_creation->create_mission); exit;
				/*tech mission details*/
				if($quote['techmissions_assigned']!="")
				{
					$quote_techmission=$quote['techmissions_assigned'];

					$tecchmissoins=explode(',',$quote_techmission);

									
					if(count($tecchmissoins)>0)
					{
						$t=0;
						foreach ($tecchmissoins as  $identifier) 
						{
							$techParameters['identifier']=$identifier;
							$tmissonDetails=$techmissionObj->getTechMissionDetails($techParameters);
							//echo "<pre>";print_r($tmissonDetails);
							if($tmissonDetails)
							{
								
								foreach($tmissonDetails as $techMmission)
								{				
									$this->quote_creation->tech_mission['product'][$t]='tech';
									$this->quote_creation->tech_mission['language'][$t]=$techMmission['language'];
									$this->quote_creation->tech_mission['tech_type'][$t]=$techMmission['title'];
									$this->quote_creation->tech_mission['volume'][$t]=$techMmission['volume'];
									$this->quote_creation->tech_mission['to_perform'][$t]=$techMmission['before_prod'];
									$this->quote_creation->tech_mission['tech_title_id'][$t]=$techMmission['tech_type_id'];
									$this->quote_creation->tech_mission['tech_mission_length'][$t]=$techMmission['delivery_time'];
									$this->quote_creation->tech_mission['tech_mission_length_option'][$t]=$techMmission['delivery_option'];
									$this->quote_creation->tech_mission['volume_max'][$t]=$techMmission['volume_max'];
									$this->quote_creation->tech_mission['delivery_volume_option'][$t]=$techMmission['delivery_volume_option'];
									$this->quote_creation->tech_mission['tempo_type'][$t]=$techMmission['tempo'];
									$this->quote_creation->tech_mission['tempo_length'][$t]=$techMmission['tempo_length'];
									$this->quote_creation->tech_mission['tempo_length_option'][$t]=$techMmission['tempo_length_option'];
											$this->quote_creation->tech_mission['linked_to_prod'][$t]=$techMmission['prod_linked'];
									if($this->quote_creation->tech_mission['linked_to_prod'][$t])
										$this->quote_creation->tech_mission['prod_mission_selected'][$t]='Yes';
									else
										$this->quote_creation->tech_mission['prod_mission_selected'][$t]='No';
											
									/* if($this->quote_creation->tech_mission['tech_title_id'][$t])
									{
									$tecch_details=$quoteObj->techtitleDetails($this->quote_creation->tech_mission['tech_title_id'][$t]);
									$this->quote_creation->tech_mission['mission_cost'][$t]=($tecch_details[0]['cost']/$tecch_details[0]['delivery_time'])*$this->quote_creation->tech_mission['tech_mission_length'][$t];
									} */	
									$this->quote_creation->tech_mission['mission_cost'][$t]=$techMmission['cost'];		
									$this->quote_creation->tech_mission['free_mission'][$t]=$techMmission['free_mission'];
									if(!$techMmission['oneshot'])
										$techMmission['oneshot']='yes';

									$this->quote_creation->tech_mission['package'][$t]=$techMmission['package'];
									$this->quote_creation->tech_mission['user_fee'][$t]=$techMmission['user_fee'];
									$this->quote_creation->tech_mission['team_fee'][$t]=$techMmission['team_fee'];
									
									$this->quote_creation->tech_mission['tech_oneshot'][$t]=$techMmission['oneshot'];
									if($techMmission['unit_price'])
									{
										$this->quote_creation->tech_mission['unit_price'][$t]=$techMmission['unit_price'];
										$this->quote_creation->tech_mission['turnover'][$t]=$techMmission['turnover'];
										$this->quote_creation->tech_mission['margin_percentage'][$t]=$techMmission['margin_percentage'];
										$this->quote_creation->tech_mission['internal_cost'][$t]=$techMmission['cost'];
									}
									
									//$this->quote_creation->tech_mission['missions_selected'][$i]=$quoteMmission['sales_suggested_missions'];
									$this->quote_creation->tech_mission['mission_identifier'][$t]=$techMmission['identifier'];
									//step3 details
									if($action=='edit')
									{
										$this->quote_creation->tech_mission['mission_identifier'][$t]=$techMmission['identifier'];							
									}
									else if($action=='duplicate')
									{
										unset($this->quote_creation->create_mission['mission_identifier']);
									}

									
								}
							}
							$t++;
							
						}
					}
				}	

				//echo "<pre>"; print_r($this->quote_creation->tech_mission); exit;

				//final step details
				$this->quote_creation->send_quote['sales_comment']=$quote['sales_comment'];
				$this->quote_creation->send_quote['client_email_text']=$quote['client_email_text'];	
				//$this->quote_creation->send_quote['sales_delivery_time']=$quote['sales_delivery_time'];
				//$this->quote_creation->send_quote['sales_delivery_time_option']=$quote['sales_delivery_time_option'];
				$this->quote_creation->send_quote['client_know']=$quote['client_know'];
				$this->quote_creation->send_quote['urgent']=$quote['urgent'];
				$this->quote_creation->send_quote['urgent_comments']=$quote['urgent_comments'];
				$this->quote_creation->send_quote['prod_review']=$quote['prod_review'];
				$this->quote_creation->send_quote['skip_prod_comments']=$quote['skip_prod_comments'];
				//$this->quote_creation->send_quote['market_team_sent']=$quote['market_team_sent'];
				//$this->quote_creation->send_quote['from_platform']=$quote['from_platform'];
				$this->quote_creation->send_quote['quote_send_team']=$quote['quote_send_team'];
				$this->quote_creation->send_quote['sales_review']=$quote['sales_review'];
				$this->quote_creation->send_quote['client_overview']=$quote['client_overview'];
				$this->quote_creation->send_quote['do_signer']=$quote['do_signer'];
				$this->quote_creation->send_quote['origin_contact']=$quote['origin_contact'];
				$this->quote_creation->send_quote['client_appoinment']=$quote['client_appoinment'];
				
				$this->quote_creation->create_mission['showtheorical']=$quote['showtheorical'];
				//NEW FIELDS
				$client_aims=explode(",",$quote['client_aims']);
				$this->quote_creation->send_quote['client_aims']=$client_aims;

				$client_prio=explode(",",$quote['client_prio']);

				if(count($client_aims)>0)
				{				
					
					foreach($client_aims as $i=>$aim)
					{
						//echo 'priority_'.$aim;//."--".$quote['client_prio'][$i];
						$this->quote_creation->send_quote['priority_'.$aim]=$client_prio[$i];
					}
				}	
				$this->quote_creation->send_quote['client_aims_comments']=$quote['client_aims_comments'];
				$this->quote_creation->send_quote['content_ordered_agency']=$quote['content_ordered_agency'];
				$this->quote_creation->send_quote['agency']=$quote['agency'];
				$this->quote_creation->send_quote['agency_name']=$quote['agency_name'];
				$this->quote_creation->send_quote['client_internal_team']=$quote['client_internal_team'];
				$this->quote_creation->send_quote['client_know_writers']=$quote['client_know_writers'];				
				$this->quote_creation->send_quote['volume_option']=$quote['volume_option'];
				$this->quote_creation->send_quote['volume_option_multi']=$quote['volume_option_multi'];
				$this->quote_creation->send_quote['volume_every']=$quote['volume_every'];
				$this->quote_creation->send_quote['budget_marketing']=$quote['budget_marketing'];
				$this->quote_creation->send_quote['budget']=$quote['budget'];
				$this->quote_creation->send_quote['budget_currency']=$quote['budget_currency'];

				$this->quote_creation->send_quote['estimate_sign_percentage']=$quote['estimate_sign_percentage'];
				$this->quote_creation->send_quote['estimate_sign_date']=$quote['estimate_sign_date'];
				$this->quote_creation->send_quote['estimate_sign_comments']=$quote['estimate_sign_comments'];
				
				$this->quote_creation->send_quote['brief_email_notify']=$quote['brief_email_notify'];
				
				
				//Quote documents added to sesssion
				$files = "";
				$documents_path =array_filter(explode("|",$quote['documents_path']));
				$documents_name =array_filter(explode("|",$quote['documents_name']));
				$k =0;
				$jsFilerFiles=array();
				foreach($documents_path as $row)
				{
					if(file_exists($this->quote_documents_path.$documents_path[$k]) && !is_dir($this->quote_documents_path.$documents_path[$k]))
					{
						$files .= '<div class="topset2"><a href="/quote-new/download-document?type=quote&quote_id='.$quote_id.'&index='.$k.'">'.$documents_name[$k].'</a><a class="delete" rel="'.$k.'_'.$quote_id.'"> <i class="glyphicon glyphicon-remove-circle"></i></a></div>';
						
						$pathinfo = pathinfo($documents_path[$k]);
						//print_r($pathinfo);
						$jsFilerFiles[$k]['id']=$quote_id;
						$jsFilerFiles[$k]['name']=$pathinfo['basename'];
						$jsFilerFiles[$k]['type']="application/".$pathinfo['extension'];
						$jsFilerFiles[$k]['size']=filesize($this->quote_documents_path.$documents_path[$k]);
						$jsFilerFiles[$k]['ext']=$pathinfo['extension'];
						
					}					
					$k++;
				}
				$this->quote_creation->send_quote['documents'] = $files;
				$this->quote_creation->send_quote['documents_files']=json_encode($jsFilerFiles);

	

				//echo "<pre>";print_r($this->quote_creation->send_quote);exit;
			}
		}

		//echo "<pre>";print_r($this->quote_creation->create_step1);exit;
	}

	public function formatCommentDetails($commentDetails)
    {
        $ticket=new Ep_Message_Ticket();
        $user_identifier=$this->adminLogin->userId;
        $cnt=0;
        foreach($commentDetails as $details)
        {
			$bo_pic_path=$this->_config->path->fo_path.'/profiles/bo/'.$details['user_id'].'/logo.jpg';
			$commentDetails[$cnt]['profile_pic']=  $bo_pic_path;
			$commentDetails[$cnt]['profile_name']= $ticket->getUserName($details['user_id']);   
			$commentDetails[$cnt]['time']= time_ago($details['action_at']);
           if($user_identifier==$details['user_id'])
              $commentDetails[$cnt]['edit']='yes';

           $cnt++;
        }   
        return $commentDetails;
    }
	/*History Mission details popup*/
	function historyMissionDetailsAction()
	{
		$hmission_obj=new Ep_Quote_HistoryQuoteMissions();
		
		$mission_params=$this->_request->getParams();		
		$mission_id=$mission_params['mission_id'];
		$from_site=$mission_params['from_site'];
		
		
		
		if($mission_id && $from_site!='fr')
		{
			$searchParameters['mission_id']=$mission_id;
			$historyMission=array();		
			//$showTheorical=$this->quote_creation->create_mission['showtheorical'];
			$missionDetails=$hmission_obj->getMissionDetails($searchParameters,1);	
			if($missionDetails)
			{
				foreach($missionDetails as $mission)
				{
					$historyMission=$mission;
					$historyMission['quote_id']=$mission['quote_id'];
					$historyMission['signaturedate']=date('d-m-Y',strtotime($mission['signaturedate']));
					$historyMission['product']=$mission['product'];
					$historyMission['product_name']=$this->product_array[$mission['product']];
					$historyMission['language']=$mission['language_source'];
					$historyMission['language_name']=$this->getLanguageName($mission['language_source']);
					$historyMission['languagedest']=$mission['language_dest'];
					$historyMission['languagedest_name']=$this->getLanguageName($mission['language_dest']);

					$historyMission['product_type']=$mission['product_type'];
					$historyMission['product_type_other']=$mission['product_type_other'];
					$historyMission['product_type_name']=$this->producttype_array[$mission['product_type']];
					$historyMission['nb_words']=$mission['nb_words'];
					$historyMission['volume']=$mission['volume'];
					
					/*added w.r.t Tempo*/
					$historyMission['volume_max']=$mission['volume_max'];
					$historyMission['mission_length']=$mission['mission_length'];
					$historyMission['mission_length_option']=$mission['mission_length_option'];
					$historyMission['mission_length_option_text']=$this->duration_array[$mission['mission_length_option']];$historyMission['tempo_text']=$this->tempo_array[$mission['tempo']];				
					$historyMission['delivery_volume_option_text']=$this->volume_option_array[$mission['delivery_volume_option']];
					$historyMission['tempo_length']=$mission['tempo_length'];
					$historyMission['tempo_length_option_text']=$this->duration_array[$mission['tempo_length_option']];
					$historyMission['oneshot']=$mission['oneshot'];
					
					$client_obj=new Ep_Quote_Client();
							$clientDetails=$client_obj->getClientDetails($mission['client_id']);
							if($clientDetails!='NO')
							{
								$client_info=$clientDetails[0];	
								
								$company_name=$client_info['company_name'];
							}	
					$historyMission['company_name']	=$company_name;
					$historyMission['staff_time_option_text']=$this->duration_array[$mission['staff_time_option']];
					
					//getting prod mission details if any
					$prod_mission_obj=new Ep_Quote_ProdMissions();
					$searchParameters['quote_mission_id']=$mission_id;
					$prod_details=$prod_mission_obj->getProdMissionDetails($searchParameters);
					//echo "<pre>";print_r($prod_details);exit;
					if($prod_details)
					{
						foreach($prod_details as $prodMission)
						{
							if($prodMission['product']=='redaction' || $prodMission['product']=='translation')
							{
								$historyMission['writer_staff']=$prodMission['staff'];
								$historyMission['writing_cost']=$prodMission['cost'];
							}
							if($prodMission['product']=='proofreading')
							{
								$historyMission['corrector_staff']=$prodMission['staff'];
								$historyMission['correcting_cost']=$prodMission['cost'];
							}
							if($prodMission['product']=='autre')
							{								
								$historyMission['other_cost']=$prodMission['cost'];
							}
						}
					}
					
				}
				$this->_view->historyMission=$historyMission;
				$this->_view->create_step1=$this->quote_creation->create_step1;
				//echo "<pre>";print_r($historyMission);
			}
		}
		else if($mission_id && $from_site=='fr')
		{
			/*build service link to get HOQ prices from FR*/
			$service_link=$this->web_service_history_details_link;
			$Parameters['mission_id']=$mission_id;
			//get details from FR with service link
			$historyMission=$this->webHttpClient($service_link,$Parameters);
			$this->_view->historyMission=$historyMission;
			$this->_view->from_site=$from_site;
		}	
		
		$this->render("history-mission-details");
	}



	function managerNotifyAction()
	{
		$quote_obj=new Ep_Quote_Quotes();	
		$notifyparams=$this->_request->getParams();		

		if($notifyparams['manager_list'] || $notifyparams['manager_list']=="")
		{
	
			$quotes_manager['brief_email_notify']=implode(',',$notifyparams['manager_list']);

			$quoteIdentifier=$notifyparams['quote_id'];
			if($quoteIdentifier)
			{
				if($notifyparams['manager_list']=="")/*added if no share list is empty after sharing*/
				{
					$quotes_manager['tec_review']='auto_skipped';
					$quotes_manager['seo_review']='auto_skipped';
					$quotes_manager['prod_review']='auto_skipped';
				}
				
				$quote_obj->updateQuote($quotes_manager,$quoteIdentifier);
			}
				
			$this->quote_creation->send_quote['brief_email_notify']=$quotes_manager['brief_email_notify'];
			$briefmail=explode(',',$this->quote_creation->send_quote['brief_email_notify']);
			$this->_view->brief_mail=$briefmail;

			 	$userDetail_obj=new Ep_Quote_Quotes();
				$manageDetails=$userDetail_obj->getManagersList();

				foreach($manageDetails as $managers)
				{
					if($managers['first_name']=="")
					{
					$managersDetails[$managers['identifier']]= $managers['email'];
					}
					else
					{
					$managersDetails[$managers['identifier']]= frenchCharsToEnglish($managers['first_name']).' '.frenchCharsToEnglish($managers['last_name']);
					}
				}
				$selected="";
				foreach ($managersDetails as $key => $value) {
					if(in_array($key, $briefmail))
					{
						$selected.="<option selected=selected value='".$key."'>".$value."</option>";
					}
					else
					{
						$selected.="<option value='".$key."'>".$value."</option>";	
					}
				}
			echo $selected;
			exit;
		}


	}

	function managerListAction()
	{
		$userDetail_obj=new Ep_Quote_Quotes();
		$manageDetails=$userDetail_obj->getManagersList();
		$managersDetails="<script>
		$(function(){
			$('#manager_list').multiselect({
								includeSelectAllOption: true,
								nonSelectedText:'Ask to <span class=glyphicon glyphicon-share-alt></span>',
								numberDisplayed: 10,
								buttonWidth:'350px',
								maxHeight: 200,
								enableCaseInsensitiveFiltering: true
							});
							$('#manager_list_chzn').addClass('btn-success');
		});
		
		</script><select id='manager_list' class='form-control validate[required] pull-right' multiple='multiple' data-placeholder='Select Managers' name='manager_list[]'>";
		foreach($manageDetails as $managers)
				{
					if($managers['first_name']=="")
					{
					$managersDetails.="<option value=".$managers['identifier'].">".$managers['email']."</option>";
					}
					else
					{
					$managersDetails.="<option value=".$managers['identifier'].">".frenchCharsToEnglish($managers['first_name']).' '.frenchCharsToEnglish($managers['last_name'])."</option>";
					}
				}
				$managersDetails.="</select>";
		echo $managersDetails;
	}



	function deleteDocumentAction()
	{
			if($this->_request->isPost() && $_SERVER['HTTP_X_REQUESTED_WITH']=='XMLHttpRequest')
			{
						$parmas = $this->_request->getParams();
						$explode_identifier = explode("_",$parmas['identifier']);
						$offset = $explode_identifier[0];
						$identifier = $explode_identifier[1];
						$quoteObj=new Ep_Quote_Quotes();
						$result=$quoteObj->getQuoteDetails($identifier);
						$sales_paths = explode("|",$result[0]['documents_path']);
						$sales_names = explode("|",$result[0]['documents_name']);

						unlink($this->quote_documents_path.$sales_paths[$offset]);
						unset($sales_paths[$offset]);
						unset($sales_names[$offset]);
						$data['documents_path']	= implode("|",$sales_paths);
						$data['documents_name']	= implode("|",$sales_names);
						$quoteObj->updateQuote($data,$identifier);
						
						$jsFilerFiles=array();
						foreach($sales_paths as $k=>$file)
						{
							$file_path=$this->quote_documents_path.$sales_paths[$k];
							if(file_exists($file_path) && !is_dir($this->quote_documents_path.$sales_paths[$k]))
							{
								
								$file_name=basename($file);
								$ofilename = pathinfo($file_path);
								$files .= '<div class="topset2"><a href="/quote-new/download-document?type=quote&quote_id='.$identifier.'&index='.$k.'">'.$file_name.'</a><a class="delete" rel="'.$k.'_'.$identifier.'"> <i class="glyphicon glyphicon-remove-circle"></i></a></div>';	
								
								$pathinfo = pathinfo($sales_paths[$k]);
								//print_r($pathinfo);
								$jsFilerFiles[$k]['id']=$identifier;
								$jsFilerFiles[$k]['name']=$pathinfo['basename'];
								$jsFilerFiles[$k]['type']="application/".$pathinfo['extension'];
								$jsFilerFiles[$k]['size']=filesize($this->quote_documents_path.$sales_paths[$k]);
								$jsFilerFiles[$k]['ext']=$pathinfo['extension'];
							}
						}
						$this->quote_creation->send_quote['documents_files']=json_encode($jsFilerFiles);	
						$this->quote_creation->send_quote['documents']=$files;
						
						echo $files;
                                   				
			}
			
		
	}

	//download quote and mission documents
	function downloadDocumentAction()
	{
		$request = $this->_request->getParams();
		$this->_redirect("/BO/download-quote.php?type=".$request['type']."&mission_id=".$request['mission_id']."&index=".$request['index']."&quote_id=".$request['quote_id']."&logid=".$request['logid']."&filename=".$request['filename']);
	}

	/*update quote mission function*/
	public function quoteMissoinUpdate($quoteMissionId,$offsetid)
	{
		$quoteMission_obj=new Ep_Quote_QuoteMissions();
		$id=$offsetid;
		
				$quoteMission_data['quote_id']=$this->quote_creation->custom['quote_id'];
						$quoteMission_data['product']=$this->quote_creation->create_mission['product'][$id];
						$quoteMission_data['product_type']=$this->quote_creation->create_mission['producttype'][$id];
						if($quoteMission_data['product_type']=='autre')
							$quoteMission_data['product_type_other']=$this->quote_creation->create_mission['producttypeother'][$id];
						else
							$quoteMission_data['product_type_other']=NULL;


						$quoteMission_data['category']=$this->quote_creation->create_step1['category'];
						if($quoteMission_data['product']=='translation')
							$quoteMission_data['language_dest']=$this->quote_creation->create_mission['languagedest'][$id];
						if($quoteMission['product']!='auture')
						{
							$quoteMission_data['language_source']=$this->quote_creation->create_mission['language'][$id];
							$quoteMission_data['nb_words']=$this->quote_creation->create_mission['nb_words'][$id];
							$quoteMission_data['volume']=$this->quote_creation->create_mission['volume'][$id];
						}	
						$quoteMission_data['comments']=$this->quote_creation->create_mission['comments'][$id];
						
						$quoteMission_data['created_by']=$this->quote_creation->create_step1['quote_by'];
						
						/*added w.r.t tempo*/
						$quoteMission_data['mission_length']=$this->quote_creation->create_mission['mission_length'][$id];
						$quoteMission_data['mission_length_option']=$this->quote_creation->create_mission['mission_length_option'][$id];						
						$quoteMission_data['volume_max']=$this->quote_creation->create_mission['volume_max'][$id];
						$quoteMission_data['delivery_volume_option']=$this->quote_creation->create_mission['delivery_volume_option'][$id];
						$quoteMission_data['tempo']=$this->quote_creation->create_mission['tempo_type'][$id];
						$quoteMission_data['tempo_length']=$this->quote_creation->create_mission['tempo_length'][$id];
						$quoteMission_data['tempo_length_option']=$this->quote_creation->create_mission['tempo_length_option'][$id];
						$quoteMission_data['oneshot']=$this->quote_creation->create_mission['oneshot'][$id];
						$quoteMission_data['demande_client']=$this->quote_creation->create_mission['demande_client'][$id];
						$quoteMission_data['duration_dont_know']=$this->quote_creation->create_mission['duration_dont_know'][$id];
						
						if($quoteMission_data['product']=='content_strategy')
						{
							$quoteMission_data['cost']=$this->quote_creation->create_mission['internal_cost'][$id];
							$quoteMission_data['unit_price']=$this->quote_creation->create_mission['unit_price'][$id];
							$quoteMission_data['margin_percentage']=$this->quote_creation->create_mission['margin_percentage'][$id];							
							$quoteMission_data['internal_cost']=$this->quote_creation->create_mission['internal_cost'][$id];	
							$quoteMission_data['product_type']=$this->quote_creation->create_mission['producttype'][$id];
							$quoteMission_data['nb_words']=0;
							$quoteMission_data['turnover']=$this->quote_creation->create_mission['turnover'][$id];
							$quoteMission_data['misson_user_type']='seo';
						}
						else
						{
							$quoteMission_data['misson_user_type']='sales';
							if($this->quote_creation->create_mission['internal_cost'][$id])
							$quoteMission_data['cost']=$this->quote_creation->create_mission['internal_cost'][$id];
						}
						if($this->quote_creation->custom['create_new_version']=='yes' && $this->quote_creation->custom['quote_id'])
						{
							$version=$this->quote_creation->custom['version'];
						}
						else
						{
						$version=1;	
						}
						
						$quoteMission_data['version']=$version;
						$quoteMission_data['is_new_quote']=1;
						

						if($quoteMissionId)
						{
							if(!$this->quote_creation->custom['create_new_version'])
							$quoteMission_data['updated_at']=date("Y-m-d H:i:s");
							$quoteMission_obj->updateQuoteMission($quoteMission_data,$quoteMissionId);
						}
						else
						{
							$quoteMission_data['created_at']=date("Y-m-d H:i:s");
							$quoteMission_obj->insertQuoteMission($quoteMission_data);
							$quoteMissionIdentifier=$quoteMission_obj->getIdentifier();

							$this->quote_creation->create_mission['mission_identifier'][$id]=$quoteMissionIdentifier;
						}


		

	}	


	function techMissionUpdate($techeMissionId,$offsetid)
	{
		$quote_obj=new Ep_Quote_Quotes();	
		$techObj=new Ep_Quote_TechMissions();
		$id=$offsetid;
			$tech_data['language']=$this->quote_creation->tech_mission['language'][$id];
			$tech_data['title']=$this->quote_creation->tech_mission['tech_type'][$id];
			$tech_data['delivery_time']=$this->quote_creation->tech_mission['tech_mission_length'][$id];
			$tech_data['delivery_option']=$this->quote_creation->tech_mission['tech_mission_length_option'][$id];
			$tech_data['volume_max']=$this->quote_creation->tech_mission['volume_max'][$id];
			$tech_data['delivery_volume_option']=$this->quote_creation->tech_mission['delivery_volume_option'][$id];
			$tech_data['tempo_length']=$this->quote_creation->tech_mission['tempo_length'][$id];
			$tech_data['tempo_length_option']=$this->quote_creation->tech_mission['tempo_length_option'][$id];
			if($this->quote_creation->tech_mission['tech_oneshot'][$id])
			$tech_data['oneshot']=$this->quote_creation->tech_mission['tech_oneshot'][$id];

			$tech_data['cost']=$this->quote_creation->tech_mission['internal_cost'][$id];
			//$tech_data['comments']=$this->quote_creation->tech_mission['comments'][$t];
			$tech_data['currency']=$this->quote_creation->create_step1['currency'];
			
			$tech_data['tech_type_id']=$this->quote_creation->tech_mission['tech_title_id'][$id];
			$tech_data['before_prod']=$this->quote_creation->tech_mission['to_perform'][$id];
			$tech_data['volume']=$this->quote_creation->tech_mission['volume'][$id];
			if($this->quote_creation->custom['create_new_version']=='yes' && $this->quote_creation->custom['quote_id'])
						{
							$version=$this->quote_creation->custom['version'];
						}
						else
						{
						$version=1;	
						}
			$tech_data['prod_linked']=$this->quote_creation->tech_mission['linked_to_prod'][$id];
			$tech_data['version']=$version;
			$tech_data['is_new_quote']=1;	
			$tech_data['include_final']='yes';
			//echo $id."<pre>"; print_r($tech_data); exit;
			if($techeMissionId=="")
			{
				$tech_data['created_at']=date("Y-m-d H:i:s");
				$tech_data['created_by']=$this->adminLogin->userId;
				$techObj->insertTechMission($tech_data);
				$this->quote_creation->tech_mission['mission_identifier'][$id]=$techObj->getIdentifier();

				if(count($this->quote_creation->tech_mission['mission_identifier'])>0)
				{
				$k=0;
				$techasigned="";
				foreach($this->quote_creation->tech_mission['mission_identifier'] as $key=>$val)
				{
					if($k==0)
						$techasigned .=$val;
					else
						$techasigned .=','.$val;
					$k++;

				}
				$margin_data['techmissions_assigned']=$techasigned;	
				}			
						
			$quote_obj->updateQuote($margin_data,$this->quote_creation->custom['quote_id']);
			}
			else
			{
				$techObj->updateTechMission($tech_data,$this->quote_creation->tech_mission['mission_identifier'][$id]);
			}
	
	}


	function quotesMissionVersion($quoteMissionId,$mission)
	{
		//echo "Test".$quoteMissionId.$mission; exit;
		$quoteMission_obj=new Ep_Quote_QuoteMissions();

			if($quoteMissionId && $mission=='quotemission')
			{
				/*versioning */
				$quoteMission_obj->insertMissionVersion($quoteMissionId);

				//versioning Prod Missions
				$prodObj=new Ep_Quote_ProdMissions();
				$prodParams['quote_mission_id']=$quoteMissionId;
				$prodMissionDetails=$prodObj->getProdMissionDetails($prodParams);
				if($prodMissionDetails)
				{
					foreach($prodMissionDetails as $prodMission)
					{
						$prodMissionId=$prodMission['identifier'];
						$prodObj->insertMissionVersion($prodMissionId);

						$version=$this->quote_creation->custom['version']-1;
						$update_prod['version']=$version;
						$prodObj->updateProdMission($update_prod,$prodMissionId);
					}
				}
			}

			if($mission=='techmission')
			{
				$techMissionObj=new Ep_Quote_TechMissions();
				
					if($quoteMissionId)
					{															
							$techMissionId=$quoteMissionId;
							$techMissionObj->insertMissionVersion($techMissionId);	
					}	

			}
	}



	//Delete mission versions details
	function deletedMissionVersions($quote_id,$version=NULL,$type=NULL)
	{							
		$quoteMission_obj=new Ep_Quote_QuoteMissions();
		$deletedMissionVersions=$quoteMission_obj->getDeletedMissionVersionDetails($quote_id,$version,$type);
		if(!$deletedMissionVersions)
			$deletedMissionVersions=array();

		//getting mission details showing current version deleted missions of final stage too
		if(!$type)
		{
			$searchParameters['quote_id']=$quote_id;
			$searchParameters['include_final']='no';

			$quoteMission_obj=new Ep_Quote_QuoteMissions();
			$missonDetails=$quoteMission_obj->getMissionDetails($searchParameters);
			if(!$missonDetails)
				$missonDetails=array();

			$deletedMissionVersions=array_merge($deletedMissionVersions,$missonDetails);
		}	


		//echo "<pre>";print_r($deletedMissionVersions);exit;

		if($deletedMissionVersions &&  count($deletedMissionVersions)>0)		
		{
			$d=0;
			foreach($deletedMissionVersions as $dmission)
			{
				$deletedMissionVersions[$d]['product_name']=$this->product_array[$dmission['product']];			
				$deletedMissionVersions[$d]['language_source_name']=$this->getLanguageName($dmission['language_source']);
				$deletedMissionVersions[$d]['product_type_name']=$this->producttype_array[$dmission['product_type']];
				if($mission['language_dest'])
					$deletedMissionVersions[$d]['language_dest_name']=$this->getLanguageName($dmission['language_dest']);

				

				$deletedMissionVersions[$d]['comment_time']=time_ago($dmission['created_at']);

				$prodMissionObj=new Ep_Quote_ProdMissions();
				
				$prodMissionDetails=$prodMissionObj->getProdVersionCostDetails($dmission['identifier'],$version);

				if(!$prodMissionDetails)
				{
					$prodParams['quote_mission_id']=$dmission['identifier'];
					$prodMissionDetails=$prodMissionObj->getProdMissionDetails($prodParams);
				}
				

				if($prodMissionDetails)
				{					
					$internalcost_details='';
					$staff_time=array();

					foreach($prodMissionDetails as $prodMission)
					{						
						$internalcost_details.=$this->seo_product_array[$prodMission['product']]. " : ".zero_cut($prodMission['cost'],2)." &".$prodMission['currency'].";<br>";
					}
					$deletedMissionVersions[$d]['internalcost_details']=$internalcost_details;

					$deletedMissionVersions[$d]['prod_mission_details']=$prodMissionDetails;	
				}

				if($deletedMissionVersions[$d]['turnover']<=0)		
					$deletedMissionVersions[$d]['turnover']=($dmission['volume']*$dmission['unit_price']);

				$d++;
			}
		}

		return $deletedMissionVersions;
	}

	/*prod Mission details popup*/
	function prodMissionEditPopupAction()
	{

		/*configure values tempos*/
		 	$this->_view->tempo_fix=$this->configval['tempo_fix'];
			$this->_view->tempo_fix_days=$this->configval['tempo_fix_days'];
			$this->_view->tempo_max=$this->configval['tempo_max'];
			$this->_view->tempo_max_days=$this->configval['tempo_max_days'];
			$this->_view->oneshot_max_writers=$this->configval['oneshot_max_writers'];
			/*config product array*/
			$oneshot_product['descriptif_produit']=$this->configval['oneshot_max_words_descriptif_produit'];
			$oneshot_product['article_de_blog']=$this->configval['oneshot_max_words_article_de_blog'];
			$oneshot_product['news']=$this->configval['oneshot_max_words_news'];
			$oneshot_product['guide']=$this->configval['oneshot_max_words_guide'];
			$oneshot_product['article_seo']=$this->configval['oneshot_max_words_article_seo'];
			
			$this->_view->oneshot_product=$oneshot_product;
			$qmission_obj=new Ep_Quote_QuoteMissions();
			
			$mission_params=$this->_request->getParams();		
			$mission_id=$mission_params['id'];
			$this->_view->offsetid=$mission_params['offset'];
			if($mission_id)
			{
				
				$searchParameters['mission_id']=$mission_id;
				$quoteMission=array();		
				$missionDetails=$qmission_obj->getMissionDetails($searchParameters);	
				if($missionDetails)
				{
					foreach($missionDetails as $mission)
					{
						$quoteMission=$mission;
						$quoteMission['identifier']=$mission['identifier'];
						$quoteMission['product']=$mission['product'];
						$quoteMission['product_name']=$this->product_array[$mission['product']];
						$quoteMission['language']=$mission['language_source'];
						$quoteMission['language_name']=$this->getLanguageName($mission['language_source']);
						$quoteMission['languagedest']=$mission['language_dest'];
						$quoteMission['languagedest_name']=$this->getLanguageName($mission['language_dest']);

						$quoteMission['product_type']=$mission['product_type'];
						$quoteMission['product_type_other']=$mission['product_type_other'];
						$quoteMission['product_type_name']=$this->producttype_array[$mission['product_type']];
						$quoteMission['nb_words']=$mission['nb_words'];
						$quoteMission['volume']=$mission['volume'];
						$quoteMission['currency']=$this->quote_creation->create_step1['currency'];
						
						/*added w.r.t Tempo*/
						$quoteMission['volume_max']=$mission['volume_max'];
						$quoteMission['mission_length']=$mission['mission_length'];
						$quoteMission['mission_length_option']=$mission['mission_length_option'];
						$quoteMission['mission_length_option_text']=$this->duration_array[$mission['mission_length_option']];
						$quoteMission['tempo_text']=$this->tempo_array[$mission['tempo']];				
						$quoteMission['delivery_volume_option_text']=$this->volume_option_array[$mission['delivery_volume_option']];
						$quoteMission['tempo_length']=$mission['tempo_length'];
						$quoteMission['tempo_length_option_text']=$this->duration_array[$mission['tempo_length_option']];
						$quoteMission['oneshot']=$mission['oneshot'];
						
						$quoteMission['staff_time_option_text']=$this->duration_array[$mission['staff_time_option']];
						$quoteMission['sales_suggested_missions']=$mission['sales_suggested_missions'];
						//getting prod mission details if any
						$prod_mission_obj=new Ep_Quote_ProdMissions();
						$searchParameters['quote_mission_id']=$mission_id;
						$prod_details=$prod_mission_obj->getProdMissionDetails($searchParameters);
						//echo "<pre>";print_r($prod_details);exit;
						if($prod_details)
						{
							foreach($prod_details as $prodMission)
							{
								if($prodMission['product']=='redaction' || $prodMission['product']=='translation')
								{
									$quoteMission['writer_staff_identifier']=$prodMission['identifier'];
									$quoteMission['writer_staff']=$prodMission['staff'];
									$quoteMission['writing_cost']=$prodMission['cost'];
								}
								if($prodMission['product']=='proofreading')
								{
									$quoteMission['corrector_staff_identifier']=$prodMission['identifier'];
									$quoteMission['corrector_staff']=$prodMission['staff'];
									$quoteMission['correcting_cost']=$prodMission['cost'];
								}
								if($prodMission['product']=='autre')
								{	
									$quoteMission['other_cost_identifier']=$prodMission['identifier'];							
									$quoteMission['other_cost']=$prodMission['cost'];
									$quoteMission['other_staff']=$prodMission['staff'];
								}
							}
						}

						$log_obj=new Ep_Quote_QuotesLog();
						$search_param['mission_id']=$mission['identifier'];
						$comment_list=$log_obj->getLogs($search_param);
						if(count($comment_list)>0)
						{
							$comments_display="";
							foreach($comment_list as $commentsVal)
							{
								$comments_display .='<div class="col-md-12 separator"><img width="30" height="30" src="'.$this->_config->path->fo_path.'/profiles/bo/'.$commentsVal['user_id'].'/logo.jpg" class="member-avatar"><span><strong>'.$commentsVal['first_name']." ".$commentsVal['last_name'].'</strong> <span>'.$commentsVal['comments'].'</span> '.time_ago($commentsVal['action_at']).'</span> </div>';

							}

							$quoteMission['logcomments']=$comments_display;

						}


						
					}



					$this->_view->volume_option=json_encode($volume_op);
					$this->_view->quoteMission=$quoteMission;
					$this->_view->create_step1=$this->quote_creation->create_step1;
					
				}
			}		
			
		$this->render("prod-mission-edit-popup");
	}


	function tempoAdjustUpdateAction()
	{
		
		$quotesparams=$this->_request->getParams();
		$quoteMission_obj=new Ep_Quote_QuoteMissions();
		$prod_mission_obj=new Ep_Quote_ProdMissions();
	//	echo "<pre>"; print_r($quotesparams); exit;
		$mission_id=$quotesparams['mission_id'];
		$offsetid=array_search($mission_id,$this->quote_creation->create_mission['mission_identifier']);
		/*tempo change if one shot no*/
		if($quotesparams['taction']=='tempo')
		{
						
			if($mission_id)
			{
				//echo $mission_id;
				$this->quote_creation->create_mission['volume_max'][$offsetid]=$quotesparams['volume_option'];
				$this->quote_creation->create_mission['delivery_volume_option'][$offsetid]=$quotesparams['tempo_volume_option'];
				$this->quote_creation->create_mission['tempo_type'][$offsetid]=$quotesparams['tempo_option'];
				$this->quote_creation->create_mission['tempo_length'][$offsetid]=$quotesparams['tempo_length'];
				$this->quote_creation->create_mission['tempo_length_option'][$offsetid]=$quotesparams['tempo_duration_option'];

				$mission_data['volume_max']=$this->quote_creation->create_mission['volume_max'][$offsetid];
				$mission_data['tempo']=$this->quote_creation->create_mission['tempo_type'][$offsetid];
				$mission_data['delivery_volume_option']=$this->quote_creation->create_mission['delivery_volume_option'][$offsetid];
				$mission_data['tempo_length']=$this->quote_creation->create_mission['tempo_length'][$offsetid];
				$mission_data['tempo_length_option']=$this->quote_creation->create_mission['tempo_length_option'][$offsetid];
				
				echo $mission_data['volume_max'].' '.$this->tempo_array[$mission_data['tempo']].' '.$this->volume_option_array[$mission_data['delivery_volume_option']].' '.$mission_data['tempo_length'].' '.$this->duration_array[$mission_data['tempo_length_option']];
					
			}

		}
		/*volume change if one shot yes*/
		elseif($quotesparams['taction']=='volume')
		{

			if($mission_id)
			{
				$this->quote_creation->create_mission['volume'][$offsetid]=$quotesparams['value'];

				$nbwords=$this->quote_creation->create_mission['nb_words'][$offsetid];

				$calculateVal=$this->quote_creation->create_mission['volume'][$offsetid]*$nbwords;

				$prod_type=$this->quote_creation->create_mission['producttype'][$offsetid];

				$configvalwords=$this->configval['oneshot_max_words_'.$prod_type];

				$max_writer=$this->configval['oneshot_max_writers'];
				
				$configureVal=($configvalwords*$max_writer);
				
				$durationVal=ceil($calculateVal/$configureVal);

				$mission_data['volume']=$quotesparams['value'];

				if($durationVal<=0)
						$durationVal=1;

				$this->quote_creation->create_mission['mission_length'][$offsetid]=$durationVal;
				$mission_data['mission_length']=$this->quote_creation->create_mission['mission_length'][$offsetid];
				

				echo $quotesparams['value'].'-'.$durationVal;
				
			}


		}
		/*staff mission in quote mission table*/
		elseif($quotesparams['taction']=='mission_writ')
		{
			if($mission_id)
			{
				$this->quote_creation->create_mission['staff_time'][$offsetid]=$quotesparams['value'];
				$mission_data['staff_time']=$this->quote_creation->create_mission['staff_time'][$offsetid];	
				echo $quotesparams['value'];		
			}
		}
		/*add submission auture mission*/
		elseif($quotesparams['taction']=='autre')
		{
			if($mission_id)
			{
				
				$prod_param['quote_mission_id']=$mission_id;
				$prod_param['currency']=$this->quote_creation->create_step1['currency'];	
				$prod_param['product']='autre';
				$prod_param['quote_mission_id']=$mission_id;
				if($quotesparams['autrewriter'])
				$prod_param['staff']=$quotesparams['autrewriter'];
				if($quotesparams['sub_mission_cost'])
				$prod_param['cost']=$quotesparams['sub_mission_cost'];
				$prod_param['created_at']=date("Y-m-d H:i:s");
				$prod_mission_obj->insertProdMission($prod_param);
				$prod_id=$prod_mission_obj->getIdentifier();
				if($quotesparams['sub_mission_cost'] && $prod_id)
				{
					$searchparam['quote_mission_id']=$mission_id;
				    $prodMissdetails=$prod_mission_obj->getProdMissionDetails($searchparam);
				    if(count($prodMissdetails)>0)
				    {
				    	$cost=0;
				    	foreach($prodMissdetails as $prod_val)
				    	{
				    		if($prod_val['product']=='proofreading' || $prod_val['product']=='translation' || $prod_val['product']=='redaction' || $prod_val['product']=='autre')
				    			$cost+=$prod_val['cost'];
				    	}
				    	$mission_data['internal_cost']=$cost;
				    	$this->quote_creation->create_mission['internal_cost'][$offsetid]=$mission_data['internal_cost'];
				    	$this->quote_creation->create_mission['unit_price'][$offsetid]=($mission_data['internal_cost']/(1-$this->quote_creation->create_mission['margin_percentage'][$offsetid]/100));
				    	$mission_data['unit_price']=$this->quote_creation->create_mission['unit_price'][$offsetid];
				    	$quoteMission_obj->updateQuoteMission($mission_data,$mission_id);		
				    }
					
				}
				
				echo '<div class="col-md-12 separator autre" id="'.$prod_id.'">
				<i class="glyphicon glyphicon-user"></i> <span class="hm-text"><span id="other_staff">'.$quotesparams['autrewriter'].'</span> -<span id="other_cost">'.$quotesparams['sub_mission_cost'].'</span> &'.$prod_param['currency'].';</span>
			</div>';
				exit;

			}
		}

		/*comments update*/
		elseif($quotesparams['taction']=='comment')
		{
			if($mission_id)
			{
					$log_params['quote_id']	= $this->quote_creation->custom['quote_id'];
					$log_params['mission_id']= $mission_id;
					$log_params['bo_user']	= $this->adminLogin->userId;					
					$log_params['version']	=$this->quote_creation->custom['version'];
					$log_params['action']	= 'edit_mission_comment';
					$log_params['comments']=$quotesparams['sales_mission_comment'];
					$quiteActionId=33;	
					
					$log_obj=new Ep_Quote_QuotesLog();
					
					$log_obj->insertLog($quiteActionId,$log_params);


					$search_param['mission_id']= $mission_id;
					$comment_list=$log_obj->getLogs($search_param);

					if(count($comment_list)>0)
					{
						$comments_display="";
							foreach($comment_list as $commentsVal)
							{
								$comments_display .='<div class="col-md-12 separator"><img width="30" height="30" src="'.$this->_config->path->fo_path.'/profiles/bo/'.$commentsVal['user_id'].'/logo.jpg" class="member-avatar"><span><strong>'.$commentsVal['first_name']." ".$commentsVal['last_name'].'</strong> <span>'.$commentsVal['comments'].'</span> '.time_ago($commentsVal['action_at']).'</span> </div>';

							}
						
					}
				echo $comments_display;
				exit;	
			}

		}
		/*prod mission details change*/
		else
		{
			$pmission_id=$quotesparams['pmid'];
			
			//echo "<pre>"; print_r($quotesparams); exit;
			if($quotesparams['taction']=='writ_staff')
			{
				$prod_data['staff']=$quotesparams['value'];
			}
			elseif($quotesparams['taction']=='writ_cost')
			{
				$prod_data['cost']=$quotesparams['value'];
			}
			elseif($quotesparams['taction']=='cor_staff')
			{
				$prod_data['staff']=$quotesparams['value'];
			}
			elseif($quotesparams['taction']=='cor_cost')
			{
				$prod_data['cost']=$quotesparams['value'];	
			}
			elseif($quotesparams['taction']=='other_staff')
			{
				$prod_data['staff']=$quotesparams['value'];	
			}
			elseif($quotesparams['taction']=='other_cost')
			{
				$prod_data['cost']=$quotesparams['value'];	
			}
			
			if($pmission_id)
			{
				
				$prod_data['updated_at']=date("Y-m-d H:i:s");
				$prod_mission_obj->updateProdMission($prod_data,$pmission_id);
				if($prod_data['cost'])
				{
					$searchparam['quote_mission_id']=$mission_id;
				    $prodMissdetails=$prod_mission_obj->getProdMissionDetails($searchparam);
				    if(count($prodMissdetails)>0)
				    {
				    	$cost=0;
				    	foreach($prodMissdetails as $prod_val)
				    	{
				    		if($prod_val['product']=='proofreading' || $prod_val['product']=='translation' || $prod_val['product']=='redaction' || $prod_val['product']=='autre')
				    		$cost+=$prod_val['cost'];
				    	}
				    	$mission_data['internal_cost']=$cost;
				    	$this->quote_creation->create_mission['internal_cost'][$offsetid]=$mission_data['internal_cost'];
				    	$this->quote_creation->create_mission['unit_price'][$offsetid]=($mission_data['internal_cost']/(1-$this->quote_creation->create_mission['margin_percentage'][$offsetid]/100));
				    	$mission_data['unit_price']=$this->quote_creation->create_mission['unit_price'][$offsetid];
				    	$quoteMission_obj->updateQuoteMission($mission_data,$mission_id);		
				    }
				 }
				 if(count($this->quote_creation->create_mission['product'])>0)
				 {
				 	$h=0;
				 	$hide='_';
				 	foreach($this->quote_creation->create_mission['product'] as $miss)
				 	{
				 		if(($this->quote_creation->create_mission['internal_cost'][$h]==0 || $this->quote_creation->create_mission['internal_cost'][$h]==0.00) && ($this->quote_creation->create_mission['product'][$h]!="content_strategy" || $this->quote_creation->create_mission['product'][$h]!="" ))
				 		{
				 			$hide='_hide';
				 		}
				 	$h++;
				 	}

				 }
				echo $quotesparams['value'].'_'.$this->quote_creation->create_mission['internal_cost'][$offsetid].$hide;
			}
			else
			{
				$prod_data['quote_mission_id']=$mission_id;
				$prod_data['currency']=$this->quote_creation->create_step1['currency'];	
				$prod_data['created_by']=$this->adminLogin->userId;

				if($quotesparams['taction']=='writ_staff'|| $quotesparams['taction']=='writ_cost')
				{
					$prod_data['product']='redaction';	
					if($quotesparams['taction']=='writ_staff')
					{
						$prod_data['staff']=$quotesparams['value'];		
					}
					else
					{
						$prod_data['cost']=$quotesparams['value'];
					}
					
				}
				else
				{
					$prod_data['product']='proofreading';
					if($quotesparams['taction']=='cor_staff')
					{
						$prod_data['staff']=$quotesparams['value'];		
					}
					else
					{
						$prod_data['cost']=$quotesparams['value'];
					}	
				}

				$prod_data['created_at']=date("Y-m-d H:i:s");
				$prod_mission_obj->insertProdMission($prod_data);
				$prod_identifier=$prod_mission_obj->getIdentifier();
				if($prod_data['cost'])
				{
					$searchparam['quote_mission_id']=$mission_id;
				    $prodMissdetails=$prod_mission_obj->getProdMissionDetails($searchparam);
				    if(count($prodMissdetails)>0)
				    {
				    	$cost=0;
				    	foreach($prodMissdetails as $prod_val)
				    	{
				    		if($prod_val['product']=='proofreading' || $prod_val['product']=='translation' || $prod_val['product']=='redaction' || $prod_val['product']=='autre')
				    		$cost+=$prod_val['cost'];
				    	}
				    	$mission_data['internal_cost']=$cost;
				    	$this->quote_creation->create_mission['internal_cost'][$offsetid]=$mission_data['internal_cost'];
				    	$this->quote_creation->create_mission['unit_price'][$offsetid]=($mission_data['internal_cost']/(1-$this->quote_creation->create_mission['margin_percentage'][$offsetid]/100));
				    	$mission_data['unit_price']=$this->quote_creation->create_mission['unit_price'][$offsetid];
				    	$quoteMission_obj->updateQuoteMission($mission_data,$mission_id);		
				    }

				 }
				echo $prod_identifier.'-'.$quotesparams['value'].'-'.$this->quote_creation->create_mission['internal_cost'][$offsetid];
			}
			
			exit;

		}

		if($mission_id)
		{
			$mission_data['updated_at']=date("Y-m-d H:i:s");
			$quoteMission_obj->updateQuoteMission($mission_data,$mission_id);
		}
		exit;

	}




	//get all sales quotes list
	public function salesQuotesListWithoutAjaxAction()
	{		
		$quote_obj=new Ep_Quote_Quotes();
		$listParams=$this->_request->getParams();
		$searchParams['client_id']=$listParams['client_id'];
		$searchParams['new_quote_system']='yes';


		$quoteList=$quote_obj->getAllQuotesList($searchParams);	

		if($quoteList)
		{
			$q=0;
			$total_turnover=0;
			$ave_count=0;
			$in_day=0;
			$briefcount=0;
			$closed_turnover=0;
			$deleted_turnover=0;
			foreach ($quoteList as $quote) {
				
				$quoteList[$q]['tech_status']=$this->status_array[$quote['tec_review']];
				$quoteList[$q]['seo_status']=$this->status_array[$quote['seo_review']];
				$quoteList[$q]['prod_status']=$this->status_array[$quote['prod_review']];
				$quoteList[$q]['sales_status']=$this->status_array[$quote['sales_review']];
				$quoteList[$q]['category_name']=$this->getCategoryName($quote['category']);
				$quoteList[$q]['closed_reason_txt'] = $this->closedreason[$quote['closed_reason']];
				if($quote['tech_timeline'])
				{					
					$quoteList[$q]['tech_challenge_time']=strtotime($quote['tech_timeline']);
				}	
				if($quote['seo_timeline'])
				{
					$quoteList[$q]['seo_challenge_time']=strtotime($quote['seo_timeline']);
				}

				$client_obj=new Ep_Quote_Client();
				$bo_user_details=$client_obj->getQuoteUserDetails($quote['quote_by']);
				
				if($quote['deleted_by'])
				{
					$deleted_user=$client_obj->getQuoteUserDetails($quote['deleted_by']);
					$quoteList[$q]['deleted_user'] = $deleted_user[0]['first_name'].' '.$deleted_user[0]['last_name'];
				}

				$quoteList[$q]['owner']=$bo_user_details[0]['first_name'].' '.$bo_user_details[0]['last_name'];

				$prod_team=$quote['prod_review']!='auto_skipped' ? 'Prod ': '';
				$seo_team=$quote['seo_review']!='auto_skipped' ? 'Seo ': '';
				$tech_team=$quote['tec_review']!='auto_skipped' ? 'Tech ': '';

				$quoteList[$q]['team']=$prod_team.$seo_team.$tech_team;

				if(!$quoteList[$q]['team'])
					$quoteList[$q]['team']='only sales';


				//turnover calculations
				if($quote['sales_review']=='not_done' || $quote['sales_review']=='to_be_approve' )
					$total_ongoing_turnover+=$quote['turnover'];

				

				if($quote['sales_review']=='signed')
				{
					$signed_turnover+=$quote['turnover'];
					$existval= $quote_obj->checkcontractexist($quote['identifier']);
					
					if(count($existval[0]['quotecontractid'])>0)
					{
						$quoteList[$q]['signed_exist']=1;
						$quoteList[$q]['signed_contract']=$existval[0]['contractname'];
						$quoteList[$q]['signed_contractid']=$existval[0]['quotecontractid'];
					}
					else
					{
						$quoteList[$q]['signed_exist']=0;
					}
					
				}

				//To check signed contract 
					
				//Mean Time Quotes signature 
				
				if(($quote['sales_review']=='validated' || $quote['sales_review']=='signed') && $quote['signed_at']!='')
				{
						$quotes_log=new Ep_Quote_QuotesLog();
						$quotesAction=$quotes_log->getquoteslogvalid($quote['identifier'],'sales_validated_ontime');
					//print_r($quotesAction);
						if($quotesAction[0]['action_at']!=""){
							$date_difference=strtotime($quote['signed_at'])-strtotime($quotesAction[0]['action_at']);
							$in_day+=$date_difference/(60 * 60 * 24);
							$ave_count++;
						  }
					
                  }   
                  
                  //relancer Section
                  if($quote['releaceraction']!=''){
					 $quoteList[$q]['relance_actiondate']=date("Y-m-d", strtotime("+1 month", strtotime($quote['releaceraction'])));
					}  
					                            
                  if($quote['quotesvalidated']!=''){
					 $quoteList[$q]['relance_validated']=date("Y-m-d", strtotime("+5 days", strtotime($quote['quotesvalidated'])));
					}                              
                  

				if($quoteList[$q]['version']>1)
				{
					$versions = $quote_obj->getQuoteVersionDetails($quote['identifier']);
					$quoteList[$q]['version_dates'] = "<table class='table quote-history table-striped'>";
					foreach($versions as $version):
					$quoteList[$q]['version_dates'] .= '<tr><td>v'.$version['version'].' - '.date('d/m/Y',strtotime($version['created_at']))."</td></tr>";

					endforeach;
					$quoteList[$q]['version_dates'] .= '</table>';
				}
				else
				$quoteList[$q]['version_dates'] = "";
			
			
				//relancer turnover and flag
					if( (($quote['sales_review']=='closed' &&  (date("Y-m-d") > $quoteList[$q]['relance_actiondate'] || $quote['boot_customer']!="") ) 
					 ||	(time() > $quoteList[$q]['sign_expire_timeline'] && $quote['sales_review']=='validated'))
					  && $quote['closed_reason']!= 'quote_permanently_lost')
					 {
						$relancer_turnover+=$quote['turnover'];
						$quoteList[$q]['relancer_status']=1;
					}else{
						$quoteList[$q]['relancer_status']=0;
						}
						
				//closed quotes flag
				if( ($quote['sales_review']=='closed'  && date("Y-m-d") <= $quoteList[$q]['relance_actiondate'] && $quote['boot_customer']=="")
				 || $quote['closed_reason']=='quote_permanently_lost') 
				{
					$quoteList[$q]['closed_status']=1;
					$closed_turnover+=$quote['turnover'];
				}else
				{
					$quoteList[$q]['closed_status']=0;
				}
					
				//validated turnover
				if($quote['sales_review']=='validated' && time() <= $quoteList[$q]['sign_expire_timeline'])
				{
				$validated_turnover+=$quote['turnover']	;
				$quoteList[$q]['validated_status']=1;
					if($quote['is_new_quote']==1)
					{
					$quoteList[$q]['new_quote']=1;
					}
					else {
					$quoteList[$q]['new_quote']=0;	
					}
				}
				else
				{
				$quoteList[$q]['validated_status']=0;
					if($quote['is_new_quote']==1)
					{
					$quoteList[$q]['new_quote']=1;
					}
					else {
					$quoteList[$q]['new_quote']=0;	
					}
				}
				
				//Deleted turnover
				if($quote['sales_review']=='deleted')
				{
					$deleted_turnover+=$quote['turnover'];
				}
				
				
				//briefing count
				if($quote['sales_review']=='briefing')
					$briefcount++;
			
			
				$q++;
			}
			
            $meantime_sign_days=round(abs($in_day)/$ave_count,0);
			//echo "<pre>";print_r($quoteList);exit;
            
			$this->_view->brief_quotes_count=$briefcount;
			$this->_view->quote_list=$quoteList;
			
			$this->_view->total_ongoing_turnover=$total_ongoing_turnover;
			$this->_view->validated_turnover=$validated_turnover;
			$this->_view->signed_turnover=$signed_turnover;
			$this->_view->relancer_turnover=$relancer_turnover;
			$this->_view->closed_turnover=$closed_turnover;
			$this->_view->deleted_turnover=$deleted_turnover;
			
			$this->_view->day_difference=$meantime_sign_days;
			//if($_REQUEST['debug']){echo "<pre>";print_r($quoteList);exit;}

		}	
		$this->_view->quote_sent_timeline=$this->configval["quote_sent_timeline"];
		$this->_view->prod_timeline=$this->configval["prod_timeline"];

		$this->_view->techManager_holiday=$this->configval["tech_manager_holiday"];
		$this->_view->seoManager_holiday=$this->configval["seo_manager_holiday"];

		//echo "<pre>";print_r($quoteList);exit;
		$this->_view->closedreasons = $this->closedreason;
		$this->render('sales-quotes-list-new');
   
		//if($listParams['file_download']=='yes' && $listParams['quote_id'])
		//	header( "refresh:1;url=/quote/download-quote-xls?quote_id=".$listParams['quote_id']);
	}

		//get all sales quotes list
	public function salesQuotesListAction()
	{		
		$quote_obj=new Ep_Quote_Quotes();
		$listParams=$this->_request->getParams();
		$searchParams['client_id']=$listParams['client_id'];
		$searchParams['new_quote_system']='yes';
		$searchParams['search']=$listParams['search'];
		$searchParams['limit']=$this->salesquotelistlimitvar;
		$searchParams['page']=$listParams['page'];
		$searchParams['sales_review']=$listParams['sales_review'];
		
		$quoteList=$quote_obj->getAllQuotesListAjax($searchParams);	

		if($quoteList)
		{
			$q=0;
			$total_turnover=0;
			$ave_count=0;
			$in_day=0;
			$briefcount=0;
			$ongoingcount=0;
			$validatecount=0;
			$signcount=0;
			$relancecount=0;
			$closedcount=0;
			$deletedcount=0;
			$closed_turnover=0;
			$deleted_turnover=0;
			foreach ($quoteList as $quote) {
				
				
				$client_obj=new Ep_Quote_Client();
				
				//turnover calculations
				if($quote['sales_review']=='not_done' || $quote['sales_review']=='to_be_approve' )
				{
					$total_ongoing_turnover+=$quote['turnover'];
					$ongoingcount++;
				}	

				

				if($quote['sales_review']=='signed')
				{
					$signed_turnover+=$quote['turnover'];
					$signcount++;				
				}

				//To check signed contract 
					
				//Mean Time Quotes signature 
				
				if(($quote['sales_review']=='validated' || $quote['sales_review']=='signed') && $quote['signed_at']!='')
				{
						$quotes_log=new Ep_Quote_QuotesLog();
						$quotesAction=$quotes_log->getquoteslogvalid($quote['identifier'],'sales_validated_ontime');
					//print_r($quotesAction);
						if($quotesAction[0]['action_at']!=""){
							$date_difference=strtotime($quote['signed_at'])-strtotime($quotesAction[0]['action_at']);
							$in_day+=$date_difference/(60 * 60 * 24);
							$ave_count++;
						  }
					
                  }   
                  
                  //relancer Section
                  if($quote['releaceraction']!=''){
					 $quoteList[$q]['relance_actiondate']=date("Y-m-d", strtotime("+1 month", strtotime($quote['releaceraction'])));
					}  
					                            
                  if($quote['quotesvalidated']!=''){
					 $quoteList[$q]['relance_validated']=date("Y-m-d", strtotime("+5 days", strtotime($quote['quotesvalidated'])));
					}                              
                  

				
			
			
				//relancer turnover and flag
					if( (($quote['sales_review']=='closed' &&  (date("Y-m-d") > $quoteList[$q]['relance_actiondate'] || $quote['boot_customer']!="") ) 
					 ||	(time() > $quoteList[$q]['sign_expire_timeline'] && $quote['sales_review']=='validated'))
					  && $quote['closed_reason']!= 'quote_permanently_lost')
					 {
						$relancer_turnover+=$quote['turnover'];
						$relancecount++;
					}
				//closed quotes flag
				if( ($quote['sales_review']=='closed'  && date("Y-m-d") <= $quoteList[$q]['relance_actiondate'] && $quote['boot_customer']=="")
				 || $quote['closed_reason']=='quote_permanently_lost') 
				{
					
					$closed_turnover+=$quote['turnover'];
					$closedcount++;
				}
					
				//validated turnover
				if($quote['sales_review']=='validated' && time() <= $quoteList[$q]['sign_expire_timeline'])
				{
				$validated_turnover+=$quote['turnover']	;
				$quoteList[$q]['validated_status']=1;
					$validatecount++;
				}
				
				//Deleted turnover
				if($quote['sales_review']=='deleted')
				{
					$deletedcount++;
					$deleted_turnover+=$quote['turnover'];
				}
				
				
				//briefing count
				if($quote['sales_review']=='briefing')
					$briefcount++;
			
			
				$q++;
			}
			
            $meantime_sign_days=round(abs($in_day)/$ave_count,0);
			//echo "<pre>";print_r($quoteList);exit;
            
			$this->_view->brief_quotes_count=$briefcount;
			$this->_view->deletedcount=$deletedcount;
			$this->_view->validatecount=$validatecount;
			$this->_view->closedcount=$closedcount;
			$this->_view->relancecount=$relancecount;
			$this->_view->signcount=$signcount;
			$this->_view->ongoingcount=$ongoingcount;

			$this->_view->quote_list=$quoteList;
			
			$this->_view->total_ongoing_turnover=$total_ongoing_turnover;
			$this->_view->validated_turnover=$validated_turnover;
			$this->_view->signed_turnover=$signed_turnover;
			$this->_view->relancer_turnover=$relancer_turnover;
			$this->_view->closed_turnover=$closed_turnover;
			$this->_view->deleted_turnover=$deleted_turnover;
			
			$this->_view->day_difference=$meantime_sign_days;
			//if($_REQUEST['debug']){echo "<pre>";print_r($quoteList);exit;}

		}	
		$this->_view->quote_sent_timeline=$this->configval["quote_sent_timeline"];
		$this->_view->prod_timeline=$this->configval["prod_timeline"];

		$this->_view->techManager_holiday=$this->configval["tech_manager_holiday"];
		$this->_view->seoManager_holiday=$this->configval["seo_manager_holiday"];

		//echo "<pre>";print_r($quoteList);exit;
		$this->_view->closedreasons = $this->closedreason;
		$this->render('sales-quotes-list-new');
   
		if($listParams['file_download']=='yes' && $listParams['quote_id'])
			header( "refresh:1;url=/quote-new/download-quote-xls?quote_id=".$listParams['quote_id']);
	}
	
	/**get all quotes activities*/
	function quoteActivitiesListAction()
	{
		$quote_params=$this->_request->getParams();
		$page=$quote_params['page'];
		if(!$page)$page=1;
		$limit=10;
		$this->_view->next=($page+1);
		
		//Quote log details
		$log_obj=new Ep_Quote_QuotesLog();
		$log_details=$log_obj->getQuotesActivities($page,$limit);

		if($log_details)
		{
			foreach($log_details as $k=>$log)
			{						
				$log_details[$k]['time_ago']=time_ago_quote($log['action_at']);
				if($log['custom']!=""){
				
						$documents_path_sale=explode("|",$log['custom']);
						$sales_file ="";
						$counr=count($documents_path_sale)-1;
						foreach($documents_path_sale as $l=>$filesale)
						{
							
							$file_name_sale=basename($filesale);
							if($l==$counr) $br=""; else $br='<br>';
								
							$sales_file .='<a href="/quote/download-document?type=saleslog&index='.$l.'&quote_id='.$quote_id.'&logid='.$log['id'].'">
							'.$file_name_sale.'</a>'.$br;
							
						}
						$log_details[$k]['sales_file']=$sales_file;
					
			}
			}
		}		
		$this->_view->log_details=$log_details;
		$this->render("quote-activites-list");
	}
	
	// To delete the Quote temporarly
	function deleteQuoteAction()
	{
		if($this->_request->isPost() && $_SERVER['HTTP_X_REQUESTED_WITH']=='XMLHttpRequest')
		{
		
			$request = $this->_request->getParams();
			$quote_id = $request['quote_id'];
			$quote_obj = new Ep_Quote_Quotes();
			$update = array('sales_review'=>'deleted','deleted_at'=>date('Y-m-d H:i:s'),'deleted_by'=>$this->_view->userId);
			$quote_obj->updateQuote($update,$quote_id);			
		}		
	}
	
	// To delete quote permenently
	function deleteQuotePermenentAction()
	{
		if($this->_request->isPost() && $_SERVER['HTTP_X_REQUESTED_WITH']=='XMLHttpRequest')
		{
			$request = $this->_request->getParams();
			$quote_id = $request['quote_id'];
			$quote_obj = new Ep_Quote_Quotes();
			
			$quote_obj->deleteQuote($quote_id);
		}
	}


	/*relance the closed quote */
	function closeRelanceViewAction()
	{
		$request = $this->_request->getParams();
		$closed_type=$request['closed_type'];
		if($request['qid'])
		{
			$this->_view->qid = $request['qid'];
			$quote_obj=new Ep_Quote_Quotes();
			$quoteDetails=$quote_obj->getQuoteDetails($request['qid']);
			if($quoteDetails):
				$this->_view->closedreasons = $this->closedreason;
				if($closed_type=='perdu')
					$this->_view->selected_reasons = 'quote_permanently_lost';
				else
					$this->_view->selected_reasons = $quoteDetails[0]['closed_reason'];
					
				$this->_view->closed_comments = $quoteDetails[0]['closed_comments'];
				$this->render('close-relance-new');
			endif;
		}
	}


	//relance popup
	function relanceQuotePopupAction()
	{
		$request = $this->_request->getParams();
		if($request['qid'])
		{
			$this->_view->qid = $request['qid'];
			$this->_view->active = $request['active'];
			$quote_obj=new Ep_Quote_Quotes();
			$quoteDetails=$quote_obj->getQuoteDetails($request['qid']);
			if($quoteDetails):
			$this->_view->closedreasons = $this->closedreason;
			$this->render('relance-quote-new');
			endif;
		}
			
	}

	//relance submit
	function relanceClientAction(){
		
		if($this->_request->isPost())
		{
			
			$request = $this->_request->getParams();
			$quote_obj=new Ep_Quote_Quotes();
			$quote_id = $request['quote_id'];
			$comments = $request['relancer_commet'];
			$relance_date = $request['relance_at'];
			$update = array('relancer_commet'=>$comments,'boot_customer'=>$relance_date);
			
			$quote_obj->updateQuote($update,$quote_id);
			
			if($request['active']=='closequote')
				$this->_redirect("/quote-new/sales-quotes-list?submenuId=ML13-SL2&active=closed");
				else
			$this->_redirect("/quote-new/sales-quotes-list?submenuId=ML13-SL2&active=closedrelancer");
		}
		
		
		}

	// Relance rasion submit
	function closeReasionRelanceAction(){
		
		if($this->_request->isPost())
		{
			
			$request = $this->_request->getParams();
			$quote_obj=new Ep_Quote_Quotes();
			$quote_id = $request['quote_id'];
			$comments = $request['closetxt'];
			$relance_reason = $request['reason'];
			$update = array('closed_comments'=>$comments,'closed_reason'=>$relance_reason);
			
			$quote_obj->updateQuote($update,$quote_id);
			$this->_redirect("/quote-new/sales-quotes-list?submenuId=ML13-SL2&active=closedrelancer");
		}
		
		
		}


	//Edit QUote files and Comments in final creation step
	function editQuoteFinalStepAction()
	{
		if($_SERVER['HTTP_X_REQUESTED_WITH']=='XMLHttpRequest')
		{
			$quote_id=$this->_request->getParam('quote_id');

			if($quote_id)
			{
				$quoteObj=new Ep_Quote_Quotes();
				$quoteDetails=$quoteObj->getQuoteDetails($quote_id);
				if($quoteDetails)
				{
					
					//Quote documents added to sesssion
					$files = "";
					$documents_path = explode("|",$quoteDetails[0]['documents_path']);
					$documents_name = explode("|",$quoteDetails[0]['documents_name']);
					$k =0;
					foreach($documents_path as $row)
					{
						if(file_exists($this->quote_documents_path.$documents_path[$k]) && !is_dir($this->quote_documents_path.$documents_path[$k]))
						{
							$files .= '<div class="topset2"><a href="/quote/download-document?type=quote&quote_id='.$quote_id.'&index='.$k.'">'.utf8_encode($documents_name[$k]).'</a><span class="delete" rel="'.$k.'_'.$quote_id.'"> <i class="splashy-error_x"></i></span> </div>';
						}
						$k++;
					}
					$quoteDetails[0]['documents'] = $files;


					$this->_view->send_quote=$quoteDetails[0];
					$this->render("popup-edit-finalstep-details-new");
				}
			}

			
		}
		
	}

	//Save Edit QUote files and Comments in final creation step
	function saveEditQuoteFinalStepAction()
	{

		if($this->_request->isPost())
		{

			$final_parameters=$this->_request->getParams();

			$quote_id=$final_parameters['quote_id'];

			//echo "<pre>";print_r($final_parameters);exit;

			if($quote_id)
			{
			
				$quote_obj=new Ep_Quote_Quotes();
				$quoteDetails=$quote_obj->getQuoteDetails($quote_id);

				$quotes_update_data = array();
				$quotes_update_data['sales_comment']=$final_parameters['bo_comments'];
				
				
				if(count($_FILES['quote_documents']['name'])>0)	
				{
					$update = false;
					$documents_path=array();
					$documents_name=array();
					foreach($_FILES['quote_documents']['name'] as $index=>$quote_files)
					{
						if($_FILES['quote_documents']['name'][$index]):
						//upload quote documents
					
						$quoteDir=$this->quote_documents_path.$quoteIdentifier."/";
			            if(!is_dir($quoteDir))
			                mkdir($quoteDir,TRUE);
			            chmod($quoteDir,0777);
			            $document_name=frenchCharsToEnglish($_FILES['quote_documents']['name'][$index]);
						$pathinfo = pathinfo($document_name);
						$document_name =$pathinfo['filename'].rand(100,1000).".".$pathinfo['extension'];
			            $document_name=str_replace(' ','_',$document_name);
			            $document_path=$quoteDir.$document_name;
			            if (move_uploaded_file($_FILES['quote_documents']['tmp_name'][$index], $document_path))
			                chmod($document_path,0777);

							$update = true;
			                $documents_path[]=$quoteIdentifier."/".$document_name;
			                $documents_name[]=  str_replace('|',"_",$final_parameters['document_name'][$index]);

						endif;

					}
					
					 
					 
					 $uploaded_documents1 = explode("|",$quoteDetails[0]['documents_path']);
					 $documents_path =array_merge($documents_path,$uploaded_documents1);
					 $quotes_update_data['documents_path']=implode("|",$documents_path);
					 $document_names =explode("|",$quoteDetails[0]['documents_name']);
					 $documents_name =array_merge($documents_name,$document_names);
					 $quotes_update_data['documents_name']=implode("|",$documents_name);
		        
			    }

			    $status=$quoteDetails[0]['sales_review'];
				if($status=='not_done')
					$status='';
			    //echo "<pre>";print_r($quotes_update_data);exit;

			    $quote_obj->updateQuote($quotes_update_data,$quote_id);

			    $this->_redirect("/quote-new/sales-quotes-list?submenuId=ML13-SL2&active=".$status);
			}     
		}
	}

	function closeQuoteViewAction()
	{
		$request = $this->_request->getParams();
		if($request['qid'])
		{
			$this->_view->qid = $request['qid'];
			$quote_obj=new Ep_Quote_Quotes();
			$quoteDetails=$quote_obj->getQuoteDetails($request['qid']);
			if($quoteDetails):
			foreach($this->closedreason as $keyclose=>$closeres){
		    	if($keyclose!='quote_permanently_lost'){
			     	$closearray[$keyclose]=$closeres;
				}
			}
			$this->_view->closedreasons = $closearray;
			$this->render('close-quote-new');
			endif;
		}
	}


	function signQuoteViewAction()
	{
		$request = $this->_request->getParams();
		if($request['qid'])
		{
			$this->_view->qid = $request['qid'];
			$quote_obj=new Ep_Quote_Quotes();
			$quote_contract_obj=new Ep_Quote_Quotecontract();
			$quoteDetails=$quote_obj->getQuoteDetails($request['qid']);
			//echo "<pre>";print_r($quoteDetails);
			if($quoteDetails):
				$this->_view->client_identifier = $quoteDetails[0]['client_id'];
				$this->_view->siret = $quoteDetails[0]['siret'];
				$this->_view->siret_applicable = $quoteDetails[0]['siret_applicable'];
				$contractexists = $quote_contract_obj->checkcontract($quoteDetails[0]['client_id']);
				if($contractexists)
				{
					if($quoteDetails[0]['client_code'])
						$this->_view->ca_number = str_ireplace("p","C",$quoteDetails[0]['client_code']);
					else
						$this->_view->ca_number = "C";
				}
				else
				$this->_view->ca_number = $quoteDetails[0]['client_code'];	
				$this->_view->contractexists = $contractexists;
				if(trim($quoteDetails[0]['client_code']))
					$this->_view->client_id = $quoteDetails[0]['client_id'];
				else
					$this->_view->client_id = "";
	
			$this->render('sign-quote-new');
			endif;
		}
	}

	function signQuoteAction()
	{
		if($this->_request->isPost() && $this->adminLogin->userId)
		{
			$request = $this->_request->getParams();
			$quote_obj=new Ep_Quote_Quotes();
			$quote_id = $request['quote_id'];
			$quoteDetails=$quote_obj->getQuoteDetails($quote_id);
			if($quoteDetails)
			{
				if($quoteDetails[0]['sales_review']=="validated")
				{
					$quote_obj->updateQuote(array('sales_review'=>'signed','signed_at'=>date('Y-m-d H:i:s')),$quote_id);
					
					$siret_applicable=isset($request['siret_applicable'])? 'no' : 'yes';
					
					$client = array('siret'=>$request['siret'],'siret_applicable'=>$siret_applicable,'client_code'=>$request['client_code']);
					$client_obj=new Ep_Quote_Client();
					$client_obj->updateClientProfile($client,$quoteDetails[0]['client_id']);
					
					$log_params['quote_id']	= $quote_id;
					$log_params['bo_user']	= $this->adminLogin->userId;			
					$log_params['version']	= $quoteDetails[0]['version'];
					$log_params['action']	= 'sales_signed';
					$log_params['comments']	= $request['signtxt'];
					$log_params['created_date']	= date("Y-m-d H:i:s");

					$log_obj=new Ep_Quote_QuotesLog();					

					$log_obj->insertLog(10,$log_params);
					
					
					// Sending mails to challangers(tech/seo/prod)
					$client_obj=new Ep_Quote_Client();
					$bo_user_details=$client_obj->getQuoteUserDetails($this->adminLogin->userId);
					$mail_param['bo_user_name'] = $bo_user_details[0]['first_name']." ".$bo_user_details[0]['last_name'];
					$mail_param['quote_title']	= $quoteDetails[0]['title'];
					$mail_param['turn_over'] = $quoteDetails[0]['final_turnover'];
					$mail_param['client_id'] = $quoteDetails[0]['client_id'];
					$mail_param['client_name'] = $quoteDetails[0]['company_name'];
					$mail_param['signedquote']=true;
					$mail_param['followup_link']=$this->url.'quote-new/sales-final-validation?qaction=briefing&quote_id='.$quote_id;
					$mail_param['subject']=$mail_param['bo_user_name'].' has declared '.$mail_param['quote_title'].' quote as signed by the '.$mail_param['client_name'].' client.';


					$notifyEmail=explode(',',$quoteDetails[0]['brief_email_notify']);
					//echo "<pre>"; print_r($notifyEmail); exit;
					$mail_param['user']='';
					$mail_param['emailid']='';
					if(count($notifyEmail)>0)
					{
						foreach($notifyEmail as $recever_id)
						{
							$user_id=$recever_id;
							$quoteuser=$client_obj->getQuoteUserDetails($user_id);
							
							$mail_param['user']=$quoteuser[0]['first_name'].' '.$quoteuser[0]['last_name'];
							$mail_param['emailid']=$quoteuser[0]['email'];
						
							$this->getNewQuoteEmails($mail_param);
						}
					}
					
					$quote_contract=new Ep_Quote_Quotecontract();
					$facturationUsers = $quote_contract->getUsers("facturation");
					foreach($facturationUsers as $row)
					{
						$mail_param['user']= $row['first_name']." ".$row['last_name'];
						$mail_param['emailid'] =$row['email'];
						$this->getNewQuoteEmails($mail_param);
						
					}
					$this->_redirect("/quote-new/sales-quotes-list?submenuId=ML13-SL2&active=signed");
				}
			}
		}
		$this->_redirect("/quote-new/sales-quotes-list?submenuId=ML13-SL2");
	}
	/*getting staff time from config based on language and Package*/
	function getStaffTimeConfigAction()
	{
		$missionParams=$this->_request->getParams();
		
		$mission_index=$missionParams['index'];
		$package=$missionParams['package_name'];
		$margin_percentage=$missionParams['margin_percentage'];
		/*getting mission details from session*/
		$oneshot=$this->quote_creation->create_mission['oneshot'][$mission_index];
		if($oneshot=='yes')
		{
			$product=$this->quote_creation->create_mission['product'][$mission_index];
			if($product=='translation')
				$language=$this->quote_creation->create_mission['languagedest'][$mission_index];
			else
				$language=$this->quote_creation->create_mission['language'][$mission_index];
			$current_staff_time=$this->quote_creation->create_mission['staff_time'][$mission_index];
			$mission_duration=$this->quote_creation->create_mission['mission_length'][$mission_index];
				
			$staff_time_config=$this->packageStaffConfig[$language][$package];
			if($package=='link')
				$staff_time_config=0;
			
			echo $new_staff_time=$mission_duration+$staff_time_config;
			
			$this->quote_creation->create_mission['staff_time'][$mission_index]=$staff_time_config;
			$this->quote_creation->create_mission['package'][$mission_index]=$package;
			$this->quote_creation->create_mission['margin_percentage'][$mission_index]=$margin_percentage;
			//echo $oneshot."--".$language."--".$current_staff_time."--".$mission_duration."--".$staff_time_config;	
		}
		exit;
		
		
	}


	function getNewQuoteEmails($email)
	{
		
		$this->_view->email=$email;   
		$email_content=$this->_view->renderHtml('new-quote-email-template');
		//echo $email_content;
	//exit;
		$subject=$email['subject'];
		$mail_obj = new Ep_Message_AutoEmails();
        $to=$email['emailid'];
        
		   $mail_obj->sendEMail('work@edit-place.com',$email_content,$to,$subject);
		
				
	}



		// test get all sales quotes list
	public function ajaxQuotesListAction()
	{		
		$quote_obj=new Ep_Quote_Quotes();
		$listParams=$this->_request->getParams();
		$searchParams['client_id']=$listParams['client_id'];
		$searchParams['search']=utf8_decode($listParams['search']);
		$searchParams['new_quote_system']='yes';
		$searchParams['page']=$listParams['page'];
		$searchParams['limit']=$this->salesquotelistlimitvar;
		$searchParams['sales_review']=$listParams['sales_review'];
		//echo "<pre>"; print_r($searchParams); exit;
		$quoteList=$quote_obj->getAllQuotesListAjax($searchParams);	
		//echo "<pre>"; print_r($quoteList); exit;
		if($quoteList)
		{
			$q=0;
			$total_turnover=0;
			$ave_count=0;
			$in_day=0;
			$briefcount=0;
			$closed_turnover=0;
			$deleted_turnover=0;
			foreach ($quoteList as $quote) {
				
				$quoteList[$q]['tech_status']=$this->status_array[$quote['tec_review']];
				$quoteList[$q]['seo_status']=$this->status_array[$quote['seo_review']];
				$quoteList[$q]['prod_status']=$this->status_array[$quote['prod_review']];
				$quoteList[$q]['sales_status']=$this->status_array[$quote['sales_review']];
				$quoteList[$q]['category_name']=$this->getCategoryName($quote['category']);
				$quoteList[$q]['closed_reason_txt'] = $this->closedreason[$quote['closed_reason']];
				if($quote['tech_timeline'])
				{					
					$quoteList[$q]['tech_challenge_time']=strtotime($quote['tech_timeline']);
				}	
				if($quote['seo_timeline'])
				{
					$quoteList[$q]['seo_challenge_time']=strtotime($quote['seo_timeline']);
				}

				$client_obj=new Ep_Quote_Client();
				$bo_user_details=$client_obj->getQuoteUserDetails($quote['quote_by']);
				
				if($quote['deleted_by'])
				{
					$deleted_user=$client_obj->getQuoteUserDetails($quote['deleted_by']);
					$quoteList[$q]['deleted_user'] = $deleted_user[0]['first_name'].' '.$deleted_user[0]['last_name'];
				}

				$quoteList[$q]['owner']=$bo_user_details[0]['first_name'].' '.$bo_user_details[0]['last_name'];

				$prod_team=$quote['prod_review']!='auto_skipped' ? 'Prod ': '';
				$seo_team=$quote['seo_review']!='auto_skipped' ? 'Seo ': '';
				$tech_team=$quote['tec_review']!='auto_skipped' ? 'Tech ': '';

				$quoteList[$q]['team']=$prod_team.$seo_team.$tech_team;

				if(!$quoteList[$q]['team'])
					$quoteList[$q]['team']='only sales';


				//turnover calculations
				if($quote['sales_review']=='not_done' || $quote['sales_review']=='to_be_approve' )
					$total_ongoing_turnover+=$quote['turnover'];

				

				if($quote['sales_review']=='signed')
				{
					$signed_turnover+=$quote['turnover'];
					$existval= $quote_obj->checkcontractexist($quote['identifier']);
					
					if(count($existval[0]['quotecontractid'])>0)
					{
						$quoteList[$q]['signed_exist']=1;
						$quoteList[$q]['signed_contract']=$existval[0]['contractname'];
						$quoteList[$q]['signed_contractid']=$existval[0]['quotecontractid'];
					}
					else
					{
						$quoteList[$q]['signed_exist']=0;
					}
					
				}

				//To check signed contract 
					
				//Mean Time Quotes signature 
				
				if(($quote['sales_review']=='validated' || $quote['sales_review']=='signed') && $quote['signed_at']!='')
				{
						$quotes_log=new Ep_Quote_QuotesLog();
						$quotesAction=$quotes_log->getquoteslogvalid($quote['identifier'],'sales_validated_ontime');
					//print_r($quotesAction);
						if($quotesAction[0]['action_at']!=""){
							$date_difference=strtotime($quote['signed_at'])-strtotime($quotesAction[0]['action_at']);
							$in_day+=$date_difference/(60 * 60 * 24);
							$ave_count++;
						  }
					
                  }   
                  
                  //relancer Section
                  if($quote['releaceraction']!=''){
					 $quoteList[$q]['relance_actiondate']=date("Y-m-d", strtotime("+1 month", strtotime($quote['releaceraction'])));
					}  
					                            
                  if($quote['quotesvalidated']!=''){
					 $quoteList[$q]['relance_validated']=date("Y-m-d", strtotime("+5 days", strtotime($quote['quotesvalidated'])));
					}                              
                  

				if($quoteList[$q]['version']>1)
				{
					$versions = $quote_obj->getQuoteVersionDetails($quote['identifier']);
					$quoteList[$q]['version_dates'] = "<table class='table quote-history table-striped'>";
					foreach($versions as $version):
					$quoteList[$q]['version_dates'] .= '<tr><td>v'.$version['version'].' - '.date('d/m/Y',strtotime($version['created_at']))."</td></tr>";

					endforeach;
					$quoteList[$q]['version_dates'] .= '</table>';
				}
				else
				$quoteList[$q]['version_dates'] = "";
			
			
				//relancer turnover and flag
					if( (($quote['sales_review']=='closed' &&  (date("Y-m-d") > $quoteList[$q]['relance_actiondate'] || $quote['boot_customer']!="") ) 
					 ||	(time() > $quoteList[$q]['sign_expire_timeline'] && $quote['sales_review']=='validated'))
					  && $quote['closed_reason']!= 'quote_permanently_lost')
					 {
						$relancer_turnover+=$quote['turnover'];
						$quoteList[$q]['relancer_status']=1;
					}else{
						$quoteList[$q]['relancer_status']=0;
						}
						
				//closed quotes flag
				if( ($quote['sales_review']=='closed'  && date("Y-m-d") <= $quoteList[$q]['relance_actiondate'] && $quote['boot_customer']=="")
				 || $quote['closed_reason']=='quote_permanently_lost') 
				{
					$quoteList[$q]['closed_status']=1;
					$closed_turnover+=$quote['turnover'];
				}else
				{
					$quoteList[$q]['closed_status']=0;
				}
					
				//validated turnover
				if($quote['sales_review']=='validated' && time() <= $quoteList[$q]['sign_expire_timeline'])
				{
				$validated_turnover+=$quote['turnover']	;
				$quoteList[$q]['validated_status']=1;
					if($quote['is_new_quote']==1)
					{
					$quoteList[$q]['new_quote']=1;
					}
					else {
					$quoteList[$q]['new_quote']=0;	
					}
				}
				else
				{
				$quoteList[$q]['validated_status']=0;
					if($quote['is_new_quote']==1)
					{
					$quoteList[$q]['new_quote']=1;
					}
					else {
					$quoteList[$q]['new_quote']=0;	
					}
				}
				
				//Deleted turnover
				if($quote['sales_review']=='deleted')
				{
					$deleted_turnover+=$quote['turnover'];
				}
				
				
				//briefing count
				if($quote['sales_review']=='briefing')
					$briefcount++;
			
			
				$q++;
			}
			
            $meantime_sign_days=round(abs($in_day)/$ave_count,0);
			//echo "<pre>";print_r($quoteList);exit;
            
			$this->_view->brief_quotes_count=$briefcount;
			$this->_view->quote_list=$quoteList;			
			$this->_view->day_difference=$meantime_sign_days;
			//if($_REQUEST['debug']){echo "<pre>";print_r($quoteList);exit;}

		}	
		$this->_view->quote_sent_timeline=$this->configval["quote_sent_timeline"];
		$this->_view->prod_timeline=$this->configval["prod_timeline"];

		$this->_view->techManager_holiday=$this->configval["tech_manager_holiday"];
		$this->_view->seoManager_holiday=$this->configval["seo_manager_holiday"];

		//echo "<pre>";print_r($quoteList);exit;
		$this->_view->closedreasons = $this->closedreason;
		$this->render('ajax-quotes-list');
   
	}
	//download Quote XLS
	public function downloadQuoteXlsAction()
	{
		$prod_parameters=$this->_request->getParams();
		$quote_id=$prod_parameters['quote_id'];
		$currency=$prod_parameters['currency']?$prod_parameters['currency']:'euro';
		$this->_view->currency=$currency;  //Dont change this line position
		$exchange_rate=$prod_parameters['exchange_rate']?$prod_parameters['exchange_rate']:1;
		if($currency=='usd')
			$currency='#36';
		//&#36;
		$quote_obj=new Ep_Quote_Quotes();
		$quote_contract_obj = new Ep_Quote_Quotecontract();
		//header('Content-type: application/octet-stream');
    	//header('Content-Disposition: attachment; filename="test.xls"');
		if($quote_id)
		{
			$quoteDetails=$quote_obj->getQuoteDetails($quote_id);
			if($quoteDetails)
			{
				$q=0;
				foreach($quoteDetails as $quote)
				{
					$quoteDetails[$q]['category_name']=$this->getCategoryName($quote['category']);
					$quoteDetails[$q]['websites']=explode("|",$quote['websites']);
					$quote['sales_suggested_currency']=$currency;
					if($quote['country'])
						$quoteDetails[$q]['country_name']=$this->getCountryName($quote['country']);
					//get client contact details
					$contact_obj=new Ep_Quote_ClientContacts();
					$contractDetails=$contact_obj->getClientMainContacts($quote['client_id']);
					if($contractDetails!='NO')
					{
						$quoteDetails[$q]['client_contact_name']=$contractDetails[0]['first_name'];
						$quoteDetails[$q]['client_contact_email']=$contractDetails[0]['email'];
						$quoteDetails[$q]['client_contact_phone']=$contractDetails[0]['office_phone'];
					}
					$contractexists = $quote_contract_obj->checkcontract($quote['client_id']);
					if($contractexists)
					{
						$quoteDetails[$q]['client_code'] = str_ireplace("p","C",$quoteDetails[$q]['client_code']);
					}
					if($quote['documents_path'])
					{
						$related_files='';
						$documents_path=explode("|",$quote['documents_path']);
						$documents_name=explode("|",$quote['documents_name']);
						foreach($documents_path as $k=>$file)
						{
							if($documents_name[$k])
								$file_name=$documents_name[$k];
							else
								$file_name=basename($file);
							$related_files.='
							<a href="/quote/download-document?type=quote&index='.$k.'&quote_id='.$quote_id.'">'.$file_name.'</a><br>';
						}
					}
					$quoteDetails[$q]['related_files']=$related_files;
					$quoteDetails[$q]['sales_suggested_price_format']=number_format($quote['sales_suggested_price'], 2, ',', ' ');
					$quoteDetails[$q]['comment_time']=time_ago($quote['created_at']);
					//bo user details
					$quote_by=$quote['quote_by'];
					$client_obj=new Ep_Quote_Client();
					$bo_user_details=$client_obj->getQuoteUserDetails($quote_by);
					if($bo_user_details!='NO')
					{
						$quoteDetails[$q]['quote_user_name']=$bo_user_details[0]['first_name'].' '.$bo_user_details[0]['last_name'];
						$quoteDetails[$q]['email']=$bo_user_details[0]['email'];
						$quoteDetails[$q]['phone_number']=$bo_user_details[0]['phone_number'];
					}	
					//getting mission details
					$searchParameters['quote_id']=$quote_id;
					$searchParameters['include_final']='yes';
					$quoteMission_obj=new Ep_Quote_QuoteMissions();
					$missonDetails=$quoteMission_obj->getMissionDetails($searchParameters);
					$total_unitprice=0;
					$total_turnover=0;
					$total_internalcost=0;
					$total_staff_setup_time=array();
					if($missonDetails)
					{
						$m=0;
						$mission_length_array=array();
						$prior_length_array=array();
						foreach($missonDetails as $mission)
						{
							$missonDetails[$m]['product_name']=$this->seo_product_array[$mission['product']];			
							$missonDetails[$m]['language_source_name']=utf8_encode($this->getLanguageName($mission['language_source']));
							$missonDetails[$m]['product_type_name']=$this->producttype_array[$mission['product_type']];
							if($mission['language_dest'])
								$missonDetails[$m]['language_dest_name']=utf8_encode($this->getLanguageName($mission['language_dest']));		
							$missonDetails[$m]['comment_time']=time_ago($mission['created_at']);
							if($mission['product']=='seo_audit' || $mission['product']=='smo_audit' || $mission['product']== 'content_strategy' )
							{
								if($mission['internal_cost']>0)
								{
									$missonDetails[$m]['internal_cost']=$mission['internal_cost']*$exchange_rate;
									$audit=$mission['product']=='seo_audit' ?'SEO':'SMO';
									$missonDetails[$m]['internalcost_details']="$audit Audit : ".zero_cut($mission['cost']*$exchange_rate,2)." &".$quote['sales_suggested_currency'].";<br>";
									$total_internalcost+=$mission['internal_cost']*$exchange_rate;
								}
								else
								{
									$missonDetails[$m]['internal_cost']=$mission['cost']*$exchange_rate;
									$audit=$mission['product']=='seo_audit' ?'SEO':'SMO';
									$missonDetails[$m]['internalcost_details']="$audit Audit : ".zero_cut($mission['cost']*$exchange_rate,2)." &".$quote['sales_suggested_currency'].";<br>";
									$total_internalcost+=$mission['cost']*$exchange_rate;
								}	
								$missonDetails[$m]['required_writes']=1;
								//if mission is prior to prod
								if($mission['before_prod']=='yes')
									$prior_length_array[]=$mission['mission_length'];
							}
							else
							{
								//get Internal cost of a mission
								$prodMissionObj=new Ep_Quote_ProdMissions();
								$prodMissionDetails=$prodMissionObj->getProdCostDetails($mission['identifier']);
								if($prodMissionDetails)
								{
									$internalcost=0;
									$internalcost_details='';
									$staff_time=array();
									//$required_writes=1;
									foreach($prodMissionDetails as $prodMission)
									{
										$internalcost=$internalcost+$prodMission['cost']*$exchange_rate;
										$internalcost_details.=$this->seo_product_array[$prodMission['product']]. " : ".zero_cut($prodMission['cost'],2)." &".$prodMission['currency'].";<br>";
										//adding proof reading time too for mission total time
										if($prodMission['product']!='proofreading')
										{
											if($prodMission['staff_time_option']=='hours')
											{
												$staff_mission_stetup=($prodMission['staff_time']/24);
											}
											else
												$staff_mission_stetup=($prodMission['staff_time']);
											if($prodMission['delivery_option']=='hours')
											{
												$prod_delivery_stetup=($prodMission['delivery_time']/24);
											}
											else
												$prod_delivery_stetup=($prodMission['delivery_time']);
										}
										if($prodMission['product']=='proofreading')
										{
											if($prodMission['staff_time_option']=='hours')
											{
												$staff_mission_stetup+=($prodMission['staff_time']/24);
											}
											else
												$staff_mission_stetup+=($prodMission['staff_time']);
											if($prodMission['delivery_option']=='hours')
											{
												$prod_delivery_stetup+=($prodMission['delivery_time']/24);
											}
											else
												$prod_delivery_stetup+=($prodMission['delivery_time']);
										}
										$staff_time[]=$staff_mission_stetup;
										$prod_delivery_time[]=$prod_delivery_stetup;
										//getting required writers	
										if($prodMission['product']=='redaction' || $prodMission['product']=='translation' || (!$required_writes && $prodMission['product']=='autre' ))
										{											
											$required_writes=$prodMission['staff'];
										}
									}
									//required Writres
									$missonDetails[$m]['required_writes']=$required_writes;
									//echo "<pre>";print_r($prodMissionDetails);		
									if($mission['internal_cost']>0)
									{
										$missonDetails[$m]['internal_cost']=$mission['internal_cost']*$exchange_rate;
										$total_internalcost+=$mission['internal_cost']*$exchange_rate;
									}
									else						
									{
										$missonDetails[$m]['internal_cost']=$internalcost;
										$total_internalcost+=$internalcost;
									}
									$missonDetails[$m]['internalcost_details']=$internalcost_details;
									//Adding prod staff setup time to mission length
									$total_staff_setup_time[]=max($staff_time);
									$prod_team_setup=max($staff_time)+max($prod_delivery_time);
									$missonDetails[$m]['mission_length']=round($missonDetails[$m]['mission_length']+$prod_team_setup);
								}
								else if($mission['internal_cost']>0)
								{
									$missonDetails[$m]['internal_cost']=$mission['internal_cost']*$exchange_rate;
									$missonDetails[$m]['internalcost_details'].="Internal cost : ".zero_cut($mission['internal_cost']*$exchange_rate,2)." &".$quote['sales_suggested_currency'].";<br>";
								}
								else
									$missonDetails[$m]['internal_cost']=0;
							}	
							//$missonDetails[$m]['unit_price']=number_format(($missonDetails[$m]['internal_cost']/(1-($mission['margin_percentage']/100))),2, '.', '');
							$missonDetails[$m]['unit_price']=$missonDetails[$m]['unit_price']*$exchange_rate;
							$missonDetails[$m]['turnover']=$missonDetails[$m]['unit_price']*$mission['volume'];
							//total turnover and total unit price
							if($missonDetails[$m]['turnover'])	
							{
								$total_unitprice+=$missonDetails[$m]['unit_price'];
								$total_turnover+=$missonDetails[$m]['turnover'];
							}	
							//array of mission lengths
							$mission_length_array[]=$missonDetails[$m]['mission_length'];//$mission['mission_length'];			
							//if mission is prior to prod
							if($mission['before_prod']=='yes')
								$prior_length_array[]=$mission['mission_length'];
							//calculating team price
							if(!$missonDetails[$m]['team_fee'])
							{
								$teamPrice=0;							
								$teamPrice=350;//(ceil($missonDetails[$m]['required_writes']/3))*350;								
								$missonDetails[$m]['team_fee']=$teamPrice;
							}
							if(!$missonDetails[$m]['team_packs'])
							{
								//$missonDetails[$m]['team_packs']=(ceil($missonDetails[$m]['required_writes']/3));	
								$missonDetails[$m]['team_packs']=$missonDetails[$m]['required_writes'];
							}
							//calculate user turnover for user package
							if(!$missonDetails[$m]['user_fee'])
								$missonDetails[$m]['user_fee']=350;
							$missonDetails[$m]['user_fee']=$missonDetails[$m]['user_fee']*$exchange_rate;
							$missonDetails[$m]['user_package_turnover']=(($missonDetails[$m]['required_writes']*$missonDetails[$m]['user_fee']));
							$missonDetails[$m]['team_fee']=$missonDetails[$m]['team_fee']*$exchange_rate;
							//$missonDetails[$m]['team_package_turnover']=(($missonDetails[$m]['turnover']+$missonDetails[$m]['team_fee']));
							$missonDetails[$m]['team_package_turnover']=(($missonDetails[$m]['team_fee']*$missonDetails[$m]['team_packs']));
							$m++;
						}						
						//echo "<pre>";print_r($missonDetails);exit;
						$quoteDetails[$q]['mission_details']=$missonDetails;
					}	
					/***************getting Tech mission details******************/
					$tech_obj=new Ep_Quote_TechMissions();
					$searchParameters['quote_id']=$quote_id;
					$searchParameters['include_final']='yes';
					$techMissionDetails=$tech_obj->getTechMissionDetails($searchParameters);
					if($techMissionDetails)
					{
						$t=0;
						$mission=array();
						foreach($techMissionDetails as $mission)
						{
							$techMissionDetails[$t]['internal_cost']=$mission['cost']*$exchange_rate;
							$techMissionDetails[$t]['unit_price']=number_format(($techMissionDetails[$t]['internal_cost']/(1-($mission['margin_percentage']/100))),2, '.', '');
						    $techMissionDetails[$t]['turnover']=$techMissionDetails[$t]['unit_price']*$mission['volume'];

						    $total_internalcost+=$mission['cost']*$exchange_rate;
						    $total_unitprice+=$techMissionDetails[$t]['unit_price'];
							$total_turnover+=$techMissionDetails[$t]['turnover'];


							$mission_length_array[]=$mission['delivery_time'];

							//if mission is prior to prod
							if($mission['before_prod']=='yes')
								$prior_length_array[]=$mission['delivery_time'];
							

							//calculating team price
							$techMissionDetails[$t]['required_writes']=1;
							if(!$techMissionDetails[$t]['team_fee'])
							{
								$teamPrice=0;							
								$teamPrice=350;//(ceil($techMissionDetails[$t]['required_writes']/3))*350;									
								$techMissionDetails[$t]['team_fee']=$teamPrice;
							}
							if(!$techMissionDetails[$t]['team_packs'])
							{
								//$techMissionDetails[$t]['team_packs']=(ceil($techMissionDetails[$t]['required_writes']/3));
								$techMissionDetails[$t]['team_packs']=$techMissionDetails[$t]['required_writes'];
							}

							//calculate user turnover for user package
							if(!$techMissionDetails[$t]['user_fee'])
								$techMissionDetails[$t]['user_fee']=350;
							
							$techMissionDetails[$t]['user_fee']=$techMissionDetails[$t]['user_fee']*$exchange_rate;

							$techMissionDetails[$t]['user_package_turnover']=(($techMissionDetails[$t]['required_writes']*$techMissionDetails[$t]['user_fee']));
							
							$techMissionDetails[$t]['team_fee']=$techMissionDetails[$t]['team_fee']*$exchange_rate;	//$techMissionDetails[$t]['team_package_turnover']=(($techMissionDetails[$t]['turnover']+$techMissionDetails[$t]['team_fee']));
							$techMissionDetails[$t]['team_package_turnover']=(($techMissionDetails[$t]['team_fee']*$techMissionDetails[$t]['team_packs']));


							$t++;
						}		

						$quoteDetails[$q]['tech_mission_details']=$techMissionDetails;
					}

					//echo "<pre>";print_r($techMissionDetails);exit;


					//total cost details
					$quoteDetails[$q]['total_internalcost']=$total_internalcost;
					$quoteDetails[$q]['total_unitprice']=$total_unitprice;
					$quoteDetails[$q]['total_turnover']=$total_turnover;
					//echo $quoteDetails[$q]['over_all_margin']=number_format(((100-($quoteDetails[$q]['total_internalcost']/$quoteDetails[$q]['total_unitprice'])*100)),2);

					if(!$quoteDetails[$q]['final_mission_length'])
					{
						if(count($prior_length_array)>0)
							$quoteDetails[$q]['total_mission_length']=max($mission_length_array)+max($prior_length_array);
						else
							$quoteDetails[$q]['total_mission_length']=max($mission_length_array);
					}
					else
						$quoteDetails[$q]['total_mission_length']=$quoteDetails[$q]['final_mission_length'];

					//total staff setup time
					$quoteDetails[$q]['total_staff_setup_time']=$total_staff_setup_time=max($total_staff_setup_time);
					$quoteDetails[$q]['total_delivery_time']=($quoteDetails[$q]['total_mission_length']-$total_staff_setup_time);
					$quoteDetails[$q]['total_duration']=$quoteDetails[$q]['total_mission_length'];

					$quoteDetails[$q]['final_turnover']=$quote['final_turnover']*$exchange_rate;
					$tva=($quoteDetails[$q]['final_turnover']*20/100);
					$quoteDetails[$q]['tva']=$tva;
					$quoteDetails[$q]['total_htc']=($quoteDetails[$q]['final_turnover']+$quoteDetails[$q]['tva']);
					
					$quoteDetails[$q]['sales_suggested_currency']=$currency;
								

					$q++;
				}				
				$this->_view->quoteDetails=$quoteDetails;
			}
		}
		//echo "<pre>";print_r($quoteDetails);exit;
		// any of your code here   	    
   	    $htmltable = $html = $this->_view->renderHtml('quote-download-xls');  // return the view script content as a string
  		//$html=strip_tags($html,'<table><tr><th><td>'); 	
  		//echo $htmltable;exit;	
  		$session_variable='session_'.rand(100000,999999);
		$cfilename = "quote-xls-".$quote_id."_".time().".xlsx";
		$this->convertHtmltableToXlsx($htmltable,$cfilename);
  		$this->_redirect("/BO/download-quote-xls.php?session_id=".$cfilename);
	}
	
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
		
		
		$explodefile=explode('/',$filename);
		$count=count($explodefile)-2;
		if($explodefile[$count]=='quotes_weekly_report'){
		$fname =$filename;
		}else{
		$fname = $_SERVER['DOCUMENT_ROOT']."/BO/quotexls/$filename"; /* Filename */
		}
	
		if(!$extract)//image not required for extract XLS
		{

			$objDrawing = new PHPExcel_Worksheet_Drawing();
			$objDrawing->setName('Logo');
			$objDrawing->setDescription('Logo');
			$objDrawing->setPath($_SERVER['DOCUMENT_ROOT'].'/BO/theme/gebo/img/edit-place.png');
			$objDrawing->setCoordinates('A1');
			$objDrawing->setWorksheet($objPHPExcel->getActiveSheet());

			$objPHPExcel->getActiveSheet()->getRowDimension('1')->setRowHeight(40);
		}	
 
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
	/*change currency modal*/
	function changeQuoteCurrencyAction()
	{
		$currencyParams=$this->_request->getParams();
		if($currencyParams['quote_id'])
		{
			$currency_rates=$this->webHttpClient($this->currency_exchange_link);
			if(count($currency_rates)>0)
			{
				$this->_view->currency_rates=$currency_rates['rates'];
			}
			//echo "<Pre>";print_r($currency_rates);			
			$this->render('change-quote-currency');
		}
			
	}
	//connect to FR server with a link to get details or connect to other sites
	function webHttpClient($serviceLink,$parameters=array(),$method='GET')
	{
		//echo $serviceLink.'?'.http_build_query($parameters)."<br>";
		if($serviceLink)
		{
			/*// Initialize CURL:
			$ch = curl_init($serviceLink);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			// Store the data:
			$json = curl_exec($ch);
			curl_close($ch);
			// Decode JSON response:
			$result = json_decode($json, true);	
			return $result;*/					
			
			//Using Zend HTTP Client
			$client = new Zend_Http_Client();
			// This is equivalent to setting a URL in the Client's constructor:
			$client->setUri($serviceLink);
			
			// Adding several parameters with one call
			if(count($parameters)>0)
			{
				$client->setParameterGet($parameters);
			}		
			//Request the uri and getting the response
			$response = $client->request($method);
			//checking request is success or not
			if($response->isSuccessful())
			{
				$result = json_decode($response->getBody(), true);	
				return $result;	
			}
			
			//exit;
		} 
	}

	
	
	
}
