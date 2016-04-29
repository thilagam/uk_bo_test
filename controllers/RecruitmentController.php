<?php

class RecruitmentController extends Ep_Controller_Action
{
	function init()
	{
		parent::init();		
		setlocale(LC_TIME, "fr_FR");
		$this->_view->fo_path = $this->_config->path->fo_path;
		$this->fo_root_path = $this->_config->path->fo_root_path;
		$this->quote_documents_path=APP_PATH_ROOT.$this->_config->path->quote_documents;
		$this->mission_documents_path=APP_PATH_ROOT.$this->_config->path->mission_documents;
		$this->adminLogin = Zend_Registry::get('adminLogin');
		$this->_view->user_type= $this->adminLogin->type ;
		$this->_view->userId = $this->adminLogin->userId;
		$this->contract_documents_path = APP_PATH_ROOT.$this->_config->path->contractDocuments;
		$this->recruitment_documents_path = APP_PATH_ROOT.$this->_config->path->recruitmentDocuments;
		$this->pre_recruitment_documents_path = APP_PATH_ROOT.$this->_config->path->recruitmentDocuments;
		$this->product_array=array(
    							"redaction"=>"Writing",
								"translation"=>"Translation",
								"autre"=>"Autre",
								"proofreading"=>"Correction",
								"seo_audit"=>"SEO Audit",
								"smo_audit"=>"SMO Audit",
        						);
		 $this->producttype_array=array(
    							"article_de_blog"=>"Blog article",
								"descriptif_produit"=>"Product description",
								"article_seo"=>"SEO article",
								"guide"=>"Guide",
								"news"=>"News",
								"autre"=>"Others"
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
		
		$this->recruitment_creation = Zend_Registry::get('recruitment');
        $this->configval=$this->getConfiguredval(); 
		$this->QZ_creation = Zend_Registry::get('QZ_creation');
	}
	/*auto recruitment details for duplicate*/
	public function autoRecruitmentSession($recruitment_id,$mission_id)
    {
    	if($recruitment_id)
    	{
    		$conversion=1;
			
			$recruit_obj=new Ep_Quote_Delivery();
    		$recruitmentDetails=$recruit_obj->getDeliveryDetails($recruitment_id);
			
			$misssionQuoteDetails=$recruit_obj->getMissionQuoteDetails($mission_id);
			if($misssionQuoteDetails[0]['conversion'] && $misssionQuoteDetails[0]['currency']!=$misssionQuoteDetails[0]['sales_suggested_currency'])
			{
				$conversion=$deliveryMission['conversion'];
			}
			
			
    		if($recruitmentDetails)
    		{
    			//echo "<pre>";print_r($recruitmentDetails);exit;	
    			foreach($recruitmentDetails as $recruitment)
    			{
					
					$this->recruitment_creation->prod_step1['user_id']=$recruitment['user_id'];					
					$this->recruitment_creation->prod_step1['title']=$recruitment['title'].' [Duplicate]';
					$this->recruitment_creation->prod_step1['product']=$recruitment['product'];
					$this->recruitment_creation->prod_step1['currency']=$recruitment['currency']; //currency from contract missions
					
					$this->recruitment_creation->prod_step1['price_min']=($recruitment['price_min']);
					$this->recruitment_creation->prod_step1['price_max']=($recruitment['price_max']);
					//for validation
					$this->recruitment_creation->prod_step1['price_min_valid']=$misssionQuoteDetails[0]['min_cost']*$conversion;
					$this->recruitment_creation->prod_step1['price_max_valid']=$misssionQuoteDetails[0]['writing']*$conversion;

					$this->recruitment_creation->prod_step1['type']=$recruitment['type'];
					$this->recruitment_creation->prod_step1['deli_anonymous']=$recruitment['deli_anonymous'];
					$this->recruitment_creation->prod_step1['language']=$recruitment['language'];
					$this->recruitment_creation->prod_step1['language_dest']=$recruitment['language_dest'];
					$this->recruitment_creation->prod_step1['signtype']=$recruitment['signtype'];
					$this->recruitment_creation->prod_step1['min_sign']=$recruitment['min_sign'];
					$this->recruitment_creation->prod_step1['max_sign']=$recruitment['max_sign'];
					$this->recruitment_creation->prod_step1['category']=$recruitment['category'];
					$this->recruitment_creation->prod_step1['premium_total']=$recruitment['premium_total'];
					$this->recruitment_creation->prod_step1['premium_option']=$recruitment['premium_option'];
					$this->recruitment_creation->prod_step1['AOtype']=$recruitment['AOtype'];
					$this->recruitment_creation->prod_step1['correction_type']=$recruitment['correction_type'];
					$this->recruitment_creation->prod_step1['test_article']=$recruitment['test_article'];
					$this->recruitment_creation->prod_step1['status_bo']='active';
					$this->recruitment_creation->prod_step1['created_by']='BO';

					$this->recruitment_creation->prod_step1['volume']=$recruitment['mission_volume'];
					$total_mission_words=($recruitment['mission_volume']*$recruitment['max_sign']);
					//tempo volume
					$oneshot=$misssionQuoteDetails[0]['oneshot'];
					if($oneshot=='yes')
					{
						$max_articles_per_contrib= $recruitment['mission_volume']/($misssionQuoteDetails[0]['mission_length']/7);
					}
					else{
						$tempo_length=$misssionQuoteDetails[0]['tempo_length'];
						$tempo_length_option=$misssionQuoteDetails[0]['tempo_length_option'];
						if($tempo_length_option=='week')
							$multiple=1;
						else if($tempo_length_option=='month')
							$multiple=(30/7);
						else if($tempo_length_option=='year')
							$multiple=(365/7);
						else if($tempo_length_option=='days')
							$multiple=(1/7);
						$max_articles_per_contrib=round($misssionQuoteDetails[0]['volume_max']/($tempo_length*$multiple));							
					}
					$this->recruitment_creation->prod_step1['max_articles_per_contrib_valid']=$max_articles_per_contrib;
					$this->recruitment_creation->prod_step1['max_articles_per_contrib']=$articles_perweek=$recruitment['max_articles_per_contrib'];

					$this->recruitment_creation->prod_step1['total_article']=$recruitment['total_article'];
					$this->recruitment_creation->prod_step1['num_hire_writers']=$recruitment['num_hire_writers'];
					
					$this->recruitment_creation->prod_step1['mission_end_days']=$misssionQuoteDetails[0]['mission_end_days'];

					// participation timings from config
					$this->recruitment_creation->prod_step1['participation_time_hour']=floor($recruitment["participation_time"]/60);
					$this->recruitment_creation->prod_step1['participation_time_min']=($recruitment["participation_time"]%60);

					//extra info for mission test
					$this->recruitment_creation->prod_step1['count_down_start']=date("Y-m-d H:i");
					$count_down_end=(time()+($this->configval["participation_time"]*60));
					$this->recruitment_creation->prod_step1['count_down_end']=date("Y-m-d H:i",$count_down_end);
					$this->recruitment_creation->prod_step1['publish_now']='yes';						
					//time frame from mission duration
					$this->recruitment_creation->prod_step1['delivery_period']=$recruitment['delivery_period'];
					$this->recruitment_creation->prod_step1['delivery_time_frame']=$recruitment['delivery_time_frame'];
					$this->recruitment_creation->prod_step1['view_to']=explode(",",$recruitment['view_to']);
					
					$this->recruitment_creation->prod_step1['editorial_chief_review']=($recruitment['editorial_chief_review']);

					//recruitment spec file
					$this->recruitment_creation->prod_step1['recruitment_spec_file_name']=$recruitment['recruitment_file_name'];
					$this->recruitment_creation->prod_step1['recruitment_spec_file_path']=$recruitment['recruitment_file_path'];
					
					//writing spec file
					$this->recruitment_creation->test_article['writing_spec_file_name']=$recruitment['file_name'];
					$this->recruitment_creation->test_article['writing_spec_file_path']=$recruitment['filepath'];
					
					//correction spec file
					$this->recruitment_creation->test_article['correction_spec_file_name']=basename($recruitment['correction_file']);
					$this->recruitment_creation->test_article['correction_spec_file_path']=$recruitment['correction_file'];
					
					//getting quiz data
					if($recruitment['link_quiz']=='yes' && $recruitment['quiz'])
					{
						$this->recruitment_creation->prod_step1['quiz_data']['quiz']=$recruitment['quiz'];
						$this->recruitment_creation->prod_step1['quiz_data']['min_good_answer']=$recruitment['quiz_marks'];
						$this->recruitment_creation->prod_step1['quiz_data']['quiz_duration']=$recruitment['quiz_duration'];
					}


					//Test article setup						
					$this->recruitment_creation->test_article['correction']=$recruitment['correction'];
					$this->recruitment_creation->test_article['correction_pricemin']=$recruitment['correction_pricemin'];
					$this->recruitment_creation->test_article['correction_pricemax']=($recruitment['correction_pricemax']);
					//for validation
					$this->recruitment_creation->test_article['correction_pricemin_valid']=0;
					$this->recruitment_creation->test_article['correction_pricemax_valid']=($misssionQuoteDetails[0]['proofreading']*$conversion);			
					// correction participation  timings from config
					$this->recruitment_creation->test_article['correction_participation_hour']=floor($recruitment["correction_participation"]/60);
					$this->recruitment_creation->test_article['correction_participation_min']=($recruitment["correction_participation"]%60);

					$this->recruitment_creation->test_article['correction_selection_hour']=floor($recruitment["correction_selection_time"]/60);
					$this->recruitment_creation->test_article['correction_selection_min']=floor($recruitment["correction_selection_time"]/60);
					//writing price display						
					$this->recruitment_creation->test_article['pricedisplay']=$recruitment["pricedisplay"];
					//correction price display
					$this->recruitment_creation->test_article['corrector_pricedisplay']=$recruitment["corrector_pricedisplay"];					
					$this->recruitment_creation->test_article['plag_excel_file']=$recruitment['plag_xls'] ? 'yes':'no';
					$this->recruitment_creation->test_article['plag_xls']= $recruitment['plag_xls'];
					$this->recruitment_creation->test_article['xls_columns']= $recruitment['xls_columns'];
					//extra info for recruitment
					$this->recruitment_creation->test_article['min_mark']=$recruitment['min_mark'];
					
					$this->recruitment_creation->test_article['free_article']= $recruitment['free_article'];
					$this->recruitment_creation->test_article['contribs_list']=explode(",",$recruitment['contribs_list']);
					//submission times
					$this->recruitment_creation->test_article['subjunior_time']=$recruitment["subjunior_time"];
					$this->recruitment_creation->test_article['junior_time']=$recruitment["junior_time"];
					$this->recruitment_creation->test_article['senior_time']=$recruitment["senior_time"];
					$this->recruitment_creation->test_article['submit_option']=$recruitment["submit_option"];
					//re submission times
					$this->recruitment_creation->test_article['jc0_resubmission']=$recruitment["jc0_resubmission"];
					$this->recruitment_creation->test_article['jc_resubmission']=$recruitment["jc_resubmission"];
					$this->recruitment_creation->test_article['sc_resubmission']=$recruitment["sc_resubmission"];
					$this->recruitment_creation->test_article['resubmit_option']=$recruitment["resubmit_option"];
			
					
					//getting all article details for step2
					$articleDetails=$recruit_obj->getArticles($recruitment_id);
					if($articleDetails)
					{
						foreach($articleDetails as $index=>$article)
						{
							$i=$index+1;
							
							//if(!$this->recruitment_creation->prod_step2['articles'][$i]['title'])
							//{
								$this->recruitment_creation->prod_step2['articles'][$i]['title']=$article['title'];

								$this->recruitment_creation->prod_step2['articles'][$i]['price_min']=$article['price_min'];
								$this->recruitment_creation->prod_step2['articles'][$i]['price_max']=$article['price_max'];
								$this->recruitment_creation->prod_step2['articles'][$i]['correction_pricemin']=$article['correction_pricemin'];
								$this->recruitment_creation->prod_step2['articles'][$i]['correction_pricemax']=$article['correction_pricemax'];
								
								if($article['view_to'])
								{
									$this->recruitment_creation->prod_step2['articles'][$i]['view_to']=explode(",",$article['view_to']);
								}								
								if($article['corrector_list'])
								{
									if($article['corrector_list']=='CB')
										$corrector_list=array('sc','jc');
									else if($article['corrector_list']=='CSC')
										$corrector_list=array('sc');
									else if($article['corrector_list']=='CJC')
										$corrector_list=array('jc');
									
									$this->recruitment_creation->prod_step2['articles'][$i]['corrector_list']=$corrector_list;
								}
								
								
								//submission times
								$this->recruitment_creation->prod_step2['articles'][$i]['subjunior_time']=$article["subjunior_time"]/60;
								$this->recruitment_creation->prod_step2['articles'][$i]['junior_time']=$article["junior_time"]/60;
								$this->recruitment_creation->prod_step2['articles'][$i]['senior_time']=$article["senior_time"]/60;
								$this->recruitment_creation->prod_step2['articles'][$i]['submit_option']='hour';

								//re submission times
								$this->recruitment_creation->prod_step2['articles'][$i]['jc0_resubmission']=$article["jc0_resubmission"]/60;
								$this->recruitment_creation->prod_step2['articles'][$i]['jc_resubmission']=$article["jc_resubmission"]/60;
								$this->recruitment_creation->prod_step2['articles'][$i]['sc_resubmission']=$article["sc_resubmission"]/60;
								$this->recruitment_creation->prod_step2['articles'][$i]['resubmit_option']='hour';
									
								$this->recruitment_creation->prod_step2['articles'][$i]['writing_start']=date("Y-m-d H:i");

								if(!$this->recruitment_creation->prod_step2['articles'][$i]['writing_end'])
								{
									$participation_time=(($this->recruitment_creation->prod_step1['participation_time_hour']*60)+$this->recruitment_creation->prod_step1['participation_time_min'])*60;
									$selection_time=(($this->recruitment_creation->prod_step1['selection_hour']*60)+$this->recruitment_creation->prod_step1['selection_min'])*60;
									$submit_time=$this->recruitment_creation->prod_step2['articles'][$i]['senior_time']*60*60;
									$this->recruitment_creation->prod_step2['articles'][$i]['writing_end']=date("Y-m-d H:i",strtotime($this->recruitment_creation->prod_step2['articles'][$i]['writing_start'])+$participation_time+$selection_time+$submit_time);
								}


								
								//corrector submission times
								$this->recruitment_creation->prod_step2['articles'][$i]['correction_jc_submission']=$article["correction_jc_submission"]/60;
								$this->recruitment_creation->prod_step2['articles'][$i]['correction_sc_submission']=$article["correction_sc_submission"]/60;
								$this->recruitment_creation->prod_step2['articles'][$i]['correction_submit_option']='hour';

								//corrector resubmission times
								$this->recruitment_creation->prod_step2['articles'][$i]['correction_jc_resubmission']=$article["correction_jc_resubmission"]/60;
								$this->recruitment_creation->prod_step2['articles'][$i]['correction_sc_resubmission']=$article["correction_sc_resubmission"]/60;
								$this->recruitment_creation->prod_step2['articles'][$i]['correction_resubmit_option']='hour';
												
								$this->recruitment_creation->prod_step2['articles'][$i]['proofread_start']=$this->recruitment_creation->prod_step2['articles'][$i]['writing_end'];	

								if(!$this->recruitment_creation->prod_step2['articles'][$i]['proofread_end'])
								{
									$participation_time=(($this->recruitment_creation->prod_step1['correction_participation_hour']*60)+$this->recruitment_creation->prod_step1['correction_participation_min'])*60;
									$selection_time=(($this->recruitment_creation->prod_step1['correction_selection_hour']*60)+$this->recruitment_creation->prod_step1['correction_selection_min'])*60;
									$submit_time=$this->recruitment_creation->prod_step2['articles'][$i]['correction_sc_submission']*60*60;
									$this->recruitment_creation->prod_step2['articles'][$i]['proofread_end']=date("Y-m-d H:i",strtotime($this->recruitment_creation->prod_step2['articles'][$i]['proofread_start'])+$participation_time+$selection_time+$submit_time);
								}


								if($article['contribs_list'])
									$this->recruitment_creation->prod_step2['articles'][$i]['contribs_list']=explode(",",$article['contribs_list']);
								if($article['corrector_privatelist'])
									$this->recruitment_creation->prod_step2['articles'][$i]['corrector_privatelist']=explode(",",$article['corrector_privatelist']);

								$this->recruitment_creation->prod_step2['articles'][$i]['stick_calendar']='yes';
								$this->recruitment_creation->prod_step2['articles'][$i]['article_id']=$i;
							//}			
						}
					}
					
					$this->recruitment_creation->prod_step3['send_email']=$recruitment['mail_send'];
					
					//echo "<pre>";print_r($this->recruitment_creation->test_article);exit;
    			}
    			//echo "<pre>";print_r($this->recruitment_creation->prod_step2['articles']);exit;
    		}
    		else
    		{
				$this->_redirect("/quotedelivery/delivery-prod1?mission_id=$mission_id&daction=new&submenuId=ML13-SL4");    			
    		}
    		
    	}
    	
    }
	
	public function recruitmentProd1Action()
	{
		
		$step1Params=$this->_request->getParams();
		$mission_id=$step1Params['contract_missionid'];
		
		if($step1Params['raction']=='duplicate' && $step1Params['recruitment_id'] )
		{
				$this->autoRecruitmentSession($step1Params['recruitment_id'],$mission_id);
		}
		elseif($step1Params['raction']=='new')
		{

			unset($this->recruitment_creation->prod_step1);			
			unset($this->recruitment_creation->prod_step2);
			unset($this->recruitment_creation->prod_step3);
			unset($this->recruitment_creation->test_article);
		}
		
		

		$deliveryObj=new Ep_Quote_Delivery();		

		if($mission_id)
		{			
			$misssionQuoteDetails=$deliveryObj->getMissionQuoteDetails($mission_id);
			
			if($misssionQuoteDetails)
			{	
				$conversion=1;
				
				foreach($misssionQuoteDetails as $pkey=>$deliveryMission)
				{
					if($deliveryMission['conversion'] && $deliveryMission['currency']!=$deliveryMission['sales_suggested_currency'])
					{
						$conversion=$deliveryMission['conversion'];
					}
					
					
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

					//echo $this->recruitment_creation->prod_step1['participation_time_hour'];
					//unset($this->recruitment_creation->prod_step1);
					//Assign quote category/lang and other details to delivery
					if(count($this->recruitment_creation->prod_step1)==0)
					{
						$this->recruitment_creation->prod_step1['user_id']=$deliveryMission['client_id'];
						$this->recruitment_creation->prod_step1['client_name']=$deliveryMission['company_name'];
						//title						
						if($deliveryMission['product']=='translation')
							$language_text=$misssionQuoteDetails[$pkey]['language_dest_name'];
						else
							$language_text=$misssionQuoteDetails[$pkey]['language_source_name'];
						$category=trim($this->getCustomName("EP_ARTICLE_CATEGORY",$deliveryMission['quote_category']));
						preg_match('/^([aeiou])?/i',$category,$matches);
						if(count($matches)>1)
							$prefix=" an'";
						else
							$prefix=" a ";
						$title=$misssionQuoteDetails[$pkey]['product_name']." in ".$language_text." website for ".$prefix.$category; 					
						$this->recruitment_creation->prod_step1['title']=str_ireplace("_new",'',$title);
						$this->recruitment_creation->prod_step1['product']=$deliveryMission['product'];
						$this->recruitment_creation->prod_step1['currency']=$deliveryMission['currency']; //currency from contract missions
						
						$this->recruitment_creation->prod_step1['price_min']=($deliveryMission['min_cost']*$conversion);
						$this->recruitment_creation->prod_step1['price_max']=($deliveryMission['writing']*$conversion);
						//for validation
						$this->recruitment_creation->prod_step1['price_min_valid']=($deliveryMission['min_cost']*$conversion);
						$this->recruitment_creation->prod_step1['price_max_valid']=($deliveryMission['writing']*$conversion);

						$this->recruitment_creation->prod_step1['type']=$deliveryMission['product_type'];
						$this->recruitment_creation->prod_step1['deli_anonymous']=0;
						$this->recruitment_creation->prod_step1['language']=$deliveryMission['language_source'];
						$this->recruitment_creation->prod_step1['language_dest']=$deliveryMission['language_dest'];
						$this->recruitment_creation->prod_step1['signtype']='words';
						$this->recruitment_creation->prod_step1['min_sign']=$deliveryMission['nb_words'];
						$this->recruitment_creation->prod_step1['max_sign']=$deliveryMission['nb_words'];
						$this->recruitment_creation->prod_step1['category']=$deliveryMission['quote_category'];
						$this->recruitment_creation->prod_step1['premium_total']=0;
						$this->recruitment_creation->prod_step1['premium_option']=13;
						$this->recruitment_creation->prod_step1['AOtype']='public';
						$this->recruitment_creation->prod_step1['correction_type']='public';
						$this->recruitment_creation->prod_step1['test_article']='yes';

						$this->recruitment_creation->prod_step1['status_bo']='active';
						$this->recruitment_creation->prod_step1['created_by']='BO';

						$this->recruitment_creation->prod_step1['volume']=$deliveryMission['volume'];
						//$this->recruitment_creation->prod_step1['files_pack']=$deliveryMission['files_pack'];

						
						$total_mission_words=($deliveryMission['volume']*$deliveryMission['nb_words']);
						//tempo volume
						$oneshot=$deliveryMission['oneshot'];
						if($oneshot=='yes')
						{
							$max_articles_per_contrib= $deliveryMission['volume']/($deliveryMission['mission_length']/7);
						}
						else{
							$tempo_length=$deliveryMission['tempo_length'];
							$tempo_length_option=$deliveryMission['tempo_length_option'];
							if($tempo_length_option=='week')
								$multiple=1;
							else if($tempo_length_option=='month')
								$multiple=(30/7);
							else if($tempo_length_option=='year')
								$multiple=(365/7);
							else if($tempo_length_option=='days')
								$multiple=(1/7);
							$max_articles_per_contrib=round($deliveryMission['volume_max']/($tempo_length*$multiple));							
						}
						$this->recruitment_creation->prod_step1['max_articles_per_contrib_valid']=$this->recruitment_creation->prod_step1['max_articles_per_contrib']=$articles_perweek=$max_articles_per_contrib;
						//$this->recruitment_creation->prod_step1['max_articles_per_contrib']=$articles_perweek=$this->configval["max_writer_".$deliveryMission['product_type']];
						$words_perweek_peruser=$articles_perweek*250;						
						$words_peruser_perdelivery=($deliveryMission['mission_end_days']/7)*$words_perweek_peruser;
						//wrting staff calculations
						$writing_staff=ceil($total_mission_words/$words_peruser_perdelivery);

						$this->recruitment_creation->prod_step1['total_article']=30;//$writing_staff;
						$this->recruitment_creation->prod_step1['num_hire_writers']=$writing_staff;
												
						//$remaining_articles=($deliveryMission['volume']-($this->recruitment_creation->prod_step1['total_article']*$this->recruitment_creation->prod_step1['files_pack']));
						//$this->recruitment_creation->prod_step1['remaining_articles']=$remaining_articles > 0 ? $remaining_articles : 0; 

						$this->recruitment_creation->prod_step1['mission_end_days']=$deliveryMission['mission_end_days'];

						// participation timings from config
						$this->recruitment_creation->prod_step1['participation_time_hour']=floor($this->configval["participation_time"]/60);
						$this->recruitment_creation->prod_step1['participation_time_min']=($this->configval["participation_time"]%60);

						//extra info for mission test
						$this->recruitment_creation->prod_step1['count_down_start']=date("Y-m-d H:i");
						$count_down_end=(time()+($this->configval["participation_time"]*60));
						$this->recruitment_creation->prod_step1['count_down_end']=date("Y-m-d H:i",$count_down_end);
						$this->recruitment_creation->prod_step1['publish_now']='yes';						
						//time frame from mission duration
						$this->recruitment_creation->prod_step1['delivery_period']=$deliveryMission['mission_length_option'];
						$this->recruitment_creation->prod_step1['delivery_time_frame']=$deliveryMission['mission_length'];
						$this->recruitment_creation->prod_step1['view_to']=array('sc','jc','jc0');


						//Test article setup						
						$this->recruitment_creation->test_article['correction']='external';
						$this->recruitment_creation->test_article['correction_pricemin']=0;
						$this->recruitment_creation->test_article['correction_pricemax']=($deliveryMission['proofreading']*$conversion);
						//for validation
						$this->recruitment_creation->test_article['correction_pricemin_valid']=0;
						$this->recruitment_creation->test_article['correction_pricemax_valid']=($deliveryMission['proofreading']*$conversion);			
						// correction participation  timings from config
						$this->recruitment_creation->test_article['correction_participation_hour']=floor($this->configval["correction_participation"]/60);
						$this->recruitment_creation->test_article['correction_participation_min']=($this->configval["correction_participation"]%60);

						$this->recruitment_creation->test_article['correction_selection_hour']=1;
						$this->recruitment_creation->test_article['correction_selection_min']=0;
						//writing price display						
						$this->recruitment_creation->test_article['pricedisplay']='yes';
						//correction price display
						$this->recruitment_creation->test_article['corrector_pricedisplay']='yes';

					}
					//total royalties
					$this->recruitment_creation->prod_step1['total_royalties']=($this->recruitment_creation->prod_step1['volume']*$this->recruitment_creation->prod_step1['price_max']);

					$this->recruitment_creation->prod_step1['mission_id']=$mission_id;
					//$this->recruitment_creation->prod_step1['title']='Recruitment '.date('dmy');
					$this->recruitment_creation->prod_step1['delivery_end_date']=date('Y-m-d', strtotime($deliveryMission['assigned_at']. ' + '.$deliveryMission['mission_end_days'].' days'));

					//echo "<pre>";print_r($this->recruitment_creation->prod_step1);exit;


				}
				$this->_view->prod_step1=$this->recruitment_creation->prod_step1;
				$this->_view->misssionQuoteDetails=$misssionQuoteDetails;
				$this->_view->details='no';
				$this->_view->month_week = array('day'=>'day(s)','week'=>'Week(s)','month'=>'Month(s)','year'=>'Year(s)');
				if(count($this->recruitment_creation->prod_step1['quiz_data'])>0)
				{
					$obj = new Ep_Quizz_Quizz() ;
					$datas = $obj->quizzdetails($this->recruitment_creation->prod_step1['quiz_data']['quiz']);
					$this->_view->quiz_title = $datas[0]['title'];
					$this->_view->quiz_status = $datas[0]['status'];
					$this->_view->quiz_duration = $this->recruitment_creation->prod_step1['quiz_data']['quiz_duration'];
					$this->_view->update_quiz='yes';
				}
				//echo "<pre>";print_r($this->recruitment_creation->prod_step1);exit;
				
				$this->render('recruitment-prod1');
			}
			else
				$this->_redirect("/contractmission/missions-list");
		}
		else
			$this->_redirect("/contractmission/missions-list");	
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
	function getCustomName($type,$name)
	{
		$categories_array = $this->_arrayDb->loadArrayv2($type, $this->_lang);
		return $categories_array[$name];
	}
	//load Quiz Action
	function loadquizAction()
	{
		$request = $this->_request->getParams();
		if($this->_request->isPost() && $_SERVER['HTTP_X_REQUESTED_WITH']=='XMLHttpRequest' && $request['quiz_id'])
		{
			$obj = new Ep_Quizz_Quizz() ;
			$datas = $obj->viewQuizz($request['quiz_id']);
			foreach($datas as $qz)
			{
				$ans[$qz['quest_id']][] = array('ans_id'=>$qz['ans_id'], 'r_ans_id'=>$qz['r_ans_id'], 'option'=>$qz['options']); 
				$qns[$qz['quest_id']] = $qz['question'];
			}
			$datas = $obj->quizzdetails($request['quiz_id']);
			$this->_view->title = $datas[0]['title'];
			$this->_view->quiz_status = $datas[0]['status'];
			$this->_view->qns = $qns;
			$this->_view->ans = $ans;
			$this->_view->nums=array('i','ii','iii','iv','v','vi','vii','viii');
			$this->render('view-quiz');
		}
	}
	/* Unlink Quiz */
	function unlinkQuizAction()
	{
		if($this->_request->isPost() && $_SERVER['HTTP_X_REQUESTED_WITH']=='XMLHttpRequest')
		{
			unset($this->recruitment_creation->prod_step1['quiz_data']);
			echo count($this->recruitment_creation->prod_step1['quiz_data']);
		}
	}
	/* Load Quiz through Ajax */
	function getQuizAction()
	{
		if($_SERVER['HTTP_X_REQUESTED_WITH']=='XMLHttpRequest')
		{
			$request = $this->_request->getParams();
			$this->_view->unlink = false;
			
			if($request['cmid'])
			{
				$contract_obj = new Ep_Quote_Quotecontract();
				$search = array('contractmissionid'=>$request['cmid']);
	
			/* 	if($request['quiz_id'])
				{
					$quiz_cat = $request['quiz_cat'];
					$quiz_id = $request['quiz_id'];
					$mini_good_answer = $request['marks'];
					$quiz_duration = $request['duration'];
					$this->_view->unlink = true;
				} */
				if(count($this->recruitment_creation->prod_step1['quiz_data'])>0)
				{
					$quiz_cat = $this->recruitment_creation->prod_step1['quiz_data']['quiz_cat'];
					$quiz_id = $this->recruitment_creation->prod_step1['quiz_data']['quiz'];
					$mini_good_answer = $this->recruitment_creation->prod_step1['quiz_data']['min_good_answer'];
					$quiz_duration = $this->recruitment_creation->prod_step1['quiz_data']['quiz_duration'];
					$this->_view->unlink = true;
				}
				else
				{
					$quote_contracts = $contract_obj->getContractDetails($search);
				
					$quote_obj = new Ep_Quote_QuoteMissions();
					$search = array('mission_id'=>$quote_contracts[0]['type_id']);
					$quote_details = $quote_obj->getMissionDetails($search);
					$quiz_cat = $quote_details[0]['quotecat'];
					$quiz_id = "";
					$mini_good_answer = "";
					$quiz_duration = "";
				}
			}
			else
			{
				$quiz_cat = "all";
				$quiz_id = "";
				$mini_good_answer = "";
				$quiz_duration = "";
			}
			
			$art_cat_type_array=  $this->_arrayDb->loadArrayv2("EP_ARTICLE_CATEGORY", $this->_lang);
			$contrib_cat_array=array_merge(array("all"=>"All"),$art_cat_type_array); 
			$this->_view->Contrib_cats = $contrib_cat_array;
			$this->_view->quiz_cat=$quiz_cat;
			$this->_view->quiz_id = $quiz_id;
			if($quiz_id)
			{
				$obj = new Ep_Quizz_Quizz() ;
				$datas = $obj->viewQuizz($quiz_id);
				foreach($datas as $qz)
				{
					$ans[$qz['quest_id']][] = array('ans_id'=>$qz['ans_id'], 'r_ans_id'=>$qz['r_ans_id'], 'option'=>$qz['options']); 
					$qns[$qz['quest_id']] = $qz['question'];
				}
				$datas = $obj->quizzdetails($this->recruitment_creation->prod_step1['quiz_data']['quiz']);
				$this->_view->title = $datas[0]['title'];
				$this->_view->quiz_status = $datas[0]['status'];
				$this->_view->qns = $qns;
				$this->_view->ans = $ans;
				$this->_view->nums=array('i','ii','iii','iv','v','vi','vii','viii');
			}
			
			$this->_view->mini_good_answer = $mini_good_answer;
			$this->_view->quiz_duration = $quiz_duration;
			
			$this->_view->quiz_list = $this->getquizlistAction(true,$quiz_id,$quiz_cat);
			$this->render('get-quiz');
		}
	}
	/* Set Quiz data through Ajax */
	function setQuizDataAction()
	{
		$request = $this->_request->getParams();
		if($this->_request->isPost() && $_SERVER['HTTP_X_REQUESTED_WITH']=='XMLHttpRequest')
		{
			$this->recruitment_creation->prod_step1['quiz_data'] = $request;			
		}
	}
	//quiz list by category
	function getquizlistAction($all=false,$quiz_id="",$cat="")
	{
		if($cat=="")
		$cat = $_REQUEST['category'];
		
		// if($cat=="")
		// $cat = "all";
		
		$quiz_obj=new Ep_Delivery_quizz();
		$quiz_list=$quiz_obj->ListQuizzbyCategory($cat);
		$quizlist.='<select name="quiz" id="quiz" placeholder="Select Quiz" data-placeholder="Select Quiz" class="validate[required]" onChange="loadquiz();"><option value="">Select</option>';
			if($all):
				for($q2=0;$q2<count($quiz_list);$q2++)
				{
					if($quiz_id==$quiz_list[$q2]['id'])
					$selected = 'selected';
					else
					$selected = '';
					$quizlist.='<option value="'.$quiz_list[$q2]['id'].'" '.$selected.' >'.$quiz_list[$q2]['title'].'</option>';
				}
					$quizlist.='</select>';
					return $quizlist;
			else:
				for($q2=0;$q2<count($quiz_list);$q2++)
					$quizlist.='<option value="'.$quiz_list[$q2]['id'].'">'.utf8_encode($quiz_list[$q2]['title']).'</option>';
					$quizlist.='</select>';
					echo $quizlist;
			endif;
	}
	//upload writing spec
	public function uploadWritingSpecAction()
	{		
		$realfilename=$_FILES['uploadfile']['name'];//echo $realfilename;exit;
		$realfilename=frenchCharsToEnglish($realfilename);
		$ext=findexts($realfilename);
		
		$uploaddir = $this->fo_root_path.'client_spec/';		
		
		$client_id=$this->recruitment_creation->prod_step1['user_id'];

		$newfilename=$client_id.".".$ext;
		if(!is_dir($uploaddir.$client_id))
		{
			mkdir($uploaddir.$client_id,0777);
			chmod($uploaddir.$client_id,0777);
		}
		$uploaddir=$uploaddir.$client_id."/";
		$realfilename=trim($realfilename);
		$realfilename=str_replace(" ","_",$realfilename);
		
		$bname=basename($realfilename,".".$ext)."_".uniqid().".".$ext;
		$file = $uploaddir . $bname;
		
		if (move_uploaded_file($_FILES['uploadfile']['tmp_name'], $file))
		{
			$this->recruitment_creation->test_article['writing_spec_file_name']=$bname;
			$this->recruitment_creation->test_article['writing_spec_file_path']="/".$client_id."/".$bname;

			chmod($file,0777);			
			echo "success#".$bname;
		}
		else
		{
			echo "error";
		}
	}
	//upload writing spec
	public function uploadRecruitmentSpecAction()
	{		
		$realfilename=$_FILES['uploadfile']['name'];//echo $realfilename;exit;
		$realfilename=frenchCharsToEnglish($realfilename);
		$ext=findexts($realfilename);
		$uploaddir = $this->fo_root_path.'client_spec/';		
		$client_id=$this->recruitment_creation->prod_step1['user_id'];
		$newfilename=$client_id.".".$ext;
		if(!is_dir($uploaddir.$client_id))
		{
			mkdir($uploaddir.$client_id,0777);
			chmod($uploaddir.$client_id,0777);
		}
		$uploaddir=$uploaddir.$client_id."/";
		$realfilename=trim($realfilename);
		$realfilename=str_replace(" ","_",$realfilename);
		$bname=basename($realfilename,".".$ext)."_".uniqid().".".$ext;
		$file = $uploaddir . $bname;
		if (move_uploaded_file($_FILES['uploadfile']['tmp_name'], $file))
		{
			$this->recruitment_creation->prod_step1['recruitment_spec_file_name']=$bname;
			$this->recruitment_creation->prod_step1['recruitment_spec_file_path']="/".$client_id."/".$bname;
			chmod($file,0777);			
			echo "success#".$bname;
		}
		else
		{
			echo "error";
		}
	}
	//upload correction spec
	public function uploadCorrectionSpecAction()
	{		
		$realfilename=$_FILES['uploadfile']['name'];
		$realfilename=frenchCharsToEnglish($realfilename);
		$ext=findexts($realfilename);
		
		$uploaddir = $this->fo_root_path.'correction_spec/';
		
		$client_id=$this->recruitment_creation->prod_step1['user_id'];
		$newfilename=$client_id.".".$ext;
		$client_corrector_spec_folder=$uploaddir.$client_id;
		if(!is_dir($client_corrector_spec_folder))
		{
			mkdir($client_corrector_spec_folder,0777);
		    chmod($client_corrector_spec_folder,0777);
		}
		
		$uploaddir=$uploaddir.$client_id."/";
		$realfilename=str_replace(" ","_",$realfilename);
		$bname=basename($realfilename,".".$ext)."_".uniqid().".".$ext;
		$file = $uploaddir.$bname;
		
		if (move_uploaded_file($_FILES['uploadfile']['tmp_name'], $file))
		{

			$this->recruitment_creation->test_article['correction_spec_file_name']=$bname;
			$this->recruitment_creation->test_article['correction_spec_file_path']="/".$client_id."/".$bname;

			chmod($file,0777);
			echo "success#".$bname;
		}
		else
		{
			echo "error";
		}
	}
	
	
	//save step1 info in session
	public function saveProd1Action()
	{
		if($this->_request->isPost())
		{
			$step1Params=$this->_request->getParams();

			//echo isodec($step1Params['editorial_chief_review']);
			//echo "<pre>";print_r($step1Params);exit;

			$mission_id=$step1Params['contract_missionid'];
			
			if($mission_id)
			{
				$product=$this->recruitment_creation->prod_step1['product']=='redaction' ? 'writers' : 'translators';
				if($step1Params['num_hire_writers']>1)
					$product.='s';
				
				$category=trim($this->getCustomName("EP_ARTICLE_CATEGORY",$this->recruitment_creation->prod_step1['category']));
				//$category=utf8_decode($category);
				
				if($step1Params['delivery_period']=='day')
					$delivery_period='day(s)';
				else if($step1Params['delivery_period']=='week')
					$delivery_period='week(s)';
				else if($step1Params['delivery_period']=='month')
					$delivery_period='month(s)';
				else if($step1Params['delivery_period']=='year')
					$delivery_period='year(s)';
				
				//$this->recruitment_creation->prod_step1['title']=$category."-".$product." ".$step1Params['volume']." ".$this->producttype_array[$this->recruitment_creation->prod_step1['type']]." during ".$step1Params['delivery_time_frame']." ".$step1Params['delivery_period']."(s) for ".$this->recruitment_creation->prod_step1['client_name'];

				//$title=$step1Params['num_hire_writers']." ".$product." en ".$category." pour ".$this->recruitment_creation->prod_step1['client_name']." . ".$step1Params['delivery_time_frame']." ".$delivery_period;
				
				//$this->recruitment_creation->prod_step1['title']=str_ireplace("_new",'',$title);

				

				//$this->recruitment_creation->prod_step1['test_article']=$test_article=$step1Params['test_article'];
				$test_article=$this->recruitment_creation->prod_step1['test_article'];

				$this->recruitment_creation->prod_step1['AOtype']=$step1Params['AOtype'];
				$this->recruitment_creation->prod_step1['correction_type']=$step1Params['AOtype'];
				$this->recruitment_creation->prod_step1['price_min']= currencyToDecimal($step1Params['price_min']);
				$this->recruitment_creation->prod_step1['price_max']= currencyToDecimal($step1Params['price_max']);
				$this->recruitment_creation->prod_step1['total_article']=$step1Params['total_article'];

				//extra info for recruitment
				$this->recruitment_creation->prod_step1['volume']= $step1Params['volume'];
				$this->recruitment_creation->prod_step1['volume_option']= $step1Params['volume_option'];
				$this->recruitment_creation->prod_step1['volume_option_multi']= $step1Params['volume_option_multi'];											
				//$this->recruitment_creation->prod_step1['delivery_time_frame']=$step1Params['delivery_time_frame'];
				//$this->recruitment_creation->prod_step1['delivery_period']=$step1Params['delivery_period'];
				$this->recruitment_creation->prod_step1['max_articles_per_contrib']=$step1Params['max_articles_per_contrib'];				
				$this->recruitment_creation->prod_step1['view_to']=$step1Params['view_to'];				
				$this->recruitment_creation->prod_step1['editorial_chief_review']=($step1Params['editorial_chief_review']);
				$this->recruitment_creation->prod_step1['publish_now']=$step1Params['publish_now']=='yes' ? 'yes':'no';
				
				$this->recruitment_creation->prod_step1['num_hire_writers']=$step1Params['num_hire_writers'];

				if($step1Params['publish_now']=='yes')
					$this->recruitment_creation->prod_step1['count_down_start']=date("Y-m-d H:i");
				else
					$this->recruitment_creation->prod_step1['count_down_start']=$step1Params['count_down_start'];				
				$this->recruitment_creation->prod_step1['count_down_end']=$step1Params['count_down_end'];

				$participation_time=dateDiff($this->recruitment_creation->prod_step1['count_down_start'],$this->recruitment_creation->prod_step1['count_down_end']);
				$this->recruitment_creation->prod_step1['participation_time_hour']=floor($participation_time/60);
				$this->recruitment_creation->prod_step1['participation_time_min']=($participation_time%60);			


				//echo "<pre>";print_r($this->recruitment_creation->prod_step1);exit;				
				//update calendar based on countdown start
				//$this->ajaxUpdateCalendar();

				if($test_article=='yes')
					$this->_redirect("/recruitment/recruitment-test-article?contract_missionid=".$mission_id);
				//else
					//$this->_redirect("/recruitment/recruitment-prod2?contract_missionid=".$mission_id);
			}				
		}	

	}

	//new function added to setup test article
	public function recruitmentTestArticleAction()
	{
		$testParams=$this->_request->getParams();
		$mission_id=$testParams['contract_missionid'];

		if(!$this->recruitment_creation->test_article['plag_excel_file'])
			$this->recruitment_creation->test_article['plag_excel_file']='yes';

		if(!$this->recruitment_creation->test_article['free_article'])
			$this->recruitment_creation->test_article['free_article']='yes';

		if($this->recruitment_creation->prod_step1['test_article']=='yes' && $mission_id)
		{
			$deliveryObj=new Ep_Quote_Delivery();
			$misssionQuoteDetails=$deliveryObj->getMissionQuoteDetails($mission_id);
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
			$this->_view->misssionQuoteDetails=$misssionQuoteDetails;
			if(!$this->recruitment_creation->test_article['senior_time'])
			{
				//submission times
				$this->recruitment_creation->test_article['subjunior_time']=$this->configval["jc0_time"]/60;
				$this->recruitment_creation->test_article['junior_time']=$this->configval["jc_time"]/60;
				$this->recruitment_creation->test_article['senior_time']=$this->configval["sc_time"]/60;
				$this->recruitment_creation->test_article['submit_option']='hour';

				//re submission times
				$this->recruitment_creation->test_article['jc0_resubmission']=$this->configval["jc0_resubmission"]/60;
				$this->recruitment_creation->test_article['jc_resubmission']=$this->configval["jc_resubmission"]/60;
				$this->recruitment_creation->test_article['sc_resubmission']=$this->configval["sc_resubmission"]/60;
				$this->recruitment_creation->test_article['resubmit_option']='hour';
			}


			//get all writers list
			if($this->recruitment_creation->prod_step1['product']=="translation")
				$searchParameters['language']=$this->recruitment_creation->prod_step1['language_dest'];
			else
				$searchParameters['language']=$this->recruitment_creation->prod_step1['language'];	

			if(is_array($this->recruitment_creation->prod_step1['view_to']))
				$searchParameters['profile_type']=$this->recruitment_creation->prod_step1['view_to'];

			$deliveryObj=new Ep_Quote_Delivery();
			$writersList=$deliveryObj->getContributorsList($searchParameters);
			
			if($writersList)
			{
				if(count($this->recruitment_creation->test_article['contribs_list'])>0)
				{
					$contribs_list['SELECTED']=array();
					$contribs_list['OTHERS']=array();		
					
					foreach($writersList as $identifier=>$writer)
					{					
						if(in_array($identifier,$this->recruitment_creation->test_article['contribs_list']))					
							$contribs_list['SELECTED'][$identifier]=$writer;
						else
							$contribs_list['OTHERS'][$identifier]=$writer;
					}
					$this->_view->writersList=$contribs_list;
				}
				else
					$this->_view->writersList=$writersList;	
			}	
			//echo "<pre>";print_r($this->_view->writersList);exit;			




			$this->_view->test_article=$this->recruitment_creation->test_article;
			$this->_view->prod_step1=$this->recruitment_creation->prod_step1;
			$this->render('recruitment-test-article');
		}
		else
			$this->_redirect("/recruitment/recruitment-prod1?contract_missionid=".$mission_id);
	}
	//save test article setup
	public function saveTestArticleAction()
	{
		$testParams=$this->_request->getParams();
		//echo "<pre>";print_r($testParams);exit;
		$mission_id=$testParams['contract_missionid'];

		if($mission_id)
		{
			$this->recruitment_creation->test_article['free_article']= $testParams['free_article'] ? 'yes' : 'no';
			//$this->recruitment_creation->test_article['article_price']= currencyToDecimal($testParams['article_price']);

			$this->recruitment_creation->test_article['contribs_list']=$testParams['contribs_list'];

			
			//submission times
			$this->recruitment_creation->test_article['subjunior_time']=$testParams["subjunior_time"];
			$this->recruitment_creation->test_article['junior_time']=$testParams["junior_time"];
			$this->recruitment_creation->test_article['senior_time']=$testParams["senior_time"];
			$this->recruitment_creation->test_article['submit_option']=$testParams["submit_option"];

			//re submission times
			$this->recruitment_creation->test_article['jc0_resubmission']=$testParams["jc0_resubmission"];
			$this->recruitment_creation->test_article['jc_resubmission']=$testParams["jc_resubmission"];
			$this->recruitment_creation->test_article['sc_resubmission']=$testParams["sc_resubmission"];
			$this->recruitment_creation->test_article['resubmit_option']=$testParams["resubmit_option"];




			$this->recruitment_creation->test_article['correction']= $testParams['correction'];

			$this->recruitment_creation->test_article['correction_pricemin']= currencyToDecimal($testParams['correction_pricemin']);
			$this->recruitment_creation->test_article['correction_pricemax']= currencyToDecimal($testParams['correction_pricemax']);
			$this->recruitment_creation->test_article['correction_participation_hour']= $testParams['correction_participation_hour'];
			$this->recruitment_creation->test_article['correction_participation_min']= $testParams['correction_participation_min'];
			
			if($testParams['correction']=='external'){
				$this->recruitment_creation->test_article['correction_selection_hour']= $testParams['correction_selection_hour'];
				$this->recruitment_creation->test_article['correction_selection_min']= $testParams['correction_selection_min'];
			}
			$this->recruitment_creation->test_article['plag_excel_file']= $testParams['plag_excel_file'];
			$this->recruitment_creation->test_article['plag_xls']= $testParams['plag_xls'];
			$this->recruitment_creation->test_article['xls_columns']= $testParams['xls_columns'];
			//extra info for recruitment
			$this->recruitment_creation->test_article['min_mark']=$testParams['min_mark'];

			$this->ajaxUpdateCalendar();

			$this->_redirect("/recruitment/recruitment-prod2?contract_missionid=".$mission_id);
		}	
	}
	
	//ajax update calendar based on launch date
	public function ajaxUpdateCalendar()
	{
		
		$publish_time=$this->recruitment_creation->prod_step1['count_down_start'];

		if($publish_time)
		{
			if($this->recruitment_creation->prod_step1['total_article']>0)
			{
				$total_article=$this->recruitment_creation->prod_step1['total_article'];
				
				for($i=1;$i<=$total_article;$i++)
				{
					$article_details=$this->recruitment_creation->prod_step2['articles'][$i];


					//writer submit times
					$article_details['subjunior_time']=$this->recruitment_creation->test_article['subjunior_time'];
					$article_details['junior_time']=$this->recruitment_creation->test_article['junior_time'];
					$article_details['senior_time']=$this->recruitment_creation->test_article['senior_time'];
					$article_details['submit_option']=$this->recruitment_creation->test_article['submit_option'];

					//writer resubmit times
					$article_details['jc0_resubmission']=$this->recruitment_creation->test_article['jc0_resubmission'];
					$article_details['jc_resubmission']=$this->recruitment_creation->test_article['jc_resubmission'];
					$article_details['sc_resubmission']=$this->recruitment_creation->test_article['sc_resubmission'];
					$article_details['resubmit_option']=$this->recruitment_creation->test_article['resubmit_option'];



					//private writers list and correctors list
					if($this->recruitment_creation->prod_step1['AOtype']=='private')
					{
						$article_details['contribs_list']=$this->recruitment_creation->test_article['contribs_list'];						
						$article_details['view_to']=$this->recruitment_creation->prod_step1['view_to'];		
					}
					else if($this->recruitment_creation->prod_step1['AOtype']=='public')
					{
						$article_details['contribs_list']=NULL;						
						$article_details['view_to']=$this->recruitment_creation->prod_step1['view_to'];
					}


					$send_time=max(array($article_details['subjunior_time'],$article_details['junior_time'],$article_details['senior_time']));
					if($article_details['submit_option']=='min')	
						$send_time=$send_time/60;					
					else if($article_details['submit_option']=='day')	
						$send_time=$send_time*24;


					//updating writing timings
					$article_details['writing_start']=$publish_time;
					
					$participation_time=(($this->recruitment_creation->prod_step1['participation_time_hour']*60)+$this->recruitment_creation->prod_step1['participation_time_min'])*60;
					//$selection_time=(($this->recruitment_creation->prod_step1['selection_hour']*60)+$this->recruitment_creation->prod_step1['selection_min'])*60;
					$submit_time=$send_time*60*60;
					$article_details['writing_end']=date("Y-m-d H:i",strtotime($article_details['writing_start'])+$participation_time+$selection_time+$submit_time);
					
					if($this->recruitment_creation->test_article['correction']=='external' || $this->recruitment_creation->test_article['correction']=='multi_external')
					{
						//updating correction timings
						$article_details['proofread_start']=$article_details['writing_end'];							
					
						$cparticipation_time=(($this->recruitment_creation->test_article['correction_participation_hour']*60)+$this->recruitment_creation->test_article['correction_participation_min'])*60;
						
						if($this->recruitment_creation->test_article['correction']=='external')
							$cselection_time=(($this->recruitment_creation->test_article['correction_selection_hour']*60)+$this->recruitment_creation->test_article['correction_selection_min'])*60;
						
						if(!$article_details['correction_sc_submission'])
							$article_details['correction_sc_submission']=$this->configval["correction_sc_submission"]/60;

						$csubmit_time=$article_details['correction_sc_submission']*60*60;
						$article_details['proofread_end']=date("Y-m-d H:i",strtotime($article_details['proofread_start'])+$cparticipation_time+$cselection_time+$csubmit_time);

						if(!is_array($article_details['corrector_list']))
							$article_details['corrector_list']=array("sc","jc");

					}
					//echo "<pre>";print_r($article_details);exit;
					$this->recruitment_creation->prod_step2['articles'][$i]=$article_details;
					unset($article_details);

				}
			}	

		}
		//echo "<pre>";print_r($launchParams);exit;
	}
	
	//Recruitment prod step2 
	public function recruitmentProd2Action()
	{
		$step2Params=$this->_request->getParams();
		$mission_id=$step2Params['contract_missionid'];

		$this->_view->timezone=date_default_timezone_get();

		//Always display the cuurent date in center of the calendar
		$day_week=date("N");
		if($day_week==7)
			$day_week=0;
		$cal_week=4+$day_week;
		if($cal_week>=7)
			$cal_week=$cal_week-7;
		$this->_view->day_week=$cal_week;	

		//echo "<pre>";print_r($this->recruitment_creation->prod_step2['articles']);exit;

		if($this->recruitment_creation->prod_step1['total_article']>0 && $mission_id)
		{
			$total_article=$this->recruitment_creation->prod_step1['total_article'];
			//$files_pack=$this->recruitment_creation->prod_step1['files_pack'];

			$type=$this->recruitment_creation->prod_step1['type'];
			$product_type=$this->producttype_array[$type];

			$session_article_count=count($this->recruitment_creation->prod_step2['articles']);

			for($i=1;$i<=$total_article;$i++)
			{
				if(!$this->recruitment_creation->prod_step2['articles'][$i]['title'])
				{
					$article_details=$this->recruitment_creation->prod_step2['articles'][$i];

					$article_details['title']="Test ".$product_type." - ".str_ireplace("_new",'',$this->recruitment_creation->prod_step1['client_name'])." - ".$i;

					$article_details['price_min']=$article_details['price_min'] ? $article_details['price_min'] : $this->recruitment_creation->prod_step1['price_min'];
					$article_details['price_max']=$article_details['price_max'] ? $article_details['price_max'] : $this->recruitment_creation->prod_step1['price_max'];
					$article_details['correction_pricemin']=$article_details['correction_pricemin'] ? $article_details['correction_pricemin'] : $this->recruitment_creation->test_article['correction_pricemin'];
					$article_details['correction_pricemax']=$article_details['correction_pricemax'] ? $article_details['correction_pricemax'] : $this->recruitment_creation->test_article['correction_pricemax'];
									

					// correction timings from config
					if(!$article_details['correction_sc_submission'] || !$article_details['correction_jc_submission'])
					{						
						//corrector submission times
						$article_details['correction_jc_submission']=$this->configval["correction_jc_submission"]/60;
						$article_details['correction_sc_submission']=$this->configval["correction_sc_submission"]/60;
						$article_details['correction_submit_option']='hour'; 

						//corrector resubmission times
						$article_details['correction_jc_resubmission']=$this->configval["correction_jc_resubmission"]/60;
						$article_details['correction_sc_resubmission']=$this->configval["correction_sc_resubmission"]/60;
						$article_details['correction_resubmit_option']='hour'; 
					}				
						

					if(!$article_details['proofread_start'])
						$article_details['proofread_start']=$article_details['writing_end'];	

					if(!$article_details['proofread_end'])
					{
						$participation_time=(($this->recruitment_creation->prod_step1['correction_participation_hour']*60)+$this->recruitment_creation->prod_step1['correction_participation_min'])*60;
						$selection_time=(($this->recruitment_creation->prod_step1['correction_selection_hour']*60)+$this->recruitment_creation->prod_step1['correction_selection_min'])*60;
						$submit_time=$article_details['correction_sc_submission']*60;
						$article_details['proofread_end']=date("Y-m-d H:i",strtotime($article_details['proofread_start'])+$participation_time+$selection_time+$submit_time);
					}


					$article_details['stick_calendar']='yes';
					$article_details['article_id']=$i;

					$this->recruitment_creation->prod_step2['articles'][$i]=$article_details;
					unset($article_details);

				}				

			}
			//echo "<pre>";print_r($this->recruitment_creation->prod_step2['articles']);exit;

			if($session_article_count>$total_article)
			{
				for($j=($total_article+1);$j<=$session_article_count;$j++)
				{
					unset($this->recruitment_creation->prod_step2['articles'][$j]);
				}
			}
			

			$this->_view->prod_step2=$this->recruitment_creation->prod_step2;
			$this->_view->test_article=$this->recruitment_creation->test_article;
			$this->_view->prod_step1=$this->recruitment_creation->prod_step1;
			$this->render('recruitment-prod2');
		}
		else
			$this->_redirect("/recruitment/recruitment-prod1?contract_missionid=".$mission_id);
	}
	
	//event json feed to the calendar from session
	public function calendarArticleEventsAction()
	{
		if(count($this->recruitment_creation->prod_step2['articles']))
		{
			$events_array=array();
			ksort($this->recruitment_creation->prod_step2['articles']);
			foreach($this->recruitment_creation->prod_step2['articles'] as $article)
			{
				if($article['stick_calendar']=='yes')
				{
					$event=array();
					
					$event['id']='article_'.$article['article_id'];
					$event['title']=$article['title'];
					$event['url']='/recruitment/create-article-pop?article_id='.$article['article_id'];

					$event['start']=$article['writing_start'];
					$event['end']=$article['writing_end'];

					$events_array[]=$event;


					if($this->recruitment_creation->test_article['correction']=='external' || $this->recruitment_creation->test_article['correction']=='multi_external')
					{
						$event['id']='article_'.$article['article_id'];
						$event['title']='Proof Reading';
						$event['url']='/recruitment/create-article-pop?article_id='.$article['article_id'];

						$event['start']=$article['proofread_start'];
						$event['end']=$article['proofread_end'];
						$event['className']='proofread-event';
						
						$events_array[]=$event;
					}
				}
			}
			/* $contract_event['id']='contract';
			$contract_event['title']='Recruitment Expected End Date';
			$contract_event['start']=$this->recruitment_creation->prod_step1['delivery_end_date'];
			$contract_event['className']='contract-event';
			$events_array[]=$contract_event; */

			
			echo json_encode($events_array);exit;
		}

	}
	
	//article creation pop in  recruitment prod2 step
	public function createArticlePopAction()
	{
		$articleParams=$this->_request->getParams();

		$deliveryObj=new Ep_Quote_Delivery();
		$recruitmentObj=new Ep_Quote_Recruitment();

		$article_id=$articleParams['article_id'];

		if($article_id)
		{
			$article_details=$this->recruitment_creation->prod_step2['articles'][$article_id];			

			//get all correctors list			
			if($this->recruitment_creation->prod_step1['product']=="translation")
				$csearchParameters['language']=$this->recruitment_creation->prod_step1['language_dest'];
			else
				$csearchParameters['language']=$this->recruitment_creation->prod_step1['language'];
			$correctorsList=$deliveryObj->getCorrectorsList($csearchParameters);
			if($correctorsList)
			{
				if(count($article_details['corrector_privatelist'])>0)
				{
					
					$correctors_list['SELECTED']=array();
					$correctors_list['OTHERS']=array();
					foreach($correctorsList as $identifier=>$corrector)
					{					
						if(in_array($identifier,$article_details['corrector_privatelist']))					
							$correctors_list['SELECTED'][$identifier]=$corrector;
						else
							$correctors_list['OTHERS'][$identifier]=$corrector;
					}
					$this->_view->correctorsList=$correctors_list;
				}
				else
					$this->_view->correctorsList=$correctorsList;	
			}	
			//echo "<pre>";print_r($this->_view->correctorsList);exit;	


			//echo "<pre>";print_r($article_details);exit;			
			$this->_view->article_details=$article_details;
			$this->_view->test_article=$this->recruitment_creation->test_article;
			$this->_view->prod_step1=$this->recruitment_creation->prod_step1;		
			$this->render('recruitment-create-article-popup');	
			
		}	
	}
	
	//article save pop up in recruitment prod2 step through ajax post
	public function ajaxSaveArticleAction()
	{
		if($this->_request-> isPost()  && $_SERVER['HTTP_X_REQUESTED_WITH']=='XMLHttpRequest')
		{
			$articleParams=$this->_request->getParams();
			//echo "<pre>";print_r($articleParams);exit;

			$article_id=$articleParams['article_id'];

			if($article_id)
			{
				
				foreach($this->recruitment_creation->prod_step2['articles'] as $key=>$article)
				{	
					if($articleParams['articles_save_action']=='save_article')
					{
						$this->recruitment_creation->prod_step2['articles'][$article_id]['title']=$articleParams['article_title'];
						if($article_id!=$key)
						{
							continue;
						}						
					}
					else if($articleParams['articles_save_action']=='save_all_articles')
					{						
						if($article_id==$key)
							$this->recruitment_creation->prod_step2['articles'][$article_id]['title']=$articleParams['article_title'];

						$article_id=$key;
					}
					

					$save_details=$this->recruitment_creation->prod_step2['articles'][$article_id];
					
					$save_details['article_id']=$article_id;
					
					
					
					//writing and correction prices
					//$save_details['price_min']=$articleParams['price_min'];
					//$save_details['price_max']=$articleParams['price_max'];									
					

					//submission times
					/*$save_details['subjunior_time']=$articleParams['subjunior_time'];
					$save_details['junior_time']=$articleParams['junior_time'];
					$save_details['senior_time']=$articleParams['senior_time'];
					$save_details['submit_option']=$articleParams['submit_option'];*/

					$send_time=max(array($save_details['subjunior_time'],$save_details['junior_time'],$save_details['senior_time']));
					if($save_details['submit_option']=='min')	
						$send_time=$send_time/60;					
					else if($save_details['submit_option']=='day')	
						$send_time=$send_time*24;


					//re submission times
					/*$save_details['jc0_resubmission']=$articleParams['jc0_resubmission'];
					$save_details['jc_resubmission']=$articleParams['jc_resubmission'];
					$save_details['sc_resubmission']=$articleParams['sc_resubmission'];
					$save_details['resubmit_option']=$articleParams['resubmit_option'];*/

					//writing start and end dates
					$save_details['writing_start']=$this->recruitment_creation->prod_step1['count_down_start'];
					$participation_time=(($this->recruitment_creation->prod_step1['participation_time_hour']*60)+$this->recruitment_creation->prod_step1['participation_time_min'])*60;
					//$selection_time=(($this->recruitment_creation->prod_step1['selection_hour']*60)+$this->recruitment_creation->prod_step1['selection_min'])*60;
					$submit_time=$send_time*60*60;
					$writing_end=date("Y-m-d H:i",strtotime($save_details['writing_start'])+$participation_time+$submit_time);					
					
					$save_details['writing_end']=$writing_end;
				
					
					if($this->recruitment_creation->test_article['correction']=='external' || $this->recruitment_creation->test_article['correction']=='multi_external')
					{

						$save_details['correction_pricemin']=$articleParams['correction_pricemin'];
						$save_details['correction_pricemax']=$articleParams['correction_pricemax'];

						//corrector submission times
						$save_details['correction_jc_submission']=$articleParams['correction_jc_submission'];
						$save_details['correction_sc_submission']=$articleParams['correction_sc_submission'];
						$save_details['correction_submit_option']=$articleParams['correction_submit_option'];

						$proodread_time=max(array($save_details['correction_jc_submission'],$save_details['correction_sc_submission']));
						if($save_details['correction_submit_option']=='min')	
							$proodread_time=$proodread_time/60;					
						else if($save_details['correction_submit_option']=='day')	
							$proodread_time=$proodread_time*24;


						//Assign write end date as proof read start if proof read start is less than write end date
						if($articleParams['proofread_start']<$save_details['writing_end'])
								$articleParams['proofread_start']=$save_details['writing_end'];

						$save_details['proofread_start']=$articleParams['proofread_start'];

						$cparticipation_time=(($this->recruitment_creation->test_article['correction_participation_hour']*60)+$this->recruitment_creation->test_article['correction_participation_min'])*60;
						$cselection_time=(($this->recruitment_creation->test_article['correction_selection_hour']*60)+$this->recruitment_creation->test_article['correction_selection_min'])*60;
						$csubmit_time=$proodread_time*60*60;
						$proofread_end=date("Y-m-d H:i",strtotime($save_details['proofread_start'])+$cparticipation_time+$cselection_time+$csubmit_time);

						$save_details['proofread_end']=$proofread_end;

						//corrector resubmission times
						$save_details['correction_jc_resubmission']=$articleParams['correction_jc_resubmission'];
						$save_details['correction_sc_resubmission']=$articleParams['correction_sc_resubmission'];
						$save_details['correction_resubmit_option']=$articleParams['correction_resubmit_option'];
					}

					//private writers list and correctors list
					if($this->recruitment_creation->prod_step1['AOtype']=='private')
					{
						//$save_details['contribs_list']=$articleParams['contribs_list'];
						$save_details['corrector_privatelist']=$articleParams['corrector_privatelist'];
						//$save_details['view_to']=array("sc","jc","jc0");
						$save_details['corrector_list']=array("sc","jc");
					}
					else if($this->recruitment_creation->prod_step1['AOtype']=='public')
					{
						//$save_details['contribs_list']=NULL;
						$save_details['corrector_privatelist']=NULL;
						//$save_details['view_to']=$articleParams['view_to'];
						$save_details['corrector_list']=$articleParams['corrector_list'];

					}

					$save_details['stick_calendar']='yes';

					//save all the submit details in to session
					$this->recruitment_creation->prod_step2['articles'][$article_id]=$save_details;
					unset($save_details);
				}	

				//echo "<pre>";print_r($save_details);exit;
				echo "success";exit;
			}
			else
				echo "error";exit;
		}

	}
	//remove session article and update packages and calendar
	public function removeSessionArticleAction()
	{
		if($this->_request-> isPost()  && $_SERVER['HTTP_X_REQUESTED_WITH']=='XMLHttpRequest')
		{
			$deleteParams=$this->_request->getParams();
			$article_id=$deleteParams['article_id'];

			if($article_id)
			{
				if(isset($this->recruitment_creation->prod_step2['articles'][$article_id]) && count($this->recruitment_creation->prod_step2['articles'][$article_id])>0)
				{
					//echo $article_id."deleted";
					unset($this->recruitment_creation->prod_step2['articles'][$article_id]);
					$article_array=$this->recruitment_creation->prod_step2['articles'];
					$this->recruitment_creation->prod_step2['articles']=array_combine(range(1, count($article_array)), array_values($article_array));
					$this->recruitment_creation->prod_step1['total_article']=$this->recruitment_creation->prod_step1['total_article']-1;
				}

				//reassign the article ids
				foreach($this->recruitment_creation->prod_step2['articles'] as $key=>$article)
				{
					$this->recruitment_creation->prod_step2['articles'][$key]['article_id']=$key;
				}

				//echo "<pre>";print_r(($this->recruitment_creation->prod_step2['articles']));
			}
		}	
	}
	
	//function to check whether corrector/writers selected if it is private
	public function checkArticleWritersCorrectorsAction()
	{
		$writingType=$this->recruitment_creation->prod_step1['AOtype'];
		$correctionType=$this->recruitment_creation->prod_step1['correction_type'];
		$error='';
		if(count($this->recruitment_creation->prod_step2['articles']))
		{			
			foreach($this->recruitment_creation->prod_step2['articles'] as $article)
			{
				if($writingType=='private')
				{
					if(count($article['contribs_list'])==0)
						$error.='please select writers for '.$article['title']."<br>";
				}
				if($correctionType=='private' && $this->recruitment_creation->test_article['correction']!='internal')
				{
					if(count($article['corrector_list'])==0)
						$error.='please select proofreaders for '.$article['title']."<br>";
				}
			}
		}
		if($error)
			echo $error;		
		else
			echo "success";
		exit;
	
	}
	
	//prod3 step
	public function recruitmentProd3Action()
	{
		$step3Params=$this->_request->getParams();
		$mission_id=$step3Params['contract_missionid'];		
		//echo "<pre>";print_r($this->_view->prod_step2=$this->recruitment_creation->prod_step2);exit;

		if($this->recruitment_creation->prod_step1['total_article']>0 && $mission_id)
		{
			$this->_view->prod_step1=$this->recruitment_creation->prod_step1;
			$this->_view->prod_step2=$this->recruitment_creation->prod_step2;
			$this->_view->prod_step3=$this->recruitment_creation->prod_step3;
			$this->render('recruitment-prod3');
		}		
		else
			$this->_redirect("/recruitment/recruitment-prod1?contract_missionid=".$mission_id);		
	}
	
	
	//save prodstep3 and create delivery and article
	public function saveProd3Action()
	{
		if($this->_request-> isPost())
		{
			$notificationParams=$this->_request->getParams();

			//echo "<pre>";print_r($notificationParams);exit;
			$mission_id=$this->recruitment_creation->prod_step1['mission_id'];
			
			$delivery_obj=new Ep_Quote_Delivery();

			$this->recruitment_creation->prod_step3['send_mission_comments']=$notificationParams['send_mission_comments'];
			$this->recruitment_creation->prod_step3['missioncomment']=$notificationParams['missioncomment'];			
			$this->recruitment_creation->prod_step3['send_email']=$notificationParams['send_email'];
			$this->recruitment_creation->prod_step3['mailsubject']=$notificationParams['mailsubject'];
			$this->recruitment_creation->prod_step3['mailcontent']=$notificationParams['mailcontent'];
			
			//Delivery insertion
			$delivery_array["contract_mission_id"]=$mission_id;

			$delivery_array["user_id"] = $this->recruitment_creation->prod_step1['user_id'];
			$delivery_array['title'] = $this->recruitment_creation->prod_step1['title'];
			$delivery_array["deli_anonymous"] =$this->recruitment_creation->prod_step1['deli_anonymous'] ? 'yes' : 'no';
			$delivery_array["total_article"] = $this->recruitment_creation->prod_step1['total_article'];
			//$delivery_array["language"] = $this->recruitment_creation->prod_step1['language'];
			$delivery_array["type"] = $this->recruitment_creation->prod_step1['type'];
			$delivery_array["product"] = $this->recruitment_creation->prod_step1['product'];
			$delivery_array["category"] = $this->recruitment_creation->prod_step1['category'];
			$delivery_array["signtype"] = $this->recruitment_creation->prod_step1['signtype'];
			$delivery_array["currency"] = $this->recruitment_creation->prod_step1['currency'];

			$delivery_array["min_sign"]=$this->recruitment_creation->prod_step1['min_sign'];
			$delivery_array['max_sign']=$this->recruitment_creation->prod_step1['max_sign'];
			$delivery_array['price_min']=$this->recruitment_creation->prod_step1['price_min'];
			$delivery_array['price_max']=$this->recruitment_creation->prod_step1['price_max'];
			
			if(is_array($this->recruitment_creation->test_article['contribs_list']))
				$delivery_array['contribs_list']=implode(",",$this->recruitment_creation->test_article['contribs_list']);
			
			$delivery_array["view_to"] =implode(",",$this->recruitment_creation->prod_step1['view_to']);// 'sc,jc,jc0';

			$delivery_array["premium_option"] = $this->recruitment_creation->prod_step1['premium_option'];
			$delivery_array["premium_total"] = $this->recruitment_creation->prod_step1['premium_total'];

			$delivery_array["participation_time"] = (($this->recruitment_creation->prod_step1['participation_time_hour']*60)+($this->recruitment_creation->prod_step1['participation_time_min']));
			
			$delivery_array["selection_time"] = (($this->recruitment_creation->prod_step1['selection_hour']*60)+($this->recruitment_creation->prod_step1['selection_min'])); //newly added

			//Submit time
			$delivery_array["submit_option"] = 'hour';
			$delivery_array['subjunior_time']=$this->configval["jc0_time"]/60;
			$delivery_array['junior_time']=$this->configval["jc_time"]/60;
			$delivery_array['senior_time']=$this->configval["sc_time"]/60;

			//Resubmit time
			$delivery_array['resubmit_option']='hour';
			$delivery_array['jc0_resubmission']=$this->configval["jc0_resubmission"]/60;
			$delivery_array['jc_resubmission']=$this->configval["jc_resubmission"]/60;
			$delivery_array['sc_resubmission']=$this->configval["sc_resubmission"]/60;

			$delivery_array["file_name"] = $this->recruitment_creation->test_article['writing_spec_file_name'];
			$delivery_array["filepath"] = $this->recruitment_creation->test_article['writing_spec_file_path'];

			$delivery_array["created_by"]='BO';
			$delivery_array["created_user"]=$this->adminLogin->userId;
			$delivery_array["status_bo"] = "active";
			$delivery_array["updated_at"] = date('Y-m-d');
			$delivery_array["published_at"] = time();

			$delivery_array["AOtype"]=$this->recruitment_creation->prod_step1['AOtype'];
			$delivery_array["plagiarism_check"]="yes";
			$delivery_array["writer_notify"]='yes';
			//recruitment brief
			$delivery_array["recruitment_file_name"] = $this->recruitment_creation->prod_step1['recruitment_spec_file_name'];
			$delivery_array["recruitment_file_path"] = $this->recruitment_creation->prod_step1['recruitment_spec_file_path'];

			//Correction
			$delivery_array["correction"]=$this->recruitment_creation->test_article['correction'];
			if($this->recruitment_creation->test_article['correction']=='external' || $this->recruitment_creation->test_article['correction']=='multi_external')
			{			
				$delivery_array["correction_type"]=$this->recruitment_creation->prod_step1['correction_type'];
				$delivery_array["correction_pricemin"]=$this->recruitment_creation->test_article['correction_pricemin'];
				$delivery_array["correction_pricemax"]=$this->recruitment_creation->test_article['correction_pricemax'];
				$delivery_array["correction_participation"]=(($this->recruitment_creation->test_article['correction_participation_hour']*60)+($this->recruitment_creation->test_article['correction_participation_min']));
				
				if($this->recruitment_creation->test_article['correction']=='external')
					$delivery_array["correction_selection_time"] = (($this->recruitment_creation->test_article['correction_selection_hour']*60)+($this->recruitment_creation->test_article['correction_selection_min']));			//newly added
				
				//corrector submission times
				$delivery_array['correction_jc_submission']=$this->configval["correction_jc_submission"]/60;
				$delivery_array['correction_sc_submission']=$this->configval["correction_sc_submission"]/60;
				$delivery_array['correction_submit_option']='hour';

				//corrector resubmission times
				$delivery_array['correction_jc_resubmission']=$this->configval["correction_jc_resubmission"]/60;
				$delivery_array['correction_sc_resubmission']=$this->configval["correction_sc_resubmission"]/60;
				$delivery_array['correction_resubmit_option']='hour';

				$delivery_array["correction_file"]=$this->recruitment_creation->test_article['correction_spec_file_path'];
				$delivery_array["corrector_mail"]="yes";
				$delivery_array["correction_type"]=$this->recruitment_creation->prod_step1['correction_type'];
				$delivery_array['corrector_list']='CB';
				$delivery_array["corrector_notify"]='yes';
				$delivery_array["min_mark"]=$this->recruitment_creation->test_article['min_mark'];

				//Added new columns
				$delivery_array["corrector_pricedisplay"]='yes';
				if($this->delivery_creation->test_article['plag_excel_file']=='yes')
				{
					$delivery_array["column_xls"]=$this->delivery_creation->test_article['xls_columns'];
					//Added new columns
					$delivery_array["plag_xls"]=$this->delivery_creation->test_article['plag_xls'];
				}
			}

			//publishtime and notifications
			if($this->recruitment_creation->prod_step1['publish_now']!="yes" && $this->recruitment_creation->prod_step1['count_down_start']!="")
				$delivery_array["publishtime"]=$this->recruitment_creation->prod_step1['count_down_start'];

			$delivery_array["mailsubject"]=isodec($this->recruitment_creation->prod_step3['mailsubject']);
			$delivery_array["mailcontent"]=$this->recruitment_creation->prod_step3['mailcontent'];
			$delivery_array["missioncomment"]=$this->recruitment_creation->prod_step3['missioncomment'];

			if($this->recruitment_creation->prod_step1['publish_now']=="yes" && $delivery_array["AOtype"]=="public")
				$delivery_array["mailnow"]="yes";

			if($this->recruitment_creation->prod_step3['send_email']=="yes"){$delivery_array["mail_send"]="yes";}else{$delivery_array["mail_send"]="no";};

			if($this->recruitment_creation->prod_step1['product']=="translation")
			{
				$delivery_array["publish_language"]=$this->recruitment_creation->prod_step1['language_dest'];
				$delivery_array["language"] = $this->recruitment_creation->prod_step1['language_dest'];
			}
			else
			{
			$delivery_array["publish_language"]=$this->recruitment_creation->prod_step1['language'];
				$delivery_array["language"] = $this->recruitment_creation->prod_step1['language'];	
			}

			$delivery_array["missiontest"]="yes";//recruitment delivery
			$delivery_array["pricedisplay"]="yes"; 	
			//Added new columns
			$delivery_array["test_article"]='yes';//$this->recruitment_creation->prod_step1['test_article'];
			$delivery_array["delivery_time_frame"]=$this->recruitment_creation->prod_step1['delivery_time_frame'];
			$delivery_array["delivery_period"]=$this->recruitment_creation->prod_step1['delivery_period'];
			$delivery_array["max_articles_per_contrib"]=$this->recruitment_creation->prod_step1['max_articles_per_contrib'];
			$delivery_array["num_hire_writers"]=$this->recruitment_creation->prod_step1['num_hire_writers'];			
			$delivery_array["editorial_chief_review"]=isodec($this->recruitment_creation->prod_step1['editorial_chief_review']);
			$delivery_array["mission_volume"]=$this->recruitment_creation->prod_step1['volume'];
			$delivery_array["volume_option"]=$this->recruitment_creation->prod_step1['volume_option'];
			if($delivery_array["volume_option"]=='multi')
				$delivery_array["volume_option_multi"]=$this->recruitment_creation->prod_step1['volume_option_multi'];

			$delivery_array["free_article"]=$this->recruitment_creation->test_article['free_article'];
			//if($this->recruitment_creation->test_article['free_article']=='no')
			//$delivery_array["test_article_price"]=$this->recruitment_creation->test_article['article_price'];

			//send emails from Bo user or service
			$delivery_array["mail_send_from"]=$notificationParams['mail_from'];
			
				
			if(count($this->recruitment_creation->prod_step1['quiz_data'])>0)
			{
				$delivery_array['link_quiz']='yes';
				$delivery_array['quiz']=$this->recruitment_creation->prod_step1['quiz_data']['quiz'];
				$delivery_array['quiz_marks']=$this->recruitment_creation->prod_step1['quiz_data']['min_good_answer'];
				$delivery_array['quiz_duration']=$this->recruitment_creation->prod_step1['quiz_data']['quiz_duration'];
			}
			//echo "<pre>";print_r($this->recruitment_creation->prod_step1['quiz_data']);
			//echo "<pre>";print_r($delivery_array);exit;
			
			$delivery_identifier=$delivery_obj->insertDelivery($delivery_array);
			
			if($this->recruitment_creation->prod_step1['total_article']>0 && $delivery_identifier)
			{
				$total_article=count($this->recruitment_creation->prod_step2['articles']);

				$total_amount=0;
				$final_array_contribs=array();
				
				for($i=1;$i<=$total_article;$i++)
				{
					$article_obj=new Ep_Quote_Delivery();

					$article_details=$this->recruitment_creation->prod_step2['articles'][$i];

					//Insert Article
					$article_array = array(); 			
					$article_array["delivery_id"]= $delivery_identifier;
					$article_array["title"] 	 = utf8dec($article_details['title']);
					//$article_array["language"] 	 = $this->recruitment_creation->prod_step1['language'];
					$article_array["category"]   = $this->recruitment_creation->prod_step1['category'];
					$article_array["type"]       = $this->recruitment_creation->prod_step1['type'];
					$article_array["currency"]       = $this->recruitment_creation->prod_step1['currency'];
					$article_array['nbwords']    = $this->recruitment_creation->prod_step1['max_sign'];
					$article_array["sign_type"]  = $this->recruitment_creation->prod_step1['signtype'];
					$article_array["num_min"]	 = $this->recruitment_creation->prod_step1['min_sign'];
					$article_array["num_max"] 	 = $this->recruitment_creation->prod_step1['max_sign'];
					$article_array["price_min"]  = currencyToDecimal($article_details['price_min']);
					$article_array["price_max"]  = currencyToDecimal($article_details['price_max']);
					$article_array["price_final"]= currencyToDecimal($article_details['price_max']);
					$article_array["status"]	 = "new";  
					$article_array["created_by"] ='BO';
					if($this->recruitment_creation->prod_step1['product']=="translation")
					{
						$article_array["publish_language"]=$this->recruitment_creation->prod_step1['language_dest'];
						$article_array["language"] 	 = $this->recruitment_creation->prod_step1['language_dest'];
					}
					else
					{
					$article_array["publish_language"]=$this->recruitment_creation->prod_step1['language'];
						$article_array["language"] 	 = $this->recruitment_creation->prod_step1['language'];
					}
					$article_array["contrib_percentage"]='100';
					$article_array["paid_status"]='paid';

					$total_amount+=$article_array["price_max"];

					//Contributors list			
					if($this->recruitment_creation->prod_step1['AOtype']=="private")
					{
					  $article_array["contribs_list"]=implode(",",$article_details['contribs_list']);
					  $final_array_contribs[]=$article_details['contribs_list'];
					}
						
					//Correction
					if($this->recruitment_creation->test_article['correction']=='external' || $this->recruitment_creation->test_article['correction']=='multi_external')
					{
						$article_array["correction"]="yes";
						if($this->recruitment_creation->test_article['correction']=='external')
							$article_array["correction_type"]='extern';
						else if($this->recruitment_creation->test_article['correction']=='multi_external')
							$article_array["correction_type"]='multi_external';
							
						$article_array["correction_pricemin"]=currencyToDecimal($article_details['correction_pricemin']);
						$article_array["correction_pricemax"]=currencyToDecimal($article_details['correction_pricemax']);
						$article_array["correction_participation"]=($delivery_array["correction_participation"]);
						
						
						//Submit time
						if($article_details['correction_submit_option']=='min')
							$csub_multiple = 1;
						elseif($article_details['correction_submit_option']=='hour')
							$csub_multiple = 60;
						elseif($article_details['correction_submit_option']=='day')
							$csub_multiple = 60*24;					
						
						$article_array['correction_jc_submission']=$article_details['correction_jc_submission']*$csub_multiple;
						$article_array['correction_sc_submission']=$article_details['correction_sc_submission']*$csub_multiple;
						$article_array['correction_submit_option']=$article_details['correction_submit_option'];
						
						//ReSubmit time
						if($article_details['correction_resubmit_option']=='min')
							$crsub_multiple = 1;
						elseif($article_details['correction_resubmit_option']=='hour')
							$crsub_multiple = 60;
						elseif($article_details['correction_resubmit_option']=='day')
							$crsub_multiple = 60*24;	
						
						$article_array["correction_jc_resubmission"]=$article_details['correction_jc_resubmission']*$crsub_multiple;
						$article_array["correction_sc_resubmission"] = $article_details['correction_sc_resubmission']*$crsub_multiple;
						$article_array["correction_resubmit_option"] = $article_details['correction_resubmit_option'];
						
						if($this->recruitment_creation->prod_step1['correction_type']=="private")
							$article_array["corrector_privatelist"]=implode(",",$article_details["corrector_privatelist"]);
							
						//new fields
						if($this->recruitment_creation->test_article['correction']=='external')
							$article_array["correction_selection_time"]=($delivery_array["correction_selection_time"]);
						
						$corrector_list=$article_details["corrector_list"];
						if(in_array('sc',$corrector_list) && in_array('jc',$corrector_list))
							$corrector_public='CB';
						else if(in_array('sc',$corrector_list))	
							$corrector_public='CSC';
						else if(in_array('jc',$corrector_list))	
							$corrector_public='CJC';
						$article_array["corrector_list"]=$corrector_public;

						$article_array["proofread_start"]=$article_details["proofread_start"];
						$article_array["proofread_end"]=$article_details["proofread_end"];
						
						//$article_array["proofread_start"]=$article_details["proofread_start"];
					}
					else
					{

					}
					if($this->recruitment_creation->prod_step1['publish_now']!="yes" && $this->recruitment_creation->prod_step1['count_down_start']!="")
						$publishtime=strtotime($this->recruitment_creation->prod_step1['count_down_start']);
					else
						$publishtime=time();
					$article_array["participation_expires"]=($publishtime + ($delivery_array["participation_time"]*60));
					
					$article_array["participation_time"] = $delivery_array["participation_time"];
					
					//Submit time
					if($article_details['submit_option']=='min')
						$sub_multiple = 1;
					elseif($article_details['submit_option']=='hour')
						$sub_multiple = 60;
					elseif($article_details['submit_option']=='day')
						$sub_multiple = 60*24;
			
					$article_array["submit_option"]=$article_details['submit_option'];
					$article_array["junior_time"] = $article_details['junior_time']*$sub_multiple;
					$article_array["senior_time"] = $article_details['senior_time']*$sub_multiple;
					$article_array["subjunior_time"] = $article_details['subjunior_time']*$sub_multiple;
						
					//Resubmit time
					if($article_details['resubmit_option']=='min')
						$resub_multiple = 1;
					elseif($article_details['resubmit_option']=='hour')
						$resub_multiple = 60;
					elseif($article_details['resubmit_option']=='day')
						$resub_multiple = 60*24;
			
					$article_array["resubmit_option"]=$article_details['resubmit_option'];
					$article_array["jc_resubmission"] = $article_details['jc_resubmission']*$resub_multiple;
					$article_array["sc_resubmission"] = $article_details['sc_resubmission']*$resub_multiple;
					$article_array["jc0_resubmission"] = $article_details['jc0_resubmission']*$resub_multiple;			
					
					/* $article_array["estimated_worktime"] = ($article_details['estimated_worktime']*60)+$article_details['estimated_worktime_min'];
					if($article_details['estimated_worktime_option'])
					$article_array["estimated_workoption"] = $article_details['estimated_worktime_option'];
					else
					$article_array["estimated_workoption"] = 'min'; */
					
					$article_array["column_xls"]=$this->recruitment_creation->test_article['xls_columns'];
					
					//new fields
					//$article_array["selection_time"]=($delivery_array["selection_time"]);
					$article_array["view_to"]=$delivery_array["view_to"];

					//echo "<pre>";print_r($article_array);exit;
					$article_obj->insertArticle($article_array);
				}
			}

			//finalise writerlist to send email if ao is private
			if(count($final_array_contribs)>0)
			{
				$final_array_contribs = array_map("unserialize", array_unique(array_map("serialize", $final_array_contribs)));
				$final_array_contribs=array_values($final_array_contribs);
			}
			//echo "<pre>";print_r($final_array_contribs);exit;

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
				$data['user_id']=$this->recruitment_creation->prod_step1['user_id'];
				$data['amount_paid']=$pay_amount;
				$data['type']='instant';
				$data['pay_type']='BO';
				$invoiceId=$payart_obj->insertPayment_article($data);
                $delivery_obj->updatePaidarticle($delivery_identifier,$invoiceId);


                //ArticleHistory Insertion
				$hist_obj = new Ep_Delivery_ArticleHistory();
				$action_obj = new EP_Delivery_ArticleActions();
				$history1=array();
				$history1['user_id']=$this->adminLogin->userId;
				$history1['article_id']=$delivery_identifier;
				$sentence1=$action_obj->getActionSentence(1);					
						
					if($delivery_array['AOtype']=='public')
						$AO_type='<b>Public</b>';
					else
						$AO_type='<b>Private</b>';						
					
				$AO_name='<a href="/followup/delivery?client_id='.$this->recruitment_creation->prod_step1['user_id'].'&ao_id='.$delivery_identifier.'&submenuId=ML13-SL4" target="_blank"><b>'.$this->recruitment_creation->prod_step1['title'].'</b></a>';

				$user_obj=new Ep_User_Client();
				$detailsC=$user_obj->getClientName($this->recruitment_creation->prod_step1['user_id']);
				$client_name='<b>'.$detailsC[0]['company_name'].'</b>';
				
				$project_manager_name='<b>'.ucfirst($this->adminLogin->loginName).'</b>';
				$actionmessage=strip_tags($sentence1[0]['Message']);
				eval("\$actionmessage= \"$actionmessage\";");
				
				$history1['stage']='creation';
				$history1['action_sentence']=$actionmessage;
				$hist_obj->insertHistory($history1);

                //sending email to writers if mail sending is true
                if($delivery_array["mail_send"]=='yes')
                { 
                    $parameters['editobject']=$this->recruitment_creation->prod_step3['mailsubject'];
                    $parameters['editmessage']=$this->recruitment_creation->prod_step3['mailcontent'];

                    $parameters['mail_from']=$notificationParams['mail_from'];
					//Mail sent only if it selected Now
					if($this->recruitment_creation->prod_step1['publish_now']=='yes')
					{
						if($delivery_array['AOtype']=='private')
						{
							$contributors=array_unique($final_array_contribs[0]);
							
							if(is_array($contributors) && count($contributors)>0)
							{
								$automailid=87;									
								foreach($contributors as $contributor)
								{									
									$this->messageToEPMail($contributor,$automailid,$parameters);
								}
							}
						}
						elseif($delivery_array['AOtype']=='public')
						{	
							//$searchParameters['language']=$this->recruitment_creation->prod_step1['language'];
							if($this->recruitment_creation->prod_step1['product']=="translation")
								$searchParameters['language']=$this->recruitment_creation->prod_step1['language_dest'];
							else	
								$searchParameters['language']=$this->recruitment_creation->prod_step1['language'];
							
							
							$writersList=$delivery_obj->getContributorsList($searchParameters);									
							
							if(is_array($writersList) && count($writersList)>0)
							{								
								$mailId=85;//											
								foreach($writersList as $contributor=>$email)
								{
									$this->messageToEPMail($contributor,$mailId,$parameters);										
								}
							}
							
						}					
						
					}
				}


                if($this->recruitment_creation->prod_step3['send_mission_comments'] && $this->recruitment_creation->prod_step3['missioncomment']!="")
				{
					$comm_obj=new Ep_User_AdComments();
					$article_obj=new Ep_Quote_Delivery();
					$artids=$article_obj->getArticles($delivery_identifier);
					for($a=0;$a<count($artids);$a++)
					{
						$commentarray=array();
						$commentarray['user_id']=$this->adminLogin->userId;;
						$commentarray['type']="article";
						$commentarray['type_identifier']=$artids[$a]['id'];
						$commentarray['comments']=isodec($this->recruitment_creation->prod_step3['missioncomment']);
						$comm_obj->InsertComment($commentarray);
					}
				}
                
				unset($this->recruitment_creation->prod_step1);			
				unset($this->recruitment_creation->prod_step2);
				unset($this->recruitment_creation->prod_step3);
				unset($this->recruitment_creation->test_article);

				$this->_redirect("/recruitment/prod-success?submenuId=ML13-SL2&contract_missionid=".$mission_id);	
		}	
	}
	//delivery created successfully
	public function prodSuccessAction()
	{
		header( "refresh:2;url=/followup/prod?submenuId=ML13-SL4&cmid=".$this->_request->getParam('contract_missionid'));		
		$this->render("recruitment-prod-success");
	}	
	
	//cotrib email content
	public function getcontribmailcontentAction()
	{		
		$automail=new Ep_Message_AutoEmails();
		$user_obj=new Ep_User_Client();		
		$mailid="";
		
			if($this->recruitment_creation->prod_step1['AOtype']=="private")
			{
				$mailid=87;
			}
			else
			{		
				if($this->recruitment_creation->prod_step1['publish_now']=='yes')
					$mailid=85;
				else
					$mailid=89;
					
			}
		
			
        $email=$automail->getAutoEmail($mailid);
		
		$participation_time=(($this->recruitment_creation->prod_step1['participation_time_hour']*60)+($this->recruitment_creation->prod_step1['participation_time_min']))*60;
		
		if($this->recruitment_creation->prod_step1['publish_now']=='yes')
		{			
			$expires=time()+(60*$participation_time);
			$submitdate_bo="<b>".strftime("%d/%m/%Y at %H:%M",$expires)."</b>";
		}
		else
		{	
			
			$expires=strtotime($this->recruitment_creation->prod_step2['publish_time'])+($participation_time);
			$submitdate_bo="<b>".strftime("%d/%m/%Y &agrave; %H:%M",$expires)."</b>";
		}

		$aowithlink='<a href="http://ep-test.edit-place.co.uk/contrib/aosearch">'.stripslashes($this->recruitment_creation->prod_step1['title']).'</a>';
		$sub=$email[0]['Object'];
		$Message=$email[0]['Message'];
		eval("\$Message= \"$Message\";");
		
		$subcon=$sub."#".$Message;
		$subcon=utf8_encode($subcon);
		echo $subcon;
		//echo $mailid;		
	}

	/**********************************************Mail code ****************************************/
	public function messageToEPMail($receiverId,$mailid,$parameters)
    {
        $automail=new Ep_Message_AutoEmails();
        $sc_limit='<b>'.$parameters['sc_limit'].'</b>';
        $jc_limit='<b>'.$parameters['jc_limit'].'</b>';
        $AO_title="<b>".$parameters['AO_title']."</b>";
        $AO_end_date='<b>'.$parameters['AO_end_date'].'</b>';
        $submitdate_bo="<b>".$parameters['submitdate_bo']."</b>";
        $articlewithlink='<a href="'.$parameters['articlename_link'].'">'.stripslashes($parameters['article_title']).'</a>';
        $aowithlink='<a href="'.$parameters['aoname_link'].'">'.stripslashes($parameters['AO_title']).'</a>';
		$poll_link='<a href="http://ep-test.edit-place.co.uk/client/emaillogin?user='.$user.'&hash='.$password.'&type='.$type.'&poll='.$parameters['poll'].'">ici</a>';
		$client_polllink='<a href="http://ep-test.edit-place.co.uk/client/devispremium?id='.$parameters['poll'].'">cliquant-ici</a>';
		$category=$parameters['category'];
		$poll_title='<b>'.$parameters['poll_title'].'</b>';
		$poll_enddate='<b>'.$parameters['poll_enddate'].'</b>';
        $clientcomment=$parameters['clientcomment'];
		$pollcategory='<b>'.$parameters['pollcategory'].'</b>';
        $poll_title='<b>'.$parameters['poll_title'].'</b>';
        $poll_enddate='<b>'.$parameters['poll_enddate'].'</b>';
        $articleclient_link  = '<a href="'.$parameters['clientartname_link'].'">'.stripslashes($parameters['AO_title']).'</a>';
        $client_link = '<a href="'.$parameters['clientartname_link'].'">Cliquant-ici</a>';
		$Recruitment='<b>'.$parameters['Recruitment'].'</b>';
		$contributor_name=$parameters['contributor_name'];
		
        $email=$automail->getAutoEmail($mailid);
        
		if($parameters['editobject']!="")
			$Object=$parameters['editobject'];
		else
			$Object=$email[0]['Object'];
		
		$Object=strip_tags($Object);
        eval("\$Object= \"$Object\";");
        if($parameters['editmessage']!="")
		{
			$Message=$parameters['editmessage'];
		}
		else
		{
			$Message=$email[0]['Message'];
			eval("\$Message= \"$Message\";");
		}
		//echo $receiverId."--".$Object."--".$Message;exit;
		
		//Added w.r.t sending email from PM a/c
		if($parameters['mail_from'])
			$mail_from_pm=$parameters['mail_from'];
		else
			$mail_from_pm=NULL;
		
		/**Inserting into EP mail Box**/
           $automail->sendMailEpMailBox($receiverId,$Object,$Message,$mail_from_pm);
    }
	
	
	/* Recruitment FollowUp */
	function followUpAction()
	{
		$step1Params=$this->_request->getParams();
		$recruitment_id=$step1Params['recruitment_id'];
		$contract_mission_id=$step1Params['cmid'];

		
		$recruit_obj=new Ep_Quote_Recruitment();	


		$misssionQuoteDetails=$recruit_obj->getRecruitmentQuoteDetails($contract_mission_id,$recruitment_id);

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
					
					$misssionQuoteDetails[$pkey]['launch'] = date("d M Y",$deliveryMission['recruitment_launch']);
					if($deliveryMission['proofread_end'])
						$misssionQuoteDetails[$pkey]['enddate'] = date("d M Y",strtotime($deliveryMission['proofread_end']));
					else
					{
						$days = ceil($misssionQuoteDetails[$pkey]['total_time']/(60*24));
						$misssionQuoteDetails[$pkey]['enddate'] = date("d M Y",strtotime("+$days days",$deliveryMission['recruitment_launch']));
					}

					$misssionQuoteDetails[$pkey]['max_participation_time']=$deliveryMission['max_participation_time'];
					$misssionQuoteDetails[$pkey]['max_submit_time']=$deliveryMission['max_submit_time'];

					//if($deliveryMission['max_submit_time'] > $deliveryMission['max_participation_time'])
						//$misssionQuoteDetails[$pkey]['global_recruitment_time']=$deliveryMission['max_submit_time'];
					//else					
					$misssionQuoteDetails[$pkey]['global_recruitment_time']=$deliveryMission['max_participation_time'];
					


					$participation_details =$recruit_obj->getRecruitmentParticipations($recruitment_id);
					
					if($participation_details)
					{				
						$qualified=0;
						$proofread_cnt=0;
						$hired_count=0;
						foreach($participation_details as $key => $value)
						{
							$participation_details[$key]['image'] = $this->check_file_exists($participation_details[$key]['user_id']);
							if($value['link_quiz']=='yes' && $value['quiz'] && $value['qualified']=='yes')
							{
								$qualified++;
							}
							if($value['link_quiz']=='yes' && $value['quiz'])
							{
								$this->_view->show_quiz='yes';
							}
							if($value['marks'])
								$participation_details[$key]['marks']=($value['marks']);
							
							if($value['current_stage']=='mission_test'|| $value['current_stage']=='corrector' || $value['current_stage']=='stage2' )
							{
								$proofread_cnt++;
							}

							if($value['is_hired']=='yes')
								$hired_count++;							


						}			
						
						$misssionQuoteDetails[$pkey]['participation_details']=$participation_details;
						$misssionQuoteDetails[$pkey]['quiz_qualified']=$qualified;
						$misssionQuoteDetails[$pkey]['proofread_cnt']=$proofread_cnt;
						$misssionQuoteDetails[$pkey]['hired_count']=$hired_count;
						
						$percentage=round(($hired_count/$deliveryMission['num_hire_writers'])*100);

						$misssionQuoteDetails[$pkey]['percentage']=$percentage;
						$misssionQuoteDetails[$pkey]['color']=getColor($percentage);						

						//$this->_view->participation_details = $participation_details;	
						//echo "<pre>";print_r($participation_details);exit;						
					}	
					else
					{
						$percentage=0;
						$misssionQuoteDetails[$pkey]['percentage']=$percentage;
						$misssionQuoteDetails[$pkey]['color']=getColor($percentage);
					}
			}
			//Check participation expired?
			$this->_view->recruitmentparticipationexpired=$recruit_obj->CheckParticipationExpired($recruitment_id);
			$this->_view->recruitmentQuoteDetails=$misssionQuoteDetails;			
			//echo "<pre>";print_r($misssionQuoteDetails);exit;
			$this->render('recruitment-follow-up');	
		}
		else
		{
			$this->_redirect("/followup/prod?submenuId=ML13-SL4&cmid=".$step1Params['cmid']);
		}

			
	}
	
	
	/* Check User Image Exists */
	function check_file_exists($name)
	{
		$filename = $this->_view->fo_path."/profiles/contrib/pictures/$name/".$name."_p.jpg";
		$file_headers = @get_headers($filename);
		if(stripos($file_headers[0],"404 Not Found") >0  || (stripos($file_headers[0], "302 Found") > 0 && stripos($file_headers[7],"404 Not Found") > 0)) 
		return "/images/editor-noimage_60x60.png";
		else
		return "/profiles/contrib/pictures/$name/".$name."_p.jpg";
	}
	function loadparticipationAction()
	{
		if($this->_request->isPost() && $_SERVER['HTTP_X_REQUESTED_WITH']=='XMLHttpRequest')
		{
			$request = $this->_request->getParams();
			$recruitment_id = $request['rid'];
			$checked_values = $request['value'];
			if($checked_values)
			{
			//	$where = " ( ";
				$explode = explode(',',$checked_values);
				if(in_array('sc',$explode))
				$where .= " OR u.profile_type='senior'";
				if(in_array('jc',$explode))
				$where .= " OR u.profile_type='junior'";
				if(in_array('jco',$explode))
				$where .= " OR u.profile_type='sub-junior'";
				//$where .= " )";
			}
			else
			$where = '';
			$recruitment_obj = new Ep_Quote_Recruitment();
			$participation_details = $recruitment_obj->getRecruitmentParticipations($recruitment_id,$where);
			if($participation_details)
			{				
				foreach($participation_details as $key => $value){
					$participation_details[$key]['image'] = $this->check_file_exists($participation_details[$key]['user_id']);
					if($value['link_quiz']=='yes' && $value['quiz'] && $value['qualified']=='yes')
					{
						$qualified++;
					}
					if($value['link_quiz']=='yes' && $value['quiz'])
					{
						$this->_view->show_quiz='yes';
					}
					if($value['marks'])
						$participation_details[$key]['marks']=($value['marks']);
				}				
			}
			$this->_view->participation_details = $participation_details;
			//echo "<pre>";print_r($participation_details);exit;
			$this->_view->currency = $request['currency'];
			$this->render('load-participants');
		}
	}

