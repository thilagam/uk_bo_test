<?php
/**
 * Webservice Controller to interact FR with UK Data
 * @author Arun
 * @version 1.0
 */
class WebserviceController extends Ep_Controller_Action
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
		
        
        $this->quote_creation = Zend_Registry::get('Quote_creation_new');

        $this->product_array=array(
    							"redaction"=>"R&eacute;daction",
								"translation"=>"Traduction",
								"autre"=>"Autre",
								"proofreading"=>"Correction",
								"content_strategy"=>"Content Strategy"
        						);
        $this->seo_product_array=array(
        						"seo_audit"=>"SEO audit",
        						"smo_audit"=>"SMO audit",
    							"redaction"=>"R&eacute;daction",
								"translation"=>"Traduction",
								"proofreading"=>"Correction",
								"autre"=>"Autre"
        						);

        $this->_view->producttype_array=$this->producttype_array=array(
    							"article_de_blog"=>"Article de blog",
								"descriptif_produit"=>"Desc.Produit",
								"article_seo"=>"Article SEO",
								"guide"=>"Guide",
								"news"=>"News",
								"autre"=>"Autres"
        						);

		$this->_view->seo_producttype_array=$this->seo_producttype_array=array(
    							"analyse_content_seo"=>"Analyse SEO"
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
								"quote_permanently_lost"=>'Devis d&#233;finitivement perdu',
								"project_cancelled"=>'Projet annul&eacute;',
								"delivery_time_long"=>'D&eacute;lai livraison trop long',
								"test_art_prob"=>'Probl&egrave;me article test',
							);
							
		$this->_view->tempo_duration_array=$this->duration_array=array(
							"days"=>"Jours",
							"week"=>"Semaine",
							"month"=>"Mois",
							"year"=>"An"
						);					
							
		$this->_view->duration_array=array(
							"days"=>"Jours"
						);	
		$this->_view->volume_option_array=$this->volume_option_array=array(
							"every"=>"Tous les",
							"within"=>"Sous"
						);	
						
		$this->_view->tempo_array=$this->tempo_array=array(
							"fix"=>"Fixe",
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
		
		if($_SERVER['REMOTE_ADDR'] !='185.103.143.52' && $_SERVER['REMOTE_ADDR'] !='5.172.177.141' && $_SERVER['REMOTE_ADDR'] !='5.172.177.148')
		{
			Die("Access Restricted");
		}
		
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
	/*History Mission details popup*/
	function historyMissionDetailsAction()
	{		
		$hmission_obj=new Ep_Quote_HistoryQuoteMissions();
		
		$mission_params=$this->_request->getParams();		
		$mission_id=$mission_params['mission_id'];
		
		if($mission_id)
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
					$historyMission['company_name']	=utf8_encode($company_name);
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
				//echo "<pre>";print_r($historyMission);exit;
				
				if($historyMission)
				{
					echo json_encode($historyMission);
				}
				else{
					echo json_encode(array());
				}
			}
		}		
		
	}
	/*get HOQ prices*/
	public function getHoqPricesAction()
	{			
		$mission_params=$this->_request->getParams();
		
		$searchParameters['product']=$mission_params['product'];
		$searchParameters['language_source']=$mission_params['language_source'];
		$searchParameters['language_dest']=$mission_params['language_dest'];
		$searchParameters['product_type']=$mission_params['product_type'];
		$searchParameters['selected_mission']=$mission_params['selected_mission'];
		$searchParameters['mission_id']=$mission_params['mission_id'];
		$searchParameters['nb_words']=$mission_params['nb_words'];
		$searchParameters['mcurrency']=$mission_params['mcurrency'];
		
		$hmission_obj=new Ep_Quote_HistoryQuoteMissions();
		
		$missionDetails=array();
		$missionDetails=$hmission_obj->getMissionDetails($searchParameters,0);
		
		foreach($missionDetails as $k=>$mission)
		{
			$client_obj=new Ep_Quote_Client();
			$clientDetails=$client_obj->getClientDetails($mission['client_id']);
			if($clientDetails!='NO')
			{
				$client_info=$clientDetails[0];	
				
				$company_name=$client_info['company_name'];
				$missionDetails[$k]['company_name']=utf8_encode($company_name);
			}
		}					
		
		header('Content-type: application/json');
		if($missionDetails)
		{
			echo json_encode($missionDetails);
		}
		else{
			echo json_encode(array());
		}
			
		//echo "<pre>";print_r($missionDetails);exit;
	}
	/*get prod missions of a selected Mission*/
	function getProdMissionDetailsAction()
	{
		$mission_params=$this->_request->getParams();
		$mission_id=$mission_params['mission_id'];
		
		if($mission_id)
		{
			$searchParameters['quote_mission_id']=$mission_params['mission_id'];
			$prod_mission_obj=new Ep_Quote_ProdMissions();
			$prod_details=$prod_mission_obj->getProdMissionDetails($searchParameters);
			header('Content-type: application/json');
			if($prod_details)
			{
				echo json_encode($prod_details);
			}
			else{
				echo json_encode(array());
			}
		}	
	}
	
}	