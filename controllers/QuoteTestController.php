<?php
/**
 * Quote Controller for Client create/Edit and Quote Create/Edit
 * @author Arun
 * @version 1.0
 */
class QuoteTestController extends Ep_Controller_Action
{
	
	public function init()
	{
		parent::init();		
		$this->_view->lang = $this->_lang;
		$this->adminLogin = Zend_Registry::get('adminLogin');
        $this->_view->userId = $this->adminLogin->userId;
        $this->_view->user_type= $this->adminLogin->type ;
        $this->sid = session_id();	

        $this->quote_documents_path=APP_PATH_ROOT.$this->_config->path->quote_documents;
        $this->mission_documents_path=APP_PATH_ROOT.$this->_config->path->mission_documents;
		$this->_view->fo_path=$this->fo_path=$this->_config->path->fo_path;
		$this->_view->baseurl=$this->baseurl=$this->_config->www->baseurl;
		$this->_view->fo_root_path=$this->fo_root_path=$this->_config->path->fo_root_path;
		$this->_arrayDb = Zend_Registry::get('_arrayDb');
        
        $this->quote_creation = Zend_Registry::get('Quote_creation');

        $this->product_array=array(
    							"redaction"=>"R&eacute;daction",
								"translation"=>"Traduction",
								"autre"=>"Autre",
								"proofreading"=>"Correction"
        						);
        $this->seo_product_array=array(
        						"seo_audit"=>"SEO audit",
        						"smo_audit"=>"SMO audit",
    							"redaction"=>"R&eacute;daction",
								"translation"=>"Traduction",
								"proofreading"=>"Correction",
								"autre"=>"Autre"
        						);

        $this->producttype_array=array(
    							"article_de_blog"=>"Article de blog",
								"descriptif_produit"=>"Desc.Produit",
								"article_seo"=>"Article SEO",
								"guide"=>"Guide",
								"news"=>"News",
								"autre"=>"Autres"
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
								"too_expensive"=>'Trop cher',
								"no_reason_client"=>'Pas de r&eacute;ponse du client',
								"project_cancelled"=>'Projet annul&eacute;',
								"delivery_time_long"=>'D&eacute;lai livraison trop long',
								"test_art_prob"=>'Probl&egrave;me article test',
								
							);
        //get Ep contacts by type
    	$client_obj=new Ep_Quote_Client();	
		$get_EP_contacts=$client_obj->getEPContacts('"salesuser","superadmin"');
		$this->_view->assign_contacts=$get_EP_contacts;


        if($this->_helper->FlashMessenger->getMessages()) {
	            $this->_view->actionmessages=$this->_helper->FlashMessenger->getMessages();
	            //echo "<pre>";print_r($this->_view->actionmessages); 
	    }
	    
    }
    public function createClientAction()
    {
    	$clientParameters=$this->_request->getParams();		

		$client_obj=new Ep_Quote_Client();			
		$contacts_obj=new Ep_Quote_ClientContacts();

		$client_id=$clientParameters['client_id'];

		if($client_id && ($clientParameters['uaction']=='edit' OR $clientParameters['uaction']=='view'))
		{
			

			$client_details=$client_obj->getClientDetails($client_id);
			//echo "<pre>";print_r($client_details);exit;

			$country_array=$this->_arrayDb->loadArrayv2("countryList", $this->_lang);

			if($client_details!='NO' )
			{
				foreach($client_details as $client)	
				{
					$client_info['client_id']=$client['identifier'];
					$client_info['email']=$client['email'];
					$client_info['company_name']=$client['company_name'];
					$client_info['web_urls']=explode("|",$client['website']);
					$client_info['website_names']=explode("|",$client['website_names']);
					$client_info['address']=$client['address'];
					$client_info['siret']=$client['siret'];
					$client_info['zipcode']=$client['zipcode'];
					$client_info['city']=$client['city'];
					$client_info['country']=$client['country'];
					$client_info['payment_type']=$client['payment_type'];
					$client_info['ca_number']=$client['ca_number'];
					//$client_info['linkedin_url']=$client['linkedin_url'];

					if($client_info['country'])
						$client_info['country_name']=$country_array[$client_info['country']];

					//client contacts
					$client_contacts=$contacts_obj->getClientContacts($client_id);
					if($client_contacts!='NO')
					{
						$client_info['contacts']=$client_contacts;
					}					

					//getting client logo
					$uploadcdir = '/home/sites/site5/web/FO/profiles/clients/logos/'.$client_id.'/'; 
					$logo_name=$client_id."_global.png";
					$logo_path=$uploadcdir.$logo_name;
					if(!is_dir($logo_path) && file_exists($logo_path))
					{
						$client_info['client_logo']=$this->fo_path.'/profiles/clients/logos/'.$client_id.'/'.$logo_name."?12345";
					}

				}
			}	


		}
		//contacts job positions
		$contacts_jobs=$contacts_obj->getClientJobs();
		if($contacts_jobs!='NO')
		{
			$this->_view->contact_jobs=$contacts_jobs;
		}

		$this->_view->client_info=$client_info;
		//echo "<pre>";print_r($this->_view->contact_jobs);exit;
		if($clientParameters['uaction']=='view')
    		$this->render('quote-view-client');
    	else
    		$this->render('quote-create-client');	
    }
    public function saveClientAction()
    {
		if($this->_request-> isPost() && $this->adminLogin->userId)           
        {        
        	$clientParameters=$this->_request->getParams();
        	
	    	$user_obj=new Ep_User_User();
			$userplus_obj=new Ep_User_UserPlus();
			$client_obj=new Ep_Quote_Client();

			$client_id=$clientParameters['client_id'];
			$password='epclient123';
			$clientParameters['company_name'] = $clientParameters['company_name'];			
			$email = $this->formatEmail(($clientParameters['company_name']));
		
			$email = trim($email)."@test.com";
			
			if($email && $password && ! $client_id)//creating new client if not exists
			{
		
				$user_obj->email=strip_tags($email);
				$user_obj->password=$password;
				$user_obj->status='Active';
				$user_obj->type='client';
				$user_obj->profile_type='';
				$user_obj->created_by='backend';				
				$user_obj->created_at=date("Y-m-d H:i:s");

				if($user_obj->insert())
				{
		
					$client_id=$client_identifier=$user_obj->getIdentifier();

					if($_FILES['logo_client'])
						$this->uploadClientLogo($_FILES,$client_identifier);

					
					//updating user table
					$suser_array['created_by']='backend';	
					$suser_array['created_user']=$this->adminLogin->userId;
					$where=" identifier='".$client_identifier."'";
					$user_obj->updateUser($suser_array,$where);						
					
					
					//inserting in Client table
					
					$client_data['user_id']=$client_identifier;
					$client_data['company_name']=$clientParameters['company_name'];
					$client_data['website']=implode("|",$clientParameters['urls']);
					$client_data['website_names']=implode("|",$clientParameters['urlnames']);

					$client_data['siret']=$clientParameters['siret'];
					$client_data['payment_type']=$clientParameters['payment_type'];
					$client_data['ca_number']=$clientParameters['ca_number'];				
					//$client_data['linkedin_url']=$clientParameters['linkedin_url'];				
					
					$client_obj->insertClient($client_data);
					

					//Inserting Userplus table
					if($clientParameters['address'])
					{
						$userplus_obj->user_id=$client_identifier;
						$userplus_obj->first_name='';
						$userplus_obj->last_name='';
						$userplus_obj->address=nl2br($clientParameters['address']);
						$userplus_obj->city=$clientParameters['city'];
						$userplus_obj->state='';
						$userplus_obj->zipcode=$clientParameters['zipcode'];
						$userplus_obj->country=$clientParameters['country'];
						$userplus_obj->phone_number='';
						$userplus_obj->insert();
					}	


					//echo "<pre>";print_r($user_obj);
					//echo "<pre>";print_r($userplus_obj);
					//echo "<pre>";print_r($client_obj);					
					
					//Inserting client contacts				
					if(is_array($clientParameters['cemail']) && count(($clientParameters['cemail'])>0))
					{
						$i=0;
						foreach($clientParameters['cemail'] as $contact)
						{
							if($clientParameters['cemail'][$i] && $clientParameters['first_name'][$i])
							{							

								$contact_obj=new Ep_Quote_ClientContacts();

								$client_contact_data['email']=$clientParameters['cemail'][$i];
								$client_contact_data['client_id']=$client_identifier;
								$client_contact_data['gender']=$clientParameters['sex'][$i];
								$client_contact_data['first_name']=$clientParameters['first_name'][$i];
								$client_contact_data['office_phone']=$clientParameters['office_phone'][$i];
								$client_contact_data['mobile_phone']=$clientParameters['mobile_phone'][$i];
								$client_contact_data['job_position']=$clientParameters['job_position'][$i];
								$client_contact_data['linkedin_url']=$clientParameters['linkedin_url'][$i];
								$client_contact_data['main_contact']=($clientParameters['main_contact']==($i+1)) ? 'yes':'no';
								$client_contact_data['created_at']=date("Y-m-d H:i:s");
								$client_contact_data['created_user']=$this->adminLogin->userId;
								
								$contact_obj->insertContact($client_contact_data);

										
							}	
							$i++;								
						}					
						
					}
				}
			}
			else if($client_id)//updating client if exists
			{				
				if($exist=$user_obj->checkProfileExist($client_id)!='NO')
				{
					if($_FILES['logo_client'])
						$this->uploadClientLogo($_FILES,$client_id);

					//Update user table
					$user_array['updated_at']=date("Y-m-d H:i:s");
					$where=" identifier='".$client_id."'";
					$user_obj->updateUser($user_array,$where);

					//Updating in Client table				
					
					$client_data['user_id']=$client_id;
					$client_data['company_name']=$clientParameters['company_name'];
					$client_data['website']=implode("|",$clientParameters['urls']);
					$client_data['website_names']=implode("|",$clientParameters['urlnames']);
					
					$client_data['siret']=$clientParameters['siret'];
					$client_data['payment_type']=$clientParameters['payment_type'];
					$client_data['ca_number']=$clientParameters['ca_number'];
					//$client_data['linkedin_url']=$clientParameters['linkedin_url'];	

					$client_obj->updateClientProfile($client_data,$client_id);

					//updating UserPLus table
					if($clientParameters['address'])
					{
						$plus_data['address']=nl2br($clientParameters['address']);
						$plus_data['zipcode']=$clientParameters['zipcode'];
						$plus_data['city']=$clientParameters['city'];
						$plus_data['country']=$clientParameters['country'];

						$query=" user_id='".$client_id."'";

						$userplus_obj->updateUserPlus($plus_data,$query);
					}
					//Updating or inserting Client contacts

					if(is_array($clientParameters['cemail']) && count($clientParameters['cemail'])>0)
					{
						$i=0;
						foreach($clientParameters['cemail'] as $contact)
						{
							
							$contact_obj=new Ep_Quote_ClientContacts();

							$client_contact_data['email']=$clientParameters['cemail'][$i];
							$client_contact_data['client_id']=$client_id;
							$client_contact_data['gender']=$clientParameters['sex'][$i];
							$client_contact_data['first_name']=$clientParameters['first_name'][$i];
							$client_contact_data['office_phone']=$clientParameters['office_phone'][$i];
							$client_contact_data['mobile_phone']=$clientParameters['mobile_phone'][$i];
							$client_contact_data['job_position']=$clientParameters['job_position'][$i];
							$client_contact_data['linkedin_url']=$clientParameters['linkedin_url'][$i];
							$client_contact_data['main_contact']=($clientParameters['main_contact']==($i+1)) ? 'yes':'no';
							$client_contact_data['created_user']=$this->adminLogin->userId;
							
							if($clientParameters['cemail'][$i] && $clientParameters['first_name'][$i] && !$clientParameters['contact_id'][$i])
							{
															
								$client_contact_data['created_at']=date("Y-m-d H:i:s");
								
								$contact_obj->insertContact($client_contact_data);	
								
							}
							else if($clientParameters['cemail'][$i] && $clientParameters['first_name'][$i] && $clientParameters['contact_id'][$i])
							{
								$contact_id=$clientParameters['contact_id'][$i];							
								
								$client_contact_data['updated_at']=date("Y-m-d H:i:s");		
								//$client_contact_data['created_user']=$this->adminLogin->userId;						
								
								//echo "<pre>";print_r($client_contact_data);
								$contact_obj->updateClientContact($client_contact_data,$contact_id);
							}

							$i++;
						}
					}


				}
			}

			$this->_helper->FlashMessenger('Profil sauvegard&eacute;');
			$this->_redirect("/quote/create-client?uaction=view&client_id=".$client_id."&submenuId=ML13-SL1");
		}	
	}
	// Format Email
	function formatEmail($email)
	{
		$email=utf8_encode($email);
		$email = frenchCharsToEnglish(str_replace(" ",".",$email));
		$email = strtolower($email);
		$email = preg_replace('/_+/','_', $email);
		$email = preg_replace('/\.+/','.', $email);
		$email = preg_replace('/^\.|\.$/',' ', $email);
		$email = preg_replace('/^_|_$/',' ', $email);
		return $email;	
	}
	// To check Contact Email through Ajax
	function clientContactValidateAction()
	{
		if($this->_request->isPost() && $_SERVER['HTTP_X_REQUESTED_WITH']=='XMLHttpRequest')
		{
			$request = $this->_request->getParams();
			$contact_email = $request['cemail'];
			$prev_email = $request['pemail'];
			$edit = $request['edit'];
			$client_obj = new Ep_User_Client();
			echo $client_obj->checkContact($contact_email,$edit,$prev_email);
		}
	}
	//upload superclient logo
	public function uploadClientLogo($FILES,$client_id)
	{
				
		$uploaddir = '/home/sites/site5/web/FO/profiles/clients/logos/'.$client_id.'/'; 			
		
		if(!is_dir($uploaddir))
		{   
			mkdir($uploaddir,0777);
			chmod($uploaddir,0777);
		}
		$file = $uploaddir.$client_id.".png"; 
		$file_global1= $uploaddir.$client_id."_global.png";
		$file_global2= $uploaddir.$client_id."_global1.png";
		list($width, $height)  = getimagesize($FILES['logo_client']['tmp_name']);

		if($width>=90 || $height>=90)
		{
			if (move_uploaded_file($FILES['logo_client']['tmp_name'], $file))
			{
				
				chmod($file,0777);	
				
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
			}
	   }
	}

	//delete client contact
	public function deleteClientContactAction()
	{
		$profile_params=$this->_request->getParams();
		$contact_obj=new Ep_Quote_ClientContacts();

		if($profile_params['identifier'])
		{
			$identifier=$profile_params['identifier'];
			$contact_obj->deleteClientContact($identifier);
			
		}    
		 
	}

	//Add job position
	public function addJobPositionAction()
	{
		$jobParameters=$this->_request->getParams();
		$contacts_obj=new Ep_Quote_ClientContacts();
		$job_title=$jobParameters['title'];

		if($job_title)
		{
			$contact_jobs_data['job_title']=$job_title;
			$job_identifier=$contacts_obj->insertClientJobs($contact_jobs_data);
			echo $job_identifier;
			exit;
		}
	}

	//
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