	/* Hire User */
	function hireUserAction()
	{
		if($this->_request->isPost())
		{
			$hireParams = $this->_request->getParams();

			//echo "<pre>";print_r($hireParams);exit;

			$recruitment_id=$hireParams['recruitment_id'];
			$cmid=$hireParams['cmid'];
	
			$recruitment_obj = new Ep_Quote_Recruitment();
			$delivery_obj=new Ep_Quote_Delivery();		

			$status=$hireParams['status'];

			if($recruitment_id)
			{
				$partcipants=array();
				$participantsDetails=$recruitment_obj->getRecruitmentParticipations($recruitment_id);
				foreach($participantsDetails as $detail)
				{
					$partcipant_id=$detail['rpid'];
					$update_recruitment['is_hired'] = 'no';
					$recruitment_obj->updateRecruitmentPartcipation($update_recruitment,$partcipant_id);

					//hired users					
					if(in_array($detail['rpid'],$hireParams['hire']) && $detail['user_id'])
					{
						$hired_writers[]=$detail['user_id'];
					}
				}

				if(count($hireParams['hire'])>0)
				{
					foreach($hireParams['hire'] as $row)
					{						
						$update['is_hired'] = 'yes';
						$update['updated_at'] = date("Y-m-d H:i");
						$recruitment_obj->updateRecruitmentPartcipation($update,$row);

						
						
					}
				}

				if($status=='closed' || isset($hireParams['close']))
				{
					$rec_array['recruitment_status']='closed';
					$rec_array['recruitment_closed_at']=date("Y-m-d H:i");
					$delivery_obj->updateDelivery($rec_array,$recruitment_id);

					if(count($hired_writers)>0)
					{
						$recruitment_details=$recruitment_obj->getRecruitmentQuoteDetails($cmid,$recruitment_id);
						$log_obj=new Ep_Quote_QuotesLog();
						//get hired writers names
						foreach($hired_writers as $writer)
						{
							$writer_details=$log_obj->getContributorDetails($writer);
							$hiredNames[]='<a href="/user/contributor-edit?submenuId=ML10-SL1&tab=viewcontrib&userId='.$writer_details[0]['identifier'].'"><b>'.$writer_details[0]['writer_name'].'</b></a>';
						}
						$hired_name=implode(", ",$hiredNames);

						$log_params['bo_user']=$this->adminLogin->userId;
						$log_params['recruitment_title']='<a href="/recruitment/follow-up?recruitment_id='.$recruitment_id.'&cmid='.$cmid.'&submenuId=ML13-SL4">'.$recruitment_details[0]['recruitment_title'].'</a>';
						$log_params['hired_name']=$hired_name;
						$log_params['quote_id']=$recruitment_details[0]['quote_identifier'];
						$log_params['version']=1;
						$log_params['contract_id']= $cmid;
						$log_params['mission_id']= $recruitment_id;
						$log_params['mission_type']= 'recruitment_prod';					
						$log_params['action']= 'recruitment_hired';

						$log_obj->insertLog(26,$log_params);
					}	
				}
				
			}

			if(isset($hireParams['close']) || $status=='closed')
				$this->_redirect("/followup/prod?submenuId=ML13-SL4&cmid=$cmid");
			else			
				$this->_redirect("/recruitment/follow-up?recruitment_id=$recruitment_id&cmid=$cmid&submenuId=ML13-SL4");
		}
	}
	//update recruitment name
    function updateRecruitmentnameAction()
    {
    	if($this->_request->isPost() && $_SERVER['HTTP_X_REQUESTED_WITH']=='XMLHttpRequest')
		{
    		$Params=$this->_request->getParams();
    		$recruitment_title=utf8_decode($Params['value']);
    		$this->recruitment_creation->prod_step1['title']=$recruitment_title;
    		exit;
    	}	
    }
    //quiz creation from recruitment step1
    function createQuizStep1Action()
    {
		$params = $this->_request->getParams();	
        $categories=$this->_arrayDb->loadArrayv2("EP_ARTICLE_CATEGORY", $this->_lang) ;
        $this->_view->categories = $categories;
		if(!$this->_view->category)
			$this->_view->category=$this->recruitment_creation->prod_step1['category'];       
        if($params['cmid'])
			$this->_view->qz_step1['cmid']=$this->QZ_creation->qz_step1['cmid']=$params['cmid'];		
		$this->_view->qz_step1=$this->QZ_creation->qz_step1;		
		//echo "<pre>";print_r($this->QZ_creation->qz_step1);		
		$this->render("recruitment-create-quiz-step1");
    }
	//save quiz step1 info
	public function saveQuizStep1Action()
    {
		$params = $this->_request->getParams();
		if($this->_request->isPost() && $_SERVER['HTTP_X_REQUESTED_WITH']=='XMLHttpRequest')
		{	
			$this->QZ_creation->qz_step1=$params;			
		}
    }
	 //quiz creation from recruitment step2
	public function createQuizStep2Action()
    {		
		$this->_view->qz_step1=$this->QZ_creation->qz_step1;
		//echo "<pre>";print_r($this->QZ_creation->qz_step1);		
		$this->render("recruitment-create-quiz-step2");		
	}
	//save quiz step2 info
	public function saveQuizStep2Action()
    {      
        if($this->_request->isPost() && $_SERVER['HTTP_X_REQUESTED_WITH']=='XMLHttpRequest')
		{
			$params=$this->_request->getParams();		
			foreach($params as $index=>$param)	
			{
				$params[$index]=utf8_decode($param);
			}			
			//echo "<pre>";print_r($params);exit;
			
			$this->QZ_creation->qz_step1['quizztitle']=utf8_decode($this->QZ_creation->qz_step1['quizztitle']);
			if($params['qn0']=="") :
				unset($this->QZ_creation->qz_step1) ;
				unset($this->QZ_creation->qz_step2) ;				
			else :
				$this->QZ_creation->qz_step2=$params;
				$obj = new Ep_Quizz_Quizz() ;
				$quiz_id=$obj->insertQuizz($this->QZ_creation->qz_step1, $this->QZ_creation->qz_step2, $this->adminLogin->userId) ;
				$this->_view->successMsg    = "Quizz cr&eacute;&eacute; avec succ&egrave;s" ;
			endif ;
			$this->recruitment_creation->prod_step1['quiz_data']['quiz_cat']=$this->QZ_creation->qz_step1['category'];
			$this->recruitment_creation->prod_step1['quiz_data']['quiz']=$quiz_id;
			$this->recruitment_creation->prod_step1['quiz_data']['min_good_answer']=$this->QZ_creation->qz_step1['correct_ans_count'];
			$this->recruitment_creation->prod_step1['quiz_data']['quiz_duration']=$this->QZ_creation->qz_step1['setuptime'];	
			unset($this->QZ_creation->qz_step1) ;
			unset($this->QZ_creation->qz_step2) ;
		}
    }
	function getCurrentDatetimeAction()
	{
		$count_down_end=(time()+($this->configval["participation_time"]*60));
		$end_date = date("Y-m-d H:i",$count_down_end);
		if($this->recruitment_creation->prod_step1['count_down_end']>$end_date)
			$end_date = $this->recruitment_creation->prod_step1['count_down_end'];
		$start_date = date("Y-m-d H:i");
		echo json_encode(array('start_date'=>$start_date,'end_date'=>$end_date));
	}
	/* Edit Recruitment */
	function editRecruitmentAction()
	{
		$request = $this->_request->getParams();
		if($request['ao_id'] && $request['client_id'] && $_SERVER['HTTP_X_REQUESTED_WITH']=='XMLHttpRequest')
		{
			$recruit_obj=new Ep_Quote_Recruitment();	
			$recruitDetails=$recruit_obj->getRecruitmentQuoteDetails($request['cmid'],$request['ao_id']);
			$cnt = 0;
			if($recruitDetails[0]['submit_option']=='day')
			{
				$recruitDetails[$cnt]['subjunior_time']=$recruitDetails[$cnt]['max_subjunior_time']/(24*60);
				$recruitDetails[$cnt]['junior_time']=$recruitDetails[$cnt]['max_junior_time']/(24*60);
				$recruitDetails[$cnt]['senior_time']=$recruitDetails[$cnt]['max_senior_time']/(24*60);
			}
			else if($recruitDetails[0]['submit_option']=='hour')
			{
				$recruitDetails[$cnt]['subjunior_time']=$recruitDetails[$cnt]['max_subjunior_time']/(60);
				$recruitDetails[$cnt]['junior_time']=$recruitDetails[$cnt]['max_junior_time']/(60);
				$recruitDetails[$cnt]['senior_time']=$recruitDetails[$cnt]['max_senior_time']/(60);
			}
			else
			{
				$recruitDetails[$cnt]['subjunior_time']=$recruitDetails[$cnt]['max_subjunior_time'];
				$recruitDetails[$cnt]['junior_time']=$recruitDetails[$cnt]['max_junior_time'];
				$recruitDetails[$cnt]['senior_time']=$recruitDetails[$cnt]['max_senior_time'];
			}
			//converting resubmit time in to corresponding options
			if($recruitDetails[0]['resubmit_option']=='day')
			{
				$recruitDetails[$cnt]['jc0_resubmission']=$recruitDetails[$cnt]['max_jc0_resubmission']/(24*60);
				$recruitDetails[$cnt]['jc_resubmission']=$recruitDetails[$cnt]['max_jc_resubmission']/(24*60);
				$recruitDetails[$cnt]['sc_resubmission']=$recruitDetails[$cnt]['max_sc_resubmission']/(24*60);
			}
			else if($recruitDetails[0]['resubmit_option']=='hour')
			{
				$recruitDetails[$cnt]['jc0_resubmission']=$recruitDetails[$cnt]['max_jc0_resubmission']/(60);
				$recruitDetails[$cnt]['jc_resubmission']=$recruitDetails[$cnt]['max_jc_resubmission']/(60);
				$recruitDetails[$cnt]['sc_resubmission']=$recruitDetails[$cnt]['max_sc_resubmission']/(60);
			}
			else
			{
				$recruitDetails[$cnt]['jc0_resubmission']=$recruitDetails[$cnt]['max_jc0_resubmission'];
				$recruitDetails[$cnt]['jc_resubmission']=$recruitDetails[$cnt]['max_jc_resubmission'];
				$recruitDetails[$cnt]['sc_resubmission']=$recruitDetails[$cnt]['max_sc_resubmission'];
			}
			if($recruitDetails[$cnt]['correction']=="external")
			{
				 // echo $aoDetails[0]['correction_file']; exit;
				$filearr = explode("/",$recruitDetails[$cnt]['correction_file']);
				$recruitDetails[$cnt]['crtfile_name'] = $filearr[2];
			}
			$this->_view->recruitDetails = $recruitDetails[0];
			$this->_view->prequest = $request;
			if($recruitDetails[$cnt]['link_quiz']=="yes")
			{
				$obj = new Ep_Quizz_Quizz() ;
				$quizz_details = $obj->quizzdetails($recruitDetails[$cnt]['quiz']);
				$this->_view->quizz_details = $quizz_details[0];
				$this->recruitment_creation->prod_step1['quiz_data']['quiz_cat'] = $recruitDetails[0]['quote_category'];
				$this->recruitment_creation->prod_step1['quiz_data']['quiz'] = $recruitDetails[0]['quiz'];
				$this->recruitment_creation->prod_step1['quiz_data']['min_good_answer'] = $recruitDetails[0]['quiz_marks'];
				$this->recruitment_creation->prod_step1['quiz_data']['quiz_duration'] = $recruitDetails[0]['quiz_duration'];
			}
			else
			{
				unset($this->recruitment_creation->prod_step1['quiz_data']);
			}
		/* 	echo "<PRE>";
			print_r($recruitDetails[0]);
			exit; */
			$this->render("edit-recruitment-popup");
		}
	}
	
