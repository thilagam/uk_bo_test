<?php
/**
 * Quotedelivery Controller for creation of Ao
 * @version 1.0
*/
 require_once('ContractmissionController.php');
class QuotedeliveryController extends ContractmissionController
{		
	public function init() 
	{
        parent::init();

        $this->adminLogin = Zend_Registry::get('adminLogin');
        $this->delivery_creation = Zend_Registry::get('AO_creation');
        $this->configval=$this->getConfiguredval(); 
        $this->_view->fo_base_path=$this->fo_base_path=$this->_config->path->fo_base_path;
        $this->fo_root_path = $this->_config->path->fo_root_path;
        //print_r($this->configval);
		//$this->_view->ebookerid = $this->ebookerid = $this->_config->wp->ebookerid;
		$this->_view->ebookerid = $this->ebookerid = $this->configval['ebooker_id'];
		Zend_Loader::loadClass('Ep_Ebookers_Stencils');
    }    

    public function autoDeliverySession($delivery_id,$mission_id)
    {
    	if($delivery_id)
    	{
    		$deliveryObj=new Ep_Quote_Delivery();
    		$deliveryDetails=$deliveryObj->getDeliveryDetails($delivery_id);
    		if($deliveryDetails)
    		{
    			//echo "<pre>";print_r($deliveryDetails);exit;	
    			foreach($deliveryDetails as $delivery)
    			{
					$this->delivery_creation->prod_step1['user_id']=$delivery['user_id'];
					$this->delivery_creation->prod_step1['client_name']=$delivery['company_name'];
					$this->delivery_creation->prod_step1['title']='';
					$this->delivery_creation->prod_step1['currency']=$delivery['currency'];
					
					$this->delivery_creation->prod_step1['price_min']=$delivery['price_min'];
					$this->delivery_creation->prod_step1['price_max']=$delivery['price_max'];
					//for validation
					$this->delivery_creation->prod_step1['price_min_valid']=$delivery['price_min'];
					$this->delivery_creation->prod_step1['price_max_valid']=$delivery['price_max'];

					$this->delivery_creation->prod_step1['type']=$delivery['type'];
					$this->delivery_creation->prod_step1['deli_anonymous']=$delivery['deli_anonymous'];
					$this->delivery_creation->prod_step1['language']=$delivery['language'];
					$this->delivery_creation->prod_step1['language_dest']=$delivery['language'];
					$this->delivery_creation->prod_step1['signtype']=$delivery['signtype'];
					$this->delivery_creation->prod_step1['min_sign']=$delivery['min_sign'];
					$this->delivery_creation->prod_step1['max_sign']=$delivery['max_sign'];
					$this->delivery_creation->prod_step1['category']=$delivery['category'];
					$this->delivery_creation->prod_step1['premium_total']=$delivery['premium_total'];
					$this->delivery_creation->prod_step1['premium_option']=$delivery['premium_option'];
					$this->delivery_creation->prod_step1['AOtype']=$delivery['AOtype'];
					$this->delivery_creation->prod_step1['correction_type']=$delivery['correction_type'];
					$this->delivery_creation->prod_step1['status_bo']=$delivery['status_bo'];
					$this->delivery_creation->prod_step1['created_by']=$delivery['created_by'];
					$this->delivery_creation->prod_step1['correction']=$delivery['correction'];

					$this->delivery_creation->prod_step1['correction_pricemin']=$delivery['correction_pricemin'];
					$this->delivery_creation->prod_step1['correction_pricemax']=$delivery['correction_pricemax'];
					//for validation
					$this->delivery_creation->prod_step1['correction_pricemin_valid']=$delivery['correction_pricemin'];
					$this->delivery_creation->prod_step1['correction_pricemax_valid']=$delivery['correction_pricemax'];

					$this->delivery_creation->prod_step1['volume']=$delivery['volume'];
					$this->delivery_creation->prod_step1['files_pack']=$delivery['files_pack'];
					$this->delivery_creation->prod_step1['total_article']=$delivery['total_article'];
					
					$remaining_articles=($delivery['volume']-($this->delivery_creation->prod_step1['total_article']*$this->delivery_creation->prod_step1['files_pack']));
					//$this->delivery_creation->prod_step1['remaining_articles']=$remaining_articles > 0 ? $remaining_articles : 0; 

					$this->delivery_creation->prod_step1['mission_end_days']=$delivery['mission_end_days'];

					// participation timings from config
					$this->delivery_creation->prod_step1['participation_time_hour']=floor($delivery["participation_time"]/60);
					$this->delivery_creation->prod_step1['participation_time_min']=($delivery["participation_time"]%60);
					// correction participation  timings from config
					$this->delivery_creation->prod_step1['correction_participation_hour']=floor($delivery["correction_participation"]/60);
					$this->delivery_creation->prod_step1['correction_participation_min']=($delivery["correction_participation"]%60);

					$this->delivery_creation->prod_step1['correction_selection_hour']=floor($delivery["correction_selection_time"]/60);
					$this->delivery_creation->prod_step1['correction_selection_min']=($delivery["correction_selection_time"]%60);
					$this->delivery_creation->prod_step1['selection_hour']=floor($delivery["selection_time"]/60);
					$this->delivery_creation->prod_step1['selection_min']=($delivery["selection_time"]%60);

					//writing price display
					$this->delivery_creation->prod_step1['pricedisplay']=$delivery["pricedisplay"];
					
					$this->delivery_creation->prod_step1['product']=$delivery['product'];
					//correction price display
					$this->delivery_creation->prod_step1['corrector_pricedisplay']=$delivery["corrector_pricedisplay"];

					$this->delivery_creation->prod_step1['urlsexcluded']=$delivery['urlsexcluded'];

					
					$this->delivery_creation->prod_step1['writing_spec_file_name']=$delivery["file_name"];
					$this->delivery_creation->prod_step1['writing_spec_file_path']=$delivery["filepath"];	
					$this->delivery_creation->prod_step1['correction_spec_file_path']=$delivery["correction_file"];
					$this->delivery_creation->prod_step1['correction_spec_file_name']=basename($delivery["correction_file"]);	

					/* set stencils */
					if($delivery["stencils_ebooker"]=='yes')
					{
						$this->delivery_creation->prod_step1['ebooker_cat_id'] = $delivery['ebooker_cat_id'];
						$ebooker_obj = new Ep_Ebookers_Stencils();
						$sampletexts = (array) $ebooker_obj->getStencils(array('cat_id'=>$delivery['ebooker_cat_id']));
						if(count($sampletexts))
							$this->delivery_creation->prod_step1['theme'] = $sampletexts[0]['theme_id'];
						$this->delivery_creation->prod_step1['sampletext'] = $delivery['ebooker_sampletxt_id']; 
						$this->delivery_creation->prod_step1['optional_tokens'] = (array)explode(",", $delivery['ebooker_tokenids']); 
					}
					//getting all article details for step2
					$articleDetails=$deliveryObj->getArticles($delivery_id);
					if($articleDetails)
					{
						foreach($articleDetails as $index=>$article)
						{
							$i=$index+1;
							if(!$this->delivery_creation->prod_step2['articles'][$i]['title'])
							{
								$this->delivery_creation->prod_step2['articles'][$i]['title']=$article['title'];

								$this->delivery_creation->prod_step2['articles'][$i]['price_min']=$article['price_min'];
								$this->delivery_creation->prod_step2['articles'][$i]['price_max']=$article['price_max'];
								$this->delivery_creation->prod_step2['articles'][$i]['correction_pricemin']=$article['correction_pricemin'];
								$this->delivery_creation->prod_step2['articles'][$i]['correction_pricemax']=$article['correction_pricemax'];
								
								if($article['view_to'])
									$this->delivery_creation->prod_step2['articles'][$i]['view_to']=explode(",",$article['view_to']);
								if($article['corrector_list'])
									$this->delivery_creation->prod_step2['articles'][$i]['corrector_list']=explode(",",$article['corrector_list']);
								
								
								//submission times
								$this->delivery_creation->prod_step2['articles'][$i]['subjunior_time']=$article["subjunior_time"]/60;
								$this->delivery_creation->prod_step2['articles'][$i]['junior_time']=$article["junior_time"]/60;
								$this->delivery_creation->prod_step2['articles'][$i]['senior_time']=$article["senior_time"]/60;
								$this->delivery_creation->prod_step2['articles'][$i]['submit_option']='hour';

								//re submission times
								$this->delivery_creation->prod_step2['articles'][$i]['jc0_resubmission']=$article["jc0_resubmission"]/60;
								$this->delivery_creation->prod_step2['articles'][$i]['jc_resubmission']=$article["jc_resubmission"]/60;
								$this->delivery_creation->prod_step2['articles'][$i]['sc_resubmission']=$article["sc_resubmission"]/60;
								$this->delivery_creation->prod_step2['articles'][$i]['resubmit_option']='hour';
									
								$this->delivery_creation->prod_step2['articles'][$i]['writing_start']=date("Y-m-d H:i");

								if(!$this->delivery_creation->prod_step2['articles'][$i]['writing_end'])
								{
									$participation_time=(($this->delivery_creation->prod_step1['participation_time_hour']*60)+$this->delivery_creation->prod_step1['participation_time_min'])*60;
									$selection_time=(($this->delivery_creation->prod_step1['selection_hour']*60)+$this->delivery_creation->prod_step1['selection_min'])*60;
									$submit_time=$this->delivery_creation->prod_step2['articles'][$i]['senior_time']*60*60;
									$this->delivery_creation->prod_step2['articles'][$i]['writing_end']=date("Y-m-d H:i",strtotime($this->delivery_creation->prod_step2['articles'][$i]['writing_start'])+$participation_time+$selection_time+$submit_time);
								}


								
								//corrector submission times
								$this->delivery_creation->prod_step2['articles'][$i]['correction_jc_submission']=$article["correction_jc_submission"]/60;
								$this->delivery_creation->prod_step2['articles'][$i]['correction_sc_submission']=$article["correction_sc_submission"]/60;
								$this->delivery_creation->prod_step2['articles'][$i]['correction_submit_option']='hour';

								//corrector resubmission times
								$this->delivery_creation->prod_step2['articles'][$i]['correction_jc_resubmission']=$article["correction_jc_resubmission"]/60;
								$this->delivery_creation->prod_step2['articles'][$i]['correction_sc_resubmission']=$article["correction_sc_resubmission"]/60;
								$this->delivery_creation->prod_step2['articles'][$i]['correction_resubmit_option']='hour';
												
								$this->delivery_creation->prod_step2['articles'][$i]['proofread_start']=$this->delivery_creation->prod_step2['articles'][$i]['writing_end'];	

								if(!$this->delivery_creation->prod_step2['articles'][$i]['proofread_end'])
								{
									$participation_time=(($this->delivery_creation->prod_step1['correction_participation_hour']*60)+$this->delivery_creation->prod_step1['correction_participation_min'])*60;
									$selection_time=(($this->delivery_creation->prod_step1['correction_selection_hour']*60)+$this->delivery_creation->prod_step1['correction_selection_min'])*60;
									$submit_time=$this->delivery_creation->prod_step2['articles'][$i]['correction_sc_submission']*60*60;
									$this->delivery_creation->prod_step2['articles'][$i]['proofread_end']=date("Y-m-d H:i",strtotime($this->delivery_creation->prod_step2['articles'][$i]['proofread_start'])+$participation_time+$selection_time+$submit_time);
								}


								if($article['contribs_list'])
									$this->delivery_creation->prod_step2['articles'][$i]['contribs_list']=explode(",",$article['contribs_list']);
								if($article['corrector_privatelist'])
									$this->delivery_creation->prod_step2['articles'][$i]['corrector_privatelist']=explode(",",$article['corrector_privatelist']);

								$this->delivery_creation->prod_step2['articles'][$i]['stick_calendar']='yes';
								$this->delivery_creation->prod_step2['articles'][$i]['article_id']=$i;

								$this->delivery_creation->prod_step2['articles'][$i]['nomoderation']=$article["nomoderation"];
								$this->delivery_creation->prod_step1['nomoderation']='no';
							}			
						}
					}	

					//final step details
					$this->delivery_creation->prod_step3['send_mission_comments']=$delivery['missioncomment'] ? 'yes' : 'no';
					$this->delivery_creation->prod_step3['missioncomment']=$delivery['missioncomment'];
					$this->delivery_creation->prod_step3['fb_send']=$delivery['fbcomment'] ? 'yes' : 'no';
					$this->delivery_creation->prod_step3['fbcomment']=$delivery['fbcomment'];
					$this->delivery_creation->prod_step3['send_email']=$delivery['mail_send'];
					$this->delivery_creation->prod_step3['mailsubject']=$delivery['mailsubject'];
					$this->delivery_creation->prod_step3['mailcontent']=$delivery['mailcontent'];	
					$this->delivery_creation->prod_step3['mail_from']=$delivery['mail_send_from'];
					
    			}
    			//echo "<pre>";print_r($this->delivery_creation->prod_step2['articles']);exit;
    		}
    		else
    		{
				$this->_redirect("/quotedelivery/delivery-prod1?mission_id=$mission_id&daction=new&submenuId=ML13-SL4");    			
    		}
    		
    	}
    	
    }