					foreach($websites_list as $site)
					{
						if($site)
						{
							if(in_array($site,$this->quote_creation->create_step1['client_websites']))
								$checked=' checked';
							else		
								$checked='';

							$web_sites.='<label class="uni-checkbox">
									<div class="uni-checkbox">
										<span><input type="checkbox" '.$disabled.' value="'.$site.'" class="uni_style validate[required]" name="client_websites[]" '.$checked.' style="opacity: 0;"></span>
									</div> '.$site.'
								</label>';
						}		
					}	
					echo $web_sites;	
				}				
			}
			exit;
		}
	}

	//Auto quote session for edit/duplicate
	public function autoQuoteSession($quote_id,$action)
	{
		unset($this->quote_creation->create_mission);
        unset($this->quote_creation->select_missions);
        unset($this->quote_creation->custom);
		unset($this->quote_creation->send_quote);

		$quoteObj=new Ep_Quote_Quotes();
		$quoteDetails=$quoteObj->getQuoteDetails($quote_id);

		if($quoteDetails)
		{
			foreach($quoteDetails as $quote)
			{				
				$this->quote_creation->custom['action']=$action;
				if($action=='edit')
				{					
					$this->quote_creation->custom['quote_id']=$quote['identifier'];
					if($quote['sales_review']=='validated' || $quote['sales_review']=='closed')
					{
						$this->quote_creation->custom['create_new_version']='yes';
						$this->quote_creation->custom['version']=($quote['version']+1);
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
				$this->quote_creation->create_step1['category']=$quote['category'];
				if($quote['category_other'])
					$this->quote_creation->create_step1['category_other']=$quote['category_other'];

				if($quote['websites'])
					$this->quote_creation->create_step1['client_websites']=explode("|",$quote['websites']);
				$this->quote_creation->create_step1['quote_by']=$quote['quote_by'];

				$this->quote_creation->create_step1['currency']=$quote['sales_suggested_currency'];
				$this->quote_creation->create_step1['conversion']=$quote['conversion'];
				$this->quote_creation->create_step1['quote_type']=$quote['quote_type'];

				//step2 session	
				$this->quote_creation->create_mission['quote_title']=$quote['title'];			
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
						$this->quote_creation->create_mission['language'][$i]=$quoteMmission['language_source'];
						$this->quote_creation->create_mission['languagedest'][$i]=$quoteMmission['language_dest'];
						$this->quote_creation->create_mission['producttype'][$i]=$quoteMmission['product_type'];
						$this->quote_creation->create_mission['producttypeother'][$i]=$quoteMmission['product_type_other'];
						$this->quote_creation->create_mission['nb_words'][$i]=$quoteMmission['nb_words'];
						$this->quote_creation->create_mission['volume'][$i]=$quoteMmission['volume'];
						$this->quote_creation->create_mission['comments'][$i]=$quoteMmission['comments'];
						$this->quote_creation->select_missions['missions_selected'][$i]=$quoteMmission['sales_suggested_missions'];
						
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
				//final step details
				$this->quote_creation->send_quote['sales_comment']=$quote['sales_comment'];
				$this->quote_creation->send_quote['client_email_text']=$quote['client_email_text'];	
				$this->quote_creation->send_quote['sales_delivery_time']=$quote['sales_delivery_time'];
				$this->quote_creation->send_quote['sales_delivery_time_option']=$quote['sales_delivery_time_option'];
				$this->quote_creation->send_quote['client_know']=$quote['client_know'];
				$this->quote_creation->send_quote['urgent']=$quote['urgent'];
				$this->quote_creation->send_quote['urgent_comments']=$quote['urgent_comments'];
				$this->quote_creation->send_quote['prod_review']=$quote['prod_review'];
				$this->quote_creation->send_quote['skip_prod_comments']=$quote['skip_prod_comments'];
				$this->quote_creation->send_quote['market_team_sent']=$quote['market_team_sent'];
				$this->quote_creation->send_quote['from_platform']=$quote['from_platform'];
				$this->quote_creation->send_quote['quote_send_team']=$quote['quote_send_team'];
				$this->quote_creation->send_quote['sales_review']=$quote['sales_review'];
				
				
				
				//Quote documents added to sesssion
				$files = "";
				$documents_path = explode("|",$quote['documents_path']);
				$documents_name = explode("|",$quote['documents_name']);
				$k =0;
				foreach($documents_path as $row)
				{
					if(file_exists($this->quote_documents_path.$documents_path[$k]) && !is_dir($this->quote_documents_path.$documents_path[$k]))
					{
						$files .= '<div class="topset2"><a href="/quote/download-document?type=quote&quote_id='.$quote_id.'&index='.$k.'">'.$documents_name[$k].'</a><span class="delete" rel="'.$k.'_'.$quote_id.'"> <i class="splashy-error_x"></i></span> 						</div>';
					}
					$k++;
				}
				$this->quote_creation->send_quote['documents'] = $files;

	

				//echo "<pre>";print_r($this->quote_creation->send_quote);exit;
			}
		}

		//echo "<pre>";print_r($this->quote_creation->create_step1);exit;
	}

	//Create Quote step1
	public function createQuoteStep1Action()
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
            unset($this->quote_creation->custom);
			unset($this->quote_creation->send_quote);

			if($quote_params['client_id'])
				$this->quote_creation->create_step1['client_id']=$quote_params['client_id'];
		}



		//All companies list
		$client_obj=new Ep_Quote_Client();
		$company_list=$client_obj->getAllCompanyList();
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

		$this->render('create-quote-step1');	
	}

	public function saveQuoteStep1Action()
	{
		if($this->_request-> isPost()  && $this->adminLogin->userId)            
        {
			$step1_params=$this->_request->getParams();
			//echo "<pre>";print_r($step1_params);exit;

						

			$client_id=$step1_params['client_id'];
			$category=$step1_params['category'];
			$category_other=$step1_params['category_other'];
			$currency=$step1_params['currency'];
			if($currency=='euro')
				$conversion=1;
			else	
				$conversion=$step1_params['conversion'];

			$quote_type=$step1_params['quote_type']; //added w.r.t onlytech or onlyseo quote


			//added w.r.t disabling client/category/website in edit mode
			if(!$client_id)
				$client_id=$this->quote_creation->create_step1['client_id'];
			if(!$quote_type)
				$quote_type=$this->quote_creation->create_step1['quote_type'];
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

				$this->quote_creation->create_step1['quote_type']=$quote_type;	

				//echo "<pre>";print_r($this->quote_creation->create_step1);exit;
				if($quote_type=='normal')
					$this->_redirect("/quote/create-quote-mission?submenuId=ML13-SL2");				
				elseif($quote_type=='only_tech' || $quote_type=='only_seo')
					$this->_redirect("/quote/send-team-quote?submenuId=ML13-SL2");

			}
			else
				$this->_redirect("/quote/create-quote-step1?submenuId=ML13-SL2");

		}	
	}

	//Create Quote mission
	public function createQuoteMissionAction()
	{		
		setlocale(LC_TIME, "fr_FR");
		//echo "<pre>";print_r($this->quote_creation->select_missions['missions_selected']);
		if($this->quote_creation->create_step1['client_id'])
		{
			$client_id=$this->quote_creation->create_step1['client_id'];
			$client_obj=new Ep_Quote_Client();		
			$quote_obj=new Ep_Quote_Quotes();

			$this->quote_creation->custom['mission_added']='no';

			//getting Client details of selected client
			$client_details=$client_obj->getClientDetails($client_id);
			if($client_details!='NO')
			{
				$this->quote_creation->create_mission['company_name']=$client_details[0]['company_name'];
				$this->quote_creation->create_mission['ca_number']=$client_details[0]['ca_number'];
				$this->quote_creation->create_mission['client_id']=$client_details[0]['identifier'];
			}

			$quote_monthly_cnt=$quote_obj->getMonthlyCount($client_id);
			$quote_monthly_cnt+=1;

			//titel should be form DB in edit
			if($this->quote_creation->custom['action']!='edit' || !$this->quote_creation->create_mission['quote_title'] || ($this->quote_creation->custom['action']=='edit' && $this->quote_creation->custom['create_new_version']=='yes'))
			{
				if($this->quote_creation->custom['action']=='edit' && $this->quote_creation->custom['create_new_version']=='yes')
				{
					$old_version='v'.($this->quote_creation->custom['version']-1);
					$new_version='v'.($this->quote_creation->custom['version']);
					$old_title=$this->quote_creation->create_mission['quote_title'];

					$this->quote_creation->create_mission['quote_title']=str_replace($old_version, $new_version, $old_title);

				}
				else
					$this->quote_creation->create_mission['quote_title']='Devis - '.$this->quote_creation->create_mission['company_name'].' - '.strftime("%B %Y").' - '.$quote_monthly_cnt. " - v1";	
			}

			

			$this->quote_creation->create_mission['category_name']=$this->getCategoryName($this->quote_creation->create_step1['category']);

			//getting Quote user details of selected Bo user
			$quote_by=$this->quote_creation->create_step1['quote_by'];
			$bo_user_details=$client_obj->getQuoteUserDetails($quote_by);
			if($bo_user_details!='NO')
			{
				$this->quote_creation->create_mission['quote_user_name']=$bo_user_details[0]['first_name'].' '.$bo_user_details[0]['last_name'];
				$this->quote_creation->create_mission['email']=$bo_user_details[0]['email'];
				$this->quote_creation->create_mission['phone_number']=$bo_user_details[0]['phone_number'];
								
			}
			//ALL language list
			$language_array=$this->_arrayDb->loadArrayv2("EP_LANGUAGES", $this->_lang);			
			natsort($language_array);
			//echo "<pre>";print_r($language_array);exit;
			

	        //quote missions
	        $quote_missions=array();
			if(is_array($this->quote_creation->create_mission['product']) && count($this->quote_creation->create_mission['product'])>0)
			{
				$i=0;
				foreach($this->quote_creation->create_mission['product'] as $mission)
				{
					$quote_missions[$i]['product']=$this->quote_creation->create_mission['product'][$i];
					$quote_missions[$i]['language']=$this->quote_creation->create_mission['language'][$i];
					$quote_missions[$i]['languagedest']=$this->quote_creation->create_mission['languagedest'][$i];
					$quote_missions[$i]['producttype']=$this->quote_creation->create_mission['producttype'][$i];
					$quote_missions[$i]['producttypeother']=$this->quote_creation->create_mission['producttypeother'][$i];					
					$quote_missions[$i]['nb_words']=$this->quote_creation->create_mission['nb_words'][$i];
					$quote_missions[$i]['volume']=$this->quote_creation->create_mission['volume'][$i];
					$quote_missions[$i]['comments']=$this->quote_creation->create_mission['comments'][$i];
					
					//Added w.r.t edit
					if($this->quote_creation->custom['action']=='edit')
					{ 
						if($this->quote_creation->create_mission['identifier'])
							$quote_missions[$i]['identifier']=$this->quote_creation->create_mission['identifier'][$i];

						else if($this->quote_creation->create_mission['mission_identifier'][$i] && !$quote_missions[$i]['identifier'])
							$quote_missions[$i]['identifier']=$this->quote_creation->create_mission['mission_identifier'][$i];
					}

					$i++;
				}
			}


	        $this->_view->ep_language_list=$language_array;
	        $this->_view->create_mission=$this->quote_creation->create_mission;
	        $this->_view->create_step1=$this->quote_creation->create_step1;
	        $this->_view->quote_missions=$quote_missions;

	        //echo "<pre>";print_r($quote_missions);print_r($this->quote_creation->create_mission);exit;

			$this->render('create-quote-mission');
		}
		else	
			$this->_redirect("/quote/create-quote-step1?submenuId=ML13-SL2");
	}	

	//save Quote mission
	public function saveQuoteMissionAction()
	{
		if($this->_request-> isPost()  && $this->adminLogin->userId)            
        {
			$mission_params=$this->_request->getParams();

			//unset($this->quote_creation->create_mission['product']);

			$this->quote_creation->create_mission['product']=$mission_params['product'];
			$this->quote_creation->create_mission['language']=$mission_params['language'];
			$this->quote_creation->create_mission['languagedest']=$mission_params['languagedest'];
			$this->quote_creation->create_mission['producttype']=$mission_params['producttype'];
			$this->quote_creation->create_mission['producttypeother']=$mission_params['producttypeother'];
			$this->quote_creation->create_mission['nb_words']=$mission_params['nb_words'];
			$this->quote_creation->create_mission['volume']=$mission_params['volume'];
			$this->quote_creation->create_mission['comments']=$mission_params['comments'];

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

			$this->_redirect("/quote/select-quote-mission?submenuId=ML13-SL2");

		}	
	}
	//check whether any info updated in edit mode or not
	function checkMissionUpdate($index,$mission_id)
	{
		//current details
		$currentproduct=$this->quote_creation->create_mission['product'][$index];
		$currentproduct_type=$this->quote_creation->create_mission['producttype'][$index];
		$currentlanguage_source=$this->quote_creation->create_mission['language'][$index];
		$currentlanguage_dest=$this->quote_creation->create_mission['languagedest'][$index];
		$currentnb_words=$this->quote_creation->create_mission['nb_words'][$index];
		$currentvolume=$this->quote_creation->create_mission['volume'][$index];

		$MissionObj=new Ep_Quote_QuoteMissions();				
		$Parameters['mission_id']=$mission_id;
		$MissionDetails=$MissionObj->getMissionDetails($Parameters);
		if($MissionDetails)
		{
			$saved_product=$MissionDetails[0]['product'];
			$saved_product_type=$MissionDetails[0]['product_type'];
			$saved_language_source=$MissionDetails[0]['language_source'];
			$saved_language_dest=$MissionDetails[0]['language_dest'];
			$saved_nb_words=$MissionDetails[0]['nb_words'];
			$saved_volume=$MissionDetails[0]['volume'];

			if($saved_product!=$currentproduct || $saved_product_type!=$currentproduct_type || $saved_language_source!=$currentlanguage_source
				|| $saved_nb_words!=$currentnb_words || $saved_volume!=$currentvolume)
				return 'updated';
			else
				return NULL;
		}
	}

	//select missions from previous missions
	public function selectQuoteMissionAction()
	{
		$quote_missions=array();

		if(!is_array($this->quote_creation->select_missions['missions_selected']))
			$this->quote_creation->select_missions['missions_selected']=array();

		//echo "<pre>";print_r($this->quote_creation->select_missions['missions_selected']);

		if(is_array($this->quote_creation->create_mission['product']) && count($this->quote_creation->create_mission['product'])>0)
		{
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
				$quote_missions[$i]['comments']=$this->quote_creation->create_mission['comments'][$i];

				$quote_missions[$i]['identifier']=$this->quote_creation->create_mission['identifier'][$i];				

				//mission object
				$mission_obj=new Ep_Quote_Mission();
				
				

				/*dont change the order of this array*/
				$searchParameters['product']=$this->quote_creation->create_mission['product'][$i];
				$searchParameters['language']=$this->quote_creation->create_mission['language'][$i];
				$searchParameters['languagedest']=$this->quote_creation->create_mission['languagedest'][$i];
				$searchParameters['producttype']=$this->quote_creation->create_mission['producttype'][$i];
				$searchParameters['volume']=$this->quote_creation->create_mission['volume'][$i];
				$searchParameters['nb_words']=$this->quote_creation->create_mission['nb_words'][$i];
				

				$missionDetails=$mission_obj->getMissionDetails($searchParameters,3);
				if($missionDetails)
				{
					$m=0;
					foreach($missionDetails as $misson)
					{
						$missionDetails[$m]['category_name']=$this->getCategoryName($misson['category']);
						$missionDetails[$m]['product']=$this->product_array[$misson['type']];
						$missionDetails[$m]['language1_name']=$this->getLanguageName($misson['language1']);
						$missionDetails[$m]['producttype']=$this->producttype_array[$misson['type_of_article']];

						//Added w.r.t conversion						
						if($misson['writing_cost_before_signature_currency']!=$this->quote_creation->create_step1['currency'])
						{
							$missionDetails[$m]['writing_cost_before_signature']=($misson['writing_cost_before_signature']*$this->quote_creation->create_step1['conversion']);
							$missionDetails[$m]['correction_cost_before_signature']=($misson['correction_cost_before_signature']*$this->quote_creation->create_step1['conversion']);
							$missionDetails[$m]['other_cost_before_signature']=($misson['other_cost_before_signature']*$this->quote_creation->create_step1['conversion']);
							$missionDetails[$m]['unit_price']=($misson['selling_price']*$this->quote_creation->create_step1['conversion']);
						}
						else
							$missionDetails[$m]['unit_price']=$misson['selling_price'];
						

						$missionDetails[$m]['mission_turnover']=($misson['num_of_articles']*$missionDetails[$m]['unit_price'])/1000;
						

						$m++;
					}

					$quote_missions[$i]['missionDetails']=$missionDetails;
				}
				
				
				$i++;
			}			

 			//echo "<pre>";print_r($quote_missions);exit;
 			$this->quote_creation->create_mission['quote_missions']=$quote_missions;

			$this->_view->quote_missions=$quote_missions;
			$this->_view->create_mission=$this->quote_creation->create_mission;
	        $this->_view->create_step1=$this->quote_creation->create_step1;
	        $this->_view->select_missions=$this->quote_creation->select_missions;

			$this->render('select-quote-mission');

		}
		else
			$this->_redirect("/quote/create-quote-step1?submenuId=ML13-SL2");		



	}

	//save selected missions in session
	public function saveSelectedMissionAction()
	{
		if($this->_request-> isPost()  && $this->adminLogin->userId)            
        {
			$selected_parameters=$this->_request->getParams();

			//$this->quote_creation->select_missions['single_article_price']=array();	
			//$this->quote_creation->select_missions['mission_ca']=array();

			//get all selected missions
			$k=1;
			foreach($_POST as $key => $missions)
			{			    
			    if (strpos($key, 'overview_missions_') === 0)
			    {	
			    	foreach($missions as $mission)
			    	{
			    		$missions_selected[]=$mission;			    		
			    		$this->quote_creation->select_missions['single_article_price'][]=$_POST['single_article_price_'.($k)];
			    		$this->quote_creation->select_missions['mission_ca'][]=$_POST['mission_ca_'.($k)];
			    		
			    	}
			    	$k++;	
			    }
			}
			
			if($_SERVER['HTTP_X_REQUESTED_WITH']=='XMLHttpRequest') //for auto match popup
			{
				$mission_id=$selected_parameters['mission_id'];

				$updateMissionObj=new Ep_Quote_QuoteMissions();

				//GET QUOTE MISSION DETAILS
				$seoParameters['mission_id']=$mission_id;
				$MissionDetails=$updateMissionObj->getMissionDetails($seoParameters);

				$mission_nb_words=$MissionDetails[0]['nb_words'];
				$conversion=$MissionDetails[0]['conversion'];


				$newSuggesion=$missions_selected[0];

				//get archieved mission details
				$archMissionObj=new Ep_Quote_Mission();
				$archData['mission_id']=$newSuggesion;
				$archMissionDetails=$archMissionObj->getMissionDetails($archData);

				if($archMissionDetails)
				{					
					$data['unit_price']=($archMissionDetails[0]['selling_price']);
					$data['mission_length']=$archMissionDetails[0]['mission_length'];
					$data['margin_percentage']=$archMissionDetails[0]['margin_before_signature'];
					//$data['volume']=$archMissionDetails[0]['num_of_articles'];

					$archieve_nb_words=$archMissionDetails[0]['article_length'];		
					
					$writing_cost=$archMissionDetails[0]['writing_cost_before_signature']*$conversion;
					$correction_cost=$archMissionDetails[0]['correction_cost_before_signature']*$conversion;
					$other_cost=$archMissionDetails[0]['other_cost_before_signature']*$conversion;
					 
				}

				//updating the details
				$data['sales_suggested_missions']=$newSuggesion;
				$updateMissionObj->updateQuoteMission($data,$mission_id);
				

				$json_update['mission_id']=$mission_id;
				$json_update['writing_cost']=zero_cut((($mission_nb_words/$archieve_nb_words)*$writing_cost),2);
				$json_update['correction_cost']=zero_cut((($mission_nb_words/$archieve_nb_words)*$correction_cost),2);
				$json_update['other_cost']=zero_cut((($mission_nb_words/$archieve_nb_words)*$other_cost),2);

				//echo $archieve_nb_words."--".$writer_cost."--".$correction_cost."--".$other_cost."--".$mission_nb_words;
				//echo "<pre>";print_r($json_update);exit;
				echo json_encode($json_update);
			}
			else
			{			    

				$this->quote_creation->select_missions['missions_selected']=$missions_selected;
				$this->quote_creation->select_missions['total_suggested_price']=$selected_parameters['total_suggested_price'];
				$this->quote_creation->select_missions['currency']=$this->quote_creation->create_step1['currency'];//$selected_parameters['currency_type_1'];
				
				//echo "<pre>";print_r($selected_parameters);print_r($this->quote_creation->select_missions);exit;				
				$this->_redirect("/quote/send-quote?submenuId=ML13-SL2");	
			}	

		}	
		else
			$this->_redirect("/quote/create-quote-step1?submenuId=ML13-SL2");	
		
	}
	//send quote to tech/seo/prod team to review
	public function sendQuoteAction()
	{
		if(is_array($this->quote_creation->select_missions['missions_selected']))
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
		    $this->_view->select_missions=$this->quote_creation->select_missions;
		    $this->_view->custom=$this->quote_creation->custom;
		    $this->_view->quote_missions=$this->quote_creation->create_mission['quote_missions'];

		    //Added w.r.t edit/duplicate
		    $this->_view->send_quote=$this->quote_creation->send_quote;

			$this->render('send-quote');
		}
		else
			$this->_redirect("/quote/create-quote-step1?submenuId=ML13-SL2");	
	}

	//insert quote and all missions in db
	public function saveSendQuoteAction()
	{
		if($this->_request-> isPost()  && $this->adminLogin->userId)            
        {
			$final_parameters=$this->_request->getParams();

			$quote_obj=new Ep_Quote_Quotes();

			//echo "<pre>";print_r($final_parameters);exit;

			//insert Quotes
			$quotes_data['title']=$this->quote_creation->create_mission['quote_title'];
			$quotes_data['client_id']=$this->quote_creation->create_step1['client_id'];
			$quotes_data['category']=$this->quote_creation->create_step1['category'];
			if($quotes_data['category']=='other')
					$quotes_data['category_other']=isodec($this->quote_creation->create_step1['category_other']);
			if($this->quote_creation->create_step1['client_websites'])
			$quotes_data['websites']=implode("|",$this->quote_creation->create_step1['client_websites']);
			$quotes_data['quote_by']=$this->quote_creation->create_step1['quote_by'];
			$quotes_data['created_by']=$this->adminLogin->userId;
			$quotes_data['sales_suggested_price']=$this->quote_creation->select_missions['total_suggested_price'];
			$quotes_data['sales_suggested_currency']=$this->quote_creation->select_missions['currency'];
			$quotes_data['sales_comment']=$final_parameters['bo_comments'];
			$quotes_data['client_email_text']=$final_parameters['client_email'];
			//$quotes_data['documents_path']='path';
			$quotes_data['conversion']=$this->quote_creation->create_step1['conversion'];

			$quotes_data['sales_delivery_time']=$final_parameters['delivery_time'];
			$quotes_data['sales_delivery_time_option']=$final_parameters['delivery_option'];
			$quotes_data['client_know']=$final_parameters['client_know']? 'no':'yes';
			$quotes_data['urgent']=$final_parameters['urgent']? 'yes':'no';
			$quotes_data['urgent_comments']=$final_parameters['urgent_comments']?$final_parameters['urgent_comments']:NULL;			
		
			$quotes_data['market_team_sent']=$final_parameters['market_team_sent'];
			if($quotes_data['market_team_sent']=='yes')
				$quotes_data['from_platform']=$final_parameters['from_platform'];

			//Quote current version
			if($this->quote_creation->custom['version'])
			{
				$version=$this->quote_creation->custom['version'];
			}
			else
				$version=1;
			$quotes_data['version']=$version;

			//Getting Quote details if quote id available
			$quoteIdentifier=$this->quote_creation->custom['quote_id'];
			if($quoteIdentifier)
			{
				$quoteEditDetails=$quote_obj->getQuoteDetails($quoteIdentifier);
				$sales_review_staus=$quoteEditDetails[0]['sales_review'];
			}

			//check manager is on holiday or not
			$salesManager_holiday=$this->configval["sales_manager_holiday"];
			$user_type=$this->adminLogin->type;

			
			//Staus of tech,seo,prod and sales
			if($this->quote_creation->custom['action']=='edit' && $this->quote_creation->custom['create_new_version']=='yes')//new version quote
			{
				if(isset($final_parameters['send_low_quote']) OR isset($final_parameters['send_big_quote']) )
				{
					$quotes_data['quote_send_team']=$final_parameters['quote_send_team'];

					if(($final_parameters['quote_send_team']=='send_sales_team'))
					{							
						$quotes_data["sales_validation_expires"]=time()+($this->configval['sales_validation_timeline']*60*60);
						$quotes_data['skip_prod_comments']=$final_parameters['skip_prod_comments'] ? $final_parameters['skip_prod_comments']:NULL;
						$prod_hours=$this->configval['sales_validation_timeline'];												
						$onlySales=true;
					}
					else
					{
						if(($final_parameters['quote_send_team']=='send_tech_prod_team') || ($final_parameters['quote_send_team']=='send_tech_team'))
						{							
							$quotes_data['tec_review']='not_done';							
							$tech_seo_time=$this->configval['quote_sent_timeline'];

							$quotes_data['tech_timeline']=NULL;
							$quotes_data['tech_challenge_comments']='';
							$quotes_data['tech_challenge']='yes';

							if($final_parameters['quote_send_team']=='send_tech_prod_team')//if tech & prod
								$quotes_data['prod_review']='challenged';						
						}
						else if(($final_parameters['quote_send_team']=='send_seo_prod_team') || ($final_parameters['quote_send_team']=='send_seo_team'))
						{
							$quotes_data['seo_review']='not_done';							
							$tech_seo_time=$this->configval['quote_sent_timeline'];

							$quotes_data['seo_timeline']=NULL;
							$quotes_data['seo_comments']='';
							$quotes_data['seo_challenge']='yes';

							if($final_parameters['quote_send_team']=='send_seo_prod_team')//if seo & prod
								$quotes_data['prod_review']='challenged';	
						}
						else if(($final_parameters['quote_send_team']=='send_prod_team'))
						{							
							$quotes_data['prod_review']='challenged';
							$quotes_data["prod_timeline"]=time()+($this->configval['prod_timeline']*60*60);				
							$onlyProd=true;
						}				
						else
						{
							$quotes_data['tec_review']='not_done';	
							$quotes_data['seo_review']='not_done';
							$quotes_data['prod_review']='challenged';

							$tech_seo_time=$this->configval['quote_sent_timeline'];
						}

						$quotes_data["response_time"]=time()+($this->configval['quote_sent_timeline']*60*60);
						$prod_hours=($this->configval['prod_timeline']+$this->configval['sales_validation_timeline']);
						$quotes_data['skip_prod_comments']='';
					}	
					
					$quote_end_hours=$this->configval['quote_end_time'];
					$quotes_data['quote_delivery_hours']=($prod_hours+$tech_seo_time+$quote_end_hours);

					//echo "<pre>";print_r($final_parameters);print_r($quotes_data);exit;
				}				
			}
			elseif($this->quote_creation->custom['action']=='edit' && !$this->quote_creation->custom['create_new_version'] && $sales_review_staus!='to_be_approve') //quote edit
			{
				if(isset($final_parameters['send_low_quote']) OR isset($final_parameters['send_big_quote']) )
				{
					//$quotes_data['quote_send_team']=$final_parameters['quote_send_team'];
					
					$oldTechReview=$quoteEditDetails[0]['tec_review'];
					$oldSeoReview=$quoteEditDetails[0]['seo_review'];
					$oldProdReview=$quoteEditDetails[0]['prod_review'];
					$oldSalesReview=$quoteEditDetails[0]['sales_review'];

					if(($final_parameters['quote_send_team']=='send_sales_team'))
					{												
						$quotes_data["sales_validation_expires"]=time()+($this->configval['sales_validation_timeline']*60*60);						
					}
					else
					{
						if(($final_parameters['quote_send_team']=='send_tech_prod_team') || ($final_parameters['quote_send_team']=='send_tech_team'))
						{
							//restart tech review 
							if($oldTechReview=='auto_skipped' || $oldTechReview=='skipped' || $oldTechReview=='not_done')
							{
								$quotes_data['tec_review']='not_done';
								$tech_seo_time=$this->configval['quote_sent_timeline'];		
							}
							elseif($oldTechReview=='challenged' || $oldTechReview=='validated')
							{
								$quotes_data['tec_review']='challenged';
							}
							
							if($final_parameters['quote_send_team']=='send_tech_prod_team')//if tech & prod
								$quotes_data['prod_review']='challenged';							
						}
						else if(($final_parameters['quote_send_team']=='send_seo_prod_team')  || ($final_parameters['quote_send_team']=='send_seo_team'))
						{
							//restart seo review 
							if($oldSeoReview=='auto_skipped' || $oldSeoReview=='skipped' || $oldSeoReview=='not_done')
							{
								$quotes_data['seo_review']='not_done';
								$tech_seo_time=$this->configval['quote_sent_timeline'];		
							}
							elseif($oldSeoReview=='challenged' || $oldSeoReview=='validated')
							{
								$quotes_data['seo_review']='challenged';
							}
							
							if($final_parameters['quote_send_team']=='send_seo_prod_team')//if seo & prod
								$quotes_data['prod_review']='challenged';															
						}
						else if(($final_parameters['quote_send_team']=='send_prod_team'))
						{
							//restart prod_review
							if($oldProdReview=='validated' || $oldProdReview=='auto_skipped' || $oldProdReview=='challenged' )
							{
								$quotes_data['prod_review']='challenged';
								$quotes_data["prod_timeline"]=time()+($this->configval['prod_timeline']*60*60);
							}
						}
						else if(($final_parameters['quote_send_team']=='send_all_team'))
						{
							//restart tech review 
							if($oldTechReview=='auto_skipped' || $oldTechReview=='skipped' || $oldTechReview=='not_done')
							{
								$quotes_data['tec_review']='not_done';
								$tech_seo_time=$this->configval['quote_sent_timeline'];		
							}
							elseif($oldTechReview=='challenged' || $oldTechReview=='validated')
							{
								$quotes_data['tec_review']='challenged';
							}
							//restart seo review 
							if($oldSeoReview=='auto_skipped' || $oldSeoReview=='skipped' || $oldSeoReview=='not_done')
							{
								$quotes_data['seo_review']='not_done';
								$tech_seo_time=$this->configval['quote_sent_timeline'];		
							}
							elseif($oldSeoReview=='challenged' || $oldSeoReview=='validated')
							{
								$quotes_data['seo_review']='challenged';
							}

							//restart prod_review
							if($oldProdReview=='validated' || $oldProdReview=='auto_skipped' || $oldProdReview=='challenged' )
							{
								$quotes_data['prod_review']='challenged';
								$quotes_data["prod_timeline"]=time()+($this->configval['prod_timeline']*60*60);
							}
						}

					}
					if($tech_seo_time)
						$quotes_data["response_time"]=time()+($tech_seo_time*60*60);
					//echo "<pre>";print_r($quotes_data);exit;
					$edited=TRUE;
				}	
			}
			else//quote creation v1
			{
				if(isset($final_parameters['send_low_quote']) OR isset($final_parameters['send_big_quote']) )
				{
					$quotes_data['quote_send_team']=$final_parameters['quote_send_team'];

					if(($final_parameters['quote_send_team']=='send_sales_team')&&isset($final_parameters['send_low_quote']))
					{
						$quotes_data['tec_review']='auto_skipped';	
						$quotes_data['seo_review']='auto_skipped';
						$quotes_data['prod_review']='auto_skipped';

						$onlySales=true;
						$quotes_data["sales_validation_expires"]=time()+($this->configval['sales_validation_timeline']*60*60);
						$quotes_data['skip_prod_comments']=$final_parameters['skip_prod_comments']?$final_parameters['skip_prod_comments']:NULL;
						$prod_hours=$this->configval['sales_validation_timeline'];
					}
					else
					{
					

						if(($final_parameters['quote_send_team']=='send_tech_prod_team'))
						{
							$quotes_data['tec_review']='not_done';	
							$quotes_data['seo_review']='auto_skipped';
							$quotes_data['prod_review']='challenged';

							$tech_seo_time=$this->configval['quote_sent_timeline'];
						}
						else if(($final_parameters['quote_send_team']=='send_seo_prod_team'))
						{
							$quotes_data['tec_review']='auto_skipped';	
							$quotes_data['seo_review']='not_done';
							$quotes_data['prod_review']='challenged';

							$tech_seo_time=$this->configval['quote_sent_timeline'];
						}
						else if(($final_parameters['quote_send_team']=='send_prod_team'))
						{
							$quotes_data['tec_review']='auto_skipped';	
							$quotes_data['seo_review']='auto_skipped';
							$quotes_data['prod_review']='challenged';

							$quotes_data["prod_timeline"]=time()+($this->configval['prod_timeline']*60*60);				
							$onlyProd=true;
						}				
						else
						{
							$quotes_data['tec_review']='not_done';	
							$quotes_data['seo_review']='not_done';
							$quotes_data['prod_review']='challenged';

							$tech_seo_time=$this->configval['quote_sent_timeline'];
						}

						$quotes_data["response_time"]=time()+($this->configval['quote_sent_timeline']*60*60);
						$prod_hours=($this->configval['prod_timeline']+$this->configval['sales_validation_timeline']);
					}	
					$quote_end_hours=$this->configval['quote_end_time'];
					$quotes_data['quote_delivery_hours']=($prod_hours+$tech_seo_time+$quote_end_hours);
				}
				
				//quote to be approved if created by sales user when sales manager is available
				if($salesManager_holiday=='no' && $user_type=='salesuser' && $quotes_data['sales_suggested_price']>=5000)
				{
					$quotes_data['sales_review']='to_be_approve';
					$send_manager_email=TRUE;
				}
				else
				{
					$quotes_data['sales_review']='not_done';
					$send_manager_email=FALSE;
				}
				
			}		

			//echo "<pre>";print_r($quotes_data);exit;


			//versioning when edited validated quote
			if($this->quote_creation->custom['action']=='edit' && $this->quote_creation->custom['create_new_version']=='yes')
			{
				//Insert this quote in to Quote version table
				$quoteIdentifier=$this->quote_creation->custom['quote_id'];
				if($quoteIdentifier)
				{
					$quote_obj->insertQuoteVersion($quoteIdentifier);

					//versioning Tech missions
					$techMissionObj=new Ep_Quote_TechMissions();
					$techParams['quote_id']=$quoteIdentifier;
					$techMissionsDetails=$techMissionObj->getTechMissionDetails($techParams);
					if($techMissionsDetails)
					{
						foreach($techMissionsDetails as $techMission)
						{							
							$techMissionId=$techMission['identifier'];
							$techMissionObj->insertMissionVersion($techMissionId);

							//update tech version
							$update_tech['version']=$version;
							$techMissionObj->updateTechMission($update_tech,$techMissionId);
							//if($quotes_data['tec_review']=='auto_skipped')
								//$techMissionObj->deleteTechMission($techMissionId);
						}
					}	

					//versioning SEO missions
					$seoParameters['quote_id']=$quoteIdentifier;
					$seoParameters['misson_user_type']='seo';
					$quoteMission_obj=new Ep_Quote_QuoteMissions();
					$seoMissionDetails=$quoteMission_obj->getMissionDetails($seoParameters);
					if($seoMissionDetails)
					{
						foreach($seoMissionDetails as  $seoMission)
						{
							$seoMissionId=$seoMission['identifier'];
							$quoteMission_obj->insertMissionVersion($seoMissionId);	

							//update seo mission version
							$update_seo['version']=$version;
							$quoteMission_obj->updateQuoteMission($update_seo,$seoMissionId);						

							//versioning Prod Missions
							$prodObj=new Ep_Quote_ProdMissions();
							$prodParams['quote_mission_id']=$seoMissionId;
							$prodMissionDetails=$prodObj->getProdMissionDetails($prodParams);
							if($prodMissionDetails)
							{
								foreach($prodMissionDetails as $prodMission)
								{
									$prodMissionId=$prodMission['identifier'];
									$prodObj->insertMissionVersion($prodMissionId);

									//update prod mission version
									$update_prod['version']=$version;
									$prodObj->updateProdMission($update_prod,$prodMissionId);

									//deleting prod mission from Prodmissions after insert into prod versioning
									//$prodObj->deleteProdMission($prodMissionId);
								}
							}
							//deleting seo mission from quote missions after insert into versioning
							//if($quotes_data['seo_review']=='auto_skipped')
							//	$quoteMission_obj->deleteQuoteMission($seoMissionId);

						}
						
					}

					

				}	


				$quotes_data['sales_review']='not_done';				
				$quotes_data['sales_margin_percentage']=0;				
				//$quotes_data['techmissions_assigned']='';	
				$quotes_data['created_at']=date("Y-m-d H:i:s");	
				$quotes_data['updated_at']=NULL;
				
				$quotes_data['final_turnover']=0;
				$quotes_data['final_margin']=0;
				$quotes_data['final_mission_length']=0;

				$quotes_data['closed_comments']='';
				//$quotes_data['prod_timeline']=0;
				$quotes_data['signed_comments']=NULL;
				$quotes_data['signed_at']=NULL;
				$quotes_data['sign_expire_timeline']=NULL;
				$quotes_data['closed_reason']=NULL;				
				$quotes_data['boot_customer']=NULL;

			}
		
			//echo "<pre>";;print_r($this->quote_creation->custom);print_r($quotes_data);exit;
			//echo "<pre>";print_r($this->quote_creation->create_mission['quote_missions']);print_r($quotes_data);exit;

			try
			{                              	
				if($this->quote_creation->custom['quote_id'])
				{	
					if(!$this->quote_creation->custom['create_new_version'])
					{
						$quotes_data['updated_at']=date("Y-m-d H:i:s");		
					}
					
					$quoteIdentifier=$this->quote_creation->custom['quote_id'];
					
					$quote_obj->updateQuote($quotes_data,$quoteIdentifier);
				}
				else
				{			
					if(!$this->quote_creation->custom['create_new_version'])
						$quotes_data['created_at']=date("Y-m-d H:i:s");
					
					$quote_obj->insertQuote($quotes_data);
					$quoteIdentifier=$quote_obj->getIdentifier();	
				}

								
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
					if($update)
					{
						 $quotes_update_data = array();
						 $quoteDetails=$quote_obj->getQuoteDetails($quoteIdentifier);
						 $uploaded_documents1 = explode("|",$quoteDetails[0]['documents_path']);
						 $documents_path =array_merge($documents_path,$uploaded_documents1);
						 $quotes_update_data['documents_path']=implode("|",$documents_path);
						 $document_names =explode("|",$quoteDetails[0]['documents_name']);
						 $documents_name =array_merge($documents_name,$document_names);
						 $quotes_update_data['documents_name']=implode("|",$documents_name);
						 $quote_obj->updateQuote($quotes_update_data,$quoteIdentifier);
					}
					//echo "<pre>";print_r($quotes_update_data);print_r($documents_name);exit;
	                
	            }
                
				//Quote missions insertion
				if(count($this->quote_creation->create_mission['quote_missions'])>0)
				{
					$sales_margin_percentage=0;
					$margin=0;
					foreach($this->quote_creation->create_mission['quote_missions'] as $qkey=>$quoteMission)
					{

						$quoteMission_obj=new Ep_Quote_QuoteMissions();
						
						$quoteMission_data['quote_id']=$quoteIdentifier;
						$quoteMission_data['product']=$quoteMission['product'];
						$quoteMission_data['product_type']=$quoteMission['producttype'];
						if($quoteMission['producttype']=='autre')
							$quoteMission_data['product_type_other']=$quoteMission['producttypeother'];
						else
							$quoteMission_data['product_type_other']=NULL;


						$quoteMission_data['category']=$this->quote_creation->create_step1['category'];
						if($quoteMission['product']=='translation')
							$quoteMission_data['language_dest']=$quoteMission['languagedest'];
						if($quoteMission['product']!='auture')
						{
							$quoteMission_data['language_source']=$quoteMission['language'];
							$quoteMission_data['nb_words']=$quoteMission['nb_words'];
							$quoteMission_data['volume']=$quoteMission['volume'];
						}	
						$quoteMission_data['comments']=$quoteMission['comments'];
						$quoteMission_data['misson_user_type']='sales';
						$quoteMission_data['created_by']=$this->quote_creation->create_step1['quote_by'];
						
						
						$suggested_missions=array();

						if(count($quoteMission['missionDetails'])>0)
						{
							foreach($quoteMission['missionDetails'] as $missions_archived)
							{
								if(in_array($missions_archived['id'],$this->quote_creation->select_missions['missions_selected']))
								{
									$suggested_missions[]=$missions_archived['id'];
								}
							}
						}
						$quoteMission_data['sales_suggested_missions']=implode(",",$suggested_missions);

						$archmission_obj=new Ep_Quote_Mission();
						$archParameters['mission_id']=$quoteMission_data['sales_suggested_missions'];
						$suggested_mission_details=$archmission_obj->getMissionDetails($archParameters);
						if($suggested_mission_details)
						{

							$quoteMission_data['mission_length']=$suggested_mission_details[0]['mission_length'];
							$quoteMission_data['mission_length_option']='days';
							//$quoteMission_data['unit_price']=($suggested_mission_details[0]['selling_price']);
							$quoteMission_data['unit_price']=$this->quote_creation->select_missions['single_article_price'][$qkey];
							$quoteMission_data['margin_percentage']=$suggested_mission_details[0]['margin_before_signature'];							

							if($quotes_data['prod_review']=='auto_skipped') //added w.r.t low quote  direct validation
							{
								
								$nb_words=($quoteMission['nb_words']/$suggested_mission_details[0]['article_length']);
								$redactionCost=$nb_words*($suggested_mission_details[0]['writing_cost_before_signature']);
								$correctionCost=$nb_words*($suggested_mission_details[0]['correction_cost_before_signature']);
								$otherCost=$nb_words*($suggested_mission_details[0]['other_cost_before_signature']);

								$internalcost=($redactionCost+$correctionCost+$otherCost);
								$internalcost=number_format($internalcost,2,'.','');

								$quoteMission_data['internal_cost']=$internalcost;//$quoteMission_data['unit_price'];
							}
						}
						$quoteMission_data['version']=$version;

						//versioning quote missions
						if($this->quote_creation->custom['action']=='edit' && $this->quote_creation->custom['create_new_version']=='yes')
						{

							//Insert this mission in to QuoteMissionsversions table							
							if($quoteMission['identifier'])
							{
								$quoteMission_obj->insertMissionVersion($quoteMission['identifier']);

								//versioning Prod Missions
								$prodObj=new Ep_Quote_ProdMissions();
								$prodParams['quote_mission_id']=$quoteMission['identifier'];
								$prodMissionDetails=$prodObj->getProdMissionDetails($prodParams);
								if($prodMissionDetails)
								{
									foreach($prodMissionDetails as $prodMission)
									{
										$prodMissionId=$prodMission['identifier'];
										$prodObj->insertMissionVersion($prodMissionId);

										//deleting prod mission from Prodmissions after insert into prod versioning
										//$prodObj->deleteProdMission($prodMissionId);
									}
								}
							}

							$quoteMission_data['cost']=0;
							$quoteMission_data['created_at']=date("Y-m-d H:i:s");	
							$quoteMission_data['updated_at']=NULL;	
							//if($quotes_data['prod_review']!='auto_skipped')
								//$quoteMission_data['internal_cost']=0;	
							$quoteMission_data['turnover']=0;
							$quoteMission_data['include_final']='yes';			

						}						



						//echo "<pre>";print_r($quoteMission_data);
						if($quoteMission['identifier'])
						{
							if(!$this->quote_creation->custom['create_new_version'])
								$quoteMission_data['updated_at']=date("Y-m-d H:i:s");
							$quoteMission_obj->updateQuoteMission($quoteMission_data,$quoteMission['identifier']);
						}
						else
						{
							if(!$this->quote_creation->custom['create_new_version'])
								$quoteMission_data['created_at']=date("Y-m-d H:i:s");
							$quoteMission_obj->insertQuoteMission($quoteMission_data);

							//updating prod status if new mission added in edit mode
							if(!$this->quote_creation->custom['create_new_version'] && $this->quote_creation->custom['action']=='edit')
								$newmissionAdded=TRUE;								
						}	

						//quote sales margin;
						$sales_margin_percentage+=$quoteMission_data['margin_percentage'];
						$margin++;
					}
					//updating sales margin in Quote table
					$avg_sales_margin_percentage=($sales_margin_percentage/$margin);
					$margin_data['sales_margin_percentage']=round($avg_sales_margin_percentage,2);					
					//echo $quoteIdentifier;echo "<pre>";print_r($margin_data);exit;
					$quote_obj->updateQuote($margin_data,$quoteIdentifier);

				}
					//Insert Quote log

					$log_params['quote_id']	= $quoteIdentifier;
					$log_params['bo_user']	= $this->adminLogin->userId;
					$log_params['quote_size']=$quotes_data['sales_suggested_price'] < 5000 ? "small" :"big";
					$log_params['urgent']	= $final_parameters['urgent']? 'urgent':'';
					$log_params['version']	= $version;					
					$log_params['created_date']	= date("Y-m-d H:i:s");

					$log_obj=new Ep_Quote_QuotesLog();
					if($edited || $sales_review_staus=='to_be_approve')
					{
						$log_params['action']	= 'quote_updated';
						$actionId=9;

						if($final_parameters['quote_updated_comments'])
						{
							$log_params['comments']=$final_parameters['quote_updated_comments'];
						}
					}
					else
					{
						$log_params['action']	= 'quote_created';
						$actionId=1;	

						if($final_parameters['bo_comments'])
						{
							$log_params['comments']=$final_parameters['bo_comments'];
						}
					}

					$log_obj->insertLog($actionId,$log_params);			
					
					//echo "<pre>";print_r($log_params);exit;

			}
			catch(Zend_Exception $e)
            {
                echo $e->getMessage();exit;                             

            }


            //sending intimation emails when quote edited
            $update_comments= $final_parameters['quote_updated_comments'];
            if($edited && $update_comments)
			{
				$bo_user_type='sales';				
				$this->sendIntimationEmail($quoteIdentifier,$bo_user_type,$update_comments,$newmissionAdded);
				//exit;
			}	


			//sending email to seo &tech OR Prod to challenge after creating the quote
			if(!$edited && !$onlySales && !$send_manager_email)
			{
				$client_obj=new Ep_Quote_Client();	
				
				if($onlyProd)
					$email_users=$get_head_prods=$client_obj->getEPContacts('"produser","prodsubmanager"');
				else if($final_parameters['quote_send_team']=='send_tech_prod_team')
					$email_users=$get_head_tech_seos=$client_obj->getEPContacts('"techuser","techmanager"');
				else if($final_parameters['quote_send_team']=='send_seo_prod_team')	
					$email_users=$get_head_tech_seos=$client_obj->getEPContacts('"seouser","seomanager"');					
				else
					$email_users=$get_head_tech_seos=$client_obj->getEPContacts('"seouser","seomanager","techuser","techmanager"');

				if(count($email_users)>0)
				{
					
					foreach($email_users as $user=>$name)
					{
						$mail_obj=new Ep_Message_AutoEmails();
						$receiver_id=$user;
						$mail_parameters['bo_user']=$user;
						$mail_parameters['sales_user']=$this->adminLogin->userId;
						$mail_parameters['quote_title']=$quotes_data['title'];
						$mail_parameters['sales_suggested_price']=$quotes_data['sales_suggested_price']." ".$quotes_data['sales_suggested_currency']."s";
						$mail_parameters['challenge_link']='/quote/sales-quotes-list?submenuId=ML13-SL2';

						$mail_obj->sendQuotePersonalEmail($receiver_id,142,$mail_parameters);        	
		        	}
		        }			
				
			}
			else if($send_manager_email)//send email to sales manager to approve the quote 
			{
				$client_obj=new Ep_Quote_Client();
				$email_users=$get_head_prods=$client_obj->getEPContacts('"salesmanager"');

				if(count($email_users)>0)
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
		        }
			}           


            unset($this->quote_creation->create_step1);
            unset($this->quote_creation->create_mission);
            unset($this->quote_creation->select_missions);
            unset($this->quote_creation->custom);
			unset($this->quote_creation->send_quote);
			if($edited)
			{
            	$this->_helper->FlashMessenger('Devis updat&eacute; avec succ&egrave;s');
			}
            else
            {
            	if(!$send_manager_email)
            		$this->_helper->FlashMessenger('Devis cr&eacute;e avec succ&egrave;s');

            	if($onlySales)
            		$this->_helper->FlashMessenger('Onlysales');
            	else if($onlyProd)
            		$this->_helper->FlashMessenger('Onlyprod');
            }

            $this->_redirect("/quote/sales-quotes-list?submenuId=ML13-SL2");	

			//echo "<pre>";print_r($quotes_data);//print_r($this->quote_creation->select_missions);print_r($this->quote_creation->create_mission['quote_missions']);

		}	
	}

	//send quote to only tech or seo
	public function sendTeamQuoteAction()
	{
		if($this->quote_creation->create_step1['quote_type']=='only_tech' || $this->quote_creation->create_step1['quote_type']=='only_seo')
		{			
			
			$client_id=$this->quote_creation->create_step1['client_id'];
			$client_obj=new Ep_Quote_Client();		
			$quote_obj=new Ep_Quote_Quotes();

			$this->quote_creation->custom['mission_added']='no';

			//getting Client details of selected client
			$client_details=$client_obj->getClientDetails($client_id);
			if($client_details!='NO')
			{
				$this->quote_creation->create_mission['company_name']=$client_details[0]['company_name'];
				$this->quote_creation->create_mission['ca_number']=$client_details[0]['ca_number'];
				$this->quote_creation->create_mission['client_id']=$client_details[0]['identifier'];
			}	

			

			$quote_monthly_cnt=$quote_obj->getMonthlyCount($client_id);
			$quote_monthly_cnt+=1;

			//titel should be form DB in edit
			if($this->quote_creation->custom['action']!='edit' || !$this->quote_creation->create_mission['quote_title'] || ($this->quote_creation->custom['action']=='edit' && $this->quote_creation->custom['create_new_version']=='yes'))
			{
				if($this->quote_creation->custom['action']=='edit' && $this->quote_creation->custom['create_new_version']=='yes')
				{
					$old_version='v'.($this->quote_creation->custom['version']-1);
					$new_version='v'.($this->quote_creation->custom['version']);
					$old_title=$this->quote_creation->create_mission['quote_title'];

					$this->quote_creation->create_mission['quote_title']=str_replace($old_version, $new_version, $old_title);

				}
				else
					$this->quote_creation->create_mission['quote_title']='Devis - '.$this->quote_creation->create_mission['company_name'].' - '.strftime("%B %Y").' - '.$quote_monthly_cnt. " - v1";	
			}

			//getting Quote user details of selected Bo user			
			$quote_by=$this->quote_creation->create_step1['quote_by'];
			$bo_user_details=$client_obj->getQuoteUserDetails($quote_by);
			if($bo_user_details!='NO')
			{
				$this->quote_creation->create_mission['quote_user_name']=$bo_user_details[0]['first_name'].' '.$bo_user_details[0]['last_name'];
				$this->quote_creation->create_mission['email']=$bo_user_details[0]['email'];
				$this->quote_creation->create_mission['phone_number']=$bo_user_details[0]['phone_number'];
								
			}

			$this->quote_creation->create_mission['category_name']=$this->getCategoryName($this->quote_creation->create_step1['category']);
			

			$this->_view->create_mission=$this->quote_creation->create_mission;
			$this->_view->create_step1=$this->quote_creation->create_step1;
			$this->_view->custom=$this->quote_creation->custom;
		    //Added w.r.t edit/duplicate
		    $this->_view->send_quote=$this->quote_creation->send_quote;
		    
		    //echo "<pre>";print_r($this->quote_creation->send_team_quote);exit;

			$this->render('send-team-quote');
		}
		else
			$this->_redirect("/quote/create-quote-step1?submenuId=ML13-SL2");	
	}

	//insert tech/seo quote in db
	public function saveSendTeamQuoteAction()
	{
		if($this->_request-> isPost()  && $this->adminLogin->userId)            
        {
			$final_parameters=$this->_request->getParams();

			$quote_obj=new Ep_Quote_Quotes();

			//echo "<pre>";print_r($final_parameters);exit;

			//insert Quotes
			$quotes_data['title']=$this->quote_creation->create_mission['quote_title'];
			$quotes_data['client_id']=$this->quote_creation->create_step1['client_id'];
			$quotes_data['category']=$this->quote_creation->create_step1['category'];
			if($quotes_data['category']=='other')
					$quotes_data['category_other']=isodec($this->quote_creation->create_step1['category_other']);
			if($this->quote_creation->create_step1['client_websites'])
			$quotes_data['websites']=implode("|",$this->quote_creation->create_step1['client_websites']);
			$quotes_data['quote_by']=$this->quote_creation->create_step1['quote_by'];
			$quotes_data['created_by']=$this->adminLogin->userId;
			$quotes_data['sales_suggested_price']=0;
			$quotes_data['sales_suggested_currency']=$this->quote_creation->create_step1['currency'];
			$quotes_data['sales_comment']=$final_parameters['bo_comments'];
			$quotes_data['client_email_text']=$final_parameters['client_email'];
			//$quotes_data['documents_path']='path';
			$quotes_data['conversion']=$this->quote_creation->create_step1['conversion'];

			$quotes_data['sales_delivery_time']=$final_parameters['delivery_time'];
			$quotes_data['sales_delivery_time_option']=$final_parameters['delivery_option'];
			$quotes_data['client_know']=$final_parameters['client_know']? 'no':'yes';
			$quotes_data['urgent']=$final_parameters['urgent']? 'yes':'no';
			$quotes_data['urgent_comments']=$final_parameters['urgent_comments']?$final_parameters['urgent_comments']:NULL;			
		
			$quotes_data['market_team_sent']=$final_parameters['market_team_sent'];
			if($quotes_data['market_team_sent']=='yes')
				$quotes_data['from_platform']=$final_parameters['from_platform'];
				
			$quotes_data['quote_type']=$this->quote_creation->create_step1['quote_type'];	

			//Quote current version
			if($this->quote_creation->custom['version'])
			{
				$version=$this->quote_creation->custom['version'];
			}
			else
				$version=1;
			$quotes_data['version']=$version;

			//Getting Quote details if quote id available
			$quoteIdentifier=$this->quote_creation->custom['quote_id'];
			if($quoteIdentifier)
			{
				$quoteEditDetails=$quote_obj->getQuoteDetails($quoteIdentifier);
				$sales_review_staus=$quoteEditDetails[0]['sales_review'];
			}		

			
			//Staus of tech,seo,prod and sales
			if($this->quote_creation->custom['action']=='edit' && $this->quote_creation->custom['create_new_version']=='yes')//new version quote
			{
				$quotes_data['quote_send_team']=$final_parameters['quote_send_team'];

				if(($final_parameters['quote_send_team']=='send_sales_team'))
				{							
					$quotes_data["sales_validation_expires"]=time()+($this->configval['sales_validation_timeline']*60*60);						
					$prod_hours=$this->configval['sales_validation_timeline'];
					$onlySales=true;
				}
				else if($final_parameters['quote_send_team']=='send_tech_team')
				{							
					$quotes_data['tec_review']='not_done';							
					$tech_seo_time=$this->configval['quote_sent_timeline'];

					$quotes_data['tech_timeline']=NULL;
					$quotes_data['tech_challenge_comments']='';
					$quotes_data['tech_challenge']='yes';
				}
				else if($final_parameters['quote_send_team']=='send_seo_team')
				{							
					$quotes_data['seo_review']='not_done';							
					$tech_seo_time=$this->configval['quote_sent_timeline'];

					$quotes_data['seo_timeline']=NULL;
					$quotes_data['seo_comments']='';
					$quotes_data['seo_challenge']='yes';
				}				
			}
			elseif($this->quote_creation->custom['action']=='edit' && !$this->quote_creation->custom['create_new_version']) //quote edit
			{	
				
				$oldTechReview=$quoteEditDetails[0]['tec_review'];
				$oldSeoReview=$quoteEditDetails[0]['seo_review'];

				if(($final_parameters['quote_send_team']=='send_sales_team'))
				{	

					$quotes_data["sales_validation_expires"]=time()+($this->configval['sales_validation_timeline']*60*60);
				}
				else if($final_parameters['quote_send_team']=='send_tech_team')
				{							
					//restart tech review 
					if($oldTechReview=='auto_skipped' || $oldTechReview=='skipped' || $oldTechReview=='not_done')
					{
						$quotes_data['tec_review']='not_done';
						$tech_seo_time=$this->configval['quote_sent_timeline'];		
					}
					elseif($oldTechReview=='challenged' || $oldTechReview=='validated')
					{
						$quotes_data['tec_review']='challenged';
					}
				}
				else if($final_parameters['quote_send_team']=='send_seo_team')
				{
					//restart seo review 
					if($oldSeoReview=='auto_skipped' || $oldSeoReview=='skipped' || $oldSeoReview=='not_done')
					{
						$quotes_data['seo_review']='not_done';
						$tech_seo_time=$this->configval['quote_sent_timeline'];		
					}
					elseif($oldSeoReview=='challenged' || $oldSeoReview=='validated')
					{
						$quotes_data['seo_review']='challenged';
					}

				}
				if($tech_seo_time)
					$quotes_data["response_time"]=time()+($tech_seo_time*60*60);
				$edited=TRUE;				
			}
			else//quote creation v1
			{
				if(isset($final_parameters['send_team_quote']))
				{
					if($quotes_data['quote_type']=='only_tech')
						$quotes_data['quote_send_team']='send_tech_team';
					elseif($quotes_data['quote_type']=='only_seo')
						$quotes_data['quote_send_team']='send_seo_team';

					if($quotes_data['quote_type']=='only_tech')
					{
						$quotes_data['tec_review']='not_done';	
						$quotes_data['seo_review']='auto_skipped';
						$quotes_data['prod_review']='auto_skipped';

						$tech_seo_time=$this->configval['quote_sent_timeline'];
					}
					else if($quotes_data['quote_type']=='only_seo')
					{
						$quotes_data['tec_review']='auto_skipped';	
						$quotes_data['seo_review']='not_done';
						$quotes_data['prod_review']='auto_skipped';

						$tech_seo_time=$this->configval['quote_sent_timeline'];
					}
					
					$quotes_data["response_time"]=time()+($this->configval['quote_sent_timeline']*60*60);
					$quote_end_hours=$this->configval['quote_end_time'];
					
					$quotes_data['quote_delivery_hours']=($tech_seo_time+$quote_end_hours);
				}
			}		

			//echo "<pre>";print_r($quotes_data);print_r($final_parameters);exit;


			//versioning when edited validated quote
			if($this->quote_creation->custom['action']=='edit' && $this->quote_creation->custom['create_new_version']=='yes')
			{
				//Insert this quote in to Quote version table
				$quoteIdentifier=$this->quote_creation->custom['quote_id'];
				if($quoteIdentifier)
				{
					$quote_obj->insertQuoteVersion($quoteIdentifier);

					//versioning Tech missions
					$techMissionObj=new Ep_Quote_TechMissions();
					$techParams['quote_id']=$quoteIdentifier;
					$techMissionsDetails=$techMissionObj->getTechMissionDetails($techParams);
					if($techMissionsDetails)
					{
						foreach($techMissionsDetails as $techMission)
						{							
							$techMissionId=$techMission['identifier'];
							$techMissionObj->insertMissionVersion($techMissionId);

							//update tech version
							$update_tech['version']=$version;
							$techMissionObj->updateTechMission($update_tech,$techMissionId);
							//if($quotes_data['tec_review']=='auto_skipped')
								//$techMissionObj->deleteTechMission($techMissionId);
						}
					}	

					//versioning SEO missions
					$seoParameters['quote_id']=$quoteIdentifier;
					$seoParameters['misson_user_type']='seo';
					$quoteMission_obj=new Ep_Quote_QuoteMissions();
					$seoMissionDetails=$quoteMission_obj->getMissionDetails($seoParameters);
					if($seoMissionDetails)
					{
						foreach($seoMissionDetails as  $seoMission)
						{
							$seoMissionId=$seoMission['identifier'];
							$quoteMission_obj->insertMissionVersion($seoMissionId);	

							//update seo mission version
							$update_seo['version']=$version;
							$quoteMission_obj->updateQuoteMission($update_seo,$seoMissionId);
						}
						
					}
				}	


				$quotes_data['sales_review']='not_done';				
				$quotes_data['sales_margin_percentage']=0;				
				//$quotes_data['techmissions_assigned']='';	
				$quotes_data['created_at']=date("Y-m-d H:i:s");	
				$quotes_data['updated_at']=NULL;
				
				$quotes_data['final_turnover']=0;
				$quotes_data['final_margin']=0;
				$quotes_data['final_mission_length']=0;

				$quotes_data['closed_comments']='';
				//$quotes_data['prod_timeline']=0;
				$quotes_data['signed_comments']=NULL;
				$quotes_data['signed_at']=NULL;
				$quotes_data['sign_expire_timeline']=NULL;
				$quotes_data['closed_reason']=NULL;				
				$quotes_data['boot_customer']=NULL;

			}
		
			//echo "<pre>";;print_r($this->quote_creation->custom);print_r($quotes_data);exit;
			//echo "<pre>";print_r($this->quote_creation->create_mission['quote_missions']);print_r($quotes_data);exit;

			try
			{                              	
				if($this->quote_creation->custom['quote_id'])
				{	
					if(!$this->quote_creation->custom['create_new_version'])
					{
						$quotes_data['updated_at']=date("Y-m-d H:i:s");		
					}
					$quoteIdentifier=$this->quote_creation->custom['quote_id'];
					
					$quote_obj->updateQuote($quotes_data,$quoteIdentifier);
				}
				else
				{			
					if(!$this->quote_creation->custom['create_new_version'])
						$quotes_data['created_at']=date("Y-m-d H:i:s");
					
					$quote_obj->insertQuote($quotes_data);
					$quoteIdentifier=$quote_obj->getIdentifier();	
				}
								
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
					if($update)
					{
						 $quotes_update_data = array();
						 $quoteDetails=$quote_obj->getQuoteDetails($quoteIdentifier);
						 $uploaded_documents1 = explode("|",$quoteDetails[0]['documents_path']);
						 $documents_path =array_merge($documents_path,$uploaded_documents1);
						 $quotes_update_data['documents_path']=implode("|",$documents_path);
						 $document_names =explode("|",$quoteDetails[0]['documents_name']);
						 $documents_name =array_merge($documents_name,$document_names);
						 $quotes_update_data['documents_name']=implode("|",$documents_name);
						 $quote_obj->updateQuote($quotes_update_data,$quoteIdentifier);
					}
					//echo "<pre>";print_r($quotes_update_data);print_r($documents_name);exit;
	                
	            }               
				
				//Insert Quote log

				$log_params['quote_id']	= $quoteIdentifier;
				$log_params['bo_user']	= $this->adminLogin->userId;
				$log_params['quote_size']=$quotes_data['sales_suggested_price'] < 5000 ? "small" :"big";
				$log_params['urgent']	= $final_parameters['urgent']? 'urgent':'';
				$log_params['version']	= $version;					
				$log_params['created_date']	= date("Y-m-d H:i:s");

				$log_obj=new Ep_Quote_QuotesLog();
				if($edited)
				{
					$log_params['action']	= 'quote_updated';
					$actionId=9;

					if($final_parameters['quote_updated_comments'])
					{
						$log_params['comments']=$final_parameters['quote_updated_comments'];
					}
				}
				else
				{
					$log_params['action']	= 'quote_created';
					$actionId=1;	

					if($final_parameters['bo_comments'])
					{
						$log_params['comments']=$final_parameters['bo_comments'];
					}
				}

				$log_obj->insertLog($actionId,$log_params);			
				
				//echo "<pre>";print_r($log_params);exit;

			}
			catch(Zend_Exception $e)
            {
                echo $e->getMessage();exit;                             

            }

			//sending email to seo &tech OR Prod to challenge after creating the quote
			if(!$edited)
			{
				$client_obj=new Ep_Quote_Client();	
				
				if($quotes_data['quote_type']=='only_tech')
					$email_users=$get_head_tech_seos=$client_obj->getEPContacts('"techuser","techmanager"');
				elseif($quotes_data['quote_type']=='only_seo')	
					$email_users=$get_head_tech_seos=$client_obj->getEPContacts('"seouser","seomanager"');
				if(count($email_users)>0)
				{
					
					foreach($email_users as $user=>$name)
					{
						$mail_obj=new Ep_Message_AutoEmails();
						$receiver_id=$user;
						$mail_parameters['bo_user']=$user;
						$mail_parameters['sales_user']=$this->adminLogin->userId;
						$mail_parameters['quote_title']=$quotes_data['title'];
						$mail_parameters['sales_suggested_price']=$quotes_data['sales_suggested_price']." ".$quotes_data['sales_suggested_currency']."s";
						$mail_parameters['challenge_link']='/quote/sales-quotes-list?submenuId=ML13-SL2';

						$mail_obj->sendQuotePersonalEmail($receiver_id,142,$mail_parameters);        	
		        	}
		        }			
				
			}

            unset($this->quote_creation->create_step1);
            unset($this->quote_creation->create_mission);
            unset($this->quote_creation->select_missions);
            unset($this->quote_creation->custom);
			unset($this->quote_creation->send_quote);
			unset($this->quote_creation->send_team_quote);
			if($edited)
			{
            	$this->_helper->FlashMessenger('Devis updat&eacute; avec succ&egrave;s');
			}
            else
            {
            	$this->_helper->FlashMessenger('Devis cr&eacute;e avec succ&egrave;s');

            	if($onlySales)
            		$this->_helper->FlashMessenger('Onlysales');
            	else if($quotes_data['quote_type']=='only_tech')
					$this->_helper->FlashMessenger('Onlytech');
				else if($quotes_data['quote_type']=='only_seo')
					$this->_helper->FlashMessenger('Onlyseo');
            }
			
            $this->_redirect("/quote/sales-quotes-list?submenuId=ML13-SL2");
		}	
	}

	//get all sales quotes list
	public function salesQuotesListAction()
	{		
		$quote_obj=new Ep_Quote_Quotes();
		$listParams=$this->_request->getParams();

		$quoteList=$quote_obj->getAllQuotesList();

		if($quoteList)
		{
			$q=0;
			$total_turnover=0;
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
				$bo_user_details=$client_obj->getQuoteUserDetails($quote['created_by']);

				$quoteList[$q]['owner']=$bo_user_details[0]['first_name'].' '.$bo_user_details[0]['last_name'];

				$prod_team=$quote['prod_review']!='auto_skipped' ? 'Prod ': '';
				$seo_team=$quote['seo_review']!='auto_skipped' ? 'Seo ': '';
				$tech_team=$quote['tec_review']!='auto_skipped' ? 'Tech ': '';

				$quoteList[$q]['team']=$prod_team.$seo_team.$tech_team;

				if(!$quoteList[$q]['team'])
					$quoteList[$q]['team']='only sales';


				if($quote['sales_review']!='closed')
					$total_turnover+=$quote['turnover'];

				if($quote['sales_review']=='validated')
					$validated_turnover+=$quote['turnover']	;
			
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
			
				$q++;
			}

			$this->_view->quote_list=$quoteList;
			$this->_view->total_turnover=$total_turnover;
			$this->_view->validated_turnover=$validated_turnover;

		}	
		$this->_view->quote_sent_timeline=$this->configval["quote_sent_timeline"];
		$this->_view->prod_timeline=$this->configval["prod_timeline"];

		$this->_view->techManager_holiday=$this->configval["tech_manager_holiday"];
		$this->_view->seoManager_holiday=$this->configval["seo_manager_holiday"];

		//echo "<pre>";print_r($quoteList);exit;

		$this->render('sales-quotes-list');

		if($listParams['file_download']=='yes' && $listParams['quote_id'])
			header( "refresh:1;url=/quote/download-quote-xls?quote_id=".$listParams['quote_id']);
	}

	//Tech team quote review
	public function techQuoteReviewAction()
	{
		$tech_parameters=$this->_request->getParams();

		$quote_id=$tech_parameters['quote_id'];

		$quote_obj=new Ep_Quote_Quotes();

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
					$related_files = "";
					if($quote['documents_path'])
					{
						$files = array('documents_path'=>$quote['documents_path'],'documents_name'=>$quote['documents_name'],'quote_id'=>$quote_id,'delete'=>false);
						$related_files = $this->getQuoteFiles($files);
					}

					$quoteDetails[$q]['related_files']=$related_files;

					$quoteDetails[$q]['sales_suggested_price_format']=number_format($quote['sales_suggested_price'], 2, ',', ' ');
					$quoteDetails[$q]['comment_time']=time_ago($quote['created_at']);

					if($quote['tech_timeline'])
						$quoteDetails[$q]['tech_timeline_stamp']=strtotime($quote['tech_timeline']);
					
					if($quote['tech_timeline'])
					{
						$quoteDetails[$q]['tech_timeline_date']=date("d/m/Y",strtotime($quote['tech_timeline']));
						$quoteDetails[$q]['tech_timeline_time']=date("H:i",strtotime($quote['tech_timeline']));
					}

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
					$searchParameters['misson_user_type']='sales';
					$quoteMission_obj=new Ep_Quote_QuoteMissions();
					$missonDetails=$quoteMission_obj->getMissionDetails($searchParameters);
					if($missonDetails)
					{
						$m=0;
						foreach($missonDetails as $mission)
						{
							$missonDetails[$m]['product_name']=$this->product_array[$mission['product']];			
							$missonDetails[$m]['language_source_name']=$this->getLanguageName($mission['language_source']);
							$missonDetails[$m]['product_type_name']=$this->producttype_array[$mission['product_type']];
							if($mission['language_dest'])
								$missonDetails[$m]['language_dest_name']=$this->getLanguageName($mission['language_dest']);

							$missonDetails[$m]['comment_time']=time_ago($mission['created_at']);


							//mission versionings if version is gt 1
							if($quote['version']>1)
							{
								$previousVersion=($quote['version']-1);

								$quoteMissionObj=new Ep_Quote_QuoteMissions();
								$previousMissionDetails=$quoteMissionObj->getMissionVersionDetails($mission['identifier'],$previousVersion,'sales');
								
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
									$allVersionMissionDetails=$quoteMissionObj->getMissionVersionDetails($mission['identifier'],NULL,'sales');
									if($allVersionMissionDetails)
									{
										$table_start='<table class="table quote-history table-striped">';
										$table_end='</table>';
										$language_versions=$product_type_versions=$volume_versions=$nb_words_versions='';
										$price_versions=$mission_length_versions='';

										foreach($allVersionMissionDetails as $versions)
										{
										 	if($versions['product']=='translation')
										  		$language= $this->getLanguageName($versions['language_source'])." > ".$this->getLanguageName($vmission['language_dest']);
										  	else
										  		$language= $this->getLanguageName($versions['language_source']);
										  	
										  	$created_at=date("d/m/Y", strtotime($versions['created_at']));

										  	$language_versions.="<tr><td>$language</td><td>$created_at</td></tr>";
										  	$product_type_versions.="<tr><td>".$this->producttype_array[$versions['product_type']]."</td><td>$created_at</td></tr>";
										  	$volume_versions.="<tr><td>".$versions['volume']."</td><td>$created_at</td></tr>";
										  	$nb_words_versions.="<tr><td>".$versions['nb_words']."</td><td>$created_at</td></tr>";
										  	$price_versions.="<tr><td>".zero_cut($versions['unit_price'],2)." &". $versions['sales_suggested_currency'].";</td><td>$created_at</td></tr>";

										  	$mission_length_option=$versions['mission_length_option']=='days' ? ' Jours' : ' Hours';

										  	$mission_length_versions.="<tr><td>".$versions['mission_length']." $mission_length_option</td><td>$created_at</td></tr>";
										}										
									}


									//checking the version differences
									if($mission['language_source'] !=$previousMissionDetails[0]['language_source'])
									{
										$missonDetails[$m]['language_difference']='yes';
										$missonDetails[$m]['language_versions']=$table_start.$language_versions.$table_end;
									}

									if($mission['language_dest'] !=$previousMissionDetails[0]['language_dest'])
									{
										$missonDetails[$m]['language_difference']='yes';
										$missonDetails[$m]['language_versions']=$table_start.$language_versions.$table_end;
									}

									if($mission['product_type'] !=$previousMissionDetails[0]['product_type'])
									{
										$missonDetails[$m]['product_type_difference']='yes';
										$missonDetails[$m]['product_type_versions']=$table_start.$product_type_versions.$table_end;
									
									}

									if($mission['volume'] !=$previousMissionDetails[0]['volume'])
									{
										$missonDetails[$m]['volume_difference']='yes';
										$missonDetails[$m]['volume_versions']=$table_start.$volume_versions.$table_end;
									}
									
									if($mission['nb_words'] !=$previousMissionDetails[0]['nb_words'])
									{
										$missonDetails[$m]['nb_words_difference']='yes';
										$missonDetails[$m]['nb_words_versions']=$table_start.$nb_words_versions.$table_end;
									}
									
									if($mission['unit_price'] !=$previousMissionDetails[0]['unit_price'])
									{
										$missonDetails[$m]['unit_price_difference']='yes';
										$missonDetails[$m]['price_versions']=$table_start.$price_versions.$table_end;
									}

									$current_mission_lenght=$mission['mission_length_option']=='hours' ? ($mission['mission_length']/24) : $mission['mission_length'];
									$previous_mission_lenght=$previousMissionDetails[0]['mission_length_option']=='hours' ? ($previousMissionDetails[0]['mission_length']/24) : $previousMissionDetails[0]['mission_length'];
									if($current_mission_lenght !=$previous_mission_lenght)
									{
										$missonDetails[$m]['mission_length_difference']='yes';	
										$missonDetails[$m]['mission_length_versions']=$table_start.$mission_length_versions.$table_end;
									}



									$missonDetails[$m]['previousMissionDetails']=$previousMissionDetails;
								}	

							}

							$m++;
						}	
						$quoteDetails[$q]['mission_details']=$missonDetails;

						//Deleted mission version details
						if($quote['version']>1)
						{
							$previousVersion=($quote['version']-1);
							$deletedMissionVersions=$this->deletedMissionVersions($quote['identifier'],$previousVersion,'sales');
							if($deletedMissionVersions)
								$quoteDetails[$q]['deletedMissionVersions']=$deletedMissionVersions;
						}	
					}	

					$q++;
				}
			}
			$this->_view->quoteDetails=$quoteDetails;

			//getting tech mission details
			$tech_obj=new Ep_Quote_TechMissions();
			$searchParameters['quote_id']=$quote_id;
			$techMissionDetails=$tech_obj->getTechMissionDetails($searchParameters);
			if($techMissionDetails)
			{
				$t=0;
				foreach($techMissionDetails as $mission)
				{
					//mission versionings if version is gt 1
					if($quoteDetails[0]['version']>1)
					{
						$previousVersion=($quoteDetails[0]['version']-1);

						$techMissionObj=new Ep_Quote_TechMissions();
						$previousMissionDetails=$techMissionObj->getMissionVersionDetails($mission['identifier'],$quoteDetails[0]['identifier'],$previousVersion);
						
						if($previousMissionDetails)
						{						
							//Get All version details of a mission									
							$allVersionMissionDetails=$techMissionObj->getMissionVersionDetails($mission['identifier'],$quoteDetails[0]['identifier']);
							if($allVersionMissionDetails)
							{
								$table_start='<table class="table quote-history table-striped">';
								$table_end='</table>';								
								$price_versions=$mission_length_versions='';
								$title_versions='';

								foreach($allVersionMissionDetails as $versions)
								{
								 	
								  	
								  	$created_at=date("d/m/Y", strtotime($versions['created_at']));
								  	
								  	$title_versions.="<tr><td>".$versions['title']."</td><td>$created_at</td></tr>";

								  	$price_versions.="<tr><td>".zero_cut($versions['cost'],2)." &". $versions['currency'].";</td><td>$created_at</td></tr>";

								  	$mission_length_option=$versions['delivery_option']=='days' ? ' Jours' : ' Hours';

								  	$mission_length_versions.="<tr><td>".$versions['delivery_time']." $mission_length_option</td><td>$created_at</td></tr>";
								}										
							}


							//checking the version differences
							if($mission['title'] !=$previousMissionDetails[0]['title'])
							{
								$techMissionDetails[$t]['title_difference']='yes';
								$techMissionDetails[$t]['title_versions']=$table_start.$title_versions.$table_end;
							}


							if($mission['cost'] !=$previousMissionDetails[0]['cost'])
							{
								$techMissionDetails[$t]['cost_difference']='yes';
								$techMissionDetails[$t]['price_versions']=$table_start.$price_versions.$table_end;
							}

							$current_mission_lenght=$mission['delivery_option']=='hours' ? ($mission['delivery_time']/24) : $mission['delivery_time'];
							$previous_mission_lenght=$previousMissionDetails[0]['delivery_option']=='hours' ? ($previousMissionDetails[0]['delivery_time']/24) : $previousMissionDetails[0]['delivery_time'];
							if($current_mission_lenght !=$previous_mission_lenght)
							{
								$techMissionDetails[$t]['mission_length_difference']='yes';	
								$techMissionDetails[$t]['mission_length_versions']=$table_start.$mission_length_versions.$table_end;
							}



							$techMissionDetails[$t]['previousMissionDetails']=$previousMissionDetails;
						}	

					}
					$techMissionDetails[$t]['files'] = "";
					if($mission['documents_path'])
					{
						/* $exploded_file_paths = explode("|",$mission['documents_path']);
						$exploded_file_names = explode("|",$mission['documents_name']);
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
									$files .= '<div class="topset2"><a href="/quote/download-document?type=tech_mission&mission_id='.$mission['identifier'].'&index='.$k.'">'.$fname.'</a><span class="deletetech" rel="'.$k.'_'.$mission['identifier'].'"> <i class="splashy-error_x"></i></span></div>';
									
							}
							$k++;
						} */
						$filesarray = array('documents_path'=>$mission['documents_path'],'documents_name'=>$mission['documents_name'],'id'=>$mission['identifier'],'delete'=>true);
						$files = $this->getTechFiles($filesarray);
						$techMissionDetails[$t]['files'] = $files;
					}
				
					$t++;
				}		
					
				$this->_view->techMissionDetails=$techMissionDetails;
			}
		}
		//check manager is on holiday or not
		$techManager_holiday=$this->configval["tech_manager_holiday"];
		$user_type=$this->adminLogin->type;
		if($techManager_holiday=='no' && $user_type=='techuser')
			$this->_view->show_validate='no';
		else
			$this->_view->show_validate='yes';	

		//echo "<pre>";print_r($quoteDetails);exit;

		$this->render('tech-quote-review');
	}
	//save tech reviews based on actions
	public function saveTechReviewAction()
	{
		if($this->_request-> isPost()  && $this->adminLogin->userId)
		{
			$tech_params=$this->_request->getParams();

			//echo "<pre>";print_r($tech_params);exit;

			$quote_id=$tech_params['quote_id'];

			if(isset($tech_params['review_skip'])) $status='skipped';
			else if(isset($tech_params['review_challenge'])) $status='challenged';
			else if(isset($tech_params['review_save'])) $status='challenged';
			else if(isset($tech_params['review_validate'])) $status='validated';

			if($quote_id)
			{	
				
				//get Quote version
				$quote_obj=new Ep_Quote_Quotes();
				$version=$quote_obj->getQuoteVersion($quote_id);
			

				//Insert Quote log
				$log_params['quote_id']	= $quote_id;
				$log_params['bo_user']	= $this->adminLogin->userId;					
				$log_params['version']	= $version;
				$log_params['action']	= 'tech_'.$status;		

				

				if(isset($tech_params['review_skip'])|| isset($tech_params['review_challenge']))
				{					
					$quote_obj=new Ep_Quote_Quotes();
					$update_quote['tec_review']=$status;

					if(isset($tech_params['review_challenge']))
					{
						$tech_params['tech_timeline']=str_replace("/","-",$tech_params['tech_timeline']);
						$tech_params['tech_timeline']=$tech_params['tech_timeline']." ".$tech_params['tech_time'];
						$update_quote['tech_timeline']=date("Y-m-d H:i:s",strtotime($tech_params['tech_timeline']));
						$update_quote['tech_challenge_comments']=$tech_params['tech_challenge_comments'];
						$update_quote['tech_challenge']='no';
						
						$log_params['challenge_time']=dateDiffHours(time(),strtotime($tech_params['tech_timeline']));
						$log_params['comments']=$update_quote['tech_challenge_comments'];
						$quiteActionId=3;

						$challenge_hours=round($log_params['challenge_time']);
						$update_quote['quote_delivery_hours'] = new Zend_Db_Expr('quote_delivery_hours+'.$challenge_hours);//Quote delivery time update

						//echo "<pre>";print_r($update_quote);exit;

						//send notifcation email to sales
						$quoteDetails=$quote_obj->getQuoteDetails($quote_id);

						$mail_obj=new Ep_Message_AutoEmails();
						$receiver_id=$quoteDetails[0]['quote_by'];
						$mail_parameters['sales_user']=$quoteDetails[0]['quote_by'];
						$mail_parameters['bo_user']=$this->adminLogin->userId;
						$mail_parameters['quote_title']=$quoteDetails[0]['title'];
						$mail_parameters['challenge_time']=$update_quote['tech_timeline'];						
						$mail_parameters['followup_link']='/quote/quote-followup?quote_id='.$quoteDetails[0]['identifier'];
						$mail_obj->sendQuotePersonalEmail($receiver_id,136,$mail_parameters);        	
					}
					else
					{
						$update_quote["prod_timeline"]=time()+($this->configval['prod_timeline']*60*60);
						$log_params['skip_date']	= date("Y-m-d H:i:s");
						$log_params['comments']=$tech_params['skip_comments'];

						$quiteActionId=2;
					}

					$log_obj=new Ep_Quote_QuotesLog();
					$log_obj->insertLog($quiteActionId,$log_params);
					//echo "<pre>";print_r($log_params);exit;	
					$quote_obj->updateQuote($update_quote,$quote_id);

					if($status=='skipped')
						$this->_redirect("/quote/sales-quotes-list?submenuId=ML13-SL2");
					else if($status=='challenged')
						$this->_redirect("/quote/tech-quote-review?quote_id=".$quote_id."&submenuId=ML13-SL2");	
				}
				elseif(isset($tech_params['review_save'])|| isset($tech_params['review_validate']))
				{					

					//echo "<pre>";print_r($tech_params);exit;

					if(count($tech_params['mission_title'])>0)
					{
						$j=0;
						foreach($tech_params['mission_title'] as $mission)
						{
							$tech_obj=new Ep_Quote_TechMissions();

							$tech_data['title']=$tech_params['mission_title'][$j];
							$tech_data['delivery_time']=$tech_params['delivery_time'][$j];
							$tech_data['delivery_option']=$tech_params['delivery_option'][$j];
							$tech_data['cost']=$tech_params['mission_cost'][$j];
							$tech_data['comments']=$tech_params['comments'][$j];
							$tech_data['currency']=$tech_params['currency'];
							$tech_data['before_prod']=$tech_params['before_prod_'.($j+1)]?'yes':'no';
							$tech_data['version']=$version;
							
							
							if(!$tech_params['tech_mission_id'][$j])
							{
								$tech_data['created_by']=$this->adminLogin->userId;
								$tech_obj->insertTechMission($tech_data);
								$missionIdentifier = $techmissions_assigned[]=$tech_obj->getIdentifier();
								//echo "<pre>";print_r($tech_data);	
							}
							if($tech_params['tech_mission_id'][$j])
							{
								$missionIdentifier=$tech_params['tech_mission_id'][$j];								
								$techmissions_assigned[]=$tech_params['tech_mission_id'][$j];
								$tech_data['updated_at']=date("Y-m-d H:i:s");
								$tech_obj->updateTechMission($tech_data,$missionIdentifier);

								$updated_tech_missions=TRUE;
							}
							
							//uploading mission document
							$update = false;
							$uploaded_documents = array();
							$uploaded_document_names = array();
							$k = 0;
							foreach($_FILES['tech_documents_'.($j+1)]['name'] as $row):

							if($_FILES['tech_documents_'.($j+1)]['name'][$k])
							{
								$missionDir=$this->mission_documents_path.$missionIdentifier."/";
								if(!is_dir($missionDir))
									mkdir($missionDir,TRUE);
									chmod($missionDir,0777);
												 
								$document_name=frenchCharsToEnglish($_FILES['tech_documents_'.($j+1)]['name'][$k]);
								$document_name=str_replace(' ','_',$document_name);
								$pathinfo = pathinfo($document_name);
								$document_name =$pathinfo['filename'].rand(100,1000).".".$pathinfo['extension'];
								$document_path=$missionDir.$document_name;
												 
								if(move_uploaded_file($_FILES['tech_documents_'.($j+1)]['tmp_name'][$k],$document_path))
								{
									chmod($document_path,0777);
								}
								//$seo_mission_data['documents_path']=$missionIdentifier."/".$document_name;
								$uploaded_documents[] = $missionIdentifier."/".$document_name;
								$uploaded_document_names[] = str_replace('|',"_",$tech_params['document_name'.($j+1)][$k]);
								$update = true;
							}
							$k++;
							endforeach;

							if($update)
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
							$j++;

						}
					}
					//updating tehcmissions assigned in quote table
					if(count($techmissions_assigned)>0)
					{
						$update_quote_tech['techmissions_assigned']=implode(",",$techmissions_assigned);	
					}
					

					if($status=='challenged')
					{
						$quote_obj->updateQuote($update_quote_tech,$quote_id);			
												
						$log_params['action']= 'tech_saved';
						if($tech_params['quote_updated_comments'])
							$log_params['comments']=$tech_params['quote_updated_comments'];

						$quiteActionId=4;	
						$log_obj=new Ep_Quote_QuotesLog();
						$log_obj->insertLog($quiteActionId,$log_params);


						//sending email to tech managers
						$techManager_holiday=$this->configval["tech_manager_holiday"];
						$user_type=$this->adminLogin->type;
						if($techManager_holiday=='no' && $user_type=='techuser')
						{
							if(!$updated_tech_missions)
							{
								$client_obj=new Ep_Quote_Client();
								$email_users=$get_head_prods=$client_obj->getEPContacts('"techmanager"');

								if(count($email_users)>0)
								{
									
									foreach($email_users as $user=>$name)
									{
										$mail_obj=new Ep_Message_AutoEmails();
										$receiver_id=$user;
										$mail_parameters['bo_user']=$user;
										$mail_parameters['sales_user']=$this->adminLogin->userId;						
										$mail_parameters['followup_link']='/quote/tech-quote-review?quote_id='.$quote_id.'&submenuId=ML13-SL2';

										$mail_obj->sendQuotePersonalEmail($receiver_id,152,$mail_parameters);        	
						        	}
						        }								
							}
							$this->_redirect("/quote/sales-quotes-list?submenuId=ML13-SL2");
						}
						else
							$this->_redirect("/quote/tech-quote-review?quote_id=".$quote_id."&submenuId=ML13-SL2");
					}
					elseif($status=='validated')
					{
						$quoteDetails=$quote_obj->getQuoteDetails($quote_id);
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

						if($tech_params['quote_updated_comments'])
							$log_params['comments']=$tech_params['quote_updated_comments'];

						
						$log_obj=new Ep_Quote_QuotesLog();
						$log_obj->insertLog($quiteActionId,$log_params);
						//exit;

						$quoteDetailsNew=$quote_obj->getQuoteDetails($quote_id);
						
						$update_quote_tech["prod_timeline"]=time()+($this->configval['prod_timeline']*60*60);
						if(isset($tech_params['review_validate']))
							$update_quote_tech['tec_review']=$status;

						if($quoteDetailsNew[0]['prod_review']=='auto_skipped')
							$update_quote_tech["sales_validation_expires"]=time()+($this->configval['sales_validation_timeline']*60*60);

						$quote_obj->updateQuote($update_quote_tech,$quote_id);


						//sending email to sales user(Quote is finalized )
						$mail_obj=new Ep_Message_AutoEmails();
						$receiver_id=$quoteDetails[0]['quote_by'];
						$mail_parameters['sales_user']=$quoteDetails[0]['quote_by'];
						$mail_parameters['bo_user']=$this->adminLogin->userId;
						$mail_parameters['bo_user_type']='tech';
						$mail_parameters['quote_title']=$quoteDetails[0]['title'];
						$mail_parameters['followup_link']='/quote/quote-followup?quote_id='.$quoteDetails[0]['identifier'];
						$mail_obj->sendQuotePersonalEmail($receiver_id,134,$mail_parameters);

						
						//send notifcation email to sales (Quote arrives to prod)						
							
							if(($quoteDetailsNew[0]['tec_review']=='skipped' || $quoteDetailsNew[0]['tec_review']=='auto_skipped' ||$quoteDetailsNew[0]['tec_review']=='validated') 
								&& ($quoteDetailsNew[0]['seo_review']=='skipped' || $quoteDetailsNew[0]['seo_review']=='auto_skipped' ||$quoteDetailsNew[0]['seo_review']=='validated') && $quoteDetailsNew[0]['prod_review']!='auto_skipped')
							{								
								$mail_obj=new Ep_Message_AutoEmails();
								$receiver_id=$quoteDetailsNew[0]['quote_by'];
								$mail_parameters['sales_user']=$quoteDetailsNew[0]['quote_by'];
								$mail_parameters['bo_user']=$this->adminLogin->userId;
								$mail_parameters['quote_title']=$quoteDetailsNew[0]['title'];
								$mail_parameters['challenge_time']=date("Y-m-d H:i:s",$update_quote_tech["prod_timeline"]);
								$mail_parameters['followup_link']='/quote/quote-followup?quote_id='.$quoteDetailsNew[0]['identifier'];
								$mail_obj->sendQuotePersonalEmail($receiver_id,137,$mail_parameters);
							}

							//sending intimation emails when quote edited
				            $update_comments= $tech_params['quote_updated_comments'];
				            if($update_comments)
							{
								$bo_user_type='tech';				
								$this->sendIntimationEmail($quote_id,$bo_user_type,$update_comments,$newmissionAdded);
								//exit;
							}	

						$this->_redirect("/quote/sales-quotes-list?submenuId=ML13-SL2");
					}
				}
			}
		}
	}

	//removing tech missions from quote assigned list
	public function	updateQuoteTechmissionAction()
	{
		$updateParams=$this->_request->getParams();
		$quote_id=$updateParams['quote_id'];
		$tech_mission_id=$updateParams['mission_identifier'];

		if($quote_id && $tech_mission_id)
		{
			$quote_obj=new Ep_Quote_Quotes();
			$quoteDetails=$quote_obj->getQuoteDetails($quote_id);
			if($quoteDetails)
			{
				$assigned_tech_missions=explode(",",$quoteDetails[0]['techmissions_assigned']);

				$key = array_search($tech_mission_id, $assigned_tech_missions);
				unset($assigned_tech_missions[$key]);

				$update_quote['techmissions_assigned']=implode(",",$assigned_tech_missions);
				$quote_obj->updateQuote($update_quote,$quote_id);

			}	
		}
	}

	//SEO team quote review
	public function seoQuoteReviewAction()
	{
		$seo_parameters=$this->_request->getParams();

		$quote_id=$seo_parameters['quote_id'];

		$quote_obj=new Ep_Quote_Quotes();

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
					
					if($quote['documents_path'])
					{
						$related_files='';
						/* $documents_path=explode("|",$quote['documents_path']);
						$documents_name=explode("|",$quote['documents_name']);

						foreach($documents_path as $k=>$file)
						{
							if(file_exists($this->quote_documents_path.$documents_path[$k]) && !is_dir($this->quote_documents_path.$documents_path[$k]))
							{
								if($documents_name[$k])
									$file_name=$documents_name[$k];
								else
									$file_name=basename($file);

								$related_files.='
								<a href="/quote/download-document?type=quote&index='.$k.'&quote_id='.$quote_id.'">'.$file_name.'</a><br>';
							}
						} */
						$files = array('documents_path'=>$quote['documents_path'],'documents_name'=>$quote['documents_name'],'quote_id'=>$quote_id,'delete'=>false);
						$related_files = $this->getQuoteFiles($files);
					}

					$quoteDetails[$q]['related_files']=$related_files;

					$quoteDetails[$q]['sales_suggested_price_format']=number_format($quote['sales_suggested_price'], 2, ',', ' ');
					$quoteDetails[$q]['comment_time']=time_ago($quote['created_at']);

					if($quote['seo_timeline'])
						$quoteDetails[$q]['seo_timeline_stamp']=strtotime($quote['seo_timeline']);

					if($quote['seo_timeline'])
					{
						$quoteDetails[$q]['seo_timeline_date']=date("d/m/Y",strtotime($quote['seo_timeline']));
						$quoteDetails[$q]['seo_timeline_time']=date("H:i",strtotime($quote['seo_timeline']));
					}	
					

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
					$searchParameters['misson_user_type']='sales';
					$quoteMission_obj=new Ep_Quote_QuoteMissions();
					$missonDetails=$quoteMission_obj->getMissionDetails($searchParameters);
					if($missonDetails)
					{
						$m=0;
						foreach($missonDetails as $mission)
						{
							$missonDetails[$m]['product_name']=$this->product_array[$mission['product']];			
							$missonDetails[$m]['language_source_name']=$this->getLanguageName($mission['language_source']);
							$missonDetails[$m]['product_type_name']=$this->producttype_array[$mission['product_type']];
							if($mission['language_dest'])
								$missonDetails[$m]['language_dest_name']=$this->getLanguageName($mission['language_dest']);

							$quoteDetails[$q]['missions_list'][$mission['identifier']]='Mission '.($m+1).' - '.$missonDetails[$m]['product_name'];

							$missonDetails[$m]['comment_time']=time_ago($mission['created_at']);

							//mission versionings if version is gt 1
							if($quote['version']>1)
							{
								$previousVersion=($quote['version']-1);

								$quoteMissionObj=new Ep_Quote_QuoteMissions();
								$previousMissionDetails=$quoteMissionObj->getMissionVersionDetails($mission['identifier'],$previousVersion,'sales');
								
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
									$allVersionMissionDetails=$quoteMissionObj->getMissionVersionDetails($mission['identifier'],NULL,'sales');
									if($allVersionMissionDetails)
									{
										$table_start='<table class="table quote-history table-striped">';
										$table_end='</table>';
										$language_versions=$product_type_versions=$volume_versions=$nb_words_versions='';
										$price_versions=$mission_length_versions='';

										foreach($allVersionMissionDetails as $versions)
										{
										 	if($versions['product']=='translation')
										  		$language= $this->getLanguageName($versions['language_source'])." > ".$this->getLanguageName($vmission['language_dest']);
										  	else
										  		$language= $this->getLanguageName($versions['language_source']);
										  	
										  	$created_at=date("d/m/Y", strtotime($versions['created_at']));

										  	$language_versions.="<tr><td>$language</td><td>$created_at</td></tr>";
										  	$product_type_versions.="<tr><td>".$this->producttype_array[$versions['product_type']]."</td><td>$created_at</td></tr>";
										  	$volume_versions.="<tr><td>".$versions['volume']."</td><td>$created_at</td></tr>";
										  	$nb_words_versions.="<tr><td>".$versions['nb_words']."</td><td>$created_at</td></tr>";
										  	$price_versions.="<tr><td>".zero_cut($versions['unit_price'],2)." &". $versions['sales_suggested_currency'].";</td><td>$created_at</td></tr>";

										  	$mission_length_option=$versions['mission_length_option']=='days' ? ' Jours' : ' Hours';

										  	$mission_length_versions.="<tr><td>".$versions['mission_length']." $mission_length_option</td><td>$created_at</td></tr>";
										}										
									}


									//checking the version differences
									if($mission['language_source'] !=$previousMissionDetails[0]['language_source'])
									{
										$missonDetails[$m]['language_difference']='yes';
										$missonDetails[$m]['language_versions']=$table_start.$language_versions.$table_end;
									}

									if($mission['language_dest'] !=$previousMissionDetails[0]['language_dest'])
									{
										$missonDetails[$m]['language_difference']='yes';
										$missonDetails[$m]['language_versions']=$table_start.$language_versions.$table_end;
									}

									if($mission['product_type'] !=$previousMissionDetails[0]['product_type'])
									{
										$missonDetails[$m]['product_type_difference']='yes';
										$missonDetails[$m]['product_type_versions']=$table_start.$product_type_versions.$table_end;
									
									}

									if($mission['volume'] !=$previousMissionDetails[0]['volume'])
									{
										$missonDetails[$m]['volume_difference']='yes';
										$missonDetails[$m]['volume_versions']=$table_start.$volume_versions.$table_end;
									}
									
									if($mission['nb_words'] !=$previousMissionDetails[0]['nb_words'])
									{
										$missonDetails[$m]['nb_words_difference']='yes';
										$missonDetails[$m]['nb_words_versions']=$table_start.$nb_words_versions.$table_end;
									}
									
									if($mission['unit_price'] !=$previousMissionDetails[0]['unit_price'])
									{
										$missonDetails[$m]['unit_price_difference']='yes';
										$missonDetails[$m]['price_versions']=$table_start.$price_versions.$table_end;
									}

									$current_mission_lenght=$mission['mission_length_option']=='hours' ? ($mission['mission_length']/24) : $mission['mission_length'];
									$previous_mission_lenght=$previousMissionDetails[0]['mission_length_option']=='hours' ? ($previousMissionDetails[0]['mission_length']/24) : $previousMissionDetails[0]['mission_length'];
									if($current_mission_lenght !=$previous_mission_lenght)
									{
										$missonDetails[$m]['mission_length_difference']='yes';	
										$missonDetails[$m]['mission_length_versions']=$table_start.$mission_length_versions.$table_end;
									}



									$missonDetails[$m]['previousMissionDetails']=$previousMissionDetails;
								}	

							}						

							$m++;
						}

						$quoteDetails[$q]['mission_details']=$missonDetails;


						//Deleted mission version details
						if($quote['version']>1)
						{
							$previousVersion=($quote['version']-1);
							$deletedMissionVersions=$this->deletedMissionVersions($quote['identifier'],$previousVersion,'sales');
							if($deletedMissionVersions)
								$quoteDetails[$q]['deletedMissionVersions']=$deletedMissionVersions;
						}
											

					}	

					$q++;
				}
			}
			$this->_view->quoteDetails=$quoteDetails;			

			//getting tech mission details
			$tech_obj=new Ep_Quote_TechMissions();
			$searchParameters['quote_id']=$quote_id;
			$techMissionDetails=$tech_obj->getTechMissionDetails($searchParameters);
			if($techMissionDetails)
			{
				$t=0;
				foreach($techMissionDetails as $mission)
				{
					$client_obj=new Ep_Quote_Client();
					$bo_user_details=$client_obj->getQuoteUserDetails($mission['created_by']);
					$techMissionDetails[$t]['tech_user_name']=$bo_user_details[0]['first_name'].' '.$bo_user_details[0]['last_name'];
					$techMissionDetails[$t]['comment_time']=time_ago($mission['created_at']);

					//mission versionings if version is gt 1
					if($quoteDetails[0]['version']>1)
					{
						$previousVersion=($quoteDetails[0]['version']-1);

						$techMissionObj=new Ep_Quote_TechMissions();
						$previousMissionDetails=$techMissionObj->getMissionVersionDetails($mission['identifier'],$quoteDetails[0]['identifier'],$previousVersion);
						
						if($previousMissionDetails)
						{						
							//Get All version details of a mission									
							$allVersionMissionDetails=$techMissionObj->getMissionVersionDetails($mission['identifier'],$quoteDetails[0]['identifier']);
							if($allVersionMissionDetails)
							{
								$table_start='<table class="table quote-history table-striped">';
								$table_end='</table>';								
								$price_versions=$mission_length_versions='';
								$title_versions='';

								foreach($allVersionMissionDetails as $versions)
								{
								 	
								  	
								  	$created_at=date("d/m/Y", strtotime($versions['created_at']));
								  	
								  	$title_versions.="<tr><td>".$versions['title']."</td><td>$created_at</td></tr>";

								  	$price_versions.="<tr><td>".zero_cut($versions['cost'],2)." &". $versions['currency'].";</td><td>$created_at</td></tr>";

								  	$mission_length_option=$versions['delivery_option']=='days' ? ' Jours' : ' Hours';

								  	$mission_length_versions.="<tr><td>".$versions['delivery_time']." $mission_length_option</td><td>$created_at</td></tr>";
								}										
							}


							//checking the version differences
							if($mission['title'] !=$previousMissionDetails[0]['title'])
							{
								$techMissionDetails[$t]['title_difference']='yes';
								$techMissionDetails[$t]['title_versions']=$table_start.$title_versions.$table_end;
							}
							

							if($mission['cost'] !=$previousMissionDetails[0]['cost'])
							{
								$techMissionDetails[$t]['cost_difference']='yes';
								$techMissionDetails[$t]['price_versions']=$table_start.$price_versions.$table_end;
							}

							$current_mission_lenght=$mission['delivery_option']=='hours' ? ($mission['delivery_time']/24) : $mission['delivery_time'];
							$previous_mission_lenght=$previousMissionDetails[0]['delivery_option']=='hours' ? ($previousMissionDetails[0]['delivery_time']/24) : $previousMissionDetails[0]['delivery_time'];
							if($current_mission_lenght !=$previous_mission_lenght)
							{
								$techMissionDetails[$t]['mission_length_difference']='yes';	
								$techMissionDetails[$t]['mission_length_versions']=$table_start.$mission_length_versions.$table_end;
							}



							$techMissionDetails[$t]['previousMissionDetails']=$previousMissionDetails;
						}	

					}
					
					$techMissionDetails[$t]['files'] = "";
					if($mission['documents_path'])
					{
						/* $exploded_file_paths = explode("|",$mission['documents_path']);
						$exploded_file_names = explode("|",$mission['documents_name']);
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
									$files .= '<div class="topset2"><a href="/quote/download-document?type=tech_mission&mission_id='.$mission['identifier'].'&index='.$k.'">'.$fname.'</a></div>';
									
							}
							$k++;
						} */
						$filesarray = array('documents_path'=>$mission['documents_path'],'documents_name'=>$mission['documents_name'],'id'=>$mission['identifier'],'delete'=>false);
						$files = $this->getTechFiles($filesarray);
						$techMissionDetails[$t]['files'] = $files;
					}

					$t++;
				}				
				
				$this->_view->techMissionDetails=$techMissionDetails;
			}

			//ALL language list
			$language_array=$this->_arrayDb->loadArrayv2("EP_LANGUAGES", $this->_lang);
			//echo "<pre>";print_r($language_array);exit;
        	natsort($language_array);
        	$this->_view->ep_language_list=$language_array;

			//getting seo mission details			
			$searchParameters['quote_id']=$quote_id;
			$searchParameters['misson_user_type']='seo';
			$quoteMission_obj=new Ep_Quote_QuoteMissions();
			$seoMissionDetails=$quoteMission_obj->getMissionDetails($searchParameters);
			if($seoMissionDetails)
			{
				$s=0;
				foreach($seoMissionDetails as $mission)
				{
					$seoMissionDetails[$s]['files'] = '';
					$seoMissionDetails[$s]['filenames'] = array();
					
					$seoMissionDetails[$s]['product_name']=$this->seo_product_array[$mission['product']];

					if($mission['documents_path'])
					{
						/* $exploded_file_paths = explode("|",$mission['documents_path']);
						$exploded_file_names = explode("|",$mission['documents_name']);
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
									$files .= '<div class="topset2"><a href="/quote/download-document?type=seo_mission&mission_id='.$mission['identifier'].'&index='.$k.'">'.$fname.'</a><span class="delete" rel="'.$k.'_'.$mission['identifier'].'"> <i class="splashy-error_x"></i></span></div>';
									
							}
							$k++;
						} */
						$filesarray = array('documents_path'=>$mission['documents_path'],'documents_name'=>$mission['documents_name'],'id'=>$mission['identifier'],'delete'=>true);
						$files = $this->getSeoFiles($filesarray);
						$seoMissionDetails[$s]['files'] = $files;
					}

					//mission versionings if version is gt 1
					if($quoteDetails[0]['version']>1)
					{
						$previousVersion=($quoteDetails[0]['version']-1);

						$quoteMissionObj=new Ep_Quote_QuoteMissions();
						$previousMissionDetails=$quoteMissionObj->getMissionVersionDetails($mission['identifier'],$previousVersion,'seo');
						
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
							$allVersionMissionDetails=$quoteMissionObj->getMissionVersionDetails($mission['identifier'],NULL,'seo');
							if($allVersionMissionDetails)
							{
								$table_start='<table class="table quote-history table-striped">';
								$table_end='</table>';
								$product_versions=$language_versions=$product_type_versions=$volume_versions=$nb_words_versions='';
								$price_versions=$mission_length_versions='';

								foreach($allVersionMissionDetails as $versions)
								{
								 	if($versions['product']=='translation')
								  		$language= $this->getLanguageName($versions['language_source'])." > ".$this->getLanguageName($vmission['language_dest']);
								  	else
								  		$language= $this->getLanguageName($versions['language_source']);
								  	
								  	$created_at=date("d/m/Y", strtotime($versions['created_at']));

								  	$language_versions.="<tr><td>$language</td><td>$created_at</td></tr>";
								  	$product_versions.="<tr><td>".$this->seo_product_array[$versions['product']]."</td><td>$created_at</td></tr>";
								  	$product_type_versions.="<tr><td>".$this->producttype_array[$versions['product_type']]."</td><td>$created_at</td></tr>";
								  	$volume_versions.="<tr><td>".$versions['volume']."</td><td>$created_at</td></tr>";
								  	$nb_words_versions.="<tr><td>".$versions['nb_words']."</td><td>$created_at</td></tr>";
								  	$price_versions.="<tr><td>".zero_cut($versions['cost'],2)." &". $versions['sales_suggested_currency'].";</td><td>$created_at</td></tr>";

								  	$mission_length_option=$versions['mission_length_option']=='days' ? ' Jours' : ' Hours';

								  	$mission_length_versions.="<tr><td>".$versions['mission_length']." $mission_length_option</td><td>$created_at</td></tr>";
								}										
							}


							//checking the version differences
							if($mission['language_source'] !=$previousMissionDetails[0]['language_source'])
							{
								$seoMissionDetails[$s]['language_difference']='yes';
								$seoMissionDetails[$s]['language_versions']=$table_start.$language_versions.$table_end;
							}

							if($mission['language_dest'] !=$previousMissionDetails[0]['language_dest'])
							{
								$seoMissionDetails[$s]['language_difference']='yes';
								$seoMissionDetails[$s]['language_versions']=$table_start.$language_versions.$table_end;
							}
							if($mission['product'] !=$previousMissionDetails[0]['product'])
							{
								$seoMissionDetails[$s]['product_difference']='yes';
								$seoMissionDetails[$s]['product_versions']=$table_start.$product_versions.$table_end;
							
							}

							if($mission['product_type'] !=$previousMissionDetails[0]['product_type'])
							{
								$seoMissionDetails[$s]['product_type_difference']='yes';
								$seoMissionDetails[$s]['product_type_versions']=$table_start.$product_type_versions.$table_end;
							
							}

							if($mission['volume'] !=$previousMissionDetails[0]['volume'])
							{
								$seoMissionDetails[$s]['volume_difference']='yes';
								$seoMissionDetails[$s]['volume_versions']=$table_start.$volume_versions.$table_end;
							}
							
							if($mission['nb_words'] !=$previousMissionDetails[0]['nb_words'])
							{
								$seoMissionDetails[$s]['nb_words_difference']='yes';
								$seoMissionDetails[$s]['nb_words_versions']=$table_start.$nb_words_versions.$table_end;
							}
							
							if($mission['unit_price'] !=$previousMissionDetails[0]['unit_price'])
							{
								$seoMissionDetails[$s]['unit_price_difference']='yes';
								$seoMissionDetails[$s]['price_versions']=$table_start.$price_versions.$table_end;
							}

							$current_mission_lenght=$mission['mission_length_option']=='hours' ? ($mission['mission_length']/24) : $mission['mission_length'];
							$previous_mission_lenght=$previousMissionDetails[0]['mission_length_option']=='hours' ? ($previousMissionDetails[0]['mission_length']/24) : $previousMissionDetails[0]['mission_length'];
							if($current_mission_lenght !=$previous_mission_lenght)
							{
								$seoMissionDetails[$s]['mission_length_difference']='yes';	
								$seoMissionDetails[$s]['mission_length_versions']=$table_start.$mission_length_versions.$table_end;
							}



							$seoMissionDetails[$s]['previousMissionDetails']=$previousMissionDetails;
						}	

					}


					$s++;

				}	
				$this->_view->seoMissionDetails=$seoMissionDetails;
			}
			
		}

		//check manager is on holiday or not
		$seoManager_holiday=$this->configval["seo_manager_holiday"];
		$user_type=$this->adminLogin->type;
		if($seoManager_holiday=='no' && $user_type=='seouser')
			$this->_view->show_validate='no';
		else
			$this->_view->show_validate='yes';

		//echo "<pre>";print_r($quoteDetails);exit;

		$this->render('seo-quote-review');
	}

	//save seo reviews based on actions
	public function saveSeoReviewAction()
	{
		if($this->_request-> isPost()  && $this->adminLogin->userId)
		{
			$seo_params=$this->_request->getParams();
			
			//echo "<pre>";print_r($_FILES);print_r($seo_params);exit;

			$quote_id=$seo_params['quote_id'];

			if(isset($seo_params['review_skip'])) $status='skipped';
			else if(isset($seo_params['review_challenge'])) $status='challenged';
			else if(isset($seo_params['review_save'])) $status='challenged';
			else if(isset($seo_params['review_validate'])) $status='validated';

			if($quote_id)
			{	
					//get Quote version
					$quote_obj=new Ep_Quote_Quotes();
					$version=$quote_obj->getQuoteVersion($quote_id);

					//Insert Quote log
					$log_params['quote_id']	= $quote_id;
					$log_params['bo_user']	= $this->adminLogin->userId;					
					$log_params['version']	= $version;
					$log_params['action']	= 'seo_'.$status;
					


				if(isset($seo_params['review_skip'])|| isset($seo_params['review_challenge']))
				{
					

					$quote_obj=new Ep_Quote_Quotes();
					$update_quote['seo_review']=$status;

					if(isset($seo_params['review_challenge']))
					{
						$seo_params['seo_timeline']=str_replace("/","-",$seo_params['seo_timeline']);
						$seo_params['seo_timeline']=$seo_params['seo_timeline']." ".$seo_params['seo_time'];
						$update_quote['seo_timeline']=date("Y-m-d H:i:s",strtotime($seo_params['seo_timeline']));
						$update_quote['seo_comments']=$seo_params['seo_comments'];
						$update_quote['seo_challenge']='no';

						$log_params['challenge_time']=dateDiffHours(time(),strtotime($seo_params['seo_timeline']));
						$log_params['comments']=$update_quote['seo_comments'];
						$quiteActionId=3;

						$challenge_hours=round($log_params['challenge_time']);
						$update_quote['quote_delivery_hours'] = new Zend_Db_Expr('quote_delivery_hours+'.$challenge_hours);//Quote delivery time update

						//send notifcation email to sales
						$quoteDetails=$quote_obj->getQuoteDetails($quote_id);

						$mail_obj=new Ep_Message_AutoEmails();
						$receiver_id=$quoteDetails[0]['quote_by'];
						$mail_parameters['sales_user']=$quoteDetails[0]['quote_by'];
						$mail_parameters['bo_user']=$this->adminLogin->userId;
						$mail_parameters['quote_title']=$quoteDetails[0]['title'];
						$mail_parameters['challenge_time']=$update_quote['seo_timeline'];						
						$mail_parameters['followup_link']='/quote/quote-followup?quote_id='.$quoteDetails[0]['identifier'];
						$mail_obj->sendQuotePersonalEmail($receiver_id,136,$mail_parameters);
					}
					else
					{
						$update_quote["prod_timeline"]=time()+($this->configval['prod_timeline']*60*60);
						$log_params['skip_date']	= date("Y-m-d H:i:s");
						$log_params['comments']=$seo_params['skip_comments'];
						$quiteActionId=2;
					}

					$log_obj=new Ep_Quote_QuotesLog();
					$log_obj->insertLog($quiteActionId,$log_params);
					//echo "<pre>";print_r($log_params);exit;


					//echo "<pre>";print_r($update_quote);exit;
					$quote_obj->updateQuote($update_quote,$quote_id);

					if($status=='skipped')
						$this->_redirect("/quote/sales-quotes-list?submenuId=ML13-SL2");
					else if($status=='challenged')
						$this->_redirect("/quote/seo-quote-review?quote_id=".$quote_id."&submenuId=ML13-SL2");	
				}
				elseif(isset($seo_params['review_save'])|| isset($seo_params['review_validate']))
				{
					//echo "<pre>";print_r($seo_params);exit;

					if(count($seo_params['product'])>0 && count($seo_params['language']))
					{
						$j=0;
						foreach($seo_params['product'] as $mission)
						{
							$quoteMission_obj=new Ep_Quote_QuoteMissions();
							$seo_mission_data['quote_id']=$quote_id;
							$seo_mission_data['product']=$seo_params['product'][$j];
							$seo_mission_data['product_type']=$seo_params['producttype'][$j];
							$seo_mission_data['language_source']=$seo_params['language'][$j];
							if($seo_params['product'][$j]=='translation')
								$seo_mission_data['language_dest']=$seo_params['languagedest'][$j];
							if($seo_params['nb_words'][$j])
								$seo_mission_data['nb_words']=$seo_params['nb_words'][$j];
							$seo_mission_data['comments']=$seo_params['scomments'][$j];

							$seo_mission_data['version']	= $version;

							if($seo_params['product'][$j]=='seo_audit' || $seo_params['product'][$j]=='smo_audit')
							{
								$seo_mission_data['mission_length']=$seo_params['sdelivery_time'][$j];
								$seo_mission_data['mission_length_option']=$seo_params['sdelivery_option'][$j];
								$seo_mission_data['cost']=$seo_params['smission_cost'][$j];
								$seo_mission_data['internal_cost']=$seo_params['smission_cost'][$j];
								
								$seo_mission_data['unit_price']=$seo_params['smission_cost'][$j];
							}
							else
							{
								$seo_mission_data['mission_length']=0;
								$seo_mission_data['mission_length_option']=$seo_params['sdelivery_option'][$j];
								$seo_mission_data['cost']=0;
								$seo_mission_data['unit_price']=0;
							}							

							$seo_mission_data['related_to']=$seo_params['related_mission'][$j];
							
							$seo_mission_data['misson_user_type']='seo';               
							if($seo_mission_data['product']=='seo_audit' || $seo_mission_data['product']=='smo_audit' )
							{
								$seo_mission_data['volume']=1;
								$seo_mission_data['related_to']=NULL;
							}
							else
							{	
								//updating seo mission details with related mission details
								$qmission_obj=new Ep_Quote_QuoteMissions();
								$archParameters['mission_id']=$seo_mission_data['related_to'];
								$suggested_mission_details=$qmission_obj->getMissionDetails($archParameters);
								if($suggested_mission_details)
								{
									$seo_mission_data['volume']=$suggested_mission_details[0]['volume'];
									$seo_mission_data['mission_length']=$suggested_mission_details[0]['volume'];
									$seo_mission_data['mission_length_option']=$suggested_mission_details[0]['mission_length_option'];
									$seo_mission_data['unit_price']=$suggested_mission_details[0]['unit_price'];
									//$seo_mission_data['margin_percentage']=$suggested_mission_details[0]['margin_percentage'];
									$seo_mission_data['sales_suggested_missions']=$suggested_mission_details[0]['sales_suggested_missions'];
									
								}
								else									
									$seo_mission_data['volume']=0;
							}

							$seo_mission_data['before_prod']=$seo_params['before_prod_'.($j+1)]?'yes':'no';
							
							$seo_mission_data['margin_percentage']=60;//default for seo
							
							if(!$seo_params['seo_mission_id'][$j])
							{
								$seo_mission_data['created_by']=$this->adminLogin->userId;
								$quoteMission_obj->insertQuoteMission($seo_mission_data);
								$missionIdentifier=$quoteMission_obj->getIdentifier();
							}
							if($seo_params['seo_mission_id'][$j])
							{
								$missionIdentifier=$seo_params['seo_mission_id'][$j];
								
								$seo_mission_data['updated_at']=date("Y-m-d H:i:s");

								$quoteMission_obj->updateQuoteMission($seo_mission_data,$missionIdentifier);
								//echo "<pre>";print_r($seo_mission_data);
								$updated_seo_missions=TRUE;//used to send email to manager
							}

							unset($seo_mission_data);

							//uploading mission document
							$update = false;
							$uploaded_documents = array();
							$uploaded_document_names = array();
							$k = 0;
							foreach($_FILES['seo_documents_'.($j+1)]['name'] as $row):

							if($_FILES['seo_documents_'.($j+1)]['name'][$k])
							{
								$missionDir=$this->mission_documents_path.$missionIdentifier."/";
								if(!is_dir($missionDir))
									mkdir($missionDir,TRUE);
									chmod($missionDir,0777);
												 
								$document_name=frenchCharsToEnglish($_FILES['seo_documents_'.($j+1)]['name'][$k]);
								$document_name=str_replace(' ','_',$document_name);
								$pathinfo = pathinfo($document_name);
								$document_name =$pathinfo['filename'].rand(100,1000).".".$pathinfo['extension'];
								$document_path=$missionDir.$document_name;
												 
								if(move_uploaded_file($_FILES['seo_documents_'.($j+1)]['tmp_name'][$k],$document_path))
								{
									chmod($document_path,0777);
								}
								//$seo_mission_data['documents_path']=$missionIdentifier."/".$document_name;
								$uploaded_documents[] = $missionIdentifier."/".$document_name;
								$uploaded_document_names[] = str_replace('|',"_",$seo_params['document_name'.($j+1)][$k]);
								$update = true;
							}
							$k++;
							endforeach;

							if($update)
							{
								$result =$quoteMission_obj->getQuoteMission($missionIdentifier);
								$uploaded_documents1 = explode("|",$result[0]['documents_path']);
								$uploaded_documents =array_merge($uploaded_documents,$uploaded_documents1);
								$seo_mission_data['documents_path'] = implode("|",$uploaded_documents);
								$document_names =explode("|",$result[0]['documents_name']);
								$document_names =array_merge($uploaded_document_names,$document_names);
								$seo_mission_data['documents_name'] = implode("|",$document_names);
							
								$quoteMission_obj->updateQuoteMission($seo_mission_data,$missionIdentifier);
								unset($seo_mission_data);
							}
							$j++;
							unset($seo_mission_data);
						}						
					}//exit;						

						if($status=='challenged')
						{
							$log_params['action']= 'seo_saved';
							$quiteActionId=4;	

							if($seo_params['quote_updated_comments'])
								$log_params['comments']=$seo_params['quote_updated_comments'];

							$log_obj=new Ep_Quote_QuotesLog();
							$log_obj->insertLog($quiteActionId,$log_params);
							//echo "<pre>";print_r($log_params);exit;

							//sending email to tech managers
							$seoManager_holiday=$this->configval["seo_manager_holiday"];
							$user_type=$this->adminLogin->type;
							if($seoManager_holiday=='no' && $user_type=='seouser')
							{
								if(!$updated_seo_missions)
								{
									$client_obj=new Ep_Quote_Client();
									$email_users=$get_head_prods=$client_obj->getEPContacts('"seomanager"');

									if(count($email_users)>0)
									{
										
										foreach($email_users as $user=>$name)
										{
											$mail_obj=new Ep_Message_AutoEmails();
											$receiver_id=$user;
											$mail_parameters['bo_user']=$user;
											$mail_parameters['sales_user']=$this->adminLogin->userId;						
											$mail_parameters['followup_link']='/quote/seo-quote-review?quote_id='.$quote_id.'&submenuId=ML13-SL2';

											$mail_obj->sendQuotePersonalEmail($receiver_id,152,$mail_parameters);        	
							        	}
							        }								
								}
								$this->_redirect("/quote/sales-quotes-list?submenuId=ML13-SL2");
							}
							else
								$this->_redirect("/quote/seo-quote-review?quote_id=".$quote_id."&submenuId=ML13-SL2");	
						}
						elseif($status=='validated')
						{
							
							$quote_obj=new Ep_Quote_Quotes();

							$quoteDetails=$quote_obj->getQuoteDetails($quote_id);
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

							if($seo_params['quote_updated_comments'])
								$log_params['comments']=$seo_params['quote_updated_comments'];


							
							$log_obj=new Ep_Quote_QuotesLog();
							$log_obj->insertLog($quiteActionId,$log_params);
							
							
							$quoteDetailsNew=$quote_obj->getQuoteDetails($quote_id);

							//update Quotes table
							$update_quote_seo["prod_timeline"]=time()+($this->configval['prod_timeline']*60*60);
							if(isset($seo_params['review_validate']))
								$update_quote_seo['seo_review']=$status;
							
							if($quoteDetailsNew[0]['prod_review']=='auto_skipped')
								$update_quote_seo["sales_validation_expires"]=time()+($this->configval['sales_validation_timeline']*60*60);
							
							$quote_obj->updateQuote($update_quote_seo,$quote_id);



							//sending email to sales user (Quote is finalized)
							$mail_obj=new Ep_Message_AutoEmails();

							$receiver_id=$quoteDetails[0]['quote_by'];
							$mail_parameters['sales_user']=$quoteDetails[0]['quote_by'];
							$mail_parameters['bo_user']=$this->adminLogin->userId;
							$mail_parameters['bo_user_type']='seo';
							$mail_parameters['quote_title']=$quoteDetails[0]['title'];
							$mail_parameters['followup_link']='/quote/quote-followup?quote_id='.$quoteDetails[0]['identifier'];
							$mail_obj->sendQuotePersonalEmail($receiver_id,134,$mail_parameters);


							//send notifcation email to sales (Quote arrives to prod)						
							if(($quoteDetailsNew[0]['tec_review']=='skipped' || $quoteDetailsNew[0]['tec_review']=='auto_skipped' ||$quoteDetailsNew[0]['tec_review']=='validated') 
								&& ($quoteDetailsNew[0]['seo_review']=='skipped' || $quoteDetailsNew[0]['seo_review']=='auto_skipped' ||$quoteDetailsNew[0]['seo_review']=='validated') && $quoteDetailsNew[0]['prod_review']!='auto_skipped')
							{
								$mail_obj=new Ep_Message_AutoEmails();
								$receiver_id=$quoteDetailsNew[0]['quote_by'];
								$mail_parameters['sales_user']=$quoteDetailsNew[0]['quote_by'];
								$mail_parameters['bo_user']=$this->adminLogin->userId;
								$mail_parameters['quote_title']=$quoteDetailsNew[0]['title'];
								$mail_parameters['challenge_time']=date("y-m-d H:i:s",$update_quote_seo["prod_timeline"]);						
								$mail_parameters['followup_link']='/quote/quote-followup?quote_id='.$quoteDetailsNew[0]['identifier'];
								$mail_obj->sendQuotePersonalEmail($receiver_id,137,$mail_parameters);
							}


							//sending intimation emails when quote edited
				            $update_comments= $seo_params['quote_updated_comments'];
				            if($update_comments)
							{
								$bo_user_type='seo';				
								$this->sendIntimationEmail($quote_id,$bo_user_type,$update_comments,$newmissionAdded);
								//exit;
							}	



							$this->_redirect("/quote/sales-quotes-list?submenuId=ML13-SL2");
						}
				}		
			}
		}
	}	

	//delete quote mission seo/sales/Tech
	public function deleteQuoteMissionAction()
	{
		//if($this->_request-> isPost())
		//{
			$mission_params=$this->_request->getParams();
			$quote_obj=new Ep_Quote_QuoteMissions();

			
			if($mission_params['mission_identifier'] && $mission_params['mission_type']=='tech' && $mission_params['type']=='includes_update' )
			{
				$update_mission['include_final']='no';
				$identifier=$mission_params['mission_identifier'];

				$tech_obj=new Ep_Quote_TechMissions();
				$tech_obj->updateTechMission($update_mission,$identifier);
			}

			else if($mission_params['mission_identifier'] && $mission_params['type']=='includes_update' )
			{
				$update_mission['include_final']='no';
				$identifier=$mission_params['mission_identifier'];

				$quote_obj->updateQuoteMission($update_mission,$identifier);
			}

			else if($mission_params['mission_identifier'])
			{
				$identifier=$mission_params['mission_identifier'];
				if($this->quote_creation->custom['action']=='edit' && $this->quote_creation->custom['create_new_version']=='yes')
				{
					//echo $this->quote_creation->custom['version'];
					//Insert this mission in to QuoteMissionsversions table							
					if($identifier)
					{
						$quoteMission_obj=new Ep_Quote_QuoteMissions();

						$quoteMission_obj->insertMissionVersion($identifier);

						//versioning Prod Missions
						$prodObj=new Ep_Quote_ProdMissions();
						$prodParams['quote_mission_id']=$identifier;
						$prodMissionDetails=$prodObj->getProdMissionDetails($prodParams);
						if($prodMissionDetails)
						{
							foreach($prodMissionDetails as $prodMission)
							{
								$prodMissionId=$prodMission['identifier'];
								$prodObj->insertMissionVersion($prodMissionId);

								//deleting prod mission from Prodmissions after insert into prod versioning
								$prodObj->deleteProdMission($prodMissionId);
							}
						}
					}
				}			



				if($mission_params['mission_index'])//Added w.r.t Quote edit
				{
					$index=$mission_params['mission_index']-1;
					unset($this->quote_creation->create_mission['product'][$index]);
					unset($this->quote_creation->create_mission['language'][$index]);
					unset($this->quote_creation->create_mission['languagedest'][$index]);
					unset($this->quote_creation->create_mission['producttype'][$index]);
					unset($this->quote_creation->create_mission['nb_words'][$index]);
					unset($this->quote_creation->create_mission['volume'][$index]);
					unset($this->quote_creation->create_mission['comments'][$index]);
					unset($this->quote_creation->create_mission['identifier'][$index]);
					unset($this->quote_creation->create_mission['mission_identifier'][$index]);
					unset($this->quote_creation->select_missions['missions_selected'][$index]);

					$this->quote_creation->create_mission['product']=array_values($this->quote_creation->create_mission['product']);
					$this->quote_creation->create_mission['language']=array_values($this->quote_creation->create_mission['language']);
					$this->quote_creation->create_mission['languagedest']=array_values($this->quote_creation->create_mission['languagedest']);
					$this->quote_creation->create_mission['producttype']=array_values($this->quote_creation->create_mission['producttype']);
					$this->quote_creation->create_mission['nb_words']=array_values($this->quote_creation->create_mission['nb_words']);
					$this->quote_creation->create_mission['volume']=array_values($this->quote_creation->create_mission['volume']);
					$this->quote_creation->create_mission['comments']=array_values($this->quote_creation->create_mission['comments']);
					$this->quote_creation->create_mission['identifier']=array_values($this->quote_creation->create_mission['identifier']);
					$this->quote_creation->create_mission['mission_identifier']=array_values($this->quote_creation->create_mission['mission_identifier']);
					$this->quote_creation->create_mission['missions_selected']=array_values($this->quote_creation->create_mission['missions_selected']);

					
				}
				
				$quote_obj->deleteQuoteMission($identifier);
				
			}    
		//}	
		 
	}	
	//Prod details view in final validation and followup
	public function prodViewDetails($quote_id)
	{
		$quote_obj=new Ep_Quote_Quotes();

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
					
					if($quote['documents_path'])
					{
						$related_files='';
						$documents_path=explode("|",$quote['documents_path']);
						$documents_name=explode("|",$quote['documents_name']);

						
						foreach($documents_path as $k=>$file)
						{
							if(file_exists($this->quote_documents_path.$documents_path[$k]) && !is_dir($this->quote_documents_path.$documents_path[$k]))
							{
							if($documents_name[$k])
								$file_name=$documents_name[$k];
							else
								$file_name=basename($file);

							$related_files.='
							<a href="/quote/download-document?type=quote&index='.$k.'&quote_id='.$quote_id.'">'.$file_name.'</a><br>';
							}
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
					$searchParameters['misson_user_type']='sales';
					$quoteMission_obj=new Ep_Quote_QuoteMissions();
					$missonDetails=$quoteMission_obj->getMissionDetails($searchParameters);
					if($missonDetails)
					{
						$m=0;
						foreach($missonDetails as $mission)
						{
							$missonDetails[$m]['product_name']=$this->product_array[$mission['product']];			
							$missonDetails[$m]['language_source_name']=$this->getLanguageName($mission['language_source']);
							$missonDetails[$m]['product_type_name']=$this->producttype_array[$mission['product_type']];
							if($mission['language_dest'])
								$missonDetails[$m]['language_dest_name']=$this->getLanguageName($mission['language_dest']);

							$quoteDetails[$q]['missions_list'][$mission['identifier']]='Mission '.($m+1).' - '.$missonDetails[$m]['product_name'];

							$missonDetails[$m]['comment_time']=time_ago($mission['created_at']);
							//mission versionings if version is gt 1
							if($quote['version']>1)
							{
								$previousVersion=($quote['version']-1);

								$quoteMissionObj=new Ep_Quote_QuoteMissions();
								$previousMissionDetails=$quoteMissionObj->getMissionVersionDetails($mission['identifier'],$previousVersion,'sales');
								
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
									$allVersionMissionDetails=$quoteMissionObj->getMissionVersionDetails($mission['identifier'],NULL,'sales');
									if($allVersionMissionDetails)
									{
										$table_start='<table class="table quote-history table-striped">';
										$table_end='</table>';
										$language_versions=$product_type_versions=$volume_versions=$nb_words_versions='';
										$price_versions=$mission_length_versions='';

										foreach($allVersionMissionDetails as $versions)
										{
										 	if($versions['product']=='translation')
										  		$language= $this->getLanguageName($versions['language_source'])." > ".$this->getLanguageName($vmission['language_dest']);
										  	else
										  		$language= $this->getLanguageName($versions['language_source']);
										  	
										  	$created_at=date("d/m/Y", strtotime($versions['created_at']));

										  	$language_versions.="<tr><td>$language</td><td>$created_at</td></tr>";
										  	$product_type_versions.="<tr><td>".$this->producttype_array[$versions['product_type']]."</td><td>$created_at</td></tr>";
										  	$volume_versions.="<tr><td>".$versions['volume']."</td><td>$created_at</td></tr>";
										  	$nb_words_versions.="<tr><td>".$versions['nb_words']."</td><td>$created_at</td></tr>";
										  	$price_versions.="<tr><td>".zero_cut($versions['unit_price'],2)." &". $versions['sales_suggested_currency'].";</td><td>$created_at</td></tr>";

										  	$mission_length_option=$versions['mission_length_option']=='days' ? ' Jours' : ' Hours';

										  	$mission_length_versions.="<tr><td>".$versions['mission_length']." $mission_length_option</td><td>$created_at</td></tr>";
										}										
									}


									//checking the version differences
									if($mission['language_source'] !=$previousMissionDetails[0]['language_source'])
									{
										$missonDetails[$m]['language_difference']='yes';
										$missonDetails[$m]['language_versions']=$table_start.$language_versions.$table_end;
									}

									if($mission['language_dest'] !=$previousMissionDetails[0]['language_dest'])
									{
										$missonDetails[$m]['language_difference']='yes';
										$missonDetails[$m]['language_versions']=$table_start.$language_versions.$table_end;
									}

									if($mission['product_type'] !=$previousMissionDetails[0]['product_type'])
									{
										$missonDetails[$m]['product_type_difference']='yes';
										$missonDetails[$m]['product_type_versions']=$table_start.$product_type_versions.$table_end;
									
									}

									if($mission['volume'] !=$previousMissionDetails[0]['volume'])
									{
										$missonDetails[$m]['volume_difference']='yes';
										$missonDetails[$m]['volume_versions']=$table_start.$volume_versions.$table_end;
									}
									
									if($mission['nb_words'] !=$previousMissionDetails[0]['nb_words'])
									{
										$missonDetails[$m]['nb_words_difference']='yes';
										$missonDetails[$m]['nb_words_versions']=$table_start.$nb_words_versions.$table_end;
									}
									
									if($mission['unit_price'] !=$previousMissionDetails[0]['unit_price'])
									{
										$missonDetails[$m]['unit_price_difference']='yes';
										$missonDetails[$m]['price_versions']=$table_start.$price_versions.$table_end;
									}

									$current_mission_lenght=$mission['mission_length_option']=='hours' ? ($mission['mission_length']/24) : $mission['mission_length'];
									$previous_mission_lenght=$previousMissionDetails[0]['mission_length_option']=='hours' ? ($previousMissionDetails[0]['mission_length']/24) : $previousMissionDetails[0]['mission_length'];
									if($current_mission_lenght !=$previous_mission_lenght)
									{
										$missonDetails[$m]['mission_length_difference']='yes';	
										$missonDetails[$m]['mission_length_versions']=$table_start.$mission_length_versions.$table_end;
									}



									$missonDetails[$m]['previousMissionDetails']=$previousMissionDetails;
								}	

							}


							//Get seo missions related to a mission
							$searchParameters['quote_id']=$quote_id;
							$searchParameters['misson_user_type']='seo';
							$searchParameters['related_to']=$mission['identifier'];
							$searchParameters['product']=$mission['product'];
							//echo "<pre>";print_r($searchParameters);
							$quoteMission_obj=new Ep_Quote_QuoteMissions();
							$seoMissonDetails=$quoteMission_obj->getMissionDetails($searchParameters);
							//echo "<pre>";print_r($seoMissonDetails);exit;
							if($seoMissonDetails)
							{
								$s=0;
								foreach($seoMissonDetails as $smission)
								{									
									$client_obj=new Ep_Quote_Client();
									$bo_user_details=$client_obj->getQuoteUserDetails($smission['created_by']);
									$seoMissonDetails[$s]['seo_user_name']=$bo_user_details[0]['first_name'].' '.$bo_user_details[0]['last_name'];

									$seoMissonDetails[$s]['comment_time']=time_ago($smission['created_at']);

									$seoMissonDetails[$s]['product_type_name']=$this->producttype_array[$smission['product_type']];

									$prodMissionObj=new Ep_Quote_ProdMissions();

									$searchParameters['quote_mission_id']=$smission['identifier'];
									$prodMissionDetails=$prodMissionObj->getProdMissionDetails($searchParameters);
									//echo "<pre>";print_r($prodMissionDetails);exit;

									if($prodMissionDetails)
									{
										$seoMissonDetails[$s]['prod_mission_details']=$prodMissionDetails;	
									}
									else
									{

										//getting suggested mission Details for seo missions
										if($smission['sales_suggested_missions'])
										{
											$archmission_obj=new Ep_Quote_Mission();
											$archParameters['mission_id']=$smission['sales_suggested_missions'];
											$suggested_mission_details=$archmission_obj->getMissionDetails($archParameters);										
											if($suggested_mission_details)
											{
												foreach($suggested_mission_details as $key=>$suggested_mission)
												{
													$sug_mission_length=$smission['volume']*($smission['nb_words']/$suggested_mission['article_length']);
													$prod_mission_length=round($suggested_mission['mission_length']*($sug_mission_length/$suggested_mission['num_of_articles']));
													$suggested_mission_details[$key]['mission_length']=$prod_mission_length;

													$suggested_mission_details[$key]['mission_length']=round(($quoteDetails[0]['sales_delivery_time']*90)/100);
													$staff_setup_length=ceil(($quoteDetails[0]['sales_delivery_time']*10)/100);
													$suggested_mission_details[$key]['staff_setup_length']=$staff_setup_length < 10 ? $staff_setup_length :10;

													//pre-fill staff calculations

													//total mission words
													$mission_volume=$smission['volume'];
													$mission_nb_words=$smission['nb_words'];
													$total_mission_words=($mission_volume*$mission_nb_words);
											
													//words that can write per writer with in delivery weeks
													$sales_delivery_time=$quote['sales_delivery_time_option']=='hours' ? ($quote['sales_delivery_time']/24) : $quote['sales_delivery_time'];
													$sales_delivery_week=ceil($sales_delivery_time/7);

													$mission_product=$smission['product_type'];
													$articles_perweek=$this->configval['max_writer_'.$mission_product];
													$words_perweek_peruser=$articles_perweek*250;
													$words_peruser_perdelivery=$sales_delivery_week*$words_perweek_peruser;

													//wrting and proofreading staff calculations
													$writing_staff=round($total_mission_words/$words_peruser_perdelivery);
													if(!$writing_staff || $writing_staff <1)
														$writing_staff=1;													

													$suggested_mission_details[$key]['writing_staff']=$writing_staff;
													
												}
												
												$seoMissonDetails[$s]['suggested_mission_details']=$suggested_mission_details;	
											}
											//echo "<pre>";print_r($seoMissonDetails);exit;
										}	
									}	

									$s++;	
								}

								$missonDetails[$m]['seoMissions']=$seoMissonDetails;
							}
							//echo "<pre>";print_r($missonDetails);exit;

							$prodMissionObj=new Ep_Quote_ProdMissions();

							$searchParameters['quote_mission_id']=$mission['identifier'];
							$prodMissionDetails=$prodMissionObj->getProdMissionDetails($searchParameters);
							//echo "<pre>";print_r($prodMissionDetails);exit;

							if($prodMissionDetails)
							{
								$p=0;
								foreach($prodMissionDetails as $mission)
								{
									//mission versionings if version is gt 1
									if($quote['version']>1)
									{
										$previousVersion=($quote['version']-1);

										$prodMissionObj=new Ep_Quote_ProdMissions();
										$previousMissionDetails=$prodMissionObj->getMissionVersionDetails($mission['identifier'],$previousVersion);
										
										if($previousMissionDetails)
										{						
											//Get All version details of a mission									
											$allVersionMissionDetails=$prodMissionObj->getMissionVersionDetails($mission['identifier']);

											if($allVersionMissionDetails)
											{
												$table_start='<table class="table quote-history table-striped">';
												$table_end='</table>';								
												$price_versions=$mission_length_versions='';
												$staff_versions=$staff_length_versions='';

												foreach($allVersionMissionDetails as $versions)
												{
												 	
												  	
												  	$created_at=date("d/m/Y", strtotime($versions['created_at']));												  	
												  	
												  	$staff_versions.="<tr><td>".$versions['staff']."</td><td>$created_at</td></tr>";

												  	$price_versions.="<tr><td>".zero_cut($versions['cost'],2)." &". $versions['currency'].";</td><td>$created_at</td></tr>";

												  	$staff_length_option=$versions['staff_time_option']=='days' ? ' Jours' : ' Hours';

												  	$staff_length_versions.="<tr><td>".$versions['staff_time']." $staff_length_option</td><td>$created_at</td></tr>";

												  	$mission_length_option=$versions['delivery_option']=='days' ? ' Jours' : ' Hours';

												  	$mission_length_versions.="<tr><td>".$versions['delivery_time']." $mission_length_option</td><td>$created_at</td></tr>";
												}										
											}


											//checking the version differences										
											

											if($mission['cost'] !=$previousMissionDetails[0]['cost'])
											{
												$prodMissionDetails[$p]['cost_difference']='yes';
												$prodMissionDetails[$p]['price_versions']=$table_start.$price_versions.$table_end;
											}
											if($mission['staff'] !=$previousMissionDetails[0]['staff'])
											{
												$prodMissionDetails[$p]['staff_difference']='yes';
												$prodMissionDetails[$p]['staff_versions']=$table_start.$staff_versions.$table_end;
											}

											$current_mission_lenght=$mission['delivery_option']=='hours' ? ($mission['delivery_time']/24) : $mission['delivery_time'];
											$previous_mission_lenght=$previousMissionDetails[0]['delivery_option']=='hours' ? ($previousMissionDetails[0]['delivery_time']/24) : $previousMissionDetails[0]['delivery_time'];
											if($current_mission_lenght !=$previous_mission_lenght)
											{
												$prodMissionDetails[$p]['mission_length_difference']='yes';	
												$prodMissionDetails[$p]['mission_length_versions']=$table_start.$mission_length_versions.$table_end;
											}

											$current_staff_lenght=$mission['staff_time_option']=='hours' ? ($mission['staff_time']/24) : $mission['staff_time'];
											$previous_staff_lenght=$previousMissionDetails[0]['staff_time_option']=='hours' ? ($previousMissionDetails[0]['staff_time']/24) : $previousMissionDetails[0]['staff_time'];
											if($current_staff_lenght !=$previous_staff_lenght)
											{
												$prodMissionDetails[$p]['staff_length_difference']='yes';	
												$prodMissionDetails[$p]['staff_length_versions']=$table_start.$staff_length_versions.$table_end;
											}



											$prodMissionDetails[$p]['previousMissionDetails']=$previousMissionDetails;
										}	

									}
									$p++;
								}


								$missonDetails[$m]['prod_mission_details']=$prodMissionDetails;	
							}
							else
							{
								//getting suggested mission Details for quote missions
								if($mission['sales_suggested_missions'])
								{
									$archmission_obj=new Ep_Quote_Mission();
									$archParameters['mission_id']=$mission['sales_suggested_missions'];
									$suggested_mission_details=$archmission_obj->getMissionDetails($archParameters);
									if($suggested_mission_details)
									{
										foreach($suggested_mission_details as $key=>$suggested_mission)
										{
											
											if($suggested_mission['writing_cost_before_signature_currency']!=$quote['sales_suggested_currency'])
											{
												$conversion=$quote['conversion'];
												$suggested_mission_details[$key]['writing_cost_before_signature']=($suggested_mission['writing_cost_before_signature']*$conversion);
												$suggested_mission_details[$key]['correction_cost_before_signature']=($suggested_mission['correction_cost_before_signature']*$conversion);
												$suggested_mission_details[$key]['other_cost_before_signature']=($suggested_mission['other_cost_before_signature']*$conversion);
												$suggested_mission_details[$key]['unit_price']=($suggested_mission['selling_price']*$conversion);
											}
											else
												$suggested_mission_details[$key]['unit_price']=($suggested_mission['selling_price']);


											//$sug_mission_length=$mission['volume']*($mission['nb_words']/$suggested_mission['article_length']);
											//$prod_mission_length=round($suggested_mission['mission_length']*($sug_mission_length/$suggested_mission['num_of_articles']));
											//$suggested_mission_details[$key]['mission_length']=$prod_mission_length;
											
											$suggested_mission_details[$key]['mission_length']=round(($quoteDetails[0]['sales_delivery_time']*90)/100);
											$staff_setup_length=ceil(($quoteDetails[0]['sales_delivery_time']*10)/100);
											$suggested_mission_details[$key]['staff_setup_length']=$staff_setup_length < 10 ? $staff_setup_length :10;

											//pre-fill staff calculations

											//total mission words
											$mission_volume=$mission['volume'];
											$mission_nb_words=$mission['nb_words'];
											$total_mission_words=($mission_volume*$mission_nb_words);
									
											//words that can write per writer with in delivery weeks
											$sales_delivery_time=$quote['sales_delivery_time_option']=='hours' ? ($quote['sales_delivery_time']/24) : $quote['sales_delivery_time'];
											$sales_delivery_week=ceil($sales_delivery_time/7);

											$mission_product=$mission['product_type'];
											if($mission['product_type']=='autre')
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

											$suggested_mission_details[$key]['writing_staff']=$writing_staff;
											$suggested_mission_details[$key]['proofreading_staff']=$proofreading_staff;

											//ENDED
										}


										$missonDetails[$m]['suggested_mission_details']=$suggested_mission_details;	
									}
								}
							}	

							$m++;
						}						
						//echo "<pre>";print_r($missonDetails);exit;
						$quoteDetails[$q]['mission_details']=$missonDetails;				


					}
					if($quote['version']>1)
					{
						$previousVersion=($quote['version']-1);
						$deletedMissionVersions=$this->deletedMissionVersions($quote['identifier'],$previousVersion,'sales');
						if($deletedMissionVersions)
							$quoteDetails[$q]['deletedMissionVersions']=$deletedMissionVersions;
					}		

					$q++;
				}
			}
			$this->_view->quoteDetails=$quoteDetails;			

			//getting tech mission details
			$tech_obj=new Ep_Quote_TechMissions();
			$searchParameters['quote_id']=$quote_id;
			$techMissionDetails=$tech_obj->getTechMissionDetails($searchParameters);
			//echo "<pre>";print_r($techMissionDetails);exit;
			if($techMissionDetails)
			{
				$t=0;
				foreach($techMissionDetails as $mission)
				{
					$client_obj=new Ep_Quote_Client();
					$bo_user_details=$client_obj->getQuoteUserDetails($mission['created_by']);
					$techMissionDetails[$t]['tech_user_name']=$bo_user_details[0]['first_name'].' '.$bo_user_details[0]['last_name'];
					$techMissionDetails[$t]['comment_time']=time_ago($mission['created_at']);

					//mission versionings if version is gt 1
					if($quoteDetails[0]['version']>1)
					{
						$previousVersion=($quoteDetails[0]['version']-1);

						$techMissionObj=new Ep_Quote_TechMissions();
						$previousMissionDetails=$techMissionObj->getMissionVersionDetails($mission['identifier'],$quoteDetails[0]['identifier'],$previousVersion);
						
						if($previousMissionDetails)
						{						
							//Get All version details of a mission									
							$allVersionMissionDetails=$techMissionObj->getMissionVersionDetails($mission['identifier'],$quoteDetails[0]['identifier']);
							if($allVersionMissionDetails)
							{
								$table_start='<table class="table quote-history table-striped">';
								$table_end='</table>';								
								$price_versions=$mission_length_versions='';
								$title_versions='';

								foreach($allVersionMissionDetails as $versions)
								{
								 	
								  	
								  	$created_at=date("d/m/Y", strtotime($versions['created_at']));
								  	
								  	$title_versions.="<tr><td>".$versions['title']."</td><td>$created_at</td></tr>";

								  	$price_versions.="<tr><td>".zero_cut($versions['cost'],2)." &". $versions['currency'].";</td><td>$created_at</td></tr>";

								  	$mission_length_option=$versions['delivery_option']=='days' ? ' Jours' : ' Hours';

								  	$mission_length_versions.="<tr><td>".$versions['delivery_time']." $mission_length_option</td><td>$created_at</td></tr>";
								}										
							}


							//checking the version differences
							if($mission['title'] !=$previousMissionDetails[0]['title'])
							{
								$techMissionDetails[$t]['title_difference']='yes';
								$techMissionDetails[$t]['title_versions']=$table_start.$title_versions.$table_end;
							}
							

							if($mission['cost'] !=$previousMissionDetails[0]['cost'])
							{
								$techMissionDetails[$t]['cost_difference']='yes';
								$techMissionDetails[$t]['price_versions']=$table_start.$price_versions.$table_end;
							}

							$current_mission_lenght=$mission['delivery_option']=='hours' ? ($mission['delivery_time']/24) : $mission['delivery_time'];
							$previous_mission_lenght=$previousMissionDetails[0]['delivery_option']=='hours' ? ($previousMissionDetails[0]['delivery_time']/24) : $previousMissionDetails[0]['delivery_time'];
							if($current_mission_lenght !=$previous_mission_lenght)
							{
								$techMissionDetails[$t]['mission_length_difference']='yes';	
								$techMissionDetails[$t]['mission_length_versions']=$table_start.$mission_length_versions.$table_end;
							}



							$techMissionDetails[$t]['previousMissionDetails']=$previousMissionDetails;
						}	

					}
					
					$techMissionDetails[$t]['files'] = "";
					if($mission['documents_path'])
					{
						/* $exploded_file_paths = explode("|",$mission['documents_path']);
						$exploded_file_names = explode("|",$mission['documents_name']);
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
									$files .= '<div class="topset2"><a href="/quote/download-document?type=tech_mission&mission_id='.$mission['identifier'].'&index='.$k.'">'.$fname.'</a></div>';
									
							}
							$k++;
						} */
						$filesarray = array('documents_path'=>$mission['documents_path'],'documents_name'=>$mission['documents_name'],'id'=>$mission['identifier'],'delete'=>false);
						$files = $this->getTechFiles($filesarray);
						$techMissionDetails[$t]['files'] = $files;
					}

					$t++;
				}				
				
				$this->_view->techMissionDetails=$techMissionDetails;
			}

			//ALL language list
			$language_array=$this->_arrayDb->loadArrayv2("EP_LANGUAGES", $this->_lang);
        	natsort($language_array);
        	$this->_view->ep_language_list=$language_array;

			//getting seo mission details
			//getting mission details
			unset($searchParameters);
			$searchParameters['quote_id']=$quote_id;
			$searchParameters['misson_user_type']='seo';
			$quoteMission_obj=new Ep_Quote_QuoteMissions();
			$seoMissionDetails=$quoteMission_obj->getMissionDetails($searchParameters);
			if($seoMissionDetails)
			{
				$s=0;
				foreach($seoMissionDetails as $mission)
				{
					if($mission['documents_path'])
					{
						/* $exploded_file_paths = explode("|",$mission['documents_path']);
						$exploded_file_names = explode("|",$mission['documents_name']);
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
									$files .= '<div class="topset2"><a href="/quote/download-document?type=seo_mission&mission_id='.$mission['identifier'].'&index='.$k.'">'.$fname.'</a></div>';
									
							}
							$k++;
						} */
						$filesarray = array('documents_path'=>$mission['documents_path'],'documents_name'=>$mission['documents_name'],'id'=>$mission['identifier'],'delete'=>false);
						$files = $this->getSeoFiles($filesarray);
						$seoMissionDetails[$s]['files'] = $files;
					}
					$client_obj=new Ep_Quote_Client();
					$bo_user_details=$client_obj->getQuoteUserDetails($mission['created_by']);
					$seoMissionDetails[$s]['seo_user_name']=$bo_user_details[0]['first_name'].' '.$bo_user_details[0]['last_name'];

					$seoMissionDetails[$s]['comment_time']=time_ago($mission['created_at']);

					$seoMissionDetails[$s]['product_name']=$this->seo_product_array[$mission['product']];

					//mission versionings if version is gt 1
					if($quoteDetails[0]['version']>1)
					{
						$previousVersion=($quoteDetails[0]['version']-1);

						$quoteMissionObj=new Ep_Quote_QuoteMissions();
						$previousMissionDetails=$quoteMissionObj->getMissionVersionDetails($mission['identifier'],$previousVersion,'seo');
						
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
							$allVersionMissionDetails=$quoteMissionObj->getMissionVersionDetails($mission['identifier'],NULL,'seo');
							if($allVersionMissionDetails)
							{
								$table_start='<table class="table quote-history table-striped">';
								$table_end='</table>';
								$product_versions=$language_versions=$product_type_versions=$volume_versions=$nb_words_versions='';
								$price_versions=$mission_length_versions='';

								foreach($allVersionMissionDetails as $versions)
								{
								 	if($versions['product']=='translation')
								  		$language= $this->getLanguageName($versions['language_source'])." > ".$this->getLanguageName($vmission['language_dest']);
								  	else
								  		$language= $this->getLanguageName($versions['language_source']);
								  	
								  	$created_at=date("d/m/Y", strtotime($versions['created_at']));

								  	$language_versions.="<tr><td>$language</td><td>$created_at</td></tr>";
								  	$product_versions.="<tr><td>".$this->seo_product_array[$versions['product']]."</td><td>$created_at</td></tr>";
								  	$product_type_versions.="<tr><td>".$this->producttype_array[$versions['product_type']]."</td><td>$created_at</td></tr>";
								  	$volume_versions.="<tr><td>".$versions['volume']."</td><td>$created_at</td></tr>";
								  	$nb_words_versions.="<tr><td>".$versions['nb_words']."</td><td>$created_at</td></tr>";
								  	$price_versions.="<tr><td>".zero_cut($versions['cost'],2)." &". $versions['sales_suggested_currency'].";</td><td>$created_at</td></tr>";

								  	$mission_length_option=$versions['mission_length_option']=='days' ? ' Jours' : ' Hours';

								  	$mission_length_versions.="<tr><td>".$versions['mission_length']." $mission_length_option</td><td>$created_at</td></tr>";
								}										
							}


							//checking the version differences
							if($mission['language_source'] !=$previousMissionDetails[0]['language_source'])
							{
								$seoMissionDetails[$s]['language_difference']='yes';
								$seoMissionDetails[$s]['language_versions']=$table_start.$language_versions.$table_end;
							}

							if($mission['language_dest'] !=$previousMissionDetails[0]['language_dest'])
							{
								$seoMissionDetails[$s]['language_difference']='yes';
								$seoMissionDetails[$s]['language_versions']=$table_start.$language_versions.$table_end;
							}
							if($mission['product'] !=$previousMissionDetails[0]['product'])
							{
								$seoMissionDetails[$s]['product_difference']='yes';
								$seoMissionDetails[$s]['product_versions']=$table_start.$product_versions.$table_end;
							
							}


							if($mission['product_type'] !=$previousMissionDetails[0]['product_type'])
							{
								$seoMissionDetails[$s]['product_type_difference']='yes';
								$seoMissionDetails[$s]['product_type_versions']=$table_start.$product_type_versions.$table_end;
							
							}

							if($mission['volume'] !=$previousMissionDetails[0]['volume'])
							{
								$seoMissionDetails[$s]['volume_difference']='yes';
								$seoMissionDetails[$s]['volume_versions']=$table_start.$volume_versions.$table_end;
							}
							
							if($mission['nb_words'] !=$previousMissionDetails[0]['nb_words'])
							{
								$seoMissionDetails[$s]['nb_words_difference']='yes';
								$seoMissionDetails[$s]['nb_words_versions']=$table_start.$nb_words_versions.$table_end;
							}
							
							if($mission['unit_price'] !=$previousMissionDetails[0]['unit_price'])
							{
								$seoMissionDetails[$s]['unit_price_difference']='yes';
								$seoMissionDetails[$s]['price_versions']=$table_start.$price_versions.$table_end;
							}

							$current_mission_lenght=$mission['mission_length_option']=='hours' ? ($mission['mission_length']/24) : $mission['mission_length'];
							$previous_mission_lenght=$previousMissionDetails[0]['mission_length_option']=='hours' ? ($previousMissionDetails[0]['mission_length']/24) : $previousMissionDetails[0]['mission_length'];
							if($current_mission_lenght !=$previous_mission_lenght)
							{
								$seoMissionDetails[$s]['mission_length_difference']='yes';	
								$seoMissionDetails[$s]['mission_length_versions']=$table_start.$mission_length_versions.$table_end;
							}



							$seoMissionDetails[$s]['previousMissionDetails']=$previousMissionDetails;
						}	

					}

					$s++;
				}	
				$this->_view->seoMissionDetails=$seoMissionDetails;
			}
			
		}
		//echo "<pre>";print_r($seoMissionDetails);exit;

		return $html=$this->_view->renderHtml('prod-quote-view-details'); 

		
	}
	//Prod review
	public function prodQuoteReviewAction()
	{
		$prod_parameters=$this->_request->getParams();

		$quote_id=$prod_parameters['quote_id'];

		$quote_obj=new Ep_Quote_Quotes();

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
					
					if($quote['documents_path'])
					{
						/* $related_files='';
						$documents_path=explode("|",$quote['documents_path']);
						$documents_name=explode("|",$quote['documents_name']);

						
						foreach($documents_path as $k=>$file)
						{
							if(file_exists($this->quote_documents_path.$documents_path[$k]) && !is_dir($this->quote_documents_path.$documents_path[$k]))
							{
							if($documents_name[$k])
								$file_name=$documents_name[$k];
							else
								$file_name=basename($file);

							$related_files.='
							<a href="/quote/download-document?type=quote&index='.$k.'&quote_id='.$quote_id.'">'.$file_name.'</a><br>';
							}
						} */
						$files = array('documents_path'=>$quote['documents_path'],'documents_name'=>$quote['documents_name'],'quote_id'=>$quote_id,'delete'=>false);
						$related_files = $this->getQuoteFiles($files);
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
					$searchParameters['misson_user_type']='sales';
					$quoteMission_obj=new Ep_Quote_QuoteMissions();
					$missonDetails=$quoteMission_obj->getMissionDetails($searchParameters);
					if($missonDetails)
					{
						$m=0;
						foreach($missonDetails as $mission)
						{
							$missonDetails[$m]['product_name']=$this->product_array[$mission['product']];			
							$missonDetails[$m]['language_source_name']=$this->getLanguageName($mission['language_source']);
							$missonDetails[$m]['product_type_name']=$this->producttype_array[$mission['product_type']];
							if($mission['language_dest'])
								$missonDetails[$m]['language_dest_name']=$this->getLanguageName($mission['language_dest']);

							$quoteDetails[$q]['missions_list'][$mission['identifier']]='Mission '.($m+1).' - '.$missonDetails[$m]['product_name'];

							$missonDetails[$m]['comment_time']=time_ago($mission['created_at']);
							//mission versionings if version is gt 1
							if($quote['version']>1)
							{
								$previousVersion=($quote['version']-1);

								$quoteMissionObj=new Ep_Quote_QuoteMissions();
								$previousMissionDetails=$quoteMissionObj->getMissionVersionDetails($mission['identifier'],$previousVersion,'sales');
								
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
									$allVersionMissionDetails=$quoteMissionObj->getMissionVersionDetails($mission['identifier'],NULL,'sales');
									if($allVersionMissionDetails)
									{
										$table_start='<table class="table quote-history table-striped">';
										$table_end='</table>';
										$language_versions=$product_type_versions=$volume_versions=$nb_words_versions='';
										$price_versions=$mission_length_versions='';

										foreach($allVersionMissionDetails as $versions)
										{
										 	if($versions['product']=='translation')
										  		$language= $this->getLanguageName($versions['language_source'])." > ".$this->getLanguageName($vmission['language_dest']);
										  	else
										  		$language= $this->getLanguageName($versions['language_source']);
										  	
										  	$created_at=date("d/m/Y", strtotime($versions['created_at']));

										  	$language_versions.="<tr><td>$language</td><td>$created_at</td></tr>";
										  	$product_type_versions.="<tr><td>".$this->producttype_array[$versions['product_type']]."</td><td>$created_at</td></tr>";
										  	$volume_versions.="<tr><td>".$versions['volume']."</td><td>$created_at</td></tr>";
										  	$nb_words_versions.="<tr><td>".$versions['nb_words']."</td><td>$created_at</td></tr>";
										  	$price_versions.="<tr><td>".zero_cut($versions['unit_price'],2)." &". $versions['sales_suggested_currency'].";</td><td>$created_at</td></tr>";

										  	$mission_length_option=$versions['mission_length_option']=='days' ? ' Jours' : ' Hours';

										  	$mission_length_versions.="<tr><td>".$versions['mission_length']." $mission_length_option</td><td>$created_at</td></tr>";
										}										
									}


									//checking the version differences
									if($mission['language_source'] !=$previousMissionDetails[0]['language_source'])
									{
										$missonDetails[$m]['language_difference']='yes';
										$missonDetails[$m]['language_versions']=$table_start.$language_versions.$table_end;
									}

									if($mission['language_dest'] !=$previousMissionDetails[0]['language_dest'])
									{
										$missonDetails[$m]['language_difference']='yes';
										$missonDetails[$m]['language_versions']=$table_start.$language_versions.$table_end;
									}

									if($mission['product_type'] !=$previousMissionDetails[0]['product_type'])
									{
										$missonDetails[$m]['product_type_difference']='yes';
										$missonDetails[$m]['product_type_versions']=$table_start.$product_type_versions.$table_end;
									
									}

									if($mission['volume'] !=$previousMissionDetails[0]['volume'])
									{
										$missonDetails[$m]['volume_difference']='yes';
										$missonDetails[$m]['volume_versions']=$table_start.$volume_versions.$table_end;
									}
									
									if($mission['nb_words'] !=$previousMissionDetails[0]['nb_words'])
									{
										$missonDetails[$m]['nb_words_difference']='yes';
										$missonDetails[$m]['nb_words_versions']=$table_start.$nb_words_versions.$table_end;
									}
									
									if($mission['unit_price'] !=$previousMissionDetails[0]['unit_price'])
									{
										$missonDetails[$m]['unit_price_difference']='yes';
										$missonDetails[$m]['price_versions']=$table_start.$price_versions.$table_end;
									}

									$current_mission_lenght=$mission['mission_length_option']=='hours' ? ($mission['mission_length']/24) : $mission['mission_length'];
									$previous_mission_lenght=$previousMissionDetails[0]['mission_length_option']=='hours' ? ($previousMissionDetails[0]['mission_length']/24) : $previousMissionDetails[0]['mission_length'];
									if($current_mission_lenght !=$previous_mission_lenght)
									{
										$missonDetails[$m]['mission_length_difference']='yes';	
										$missonDetails[$m]['mission_length_versions']=$table_start.$mission_length_versions.$table_end;
									}



									$missonDetails[$m]['previousMissionDetails']=$previousMissionDetails;
								}	

							}


							//Get seo missions related to a mission
							$searchParameters['quote_id']=$quote_id;
							$searchParameters['misson_user_type']='seo';
							$searchParameters['related_to']=$mission['identifier'];
							$searchParameters['product']=$mission['product'];
							//echo "<pre>";print_r($searchParameters);
							$quoteMission_obj=new Ep_Quote_QuoteMissions();
							$seoMissonDetails=$quoteMission_obj->getMissionDetails($searchParameters);
							//echo "<pre>";print_r($seoMissonDetails);exit;
							if($seoMissonDetails)
							{
								$s=0;
								foreach($seoMissonDetails as $smission)
								{									
									$client_obj=new Ep_Quote_Client();
									$bo_user_details=$client_obj->getQuoteUserDetails($smission['created_by']);
									$seoMissonDetails[$s]['seo_user_name']=$bo_user_details[0]['first_name'].' '.$bo_user_details[0]['last_name'];

									$seoMissonDetails[$s]['comment_time']=time_ago($smission['created_at']);

									$seoMissonDetails[$s]['product_type_name']=$this->producttype_array[$smission['product_type']];

									$prodMissionObj=new Ep_Quote_ProdMissions();

									$searchParameters['quote_mission_id']=$smission['identifier'];
									$prodMissionDetails=$prodMissionObj->getProdMissionDetails($searchParameters);
									//echo "<pre>";print_r($prodMissionDetails);exit;

									if($prodMissionDetails)
									{
										$seoMissonDetails[$s]['prod_mission_details']=$prodMissionDetails;	
									}
									else
									{

										//getting suggested mission Details for seo missions
										if($smission['sales_suggested_missions'])
										{
											$archmission_obj=new Ep_Quote_Mission();
											$archParameters['mission_id']=$smission['sales_suggested_missions'];
											$suggested_mission_details=$archmission_obj->getMissionDetails($archParameters);										
											if($suggested_mission_details)
											{
												foreach($suggested_mission_details as $key=>$suggested_mission)
												{
													$sug_mission_length=$smission['volume']*($smission['nb_words']/$suggested_mission['article_length']);
													$prod_mission_length=round($suggested_mission['mission_length']*($sug_mission_length/$suggested_mission['num_of_articles']));
													$suggested_mission_details[$key]['mission_length']=$prod_mission_length;

													$suggested_mission_details[$key]['mission_length']=round(($quoteDetails[0]['sales_delivery_time']*90)/100);
													$staff_setup_length=ceil(($quoteDetails[0]['sales_delivery_time']*10)/100);
													$suggested_mission_details[$key]['staff_setup_length']=$staff_setup_length < 10 ? $staff_setup_length :10;

													//pre-fill staff calculations

													//total mission words
													$mission_volume=$smission['volume'];
													$mission_nb_words=$smission['nb_words'];
													$total_mission_words=($mission_volume*$mission_nb_words);
											
													//words that can write per writer with in delivery weeks
													$sales_delivery_time=$quote['sales_delivery_time_option']=='hours' ? ($quote['sales_delivery_time']/24) : $quote['sales_delivery_time'];
													$sales_delivery_week=ceil($sales_delivery_time/7);

													$mission_product=$smission['product_type'];
													$articles_perweek=$this->configval['max_writer_'.$mission_product];
													$words_perweek_peruser=$articles_perweek*250;
													$words_peruser_perdelivery=$sales_delivery_week*$words_perweek_peruser;

													//wrting and proofreading staff calculations
													$writing_staff=round($total_mission_words/$words_peruser_perdelivery);
													if(!$writing_staff || $writing_staff <1)
														$writing_staff=1;													

													$suggested_mission_details[$key]['writing_staff']=$writing_staff;
													
												}
												
												$seoMissonDetails[$s]['suggested_mission_details']=$suggested_mission_details;	
											}
											//echo "<pre>";print_r($seoMissonDetails);exit;
										}	
									}	

									$s++;	
								}

								$missonDetails[$m]['seoMissions']=$seoMissonDetails;
							}
							//echo "<pre>";print_r($missonDetails);exit;

							$prodMissionObj=new Ep_Quote_ProdMissions();

							$searchParameters['quote_mission_id']=$mission['identifier'];
							$prodMissionDetails=$prodMissionObj->getProdMissionDetails($searchParameters);
							//echo "<pre>";print_r($prodMissionDetails);exit;

							if($prodMissionDetails)
							{
								$p=0;
								foreach($prodMissionDetails as $mission)
								{
									//mission versionings if version is gt 1
									if($quote['version']>1)
									{
										$previousVersion=($quote['version']-1);

										$prodMissionObj=new Ep_Quote_ProdMissions();
										$previousMissionDetails=$prodMissionObj->getMissionVersionDetails($mission['identifier'],$previousVersion);
										
										if($previousMissionDetails)
										{						
											//Get All version details of a mission									
											$allVersionMissionDetails=$prodMissionObj->getMissionVersionDetails($mission['identifier']);

											if($allVersionMissionDetails)
											{
												$table_start='<table class="table quote-history table-striped">';
												$table_end='</table>';								
												$price_versions=$mission_length_versions='';
												$staff_versions=$staff_length_versions='';

												foreach($allVersionMissionDetails as $versions)
												{
												 	
												  	
												  	$created_at=date("d/m/Y", strtotime($versions['created_at']));												  	
												  	
												  	$staff_versions.="<tr><td>".$versions['staff']."</td><td>$created_at</td></tr>";

												  	$price_versions.="<tr><td>".zero_cut($versions['cost'],2)." &". $versions['currency'].";</td><td>$created_at</td></tr>";

												  	$staff_length_option=$versions['staff_time_option']=='days' ? ' Jours' : ' Hours';

												  	$staff_length_versions.="<tr><td>".$versions['staff_time']." $staff_length_option</td><td>$created_at</td></tr>";

												  	$mission_length_option=$versions['delivery_option']=='days' ? ' Jours' : ' Hours';

												  	$mission_length_versions.="<tr><td>".$versions['delivery_time']." $mission_length_option</td><td>$created_at</td></tr>";
												}										
											}


											//checking the version differences										
											

											if($mission['cost'] !=$previousMissionDetails[0]['cost'])
											{
												$prodMissionDetails[$p]['cost_difference']='yes';
												$prodMissionDetails[$p]['price_versions']=$table_start.$price_versions.$table_end;
											}
											if($mission['staff'] !=$previousMissionDetails[0]['staff'])
											{
												$prodMissionDetails[$p]['staff_difference']='yes';
												$prodMissionDetails[$p]['staff_versions']=$table_start.$staff_versions.$table_end;
											}

											$current_mission_lenght=$mission['delivery_option']=='hours' ? ($mission['delivery_time']/24) : $mission['delivery_time'];
											$previous_mission_lenght=$previousMissionDetails[0]['delivery_option']=='hours' ? ($previousMissionDetails[0]['delivery_time']/24) : $previousMissionDetails[0]['delivery_time'];
											if($current_mission_lenght !=$previous_mission_lenght)
											{
												$prodMissionDetails[$p]['mission_length_difference']='yes';	
												$prodMissionDetails[$p]['mission_length_versions']=$table_start.$mission_length_versions.$table_end;
											}

											$current_staff_lenght=$mission['staff_time_option']=='hours' ? ($mission['staff_time']/24) : $mission['staff_time'];
											$previous_staff_lenght=$previousMissionDetails[0]['staff_time_option']=='hours' ? ($previousMissionDetails[0]['staff_time']/24) : $previousMissionDetails[0]['staff_time'];
											if($current_staff_lenght !=$previous_staff_lenght)
											{
												$prodMissionDetails[$p]['staff_length_difference']='yes';	
												$prodMissionDetails[$p]['staff_length_versions']=$table_start.$staff_length_versions.$table_end;
											}



											$prodMissionDetails[$p]['previousMissionDetails']=$previousMissionDetails;
										}	

									}
									$p++;
								}


								$missonDetails[$m]['prod_mission_details']=$prodMissionDetails;	
							}
							else
							{
								//getting suggested mission Details for quote missions
								if($mission['sales_suggested_missions'])
								{
									$archmission_obj=new Ep_Quote_Mission();
									$archParameters['mission_id']=$mission['sales_suggested_missions'];
									$suggested_mission_details=$archmission_obj->getMissionDetails($archParameters);
									if($suggested_mission_details)
									{
										foreach($suggested_mission_details as $key=>$suggested_mission)
										{
											
											if($suggested_mission['writing_cost_before_signature_currency']!=$quote['sales_suggested_currency'])
											{
												$conversion=$quote['conversion'];
												$suggested_mission_details[$key]['writing_cost_before_signature']=($suggested_mission['writing_cost_before_signature']*$conversion);
												$suggested_mission_details[$key]['correction_cost_before_signature']=($suggested_mission['correction_cost_before_signature']*$conversion);
												$suggested_mission_details[$key]['other_cost_before_signature']=($suggested_mission['other_cost_before_signature']*$conversion);
												$suggested_mission_details[$key]['unit_price']=($suggested_mission['selling_price']*$conversion);
											}
											else
												$suggested_mission_details[$key]['unit_price']=($suggested_mission['selling_price']);


											//$sug_mission_length=$mission['volume']*($mission['nb_words']/$suggested_mission['article_length']);
											//$prod_mission_length=round($suggested_mission['mission_length']*($sug_mission_length/$suggested_mission['num_of_articles']));
											//$suggested_mission_details[$key]['mission_length']=$prod_mission_length;
											
											$suggested_mission_details[$key]['mission_length']=round(($quoteDetails[0]['sales_delivery_time']*90)/100);
											$staff_setup_length=ceil(($quoteDetails[0]['sales_delivery_time']*10)/100);
											$suggested_mission_details[$key]['staff_setup_length']=$staff_setup_length < 10 ? $staff_setup_length :10;

											//pre-fill staff calculations

											//total mission words
											$mission_volume=$mission['volume'];
											$mission_nb_words=$mission['nb_words'];
											$total_mission_words=($mission_volume*$mission_nb_words);
									
											//words that can write per writer with in delivery weeks
											$sales_delivery_time=$quote['sales_delivery_time_option']=='hours' ? ($quote['sales_delivery_time']/24) : $quote['sales_delivery_time'];
											$sales_delivery_week=ceil($sales_delivery_time/7);

											$mission_product=$mission['product_type'];
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

											$suggested_mission_details[$key]['writing_staff']=$writing_staff;
											$suggested_mission_details[$key]['proofreading_staff']=$proofreading_staff;

											//ENDED
										}


										$missonDetails[$m]['suggested_mission_details']=$suggested_mission_details;	
									}
								}
							}	

							$m++;
						}						
						//echo "<pre>";print_r($missonDetails);exit;
						$quoteDetails[$q]['mission_details']=$missonDetails;				


					}
					if($quote['version']>1)
					{
						$previousVersion=($quote['version']-1);
						$deletedMissionVersions=$this->deletedMissionVersions($quote['identifier'],$previousVersion,'sales');
						if($deletedMissionVersions)
							$quoteDetails[$q]['deletedMissionVersions']=$deletedMissionVersions;
					}		

					$q++;
				}
			}
			$this->_view->quoteDetails=$quoteDetails;			

			//getting tech mission details
			$tech_obj=new Ep_Quote_TechMissions();
			$searchParameters['quote_id']=$quote_id;
			$techMissionDetails=$tech_obj->getTechMissionDetails($searchParameters);
			if($techMissionDetails)
			{
				$t=0;
				foreach($techMissionDetails as $mission)
				{
					$client_obj=new Ep_Quote_Client();
					$bo_user_details=$client_obj->getQuoteUserDetails($mission['created_by']);
					$techMissionDetails[$t]['tech_user_name']=$bo_user_details[0]['first_name'].' '.$bo_user_details[0]['last_name'];
					$techMissionDetails[$t]['comment_time']=time_ago($mission['created_at']);

					//mission versionings if version is gt 1
					if($quoteDetails[0]['version']>1)
					{
						$previousVersion=($quoteDetails[0]['version']-1);

						$techMissionObj=new Ep_Quote_TechMissions();
						$previousMissionDetails=$techMissionObj->getMissionVersionDetails($mission['identifier'],$quoteDetails[0]['identifier'],$previousVersion);
						
						if($previousMissionDetails)
						{						
							//Get All version details of a mission									
							$allVersionMissionDetails=$techMissionObj->getMissionVersionDetails($mission['identifier'],$quoteDetails[0]['identifier']);
							if($allVersionMissionDetails)
							{
								$table_start='<table class="table quote-history table-striped">';
								$table_end='</table>';								
								$price_versions=$mission_length_versions='';
								$title_versions='';

								foreach($allVersionMissionDetails as $versions)
								{
								 	
								  	
								  	$created_at=date("d/m/Y", strtotime($versions['created_at']));
								  	
								  	$title_versions.="<tr><td>".$versions['title']."</td><td>$created_at</td></tr>";

								  	$price_versions.="<tr><td>".zero_cut($versions['cost'],2)." &". $versions['currency'].";</td><td>$created_at</td></tr>";

								  	$mission_length_option=$versions['delivery_option']=='days' ? ' Jours' : ' Hours';

								  	$mission_length_versions.="<tr><td>".$versions['delivery_time']." $mission_length_option</td><td>$created_at</td></tr>";
								}										
							}


							//checking the version differences
							if($mission['title'] !=$previousMissionDetails[0]['title'])
							{
								$techMissionDetails[$t]['title_difference']='yes';
								$techMissionDetails[$t]['title_versions']=$table_start.$title_versions.$table_end;
							}
							

							if($mission['cost'] !=$previousMissionDetails[0]['cost'])
							{
								$techMissionDetails[$t]['cost_difference']='yes';
								$techMissionDetails[$t]['price_versions']=$table_start.$price_versions.$table_end;
							}

							$current_mission_lenght=$mission['delivery_option']=='hours' ? ($mission['delivery_time']/24) : $mission['delivery_time'];
							$previous_mission_lenght=$previousMissionDetails[0]['delivery_option']=='hours' ? ($previousMissionDetails[0]['delivery_time']/24) : $previousMissionDetails[0]['delivery_time'];
							if($current_mission_lenght !=$previous_mission_lenght)
							{
								$techMissionDetails[$t]['mission_length_difference']='yes';	
								$techMissionDetails[$t]['mission_length_versions']=$table_start.$mission_length_versions.$table_end;
							}



							$techMissionDetails[$t]['previousMissionDetails']=$previousMissionDetails;
						}	

					}
					
					$techMissionDetails[$t]['files'] = "";
					if($mission['documents_path'])
					{
						/* $exploded_file_paths = explode("|",$mission['documents_path']);
						$exploded_file_names = explode("|",$mission['documents_name']);
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
									$files .= '<div class="topset2"><a href="/quote/download-document?type=tech_mission&mission_id='.$mission['identifier'].'&index='.$k.'">'.$fname.'</a></div>';
									
							}
							$k++;
						} */
						$filesarray = array('documents_path'=>$mission['documents_path'],'documents_name'=>$mission['documents_name'],'id'=>$mission['identifier'],'delete'=>false);
						$files = $this->getTechFiles($filesarray);
						$techMissionDetails[$t]['files'] = $files;
					}

					$t++;
				}				
				
				$this->_view->techMissionDetails=$techMissionDetails;
			}

			//ALL language list
			$language_array=$this->_arrayDb->loadArrayv2("EP_LANGUAGES", $this->_lang);
        	natsort($language_array);
        	$this->_view->ep_language_list=$language_array;

			//getting seo mission details
			//getting mission details
			unset($searchParameters);
			$searchParameters['quote_id']=$quote_id;
			$searchParameters['misson_user_type']='seo';
			$quoteMission_obj=new Ep_Quote_QuoteMissions();
			$seoMissionDetails=$quoteMission_obj->getMissionDetails($searchParameters);
			if($seoMissionDetails)
			{
				$s=0;
				foreach($seoMissionDetails as $mission)
				{
					if($mission['documents_path'])
					{
							/* $exploded_file_paths = explode("|",$mission['documents_path']);
						$exploded_file_names = explode("|",$mission['documents_name']);
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
									$files .= '<div class="topset2"><a href="/quote/download-document?type=seo_mission&mission_id='.$mission['identifier'].'&index='.$k.'">'.$fname.'</a></div>';
									
							}
							$k++;
						} */
						$filesarray = array('documents_path'=>$mission['documents_path'],'documents_name'=>$mission['documents_name'],'id'=>$mission['identifier'],'delete'=>false);
						$files = $this->getSeoFiles($filesarray);
						$seoMissionDetails[$s]['files'] = $files;
					}
					$client_obj=new Ep_Quote_Client();
					$bo_user_details=$client_obj->getQuoteUserDetails($mission['created_by']);
					$seoMissionDetails[$s]['seo_user_name']=$bo_user_details[0]['first_name'].' '.$bo_user_details[0]['last_name'];

					$seoMissionDetails[$s]['comment_time']=time_ago($mission['created_at']);

					$seoMissionDetails[$s]['product_name']=$this->seo_product_array[$mission['product']];

					//mission versionings if version is gt 1
					if($quoteDetails[0]['version']>1)
					{
						$previousVersion=($quoteDetails[0]['version']-1);

						$quoteMissionObj=new Ep_Quote_QuoteMissions();
						$previousMissionDetails=$quoteMissionObj->getMissionVersionDetails($mission['identifier'],$previousVersion,'seo');
						
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
							$allVersionMissionDetails=$quoteMissionObj->getMissionVersionDetails($mission['identifier'],NULL,'seo');
							if($allVersionMissionDetails)
							{
								$table_start='<table class="table quote-history table-striped">';
								$table_end='</table>';
								$product_versions=$language_versions=$product_type_versions=$volume_versions=$nb_words_versions='';
								$price_versions=$mission_length_versions='';

								foreach($allVersionMissionDetails as $versions)
								{
								 	if($versions['product']=='translation')
								  		$language= $this->getLanguageName($versions['language_source'])." > ".$this->getLanguageName($vmission['language_dest']);
								  	else
								  		$language= $this->getLanguageName($versions['language_source']);
								  	
								  	$created_at=date("d/m/Y", strtotime($versions['created_at']));

								  	$language_versions.="<tr><td>$language</td><td>$created_at</td></tr>";
								  	$product_versions.="<tr><td>".$this->seo_product_array[$versions['product']]."</td><td>$created_at</td></tr>";
								  	$product_type_versions.="<tr><td>".$this->producttype_array[$versions['product_type']]."</td><td>$created_at</td></tr>";
								  	$volume_versions.="<tr><td>".$versions['volume']."</td><td>$created_at</td></tr>";
								  	$nb_words_versions.="<tr><td>".$versions['nb_words']."</td><td>$created_at</td></tr>";
								  	$price_versions.="<tr><td>".zero_cut($versions['cost'],2)." &". $versions['sales_suggested_currency'].";</td><td>$created_at</td></tr>";

								  	$mission_length_option=$versions['mission_length_option']=='days' ? ' Jours' : ' Hours';

								  	$mission_length_versions.="<tr><td>".$versions['mission_length']." $mission_length_option</td><td>$created_at</td></tr>";
								}										
							}


							//checking the version differences
							if($mission['language_source'] !=$previousMissionDetails[0]['language_source'])
							{
								$seoMissionDetails[$s]['language_difference']='yes';
								$seoMissionDetails[$s]['language_versions']=$table_start.$language_versions.$table_end;
							}

							if($mission['language_dest'] !=$previousMissionDetails[0]['language_dest'])
							{
								$seoMissionDetails[$s]['language_difference']='yes';
								$seoMissionDetails[$s]['language_versions']=$table_start.$language_versions.$table_end;
							}
							if($mission['product'] !=$previousMissionDetails[0]['product'])
							{
								$seoMissionDetails[$s]['product_difference']='yes';
								$seoMissionDetails[$s]['product_versions']=$table_start.$product_versions.$table_end;
							
							}


							if($mission['product_type'] !=$previousMissionDetails[0]['product_type'])
							{
								$seoMissionDetails[$s]['product_type_difference']='yes';
								$seoMissionDetails[$s]['product_type_versions']=$table_start.$product_type_versions.$table_end;
							
							}

							if($mission['volume'] !=$previousMissionDetails[0]['volume'])
							{
								$seoMissionDetails[$s]['volume_difference']='yes';
								$seoMissionDetails[$s]['volume_versions']=$table_start.$volume_versions.$table_end;
							}
							
							if($mission['nb_words'] !=$previousMissionDetails[0]['nb_words'])
							{
								$seoMissionDetails[$s]['nb_words_difference']='yes';
								$seoMissionDetails[$s]['nb_words_versions']=$table_start.$nb_words_versions.$table_end;
							}
							
							if($mission['unit_price'] !=$previousMissionDetails[0]['unit_price'])
							{
								$seoMissionDetails[$s]['unit_price_difference']='yes';
								$seoMissionDetails[$s]['price_versions']=$table_start.$price_versions.$table_end;
							}

							$current_mission_lenght=$mission['mission_length_option']=='hours' ? ($mission['mission_length']/24) : $mission['mission_length'];
							$previous_mission_lenght=$previousMissionDetails[0]['mission_length_option']=='hours' ? ($previousMissionDetails[0]['mission_length']/24) : $previousMissionDetails[0]['mission_length'];
							if($current_mission_lenght !=$previous_mission_lenght)
							{
								$seoMissionDetails[$s]['mission_length_difference']='yes';	
								$seoMissionDetails[$s]['mission_length_versions']=$table_start.$mission_length_versions.$table_end;
							}



							$seoMissionDetails[$s]['previousMissionDetails']=$previousMissionDetails;
						}	

					}

					$s++;
				}	
				$this->_view->seoMissionDetails=$seoMissionDetails;
			}
			
		}
		//echo "<pre>";print_r($seoMissionDetails);exit;

		$this->render('prod-quote-review');
	}

	//save seo reviews based on actions
	public function saveProdReviewAction()
	{
		if($this->_request-> isPost()  && $this->adminLogin->userId)
		{
			$prod_params=$this->_request->getParams();
			
			//echo "<pre>";print_r($_FILES);print_r($prod_params);exit;

			$quote_id=$prod_params['quote_id'];

			if(isset($prod_params['review_skip'])) $status='skipped';
			else if(isset($prod_params['review_challenge'])) $status='challenged';
			else if(isset($prod_params['review_save'])) $status='challenged';
			else if(isset($prod_params['review_validate'])) $status='validated';

			if($quote_id)
			{	
				
				//get Quote version
					$quote_obj=new Ep_Quote_Quotes();
					$version=$quote_obj->getQuoteVersion($quote_id);

				//Insert Quote log
				$log_params['quote_id']	= $quote_id;
				$log_params['bo_user']	= $this->adminLogin->userId;					
				$log_params['version']	= $version;
				$log_params['action']	= 'prod_'.$status;				


				if(isset($prod_params['review_skip'])|| isset($prod_params['review_challenge']))
				{
					

					$quote_obj=new Ep_Quote_Quotes();
					$update_quote['prod_review']=$status;
					
					//echo "<pre>";print_r($update_quote);exit;
					$quote_obj->updateQuote($update_quote,$quote_id);

					if($status=='skipped')
						$this->_redirect("/quote/sales-quotes-list?submenuId=ML13-SL2");
					else if($status=='challenged')
						$this->_redirect("/quote/prod-quote-review?quote_id=".$quote_id);	
				}
				elseif(isset($prod_params['review_save'])|| isset($prod_params['review_validate']))
				{
						//echo "<pre>";print_r($prod_params);
					foreach($_POST as $key => $prod_missions)
					{
					    if (strpos($key, 'pmission_cost_') === 0)
					    {
					    	$mission_id=str_replace('pmission_cost_','',$key);
					    	foreach($prod_missions as $pkey=>$pcost)
					    	{						    		
					    		$pcost=str_replace(",",".",$pcost);
					    		$pdelivery_time=$prod_params['pdelivery_time_'.$mission_id][$pkey];
					    		$pdelivery_option=$prod_params['pdelivery_option_'.$mission_id][$pkey];
					    		$staff=$prod_params['staff_'.$mission_id][$pkey];
					    		$staff_time=$prod_params['staff_time_'.$mission_id][$pkey];
					    		$staff_time_option=$prod_params['staff_time_option_'.$mission_id][$pkey];
					    		$prod_comments=isodec($prod_params['prodcomments_'.$mission_id][$pkey]);
					    		$product=$prod_params['prod_product_'.$mission_id][$pkey];					    		

					    		//if($pdelivery_time && $staff && $staff_time)
					    		//{
					    			$prod_mission_obj=new Ep_Quote_ProdMissions();

					    			$prod_mission_data['quote_mission_id']=$mission_id;
					    			$prod_mission_data['product']=$product;						    			
					    			$prod_mission_data['delivery_time']=$pdelivery_time;
					    			$prod_mission_data['delivery_option']=$pdelivery_option;
					    			$prod_mission_data['staff']=$staff;
					    			$prod_mission_data['staff_time']=$staff_time;
					    			$prod_mission_data['staff_time_option']=$staff_time_option;
					    			$prod_mission_data['cost']=$pcost;
					    			$prod_mission_data['currency']=$prod_params['currency'];
					    			$prod_mission_data['comments']=$prod_comments;
					    			$prod_mission_data['created_by']=$this->adminLogin->userId;
					    			$prod_mission_data['version']	= $version;

					    			//echo "<pre>";print_r($prod_mission_obj);

					    			$prod_mission_id=$prod_params['prod_mission_id_'.$mission_id][$pkey];

					    			if($prod_mission_id)
					    			{
					    				$prod_mission_data['updated_at']=date('Y-m-d H:i:s');
					    				$prod_mission_obj->updateProdMission($prod_mission_data,$prod_mission_id);
					    			}
					    			else
					    			{
					    				$prod_mission_obj->insertProdMission($prod_mission_data);	
					    			}

					    			//
					    			$package=$prod_params['package_'.$mission_id];
						    		$quoteMissionObj=new Ep_Quote_QuoteMissions();
						    		$updateQuoteMission['package']=$package;
						    		if($package=='lead')
						    			 	$updateQuoteMission['margin_percentage']=60;
						    		elseif($package=='link')
						    			 	$updateQuoteMission['margin_percentage']=30;
						    		elseif($package=='team')
						    			 	$updateQuoteMission['margin_percentage']=50;
						    			 		 
						    		$quoteMissionObj->updateQuoteMission($updateQuoteMission,$mission_id);

					    			
					    			
					    		//}

					    	}	
					    }
					}
						$quote_obj=new Ep_Quote_Quotes();	
						if(isset($prod_params['review_validate']))
						{
							$update_quote_prod['prod_review']=$status;
							$update_quote_prod["sales_validation_expires"]=time()+($this->configval['sales_validation_timeline']*60*60);
							$quote_obj->updateQuote($update_quote_prod,$quote_id);
						}
						if($prod_params['prod_extra_info'])
						{
							$update_quote_prod = array();
							$update_quote_prod['prod_extra_info']=$prod_params['prod_extra_info'];
							if($prod_params['prod_extra_info']=='yes')
							$update_quote_prod["prod_extra_comments"] = $prod_params['prod_extra_comments'];	
							else
							$update_quote_prod["prod_extra_comments"] = NULL;
							$quote_obj->updateQuote($update_quote_prod,$quote_id);
						}

						if($status=='challenged')
						{
							$log_params['action']= 'prod_saved';
							$quiteActionId=4;	

							if($prod_params['quote_updated_comments'])
								$log_params['comments']=$prod_params['quote_updated_comments'];
							
							$log_obj=new Ep_Quote_QuotesLog();
							$log_obj->insertLog($quiteActionId,$log_params);

							$this->_redirect("/quote/prod-quote-review?quote_id=".$quote_id);	
						}
						elseif($status=='validated')
						{
							$quoteDetails=$quote_obj->getQuoteDetails($quote_id);
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

							if($prod_params['quote_updated_comments'])
								$log_params['comments']=$prod_params['quote_updated_comments'];

							//echo "<pre>";print_r($log_params);exit;

							$log_obj=new Ep_Quote_QuotesLog();
							$log_obj->insertLog($quiteActionId,$log_params);

							//sending email to sales user
							$mail_obj=new Ep_Message_AutoEmails();
							$receiver_id=$quoteDetails[0]['quote_by'];
							$mail_parameters['sales_user']=$quoteDetails[0]['quote_by'];
							$mail_parameters['bo_user']=$this->adminLogin->userId;
							$mail_parameters['bo_user_type']='prod';
							$mail_parameters['quote_title']=$quoteDetails[0]['title'];
							$mail_parameters['followup_link']='/quote/quote-followup?quote_id='.$quoteDetails[0]['identifier'];
							$mail_obj->sendQuotePersonalEmail($receiver_id,134,$mail_parameters);

							
							//sending intimation emails when quote edited
				            $update_comments= $prod_params['quote_updated_comments'];
				            if($update_comments)
							{
								$bo_user_type='prod';				
								$this->sendIntimationEmail($quote_id,$bo_user_type,$update_comments,$newmissionAdded);
								//exit;
							}



							$this->_redirect("/quote/sales-quotes-list?submenuId=ML13-SL2");
						}

				}
			}	
		}		
	}			

	// Save Prod Challange
	function saveProdChallengeAction()
	{
		if($this->_request-> isPost()  && $this->adminLogin->userId)
		{
			$prod_params=$this->_request->getParams();
			
			//echo "<pre>";print_r($_FILES);print_r($prod_params);exit;

			$quote_id=$prod_params['quote_id'];

			if($quote_id)
			{
				$quote_obj=new Ep_Quote_Quotes();
				$version=$quote_obj->getQuoteVersion($quote_id);
	
				//Insert Quote log
				$log_params['quote_id']	= $quote_id;
				$log_params['bo_user']	= $this->adminLogin->userId;					
				$log_params['version']	= $version;
				$log_params['action']	= 'prod_challenged';
							
				$prod_params['prod_timeline']=str_replace("/","-",$prod_params['prod_timeline']);
				$prod_params['prod_timeline']=$prod_params['prod_timeline']." ".$prod_params['prod_time'];
				$update_quote['prod_timeline']=strtotime($prod_params['prod_timeline']);
				$update_quote['prod_challege_comments']=$prod_params['prod_comments'];
				
				$log_params['challenge_time']=dateDiffHours(time(),strtotime($prod_params['prod_timeline']));
				$log_params['comments']=$update_quote['prod_challege_comments'];
				$quiteActionId=3;
				
				$challenge_hours=round($log_params['challenge_time']);
				$update_quote['quote_delivery_hours'] = new Zend_Db_Expr('quote_delivery_hours+'.$challenge_hours);
				
				$log_obj=new Ep_Quote_QuotesLog();
				$log_obj->insertLog($quiteActionId,$log_params);
				$quote_obj->updateQuote($update_quote,$quote_id);
				
				//send notifcation email to sales
				$quoteDetails=$quote_obj->getQuoteDetails($quote_id);

				$mail_obj=new Ep_Message_AutoEmails();
				$receiver_id=$quoteDetails[0]['quote_by'];
				$mail_parameters['sales_user']=$quoteDetails[0]['quote_by'];
				$mail_parameters['bo_user']=$this->adminLogin->userId;
				$mail_parameters['bo_user_comments']=$prod_params['prod_comments'];
				$mail_parameters['challenge_hours']=$challenge_hours;
				
				$mail_obj->sendQuotePersonalEmail($receiver_id,141,$mail_parameters);      
				
				$this->_redirect("/quote/prod-quote-review?quote_id=".$quote_id."&submenuId=ML13-SL2");
			}
		}
	}
	
	//popup to show the auto match missions for a mission
	public function automatchMissionPopupAction()
	{
		$mission_params=$this->_request->getParams();
		$mission_obj=new Ep_Quote_Mission();
		$quoteMissionObj=new Ep_Quote_QuoteMissions();

		$quote_id=$mission_params['quote_id'];
		$mission_id=$mission_params['mission_id'];
		$suggested_mission=$mission_params['suggested_mission'];
		$suggested_status=$mission_params['suggested'];

		$archieve_mission=$suggested_mission;
		//get quotemission details
		$qmission_params['mission_id']=$mission_id;
		$QuotemissionDetails=$quoteMissionObj->getMissionDetails($qmission_params);		

		//getting auto matched quotes
		if($QuotemissionDetails)
		{
			$i=0;//
			foreach ($QuotemissionDetails as $qmission) 
			{
			
				$QuotemissionDetails[$i]['product']=$qmission['product'];
				$QuotemissionDetails[$i]['product_name']=$this->product_array[$qmission['product']];
				$QuotemissionDetails[$i]['language']=$qmission['language_source'];
				$QuotemissionDetails[$i]['language_name']=$this->getLanguageName($qmission['language_source']);
				$QuotemissionDetails[$i]['languagedest']=$qmission['language_dest'];
				$QuotemissionDetails[$i]['languagedest_name']=$this->getLanguageName($qmission['language_dest']);
				$QuotemissionDetails[$i]['producttype']=$qmission['product_type'];
				$QuotemissionDetails[$i]['producttype_name']=$this->producttype_array[$qmission['product_type']];
				$QuotemissionDetails[$i]['nb_words']=$qmission['nb_words'];
				$QuotemissionDetails[$i]['comments']=$qmission['comments'];
				
				if($qmission['related_to'])
				{
					$qmission_params['mission_id']=$qmission['related_to'];					
					$relatedMissionDetails=$quoteMissionObj->getMissionDetails($qmission_params);	
					//echo "<pre>";print_r($relatedMissionDetails);
					$qmission['volume']=$relatedMissionDetails[0]['volume'];
					if(!$qmission['sales_suggested_missions'])
					$qmission['sales_suggested_missions']=$relatedMissionDetails[0]['sales_suggested_missions'];

				}

				$QuotemissionDetails[$i]['volume']=$qmission['volume'];


				$quote_by=$qmission['quote_by'];
				$client_obj=new Ep_Quote_Client();
				$bo_user_details=$client_obj->getQuoteUserDetails($quote_by);
				if($bo_user_details!='NO')
				{
					$QuotemissionDetails[$i]['sales_user_name']=$bo_user_details[0]['first_name'].' '.$bo_user_details[0]['last_name'];					
				}
				

				
				$suggested_mission=$qmission['sales_suggested_missions'];

				$suggested_currency=$qmission['sales_suggested_currency'];

				$i++;
			}

			/*dont change the order of this array*/
			$searchParameters['product']=$QuotemissionDetails[0]['product'];
			$searchParameters['language']=$QuotemissionDetails[0]['language_source'];
			$searchParameters['languagedest']=$QuotemissionDetails[0]['language_dest'];			
			$searchParameters['producttype']=$QuotemissionDetails[0]['product_type'];
			$searchParameters['volume']=$QuotemissionDetails[0]['volume'];
			$searchParameters['nb_words']=$QuotemissionDetails[0]['nb_words'];
			
		}	

		$missionDetails=$mission_obj->getMissionDetails($searchParameters,3);
		if($missionDetails)
		{
			$m=0;
			foreach($missionDetails as $misson)
			{
				$missionDetails[$m]['category_name']=$this->getCategoryName($misson['category']);
				$missionDetails[$m]['product']=$this->product_array[$misson['type']];
				$missionDetails[$m]['language1_name']=$this->getLanguageName($misson['language1']);
				$missionDetails[$m]['producttype']=$this->producttype_array[$misson['type_of_article']];
				
				if($misson['writing_cost_before_signature_currency']!=$QuotemissionDetails[0]['sales_suggested_currency'])
				{
					$conversion=$QuotemissionDetails[0]['conversion'];
					$missionDetails[$m]['writing_cost_before_signature']=($misson['writing_cost_before_signature']*$conversion);
					$missionDetails[$m]['correction_cost_before_signature']=($misson['correction_cost_before_signature']*$conversion);
					$missionDetails[$m]['other_cost_before_signature']=($misson['other_cost_before_signature']*$conversion);
					$missionDetails[$m]['unit_price']=($misson['selling_price']*$conversion);
				}
				else
					$missionDetails[$m]['unit_price']=$misson['selling_price'];

				$missionDetails[$m]['mission_turnover']=($misson['num_of_articles']*$missionDetails[$m]['unit_price'])/1000;
				

				$m++;
			}			
		}		
		$QuotemissionDetails[0]['missionDetails']=$missionDetails;


		$this->_view->quote_missions=$QuotemissionDetails;
		$this->_view->suggested_mission=$suggested_mission;
		$this->_view->suggested_currency=$suggested_currency;
		//echo "<pre>";print_r($QuotemissionDetails);

		$this->render('popup_automatch_missions');
	}

	//assign quote to some other user
	public function assignQuoteAction()
	{
		if($this->_request-> isPost()  && $this->adminLogin->userId)            
        {
			$assign_parameters=$this->_request->getParams();

			$ep_user_id=$assign_parameters['ep_user_id'];
			$quote_by=$this->quote_creation->create_step1['quote_by'];
			if($ep_user_id==$quote_by)
			{
				echo json_encode(array('status'=>'same_user'));
			}
			else
			{
				$this->quote_creation->create_step1['quote_by']=$ep_user_id;
				echo json_encode(array('status'=>'success'));
			}
			exit;
		}	

	}


	//sales team final validation
	public function salesFinalValidationAction()
	{
		$prod_parameters=$this->_request->getParams();

		$quote_id=$prod_parameters['quote_id'];

		$quote_obj=new Ep_Quote_Quotes();		

		if($quote_id)
		{
			//prod details to view in a tab
			$this->_view->prod_view_details=$this->prodViewDetails($quote_id);


			$quoteDetails=$quote_obj->getQuoteDetails($quote_id);
			if($quoteDetails)
			{
				$q=0;
				foreach($quoteDetails as $quote)
				{
					$quoteDetails[$q]['category_name']=trim($this->getCategoryName($quote['category']));
					$quoteDetails[$q]['websites']=explode("|",$quote['websites']);
					
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

							$related_files.='<a href="/quote/download-document?type=quote&index='.$k.'&quote_id='.$quote_id.'">'.$file_name.'</a><br>';
						}

					}

					$quoteDetails[$q]['related_files']=trim($related_files);

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
					if($missonDetails)
					{
						$m=0;
						$mission_length_array=array();
						$prior_length_array=array();
						foreach($missonDetails as $mission)
						{
							$missonDetails[$m]['product_name']=$this->seo_product_array[$mission['product']];			
							$missonDetails[$m]['language_source_name']=$this->getLanguageName($mission['language_source']);
							$missonDetails[$m]['product_type_name']=$this->producttype_array[$mission['product_type']];
							if($mission['language_dest'])
								$missonDetails[$m]['language_dest_name']=$this->getLanguageName($mission['language_dest']);						

							$missonDetails[$m]['comment_time']=time_ago($mission['created_at']);

							if($mission['product']=='seo_audit' || $mission['product']=='smo_audit' )
							{
								if($mission['internal_cost']>0)
								{
									$missonDetails[$m]['internal_cost']=$mission['internal_cost'];
									$audit=$mission['product']=='seo_audit' ?'SEO':'SMO';
									$missonDetails[$m]['internalcost_details']="$audit Audit : ".zero_cut($mission['cost'],2)." &".$quote['sales_suggested_currency'].";<br>";
									$total_internalcost+=$mission['internal_cost'];
								}
								else
								{
									$missonDetails[$m]['internal_cost']=$mission['cost'];
									$audit=$mission['product']=='seo_audit' ?'SEO':'SMO';
									$missonDetails[$m]['internalcost_details']="$audit Audit : ".zero_cut($mission['cost'],2)." &".$quote['sales_suggested_currency'].";<br>";
									$total_internalcost+=$mission['cost'];
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
									$prod_delivery_time=array();
									//$required_writes=1;

									foreach($prodMissionDetails as $prodMission)
									{
										$internalcost=$internalcost+$prodMission['cost'];
										$internalcost_details.=$this->seo_product_array[$prodMission['product']]. " : ".zero_cut($prodMission['cost'],2)." &".$prodMission['currency'].";<br>";

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

										

										$staff_time[]=$staff_mission_stetup;
										$prod_delivery_time[]=$prod_delivery_stetup;

										//getting required writers	
										if($prodMission['product']=='redaction' || (!$required_writes && $prodMission['product']=='autre' ))
										{											
											$required_writes=$prodMission['staff'];
										}

									}
									//required Writres
									$missonDetails[$m]['required_writes']=$required_writes;

									//echo "<pre>";print_r($prodMissionDetails);		
									if($mission['internal_cost']>0)
									{
										$missonDetails[$m]['internal_cost']=$mission['internal_cost'];
										$total_internalcost+=$mission['internal_cost'];
									}
									else						
									{
										$missonDetails[$m]['internal_cost']=$internalcost;
										$total_internalcost+=$internalcost;
									}
									$missonDetails[$m]['internalcost_details']=$internalcost_details;


									//Adding prod staff setup time to mission length
									$prod_team_setup=max($staff_time)+max($prod_delivery_time);

									//$missonDetails[$m]['mission_length']=round($missonDetails[$m]['mission_length']+$prod_team_setup);
									$missonDetails[$m]['mission_length']=round($prod_team_setup);
									
								}
								else if($mission['internal_cost']>0)
								{
									//$missonDetails[$m]['internal_cost']=$mission['internal_cost'];
									//$missonDetails[$m]['internalcost_details'].="Internal cost : ".zero_cut($mission['internal_cost'],2)." &".$quote['sales_suggested_currency'].";<br>";
									$archmission_obj=new Ep_Quote_Mission();
									$archParameters['mission_id']=$mission['sales_suggested_missions'];
									$suggested_mission_details=$archmission_obj->getMissionDetails($archParameters);
									if($suggested_mission_details)
									{										
											
										$nb_words=($mission['nb_words']/$suggested_mission_details[0]['article_length']);
										$redactionCost=$nb_words*($suggested_mission_details[0]['writing_cost_before_signature']);
										$correctionCost=$nb_words*($suggested_mission_details[0]['correction_cost_before_signature']);
										$otherCost=$nb_words*($suggested_mission_details[0]['other_cost_before_signature']);

										$internalcost=($redactionCost+$correctionCost+$otherCost);
										$internalcost=number_format($internalcost,2,'.','');

										$missonDetails[$m]['internal_cost']=$internalcost;
									    $missonDetails[$m]['internalcost_details'].=$this->seo_product_array['redaction']. " : ".zero_cut($redactionCost,2)." &".$quote['sales_suggested_currency'].";<br>";
									    $missonDetails[$m]['internalcost_details'].=$this->seo_product_array['translation']. " : ".zero_cut($correctionCost,2)." &".$quote['sales_suggested_currency'].";<br>";
									    if($otherCost)
									    	$missonDetails[$m]['internalcost_details'].=$this->seo_product_array['autre']. " : ".zero_cut($otherCost,2)." &".$quote['sales_suggested_currency'].";<br>";


									    //pre-fill staff calculations

										//total mission words
										$mission_volume=$mission['volume'];
										$mission_nb_words=$mission['nb_words'];
										$total_mission_words=($mission_volume*$mission_nb_words);
								
										//words that can write per writer with in delivery weeks
										$sales_delivery_time=$quote['sales_delivery_time_option']=='hours' ? ($quote['sales_delivery_time']/24) : $quote['sales_delivery_time'];
										$sales_delivery_week=ceil($sales_delivery_time/7);

										$mission_product=$mission['product_type'];
										if($mission['product_type']=='autre')
												$mission_product='article_seo';
										$articles_perweek=$this->configval['max_writer_'.$mission_product];
										$words_perweek_peruser=$articles_perweek*250;
										$words_peruser_perdelivery=$sales_delivery_week*$words_perweek_peruser;

										//wrting and proofreading staff calculations
										$writing_staff=round($total_mission_words/$words_peruser_perdelivery);
										if(!$writing_staff || $writing_staff <1)
											$writing_staff=1;	

										$missonDetails[$m]['required_writes']=$writing_staff;

									}						

								}
								else
									$missonDetails[$m]['internal_cost']=0;
							}	


							$missonDetails[$m]['unit_price']=number_format(($missonDetails[$m]['internal_cost']/(1-($mission['margin_percentage']/100))),2, '.', '');
							//echo $missonDetails[$m]['unit_price']."--".$missonDetails[$m]['internal_cost']."--".$mission['margin_percentage']."<br>";
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


							//versioning
							//mission versionings if version is gt 1
							if($quote['version']>1)
							{
								$previousVersion=($quote['version']-1);

								$quoteMissionObj=new Ep_Quote_QuoteMissions();
								$previousMissionDetails=$quoteMissionObj->getMissionVersionDetails($mission['identifier'],$previousVersion);
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
									$allVersionMissionDetails=$quoteMissionObj->getMissionVersionDetails($mission['identifier']);
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

										  	$language_versions.="<tr><td>$language</td><td>$created_at</td></tr>";
										  	$product_type_versions.="<tr><td>".$this->producttype_array[$versions['product_type']]."</td><td>$created_at</td></tr>";
										  	$volume_versions.="<tr><td>".$versions['volume']."</td><td>$created_at</td></tr>";
										  	$nb_words_versions.="<tr><td>".$versions['nb_words']."</td><td>$created_at</td></tr>";
										  	$price_versions.="<tr><td>".zero_cut($versions['unit_price'],2)." &". $versions['sales_suggested_currency'].";</td><td>$created_at</td></tr>";

										  	$mission_length_option=$versions['mission_length_option']=='days' ? ' Jours' : ' Hours';

										  	$mission_length_versions.="<tr><td>".$versions['mission_length']." $mission_length_option</td><td>$created_at</td></tr>";

										  	$turnover_versions.="<tr><td>".zero_cut($versions['turnover'],2)." &". $versions['sales_suggested_currency'].";</td><td>$created_at</td></tr>";

										  	$internal_cost_versions.="<tr><td>".zero_cut($versions['internal_cost'],2)." &". $versions['sales_suggested_currency'].";</td><td>$created_at</td></tr>";
										  	
										  	$margin_versions.="<tr><td>".$versions['margin_percentage']."</td><td>$created_at</td></tr>";
										}										
									}


									//checking the version differences
									if($mission['language_source'] !=$previousMissionDetails[0]['language_source'])
									{
										$missonDetails[$m]['language_difference']='yes';
										$missonDetails[$m]['language_versions']=$table_start.$language_versions.$table_end;
									}

									if($mission['language_dest'] !=$previousMissionDetails[0]['language_dest'])
									{
										$missonDetails[$m]['language_difference']='yes';
										$missonDetails[$m]['language_versions']=$table_start.$language_versions.$table_end;
									}

									if($mission['product_type'] !=$previousMissionDetails[0]['product_type'])
									{
										$missonDetails[$m]['product_type_difference']='yes';
										$missonDetails[$m]['product_type_versions']=$table_start.$product_type_versions.$table_end;
									
									}

									if($missonDetails[$m]['turnover'] !=$previousMissionDetails[0]['turnover'])
									{
										$missonDetails[$m]['turnover_difference']='yes';
										$missonDetails[$m]['turnover_versions']=$table_start.$turnover_versions.$table_end;
									}	

									$current_internal_cost=number_format($missonDetails[$m]['internal_cost'],2);
									$prev_internal_cost=number_format($previousMissionDetails[0]['internal_cost'],2);

									if($current_internal_cost != $prev_internal_cost)
									{
										//echo $current_internal_cost."---".$prev_internal_cost."<br>";
										$missonDetails[$m]['internal_cost_difference']='yes';
										$missonDetails[$m]['internal_cost_versions']=$table_start.$internal_cost_versions.$table_end;
									}	

									if($mission['margin_percentage'] !=$previousMissionDetails[0]['margin_percentage'])
									{
										$missonDetails[$m]['margin_difference']='yes';
										$missonDetails[$m]['margin_versions']=$table_start.$margin_versions.$table_end;
									}

									if($mission['volume'] !=$previousMissionDetails[0]['volume'])
									{
										$missonDetails[$m]['volume_difference']='yes';
										$missonDetails[$m]['volume_versions']=$table_start.$volume_versions.$table_end;
									}
									
									if($mission['nb_words'] !=$previousMissionDetails[0]['nb_words'])
									{
										$missonDetails[$m]['nb_words_difference']='yes';
										$missonDetails[$m]['nb_words_versions']=$table_start.$nb_words_versions.$table_end;
									}
									
									//echo $missonDetails[$m]['unit_price']."--".$previousMissionDetails[0]['unit_price']."<br>";
									if($missonDetails[$m]['unit_price'] !=$previousMissionDetails[0]['unit_price'])
									{
										$missonDetails[$m]['unit_price_difference']='yes';
										$missonDetails[$m]['price_versions']=$table_start.$price_versions.$table_end;
									}

									$current_mission_lenght=$mission['mission_length_option']=='hours' ? ($missonDetails[$m]['mission_length']/24) : $missonDetails[$m]['mission_length'];
									$previous_mission_lenght=$previousMissionDetails[0]['mission_length_option']=='hours' ? ($previousMissionDetails[0]['mission_length']/24) : $previousMissionDetails[0]['mission_length'];
									//echo $current_mission_lenght."--".$previous_mission_lenght."<br>";
									if($current_mission_lenght !=$previous_mission_lenght)
									{
										$missonDetails[$m]['mission_length_difference']='yes';	
										$missonDetails[$m]['mission_length_versions']=$table_start.$mission_length_versions.$table_end;
									}



									$missonDetails[$m]['previousMissionDetails']=$previousMissionDetails;
								}	

							}

							//calculating team price
							if(!$missonDetails[$m]['team_fee'])
							{
								$teamPrice=0;							
								$teamPrice=(ceil($missonDetails[$m]['required_writes']/3))*350;								
								$missonDetails[$m]['team_fee']=$teamPrice;
							}
							if(!$missonDetails[$m]['team_packs'])
								$missonDetails[$m]['team_packs']=(ceil($missonDetails[$m]['required_writes']/3));	

							//calculate user turnover for user package
							if(!$missonDetails[$m]['user_fee'])
								$missonDetails[$m]['user_fee']=350;

							$missonDetails[$m]['user_package_turnover']=(($missonDetails[$m]['required_writes']*$missonDetails[$m]['user_fee']));
							$missonDetails[$m]['team_package_turnover']=(($missonDetails[$m]['turnover']+$missonDetails[$m]['team_fee']));

							if($missonDetails[$m]['package']=='team')
								$total_turnover+=($missonDetails[$m]['team_package_turnover']-$missonDetails[$m]['turnover']);
							elseif($missonDetails[$m]['package']=='user')
								$total_turnover+=$missonDetails[$m]['user_package_turnover'];
							//echo $total_turnover;exit;							

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
							
							$techMissionDetails[$t]['internal_cost']=$mission['cost'];
							$techMissionDetails[$t]['unit_price']=number_format(($techMissionDetails[$t]['internal_cost']/(1-($mission['margin_percentage']/100))),2, '.', '');
						    $techMissionDetails[$t]['turnover']=$techMissionDetails[$t]['unit_price']*$mission['volume'];

						    $total_internalcost+=$mission['cost'];
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
								$teamPrice=(ceil($techMissionDetails[$t]['required_writes']/3))*350;
								$techMissionDetails[$t]['team_fee']=$teamPrice;									
							}
							if(!$techMissionDetails[$t]['team_packs'])
								$techMissionDetails[$t]['team_packs']=(ceil($techMissionDetails[$t]['required_writes']/3));	

							//calculate user turnover for user package
							if(!$techMissionDetails[$t]['user_fee'])
								$techMissionDetails[$t]['user_fee']=350;

							$techMissionDetails[$t]['user_package_turnover']=(($techMissionDetails[$t]['required_writes']*$techMissionDetails[$t]['user_fee']));
							$techMissionDetails[$t]['team_package_turnover']=(($techMissionDetails[$t]['turnover']+$techMissionDetails[$t]['team_fee']));
							
							//mission versionings if version is gt 1
							if($quoteDetails[0]['version']>1)
							{
								$previousVersion=($quoteDetails[0]['version']-1);

								$techMissionObj=new Ep_Quote_TechMissions();
								$previousMissionDetails=$techMissionObj->getMissionVersionDetails($mission['identifier'],$quoteDetails[0]['identifier'],$previousVersion);
								
								if($previousMissionDetails)
								{						
									//Get All version details of a mission									
									$allVersionMissionDetails=$techMissionObj->getMissionVersionDetails($mission['identifier'],$quoteDetails[0]['identifier']);
									if($allVersionMissionDetails)
									{
										$table_start='<table class="table quote-history table-striped">';
										$table_end='</table>';								
										$price_versions=$mission_length_versions='';
										$title_versions='';

										foreach($allVersionMissionDetails as $versions)
										{
										 	
										  	
										  	$created_at=date("d/m/Y", strtotime($versions['created_at']));
										  	
										  	$title_versions.="<tr><td>".$versions['title']."</td><td>$created_at</td></tr>";

										  	$price_versions.="<tr><td>".zero_cut($versions['cost'],2)." &". $versions['currency'].";</td><td>$created_at</td></tr>";

										  	$mission_length_option=$versions['delivery_option']=='days' ? ' Jours' : ' Hours';

										  	$mission_length_versions.="<tr><td>".$versions['delivery_time']." $mission_length_option</td><td>$created_at</td></tr>";
										}										
									}


									//checking the version differences
									if($mission['title'] !=$previousMissionDetails[0]['title'])
									{
										$techMissionDetails[$t]['title_difference']='yes';
										$techMissionDetails[$t]['title_versions']=$table_start.$title_versions.$table_end;
									}


									if($mission['cost'] !=$previousMissionDetails[0]['cost'])
									{
										$techMissionDetails[$t]['cost_difference']='yes';
										$techMissionDetails[$t]['price_versions']=$table_start.$price_versions.$table_end;
									}

									$current_mission_lenght=$mission['delivery_option']=='hours' ? ($mission['delivery_time']/24) : $mission['delivery_time'];
									$previous_mission_lenght=$previousMissionDetails[0]['delivery_option']=='hours' ? ($previousMissionDetails[0]['delivery_time']/24) : $previousMissionDetails[0]['delivery_time'];
									if($current_mission_lenght !=$previous_mission_lenght)
									{
										$techMissionDetails[$t]['mission_length_difference']='yes';	
										$techMissionDetails[$t]['mission_length_versions']=$table_start.$mission_length_versions.$table_end;
									}



									$techMissionDetails[$t]['previousMissionDetails']=$previousMissionDetails;
								}	

							}
							$t++;
						}		

						$quoteDetails[$q]['tech_mission_details']=$techMissionDetails;
					}

					//echo "<pre>";print_r($techMissionDetails);exit;


					//total cost details
					$quoteDetails[$q]['total_internalcost']=$total_internalcost;
					$quoteDetails[$q]['total_unitprice']=$total_unitprice;
					$quoteDetails[$q]['total_turnover']=$total_turnover;
					$quoteDetails[$q]['over_all_margin']=number_format(((100-($quoteDetails[$q]['total_internalcost']/$quoteDetails[$q]['total_unitprice'])*100)),2);

					//echo max($mission_length_array)+max($prior_length_array);

					//if(!$quoteDetails[$q]['final_mission_length'])
					//{
						if(count($prior_length_array)>0)
							$quoteDetails[$q]['total_mission_length']=max($mission_length_array)+max($prior_length_array);
						else
							$quoteDetails[$q]['total_mission_length']=max($mission_length_array);
					//}
					//else
					//	$quoteDetails[$q]['total_mission_length']=$quoteDetails[$q]['final_mission_length'];	

					//Quote versioning	
					if($quote['version']>1)
					{
						$previousVersion=($quote['version']-1);

						$quoteObj=new Ep_Quote_Quotes();
						$previousQuoteDetails=$quoteObj->getQuoteVersionDetails($quote['identifier'],$previousVersion);

						if($previousQuoteDetails)
						{
							//Get All Quote version Details
							$allVersionQuoteDetails=$quoteObj->getQuoteVersionDetails($quote['identifier']);							
							if($allVersionQuoteDetails)
							{
								$table_start='<table class="table quote-history table-striped">';
								$table_end='</table>';								
								$final_margin_versions=$final_turnover_versions=$final_mission_length_versions='';

								foreach($allVersionQuoteDetails as $versions)
								{
								 	
								  	$created_at=date("d/m/Y", strtotime($versions['created_at']));								  	

								  	$mission_length_option=$versions['final_mission_length_option']=='days' ? ' Jours' : ' Hours';

								  	$final_mission_length_versions.="<tr><td>".$versions['final_mission_length']." $mission_length_option</td><td>$created_at</td></tr>";

								  	$final_turnover_versions.="<tr><td>".zero_cut($versions['final_turnover'],2)." &". $versions['sales_suggested_currency'].";</td><td>$created_at</td></tr>";								  	
								  	
								  	$final_margin_versions.="<tr><td>".$versions['final_margin']."</td><td>$created_at</td></tr>";
								}

								//echo $quoteDetails[0]['total_turnover']."--".$previousQuoteDetails[0]['final_turnover'];
								if($quoteDetails[0]['total_turnover'] !=$previousQuoteDetails[0]['final_turnover'])
								{
									$quoteDetails[$q]['final_turnover_difference']='yes';
									$quoteDetails[$q]['final_turnover_versions']=$table_start.$final_turnover_versions.$table_end;
								}


								if($quoteDetails[0]['over_all_margin'] !=$previousQuoteDetails[0]['final_margin'])
								{
									$quoteDetails[$q]['final_margin_difference']='yes';
									$quoteDetails[$q]['final_margin_versions']=$table_start.$final_margin_versions.$table_end;
								}

								$current_quote_lenght=$quote['final_mission_length_option']=='hours' ? ($quoteDetails[$q]['total_mission_length']/24) : $quoteDetails[$q]['total_mission_length'];
								$previous_quote_lenght=$previousQuoteDetails[0]['final_mission_length_option']=='hours' ? ($previousQuoteDetails[0]['final_mission_length']/24) : $previousQuoteDetails[0]['final_mission_length'];								
								if($current_quote_lenght !=$previous_quote_lenght)
								{
									$quoteDetails[$q]['final_mission_length_difference']='yes';	
									$quoteDetails[$q]['final_mission_length_versions']=$table_start.$final_mission_length_versions.$table_end;
								}


							}
						}

						//Deleted mission version details
						
						$previousVersion=($quote['version']-1);
						$deletedMissionVersions=$this->deletedMissionVersions($quote['identifier'],$previousVersion);
						if($deletedMissionVersions)
							$quoteDetails[$q]['deletedMissionVersions']=$deletedMissionVersions;

						
						
					}

					$q++;
				}
				//echo "<pre>";print_r($quoteDetails);exit;
				$this->_view->quoteDetails=$quoteDetails;
			}
		}
	
		//echo "<pre>";print_r($quoteDetails);exit;

		if($prod_parameters['ajax']=='yes' && $_SERVER['HTTP_X_REQUESTED_WITH']=='XMLHttpRequest')
		{
			$this->render('popup-sales-final-validation');
		}
		else
			$this->render('sales-final-validation');
	}

	//save final sales validation

	public function saveFinalValidationAction()
	{
		if($this->_request-> isPost()  && $this->adminLogin->userId)            
        {        
        	$finalParameters=$this->_request->getParams();

        	//echo "<pre>";print_r($finalParameters);exit;

        	$total_turnover=0;
        	//get all selected missions
			foreach($_POST as $key => $missions)
			{
			    if (strpos($key, 'unit_price_') === 0)
			    {
			    	$mission_id=str_replace('unit_price_','',$key);

			    	$mission_update['unit_price']=currencyToDecimal($finalParameters['unit_price_'.$mission_id]);			    	
			    	
			    	if($finalParameters['tech_mission_'.$mission_id]==1)
			    	{
			    		$mission_update['cost']=currencyToDecimal($finalParameters['internal_cost_'.$mission_id]);
			    		unset($mission_update['internal_cost']);
			    		unset($mission_update['mission_length']);
			    	}
			    	else
			    	{
			    		$mission_update['internal_cost']=currencyToDecimal($finalParameters['internal_cost_'.$mission_id]);
			    		unset($mission_update['cost']);

			    		if($finalParameters['mission_length_'.$mission_id])
			    			$mission_update['mission_length']=$finalParameters['mission_length_'.$mission_id];

			    	}
			    	
			    	$mission_update['margin_percentage']=currencyToDecimal($finalParameters['margin_percentage_'.$mission_id]);
			    	
			    	//Added w.r.t packags
			    	$mission_update['package']=($finalParameters['package_'.$mission_id]);

			    	if($mission_update['package']=='team')
			    	{
			    		$mission_update['team_fee']=currencyToDecimal($finalParameters['team_fee_'.$mission_id]);
			    		$mission_update['team_packs']=currencyToDecimal($finalParameters['team_packs_'.$mission_id]);
			    		$mission_update['turnover']=currencyToDecimal($finalParameters['turnover_'.$mission_id]);
			    	}

			    	else if($mission_update['package']=='user')
			    	{
			    		$mission_update['user_fee']=currencyToDecimal($finalParameters['user_fee_'.$mission_id]);
			    		$mission_update['turnover']=currencyToDecimal($finalParameters['user_ca_'.$mission_id]);
			    	}
			    	else
			    	{
			    		$mission_update['turnover']=currencyToDecimal($finalParameters['turnover_'.$mission_id]);
			    	}		    	
			    		    	

			    	//echo $mission_id."--".$unit_price."--".$turnover."--".$internal_cost."--".$margin_percentage."<br>";
			    	

			    	//Added w.r.t Tech mission updates
			    	if($finalParameters['tech_mission_'.$mission_id]==1)
			    	{	
			    		$updateMissionObj=new Ep_Quote_TechMissions();
			    		$updateMissionObj->updateTechMission($mission_update,$mission_id);//updating unitprice,internalcost,turnover and margin

			    	}
			    	else
			    	{
			    		$updateMissionObj=new Ep_Quote_QuoteMissions();
			    		$updateMissionObj->updateQuoteMission($mission_update,$mission_id);//updating unitprice,internalcost,turnover and margin
			    	}

			    	if($mission_update['package']=='team')
			    	{
			    		//echo $mission_update['turnover']+$mission_update['team_fee'];exit;
			    		$team_turnover=currencyToDecimal($finalParameters['team_ca_'.$mission_id]);

			    		$total_turnover+=$team_turnover;//$mission_update['turnover']+$mission_update['team_fee'];
			    	}
			    	else
			    		$total_turnover+=$mission_update['turnover'];

			    }
			}

			//updating Quote table with overall margin and total turnover and delviery
			$quote_id=$finalParameters['quote_id'];
			$quote_update['final_turnover']=$total_turnover;
			$quote_update['final_margin']=currencyToDecimal($finalParameters['total_margin']);
			$quote_update['final_mission_length']=$finalParameters['total_mission_length'];
			$quote_update['final_mission_length_option']=$finalParameters['total_time_option'];
			$quote_update['sales_review']='validated';
			$quote_update['prod_extra_launch_days']=$finalParameters['prod_extra_launch_days'] ? $finalParameters['prod_extra_launch_days'] : 0 ;
			$quote_update['package']=$finalParameters['quote_package'];
			$quote_update['updated_at']=date("Y-m-d H:i:s");
			//time to sign the quote
			$quote_update['sign_expire_timeline']=time()+($this->configval['quote_sign_timeline']*60*60);

			//echo "<pre>";print_r($quote_update);exit;

			$quote_obj=new Ep_Quote_Quotes();
			$quote_obj->updateQuote($quote_update,$quote_id);

			//Insert Quote log
			$version=$quote_obj->getQuoteVersion($quote_id);

			$log_params['quote_id']	= $quote_id;
			$log_params['bo_user']	= $this->adminLogin->userId;			
			$log_params['version']	= $version;
			$log_params['action']	= 'sales_validated_ontime';
			$log_params['created_date']	= date("Y-m-d H:i:s");

			$log_obj=new Ep_Quote_QuotesLog();

			$log_obj->insertLog(7,$log_params);


			//Getting SEO/TECH/Prod users

			$techObj=new Ep_Quote_TechMissions();
			$techParameters['quote_id']=$quote_id;
			$techMissionDetails=$techObj->getTechMissionDetails($techParameters,1);
			if($techMissionDetails)
			{
				$bo_users[]=$techMissionDetails[0]['created_by'];
			}

			$seoObj=new Ep_Quote_QuoteMissions();
			$seoParameters['quote_id']=$quote_id;
			$seoParameters['misson_user_type']='seo';
			$seoMissionDetails=$seoObj->getMissionDetails($seoParameters,1);
			if($seoMissionDetails)
			{
				$bo_users[]=$seoMissionDetails[0]['created_by'];
			}


			$prodObj=new Ep_Quote_ProdMissions();			
			$prodMissionDetails=$prodObj->getProdQuoteDetails($quote_id);
			if($prodMissionDetails)
			{
				$bo_users[]=$prodMissionDetails[0]['created_by'];
			}

		
			//sending email to tech/seo/prod
			if(count($bo_users)>0)
			{
				$quoteDetails=$quote_obj->getQuoteDetails($quote_id);
				foreach($bo_users as $user)
				{
					$mail_obj=new Ep_Message_AutoEmails();
					$receiver_id=$user;
					$mail_parameters['bo_user']=$user;
					$mail_parameters['sales_user']=$this->adminLogin->userId;
					$mail_parameters['quote_title']=$quoteDetails[0]['title'];
					$mail_parameters['followup_link']='/quote/quote-followup?quote_id='.$quoteDetails[0]['identifier'];
					$mail_obj->sendQuotePersonalEmail($receiver_id,135,$mail_parameters);        	
	        	}
	        }	

	        if(isset($finalParameters['review_download']))
	        {
	        	//$this->_redirect("/quote/download-quote-xls?quote_id=".$quote_id);	

	        	$this->_redirect("/quote/sales-quotes-list?submenuId=ML13-SL2&active=validated&file_download=yes&quote_id=".$quote_id);

	        }
	        else
	        	$this->_redirect("/quote/sales-quotes-list?submenuId=ML13-SL2&active=validated");

	        $this->_redirect("/quote/sales-quotes-list?submenuId=ML13-SL2&active=validated");
        	//$this->_redirect("/quote/sales-final-validation?quote_id=".$quote_id);
        	
        }	
	}

	//Quote followup page

	public function quoteFollowupAction()
	{
		$quote_parameters=$this->_request->getParams();

		$quote_id=$quote_parameters['quote_id'];

		$quote_obj=new Ep_Quote_Quotes();

		if($quote_id)
		{
			$quoteDetails=$quote_obj->getQuoteDetails($quote_id);
			if($quoteDetails)
			{
				$q=0;
				foreach($quoteDetails as $quote)
				{
					$quoteDetails[$q]['category_name']=trim($this->getCategoryName($quote['category']));
					$quoteDetails[$q]['websites']=explode("|",$quote['websites']);
					$this->_view->closedreason=$this->closedreason[trim($quote['closed_reason'])];
					
					if($quote['tech_timeline'])
					{	
						$quoteDetails[$q]['tech_challenge_time']=strtotime($quote['tech_timeline']);
					}	
					if($quote['seo_timeline'])
					{					
						$quoteDetails[$q]['seo_challenge_time']=strtotime($quote['seo_timeline']);
					}	
					
					$sales_delivery_time = "";
					//Quote count down calculation
					if($quote['quote_delivery_hours'])
						$sales_delivery_time=$quote['quote_delivery_hours']*60*60;	
					else if($quote['sales_delivery_time_option']=='days')
						$sales_delivery_time=$quote['sales_delivery_time']*24*60*60;
					else if($quote['sales_delivery_time_option']=='hours')
						$sales_delivery_time=$quote['sales_delivery_time']*60*60;

					$quoteDetails[$q]['delivery_count_down']=(string)(strtotime($quote['created_at'])+$sales_delivery_time);
					$this->_view->delivery_count_down = $quoteDetails[$q]['delivery_count_down'];
				
					if($quote['documents_path'])
					{
						/* $related_files='';
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
						*/
						$files = array('documents_path'=>$quote['documents_path'],'documents_name'=>$quote['documents_name'],'quote_id'=>$quote_id,'delete'=>false);
						$related_files = $this->getQuoteFiles($files);
					}

					$quoteDetails[$q]['related_files']=$related_files;

					$quoteDetails[$q]['sales_suggested_price_format']=number_format($quote['sales_suggested_price'], 2, ',', ' ');
					

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
					//$searchParameters['quote_id']=$quote_id;
					if($quote['quote_type']=='normal') //added if w.r.t seo only quote
						$searchParameters['misson_user_type']='sales';

					$quoteMission_obj=new Ep_Quote_QuoteMissions();
					$missonDetails=$quoteMission_obj->getMissionDetails($searchParameters);
					//echo "<pre>";print_r($missonDetails);exit;
					if($missonDetails)
					{
						$m=0;
						foreach($missonDetails as $mission)
						{
							$missonDetails[$m]['product_name']=$this->seo_product_array[$mission['product']];			
							$missonDetails[$m]['language_source_name']=$this->getLanguageName($mission['language_source']);
							$missonDetails[$m]['product_type_name']=$this->producttype_array[$mission['product_type']];
							if($mission['language_dest'])
								$missonDetails[$m]['language_dest_name']=$this->getLanguageName($mission['language_dest']);

							$missonDetails[$m]['comment_time']=time_ago($mission['created_at']);

							$missonDetails[$m]['mission_length_option']=($mission['mission_length_option']=='days' ? 'Jours' : 'Hours');



							//mission versionings if version is gt 1
							if($quote['version']>1)
							{
								$previousVersion=($quote['version']-1);

								if($quote['quote_type']=='normal')
									$mission_user_type='sales';
								else
									$mission_user_type='seo';

								$quoteMissionObj=new Ep_Quote_QuoteMissions();
								$previousMissionDetails=$quoteMissionObj->getMissionVersionDetails($mission['identifier'],$previousVersion,$mission_user_type);
								
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
									$allVersionMissionDetails=$quoteMissionObj->getMissionVersionDetails($mission['identifier'],NULL,$mission_user_type);
									if($allVersionMissionDetails)
									{
										$table_start='<table class="table quote-history table-striped">';
										$table_end='</table>';
										$language_versions=$product_type_versions=$volume_versions=$nb_words_versions='';
										$price_versions=$mission_length_versions='';

										foreach($allVersionMissionDetails as $versions)
										{
										 	if($versions['product']=='translation')
										  		$language= $this->getLanguageName($versions['language_source'])." > ".$this->getLanguageName($vmission['language_dest']);
										  	else
										  		$language= $this->getLanguageName($versions['language_source']);
										  	
										  	$created_at=date("d/m/Y", strtotime($versions['created_at']));

										  	$language_versions.="<tr><td>$language</td><td>$created_at</td></tr>";
										  	$product_type_versions.="<tr><td>".$this->producttype_array[$versions['product_type']]."</td><td>$created_at</td></tr>";
										  	$volume_versions.="<tr><td>".$versions['volume']."</td><td>$created_at</td></tr>";
										  	$nb_words_versions.="<tr><td>".$versions['nb_words']."</td><td>$created_at</td></tr>";
										  	$price_versions.="<tr><td>".zero_cut($versions['unit_price'],2)." &". $versions['sales_suggested_currency'].";</td><td>$created_at</td></tr>";

										  	$mission_length_option=$versions['mission_length_option']=='days' ? ' Jours' : ' Hours';

										  	$mission_length_versions.="<tr><td>".$versions['mission_length']." $mission_length_option</td><td>$created_at</td></tr>";
										}										
									}


									//checking the version differences
									if($mission['language_source'] !=$previousMissionDetails[0]['language_source'])
									{
										$missonDetails[$m]['language_difference']='yes';
										$missonDetails[$m]['language_versions']=$table_start.$language_versions.$table_end;
									}

									if($mission['language_dest'] !=$previousMissionDetails[0]['language_dest'])
									{
										$missonDetails[$m]['language_difference']='yes';
										$missonDetails[$m]['language_versions']=$table_start.$language_versions.$table_end;
									}

									if($mission['product_type'] !=$previousMissionDetails[0]['product_type'])
									{
										$missonDetails[$m]['product_type_difference']='yes';
										$missonDetails[$m]['product_type_versions']=$table_start.$product_type_versions.$table_end;
									
									}

									if($mission['volume'] !=$previousMissionDetails[0]['volume'])
									{
										$missonDetails[$m]['volume_difference']='yes';
										$missonDetails[$m]['volume_versions']=$table_start.$volume_versions.$table_end;
									}
									
									if($mission['nb_words'] !=$previousMissionDetails[0]['nb_words'])
									{
										$missonDetails[$m]['nb_words_difference']='yes';
										$missonDetails[$m]['nb_words_versions']=$table_start.$nb_words_versions.$table_end;
									}
									
									if($mission['unit_price'] !=$previousMissionDetails[0]['unit_price'])
									{
										$missonDetails[$m]['unit_price_difference']='yes';
										$missonDetails[$m]['price_versions']=$table_start.$price_versions.$table_end;
									}

									$current_mission_lenght=$mission['mission_length_option']=='hours' ? ($mission['mission_length']/24) : $mission['mission_length'];
									$previous_mission_lenght=$previousMissionDetails[0]['mission_length_option']=='hours' ? ($previousMissionDetails[0]['mission_length']/24) : $previousMissionDetails[0]['mission_length'];
									if($current_mission_lenght !=$previous_mission_lenght)
									{
										$missonDetails[$m]['mission_length_difference']='yes';	
										$missonDetails[$m]['mission_length_versions']=$table_start.$mission_length_versions.$table_end;
									}



									$missonDetails[$m]['previousMissionDetails']=$previousMissionDetails;									
								}	

							}



							$m++;
						}	
						//echo "<pre>";print_r($missonDetails);exit;
						$quoteDetails[$q]['mission_details']=$missonDetails;
					}	

					if($quote['quote_type']=='only_tech')
					{
						//getting tech mission details
						$tech_obj=new Ep_Quote_TechMissions();
						$searchParameters['quote_id']=$quote_id;
						$techMissionDetails=$tech_obj->getTechMissionDetails($searchParameters);
						if($techMissionDetails)
						{
							$t=0;
							foreach($techMissionDetails as $mission)
							{
								//mission versionings if version is gt 1
								if($quoteDetails[0]['version']>1)
								{
									$previousVersion=($quoteDetails[0]['version']-1);

									$techMissionObj=new Ep_Quote_TechMissions();
									$previousMissionDetails=$techMissionObj->getMissionVersionDetails($mission['identifier'],$quoteDetails[0]['identifier'],$previousVersion);
									
									if($previousMissionDetails)
									{						
										//Get All version details of a mission									
										$allVersionMissionDetails=$techMissionObj->getMissionVersionDetails($mission['identifier'],$quoteDetails[0]['identifier']);
										if($allVersionMissionDetails)
										{
											$table_start='<table class="table quote-history table-striped">';
											$table_end='</table>';								
											$price_versions=$mission_length_versions='';
											$title_versions='';

											foreach($allVersionMissionDetails as $versions)
											{
											 	
											  	
											  	$created_at=date("d/m/Y", strtotime($versions['created_at']));
											  	
											  	$title_versions.="<tr><td>".$versions['title']."</td><td>$created_at</td></tr>";

											  	$price_versions.="<tr><td>".zero_cut($versions['cost'],2)." &". $versions['currency'].";</td><td>$created_at</td></tr>";

											  	$mission_length_option=$versions['delivery_option']=='days' ? ' Jours' : ' Hours';

											  	$mission_length_versions.="<tr><td>".$versions['delivery_time']." $mission_length_option</td><td>$created_at</td></tr>";
											}										
										}


										//checking the version differences
										if($mission['title'] !=$previousMissionDetails[0]['title'])
										{
											$techMissionDetails[$t]['title_difference']='yes';
											$techMissionDetails[$t]['title_versions']=$table_start.$title_versions.$table_end;
										}


										if($mission['cost'] !=$previousMissionDetails[0]['cost'])
										{
											$techMissionDetails[$t]['cost_difference']='yes';
											$techMissionDetails[$t]['price_versions']=$table_start.$price_versions.$table_end;
										}

										$current_mission_lenght=$mission['delivery_option']=='hours' ? ($mission['delivery_time']/24) : $mission['delivery_time'];
										$previous_mission_lenght=$previousMissionDetails[0]['delivery_option']=='hours' ? ($previousMissionDetails[0]['delivery_time']/24) : $previousMissionDetails[0]['delivery_time'];
										if($current_mission_lenght !=$previous_mission_lenght)
										{
											$techMissionDetails[$t]['mission_length_difference']='yes';	
											$techMissionDetails[$t]['mission_length_versions']=$table_start.$mission_length_versions.$table_end;
										}



										$techMissionDetails[$t]['previousMissionDetails']=$previousMissionDetails;
									}	

								}
								$techMissionDetails[$t]['files'] = "";
								if($mission['documents_path'])
								{
									/* $exploded_file_paths = explode("|",$mission['documents_path']);
									$exploded_file_names = explode("|",$mission['documents_name']);
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
												$files .= '<div class="topset2"><a href="/quote/download-document?type=tech_mission&mission_id='.$mission['identifier'].'&index='.$k.'">'.$fname.'</a><span class="deletetech" rel="'.$k.'_'.$mission['identifier'].'"> <i class="splashy-error_x"></i></span></div>';
												
										}
										$k++;
									} */
									$filesarray = array('documents_path'=>$mission['documents_path'],'documents_name'=>$mission['documents_name'],'id'=>$mission['identifier'],'delete'=>true);
									$files = $this->getTechFiles($filesarray);
									$techMissionDetails[$t]['files'] = $files;
								}
							
								$t++;
							}		
								
							$quoteDetails[$q]['techMissionDetails']=$techMissionDetails;
						}
					}	

					//getting version
					$version=$quote['version'];
					if($quote['version']>1)
					{
						$previousVersion=($quote['version']-1);
						$deletedMissionVersions=$this->deletedMissionVersions($quote['identifier'],$previousVersion,'sales');
						if($deletedMissionVersions)
							$quoteDetails[$q]['deletedMissionVersions']=$deletedMissionVersions;
					}				

					$q++;

				}

				//Quote log details
				$log_obj=new Ep_Quote_QuotesLog();
				$log_details=$log_obj->getQuotesLog($quote_id);
				if($log_details)
				{
					foreach($log_details as $k=>$log)
					{						
						$log_details[$k]['time_ago']=time_ago_quote($log['action_at']);
					}
				}
				$this->_view->log_details=$log_details;
				//echo "<pre>";print_r($log_details);exit;

				//getting turnover details
				$quoteMissionObj=new Ep_Quote_QuoteMissions();
				$techMissionObj=new Ep_Quote_TechMissions();
				$prodMissionObj=new Ep_Quote_prodMissions();
				$seo_turnover=$tech_turnover=$prod_turnover=0;

				//seo user
				$seo_details=$log_obj->getBoMissionUserDetails($quote_id,'seo');
				if($seo_details)
					$this->_view->seo_user=$seo_details[0]['first_name']." ".$seo_details[0]['last_name'];

				//tech user
				$tech_details=$log_obj->getBoMissionUserDetails($quote_id,'tech');
				if($tech_details)
					$this->_view->tech_user=$tech_details[0]['first_name']." ".$tech_details[0]['last_name'];

				//prod user
				$prod_details=$log_obj->getBoMissionUserDetails($quote_id,'prod');
				if($prod_details)
					$this->_view->prod_user=$prod_details[0]['first_name']." ".$prod_details[0]['last_name'];
				
				

				$this->_view->seo_turnover=$seo_turnover=$quoteMissionObj->seoTurnover($quote_id);
				$this->_view->tech_turnover=$tech_turnover=$techMissionObj->techTurnover($quote_id);
				$this->_view->prod_turnover=$prod_turnover=$prodMissionObj->prodTurnover($quote_id);

				
				$sales_suggested_turnover=$quoteDetails[0]['sales_suggested_price'];
				
				//if($this->adminLogin->type=='superadmin' || $this->adminLogin->type=='salesuser')
				//{
					//echo $sales_suggested_turnover."--".$seo_turnover."--".$tech_turnover."--".$prod_turnover."--total".($sales_suggested_turnover+$seo_turnover+$tech_turnover+$prod_turnover);exit;
					if($quoteDetails[0]['final_turnover']>0)
					{
						$total_turnover=$quoteDetails[0]['final_turnover'];
					}
					else
					{
						$total_turnover=($sales_suggested_turnover+$seo_turnover+$tech_turnover+$prod_turnover);
					}
				/*}				
				else if($this->adminLogin->type=='seouser' )	
					$total_turnover=$sales_suggested_turnover+$seo_turnover;
				else if($this->adminLogin->type=='techuser' )	
					$total_turnover=$sales_suggested_turnover+$tech_turnover;
				else if($this->adminLogin->type=='produser' )	
					$total_turnover=$sales_suggested_turnover+$prod_turnover;*/
				
				
				$this->_view->total_turnover=$total_turnover;
				//echo $total_turnover."--".$sales_suggested_turnover."--".$seo_turnover."--".$tech_turnover."--".$prod_turnover."--total".($sales_suggested_turnover+$seo_turnover+$tech_turnover+$prod_turnover);exit;
				
				if($quoteDetails[0]['final_margin']>0)
				{
					if($quoteDetails[0]['sales_suggested_price'] > $total_turnover)
					{
						$this->_view->precentage_change=$precentage_change=round((($total_turnover/$sales_suggested_turnover)*100)-100,2);
					}
					else
						$this->_view->precentage_change=$precentage_change=$quoteDetails[0]['final_margin'];

				}
				else	
					$this->_view->precentage_change=$precentage_change=round(100-(($sales_suggested_turnover/$total_turnover)*100),2);
			}
			else
				$this->_redirect("/quote/sales-quotes-list?submenuId=ML13-SL2");	

		}
		$this->_view->quoteDetailsFinal=$quoteDetails;
		//echo "<pre>";print_r($this->_view->quoteDetails);exit;

		//prod details to view in a tab
		$this->_view->prod_view_details=$this->prodViewDetails($quote_id);

		$this->render("quote-follow-up");
	}

	//followup mission details popup
	public function missionFollowupDetailsAction()
	{
		if($_SERVER['HTTP_X_REQUESTED_WITH']=='XMLHttpRequest')
		{
			$missionParams=$this->_request->getParams();
			$quote_id=$missionParams['quote_id'];
			$mission_type=$missionParams['type'];
			$request_verion = $missionParams['version'];
			
			$quote_obj = new Ep_Quote_Quotes();
			$res = $quote_obj->getQuoteDetails($quote_id);
			$current_version = $res[0]['version'];
			
			if($quote_id && $mission_type)
			{
				if($mission_type=='seo')
				{
					//getting seo mission details			
					$searchParameters['quote_id']=$quote_id;
					$searchParameters['misson_user_type']='seo';
					$quoteMission_obj=new Ep_Quote_QuoteMissions();
					if($current_version==$request_verion)
					$seoMissionDetails=$quoteMission_obj->getMissionDetails($searchParameters);
					else
					$seoMissionDetails=$quoteMission_obj->getQuoteMissionVersionDetails($quote_id,$request_verion,'seo');
					if($seoMissionDetails)
					{
						$s=0;
						foreach($seoMissionDetails as $mission)
						{
							
							$seoMissionDetails[$s]['product_name'] = $this->seo_product_array[$mission['product']];

							$seoMissionDetails[$s]['language_source_name'] = $this->getLanguageName($mission['language_source']);
							$seoMissionDetails[$s]['language_dest_name'] = $this->getLanguageName($mission['language_dest']);
							$seoMissionDetails[$s]['product_type_name']=$this->producttype_array[$mission['product_type']];

							$client_obj=new Ep_Quote_Client();
							$bo_user_details=$client_obj->getQuoteUserDetails($mission['created_by']);
							$seoMissionDetails[$s]['seo_user_name']=$bo_user_details[0]['first_name'].' '.$bo_user_details[0]['last_name'];

							$seoMissionDetails[$s]['comment_time']=time_ago($mission['created_at']);


							$seoMissionDetails[$s]['files'] = false;
							//$seoMissionDetails[$s]['filenames'] = array();
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
								$filesarray = array('documents_path'=>$mission['documents_path'],'documents_name'=>$mission['documents_name'],'id'=>$mission['identifier'],'delete'=>false);
								$seoMissionDetails[$s]['files'] = $this->getSeoFiles($filesarray);
							}
							$s++;

						}	
						$this->_view->seoMissionDetails=$seoMissionDetails;
						$this->_view->mission_type='seo';
					}
				}
				elseif($mission_type=='tech')
				{
					//getting tech mission details
					$tech_obj=new Ep_Quote_TechMissions();
					$searchParameters['quote_id']=$quote_id;
					if($current_version==$request_verion)
					$techMissionDetails=$tech_obj->getTechMissionDetails($searchParameters);
					else
					$techMissionDetails=$tech_obj->getQuoteVersionDetails($quote_id,$request_verion);
					if($techMissionDetails)
					{
						$t=0;
						foreach($techMissionDetails as $mission)
						{
							$client_obj=new Ep_Quote_Client();
							$bo_user_details=$client_obj->getQuoteUserDetails($mission['created_by']);
							$techMissionDetails[$t]['tech_user_name']=$bo_user_details[0]['first_name'].' '.$bo_user_details[0]['last_name'];

							$techMissionDetails[$t]['comment_time']=time_ago($mission['created_at']);
							
							$techMissionDetails[$t]['files'] = "";
							if($mission['documents_path'])
							{
								/* $exploded_file_paths = explode("|",$mission['documents_path']);
								$exploded_file_names = explode("|",$mission['documents_name']);
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
											$files .= '<div class="topset2"><a href="/quote/download-document?type=tech_mission&mission_id='.$mission['identifier'].'&index='.$k.'">'.$fname.'</a></div>';
											
									}
									$k++;
								} */
								$filesarray = array('documents_path'=>$mission['documents_path'],'documents_name'=>$mission['documents_name'],'id'=>$mission['identifier'],'delete'=>false);
								$files = $this->getTechFiles($filesarray);
								$techMissionDetails[$t]['files'] = $files;
							}
							
							
							$t++;
						}				
						
						$this->_view->techMissionDetails=$techMissionDetails;
						$this->_view->mission_type='tech';
					}
				}
				elseif($mission_type=='prod')
				{
					//getting mission details
					$searchParameters['quote_id']=$quote_id;
					$searchParameters['misson_user_type']='sales';
					$quoteMission_obj=new Ep_Quote_QuoteMissions();
					if($current_version==$request_verion)
					$missonDetails=$quoteMission_obj->getMissionDetails($searchParameters);
					else
					$missonDetails=$quoteMission_obj->getQuoteMissionVersionDetails($quote_id,$request_verion,'sales');
					if($missonDetails)
					{
						$m=0;
						foreach($missonDetails as $mission)
						{
							$missonDetails[$m]['product_name']=$this->product_array[$mission['product']];			
							$missonDetails[$m]['language_source_name']=$this->getLanguageName($mission['language_source']);
							$missonDetails[$m]['product_type_name']=$this->producttype_array[$mission['product_type']];
							if($mission['language_dest'])
								$missonDetails[$m]['language_dest_name']=$this->getLanguageName($mission['language_dest']);

							$quoteDetails[$q]['missions_list'][$mission['identifier']]='Mission '.($m+1).' - '.$missonDetails[$m]['product_name'];

							$missonDetails[$m]['comment_time']=time_ago($mission['created_at']);


							//Get seo missions related to a mission
							$searchParameters['quote_id']=$quote_id;
							$searchParameters['misson_user_type']='seo';
							$searchParameters['related_to']=$mission['identifier'];
							$searchParameters['product']=$mission['product'];
							//echo "<pre>";print_r($searchParameters);
							$quoteMission_obj=new Ep_Quote_QuoteMissions();
							
							if($current_version==$request_verion)
								$seoMissonDetails=$quoteMission_obj->getMissionDetails($searchParameters);
							else
								$seoMissonDetails=$quoteMission_obj->getQuoteMissionVersionDetails($quote_id,$request_verion,'seo',true);
							
							//echo "<pre>";print_r($seoMissonDetails);exit;
							if($seoMissonDetails)
							{
								$s=0;
								foreach($seoMissonDetails as $smission)
								{									
									$client_obj=new Ep_Quote_Client();
									$bo_user_details=$client_obj->getQuoteUserDetails($smission['created_by']);
									$seoMissonDetails[$s]['seo_user_name']=$bo_user_details[0]['first_name'].' '.$bo_user_details[0]['last_name'];

									$seoMissonDetails[$s]['comment_time']=time_ago($smission['created_at']);

									$prodMissionObj=new Ep_Quote_ProdMissions();

									$searchParameters['quote_mission_id']=$smission['identifier'];
									if($current_version==$request_verion)
									$prodMissionDetails=$prodMissionObj->getProdMissionDetails($searchParameters);
									else
									$prodMissionDetails=$prodMissionObj->getMissionVersionDetails($smission['identifier'],$request_verion);
									//echo "<pre>";print_r($prodMissionDetails);exit;

									if($prodMissionDetails)
									{
										if($prodMissionDetails)
										{
											foreach($prodMissionDetails as $key=>$details)
											{
												$client_obj=new Ep_Quote_Client();
												$bo_user_details=$client_obj->getQuoteUserDetails($details['created_by']);
												$prodMissionDetails[$key]['prod_user_name']=$bo_user_details[0]['first_name'].' '.$bo_user_details[0]['last_name'];

												$prodMissionDetails[$key]['comment_time']=time_ago($details['created_at']);
											}

											$seoMissonDetails[$s]['prod_mission_details']=$prodMissionDetails;
										}										
									}										

									$s++;	
								}

								$missonDetails[$m]['seoMissions']=$seoMissonDetails;
							}
							//echo "<pre>";print_r($missonDetails);exit;

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
						//echo "<pre>";print_r($this->_view->prodMissionDetails);

					}
				}
							

				$this->render("mission-followup-details");				
			}
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
	/** To delete the Documents through Ajax **/

	function deleteDocumentAction()
	{
		if($this->_request->isPost() && $_SERVER['HTTP_X_REQUESTED_WITH']=='XMLHttpRequest')
		{
			$parmas = $this->_request->getParams();
			$explode_identifier = explode("_",$parmas['identifier']);
			$offset = $explode_identifier[0];
			$identifier = $explode_identifier[1];
			$quoteMission_obj=new Ep_Quote_QuoteMissions();
			$result = $quoteMission_obj->getQuoteMission($identifier);
			$documents_paths = explode("|",$result[0]['documents_path']);
			$documents_names = explode("|",$result[0]['documents_name']);

			unlink($this->mission_documents_path.$documents_paths[$offset]);

			unset($documents_paths[$offset]);
			unset($documents_names[$offset]);

			$data['documents_path']	= implode("|",$documents_paths);
			$data['documents_name']	= implode("|",$documents_names);
			$quoteMission_obj->updateQuoteMission($data,$identifier);

			$documents_paths = array_filter(array_values($documents_paths));
			$documents_names = array_values($documents_names);


			$files = "<table class='table'>";

			if($parmas['assignmission']):
			$k=0;
			$zip = "";
			if(count($documents_paths))
			$zip = '<tr><th colspan="5"><a href="/quote/download-document?type=seo_mission&index=-1&mission_id='.$identifier.'" class="btn btn-small pull-right">Download Zip</a></th></tr>';
		
			$files = '<table class="table">'.$zip;
			foreach($documents_paths as $row)
			{
				$file_path=$this->mission_documents_path.$row;
				if(file_exists($this->mission_documents_path.$documents_paths[$k]) && !is_dir($this->mission_documents_path.$documents_paths[$k]))
				{
                    $fname = $documents_names[$k];
					if($fname=="")
						$fname = basename($row);
					$ofilename = pathinfo($file_path);
					$files .= '<tr><td width="30%">'.utf8_encode($fname).'</td><td width="35%">'.utf8_encode($ofilename['basename']).'</td><td width="20%">'.formatSizeUnits(filesize($file_path)).'</td><td align="center" width="15%"><a href="/quote/download-document?type=seo_mission&mission_id='.$identifier.'&index='.$k.'"><i style="margin-right:5px" class="splashy-download"></i></a><span class="delete" rel="'.$k.'_'.$identifier.'"> <i class="icon-adt_trash"></i></span><td></tr>';

				}
				$k++;
			}
			else:
			$k=0;
			foreach($documents_paths as $row)
			{
				if(file_exists($this->mission_documents_path.$documents_paths[$k]) && !is_dir($this->mission_documents_path.$documents_paths[$k]))
				{
                    $fname = $documents_names[$k];
					if($fname=="")
						$fname = basename($row);
					$files .= '<div class="topset2"><a href="/quote/download-document?type=seo_mission&mission_id='.$identifier.'&index='.$k.'">'.utf8_encode($fname).'</a><span class="delete" rel="'.$k.'_'.$identifier.'"> <i class="splashy-error_x"></i></span></div>';
					
				}
				$k++;
			}	
			endif;
			$files .= '</table>';
			echo $files;
		}
	}

	function deleteDocumentTechAction()
	{
		if($this->_request->isPost() && $_SERVER['HTTP_X_REQUESTED_WITH']=='XMLHttpRequest')
		{
			$parmas = $this->_request->getParams();
			$explode_identifier = explode("_",$parmas['identifier']);
			$offset = $explode_identifier[0];
			$identifier = $explode_identifier[1];
			$tech_obj=new Ep_Quote_TechMissions();
			$result = $tech_obj->getTechMissionDetails(array('identifier'=>$identifier));
			$documents_paths = explode("|",$result[0]['documents_path']);
			$documents_names = explode("|",$result[0]['documents_name']);

			unlink($this->mission_documents_path.$documents_paths[$offset]);

			unset($documents_paths[$offset]);
			unset($documents_names[$offset]);

			$data['documents_path']	= implode("|",$documents_paths);
			$data['documents_name']	= implode("|",$documents_names);
			$tech_obj->updateTechMission($data,$identifier);
			$documents_paths = array_filter(array_values($documents_paths));
			$documents_names = array_values($documents_names);
			
			$zip = "";
			if(count($documents_paths))
				$zip = '<tr><th colspan="5"><a href="/quote/download-document?type=tech_mission&index=-1&mission_id='.$identifier.'" class="btn btn-small pull-right">Download Zip</a></th></tr>';
			$files = '<table class="table">
					'.$zip;
			$k=0;
			foreach($documents_paths as $row)
			{
				$file_path=$this->mission_documents_path.$row;
				if(file_exists($file_path) && !is_dir($file_path))
				{
                    $fname = $documents_names[$k];
					if($fname=="")
						$fname = basename($row);
					$ofilename = pathinfo($file_path);
					//$files .= '<div class="topset2"><a href="/quote/download-document?type=tech_mission&mission_id='.$identifier.'&index='.$k.'">'.$fname.'</a><span class="deletetech" rel="'.$k.'_'.$identifier.'"> <i class="splashy-error_x"></i></span></div>';
					$files .= '<tr><td width="30%">'.$fname.'</td><td width="35%">'.substr($ofilename['filename'],0,-3).".".$ofilename['extension'].'</td><td width="20%">'.formatSizeUnits(filesize($file_path)).'</td><td>Tech</td><td align="center" width="15%"><a href="/quote/download-document?type=tech_mission&mission_id='.$identifier.'&index='.$k.'"><i style="margin-right:5px" class="splashy-download"></i></a><span class="deletetech" rel="'.$k.'_'.$identifier.'"> <i class="icon-adt_trash"></i></span></td></tr>';	
				}
				$k++;
			}	
			$files .='</table';
			echo $files;
		}
	}
	
	
	function deleteDocumentQuoteAction()
	{
		if($this->_request->isPost() && $_SERVER['HTTP_X_REQUESTED_WITH']=='XMLHttpRequest')
		{
			$parmas = $this->_request->getParams();
			$explode_identifier = explode("_",$parmas['identifier']);
			$offset = $explode_identifier[0];
			$identifier = $explode_identifier[1];
			$quoteObj=new Ep_Quote_Quotes();
			$result=$quoteObj->getQuoteDetails($identifier);
			$documents_paths = explode("|",$result[0]['documents_path']);
			$documents_names = explode("|",$result[0]['documents_name']);

			unlink($this->quote_documents_path.$documents_paths[$offset]);
			unset($documents_paths[$offset]);
			unset($documents_names[$offset]);
			$data['documents_path']	= implode("|",$documents_paths);
			$data['documents_name']	= implode("|",$documents_names);
			$quoteObj->updateQuote($data,$identifier);

			$documents_paths = array_values($documents_paths);
			$documents_names = array_values($documents_names);

			$files = "";

			$k=0;
			foreach($documents_paths as $row)
			{
				if(file_exists($this->quote_documents_path.$documents_paths[$k]) && !is_dir($this->quote_documents_path.$documents_paths[$k]))
				{
					$fname = $documents_names[$k];
					if($fname=="")
						$fname = basename($row);
					$files .= '<div class="topset2"><a href="/quote/download-document?type=quote&quote_id='.$identifier.'&index='.$k.'">'.$fname.'</a><span class="delete" rel="'.$k.'_'.$identifier.'"> <i class="splashy-error_x"></i></span></div>';
				}
				$k++;
			}
			$this->quote_creation->send_quote['documents'] = $files;
			echo $files;
		}
	}
	//download quote and mission documents
	function downloadDocumentAction()
	{
		$request = $this->_request->getParams();
		$this->_redirect("/BO/download-quote.php?type=".$request['type']."&mission_id=".$request['mission_id']."&index=".$request['index']."&quote_id=".$request['quote_id']);
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
			$this->_view->closedreasons = $this->closedreason;
			$this->render('close-quote');
			endif;
		}
	}
	
	//close Quote
	function closeQuoteAction()
	{
		if($this->_request->isPost())
		{
			$request = $this->_request->getParams();
			$quote_obj=new Ep_Quote_Quotes();
			$quote_id = $request['quote_id'];
			$quoteDetails=$quote_obj->getQuoteDetails($quote_id);
			if($quoteDetails)
			{
				if($quoteDetails[0]['sales_review']=="validated")
				{
					$quote_obj->updateQuote(array('sales_review'=>'closed','closed_reason'=>$request['reason'],'closed_comments'=>$request['closetxt']),$quote_id);
					$log_params['quote_id']	= $quote_id;
					$log_params['bo_user']	= $this->adminLogin->userId;			
					$log_params['version']	= $quoteDetails[0]['version'];
					$log_params['action']	= 'sales_closed';
					$log_params['comments']	= $request['closetxt'];
					$log_params['created_date']	= date("Y-m-d H:i:s");

					$log_obj=new Ep_Quote_QuotesLog();					

					$log_obj->insertLog(8,$log_params);
					
					//echo "<pre>";print_r($log_params);exit;
										
					$this->_redirect("/quote/sales-quotes-list?submenuId=ML13-SL2&active=closed");
				}
			}
		}
		$this->_redirect("/quote/sales-quotes-list?submenuId=ML13-SL2");
	}

	function signQuoteViewAction()
	{
		$request = $this->_request->getParams();
		if($request['qid'])
		{
			$this->_view->qid = $request['qid'];
			$quote_obj=new Ep_Quote_Quotes();
			$quoteDetails=$quote_obj->getQuoteDetails($request['qid']);
			if($quoteDetails):
			$this->_view->siret = $quoteDetails[0]['siret'];
			$this->_view->ca_number = $quoteDetails[0]['ca_number'];
			$this->render('sign-quote');
			endif;
		}
	}
	
	function signQuoteAction()
	{
		if($this->_request->isPost())
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
					
					$client = array('siret'=>$request['siret'],'ca_number'=>$request['ca_number']);
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
					
					//echo "<pre>";print_r($log_params);exit;
										
					$this->_redirect("/quote/sales-quotes-list?submenuId=ML13-SL2&active=signed");
				}
			}
		}
		$this->_redirect("/quote/sales-quotes-list?submenuId=ML13-SL2");
	}
	
	// To set Boot Customer Date through Ajax
	function bootCustomerAction()
	{
		if($this->_request->isPost() && $_SERVER['HTTP_X_REQUESTED_WITH']=='XMLHttpRequest')
		{
			$request = $this->_request->getParams();
			$quote_obj = new Ep_Quote_Quotes();
			$quote_obj->updateQuote(array('boot_customer'=>date('Y-m-d')),$request['qid']);
			echo 'Client relanc&eacute; le '.date('d/m/Y');
		}	
	}
	
	//To check if other Quote is opened or not 
	function checkEditQuoteAction()
	{	
		if($this->_request->isPost() && $_SERVER['HTTP_X_REQUESTED_WITH']=='XMLHttpRequest')
		{
			$request = $this->_request->getParams();
			$quote_id = $request['qid'];
			
			if($this->quote_creation->custom['quote_id']=="" || $quote_id==$this->quote_creation->custom['quote_id'])
			echo false;
			else
			echo true;
		}
	}
	
	// To delete the Quote by Superadmin
	function deleteQuoteAction()
	{
		if($this->_view->user_type == 'superadmin')
		{
			$request = $this->_request->getParams();
			$quote_id = $request['quote_id'];
			$quote_obj = new Ep_Quote_Quotes();
			$update = array('sales_review'=>'deleted','deleted_at'=>date('Y-m-d H:i:s'));
			$quote_obj->updateQuote($update,$quote_id);
			$this->_redirect('/quote/sales-quotes-list?submenuId=ML13-SL2&active=deleted');
		}
		else
		$this->_redirect('/quote/sales-quotes-list?submenuId=ML13-SL2');
	}
	
	
	function deleteOtherAction()
	{
		if($this->_request->isPost() && $_SERVER['HTTP_X_REQUESTED_WITH']=='XMLHttpRequest')
		{
			$request = $this->_request->getParams();
			$quote_obj=new Ep_Quote_ProdMissions();
			if($request['id'])
			$quoteDetails=$quote_obj->deleteProdMission($request['id']);
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


	//send intimation email seo/sales/tech/prod when edit the quote
	public function sendIntimationEmail($quote_id,$bo_user_type,$comments,$prod_only=NULL)
	{

		//Getting SEO/TECH/Prod users

			$salesObj=new Ep_Quote_Quotes();
			$QuoteDetails=$salesObj->getQuoteDetails($quote_id);
			if($QuoteDetails)
			{
				$sales_user=$QuoteDetails[0]['created_by'];
				$quote_title=$QuoteDetails[0]['title'];
			}		


			$techObj=new Ep_Quote_TechMissions();
			$techParameters['quote_id']=$quote_id;
			$techMissionDetails=$techObj->getTechMissionDetails($techParameters,1);
			if($techMissionDetails)
			{
				$tech_user=$techMissionDetails[0]['created_by'];
			}

			$seoObj=new Ep_Quote_QuoteMissions();
			$seoParameters['quote_id']=$quote_id;
			$seoParameters['misson_user_type']='seo';
			$seoMissionDetails=$seoObj->getMissionDetails($seoParameters,1);
			if($seoMissionDetails)
			{
				$seo_user=$seoMissionDetails[0]['created_by'];
			}


			$prodObj=new Ep_Quote_ProdMissions();			
			$prodMissionDetails=$prodObj->getProdQuoteDetails($quote_id);
			if($prodMissionDetails)
			{
				$prod_user=$prodMissionDetails[0]['created_by'];
			}	

			//send email to sales
			$mail_obj=new Ep_Message_AutoEmails();
			$mail_parameters['bo_user']=$this->adminLogin->userId;
			$mail_parameters['quote_title']=$quote_title;
			$mail_parameters['bo_user_comments']=$comments;
			$mail_parameters['validate_link']='/quote/sales-quotes-list?submenuId=ML13-SL2';			

			if($bo_user_type!='sales' && $sales_user)
			{				
				$receiver_id=$sales_user;
				$mail_parameters['sales_user']=$sales_user;				
				
				$mail_obj->sendQuotePersonalEmail($receiver_id,140,$mail_parameters);
			}
			if($bo_user_type!='tech' && $tech_user)
			{				
				$receiver_id=$tech_user;
				$mail_parameters['sales_user']=$tech_user;	
				
				$mail_obj->sendQuotePersonalEmail($receiver_id,140,$mail_parameters);
			}
			if($bo_user_type!='seo' && $seo_user)
			{				
				$receiver_id=$seo_user;
				$mail_parameters['sales_user']=$seo_user;
				
				$mail_obj->sendQuotePersonalEmail($receiver_id,140,$mail_parameters);
			}
			if($bo_user_type!='prod' && $prod_user)
			{				
				$receiver_id=$prod_user;
				$mail_parameters['sales_user']=$prod_user;
				if($prod_only)
				{
					$mail_parameters['prod_only_text']='yes';
				}
				$mail_obj->sendQuotePersonalEmail($receiver_id,140,$mail_parameters);
			}	

			//echo $sales_user."--".$tech_user."--".$seo_user."--".$prod_user.$mail_parameters['prod_only_text'];
	}

	public function ajaxCalculateDeliveryTimeAction()
	{

		$prod_parameters=$this->_request->getParams();
			
		$mission_id=$prod_parameters['mission_id'];
		$mission_type=$prod_parameters['mission_type'];
		$nbwriters=$prod_parameters['nbwriters'];

		if($this->_request-> isPost()  && $this->adminLogin->userId)
		{
			//Get seo missions related to a mission
			$searchParameters['mission_id']=$mission_id;
			
			//echo "<pre>";print_r($searchParameters);
			$quoteMission_obj=new Ep_Quote_QuoteMissions();
			$MissonDetails=$quoteMission_obj->getMissionDetails($searchParameters);
			//echo "<pre>";print_r($MissonDetails);exit;
			if($MissonDetails)
			{
				$s=0;
				foreach($MissonDetails as $smission)
				{	
					$quote_id=$smission['quote_id'];
					$quote_obj=new Ep_Quote_Quotes();
					$quoteDetails=$quote_obj->getQuoteDetails($quote_id);

					//getting suggested mission Details for given missions
					if($smission['sales_suggested_missions'])
					{
						$archmission_obj=new Ep_Quote_Mission();
						$archParameters['mission_id']=$smission['sales_suggested_missions'];
						$suggested_mission_details=$archmission_obj->getMissionDetails($archParameters);										
						if($suggested_mission_details)
						{
							foreach($suggested_mission_details as $key=>$suggested_mission)
							{								
						

								//delivery time

								//total mission words
								$mission_volume=$smission['volume'];
								$mission_nb_words=$smission['nb_words'];
								$total_mission_words=($mission_volume*$mission_nb_words);
						
								//words that can write per writer with in delivery weeks
								//$sales_delivery_time=$quoteDetails[0]['sales_delivery_time_option']=='hours' ? ($quoteDetails[0]['sales_delivery_time']/24) : $quoteDetails[0]['sales_delivery_time'];
								//$sales_delivery_week=ceil($sales_delivery_time/7);

								//words peruser per type

								$mission_product=$smission['product_type'];
								if($smission['product_type']=='autre')
									$mission_product='article_seo';
								$articles_perweek=$this->configval['max_writer_'.$mission_product];
								$words_perweek_peruser=$articles_perweek*250;
								//$words_peruser_perdelivery=$sales_delivery_week*$words_perweek_peruser;
								
								if($mission_type=='proofreading')
									$givenWriters=$nbwriters*5;
								else	
									$givenWriters=$nbwriters;

								$total_delivery_days=number_format((($total_mission_words/($words_perweek_peruser*$givenWriters))*7),2);


								$mission_length=round(($total_delivery_days*90)/100);								
								$staff_setup_length=round(($total_delivery_days*10)/100);								
								$staff_setup_length=$staff_setup_length < 10 ? $staff_setup_length :10;

								if($quoteDetails[0]['sales_delivery_time_option']=='hours')	
								{
									$mission_length=round($mission_length/24);
									$staff_setup_length=round($staff_setup_length/24);
								}
								if(!$mission_length)
										$mission_length=1;
								if(!$staff_setup_length)
										$staff_setup_length=1;
							
									
								$time_option='days';
								
								echo json_encode(array('staff_length'=>$staff_setup_length,'mission_length'=>$mission_length,'time_option'=>$time_option));exit;

							}						
							
						}
						//echo "<pre>";print_r($seoMissonDetails);exit;
					}					
				}				
			}
		}
		else{

			$this->render('popup-delivery-time-calculation');			
		}	
	}

	//download Quote XLS
	public function downloadQuoteXlsAction()
	{
		$prod_parameters=$this->_request->getParams();

		$quote_id=$prod_parameters['quote_id'];

		$quote_obj=new Ep_Quote_Quotes();

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
							$missonDetails[$m]['language_source_name']=$this->getLanguageName($mission['language_source']);
							$missonDetails[$m]['product_type_name']=$this->producttype_array[$mission['product_type']];
							if($mission['language_dest'])
								$missonDetails[$m]['language_dest_name']=$this->getLanguageName($mission['language_dest']);						

							$missonDetails[$m]['comment_time']=time_ago($mission['created_at']);

							if($mission['product']=='seo_audit' || $mission['product']=='smo_audit' )
							{
								if($mission['internal_cost']>0)
								{
									$missonDetails[$m]['internal_cost']=$mission['internal_cost'];
									$audit=$mission['product']=='seo_audit' ?'SEO':'SMO';
									$missonDetails[$m]['internalcost_details']="$audit Audit : ".zero_cut($mission['cost'],2)." &".$quote['sales_suggested_currency'].";<br>";
									$total_internalcost+=$mission['internal_cost'];
								}
								else
								{
									$missonDetails[$m]['internal_cost']=$mission['cost'];
									$audit=$mission['product']=='seo_audit' ?'SEO':'SMO';
									$missonDetails[$m]['internalcost_details']="$audit Audit : ".zero_cut($mission['cost'],2)." &".$quote['sales_suggested_currency'].";<br>";
									$total_internalcost+=$mission['cost'];
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
										$internalcost=$internalcost+$prodMission['cost'];
										$internalcost_details.=$this->seo_product_array[$prodMission['product']]. " : ".zero_cut($prodMission['cost'],2)." &".$prodMission['currency'].";<br>";

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

										

										$staff_time[]=$staff_mission_stetup;
										$prod_delivery_time[]=$prod_delivery_stetup;

										

										//getting required writers	
										if($prodMission['product']=='redaction' || (!$required_writes && $prodMission['product']=='autre' ))
										{											
											$required_writes=$prodMission['staff'];
										}

									}
									//required Writres
									$missonDetails[$m]['required_writes']=$required_writes;

									//echo "<pre>";print_r($prodMissionDetails);		
									if($mission['internal_cost']>0)
									{
										$missonDetails[$m]['internal_cost']=$mission['internal_cost'];
										$total_internalcost+=$mission['internal_cost'];
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
									$missonDetails[$m]['internal_cost']=$mission['internal_cost'];
									$missonDetails[$m]['internalcost_details'].="Internal cost : ".zero_cut($mission['internal_cost'],2)." &".$quote['sales_suggested_currency'].";<br>";
								}
								else
									$missonDetails[$m]['internal_cost']=0;
							}	


							$missonDetails[$m]['unit_price']=number_format(($missonDetails[$m]['internal_cost']/(1-($mission['margin_percentage']/100))),2, '.', '');
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
								$teamPrice=(ceil($missonDetails[$m]['required_writes']/3))*350;								
								$missonDetails[$m]['team_fee']=$teamPrice;
							}

							if(!$missonDetails[$m]['team_packs'])
								$missonDetails[$m]['team_packs']=(ceil($missonDetails[$m]['required_writes']/3));

							//calculate user turnover for user package
							if(!$missonDetails[$m]['user_fee'])
								$missonDetails[$m]['user_fee']=350;

							$missonDetails[$m]['user_package_turnover']=(($missonDetails[$m]['required_writes']*$missonDetails[$m]['user_fee']));
							$missonDetails[$m]['team_package_turnover']=(($missonDetails[$m]['turnover']+$missonDetails[$m]['team_fee']));


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
							
							$techMissionDetails[$t]['internal_cost']=$mission['cost'];
							$techMissionDetails[$t]['unit_price']=number_format(($techMissionDetails[$t]['internal_cost']/(1-($mission['margin_percentage']/100))),2, '.', '');
						    $techMissionDetails[$t]['turnover']=$techMissionDetails[$t]['unit_price']*$mission['volume'];

						    $total_internalcost+=$mission['cost'];
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
								$teamPrice=(ceil($techMissionDetails[$t]['required_writes']/3))*350;									
								$techMissionDetails[$t]['team_fee']=$teamPrice;
							}
							if(!$techMissionDetails[$t]['team_packs'])
								$techMissionDetails[$t]['team_packs']=(ceil($techMissionDetails[$t]['required_writes']/3));

							//calculate user turnover for user package
							if(!$techMissionDetails[$t]['user_fee'])
								$techMissionDetails[$t]['user_fee']=350;

							$techMissionDetails[$t]['user_package_turnover']=(($techMissionDetails[$t]['required_writes']*$techMissionDetails[$t]['user_fee']));
							$techMissionDetails[$t]['team_package_turnover']=(($techMissionDetails[$t]['turnover']+$techMissionDetails[$t]['team_fee']));
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
								

					$q++;
				}				
				$this->_view->quoteDetails=$quoteDetails;
			}
		}		
		// any of your code here   	    
   	    $htmltable = $html = $this->_view->renderHtml('quote-download-xls');  // return the view script content as a string
  		//$html=strip_tags($html,'<table><tr><th><td>'); 	
  		//echo $html;exit;	
  		$session_variable='session_'.rand(100000,999999);
		$cfilename = "quote-xls-".$quote_id.".xlsx";
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
		$objPHPExcel->getDefaultStyle()->getFont()->setSize(11);
		$tm = date("YmdHis");
		$pos = strpos($usermail, "@");
		$user = substr($usermail, 0, $pos);
		$user = str_replace(".","",$user);
		
		
		$fname = $_SERVER['DOCUMENT_ROOT']."/BO/quotexls/$filename"; /* Filename */
	
		if(!$extract)//image not required for extract XLS
		{

			$objDrawing = new PHPExcel_Worksheet_Drawing();
			$objDrawing->setName('Logo');
			$objDrawing->setDescription('Logo');
			$objDrawing->setPath($_SERVER['DOCUMENT_ROOT'].'/BO/theme/gebo/img/edit-place.png');
			$objDrawing->setCoordinates('B1');
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
					   $bodyrows[$r]['size'][] = 11;
					  else
					   $bodyrows[$r]['size'][] = $fontsize;
					}else{
					  $bodyrows[$r]['color'][] = $this->findSpanColor($this->innerHTML($tds->item($x)));
					  $bodyrows[$r]['size'][] = 11;
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
				$worksheet->getColumnDimension($xcol)->setWidth(25);
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
	
	//Quote mission extract XLS
	public function quoteMissionExtractAction()
	{
		$quoteMission_obj=new Ep_Quote_QuoteMissions();
		$extractedMissions=$quoteMission_obj->quoteMissionExtract();
		$client_obj=new Ep_Quote_Client();	
		
		$xls_array=array();
		$last_quote_id='';
		
		foreach($extractedMissions as $index=>$mission)
		{
			$file_index=$index+1;
			if($last_quote_id!=$mission['quote_id'])
			{
				$xls_array[$file_index][0]=$mission['quote_id'];
				$xls_array[$file_index][1]=$mission['title'];
				$xls_array[$file_index][2]=$mission['sales_review'];
				$xls_array[$file_index][3]=$mission['company_name'];			
				$bo_user_details=$client_obj->getQuoteUserDetails($mission['quote_by']);
				$xls_array[$file_index][4]=$bo_user_details[0]['first_name'].' '.$bo_user_details[0]['last_name'];
			}
			else{
				$xls_array[$file_index][0]='';
				$xls_array[$file_index][1]='';
				$xls_array[$file_index][2]='';
				$xls_array[$file_index][3]='';
				$xls_array[$file_index][4]='';
			}	
			
			$xls_array[$file_index][5]=date("d/m/Y",strtotime($mission['created_at']));
			$xls_array[$file_index][6]=$this->seo_product_array[$mission['product']];
			$xls_array[$file_index][7]=($mission['product']=='seo_audit' OR $mission['product']=='smo_audit') ? $this->seo_product_array[$mission['product']] : $this->producttype_array[$mission['product_type']];
			$xls_array[$file_index][8]=$mission['internal_cost']." &".$mission['sales_suggested_currency'].";";
			$xls_array[$file_index][9]=$mission['product']=='translation' ? $this->getLanguageName($mission['language_source'])." > ".$this->getLanguageName($mission['language_dest']) : $this->getLanguageName($mission['language_source']);
			$xls_array[$file_index][10]=$mission['volume'];
			$xls_array[$file_index][11]=$mission['unit_price']." &".$mission['sales_suggested_currency'].";";
			$xls_array[$file_index][12]=ucfirst($mission['package']);
			
			//get mission version details
			$mission_id=$mission['mission_id'];
			$versionDetails=array();
			$versionDetails=$quoteMission_obj->getExtractMissionVersionDetails($mission_id);
			if($versionDetails)
			{
				foreach($versionDetails as $verison)
				{					
					$xls_array[$file_index][]=date("d/m/Y",strtotime($verison['created_at']));
					$xls_array[$file_index][]=$verison['volume'];
					$xls_array[$file_index][]=$verison['unit_price']." &".$mission['sales_suggested_currency'].";";
					$xls_array[$file_index][]='';
					$xls_array[$file_index][]=ucfirst($verison['package']);
				}	
			}
			
			$max_array_index[]=max(array_keys($xls_array[$file_index]));
			$last_quote_id=$mission['quote_id'];
		}
		$highest_column_count=max($max_array_index);
		if(count($xls_array)>0)
		{
			$default_column_count=12;
			
			$xls_array[0][0]='Quote id';
			$xls_array[0][1]='Quote Title';
			$xls_array[0][2]='Status';
			$xls_array[0][3]='Client';
			$xls_array[0][4]='Sales name';
			$xls_array[0][5]='Date of Creation';
			$xls_array[0][6]='Type';
			$xls_array[0][7]='Produit';
			$xls_array[0][8]='Co&ucirc;t interne';
			$xls_array[0][9]='Langue';
			$xls_array[0][10]='Volume';
			$xls_array[0][11]='P vente';
			$xls_array[0][12]='Formule';

			$version_count=($highest_column_count-$default_column_count)/5;
			$z=0;			
			for($i=1;$i<=$version_count;$i++)
			{
				$xls_array[0][$default_column_count+$i+$z]='Creation date V'.$i;
				$xls_array[0][$default_column_count+$i+$z+1]='Volume V'.$i;
				$xls_array[0][$default_column_count+$i+$z+2]='P vente V'.$i;
				$xls_array[0][$default_column_count+$i+$z+3]='Empty column V'.$i;
				$xls_array[0][$default_column_count+$i+$z+4]='Formule V'.$i;
				$z=$z+4;
			}
			ksort($xls_array);
			
			$xls_table='<table border="1" cellpadding=5 cellspacing=5>';
			
			foreach($xls_array as $r=>$row)
			{
				$xls_table.='<tr>';
				foreach($row as $c=>$column)
				{
					if($r==0)
						$xls_table.='<th><b>'.$column.'</b></th>';
					else
						$xls_table.='<td>'.$column.'</td>';
				}
				$xls_table.='</tr>';
				
			}
			$xls_table.='</table>';

			$cfilename = "quote-extract-".date("YmdHis").".xlsx";
			$this->convertHtmltableToXlsx($xls_table,$cfilename,TRUE);
			$this->_redirect("/BO/download-quote-xls.php?session_id=".$cfilename);
			//echo $xls_table;
			//echo "<pre>";print_r($xls_array);
		}
	}
	
	public function getAvgProdSalesAction()
	{
		$contract_obj = new Ep_Quote_Quotecontract();
		$quote_logs = $contract_obj->getAvgProdSales();
		$prev_id = "";
		$prod = true;
		$prod_action_at = "";
		$sales_action_at = "";
		$total_hours = 0;
		$total_count = 0;
		foreach($quote_logs as $row)
		{
			if($prev_id == $row['quote_id'])
			{
				if($row['action']=='sales_validated_ontime' && !$prod)
				{
					$sales_action_at = $row['action_at'];
					$prod = true;
					$timestamp1 = strtotime($prod_action_at);
					$timestamp2 = strtotime($sales_action_at);
					$hour = abs($timestamp2 - $timestamp1)/(60*60);
					$total_hours += $hour;
					$total_count++;
				}
			}
			else
			{
				if(($row['action']=='prod_validated_delay' || $row['action']=='prod_validated_ontime') && $prod)
				{
					$prod = false;
					$prod_action_at = $row['action_at'];
				}
				else
				$prod = true;
			}
			$prev_id = $row['quote_id'];
		}
		echo number_format($total_hours/$total_count,2);
	}
	
	// Client listing from Quotes
	function clientsListAction()
	{
		$client_obj = new Ep_Quote_Client();
		$this->_view->clients = $client_obj->getClients();
		$this->render('clients-list');
	}
	
	function getCustomName($type,$name)
	{
		$categories_array = $this->_arrayDb->loadArrayv2($type, $this->_lang);
		return $categories_array[$name];
	}
	
	function getExtractAction()
	{
		$ongoing_obj = new Ep_Quote_Ongoing();
		$result = $ongoing_obj->getExtract();
		if($result)
		{
			$i = 0;
			$htmltable ="<table>
					<tr>
					<td><b>Client Name</b></td>
					<td colspan='2'></td>
					<td><b>Article Create Date</b></td>
					<td></td>
					<td><b>Sent On</b></td>
					<td></td>
					<td><b>Sent On</b></td>
					</tr>";
			$newmission =  $newdelivery = "";
			$participationObject=new EP_Ongoing_Participation();
    		$cParticipationObject=new EP_Ongoing_CorrectorParticipation();
			$artprocess_obj= new EP_Delivery_ArticleProcess();
			foreach($result as $row)
			{
				if($newmission=="" || $newmission!=$row['qmid'])
				{
					if($row['product']=='translation')
					$mtitle = $this->product_array[$row['product']]." ".$this->producttype_array[$row['product_type']]." ".$this->getCustomName("EP_LANGUAGES",$row['language_source'])." au ".$this->getCustomName("EP_LANGUAGES",$row['language_dest']);
					else
					$mtitle = $this->product_array[$row['product']]." ".$this->producttype_array[$row['product_type']]." in ".$this->getCustomName("EP_LANGUAGES",$row['language_source']);
					$htmltable .= "<tr>
									<td >$mtitle</td>
									<td >$row[dtitle]</td>";	
				}
				else if($newdelivery=="" || $newdelivery!=$row['did'])
				{
					$htmltable .= "<tr>
									<td></td>
									<td >$row[dtitle]</td>";	
				}
				else
				{
					$htmltable .= "<tr>
									<td colspan='2'></td>";
				}
				$htmltable .=	"<td >$row[atitle]</td>
								 <td >".date('d-m-Y',strtotime($row['artcreatedate']))."</td>
									";	
				if($row['writerParticipation'])
				{
					if($row['bo_closed_status']!="closed")
					{
						$writer_details=$participationObject->getBiddingDetails($row['writerParticipation']);
						if($writer_details[0]['status']!='bid')
						{
							$writres=$artprocess_obj->getLatestWriterArticle($row['aid']);
							$htmltable .= "<td>".$this->baseurl."BO/download_article_latestversion.php?article_id=$row[aid]&type=writer</td>";
							$htmltable .= "<td>".date('d-m-Y',strtotime($writres[0]['article_sent_at']))."</td>";
						}
						else
							$htmltable .="<td></td><td></td>";
					}
					else
							$htmltable .="<td></td><td></td>";
				}
				else
					$htmltable .="<td></td><td></td>";
				
				if($row['correctorParticipation'])
				{
					//$corrector_details=$cParticipationObject->getBiddingDetails($row['correctorParticipation']);
					$corres=$artprocess_obj->getLatestCorrectionArticle($row['aid']);
					if($corres !='NO')
					{
						$htmltable .= "<td>".$this->baseurl."BO/download_article_latestversion.php?article_id=$row[aid]&type=corrector</td>";
						$htmltable .= "<td>".date('d-m-Y',strtotime($corres[0]['article_sent_at']))."</td>";
					}
					else
							$htmltable .="<td></td><td></td>";
				}
				else
					$htmltable .="<td></td><td></td>";
				
				$htmltable .="</tr>";
				
				$newmission = $row['qmid'];
				$newdelivery = $row['did'];
			}
			$htmltable .= '</table>';
		
			$cfilename = "extract-".time().".xlsx";
			$this->convertHtmltableToXlsx($htmltable,$cfilename,true);
			$this->_redirect("/BO/download-quote-xls.php?session_id=".$cfilename);
		}
	}
	
	
	/* To get Quote Files */
	function getQuoteFiles($quote=array())
	{
		$files='<table class="table">';
		$documents_path=array_filter(explode("|",$quote['documents_path']));
		$documents_name=explode("|",$quote['documents_name']);
		$quote_id = $quote['quote_id'];
		$zip = "";
		if(count($documents_path))
			$zip = '<tr><th colspan="5"><a href="/quote/download-document?type=quote&index=-1&quote_id='.$quote_id.'" class="btn btn-small pull-right">Download Zip</a></th></tr>';
		$files .=$zip;

		if(!$quote['delete']):
		foreach($documents_path as $k=>$file)
		{
			$file_path=$this->quote_documents_path.$documents_path[$k];
			if(file_exists($file_path) && !is_dir($this->quote_documents_path.$documents_path[$k]))
			{
				if($documents_name[$k])
					$file_name=$documents_name[$k];
				else
					$file_name=basename($file);
				$ofilename = pathinfo($file_path);
				$files .= '<tr><td width="30%">'.$file_name.'</td><td width="35%">'.substr($ofilename['filename'],0,-3).".".$ofilename['extension'].'</td><td width="20%">'.formatSizeUnits(filesize($file_path)).'</td><td>Sales</td><td align="center" width="15%"><a href="/quote/download-document?type=quote&quote_id='.$quote_id.'&index='.$k.'"><i style="margin-right:5px" class="splashy-download"></i></a><td></tr>';
			}
		}
		$files .='</table>';
		endif;
		return $files;
	}
	
	/* To get Tech Files */
	function getTechFiles($mission = array())
	{
		$exploded_file_paths = array_filter(explode("|",$mission['documents_path']));
		$exploded_file_names = explode("|",$mission['documents_name']);
		$zip = "";
		if(count($exploded_file_paths))
			$zip = '<tr><th colspan="5"><a href="/quote/download-document?type=tech_mission&index=-1&mission_id='.$mission['id'].'" class="btn btn-small pull-right">Download Zip</a></th></tr>';
		$files = '<table class="table">
				'.$zip;
		$k=0;
		if($mission['delete']):
		foreach($exploded_file_paths as $row)
		{
			$file_path=$this->mission_documents_path.$row;
			if(file_exists($file_path) && !is_dir($file_path))
			{
				$fname = $exploded_file_names[$k];
				if($fname=="")
					$fname = basename($row);
				$ofilename = pathinfo($file_path);
				$files .= '<tr><td width="30%">'.$fname.'</td><td width="35%">'.substr($ofilename['filename'],0,-3).".".$ofilename['extension'].'</td><td width="20%">'.formatSizeUnits(filesize($file_path)).'</td><td>Tech</td><td align="center" width="15%"><a href="/quote/download-document?type=tech_mission&mission_id='.$mission['id'].'&index='.$k.'"><i style="margin-right:5px" class="splashy-download"></i></a><span class="deletetech" rel="'.$k.'_'.$mission['id'].'"> <i class="icon-adt_trash"></i></span></td></tr>';	
			}
			$k++;
		}
		$files .="</table>";
		else:
		foreach($exploded_file_paths as $row)
		{
			$file_path=$this->mission_documents_path.$row;
			if(file_exists($file_path) && !is_dir($file_path))
			{
				$fname = $exploded_file_names[$k];
				if($fname=="")
					$fname = basename($row);
				$ofilename = pathinfo($file_path);
				$files .= '<tr><td width="30%">'.$fname.'</td><td width="35%">'.substr($ofilename['filename'],0,-3).".".$ofilename['extension'].'</td><td width="20%">'.formatSizeUnits(filesize($file_path)).'</td><td>Tech</td><td align="center" width="15%"><a href="/quote/download-document?type=tech_mission&mission_id='.$mission['id'].'&index='.$k.'"><i style="margin-right:5px" class="splashy-download"></i></a></td></tr>';	
			}
			$k++;
		}
		$files .="</table>";
		endif;			
		return $files;
	}
	
	/* To get SEO files */
	function getSeoFiles($mission=array())
	{
		$exploded_file_paths = array_filter(explode("|",$mission['documents_path']));
		$exploded_file_names = explode("|",$mission['documents_name']);
		$zip = "";
		
		if(count($exploded_file_paths))
			$zip = '<tr><th colspan="5"><a href="/quote/download-document?type=seo_mission&index=-1&mission_id='.$mission['id'].'" class="btn btn-small pull-right">Download Zip</a></th></tr>';
		
		$files = '<table class="table">'.$zip;
		$k=0;
		if($mission['delete']):
		foreach($exploded_file_paths as $row)
		{
			$file_path=$this->mission_documents_path.$row;
			if(file_exists($file_path) && !is_dir($file_path))
			{
					$fname = $exploded_file_names[$k];
					if($fname=="")
						$fname = basename($row);
					$ofilename = pathinfo($file_path);
					$files .= '<tr><td width="30%">'.$fname.'</td><td width="35%">'.substr($ofilename['filename'],0,-3).".".$ofilename['extension'].'</td><td width="20%">'.formatSizeUnits(filesize($file_path)).'</td><td>SEO</td><td align="center" width="15%"><a href="/quote/download-document?type=seo_mission&mission_id='.$mission['id'].'&index='.$k.'"><i style="margin-right:5px" class="splashy-download"></i></a><span class="delete" rel="'.$k.'_'.$mission['id'].'"> <i class="icon-adt_trash"></i></span></td></tr>';	
					
			}
			$k++;
		} 
		$files .= '</table>';
		else:
		foreach($exploded_file_paths as $row)
		{
			$file_path=$this->mission_documents_path.$row;
			if(file_exists($file_path) && !is_dir($file_path))
			{
					$fname = $exploded_file_names[$k];
					if($fname=="")
						$fname = basename($row);
					$ofilename = pathinfo($file_path);
					$files .= '<tr><td width="30%">'.$fname.'</td><td width="35%">'.substr($ofilename['filename'],0,-3).".".$ofilename['extension'].'</td><td width="20%">'.formatSizeUnits(filesize($file_path)).'</td><td>SEO</td><td align="center" width="15%"><a href="/quote/download-document?type=seo_mission&mission_id='.$mission['id'].'&index='.$k.'"><i style="margin-right:5px" class="splashy-download"></i></a></td></tr>';	
					
			}
			$k++;
		} 
		$files .= '</table>';
		endif;
		return $files;
	}
	
	function getExtract2Action()
	{
		$cron_obj = new Ep_Quote_Cron();
		$tech_obj = new Ep_Quote_TechMissions();
		$result = $cron_obj->getExtract();
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
								";	
			
				$htmltable .="</tr>";
				$newmission = $row['qmid'];
				$newdelivery = $row['did'];
			}
			$htmltable .= '</table>';
		
			$cfilename = "extract-".date('M',strtotime(date('Y-m')." -1 month"))."-".time().".xlsx";
			$this->convertHtmltableToXlsx($htmltable,$cfilename,true);
			$this->_redirect("/BO/download-quote-xls.php?session_id=".$cfilename);
		}
	}
	
}    