	function updateRecruitmentAction()
	{
		$request = $this->_request->getParams();
		if($request['rid'] && $request['cmid'] && $this->_request->isPost())
		{
			$updateDel = $updateArt = array();
			$updateDel['title'] = $request['rtitle'];
			//Submit time
			if($request['submit_option']=='min')
				$multiple = 1;
			elseif($request['submit_option']=='hour')
				$multiple = 60;
			elseif($request['submit_option']=='day')
				$multiple = 60*24;			
			$updateArt['junior_time']  = $updateDel['junior_time'] = $request['junior_time'] * $multiple;
			$updateArt['subjunior_time'] = $updateDel['subjunior_time'] = $request['subjunior_time']  * $multiple;
			$updateArt['senior_time'] = $updateDel['senior_time'] = $request['senior_time']  * $multiple;
			$updateArt['submit_option'] = $updateDel['submit_option'] = $request['submit_option'];
			if($request['resubmit_option']=='min')
				$multiple = 1;
			elseif($request['resubmit_option']=='hour')
				$multiple = 60;
			elseif($request['resubmit_option']=='day')
				$multiple = 60*24;			
			$updateArt['jc0_resubmission'] = $updateDel['jc0_resubmission'] = $request['jc0_resubmission'] * $multiple;
			$updateArt['jc_resubmission'] = $updateDel['jc_resubmission'] = $request['jc_resubmission']  * $multiple;
			$updateArt['sc_resubmission'] = $updateDel['sc_resubmission'] = $request['sc_resubmission']  * $multiple;
			$updateArt['resubmit_option'] = $updateDel['resubmit_option'] = $request['resubmit_option'];
			/* Uploading Recruitment brief */
			if($_FILES['recruitmentbrief']['name'])
			{
				$realfilename=$_FILES['recruitmentbrief']['name'];//echo $realfilename;exit;
				$realfilename=frenchCharsToEnglish($realfilename);
				$ext=findexts($realfilename);
				$uploaddir = $this->fo_root_path.'client_spec/';		
				$client_id=$request['client_id'];
				$newfilename=$client_id.".".$ext;
				if(!is_dir($uploaddir.$client_id))
				{
					mkdir($uploaddir.$client_id,0777);
					chmod($uploaddir.$client_id,0777);
				}
				$uploaddir=$uploaddir.$client_id."/";
				$realfilename=trim($realfilename);
				$realfilename=str_replace(" ","_",$realfilename);
				$bname=basename($realfilename,".".$ext)."_".uniqid().".".$ext;
				$bname=isodec($bname);
				$file = $uploaddir . $bname;
				if (move_uploaded_file($_FILES['recruitmentbrief']['tmp_name'], $file))
				{
					$updateDel['recruitment_file_name']=$bname;
					$updateDel['recruitment_file_path']="/".$client_id."/".$bname;
					chmod($file,0777);			
				}
			}
			
			//Fileupload fo_root_path
			if($_FILES['uploadfile']['name'])
			{
				$realfilename=$_FILES['uploadfile']['name'];
				$ext=pathinfo($realfilename);
				
				$uploaddir = $this->fo_root_path.'/client_spec/';
				
				$client_id=$request['client_id'];
				$newfilename=$client_id.".".$ext["extension"];
				if(!is_dir($uploaddir.$client_id))
				{
					mkdir($uploaddir.$client_id,0777);
					chmod($uploaddir.$client_id,0777);
				}
				else
				{					
					chmod($uploaddir.$client_id,0777);
				}	
				
				$uploaddir = $uploaddir.$client_id."/";
				$bname=basename($realfilename,".".$ext["extension"])."_".uniqid().".".$ext["extension"];
				$bname=isodec($bname);
				//echo $bname;exit;
				$file = $uploaddir . $bname;
			
				if (move_uploaded_file($_FILES['uploadfile']['tmp_name'], $file))
				{
					chmod($file,0777);
					$updateDel['file_name']=$bname;
					$updateDel['filepath']="/".$client_id."/".$bname;
				}
			}
				//corrector spec Fileupload////////////////////
			if($_FILES['crtuploadfile']['name'])
			{
				$crtrealfilename=$_FILES['crtuploadfile']['name'];
				$ext=pathinfo($crtrealfilename);

				$uploaddir = $this->fo_root_path.'/correction_spec/';

				$client_id=$request['client_id'];
				$newfilename=$client_id.".".$ext["extension"];
				if(!is_dir($uploaddir.$client_id))
				{
					mkdir($uploaddir.$client_id,0777);
					chmod($uploaddir.$client_id,0777);
				}
				else
				{
					chmod($uploaddir.$client_id,0777);
				}

				$uploaddir=$uploaddir.$client_id."/";
				$bname=basename($crtrealfilename,".".$ext["extension"])."_".uniqid().".".$ext["extension"];
				$bname=isodec($bname);
				//echo $bname;exit;
				$file = $uploaddir . $bname;
				if (move_uploaded_file($_FILES['crtuploadfile']['tmp_name'], $file))
				{
					chmod($file,0777);
					$updateDel['correction_file']="/".$client_id."/".$bname;
				}
			}	
			
			if(count($this->recruitment_creation->prod_step1['quiz_data'])>0)
			{
				$updateDel['link_quiz']='yes';
				$updateDel['quiz']=$this->recruitment_creation->prod_step1['quiz_data']['quiz'];
				$updateDel['quiz_marks']=$this->recruitment_creation->prod_step1['quiz_data']['min_good_answer'];
				$updateDel['quiz_duration']=$this->recruitment_creation->prod_step1['quiz_data']['quiz_duration'];
			}
			else
			{
				$updateDel['link_quiz']='no';
				$updateDel['quiz']= "";
				$updateDel['quiz_marks']= "";
				$updateDel['quiz_duration']= "";
			}
			$query=" id='".$request['rid']."'";
			$deliveyObj=new EP_Ongoing_Delivery();
            $deliveyObj->updateDelivery($updateDel,$query);
			$articleObj=new EP_Ongoing_Article();
			$updateArt['participation_expires'] = strtotime($request['count_down_end']);
			$articleQuery=" delivery_id='".$request['rid']."'";
			$articleObj->updateArticle($updateArt,$articleQuery);
			$this->_helper->FlashMessenger("Details of Recruitment has been updated successfully");	
			$this->_redirect("/followup/prod?cmid=".$request['cmid']."&index=".$request['cindex']."&submenuId=ML13-SL4");
		}
	}
	