	public function deliveryProd1Action()
	{
		
		$step1Params=$this->_request->getParams();
		$mission_id=$step1Params['mission_id'];


		if($step1Params['daction']=='duplicate' && $step1Params['ao_id'] )
		{
				$this->autoDeliverySession($step1Params['ao_id'],$mission_id);
		}
		elseif($step1Params['daction']=='new')
		{

			unset($this->delivery_creation->prod_step1);			
			unset($this->delivery_creation->prod_step2);
			unset($this->delivery_creation->prod_step3);
			unset($this->delivery_creation->repeat_delivery);
		}	

		$deliveryObj=new Ep_Quote_Delivery();
        $bnp_obj = new Ep_Bnp_Bnp();

		if($mission_id)
		{
			$misssionQuoteDetails=$deliveryObj->getMissionQuoteDetails($mission_id);

			//echo "<pre>";print_r($misssionQuoteDetails);exit;

			if($misssionQuoteDetails)
			{	
				$conversion=1;
		
				foreach($misssionQuoteDetails as $pkey=>$deliveryMission)
				{
					//echo $deliveryMission['currency']."--".$deliveryMission['sales_suggested_currency'];
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


					//unset($this->delivery_creation->prod_step1);
					//Assign quote category/lang and other details to delivery
					if(count($this->delivery_creation->prod_step1)==0)
					{
						$this->delivery_creation->prod_step1['user_id']=$deliveryMission['client_id'];
						$this->delivery_creation->prod_step1['client_name']=$deliveryMission['company_name'];
						$this->delivery_creation->prod_step1['product_type_name']=$misssionQuoteDetails[$pkey]['product_type_name'];
						if($deliveryMission['product']=='translation')
							$title=str_ireplace("_new",'',$deliveryMission['company_name']).' - '.$misssionQuoteDetails[$pkey]['product_name'].' '.$misssionQuoteDetails[$pkey]['product_type_name'].' - '.$misssionQuoteDetails[$pkey]['language_source_name'].' > '.$misssionQuoteDetails[$pkey]['language_dest_name'];
						else
							$title=str_ireplace("_new",'',$deliveryMission['company_name']).' - '.$misssionQuoteDetails[$pkey]['product_name'].' '.$misssionQuoteDetails[$pkey]['product_type_name'].' - '.$misssionQuoteDetails[$pkey]['language_source_name'];
						
						$this->delivery_creation->prod_step1['title']=$title;



						$this->delivery_creation->prod_step1['product']=$deliveryMission['product'];
						$this->delivery_creation->prod_step1['currency']=$deliveryMission['currency']; //currency from contract missions
						
						$this->delivery_creation->prod_step1['price_min']=($deliveryMission['min_cost']*$conversion);
						$this->delivery_creation->prod_step1['price_max']=($deliveryMission['writing']*$conversion);
						//for validation
						$this->delivery_creation->prod_step1['price_min_valid']=($deliveryMission['min_cost']*$conversion);
						$this->delivery_creation->prod_step1['price_max_valid']=($deliveryMission['writing']*$conversion);

						$this->delivery_creation->prod_step1['type']=$deliveryMission['product_type'];
						$this->delivery_creation->prod_step1['deli_anonymous']=0;
						$this->delivery_creation->prod_step1['language']=$deliveryMission['language_source'];
						$this->delivery_creation->prod_step1['language_dest']=$deliveryMission['language_dest'];
						$this->delivery_creation->prod_step1['signtype']='words';
						$this->delivery_creation->prod_step1['min_sign']=$deliveryMission['nb_words'];
						$this->delivery_creation->prod_step1['max_sign']=$deliveryMission['nb_words'];
						$this->delivery_creation->prod_step1['category']=$deliveryMission['quote_category'];
						$this->delivery_creation->prod_step1['premium_total']=1;
						$this->delivery_creation->prod_step1['premium_option']=13;
						$this->delivery_creation->prod_step1['AOtype']=$deliveryMission['privatedelivery']=='yes' ? 'private' : 'public';
						$this->delivery_creation->prod_step1['correction_type']=$deliveryMission['privatedelivery']=='yes' ? 'private' : 'public';
						$this->delivery_creation->prod_step1['status_bo']='active';
						$this->delivery_creation->prod_step1['created_by']='BO';
						$this->delivery_creation->prod_step1['correction']=$deliveryMission['correction'];
						$this->delivery_creation->prod_step1['nomoderation']='no';

						$this->delivery_creation->prod_step1['correction_pricemin']=0;
						$this->delivery_creation->prod_step1['correction_pricemax']=($deliveryMission['proofreading']*$conversion);
						//for validation
						$this->delivery_creation->prod_step1['correction_pricemin_valid']=0;
						$this->delivery_creation->prod_step1['correction_pricemax_valid']=($deliveryMission['proofreading']*$conversion);

						$this->delivery_creation->prod_step1['volume']=$deliveryMission['volume'];
						$this->delivery_creation->prod_step1['files_pack']=$deliveryMission['files_pack'];
						$this->delivery_creation->prod_step1['total_article']=round($deliveryMission['volume']/$deliveryMission['files_pack']);
						
						$remaining_articles=($deliveryMission['volume']-($this->delivery_creation->prod_step1['total_article']*$this->delivery_creation->prod_step1['files_pack']));
						//$this->delivery_creation->prod_step1['remaining_articles']=$remaining_articles > 0 ? $remaining_articles : 0; 

						$this->delivery_creation->prod_step1['mission_end_days']=$deliveryMission['mission_end_days'];

						// participation timings from config
						$this->delivery_creation->prod_step1['participation_time_hour']=floor($this->configval["participation_time"]/60);
						$this->delivery_creation->prod_step1['participation_time_min']=($this->configval["participation_time"]%60);
						// correction participation  timings from config
						$this->delivery_creation->prod_step1['correction_participation_hour']=floor($this->configval["correction_participation"]/60);
						$this->delivery_creation->prod_step1['correction_participation_min']=($this->configval["correction_participation"]%60);

						$this->delivery_creation->prod_step1['correction_selection_hour']=1;
						$this->delivery_creation->prod_step1['correction_selection_min']=0;
						$this->delivery_creation->prod_step1['selection_hour']=1;
						$this->delivery_creation->prod_step1['selection_min']=0;

						$this->delivery_creation->prod_step1['pricedisplay']='yes';
						$this->delivery_creation->prod_step1['corrector_pricedisplay']='yes';

						/*//writing price display
						if($deliveryMission['privatedelivery']=='yes')
							$this->delivery_creation->prod_step1['pricedisplay']='yes';
						else
							$this->delivery_creation->prod_step1['pricedisplay']='no';

						//correction price display
						if($deliveryMission['privatedelivery']=='yes')
							$this->delivery_creation->prod_step1['corrector_pricedisplay']='yes';
						else
							$this->delivery_creation->prod_step1['corrector_pricedisplay']='no';*/
					}
					else
					{
						//added w.r.t duplicate
						if(!$this->delivery_creation->prod_step1['client_name'])
							$this->delivery_creation->prod_step1['client_name']=$deliveryMission['company_name'];
						if(!$this->delivery_creation->prod_step1['volume'])
							$this->delivery_creation->prod_step1['volume']=$deliveryMission['volume'];
						if(!$this->delivery_creation->prod_step1['files_pack'])
							$this->delivery_creation->prod_step1['files_pack']=$deliveryMission['files_pack'];
						if(!$this->delivery_creation->prod_step1['mission_end_days'])
							$this->delivery_creation->prod_step1['mission_end_days']=$deliveryMission['mission_end_days'];
						
					}
					//tempo details
					$this->delivery_creation->prod_step1['tempo']=$deliveryMission['tempo'];						
					$this->delivery_creation->prod_step1['volume_max']=$deliveryMission['volume_max'];
					$this->delivery_creation->prod_step1['delivery_volume_option']=$deliveryMission['delivery_volume_option'];					
					$this->delivery_creation->prod_step1['tempo_length']=$deliveryMission['tempo_length'];
					$this->delivery_creation->prod_step1['tempo_length_option']=$deliveryMission['tempo_length_option'];
					$this->delivery_creation->prod_step1['prod_manager']=$deliveryMission['prod_manager'];

					$this->delivery_creation->prod_step1['mission_id']=$mission_id;
					if(!$this->delivery_creation->prod_step1['title'])
						$this->delivery_creation->prod_step1['title']='Production delivery '.date('dmy');
						
					$this->delivery_creation->prod_step1['delivery_end_date']=date('Y-m-d', strtotime($deliveryMission['assigned_at']. ' + '.$deliveryMission['mission_end_days'].' days'));

					
					//getting refusal reasons
					$template_type=$deliveryMission['product'];
					$template_obj=new Ep_Message_Template();
					$templatelist=$template_obj->getActiveValidationtemplates($template_type);
					if($templatelist!='NO')
					{
						$refusal_reasons=array();
						$selected_resaons=array();
						foreach($templatelist as $template)
						{
							$refusal_reasons[$template['identifier']]=$template['title'];

							if($template['selected']=='yes')
								$selected_resaons[]=$template['identifier'];
						}

						$total_reasons=count($refusal_reasons);

						
						$half_count=round($total_reasons/2);
						$reasons1=array_slice($refusal_reasons,0,$half_count,true);
						$reasons2=array_slice($refusal_reasons,$half_count,$total_reasons,true);
						
						$this->_view->reasons1=$reasons1;
						$this->_view->reasons2=$reasons2;
						$this->_view->refusal_reasons_max=$this->configval['refusal_reasons_max'];


						if(count($this->delivery_creation->prod_step1['refusalreasons'])==0)
						{
							$this->delivery_creation->prod_step1['refusalreasons']=$selected_resaons;
						}
						//echo "<pre>";print_r($selected_resaons);exit;
					}					

					//echo "<pre>";print_r($this->delivery_creation->prod_step1);exit;
					/* Start of Stencils */
					$this->_view->ebooker_delivery = false;
					if(($deliveryMission['client_id']==$this->ebookerid && $deliveryMission['stencils_ebooker'] == 'yes'))
					{
						$ebooker_obj = new Ep_Ebookers_Stencils();
						$stencils = (array) $ebooker_obj->getStencils(array('theme'=>true));
						$sthemes = array();						
						foreach($stencils as $stencil)
						{
							$sthemes[$stencil['theme_id']] = $stencil['theme_name'];
						}
						$this->_view->sthemes = $sthemes;
						$this->_view->ebooker_delivery = true;			
						
					}
					/* $this->delivery_creation->prod_step1['theme'] = $step1Params['theme'];
					$this->delivery_creation->prod_step1['category'] = $step1Params['category'];
					$this->delivery_creation->prod_step1['sampletext'] = $step1Params['sampletext']; */
					if($this->delivery_creation->prod_step1['theme'])
					{
						$scat = $ebooker_obj->getStencils(array('theme_id'=>$this->delivery_creation->prod_step1['theme'],'category'=>true));
						$scats = array();
						foreach($scat as $cat)
						{
							$scats[$cat['cat_id']] = $cat['category_name'];
						}
						$this->_view->scat = $scats;
						$sampletexts = $ebooker_obj->getSampleTexts(array('sample_id'=>$this->delivery_creation->prod_step1['sampletext']));
						$this->_view->sampletexts = $sampletexts[0]['description'];
					}
                    /* End of Stencils */
                    elseif($this->delivery_creation->prod_step1['city']){


                        $sampletexts = $bnp_obj->getBnpSampleText(array('city_id'=>$this->delivery_creation->prod_step1['city']));
                        $this->_view->sampletexts = $sampletexts[0]['title'];
                    }
                    $citys = (array) $bnp_obj->getCity();
                    $scitys = array();
                    foreach($citys as $city)
                    {
                        $scitys[$city['city_id']] = $city['city_name'];
                    }
                    $this->_view->scitys = $scitys;
                    $this->_view->bnp_delivery = true;
				}
                /* *** added on 24.02.2016 *** */


				$this->_view->prod_step1=$this->delivery_creation->prod_step1;
				$this->_view->misssionQuoteDetails=$misssionQuoteDetails;
				$this->_view->details='yes';
				//echo "<pre>";print_r($misssionQuoteDetails);exit;
				
				$this->render('delivery-prod1');
			}
			else
				$this->_redirect("/followup/prod?submenuId=ML13-SL4&cmid=".$mission_id);
		}	
	}
	//upload writing spec
	public function uploadWritingSpecAction()
	{		
		$realfilename=$_FILES['uploadfile']['name'];//echo $realfilename;exit;
		$realfilename=frenchCharsToEnglish($realfilename);
		$ext=findexts($realfilename);
		
		$uploaddir = $this->fo_root_path.'/client_spec/';		
		
		$client_id=$this->delivery_creation->prod_step1['user_id'];

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
			$this->delivery_creation->prod_step1['writing_spec_file_name']=$bname;
			$this->delivery_creation->prod_step1['writing_spec_file_path']="/".$client_id."/".$bname;

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
		
		$client_id=$this->delivery_creation->prod_step1['user_id'];
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

			$this->delivery_creation->prod_step1['correction_spec_file_name']=$bname;
			$this->delivery_creation->prod_step1['correction_spec_file_path']="/".$client_id."/".$bname;

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

			//echo "<pre>";print_r($step1Params);exit;

			$mission_id=$step1Params['mission_id'];
			
			if($mission_id)
			{

				$this->delivery_creation->prod_step1['AOtype']= $step1Params['AOtype'];
				$this->delivery_creation->prod_step1['correction_type']= $step1Params['correction_type'];
				$this->delivery_creation->prod_step1['price_min']= currencyToDecimal($step1Params['price_min']);
				$this->delivery_creation->prod_step1['price_max']= currencyToDecimal($step1Params['price_max']);
				$this->delivery_creation->prod_step1['participation_time_hour']= $step1Params['participation_time_hour'];
				$this->delivery_creation->prod_step1['participation_time_min']= $step1Params['participation_time_min'];
				
				$this->delivery_creation->prod_step1['selection_hour']= $step1Params['selection_hour'];
				$this->delivery_creation->prod_step1['selection_min']= $step1Params['selection_min'];	

				
				$this->delivery_creation->prod_step1['refusalreasons']=$step1Params['refusalreasons'];


				if($this->adminLogin->type=='superadmin')
					$this->delivery_creation->prod_step1['pricedisplay']=$step1Params['pricedisplay']=='yes' ? 'yes':'no';

				$this->delivery_creation->prod_step1['urlsexcluded']=$step1Params['urlsexcluded'];
				
				



				if($step1Params['correction'])
					$this->delivery_creation->prod_step1['correction']= $step1Params['correction'];


				if($step1Params['nomoderation'])
					$this->delivery_creation->prod_step1['nomoderation']=$step1Params['nomoderation'];
				else
					$this->delivery_creation->prod_step1['nomoderation']='no';


				$this->delivery_creation->prod_step1['correction_pricemin']= currencyToDecimal($step1Params['correction_pricemin']);
				$this->delivery_creation->prod_step1['correction_pricemax']= currencyToDecimal($step1Params['correction_pricemax']);
				$this->delivery_creation->prod_step1['correction_participation_hour']= $step1Params['correction_participation_hour'];
				$this->delivery_creation->prod_step1['correction_participation_min']= $step1Params['correction_participation_min'];
				
				$this->delivery_creation->prod_step1['correction_selection_hour']= $step1Params['correction_selection_hour'];
				$this->delivery_creation->prod_step1['correction_selection_min']= $step1Params['correction_selection_min'];

				if($this->adminLogin->type=='superadmin')
					$this->delivery_creation->prod_step1['corrector_pricedisplay']=$step1Params['corrector_pricedisplay']=='yes' ? 'yes':'no';
				
				
				$this->delivery_creation->prod_step1['plag_excel_file']= $step1Params['plag_excel_file'];
				$this->delivery_creation->prod_step1['plag_xls']= $step1Params['plag_xls'];
				$this->delivery_creation->prod_step1['xls_columns']= $step1Params['xls_columns'];
				
				$this->delivery_creation->prod_step1['correction_launch']= $step1Params['correction_launch']?'yes':'no';				
				$this->delivery_creation->prod_step1['files_pack']= $step1Params['files_pack'];
				$this->delivery_creation->prod_step1['total_article']=$step1Params['total_article'];

				if(!empty($step1Params['theme']))
				{
					$this->delivery_creation->prod_step1['theme'] = $step1Params['theme'];
					$this->delivery_creation->prod_step1['ebooker_cat_id'] = $step1Params['category'];
					$this->delivery_creation->prod_step1['sampletext'] = $step1Params['sampletext'];
					$this->delivery_creation->prod_step1['mandatory_token'] = $step1Params['mandatory'];
					$this->delivery_creation->prod_step1['optional_tokens'] = $step1Params['optional'];
				}
					/* *** added on 24.02.2016 *** */
                //related to BNP dev//
                if(!empty($step1Params['city']))
                {
                    $this->delivery_creation->prod_step1['city'] = $step1Params['city'];
                    $this->delivery_creation->prod_step1['sampletext'] = $step1Params['sampletext'];
                }
				if($this->delivery_creation->prod_step1['product']=='translation')
					$this->delivery_creation->prod_step1['sourcelang_nocheck']=$step1Params['sourcelang_nocheck']=='yes' ? 'yes':'no';
					
				if($this->delivery_creation->prod_step1['product']=='translation' &&  $this->delivery_creation->prod_step1['correction']=='external')
					$this->delivery_creation->prod_step1['sourcelang_nocheck_correction']=$step1Params['sourcelang_nocheck_correction']=='yes' ? 'yes':'no';	
				$this->_redirect("/quotedelivery/delivery-prod2?mission_id=".$mission_id);
			}				
		}	

	}

	//delivery prod step2 
	public function deliveryProd2Action()
	{
		$step2Params=$this->_request->getParams();
		$mission_id=$step2Params['mission_id'];

		$this->_view->timezone=date_default_timezone_get();

		//Always display the cuurent date in center of the calendar
		$day_week=date("N");
		if($day_week==7)
			$day_week=0;
		$cal_week=4+$day_week;
		if($cal_week>=7)
			$cal_week=$cal_week-7;
		$this->_view->day_week=$cal_week;

		//unset($this->delivery_creation->prod_step2);
		//echo "<pre>";print_r($this->delivery_creation->prod_step2['articles']);

		//assigning publish time
		if(!$this->delivery_creation->prod_step2['publish_time'])
		{
			$this->delivery_creation->prod_step2['publish_time']=date("Y-m-d H:i");
			$this->delivery_creation->prod_step2['publish_now']='yes';
		}	


		if($this->delivery_creation->prod_step1['total_article']>0 && $mission_id)
		{
			$total_article=$this->delivery_creation->prod_step1['total_article'];
			$files_pack=$this->delivery_creation->prod_step1['files_pack'];

			$type=$this->delivery_creation->prod_step1['type'];
			$product_type=$this->producttype_array[$type];

			$session_article_count=count($this->delivery_creation->prod_step2['articles']);

			$files_pack=$this->delivery_creation->prod_step1['files_pack'];

			/*price max and min valid for packs*/			
			$this->delivery_creation->prod_step1['price_max_packs']=($this->delivery_creation->prod_step1['price_max']*$files_pack);
			$this->delivery_creation->prod_step1['correction_pricemax_packs']=($this->delivery_creation->prod_step1['correction_pricemax']*$files_pack);
			
			
			//getting and assigning stencils if translation mission
			if($this->delivery_creation->prod_step1['product']=='translation' && $this->delivery_creation->prod_step1['mandatory_token'])
			{
				$articleStencils=array();
				$total_stencils=$total_article*$files_pack;
				$translation_lang=$this->delivery_creation->prod_step1['language_dest'];
				$token_id=$this->delivery_creation->prod_step1['mandatory_token'];
				
				$ebooker_obj = new Ep_Ebookers_Stencils();
				$translateStencils=$ebooker_obj->getStencilsForTranslation($translation_lang,$token_id,$total_stencils);
				if($translateStencils)
				{
					$articleStencils=array_chunk($translateStencils,$files_pack);
					if(count($translateStencils) <($total_article*$files_pack))
					{
						$this->_redirect('/quotedelivery/delivery-prod1?mission_id='.$mission_id);
					}
				}
				//echo "<pre>";print_r($articleStencils);exit;
			}


			for($i=1;$i<=$total_article;$i++)
			{
				if(!$this->delivery_creation->prod_step2['articles'][$i]['title'])				
				{
					$article_details['title']="Pack of ".$files_pack." ".$product_type." - ".$this->delivery_creation->prod_step1['client_name']." - ".$i;

					$article_details['price_min']=$article_details['price_min'] ? $article_details['price_min'] : $this->delivery_creation->prod_step1['price_min'];
					$article_details['price_max']=$article_details['price_max'] ? $article_details['price_max'] : $this->delivery_creation->prod_step1['price_max'];
					$article_details['correction_pricemin']=$article_details['correction_pricemin'] ? $article_details['correction_pricemin'] : $this->delivery_creation->prod_step1['correction_pricemin'];
					$article_details['correction_pricemax']=$article_details['correction_pricemax'] ? $article_details['correction_pricemax'] : $this->delivery_creation->prod_step1['correction_pricemax'];
					
					/*price multiplication with file packs*/
					$article_details['price_min']=($article_details['price_min']*$files_pack);
					$article_details['price_max']=($article_details['price_max']*$files_pack);
					$article_details['correction_pricemin']=($article_details['correction_pricemin']*$files_pack);
					$article_details['correction_pricemax']=($article_details['correction_pricemax']*$files_pack);


					if($this->delivery_creation->prod_step1['AOtype']=='public')
					{
						if(count($article_details['view_to'])==0)
						{
							if($this->delivery_creation->prod_step1['product']=='translation')
								$article_details['view_to']=array("sc","jc");
							else
								$article_details['view_to']=array("sc","jc","jc0");
						}
						if(count($article_details['corrector_list'])==0)
							$article_details['corrector_list']=array("sc","jc");
					}

					$article_details["nomoderation"]=$this->delivery_creation->prod_step1['nomoderation'];

					// writing timings from config	
					if(!$article_details['senior_time'])
					{
						//submission times
						$article_details['subjunior_time']=$this->configval["jc0_time"]/60;
						$article_details['junior_time']=$this->configval["jc_time"]/60;
						$article_details['senior_time']=$this->configval["sc_time"]/60;
						$article_details['submit_option']='hour';

						//re submission times
						$article_details['jc0_resubmission']=$this->configval["jc0_resubmission"]/60;
						$article_details['jc_resubmission']=$this->configval["jc_resubmission"]/60;
						$article_details['sc_resubmission']=$this->configval["sc_resubmission"]/60;
						$article_details['resubmit_option']='hour';
					}	
					if($articleParams['writing_start'] && !$article_details['writing_start'])
					{
						$article_details['writing_start']=date("Y-m-d H:i",strtotime($articleParams['writing_start']));
					}
					else if(!$article_details['writing_start'])
						$article_details['writing_start']=date("Y-m-d H:i");

					if(!$article_details['writing_end'])
					{
						$participation_time=(($this->delivery_creation->prod_step1['participation_time_hour']*60)+$this->delivery_creation->prod_step1['participation_time_min'])*60;
						$selection_time=(($this->delivery_creation->prod_step1['selection_hour']*60)+$this->delivery_creation->prod_step1['selection_min'])*60;
						$submit_time=$article_details['senior_time']*60*60;
						$article_details['writing_end']=date("Y-m-d H:i",strtotime($article_details['writing_start'])+$participation_time+$selection_time+$submit_time);
					}


					// correction timings from config
					if(!$article_details['correction_sc_submission'])
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
						$participation_time=(($this->delivery_creation->prod_step1['correction_participation_hour']*60)+$this->delivery_creation->prod_step1['correction_participation_min'])*60;
						$selection_time=(($this->delivery_creation->prod_step1['correction_selection_hour']*60)+$this->delivery_creation->prod_step1['correction_selection_min'])*60;
						$submit_time=$article_details['correction_sc_submission']*60*60;
						$article_details['proofread_end']=date("Y-m-d H:i",strtotime($article_details['proofread_start'])+$participation_time+$selection_time+$submit_time);
					}


					$article_details['stick_calendar']='yes';
					$article_details['article_id']=$i;

					$this->delivery_creation->prod_step2['articles'][$i]=$article_details;
					unset($article_details);		
					

				}				
				else
				{
					$article_details=$this->delivery_creation->prod_step2['articles'][$i];
					//if($article_details['price_max']>$this->delivery_creation->prod_step1['price_max_packs'])
					//{
						$article_details['price_min']=($this->delivery_creation->prod_step1['price_min']*$files_pack);
						$article_details['price_max']=($this->delivery_creation->prod_step1['price_max']*$files_pack);
						$article_details['correction_pricemin']=($this->delivery_creation->prod_step1['correction_pricemin']*$files_pack);
						$article_details['correction_pricemax']=($this->delivery_creation->prod_step1['correction_pricemax']*$files_pack);
						$this->delivery_creation->prod_step2['articles'][$i]=$article_details;
						unset($article_details);
					//}


				}
				
				//Assign stencils to articles
				if(is_array($articleStencils) && count($articleStencils)>0)
				{
					$this->delivery_creation->prod_step2['articles'][$i]['stencils_translate']=implode(",",$articleStencils[$i-1]);
				}

			}			
			//echo "<pre>";print_r($this->delivery_creation->prod_step2['articles']);exit;

			if($session_article_count>$total_article)
			{
				for($j=($total_article+1);$j<=$session_article_count;$j++)
				{
					unset($this->delivery_creation->prod_step2['articles'][$j]);
				}
			}
			
			$this->checkMaxTemoRespect(); //tempo related

			$this->_view->prod_step2=$this->delivery_creation->prod_step2;
			$this->_view->prod_step1=$this->delivery_creation->prod_step1;
			$this->_view->repeat_delivery=$this->delivery_creation->repeat_delivery;
			$this->render('delivery-prod2');
		}
		else
			$this->_redirect("/quotedelivery/delivery-prod1?mission_id=".$mission_id);
	}
	//check whether delivery respects the max tempo or not
	public function checkMaxTemoRespect()
	{
		$delivery_date=array();
		foreach($this->delivery_creation->prod_step2['articles'] as $articleDetails)
		{
			$delivery_date[]=strtotime($articleDetails['writing_end']);//related to tempo
			$delivery_date[]=strtotime($articleDetails['proofread_end']);//related to tempo
		}
		$delivery_end_date=max($delivery_date);
		if($delivery_end_date && $this->delivery_creation->prod_step1['tempo']=='max')
		{
			$tempo_length=$this->delivery_creation->prod_step1['tempo_length'];
			$length_option=$this->delivery_creation->prod_step1['tempo_length_option'];
			$tempo_volume_max=$this->delivery_creation->prod_step1['volume_max'];			
			$delivery_volume=($this->delivery_creation->prod_step1['total_article']*$this->delivery_creation->prod_step1['files_pack']);
			if($this->delivery_creation->prod_step2['publish_now']=='yes')
			{
				$publish_time=time();
			}
			else{
				$publish_time=strtotime($this->delivery_creation->prod_step2['publish_time']);
			}
			$deliveryTimeline=round((dateDiffHours($publish_time,$delivery_end_date)/24)); //delivery time
			if($length_option=='year')
				$multiple=365;
			else if($length_option=='month')
				$multiple=30;
			else if($length_option=='week')
				$multiple=7;
			else if($length_option=='days')
				$multiple=1;
			$tempo_length=$tempo_length*$multiple; //tempo time in days
			//$delivery_volue_tempo=(($delivery_volume/$deliveryTimeline)*$tempo_length);
			//echo $deliveryTimeline."--".$tempo_length."--".$delivery_volume."--".$tempo_volume_max."<br>";
			if(($deliveryTimeline <= $tempo_length) && ($delivery_volume <= $tempo_volume_max))			
				$this->delivery_creation->prod_step2['tempo_respected']='yes';
			else if(($deliveryTimeline <= $tempo_length) && ($delivery_volume >= $tempo_volume_max))		
				$this->delivery_creation->prod_step2['tempo_respected']='no';
			else if(($deliveryTimeline >= $tempo_length) && ($delivery_volume >= $tempo_volume_max))	
				$this->delivery_creation->prod_step2['tempo_respected']='yes';
		}
		else
			$this->delivery_creation->prod_step2['tempo_respected']='yes';
		//echo $this->delivery_creation->prod_step2['tempo_respected'];
		//exit;
	}

	//article creation pop in prod2 step
	public function createArticlePopAction()
	{
		$articleParams=$this->_request->getParams();

		$deliveryObj=new Ep_Quote_Delivery();
		$recruitmentObj=new Ep_Quote_Recruitment();

		$article_id=$articleParams['article_id'];

		if($article_id)
		{
			$article_details=$this->delivery_creation->prod_step2['articles'][$article_id];

			//default view of writer and corrector articles to all types
			//if($this->delivery_creation->prod_step1['AOtype']=='public')
			//{
				if(count($article_details['view_to'])==0)
				{
					if($this->delivery_creation->prod_step1['product']=='translation')
						$article_details['view_to']=array("sc","jc");
					else
					$article_details['view_to']=array("sc","jc","jc0");
				}
				if(count($article_details['corrector_list'])==0)
				{
					$article_details['corrector_list']=array("sc","jc");
				}
			//}

						//get hired users in recruitment stage
			if(!$article_details['contribs_list'] && $this->delivery_creation->prod_step1['AOtype']=='private')
			{
				$contract_mission_id=$this->delivery_creation->prod_step1['mission_id'];
				if($contract_mission_id)
				{
					$hiredProfiles=$recruitmentObj->getHiredParticipants($contract_mission_id);
					if($hiredProfiles)
					{
						foreach($hiredProfiles as $participate){
							$hiredUsers[]=$participate['user_id'];
						}
						$article_details['contribs_list']=$hiredUsers;
					}
				}
			}	

			//get all writers list
			$searchParameters['profile_type']=$article_details['view_to'];
			
			if($this->delivery_creation->prod_step1['product']=='translation')
			{
				$searchParameters['product']=$this->delivery_creation->prod_step1['product'];
				$searchParameters['language']=$this->delivery_creation->prod_step1['language_dest'];
				$searchParameters['language_source']=$this->delivery_creation->prod_step1['language'];
				$searchParameters['sourcelang_nocheck']=$this->delivery_creation->prod_step1['sourcelang_nocheck'];
				$writersList=$deliveryObj->getTranslatorList($searchParameters);	
			}			
			else
			{
				$searchParameters['language']=$this->delivery_creation->prod_step1['language'];
				$writersList=$deliveryObj->getContributorsList($searchParameters);
			}

			//echo "<pre>";print_r($writersList);exit;
			
			if($writersList)
			{
				if(count($article_details['contribs_list'])>0)
				{
					$contribs_list['SELECTED']=array();
					$contribs_list['OTHERS']=array();

					foreach($writersList as $identifier=>$writer)
					{					
						if(count($hiredUsers)>0)//added w.r.t only show hired users if mission has a recruitment
						{
							if(in_array($identifier,$hiredUsers))
							{
								if(in_array($identifier,$article_details['contribs_list']))					
									$contribs_list['SELECTED'][$identifier]=$writer;
								else
									$contribs_list['OTHERS'][$identifier]=$writer;
							}		
						}
						else
						{
							if(in_array($identifier,$article_details['contribs_list']))					
								$contribs_list['SELECTED'][$identifier]=$writer;
							else
								$contribs_list['OTHERS'][$identifier]=$writer;
						}


						
					}
					$this->_view->writersList=$contribs_list;
				}
				else
					$this->_view->writersList=$writersList;	
			}

			//get profile type counts of writers
			if($this->delivery_creation->prod_step1['product']=='translation')
				$profileCountWriter=$deliveryObj->getProfileTypeCountTranslators($searchParameters['language'],$searchParameters['language_source'],$searchParameters['sourcelang_nocheck']);
			else	
				$profileCountWriter=$deliveryObj->getProfileTypeCountWriters($searchParameters['language']);
				
			if($profileCountWriter)
				$this->_view->profileCountWriter=$profileCountWriter;	
			


			//echo "<pre>";print_r($writersList);

			//get all correctors list
			$csearchParameters['profile_type2']=$article_details['corrector_list'];
			
			if($this->delivery_creation->prod_step1['product']=='translation')
			{
				$csearchParameters['product']=$this->delivery_creation->prod_step1['product'];
				$csearchParameters['language']=$this->delivery_creation->prod_step1['language_dest'];
				$csearchParameters['language_source']=$this->delivery_creation->prod_step1['language'];
				$csearchParameters['sourcelang_nocheck_correction']=$this->delivery_creation->prod_step1['sourcelang_nocheck_correction'];
				$correctorsList=$deliveryObj->getCorrectorsTranslationList($csearchParameters);
			}			
			else
			{
				$csearchParameters['language']=$this->delivery_creation->prod_step1['language'];
				$correctorsList=$deliveryObj->getCorrectorsList($csearchParameters);
			}
			
			//$correctorsList=$deliveryObj->getCorrectorsList($csearchParameters);
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

			//get profile type counts of correctors
			if($this->delivery_creation->prod_step1['product']=='translation')
				$profileCountCorrectors=$deliveryObj->getProfileTypeCountTranslatorCorrectors($csearchParameters['language'],$csearchParameters['language_source'],$csearchParameters['sourcelang_nocheck_correction']);
			else
				$profileCountCorrectors=$deliveryObj->getProfileTypeCountCorrectors($csearchParameters['language']);
				
			if($profileCountCorrectors)
				$this->_view->profileCountCorrectors=$profileCountCorrectors;	

			


			//echo "<pre>";print_r($article_details);exit;			
			$this->_view->article_details=$article_details;
			$this->_view->prod_step1=$this->delivery_creation->prod_step1;		
			$this->render('delivery-create-article-popup');	
			
		}	
	}

	//ajax function to get writeres/corrector profiles
	function ajaxGetUserProfilesAction()
	{
		if($_SERVER['HTTP_X_REQUESTED_WITH']=='XMLHttpRequest')
		{
			$userParams=$this->_request->getParams();

			$deliveryObj=new Ep_Quote_Delivery();
			$user_type=$userParams['user_type'];


			if($user_type=='writer')
			{
				$view_to=$userParams['view_to'];

				//get all writers list
				$searchParameters['profile_type']=explode(",",$view_to);
				
				if($this->delivery_creation->prod_step1['product']=='translation')
				{
					$searchParameters['product']=$this->delivery_creation->prod_step1['product'];
					$searchParameters['language']=$this->delivery_creation->prod_step1['language_dest'];
					$searchParameters['language_source']=$this->delivery_creation->prod_step1['language'];
					$searchParameters['sourcelang_nocheck']=$this->delivery_creation->prod_step1['sourcelang_nocheck'];
					$writersList=$deliveryObj->getTranslatorList($searchParameters);	
				}			
				else
				{
					$searchParameters['language']=$this->delivery_creation->prod_step1['language'];
					$writersList=$deliveryObj->getContributorsList($searchParameters);
				}
				
				$options='';
				if($writersList)
				{

					foreach($writersList as $identifier=>$writer)
					{					
						$options.='<option value="'.$identifier.'">'.$writer.'</option>';
							
					}

					echo $options;exit;				
				}

			}
			else if($user_type=='corrector')
			{
				$corrector_list=$userParams['corrector_list'];

				//get all writers list
				$csearchParameters['profile_type2']=explode(",",$corrector_list);
				
				if($this->delivery_creation->prod_step1['product']=='translation')
				{
					$csearchParameters['product']=$this->delivery_creation->prod_step1['product'];
					$csearchParameters['language']=$this->delivery_creation->prod_step1['language_dest'];
					$csearchParameters['language_source']=$this->delivery_creation->prod_step1['language'];
					$csearchParameters['sourcelang_nocheck_correction']=$this->delivery_creation->prod_step1['sourcelang_nocheck_correction'];
					$correctorsList=$deliveryObj->getCorrectorsTranslationList($csearchParameters);
					//print_r($correctorsList);exit;
				}			
				else
				{
					$csearchParameters['language']=$this->delivery_creation->prod_step1['language'];
					$correctorsList=$deliveryObj->getCorrectorsList($csearchParameters);
				}

				//$correctorsList=$deliveryObj->getCorrectorsList($csearchParameters);

				$options='';
				if($correctorsList)
				{

					foreach($correctorsList as $identifier=>$corrector)
					{					
						$options.='<option value="'.$identifier.'">'.$corrector.'</option>';
							
					}

					echo $options;exit;				
				}

			}

		}
	}


	//article save pop up in prod2 step through ajax post
	public function ajaxSaveArticleAction()
	{
		if($this->_request-> isPost()  && $_SERVER['HTTP_X_REQUESTED_WITH']=='XMLHttpRequest')
		{
			$articleParams=$this->_request->getParams();
			//echo "<pre>";print_r($articleParams);exit;

			$article_id=$articleParams['article_id'];

			if($article_id)
			{
				
				foreach($this->delivery_creation->prod_step2['articles'] as $key=>$article)
				{	
					if($articleParams['articles_save_action']=='save_article')
					{
						$this->delivery_creation->prod_step2['articles'][$article_id]['title']=$articleParams['article_title'];
						if($article_id!=$key)
						{
							continue;
						}						
					}
					else if($articleParams['articles_save_action']=='save_all_articles')
					{						
						if($article_id==$key)
							$this->delivery_creation->prod_step2['articles'][$article_id]['title']=$articleParams['article_title'];

						$article_id=$key;
					}
					

					$save_details=$this->delivery_creation->prod_step2['articles'][$article_id];
					
					$save_details['article_id']=$article_id;
					
					
					
					//writing and correction prices
					$save_details['price_min']=$articleParams['price_min'];
					$save_details['price_max']=$articleParams['price_max'];									
					

					//re submission times
					$save_details['subjunior_time']=$articleParams['subjunior_time'];
					$save_details['junior_time']=$articleParams['junior_time'];
					$save_details['senior_time']=$articleParams['senior_time'];
					$save_details['submit_option']=$articleParams['submit_option'];

					$send_time=max(array($save_details['subjunior_time'],$save_details['junior_time'],$save_details['senior_time']));
					if($save_details['submit_option']=='min')	
						$send_time=$send_time/60;					
					else if($save_details['submit_option']=='day')	
						$send_time=$send_time*24;


					//re submission times
					$save_details['jc0_resubmission']=$articleParams['jc0_resubmission'];
					$save_details['jc_resubmission']=$articleParams['jc_resubmission'];
					$save_details['sc_resubmission']=$articleParams['sc_resubmission'];
					$save_details['resubmit_option']=$articleParams['resubmit_option'];

					//writing start and end dates
					$save_details['writing_start']=$this->delivery_creation->prod_step2['publish_time'];
					$participation_time=(($this->delivery_creation->prod_step1['participation_time_hour']*60)+$this->delivery_creation->prod_step1['participation_time_min'])*60;
					$selection_time=(($this->delivery_creation->prod_step1['selection_hour']*60)+$this->delivery_creation->prod_step1['selection_min'])*60;
					$submit_time=$send_time*60*60;
					$writing_end=date("Y-m-d H:i",strtotime($save_details['writing_start'])+$participation_time+$selection_time+$submit_time);					
					
					$save_details['writing_end']=$writing_end;
				
					
					if($this->delivery_creation->prod_step1['correction']=='external')
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

						$cparticipation_time=(($this->delivery_creation->prod_step1['correction_participation_hour']*60)+$this->delivery_creation->prod_step1['correction_participation_min'])*60;
						$cselection_time=(($this->delivery_creation->prod_step1['correction_selection_hour']*60)+$this->delivery_creation->prod_step1['correction_selection_min'])*60;
						$csubmit_time=$proodread_time*60*60;
						$proofread_end=date("Y-m-d H:i",strtotime($save_details['proofread_start'])+$cparticipation_time+$cselection_time+$csubmit_time);

						$save_details['proofread_end']=$proofread_end;

						//corrector resubmission times
						$save_details['correction_jc_resubmission']=$articleParams['correction_jc_resubmission'];
						$save_details['correction_sc_resubmission']=$articleParams['correction_sc_resubmission'];
						$save_details['correction_resubmit_option']=$articleParams['correction_resubmit_option'];
					}

					//private writers list and correctors list
					if($this->delivery_creation->prod_step1['AOtype']=='private')
					{
						$save_details['contribs_list']=$articleParams['contribs_list'];						
						$save_details['view_to']=$articleParams['view_to'];//array("sc","jc","jc0");
						
					}
					else if($this->delivery_creation->prod_step1['AOtype']=='public')
					{
						$save_details['contribs_list']=NULL;						
						$save_details['view_to']=$articleParams['view_to'];
					}

					if($this->delivery_creation->prod_step1['correction_type']=='private')
					{					
						$save_details['corrector_privatelist']=$articleParams['corrector_privatelist'];
						$save_details['corrector_list']=$articleParams['corrector_list'];//array("sc","jc");
					}
					else if($this->delivery_creation->prod_step1['correction_type']=='public')
					{					
						$save_details['corrector_privatelist']=NULL;
						$save_details['corrector_list']=$articleParams['corrector_list'];
					}

					

					$save_details['stick_calendar']='yes';

					//save all the submit details in to session
					$this->delivery_creation->prod_step2['articles'][$article_id]=$save_details;
					unset($save_details);
				}	

				//echo "<pre>";print_r($save_details);exit;
				$this->checkMaxTemoRespect(); //tempo related
				$tempo_respected=$this->delivery_creation->prod_step2['tempo_respected'];
				echo json_encode(array("status"=>"success","tempo_respect"=>$tempo_respected));
				//echo "success";exit;
			}
			else
			{
				echo json_encode(array("status"=>"error","tempo_respect"=>"yes"));
				//echo "error";exit;
			}


		}

	}
	//event json feed to the calendar from session
	public function calendarArticleEventsAction()
	{
		if(count($this->delivery_creation->prod_step2['articles']))
		{
			$events_array=array();
			foreach($this->delivery_creation->prod_step2['articles'] as $article)
			{
				if($article['stick_calendar']=='yes')
				{
					$event=array();
					
					$event['id']='article_'.$article['article_id'];
					$event['title']=$article['title'];
					$event['url']='/quotedelivery/create-article-pop?article_id='.$article['article_id'];

					$event['start']=$article['writing_start'];
					$event['end']=$article['writing_end'];

					$events_array[]=$event;


					if($this->delivery_creation->prod_step1['correction']=='external')
					{
						$event['id']='article_'.$article['article_id'];
						$event['title']='Proof Reading';
						$event['url']='/quotedelivery/create-article-pop?article_id='.$article['article_id'];

						$event['start']=$article['proofread_start'];
						$event['end']=$article['proofread_end'];
						$event['className']='proofread-event';
						
						$events_array[]=$event;
					}
				}
			}		

			
			//Added w.r.t Repeat delivery

			if($this->delivery_creation->prod_step2['repeat']=='yes')
			{
				$repeat_option=$this->delivery_creation->repeat_delivery['repeat_option'];
		    	$repeat_every=$this->delivery_creation->repeat_delivery['repeat_every'];
		    	$repeat_on=$this->delivery_creation->repeat_delivery['repeat_on'];    	
		    	$repeat_start=$this->delivery_creation->repeat_delivery['repeat_start'];
		    	$repeat_end=$this->delivery_creation->repeat_delivery['repeat_end'];
		    	$after_occurance=$this->delivery_creation->repeat_delivery['after_occurance'];
		    	$end_on=$this->delivery_creation->repeat_delivery['end_on'];
		    	
		    	//echo "<pre>";print_r($this->delivery_creation->repeat_delivery);
		    	if(count($repeat_on)>0)
		    	{
		    		$date_index=date('N');
		    		$array_1=array_slice($repeat_on, 0, $date_index);
		    		$array_2=array_slice($repeat_on, $date_index);

		    		$repeat_on=array_merge($array_2,$array_1);
		    	}

		    	//echo "<pre>";print_r($repeat_on);

		    			    		
	    		$weekday[1]="monday";
				$weekday[2]="tuesday";
				$weekday[3]="wednesday";
				$weekday[4]="thursday";
				$weekday[5]="friday";
				$weekday[6]="saturday";
				$weekday[7]="sunday";

	    		$all_events=$events_array;

	    		$repeat=true; //userd to loop the events of calendar
	    		$multiple=0;
	    		while($repeat)
	    		{
	    			
		    		foreach($all_events as $index=>$event)
					{
						if($event['className']!='proofread-event')
						{
							$time=date("H:i",strtotime($event['start']));						
							$timestamp_diff=(strtotime($event['end'])-strtotime($event['start']));

							if($repeat_option=='week' OR $repeat_option=='week_b')//weekly repeat
	    					{	
								if(count($repeat_on)>0)
								{
									foreach($repeat_on as $week_day)
									{
										$writer_event=$event;										
										$repeat_week=$multiple*$repeat_every;
										
										//echo "$repeat_start +($multiple*$repeat_every) week $weekday[$week_day]";//exit;										
										$from=date("Y-m-d",strtotime("$repeat_start +$repeat_week week $weekday[$week_day]"));
										//echo $from."<br>";

										if($from > $repeat_start)
										{
											$writer_event['start']=$from." ".$time;
											$writer_event['end']=date("Y-m-d H:i",(strtotime($writer_event['start'])+$timestamp_diff));
											$writer_event['className']='event-repeat';
											unset($writer_event['url']); //not giving the URL for repeat

											//check repeat end date condition
											if($repeat_end=='on' && $end_on)
											{
												if($end_on < $from)
												{
													$repeat=false;
													break;
												}
											}										

											$events_array[]=$writer_event;

											//for proofreading event
											$pf_index=$index+1;
											if($all_events[$pf_index]['className']=='proofread-event')
											{
												$pf_event=$all_events[$pf_index];
												$writing_correction_diff=(strtotime($all_events[$pf_index]['start'])-strtotime($event['end']));
												$correction_start_end_diff=(strtotime($all_events[$pf_index]['end'])-strtotime($all_events[$pf_index]['start']));
												
												$pf_event['start']=date("Y-m-d H:i",(strtotime($writer_event['end'])+$writing_correction_diff));
												$pf_event['end']=date("Y-m-d H:i",(strtotime($pf_event['start'])+$correction_start_end_diff));
												$pf_event['className']='proofread-event-repeat';
												unset($pf_event['url']); //not giving the URL for repeat
												$events_array[]=$pf_event;
											}
										}	
									}
								}
							}
							else if($repeat_option=='month') //monthly repeat
	    					{		    						
	    						$writer_event=$event;										
	    						$month_multi=($multiple+1);

								$repeat_month=$month_multi*$repeat_every;
								
								//echo "$repeat_start +($multiple*$repeat_every) week $weekday[$week_day]";exit;
								
								$from=date("Y-m-d",strtotime("$repeat_start +$repeat_month month"));
								if($from > $repeat_start)
								{
									$writer_event['start']=$from." ".$time;
									$writer_event['end']=date("Y-m-d H:i",(strtotime($writer_event['start'])+$timestamp_diff));
									$writer_event['className']='event-repeat';
									unset($events_array['url']); //not giving the URL for repeat
									//check repeat end date condition
									if($repeat_end=='on' && $end_on)
									{
										if($end_on < $from)
										{
											$repeat=false;
											break;
										}
									}									
									$events_array[]=$writer_event;

									//for proofreading event
									$pf_index=$index+1;
									if($all_events[$pf_index]['className']=='proofread-event')
									{
										$pf_event=$all_events[$pf_index];
										$writing_correction_diff=(strtotime($all_events[$pf_index]['start'])-strtotime($event['end']));
										$correction_start_end_diff=(strtotime($all_events[$pf_index]['end'])-strtotime($all_events[$pf_index]['start']));
										
										$pf_event['start']=date("Y-m-d H:i",(strtotime($writer_event['end'])+$writing_correction_diff));
										$pf_event['end']=date("Y-m-d H:i",(strtotime($pf_event['start'])+$correction_start_end_diff));
										$pf_event['className']='proofread-event-repeat';
										unset($pf_event['url']); //not giving the URL for repeat
										$events_array[]=$pf_event;
									}	
								}		
	    					}
	    					else if($repeat_option=='daily') //daily repeat
	    					{	
	    						$writer_event=$event;										
								$repeat_day=$multiple;//*$repeat_every;
								
								//echo "$repeat_start +$repeat_day day"."<br>";
								
								$from=date("Y-m-d",strtotime("$repeat_start +$repeat_day day"));
								//echo $multiple."--".$from."<br>";
								if($from > $repeat_start)
								{
									$writer_event['start']=$from." ".$time;
									$writer_event['end']=date("Y-m-d H:i",(strtotime($writer_event['start'])+$timestamp_diff));
									$writer_event['className']='event-repeat';
									unset($events_array['url']); //not giving the URL for repeat
									//check repeat end date condition
									if($repeat_end=='on' && $end_on)
									{
										if($end_on < $from)
										{
											$repeat=false;
											break;
										}
									}									
									$events_array[]=$writer_event;

									//for proofreading event
									$pf_index=$index+1;
									if($all_events[$pf_index]['className']=='proofread-event')
									{
										$pf_event=$all_events[$pf_index];
										$writing_correction_diff=(strtotime($all_events[$pf_index]['start'])-strtotime($event['end']));
										$correction_start_end_diff=(strtotime($all_events[$pf_index]['end'])-strtotime($all_events[$pf_index]['start']));
										
										$pf_event['start']=date("Y-m-d H:i",(strtotime($writer_event['end'])+$writing_correction_diff));
										$pf_event['end']=date("Y-m-d H:i",(strtotime($pf_event['start'])+$correction_start_end_diff));
										$pf_event['className']='proofread-event-repeat';
										unset($pf_event['url']); //not giving the URL for repeat
										$events_array[]=$pf_event;
									}	
								}		
	    					}	
						}						
				    }

				    $multiple++;

				    //condition to skip after N occurances
				    if($repeat_end=='after' && $multiple==($after_occurance))
				    	break;
				    else if($repeat_end=='never' && $multiple==10)
				    	break;

				    	
				}
			}

			//mission expected end date
			$contract_event['id']='contract';
			$contract_event['title']='Mission Expected End Date';
			$contract_event['start']=$this->delivery_creation->prod_step1['delivery_end_date'];
			$contract_event['className']='contract-event';
			$events_array[]=$contract_event;	    
			
			//print_r($events_array_new);
			//exit;			
			//echo "<pre>";print_r($events_array);exit;
			echo json_encode($events_array);exit;
		}

	}

	//ajax update calendar based on launch date
	public function ajaxUpdateCalendarAction()
	{
		if($this->_request-> isPost()  && $_SERVER['HTTP_X_REQUESTED_WITH']=='XMLHttpRequest')
		{
			$launchParams=$this->_request->getParams();

			$publish_now=$launchParams['publish_now'];

			if($publish_now=='yes')
			{
				$publish_time=date("Y-m-d H:i");
				$this->delivery_creation->prod_step2['publish_now']='yes';
			}
			else
			{
				$publish_time=$launchParams['publish_time'];
				$this->delivery_creation->prod_step2['publish_now']='no';				
			}
			$this->delivery_creation->prod_step2['publish_time']=$publish_time;

			if($publish_time)
			{
				if($this->delivery_creation->prod_step1['total_article']>0)
				{
					$total_article=$this->delivery_creation->prod_step1['total_article'];
					
					for($i=1;$i<=$total_article;$i++)
					{
						$article_details=$this->delivery_creation->prod_step2['articles'][$i];

						//updating writing timings
						$article_details['writing_start']=$publish_time;

						
						$participation_time=(($this->delivery_creation->prod_step1['participation_time_hour']*60)+$this->delivery_creation->prod_step1['participation_time_min'])*60;
						$selection_time=(($this->delivery_creation->prod_step1['selection_hour']*60)+$this->delivery_creation->prod_step1['selection_min'])*60;
						$submit_time=$article_details['senior_time']*60;
						$article_details['writing_end']=date("Y-m-d H:i",strtotime($article_details['writing_start'])+$participation_time+$selection_time+$submit_time);
						
						if($this->delivery_creation->prod_step1['correction']=='external')
						{
							//updating correction timings
							$article_details['proofread_start']=$article_details['writing_end'];							



						
							$cparticipation_time=(($this->delivery_creation->prod_step1['correction_participation_hour']*60)+$this->delivery_creation->prod_step1['correction_participation_min'])*60;
							$cselection_time=(($this->delivery_creation->prod_step1['correction_selection_hour']*60)+$this->delivery_creation->prod_step1['correction_selection_min'])*60;
							$csubmit_time=$article_details['correction_sc_submission']*60;
							$article_details['proofread_end']=date("Y-m-d H:i",strtotime($article_details['proofread_start'])+$cparticipation_time+$cselection_time+$csubmit_time);
						}
						//echo "<pre>";print_r($article_details);exit;
						$this->delivery_creation->prod_step2['articles'][$i]=$article_details;
						unset($article_details);

					}
				}	

			}
			
			//echo "<pre>";print_r($launchParams);exit;
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
				if(isset($this->delivery_creation->prod_step2['articles'][$article_id]) && count($this->delivery_creation->prod_step2['articles'][$article_id])>0)
				{
					//echo $article_id."deleted";
					unset($this->delivery_creation->prod_step2['articles'][$article_id]);
					$article_array=$this->delivery_creation->prod_step2['articles'];
					$this->delivery_creation->prod_step2['articles']=array_combine(range(1, count($article_array)), array_values($article_array));
					$this->delivery_creation->prod_step1['total_article']=$this->delivery_creation->prod_step1['total_article']-1;
				}

				//reassign the article ids
				foreach($this->delivery_creation->prod_step2['articles'] as $key=>$article)
				{
					$this->delivery_creation->prod_step2['articles'][$key]['article_id']=$key;
				}

				//echo "<pre>";print_r(($this->delivery_creation->prod_step2['articles']));
			}

		}	
	}

	//prod step3 to create delivery/article and send emails
	public function deliveryProd3Action()
	{
		$step3Params=$this->_request->getParams();
		$mission_id=$step3Params['mission_id'];

		if($this->delivery_creation->prod_step1['total_article']>0 && $mission_id)
		{
			$this->_view->prod_step1=$this->delivery_creation->prod_step1;
			$this->_view->prod_step2=$this->delivery_creation->prod_step2;
			$this->_view->prod_step3=$this->delivery_creation->prod_step3;
			$this->render('delivery-prod3');
		}		
		else
			$this->_redirect("/quotedelivery/delivery-prod1?mission_id=".$mission_id);
	}


	//save prodstep3 and create delivery and article
	public function saveProd3Action()
	{
		if($this->_request-> isPost())
		{
			$notificationParams=$this->_request->getParams();
			$mission_id=$this->delivery_creation->prod_step1['mission_id'];

			//echo "<prE>";print_r($notificationParams);exit;

			$delivery_obj=new Ep_Quote_Delivery();

			$this->delivery_creation->prod_step3['send_mission_comments']=$notificationParams['send_mission_comments'];
			$this->delivery_creation->prod_step3['missioncomment']=$notificationParams['missioncomment'];
			$this->delivery_creation->prod_step3['fb_send']=$notificationParams['fb_send'];
			$this->delivery_creation->prod_step3['fbcomment']=$notificationParams['fbcomment'];
			$this->delivery_creation->prod_step3['send_email']=$notificationParams['send_email'];
			$this->delivery_creation->prod_step3['mailsubject']=$notificationParams['mailsubject'];
			$this->delivery_creation->prod_step3['mailcontent']=$notificationParams['mailcontent'];


			//Delivery insertion
			$delivery_array["contract_mission_id"]=$mission_id;

			$delivery_array["user_id"] = $this->delivery_creation->prod_step1['user_id'];
			$delivery_array['title'] = $this->delivery_creation->prod_step1['title'];
			$delivery_array["deli_anonymous"] =$this->delivery_creation->prod_step1['deli_anonymous'] ? 'yes' : 'no';
			$delivery_array["total_article"] = $this->delivery_creation->prod_step1['total_article'];
			//$delivery_array["language"] = $this->delivery_creation->prod_step1['language'];
			$delivery_array["type"] = $this->delivery_creation->prod_step1['type'];
			$delivery_array["product"] = $this->delivery_creation->prod_step1['product'];
			$delivery_array["category"] = $this->delivery_creation->prod_step1['category'];
			$delivery_array["signtype"] = $this->delivery_creation->prod_step1['signtype'];

			$delivery_array["min_sign"]=$this->delivery_creation->prod_step1['min_sign'];
			$delivery_array['max_sign']=$this->delivery_creation->prod_step1['max_sign'];
			$delivery_array['price_min']=$this->delivery_creation->prod_step1['price_min'];
			$delivery_array['price_max']=$this->delivery_creation->prod_step1['price_max'];
			if($this->delivery_creation->prod_step1['product']=='translation')
				$delivery_array["view_to"] = 'sc,jc';
			else
				$delivery_array["view_to"] = 'sc,jc,jc0';

			$delivery_array["premium_option"] = $this->delivery_creation->prod_step1['premium_option'];
			$delivery_array["premium_total"] = $this->delivery_creation->prod_step1['premium_total'];

			$delivery_array["participation_time"] = (($this->delivery_creation->prod_step1['participation_time_hour']*60)+($this->delivery_creation->prod_step1['participation_time_min']));
			
			$delivery_array["selection_time"] = (($this->delivery_creation->prod_step1['selection_hour']*60)+($this->delivery_creation->prod_step1['selection_min'])); //newly added

			
			$delivery_array['urlsexcluded']=$this->delivery_creation->prod_step1['urlsexcluded'];

			//Submit time
			$delivery_array["submit_option"] = 'hour';
			$delivery_array['subjunior_time']=$this->configval["jc0_time"];
			$delivery_array['junior_time']=$this->configval["jc_time"];
			$delivery_array['senior_time']=$this->configval["sc_time"];

			//Resubmit time
			$delivery_array['resubmit_option']='hour';
			$delivery_array['jc0_resubmission']=$this->configval["jc0_resubmission"];
			$delivery_array['jc_resubmission']=$this->configval["jc_resubmission"];
			$delivery_array['sc_resubmission']=$this->configval["sc_resubmission"];

			$delivery_array["file_name"] = $this->delivery_creation->prod_step1['writing_spec_file_name'];
			$delivery_array["filepath"] = $this->delivery_creation->prod_step1['writing_spec_file_path'];

			$delivery_array["created_by"]='BO';
			$delivery_array["created_user"]=$this->adminLogin->userId;
			$delivery_array["status_bo"] = "active";
			$delivery_array["updated_at"] = date('Y-m-d');
			$delivery_array["published_at"] = time();

			$delivery_array["AOtype"]=$this->delivery_creation->prod_step1['AOtype'];
			$delivery_array["plagiarism_check"]="yes";
			$delivery_array["writer_notify"]='yes';

			$delivery_array["files_pack"]=$this->delivery_creation->prod_step1['files_pack'];

			$delivery_array["refusalreasons"]=implode("|",$this->delivery_creation->prod_step1['refusalreasons']);
			
			$delivery_array["sourcelang_nocheck"] = $this->delivery_creation->prod_step1['sourcelang_nocheck'];

			//Correction
			$delivery_array["correction"]=$this->delivery_creation->prod_step1['correction'];
			if($this->delivery_creation->prod_step1['correction']=='external')
			{			
				$delivery_array["correction_type"]=$this->delivery_creation->prod_step1['correction_type'];
				$delivery_array["correction_pricemin"]=$this->delivery_creation->prod_step1['correction_pricemin'];
				$delivery_array["currency"]=$this->delivery_creation->prod_step1['currency'];
				$delivery_array["correction_pricemax"]=$this->delivery_creation->prod_step1['correction_pricemax'];
				$delivery_array["correction_participation"]=(($this->delivery_creation->prod_step1['correction_participation_hour']*60)+($this->delivery_creation->prod_step1['correction_participation_min']));
				$delivery_array["correction_selection_time"] = (($this->delivery_creation->prod_step1['correction_selection_hour']*60)+($this->delivery_creation->prod_step1['correction_selection_min']));			//newly added
				
				//corrector submission times
				$delivery_array['correction_jc_submission']=$this->configval["correction_jc_submission"];
				$delivery_array['correction_sc_submission']=$this->configval["correction_sc_submission"];
				$delivery_array['correction_submit_option']='hour';

				//corrector resubmission times
				$delivery_array['correction_jc_resubmission']=$this->configval["correction_jc_resubmission"];
				$delivery_array['correction_sc_resubmission']=$this->configval["correction_sc_resubmission"];
				$delivery_array['correction_resubmit_option']='hour';

				$delivery_array["correction_file"]=$this->delivery_creation->prod_step1['correction_spec_file_path'];
				$delivery_array["corrector_mail"]="yes";
				$delivery_array["correction_type"]=$this->delivery_creation->prod_step1['correction_type'];
				$delivery_array['corrector_list']='CB';
				$delivery_array["corrector_notify"]='yes';




				//Added new columns
				$delivery_array["corrector_pricedisplay"]=$this->delivery_creation->prod_step1['corrector_pricedisplay']=="yes" ? 'yes' : 'no';
				$delivery_array["correction_launch"]=$this->delivery_creation->prod_step1['correction_launch'];				
				
				$delivery_array["sourcelang_nocheck_correction"] = $this->delivery_creation->prod_step1['sourcelang_nocheck_correction'];
			}

			/* Stencils */
			if($this->delivery_creation->prod_step1['theme'])
			{
				$delivery_array["stencils_ebooker"] = "yes";
				$delivery_array["ebooker_cat_id"] = $this->delivery_creation->prod_step1['ebooker_cat_id'];
				$delivery_array["ebooker_sampletxt_id"] = $this->delivery_creation->prod_step1['sampletext'];
			/* 	$ebooker_obj = new Ep_Ebookers_Stencils();
				$tokens = (array) $ebooker_obj->getTokens(array('cat_id'=>$this->delivery_creation->prod_step1['category']));
				$tokenids = array();
				foreach($tokens as $row)
				$tokenids[] = $row['token_id'];
				$tokenids_imp = implode(",",$tokenids); */
				$mandatory = $this->delivery_creation->prod_step1['mandatory_token'];
				$optional = (array) $this->delivery_creation->prod_step1['optional_tokens'];
				if($mandatory)
				$optional[] = $mandatory;
				$tokenids_imp = implode(",",$optional);
				$delivery_array["ebooker_tokenids"] = $tokenids_imp;
			}
			//publishtime and notifications
			if($this->delivery_creation->prod_step2['publish_now']!="yes" && $this->delivery_creation->prod_step2['publish_time']!="")
				$delivery_array["publishtime"]=strtotime($this->delivery_creation->prod_step2['publish_time']);

			//send emails from Bo user or service
			$delivery_array["mail_send_from"]=$notificationParams['mail_from'];
			$delivery_array["mailsubject"]=$this->delivery_creation->prod_step3['mailsubject'];
			$delivery_array["mailcontent"]=$this->delivery_creation->prod_step3['mailcontent'];
			$delivery_array["missioncomment"]=$this->delivery_creation->prod_step3['missioncomment'];
			$delivery_array["fbcomment"]=$this->delivery_creation->prod_step3['fbcomment'];
			
			//$delivery_array["nltitle"]=$this->delivery_creation->prod_step3['nltitle'];

			if($this->delivery_creation->prod_step2['publish_now']=="yes" && $delivery_array["AOtype"]=="public")
				$delivery_array["mailnow"]="yes";

			if($this->delivery_creation->prod_step3['send_email']=="yes"){$delivery_array["mail_send"]="yes";}else{$delivery_array["mail_send"]="no";};

			if($this->delivery_creation->prod_step1['product']=="translation")
			{
				$delivery_array["publish_language"]=$this->delivery_creation->prod_step1['language_dest'];
				$delivery_array["language"] = $this->delivery_creation->prod_step1['language_dest'];
				$delivery_array["language_source"] = $this->delivery_creation->prod_step1['language'];
			}
			else
			{
				$delivery_array["publish_language"]=$delivery_array["language"];
				$delivery_array["language"] = $this->delivery_creation->prod_step1['language'];	
			}

			

			$delivery_array["missiontest"]="no";
			$delivery_array["pricedisplay"]=$this->delivery_creation->prod_step1['pricedisplay']=="yes" ? 'yes' : 'no';
			//$delivery_array["pricedisplay"]="yes"; 

			if($this->delivery_creation->prod_step1['plag_excel_file']=='yes')
			{
				$delivery_array["column_xls"]=$this->delivery_creation->prod_step1['xls_columns'];
				//Added new columns
				$delivery_array["plag_xls"]=$this->delivery_creation->prod_step1['plag_xls'];
			}			


			
			
			//echo "<pre>";print_r($delivery_array);exit;
			$delivery_identifier=$delivery_obj->insertDelivery($delivery_array);
			
			if($this->delivery_creation->prod_step1['total_article']>0 && $delivery_identifier)
			{
				$total_article=count($this->delivery_creation->prod_step2['articles']);

				$total_amount=0;
				$final_array_contribs=array();
				
				for($i=1;$i<=$total_article;$i++)
				{
					$article_obj=new Ep_Quote_Delivery();

					$article_details=$this->delivery_creation->prod_step2['articles'][$i];

					//Insert Article
					$article_array = array(); 			
					$article_array["delivery_id"]= $delivery_identifier;
					$article_array["title"] 	 = utf8dec($article_details['title']);
					//$article_array["language"] 	 = $this->delivery_creation->prod_step1['language'];
					$article_array["category"]   = $this->delivery_creation->prod_step1['category'];
					$article_array["currency"]   = $this->delivery_creation->prod_step1['currency'];
					$article_array["type"]       = $this->delivery_creation->prod_step1['type'];
					$article_array["sign_type"]  = $this->delivery_creation->prod_step1['signtype'];
					$article_array["num_min"]	 = $this->delivery_creation->prod_step1['min_sign'];
					$article_array["num_max"] 	 = $this->delivery_creation->prod_step1['max_sign'];
					$article_array["price_min"]  = currencyToDecimal($article_details['price_min']);
					$article_array["price_max"]  = currencyToDecimal($article_details['price_max']);
					$article_array["price_final"]= currencyToDecimal($article_details['price_max']);
					$article_array["status"]	 = "new";  
					$article_array["created_by"] ='BO';

					$article_array["product"] = $this->delivery_creation->prod_step1['product'];
					//stencils translation
					if($article_array["product"]=='translation')
					{
						$article_array["stencils_translate"]=$article_details['stencils_translate'];
					}
					
					$article_array["files_pack"]=$this->delivery_creation->prod_step1['files_pack'];
					if($this->delivery_creation->prod_step1['product']=="translation")
					{						
						$article_array["publish_language"]=$this->delivery_creation->prod_step1['language_dest'];
						$article_array["language"] 	 = $this->delivery_creation->prod_step1['language_dest'];
						$article_array["language_source"] = $this->delivery_creation->prod_step1['language'];
						$article_array["sourcelang_nocheck"] = $this->delivery_creation->prod_step1['sourcelang_nocheck'];
					}
					else
					{						
						$article_array["publish_language"]=$this->delivery_creation->prod_step1['language'];	
						$article_array["language"] 	 = $this->delivery_creation->prod_step1['language'];
					}
					//$article_array["publish_language"]=$article_array["language"];

					
					
					$article_array["contrib_percentage"]='100';
					$article_array["paid_status"]='paid';

					$total_amount+=$article_array["price_max"];

					//Contributors list			
					if($this->delivery_creation->prod_step1['AOtype']=="private")
					{
					  $article_array["contribs_list"]=implode(",",$article_details['contribs_list']);			
					  $final_array_contribs[]=$article_details['contribs_list'];
					}

					if($this->delivery_creation->prod_step2['publish_now']!="yes" && $this->delivery_creation->prod_step2['publish_time']!="")
						$publishtime=strtotime($this->delivery_creation->prod_step2['publish_time']);
					else
						$publishtime=time();

					$article_array["participation_expires"]=($publishtime + ($delivery_array["participation_time"]*60));
					
					$article_array["participation_time"] = $delivery_array["participation_time"];


					$article_array["refusalreasons"]=implode("|",$this->delivery_creation->prod_step1['refusalreasons']);


						
					//Correction
					if($this->delivery_creation->prod_step1['correction']=='external')
					{
						$article_array["correction"]="yes";
		                $article_array["correction_type"]='extern';
		                $article_array["nomoderation"]=$article_details['nomoderation'];		                

						$article_array["correction_pricemin"]=currencyToDecimal($article_details['correction_pricemin']);
						$article_array["correction_pricemax"]=currencyToDecimal($article_details['correction_pricemax']);
						
						$article_array["correction_participation"]=($delivery_array["correction_participation"]);

						//$article_array["correction_participationexpires"]=($publishtime + ($delivery_array["correction_participation"]*60));
						$article_array["correction_participationexpires"]=0;
						
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
						
						if($this->delivery_creation->prod_step1['correction_type']=="private")
							$article_array["corrector_privatelist"]=implode(",",$article_details["corrector_privatelist"]);

							
						//new fields
						$article_array["correction_selection_time"]=($delivery_array["correction_selection_time"]);
						$corrector_list=$article_details["corrector_list"];
						if((in_array('sc',$corrector_list) && in_array('jc',$corrector_list)) || in_array('CB',$corrector_list))
							$corrector_public='CB';
						else if(in_array('sc',$corrector_list) || in_array('CSC',$corrector_list))	
							$corrector_public='CSC';
						else if(in_array('jc',$corrector_list) || in_array('CJC',$corrector_list))	
							$corrector_public='CJC';
						$article_array["corrector_list"]=$corrector_public;
						
						$article_array["proofread_start"]=$article_details["proofread_start"];
						$article_array["proofread_end"]=$article_details["proofread_end"];
						
						if($this->delivery_creation->prod_step1['product']=="translation")
							$article_array["sourcelang_nocheck_correction"] = $this->delivery_creation->prod_step1['sourcelang_nocheck_correction'];
						
					}					
					
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
					
					$article_array["column_xls"]=$this->delivery_creation->prod_step1['xls_columns'];
					
					//new fields
					$article_array["selection_time"]=($delivery_array["selection_time"]);
						$article_array["view_to"]=implode(",",$article_details["view_to"]);
					
					/* Stencils */
					if($this->delivery_creation->prod_step1['theme'])
					{
					$article_array["ebooker_cat_id"] = $this->delivery_creation->prod_step1['ebooker_cat_id'];
					$article_array["ebooker_sampletxt_id"] = $this->delivery_creation->prod_step1['sampletext'];
					$article_array["ebooker_tokenids"] = $tokenids_imp;
					}

                    /* ** added on 25.02.2016 *** */
                    //BNP related fields which has to be updated in  DB//
                    if($this->delivery_creation->prod_step1['city']){
                        $article_array["bnp_city_id"] = $this->delivery_creation->prod_step1['city'];
                        $article_array["bnp_sampletext_id"] = $this->delivery_creation->prod_step1['sampletext'];
                    }
                    //echo "<pre>";print_r($article_array);exit;
					$article_obj->insertArticle($article_array);
					

				}
			}

			//repeat delivery info insertion
			if($this->delivery_creation->prod_step2['repeat']=='yes' && $delivery_identifier)
			{
				$repeat_option=$this->delivery_creation->repeat_delivery['repeat_option'];

		    	$repeat_every=$this->delivery_creation->repeat_delivery['repeat_every'];
		    	if($repeat_option=='week' || $repeat_option=='week_b')
		    		$repeat_days=$this->delivery_creation->repeat_delivery['repeat_on'];    	
		    	
		    	$repeat_start=$this->delivery_creation->repeat_delivery['repeat_start'];
		    	
		    	$repeat_end=$this->delivery_creation->repeat_delivery['repeat_end'];
		    	if($repeat_end=='after')
		    		$end_occurances=$this->delivery_creation->repeat_delivery['after_occurance'];
		    	elseif($repeat_end=='on')
		    		$end_date=$this->delivery_creation->repeat_delivery['end_on'];

		    	$repeat_obj=new Ep_Quote_DeliveryRepeat();

		    	$repeat_array['delivery_id']=$delivery_identifier;
		    	$repeat_array['repeat_option']=$repeat_option;
		    	$repeat_array['repeat_every']=$repeat_every;
		    	$repeat_array['repeat_days']=implode(",",$repeat_days);
		    	$repeat_array['repeat_start']=$repeat_start;
		    	$repeat_array['repeat_end']=$repeat_end;
		    	$repeat_array['end_occurances']=$end_occurances;
		    	$repeat_array['end_date']=$end_date;		    	

		    	$repeat_obj->insertRepeatDelivery($repeat_array);
		    	//echo "<pre>";print_r($repeat_array);exit;
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
				$data['user_id']=$this->delivery_creation->prod_step1['user_id'];
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
					
				$AO_name='<a href="/followup/delivery?client_id='.$this->delivery_creation->prod_step1['user_id'].'&ao_id='.$delivery_identifier.'&submenuId=ML13-SL4" target="_blank"><b>'.$this->delivery_creation->prod_step1['title'].'</b></a>';

				$user_obj=new Ep_User_Client();
				$detailsC=$user_obj->getClientName($this->delivery_creation->prod_step1['user_id']);
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

		                $parameters['editobject']=$this->delivery_creation->prod_step3['mailsubject'];
		                $parameters['editmessage']=$this->delivery_creation->prod_step3['mailcontent'];
		                $parameters['mail_from']=$notificationParams['mail_from'];
					//Mail sent only if it selected Now
					if($this->delivery_creation->prod_step2['publish_now']=='yes')
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
							if($this->delivery_creation->prod_step1['product']=="translation")
							{
								$searchParameters['product']=$this->delivery_creation->prod_step1['product'];
								$searchParameters['language']=$this->delivery_creation->prod_step1['language_dest'];
								$searchParameters['language_source']=$this->delivery_creation->prod_step1['language'];
								$searchParameters['sourcelang_nocheck']=$this->delivery_creation->prod_step1['sourcelang_nocheck'];
								$writersList=$delivery_obj->getTranslatorList($searchParameters);		
							}
							else	
							{
								$searchParameters['language']=$this->delivery_creation->prod_step1['language'];
								$writersList=$delivery_obj->getContributorsList($searchParameters);		
							}
							//print_r($writersList);exit;
							//$writersList=$delivery_obj->getContributorsList($searchParameters);									
							
							if(is_array($writersList) && count($writersList)>0)
							{								
								$mailId=85;//											
								foreach($writersList as $contributor=>$email)
								{
									$this->messageToEPMail($contributor,$mailId,$parameters);										
								}
							}
							//echo "<pre>";print_r($writersList);exit;
							
						}		



								

								
								
					}
				}
				//FB posting
				if($this->delivery_creation->prod_step3['fb_send']=='yes' && $this->delivery_creation->prod_step1['AOtype']=="private")
				{
					if($this->delivery_creation->prod_step2['publish_now']=='yes')
					{	
					//FB & TWT posting
						if($this->delivery_creation->prod_step3['fbcomment']!="")
						{
							require_once $this->fo_root_path.'postfb/facebook.php';
							//require_once "/home/sites/site5/web/FO/tmhOAuth/tmhOAuth.php";
							
							//FB details
							$appId = $this->configval['fb_app_id'];
							$secret = $this->configval['fb_secret'];
							//$returnurl = 'http://localhost:8086/posttofb/';
							$permissions = 'publish_stream,offline_access';
							
							$fb = new Facebook(array("appId" => $appId, "secret" => $secret,'scope' => $permissions));
							
							//TWT details
							/* $tmhOAuth = new tmhOAuth(array(
							  'consumer_key' => $this->configval['twt_consumer_key'],
							  'consumer_secret' => $this->configval['twt_consumer_secret'],
							  'token' => $this->configval['twt_token'],
							  'secret' => $this->configval['twt_secret'],
							)); */
							
							$Isposted=$delivery_obj->checkfbpost($this->delivery_creation->prod_step1['user_id']);
							
							if($Isposted!="yes")
							{
								if($this->delivery_creation->prod_step3['fbcomment']!="")
								{
									
									//FB posting
									$message = array(
												//'access_token'=>'CAACxflqXW94BAJ98HE6bGmtwoClekKGxSpYkqypF8LcQcJKWkPPZCUZAwkO0QkXYo67ceYrHjrn4ScrZA48KMyvBm2bHQIF3KVYCZB5SwR8nEjhwgEg2UZCDIpYHthaTuX9XKAv9w54j0PZA8jUGJUOgcb7x6pGi3AAxgt3MZAaK9zZC2ELMbs3Q',
												'access_token'=>$this->configval['fb_access_token'],
												'message'=>utf8_encode(stripslashes($this->delivery_creation->prod_step3['fbcomment']))
												);
									
									//$url='/237274402982745/feed';
									$url='/541125642696272/feed';
									
									$result = $fb->api($url, 'POST', $message);
									
									/* //TWT posting
									$response = $tmhOAuth->request('POST', $tmhOAuth->url('1.1/statuses/update'), array(
									  'status' => utf8_encode(stripslashes($this->delivery_creation->prod_step3['fbcomment'])
									))); */
									
									$mail_text='<b>AO ID</b> : '.$delivery_identifier.'<br><br>
												<b>Title</b> : '.$this->delivery_creation->prod_step1['title'].'<br><br>
												<b>Comment</b> : '.$this->delivery_creation->prod_step3['fbcomment'];
									$mail = new Zend_Mail();
									$mail->addHeader('Reply-To','support@edit-place.com');
									$mail->setBodyHtml($mail_text)
										 ->setFrom('support@edit-place.com','Support Edit-place')
										 ->addTo('arunravuri@edit-place.com')
										 //->addCc('kavithashree.r@gmail.com')
										 ->setSubject('FB posting Test Site');
									//$mail->send();
									//$this->critsendMail($this->mail_from, 'mailpearls@gmail.com', 'FB & TWT posting Test Site', $mail_text);
									
								}
							}
							// update fbpost status
							$array['fbpost']='yes';
							$array['postoftheday']='yes';								
							$delivery_obj->updateDelivery($array,$delivery_identifier);
						}
					}
				}

                //Add mission comments for each article
                if($this->delivery_creation->prod_step3['send_mission_comments'] && $this->delivery_creation->prod_step3['missioncomment']!="")
				{
					$comm_obj=new Ep_User_AdComments();
					$article_obj=new Ep_Quote_Delivery();
					$artids=$article_obj->getArticles($delivery_identifier);
					for($a=0;$a<count($artids);$a++)
					{
						$commentarray=array();
						$commentarray['user_id']=$this->adminLogin->userId;
						$commentarray['type']="article";
						$commentarray['type_identifier']=$artids[$a]['id'];
						$commentarray['comments']=isodec($this->delivery_creation->prod_step3['missioncomment']);
						$comm_obj->InsertComment($commentarray);
					}
				}
				//send email to prod manger if tempo not respected
				$tempo_respect=$this->delivery_creation->prod_step2['tempo_respected'];
				if($tempo_respect=='no')
				{
					$client_obj=new Ep_Quote_Client();
					$manager=$this->delivery_creation->prod_step1['prod_manager'];
					$manager_details=$client_obj->getQuoteUserDetails($manager);
					if($manager_details!='NO')
					{
						$prod_manager='<b>'.$manager_details[0]['first_name'].' '.$manager_details[0]['last_name'].'</b>';
						$prod_email=$manager_details[0]['email'];
					}	
					$bo_user_details=$client_obj->getQuoteUserDetails($this->adminLogin->userId);
					if($bo_user_details!='NO')
					{
						$bo_user='<b>'.$bo_user_details[0]['first_name'].' '.$bo_user_details[0]['last_name'].'</b>';
					}
					$mail_obj=new Ep_Message_AutoEmails();
					$email_contents = $mail_obj->getAutoEmail(196);
					$subject =$email_contents[0]['Object'];
					$message =stripslashes($email_contents[0]['Message']);
					$client_name='<b>'.$this->delivery_creation->prod_step1['client_name'].'</b>';
					$volume_max=$this->delivery_creation->prod_step1['volume_max'];
					$product_name=$this->delivery_creation->prod_step1['product_type_name'];
					$delivery_volume_option=$this->volume_option_array[$this->delivery_creation->prod_step1['delivery_volume_option']];
					$tempo_length=$this->delivery_creation->prod_step1['tempo_length'];
					$tempo_length_option=$this->duration_array[$this->delivery_creation->prod_step1['tempo_length_option']];
					$click_here='<a href="http://'.$_SERVER['HTTP_HOST'].'/followup/prod?submenuId=ML13-SL4&cmid='.$this->delivery_creation->prod_step1['mission_id'].'">cliquez-ici </a>';
					eval("\$subject= \"$subject\";");
					$subject=strip_tags($subject);

					eval("\$message= \"$message\";");
					//echo $subject."--".$message;
					$mail_obj->sendEmail('work@edit-place.com',$message,$prod_email,$subject);
					//$this->messageToEPMail($contributor,$automailid,$parameters);
				}
				//exit;	

				//run cron immediately to create the next day delivery
				if($this->delivery_creation->prod_step2['repeat']=='yes' && $delivery_identifier)
				{
					//echo "http://".$_SERVER['HTTP_HOST']."/quotes-cron/repeat-delivery?delivery_id=".$delivery_identifier;exit;
					file_get_contents("http://".$_SERVER['HTTP_HOST']."/quotes-cron/repeat-delivery?delivery_id=".$delivery_identifier);
				}

                
                //remove all from session
				unset($this->delivery_creation->prod_step1);			
				unset($this->delivery_creation->prod_step2);
				unset($this->delivery_creation->prod_step3);
				unset($this->delivery_creation->repeat_delivery);

				$this->_redirect("/quotedelivery/prod-success?submenuId=ML13-SL2&mission_id=".$mission_id);
			//echo "<pre>";print_r($this->delivery_creation->prod_step1);

		}	
	}
	//delivery created successfully
	public function prodSuccessAction()
	{
		header( "refresh:2;url=/followup/prod?submenuId=ML13-SL4&cmid=".$this->_request->getParam('mission_id'));
		$this->render("delivery-prod-success");
	}

	//function to check whether corrector/writers selected if it is private
	public function checkArticleWritersCorrectorsAction()
	{
		$writingType=$this->delivery_creation->prod_step1['AOtype'];
		$correctionType=$this->delivery_creation->prod_step1['correction_type'];
		$error='';
		if(count($this->delivery_creation->prod_step2['articles']))
		{			
			foreach($this->delivery_creation->prod_step2['articles'] as $article)
			{
				if($writingType=='private')
				{
					if(count($article['contribs_list'])==0)
						$error.='please select writers for '.$article['title']."<br>";
				}
				if($correctionType=='private' && $this->delivery_creation->prod_step1['correction']!='internal')
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

	//cotrib email content
	public function getcontribmailcontentAction()
	{		
		$automail=new Ep_Message_AutoEmails();
		$user_obj=new Ep_User_Client();		
		$mailid="";
		
			if($this->delivery_creation->prod_step1['AOtype']=="private")
			{
				$mailid=87;
			}
			else
			{		
				if($this->delivery_creation->prod_step2['publish_now']=='yes')
					$mailid=85;
				else
					$mailid=89;
					
			}
		
			
        $email=$automail->getAutoEmail($mailid);
		
		$participation_time=(($this->delivery_creation->prod_step1['participation_time_hour']*60)+($this->delivery_creation->prod_step1['participation_time_min']))*60;
		
		if($this->delivery_creation->prod_step2['publish_now']=='yes')
		{			
			$expires=time()+($participation_time);
			//echo date("Y-m-d H:m",$expires);
			$submitdate_bo="<b>".strftime("%d/%m/%Y &agrave; %H:%M",$expires)."</b>";
		}
		else
		{	
			
			$expires=strtotime($this->delivery_creation->prod_step2['publish_time'])+($participation_time);
			$submitdate_bo="<b>".strftime("%d/%m/%Y &agrave; %H:%M",$expires)."</b>";
		}

		$aowithlink='<a href="http://ep-test.edit-place.co.uk/contrib/aosearch">'.stripslashes($this->delivery_creation->prod_step1['title']).'</a>';
		$sub=$email[0]['Object'];
		$Message=$email[0]['Message'];
		eval("\$Message= \"$Message\";");
		
		$subcon=$sub."#".$Message;
		echo utf8_encode($subcon);	
		
		//echo $subcon;
		//echo $mailid;
		
	}
	//proofreader email content
	public function getproofreadmailcontentAction()
	{		
		$automail=new Ep_Message_AutoEmails();
		$user_obj=new Ep_User_Client();		
		$mailid=178;
		
		$participation_time=(($this->delivery_creation->prod_step1['participation_time_hour']*60)+($this->delivery_creation->prod_step1['participation_time_min']))*60;

		$cparticipation_time=(($this->delivery_creation->prod_step1['correction_participation_hour']*60)+($this->delivery_creation->prod_step1['correction_participation_min']))*60;	

        if($this->delivery_creation->prod_step2['publish_now']=='yes')
		{
			$expires=time()+($participation_time);

			$correxpires=time()+($cparticipation_time);
			$submit_hours="<b>".strftime("%d/%m/%Y &agrave; %H:%M",$correxpires)."</b>";
		}
		else
		{
			$expires=strtotime($this->delivery_creation->prod_step2['publish_time'])+($participation_time);

			$correxpires=strtotime($this->delivery_creation->prod_step2['publish_time'])+($cparticipation_time);			
			$submit_hours="<b>".strftime("%d/%m/%Y &agrave; %H:%M",$correxpires)."</b>";
		}
	
		
		$article='<a href="'.$this->fo_base_path.'/contrib/aosearch">'.stripslashes($this->delivery_creation->prod_step1['title']).'</a>';

		$corrector_ao_link='<a href="'.$this->fo_base_path.'/contrib/aosearch">Cliquant-ici</a>';
		
		
		//max_reception_writer_file_date_hour
			$max=max($this->configval["jc0_time"], $this->configval["jc_time"], $this->configval["sc_time"]);		
			$submittime=60*$max;

		$max_reception_writer_file_date_hour="<b>".strftime("%d/%m/%Y &agrave; %H:%M",($expires+$submittime))."</b>"; ;
		
		$email=$automail->getAutoEmail($mailid);

		$sub=$email[0]['Object'];
		$Message=$email[0]['Message'];
		eval("\$Message= \"$Message\";");
		
		$subcon=$sub."#".$Message;
		echo utf8_encode($subcon);
		
		//echo $subcon;
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

    //repeat delivery modal

    public function repeatDeliveryAction()
    {
    	$repeatParams=$this->_request->getParams();
    	$unset=$repeatParams['unset'];

    	if($unset=='yes')
    	{
    		unset($this->delivery_creation->prod_step2['repeat']);
    		unset($this->delivery_creation->repeat_delivery);
    	}
    	else
    	{

	    	if(!$this->delivery_creation->repeat_delivery['repeat_option'])
	    		$this->delivery_creation->repeat_delivery['repeat_option']='week';
	    	
	    	if(!$this->delivery_creation->repeat_delivery['repeat_every'])
	    		$this->delivery_creation->repeat_delivery['repeat_every']=1;   

	    	if(!$this->delivery_creation->repeat_delivery['repeat_start'])
	    		$this->delivery_creation->repeat_delivery['repeat_start']=date("Y-m-d",strtotime($this->delivery_creation->prod_step2['publish_time']));  

	    		 	

	    	if(!is_array($this->delivery_creation->repeat_delivery['repeat_on']))
	    		$this->delivery_creation->repeat_delivery['repeat_on']=array(date('N'));

	    	if(!$this->delivery_creation->repeat_delivery['repeat_end'])
	    		$this->delivery_creation->repeat_delivery['repeat_end']='never';
	    	    	
	    	$this->_view->repeat_delivery=$this->delivery_creation->repeat_delivery;
	    	$this->_view->prod_step2=$this->delivery_creation->prod_step2;
	    	$this->render('repeat-delivery-popup');
	    }	
    }
    //save repeat form data
    function saveRepeatDeliveryAction()
    {
    	$repeatParams=$this->_request->getParams();

    	$repeat_option=$repeatParams['repeat_option'];
    	$repeat_every=$repeatParams['repeat_every'];
    	$repeat_on=$repeatParams['repeat_on'];    	
    	$repeat_start=date("Y-m-d",strtotime($this->delivery_creation->prod_step2['publish_time']));  //$repeatParams['repeat_start'];
    	$repeat_end=$repeatParams['repeat_end'];
    	$after_occurance=$repeatParams['after_occurance'];
    	$end_on=$repeatParams['end_on'];
    	$summary_text=$repeatParams['summary_text_input'];

    	if($repeat_option && $repeat_every && $repeat_start && $repeat_end)
    	{
    		$this->delivery_creation->prod_step2['repeat']='yes';
    		$this->delivery_creation->repeat_delivery['repeat_option']=$repeat_option;
    		$this->delivery_creation->repeat_delivery['repeat_every']=$repeat_every;
    		$this->delivery_creation->repeat_delivery['repeat_on']=$repeat_on;
    		$this->delivery_creation->repeat_delivery['repeat_start']=$repeat_start;
    		$this->delivery_creation->repeat_delivery['repeat_end']=$repeat_end;
    		$this->delivery_creation->repeat_delivery['summary_text']=$summary_text;

    		

    		if($repeat_end=='after')
    			$this->delivery_creation->repeat_delivery['after_occurance']=$after_occurance;
    		else if($repeat_end=='on')
    			$this->delivery_creation->repeat_delivery['end_on']=$end_on;
    		else{
    			unset($this->delivery_creation->repeat_delivery['after_occurance']);
    			unset($this->delivery_creation->repeat_delivery['end_on']);
    		}
    	}
    	//print_r($this->delivery_creation->repeat_delivery);
    	

    }

    //update delivery name
    function updateDeliverynameAction()
    {
    	if($this->_request->isPost() && $_SERVER['HTTP_X_REQUESTED_WITH']=='XMLHttpRequest')
		{
    		$Params=$this->_request->getParams();
    		$delivery_title=utf8_decode($Params['value']);
    		$this->delivery_creation->prod_step1['title']=$delivery_title;
    		exit;
    	}	
    }

	/* To load Category through Ajax in stencil setup */
	function getCategoryAction()
	{
		if($this->_request->isPost() && $_SERVER['HTTP_X_REQUESTED_WITH']=='XMLHttpRequest')
		{
    		$request = $this->_request->getParams();
			$ebooker_obj = new Ep_Ebookers_Stencils();
			$stencils = (array) $ebooker_obj->getStencils(array('theme_id'=>$request['theme_id'],'category'=>true));
			$stencil = array();
			$i = 0;
			foreach($stencils as $row)
			{
				$stencil[$i]['cat_id'] = ($row['cat_id']);
				$stencil[$i++]['category_name'] = utf8_encode($row['category_name']);
			}
			//$stencil['description'] = $row['themedesc'];
            echo json_encode($stencil);
		}
	}
	/* To load sample texts */
	function sampleTextsAction()
	{
		if($this->_request->isPost() && $_SERVER['HTTP_X_REQUESTED_WITH']=='XMLHttpRequest')
		{
    		$request = $this->_request->getParams();
			$ebooker_obj = new Ep_Ebookers_Stencils();
			$this->_view->sample_id = $request['sample_id'];
			$sampletexts = $ebooker_obj->getStencils(array('token_id'=>$request['token_id'],'sampletexts'=>true,'language'=>$request['language']));
			$this->_view->sampletexts = $sampletexts;
			$this->render('sample-texts');
		}
	}
	/* *** added on 24.02.2016 *** */
    function sampleBnpTextsAction(){
        if($this->_request->isPost() && $_SERVER['HTTP_X_REQUESTED_WITH']=='XMLHttpRequest')
        {
            $request = $this->_request->getParams();
            $bnp_obj = new Ep_Bnp_Bnp();
            $this->_view->sample_id = '1';
            $sampletexts = $bnp_obj->getBnpSampleText(array('city_id'=>$request['city_id'],'sampletexts'=>true));
            $this->_view->sampletexts = $sampletexts;
            $this->_view->city_name = $bnp_obj->getCityName($request['city_id']);
            $this->render('sample-bnp-texts');
        }
    }
	/* To get Tokens */
	function getTokensAction()
	{
		if($this->_request->isPost() && $_SERVER['HTTP_X_REQUESTED_WITH']=='XMLHttpRequest')
		{
    		$request = $this->_request->getParams();
			$ebooker_obj = new Ep_Ebookers_Stencils();
			if(is_array($request['cat_ids']))
			{
				$cat_ids = array_filter($request['cat_ids']);
				$cat_ids = implode(",",$cat_ids);
			}
			else
				$cat_ids = "";
			$tokens = $ebooker_obj->getTokens(array('cat_id'=>$request['cat_id'],'cat_ids'=>$cat_ids));
			//$tokens = $ebooker_obj->getTokens(array('cat_ids'=>$cat_ids));
			$json = $mtokens = $otokens = $htokens = array();
			if($request['all'])
			{
				$json = $tokens;
			}
			else
			{
				foreach($tokens as $row)
				{
					if($row['token_type']=='mandatory')
					$mtokens[$row['token_id']] = htmlentities(utf8_encode($row['token_name']));
					elseif($row['token_type']=='optional')
					$otokens[$row['token_id']] = htmlentities(utf8_encode($row['token_name']));
					else
					$htokens[$row['token_id']] = htmlentities(utf8_encode($row['token_name']));
				}
				$json['mtokens'] = $mtokens;
				$json['otokens'] = $otokens;
				$json['htokens'] = $htokens;
				$mandatory = $this->delivery_creation->prod_step1['mandatory_token'];
				$optional = (array) $this->delivery_creation->prod_step1['optional_tokens'];
				if(!empty($mandatory) || !empty($optional))
				{
					$optional[] = $mandatory;
					$json['selected_ids'] = $optional;
				}
				else
					$json['selected_ids'] = array();
			}
			echo json_encode($json);
		}
	}
	/* Replace tokens */
	function replaceStencilsAction()
	{
		$this->render('convert-stencils');
	}
	
	/*function to import Deliveries/Articles from a Excel file*/
	function importDeliveryArticlesAction()
	{
		ini_set('max_execution_time', 300);
		$document_name = "Delivery-Article-Import.xlsx";
		$document_path= $_SERVER['DOCUMENT_ROOT'].'/BO/oldmissionsupload/'.$document_name;
		$data = xlsxread($document_path);
		$delivery_details = $data[0][0];
		//echo "<PRE>";print_r($delivery_details);exit;
		
		
		$delivery_obj=new Ep_Quote_Delivery();
		
		if(count($delivery_details)>1)
		{
			foreach($delivery_details as $index=>$delivery)
			{	
				//echo "<PRE>";print_r($delivery);exit;
				if($index>0)
				{
					//echo "<PRE>";print_r($delivery);exit;
					//Delivery insertion	
					$delivery_id_xlsx=$delivery[1];
					
					$delivery_array["user_id"] = $delivery[2];
					$delivery_array['title'] = $delivery[3];
					$delivery_array['price_min']=$delivery[4];
					$delivery_array['price_max']=$delivery[5];
					$delivery_array["type"] = $delivery[6];
					$delivery_array["language"] = $delivery[7];
					$delivery_array["min_sign"]=$delivery[8];
					$delivery_array['max_sign']=$delivery[9];
					$delivery_array["category"] = $delivery[10];
					$delivery_array["total_article"] = $delivery[11];
					$delivery_array["AOtype"]=$delivery[12];
					$delivery_array["contribs_list"]=$delivery[13];
					//Submit time					 
					$delivery_array['subjunior_time']=$delivery[14] ;
					$delivery_array['junior_time']=$delivery[15] ;
					$delivery_array['senior_time']=$delivery[16] ;
					$delivery_array["submit_option"] = $delivery[17];					
					$delivery_array["participation_time"] = $delivery[18];
					
					//Resubmit time					
					$delivery_array['jc0_resubmission']=$delivery[19];
					$delivery_array['jc_resubmission']=$delivery[20];
					$delivery_array['sc_resubmission']=$delivery[21];
					$delivery_array['resubmit_option']=$delivery[22];
					
					$delivery_array['mail_send_contrib']=$delivery[23];
					$delivery_array['mailtoall']=$delivery[24];
					$delivery_array['mail_lang']=$delivery[25];
					//Correction
					$delivery_array["correction"]=$delivery[26];
					if($delivery['correction']=='external')
					{			
						$delivery_array["correction_type"]=$delivery[27];
						$delivery_array["correction_pricemin"]=$delivery[28];
						$delivery_array["correction_pricemax"]=$delivery[29];
						$delivery_array["correction_participation"]=$delivery[30];				
						//corrector submission times
						$delivery_array['correction_jc_submission']=$delivery[31];
						$delivery_array['correction_sc_submission']=$delivery[32];
						$delivery_array['correction_submit_option']=$delivery[33];
						//corrector resubmission times
						$delivery_array['correction_jc_resubmission']=$delivery[34];
						$delivery_array['correction_sc_resubmission']=$delivery[35];
						$delivery_array['correction_resubmit_option']=$delivery[36];
						
						$delivery_array['corrector_list']=$delivery[37];
						$delivery_array["corrector_mail"]=$delivery[38];									
						
						$delivery_array["correction_file"]='';
						$delivery_array["corrector_notify"]=$delivery[49];
						$delivery_array["correction_selection_time"]=$delivery[56];//newly added
						//Added new columns
						$delivery_array["corrector_pricedisplay"]=$delivery[54];
					}
					$delivery_array["plagiarism_check"]=$delivery[39];
					$delivery_array["view_to"] = $delivery[40];
					//publishtime and notifications
					$delivery_array["fbcomment"]=$delivery[41];
					$delivery_array["mailsubject"]=$delivery[42];
					$delivery_array["mailcontent"]=$delivery[43];
					$delivery_array["mailnow"]=$delivery[44];
					$delivery_array["currency"]=$delivery[45];
					$delivery_array["publish_language"]=$delivery[46];
					$delivery_array["missiontest"]="no";//47
					$delivery_array["writer_notify"]=$delivery[48];
					$delivery_array["pricedisplay"]=$delivery[50];					
					$delivery_array["column_xls"]=$delivery[51];
					//Added new columns
					$delivery_array["plag_xls"]=$delivery[52];
					$delivery_array["files_pack"]=$delivery[53];
					$delivery_array["selection_time"] = $delivery[55];
					$delivery_array["contract_mission_id"]=$delivery[57];
					$delivery_array["corrector_privatelist"]=$delivery[58];
					$delivery_array["product"]=$delivery[59];
					
					$delivery_array["correctormailsubject"]=$delivery[60];
					$delivery_array["correctormailcontent"]=$delivery[61];					
					
					//stencils
					$delivery_array["stencils_ebooker"]=$delivery[62];
					$delivery_array["ebooker_cat_id"]=$delivery[63];
					$delivery_array["ebooker_sampletxt_id"]=$delivery[64];
					$delivery_array["ebooker_tokenids"]=$delivery[65];
					
					
					$delivery_array["mail_send"]='no';
					$delivery_array["missioncomment"]='';
					$delivery_array["publishtime"]=time();
					$delivery_array["deli_anonymous"] =0;
					$delivery_array["signtype"] = 'words';					
					$delivery_array["premium_option"] = 1;
					$delivery_array["premium_total"] = 13;					
					$delivery_array['urlsexcluded']='';	
					$delivery_array["file_name"] = '';
					$delivery_array["filepath"] = '';
					$delivery_array["created_by"]='AUTO';
					$delivery_array["created_user"]='110823103540627';
					$delivery_array["status_bo"] = "active";					
					$delivery_array["published_at"] =time();
					//send emails from Bo user or service
					$delivery_array["mail_send_from"]='service';
					
					$delivery_identifier=$delivery_obj->insertDelivery($delivery_array);
					//echo "<PRE>";print_r($delivery_array);exit;	
					
					$all_article_details = $data[0][1];
					
					if(count($all_article_details)>1 && $delivery_identifier)
					{	
						foreach($all_article_details as $index=>$article_details)
						{
							//echo "<PRE>";print_r($article_details);exit;
							
							$delivery_id_xlsx_article=$article_details[2];
							
							if($index>0 && $delivery_id_xlsx==$delivery_id_xlsx_article)
							{
							
								$article_obj=new Ep_Quote_Delivery();
							
								//Insert Article
								$article_array = array(); 			
								$article_array["delivery_id"]= $delivery_identifier;
								$article_array["title"] 	 = $article_details[3];
								$article_array["language"] 	 = $article_details[4];
								$article_array["category"]   = $article_details[5];
								$article_array["type"]       = $article_details[6];
								$article_array["sign_type"]  = $delivery_array['signtype'];
								$article_array["num_min"]	 = $article_details[7];
								$article_array["num_max"] 	 = $article_details[8];
								$article_array["price_min"]  = currencyToDecimal($article_details[9]);
								$article_array["price_max"]  = currencyToDecimal($article_details[10]);
								$article_array["price_final"]= currencyToDecimal($article_details['price_max']);
								//Contributors list			
								if($delivery_array['AOtype']=="private")
								{
									$article_array["contribs_list"]=$article_details[11];
								}
								
								$article_array["files_pack"]= $article_details[26];
								
								
								$total_amount+=$article_array["price_max"];		

								$article_array["participation_time"] =$article_details[15];
								//Submit time
								$article_array["junior_time"] = $article_details[16];
								$article_array["senior_time"] = $article_details[17];
								$article_array["subjunior_time"] = $article_details[18];
								$article_array["submit_option"]=$article_details[19];
									
								//Resubmit time
								$article_array["jc_resubmission"] = $article_details[20];
								$article_array["sc_resubmission"] = $article_details[21];
								$article_array["jc0_resubmission"] = $article_details[22];
								$article_array["resubmit_option"] = $article_details[23];									
								//Correction
								if($delivery_array['correction']=='external')
								{
									$article_array["correction"]="yes";
									$article_array["correction_type"]=$article_details[13];
									$article_array["corrector_privatelist"]=$article_details[14];					

									$article_array["correction_pricemin"]=currencyToDecimal($article_details[24]);
									$article_array["correction_pricemax"]=currencyToDecimal($article_details[25]);
									$article_array["correction_participation"]=$article_details[27];
																			
									//Submit time								
									$article_array['correction_jc_submission']=$article_details[28];
									$article_array['correction_sc_submission']=$article_details[29];
									$article_array['correction_submit_option']=$article_details[30];
									
									//ReSubmit time
									$article_array["correction_jc_resubmission"]=$article_details[31];
									$article_array["correction_sc_resubmission"] = $article_details[32];
									$article_array["correction_resubmit_option"] = $article_details[33];		
									
									$article_array["nomoderation"]=$article_details[36];	
									//new fields
									$article_array["correction_selection_time"]=$delivery_array["selection_time"];
									$article_array["corrector_list"]='CB';										
								}
								$article_array['currency']=$article_details[34];
								$article_array['publish_language']=$article_details[35];
								$article_array['product']=$article_details[37];
								$article_array['ebooker_cat_id']=$article_details[38];
								$article_array['ebooker_sampletxt_id']=$article_details[39];
								$article_array['ebooker_tokenids']=$article_details[40];
								
								
								$article_array["status"]	 = "new";  
								$article_array["created_by"] ='AUTO';									
								$article_array["contrib_percentage"]='100';
								$article_array["paid_status"]='paid';
								$article_array["participation_expires"]=time();							
								$article_array["column_xls"]=$article_details['xls_columns'];
							
								//new fields
								$article_array["selection_time"]=($delivery_array["selection_time"]);
								$article_array["view_to"]=$delivery_array["view_to"];
								$article_obj->insertArticle($article_array);
								//echo "<pre>";print_r($article_array);
							}
						}
					}
				}				
			}
		}	
	}		
	
	//get available stencils to translateStencils
	function getAvailableStencilsTranslateAction()
	{
		$Params=$this->_request->getParams();
		$translation_lang=$Params['language'];
		$token_id=$Params['token_id'];
		if($translation_lang && $token_id)
		{
			$ebooker_obj = new Ep_Ebookers_Stencils();
			$translateStencils=$ebooker_obj->getStencilsForTranslation($translation_lang,$token_id);
			if($translateStencils)	
				$stencilCount=count($translateStencils);
			else
				$stencilCount=0;
		}
		else
			$stencilCount=0;
		
		echo json_encode(array("stencil_count"=>$stencilCount));exit;
		
	}
}
?>