		public function stoprecruitmentAction()
	{
		if($_REQUEST['recruitment'])
		{
			$recruit_obj=new Ep_Quote_Recruitment();	
			$recruit_obj->stopRecruitment($_REQUEST['recruitment'],$_REQUEST['action']);
			echo 'stopped';
		}
	}
	
	public function hireparticipationAction()
	{
		if($_REQUEST['rpid'])
		{
			$recruit_obj=new Ep_Quote_Recruitment();
			$partdata=array();
			$partdata['is_hired']=$_REQUEST['hire'];
			$recruit_obj->updateRecruitmentPartcipation($partdata,$_REQUEST['rpid']);
			
			if($_REQUEST['hire']=='no')
			{ 
				$parameters['Recruitment']=	$recruit_obj->getRecruitmentDetail($_REQUEST['rid']);	
				$user_obj=new Ep_User_User();
				$parameters['contributor_name']=$user_obj->getUsername($_REQUEST['user']);
				$this->messageToEPMail($_REQUEST['user'],207,$parameters);
			}
		}
	}
	
	public function closerecruitmentAction()
	{
		$recruit_obj=new Ep_Quote_Recruitment();
		
		$delivery_obj=new Ep_Quote_Delivery();

		$rec_array['recruitment_status']='closed';
		$rec_array['recruitment_closed_at']=date("Y-m-d H:i");
		$delivery_obj->updateDelivery($rec_array,$_REQUEST['rid']);
					
		$participation_details =$recruit_obj->getRecruitmentParticipations($_REQUEST['rid']);
		
		if(count($participation_details)>0)
		{
			foreach($participation_details as $part_item)
			{
				if($part_item['is_hired']!='yes' && $part_item['is_hired']!='no')
				{
					$partdata['is_hired']='no';
					$recruit_obj->updateRecruitmentPartcipation($partdata,$part_item['rpid']);
					
					$parameters['Recruitment']=	$part_item['title'];	
					$user_obj=new Ep_User_User();
					$parameters['contributor_name']=$user_obj->getUsername($part_item['user_id']);
					//$this->messageToEPMail('142415937584986',207,$parameters);
					$this->messageToEPMail($part_item['user_id'],207,$parameters);
				}
			}
		}
	}
}

?>	