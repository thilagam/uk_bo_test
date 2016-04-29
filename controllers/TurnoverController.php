<?php
/**
 * Turnover for split/month, real and difference in Workplace
 * @version 1.0
*/
class TurnoverController extends Ep_Controller_Action
{
	public function init()
	{
		parent::init();	
		$this->_view->year_list = $this->year_array = array('2014','2015','2016','2017');
		$this->_view->fo_path = $this->_config->path->fo_path;
		$this->product_array=array(
    							"redaction"=>"R&eacute;daction",
								"translation"=>"Traduction",
								"autre"=>"Autre",
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
								"autre"=>"Autres"
								);
		$this->adminLogin = Zend_Registry::get('adminLogin');
		//setlocale(LC_TIME, "fr_FR");
		$this->_view->user_type= $this->adminLogin->type ;
		$this->_view->userId = $this->adminLogin->userId;
		$this->month_array=array( "01", "02", "03", "04", "05", "06", "07", "08", "09","10", "11", "12" );
		$this->month_details=array( "01"=>"January", "02"=>"February", "03"=>"March", "04"=>"April", "05"=>"May", "06"=>"June", "07"=>"July", "08"=>"August", "09"=>"Septemper","10"=>"October", "11"=>"November", "12"=>"December" );
		$this->month_days=array( "01"=>"31", "02"=>"28", "03"=>"31", "04"=>"30", "05"=>"31", "06"=>"30", "07"=>"31", "08"=>"31", "09"=>"30","10"=>"31", "11"=>"30", "12"=>"31" );
		$this->_view->month_array_val=array("01"=> ucfirst(strftime("%b", mktime(null, null, null, '01'))), "02"=>ucfirst(strftime("%b", mktime(null, null, null, '02',1))),
			"03"=>ucfirst(strftime("%b", mktime(null, null, null, '03'))),"04"=> ucfirst(strftime("%b", mktime(null, null, null, '04'))),"05"=>ucfirst(strftime("%b", mktime(null, null, null, '05'))),
			"06"=> ucfirst(strftime("%b", mktime(null, null, null, '06'))),"07"=> ucfirst(strftime("%b", mktime(null, null, null, '07'))),"08"=> ucfirst(strftime("%b", mktime(null, null, null, '08'))),
			  "09"=> ucfirst(strftime("%b", mktime(null, null, null, '09'))),"10"=>ucfirst(strftime("%b", mktime(null, null, null, '10'))), 
			  "11"=>ucfirst(strftime("%b", mktime(null, null, null, '11'))),"12"=> ucfirst(strftime("%b", mktime(null, null, null, '12'))) );
		
	}
	
	/* Turnover Followup page of contracts and missions based on delivery and articles */
	function realMonthAction()
	{
		$request = $this->_request->getParams();
		$turnover_obj = new Ep_Quote_Turnover();
		$contract_obj = new Ep_Quote_Quotecontract();
		if($request['year'])
			$this->_view->year = $request['year'];
		else
		{
			$request['year'] = $this->_view->year = date("Y");
		}
		$this->_view->client = $request['client'];
		//sales details
		$client_reallist = array();
		$contracts = $contract_obj->getContracts(array('not_mulitple_status'=>"'deleted','sales'",'client_id'=>$request['client']));
		foreach($contracts as $contract)
		{
		$salesrealDetails[$contract['sales_creator_id']]=$contract['first_name'].'&nbsp;'.$contract['last_name'];
		}
		$realturnovers = $turnover_obj->getRealTurnovers($request);
		//echo "<pre>"; print_r($realturnovers); exit;
		$monthturnovers = array();
		/* Turnover of Prodmissions */
		foreach($realturnovers as $realturnover)
		{
			//$monthturnovers[$realturnover['user_id']][$realturnover['yearmonth']] = $realturnover['publishedprice'];
			$monthturnovers[$realturnover['user_id']][$realturnover['yearmonth']] = $realturnover['total'];
			$monthturnovers[$realturnover['user_id']]['other_info'] = $realturnover;

			if($monthturnovers[$realturnover['user_id']][$realturnover['yearmonth']]==0)
            {
            	unset($monthturnovers[$realturnover['user_id']]);	
            }
		}
		/* Turnover of Seomissions */
		$realseoturnovers = $turnover_obj->getRealSeoMission($request);
		foreach($realseoturnovers as $realturnover)
		{
			$monthturnovers[$realturnover['user_id']][$realturnover['yearmonth']] += $realturnover['missionturnover'];
			$monthturnovers[$realturnover['user_id']]['other_info'] = $realturnover;

			if($monthturnovers[$realturnover['user_id']][$realturnover['yearmonth']]==0)
            {
            	unset($monthturnovers[$realturnover['user_id']]);	
            }
			
		}
		/* Turnover of Techmissions */
		$realtechturnovers = $turnover_obj->getRealTechMission($request);
		foreach($realtechturnovers as $realturnover)
		{
			$monthturnovers[$realturnover['user_id']][$realturnover['yearmonth']] += $realturnover['missionturnover'];
			$monthturnovers[$realturnover['user_id']]['other_info'] = $realturnover;
			$client_reallist[$realturnover['user_id']]=$realturnover['company_name'];

			if($monthturnovers[$realturnover['user_id']][$realturnover['yearmonth']]==0)
            {
            	unset($monthturnovers[$realturnover['user_id']]);	
            }
		}
		$real_client = $turnover_obj->getRealTurnovers(array('year'=>$request['year']));
		$realtotalturnovereuro=0;
		$realtotalturnoverpound=0;
		foreach($real_client as $client_list)
		{
		$client_reallist[$client_list['user_id']]=$client_list['company_name'];
			if($client_list['currency']=='euro')
			{
			$realtotalturnovereuro += $client_list['total'];
			}
			else
			{
			$realtotalturnoverpound += $client_list['total'];
			}
			if($client_list['total']==0)
			{
				unset($client_reallist[$client_list['user_id']]);
			}
		}
		$real_seo_clients = $turnover_obj->getRealSeoMission(array('year'=>$request['year']));
		foreach($real_seo_clients as $client_list)
		{
			$client_reallist[$client_list['user_id']]=$client_list['company_name'];
			if($client_list['currency']=='euro')
			{
			$realtotalturnovereuro += $client_list['missionturnover'];
			}
			else
			{
			$realtotalturnoverpound += $client_list['missionturnover'];
			}

			if($client_list['missionturnover']==0)
			{
				unset($client_reallist[$client_list['user_id']]);
			}
		}
		$real_tech_clients = $turnover_obj->getRealTechMission(array('year'=>$request['year']));
		foreach($real_tech_clients as $client_list)
		{
			$client_reallist[$client_list['user_id']]=$client_list['company_name'];
			if($client_list['currency']=='euro')
			{
			$realtotalturnovereuro += $client_list['missionturnover'];
			}
			else
			{
			$realtotalturnoverpound += $client_list['missionturnover'];
			}

			if($client_list['missionturnover']==0)
			{
				unset($client_reallist[$client_list['user_id']]);
			}
		}
		natcasesort($client_reallist);
		$this->_view->monthturnovers = $monthturnovers;
		$this->_view->clients = $client_reallist;
		$this->_view->totalturnovereuro = $realtotalturnovereuro;
		$this->_view->totalturnoverpound = $realtotalturnoverpound;
		natcasesort($salesrealDetails);
		$this->_view->salesusers = $salesrealDetails;
		//$this->_view->clients = $contract_obj->getUsers('client');
		$this->render('real-month-turnover');
	}
	
	/* Real month turnover for a client */
	function realMonthClientFocusAction()
	{
		$request = $this->_request->getParams();
		$search = array();
		if($request['client']&&$request['year'])
		{
			$year = $request['year'];
			$turnover_obj = new Ep_Quote_Turnover();
			$quotecontract_obj = new Ep_Quote_Quotecontract();
			$contracts = $quotecontract_obj->getContracts(array('client_id'=>$request['client'],'not_mulitple_status'=>"'deleted','sales'",'sales_id'=>$request['sales_id']));
		
			$client_obj = new Ep_Quote_Client();		
			$client_details = $client_obj->getClientDetails($request['client']);
			$this->_view->client_details = $client_details[0];
			$this->_view->year = $request['year'];
			$contractturnover = array();
			$canvas_real = $canvas_expected = array($year.'-01'=>0,$year.'-02'=>0,$year.'-03'=>0,$year.'-04'=>0,$year.'-05'=>0,$year.'-06'=>0,$year.'-07'=>0,$year.'-08'=>0,$year.'-09'=>0,$year.'-10'=>0,$year.'-11'=>0,$year.'-12'=>0);
		
			foreach($contracts as $contract)
			{
				$contractturnover[$contract['quotecontractid']]['contract_details'] = $contract;
				/* Real turnover and missions */
				 $filters_product = $turnover_obj->getProdSeoMissions(array('contract_id'=>$contract['quotecontractid']));
				foreach($filters_product as $filetercontract)
				{
					$product_total_array[$filetercontract['product']] = $this->product_array[$filetercontract['product']];
					$procuct_type_array[$filetercontract['product_type']]=$this->producttype_array[$filetercontract['product_type']];
				}
				$techParameters = array();
				$techParameters['quote_id']= $contract['quoteid'];
				$techParameters['contract_id']= $contract['quotecontractid'];
				$techParameters['include_final']='yes';
				$techMissionDetails=$turnover_obj->getTechMissionDetails($techParameters);
					foreach($techMissionDetails as $techtitlemisson)
					 {
					  $key=str_replace(' ','_',$techtitlemisson['title']);
					  $product_total_array[$key] = $techtitlemisson['title'];
					} 
				/*End Filters*/
				$cmissiondetails = $turnover_obj->getProdSeoMissions(array('contract_id'=>$contract['quotecontractid'],'product'=>$request['product'],'product_type'=>$request['p_type']));
				foreach($cmissiondetails as $contract_mission)
				{
					$contractturnover[$contract['quotecontractid']][$contract_mission['qmid']] = $contract_mission;
					
					if($contract_mission['contractmissionid'] && $contract_mission['type']=="prod")
					{
						$years_mission = $turnover_obj->getRealTurnovers(array('contract_mission_id'=>$contract_mission['contractmissionid'],'year'=>$year));
					
						foreach($years_mission as $yearm)
						{
							$real_price = $contract_mission['unit_price']*$yearm['total_packs'];
							/* $contractturnover[$contract['quotecontractid']][$contract_mission['qmid']][$yearm['yearmonth']]  =$yearm['publishedprice'];
							$contractturnover[$contract['quotecontractid']]['contract_details'][$yearm['yearmonth']] +=  $yearm['publishedprice'];
							$contractturnover[$contract['quotecontractid']]['contract_details']['realturnover'] += $yearm['publishedprice'];
							$canvas_real[$yearm['yearmonth']] += $yearm['publishedprice']; */
							$contractturnover[$contract['quotecontractid']][$contract_mission['qmid']][$yearm['yearmonth']]  =$real_price;
							$contractturnover[$contract['quotecontractid']]['contract_details'][$yearm['yearmonth']] +=  $real_price;
							$contractturnover[$contract['quotecontractid']]['contract_details']['realturnover'] += $real_price;
							$canvas_real[$yearm['yearmonth']] += $real_price;
						}
					}
					else
					{
						if($year==$contract_mission['validatedyear'])
						{
							$contractturnover[$contract['quotecontractid']][$contract_mission['qmid']][$contract_mission['validatedyearmonth']]  = $contract_mission['missionturnover'];
							$contractturnover[$contract['quotecontractid']]['contract_details'][$contract_mission['validatedyearmonth']] += $contract_mission['missionturnover'];
							$canvas_real[$contract_mission['validatedyearmonth']] +=  $contract_mission['missionturnover'];
							$contractturnover[$contract['quotecontractid']]['contract_details']['realturnover'] += $contract_mission['missionturnover'];
						}
						$contractturnover[$contract['quotecontractid']][$contract_mission['qmid']]['type']  = "seo";
					}
					/* To title / name of the mission */
					$contractturnover[$contract['quotecontractid']][$contract_mission['qmid']]['title_other']=$contract_mission['product_type_other'];
					if($contract_mission['product']=='translation')
					{
						$contractturnover[$contract['quotecontractid']][$contract_mission['qmid']]['title'] = $this->product_array[$contract_mission['product']]." ".$this->producttype_array[$contract_mission['product_type']]." ".$this->getCustomName("EP_LANGUAGES",$contract_mission['language_source'])." vers ".$this->getCustomName("EP_LANGUAGES",$contract_mission['language_dest']);
					}
					else
					{
						$contractturnover[$contract['quotecontractid']][$contract_mission['qmid']]['title'] = $this->product_array[$contract_mission['product']]." ".$this->producttype_array[$contract_mission['product_type']]." en ".$this->getCustomName("EP_LANGUAGES",$contract_mission['language_source']);
						$array['lang'] = $this->getCustomName("EP_LANGUAGES",$contract_mission['language_source']);
					}
					/* Check assigned if assigned get name */
					$contractturnover[$contract['quotecontractid']][$contract_mission['qmid']]['assigned_to'] = $contract_mission['assigned_to'];
					$contractturnover[$contract['quotecontractid']][$contract_mission['qmid']]['assigned_name'] = $contract_mission['first_name']." ".$contract_mission['last_name'];
					
					/* Check edited and deleted */
					if($contract_mission['is_edited'])
					{
						$updated_at = date("Y-m",strtotime($contract_mission['updated_at']));
						$contractturnover[$contract['quotecontractid']][$contract_mission['qmid']]['edited_at'.$updated_at]  ="missionEdited" ;
					} 
					if($contract_mission['cm_status']=="deleted")
					{
						$updated_at = date("Y-m",strtotime($contract_mission['cmupdated_at']));
						$contractturnover[$contract['quotecontractid']][$contract_mission['qmid']]['deleted_at'.$updated_at]  ="missionDeleted" ;
					}
					if($contract_mission['year_freeze_start_date']==$request['year'] || $contract_mission['year_freeze_end_date']==$request['year'])
					{
						$contract_mission['freeze_start_date']." ".$contract_mission['freeze_end_date']."<br>";
						$date1  = $contract_mission['freeze_start_date'];
						$date2  = $contract_mission['freeze_end_date'];
						$output = array();
						$time   = strtotime($date1);
						$last   = date('m-Y', strtotime($date2));

						do 
						{
							$month = date('m-Y', $time);
							$month_year = date('Y-m', $time);
							
						$contractturnover[$contract['quotecontractid']][$contract_mission['qmid']]['freezed_at'.$month_year] = "missionFreezed";

							$time = strtotime('+1 month', $time);
						} while ($month != $last);
						
						
					}
					
				}
				
				/* To get tech missions and turnover */
				$searchParameters = array();
				$searchParameters['quote_id']= $contract['quoteid'];
				$searchParameters['contract_id']= $contract['quotecontractid'];
				$searchParameters['include_final']='yes';
				if($request['product']) $searchParameters['product']=str_replace("_",' ',$request['product']);
				if($request['p_type']) $searchParameters['p_type']=str_replace("_",' ',$request['p_type']);
				$techMissionDetails=$turnover_obj->getTechMissionDetails($searchParameters);
				
				foreach($techMissionDetails as $tech_mission)
				{
					if($year==$tech_mission['validatedyear'])
					{
						$contractturnover[$contract['quotecontractid']][$tech_mission['identifier']][$tech_mission['validatedyearmonth']]  = $tech_mission['turnover'];
						$contractturnover[$contract['quotecontractid']]['contract_details'][$tech_mission['validatedyearmonth']] += $tech_mission['turnover'];
						$canvas_real[$tech_mission['validatedyearmonth']] +=  $tech_mission['turnover'];
						$contractturnover[$contract['quotecontractid']]['contract_details']['realturnover'] +=  $tech_mission['turnover'];
					}
					$contractturnover[$contract['quotecontractid']][$tech_mission['identifier']]['type']  = "tech";
					$contractturnover[$contract['quotecontractid']][$tech_mission['identifier']]['title'] = $tech_mission['title'];
					$contractturnover[$contract['quotecontractid']][$tech_mission['identifier']]['assigned_to'] = $tech_mission['assigned_to'];
					$contractturnover[$contract['quotecontractid']][$tech_mission['identifier']]['assigned_name'] = $tech_mission['first_name']." ".$tech_mission['last_name'];
				}
				
				/* To get expected turnover by Naveen */
				$searchcontract['contract_id']=$contract['quotecontractid'];
				if($request['p_type']){
					$searchcontract['name']=$this->producttype_array[$request['p_type']];
					}
				if($request['product']){
					$searchcontract['product']=$this->product_array[$request['product']];
					}
				$splitmonthturnoverclient=$turnover_obj->getSplitTurnoversclients($searchcontract);
			
				$clienttrunarray = array();
				$clienttrunarray=$this->clientcontractmissionloop($splitmonthturnoverclient,$contract,$clienttrunarray,$year);
			 	
				foreach($clienttrunarray as $contractarray => $cvalue)
				{
					foreach($cvalue as $key => $value)
					{
						if(is_array($value))
						{
							foreach($value as $mission => $mvalue)
							{
								$contractturnover[$contract['quotecontractid']][$key]['expected_'.$mission] = $mvalue;
							}
						}
						else
						{
							$contractturnover[$contract['quotecontractid']]['contract_details']['expected_'.$key] = $value;
						}

						if(strpos($key,"-"))
						{
							$canvas_expected[$key] += $value;
						}

					}
				}
			} /* Contracts loop */
		 	
		/* 	echo "<PRE>";
			print_r($contractturnover);
			exit;   */  
			//print_r($canvas_expected);			
			$this->_view->contractturnover = $contractturnover;
			/* Fetching sales user and other details for search */
			/*if($this->_view->user_type=='superadmin')
				$salesusers = $quotecontract_obj->getUsers('salesuser',true);
			else 
				$salesusers = $quotecontract_obj->getUsers('salesuser');*/
			natsort($product_total_array);
			natsort($procuct_type_array);
			$contracts = $quotecontract_obj->getContracts(array('client_id'=>$request['client'],'not_mulitple_status'=>"'deleted','sales'"));
			$salesrealDetails = array();
			foreach($contracts as $contract)
			$salesrealDetails[$contract['sales_creator_id']]=$contract['first_name'].'&nbsp;'.$contract['last_name'];
			natsort($salesrealDetails);
			$this->_view->salesusers = $salesrealDetails;
			$this->_view->product_type = $procuct_type_array;
			$this->_view->product_array = $product_total_array;
			
			$this->_view->canvas_real = $canvas_real;
			$this->_view->canvas_expected = $canvas_expected;
			$this->_view->client_id = $request['client'];
			$this->_view->two_year = substr($request['year'],2);
			$this->render('real-month-clientfocus');
		}
	}
	
	function getCustomName($type,$name)
	{
		$categories_array = $this->_arrayDb->loadArrayv2($type, $this->_lang);
		return $categories_array[$name];
	}
	
	//contract total details details
	function clientcontracttotalloop($splitmonthturnoverclient,$clientloop,$totaltrunarray)
	{		
		$request = $this->_request->getParams();
		//echo "<pre>";print_r($request);exit;
		foreach($splitmonthturnoverclient as $totalbyclient)
		{
			//echo "<pre>";print_r($splitmonthturnoverclient);exit;
			//split turnover for total contract				
			$year_montharray=explode('-',$totalbyclient['month_year']);
			if($request['pm'])
			{
			if($year_montharray[0]==$this->_view->default_year)
			{
				
				if(array_key_exists($totalbyclient['month_year'],$totaltrunarray[$clientloop['client_id']]))
				{
				$totaltrunarray[$clientloop['client_id']][$totalbyclient['month_year']]=$totaltrunarray[$clientloop['client_id']][$totalbyclient['month_year']]+$totalbyclient['turnover'];
				}
				else
				{
				$totaltrunarray[$clientloop['client_id']][$totalbyclient['month_year']]=$totalbyclient['turnover'];
				}
					/*Margin calculation*/
					if(array_key_exists('margin-'.$totalbyclient['month_year'],$totaltrunarray[$clientloop['client_id']]))
					{
					$totaltrunarray[$clientloop['client_id']]['intcost-'.$totalbyclient['month_year']]=$totalbyclient['max_cost']+$totaltrunarray[$clientloop['client_id']]['intcost-'.$totalbyclient['month_year']];
					$totaltrunarray[$clientloop['client_id']]['unitpri-'.$totalbyclient['month_year']]=$totalbyclient['unit_price']+$totaltrunarray[$clientloop['client_id']]['unitpri-'.$totalbyclient['month_year']];
					$totaltrunarray[$clientloop['client_id']]['margin-'.$totalbyclient['month_year']]=(100-($totaltrunarray[$clientloop['client_id']]['intcost-'.$totalbyclient['month_year']]/$totaltrunarray[$clientloop['client_id']]['unitpri-'.$totalbyclient['month_year']])*100);
					}
					else
					{
					$totaltrunarray[$clientloop['client_id']]['intcost-'.$totalbyclient['month_year']]=$totalbyclient['max_cost'];
					$totaltrunarray[$clientloop['client_id']]['unitpri-'.$totalbyclient['month_year']]=$totalbyclient['unit_price'];
					$totaltrunarray[$clientloop['client_id']]['margin-'.$totalbyclient['month_year']]=(100-($totalbyclient['max_cost']/$totalbyclient['unit_price'])*100);
					}
					$totaltrunarray[$clientloop['client_id']]['turnover']=$totaltrunarray[$clientloop['client_id']]['turnover']+$totalbyclient['turnover'];
					$totaltrunarray[$clientloop['client_id']]['intcost']=$totaltrunarray[$clientloop['client_id']]['intcost']+$totalbyclient['max_cost'];
					$totaltrunarray[$clientloop['client_id']]['unitpri']=$totaltrunarray[$clientloop['client_id']]['unitpri']+$totalbyclient['unit_price'];
					$totaltrunarray[$clientloop['client_id']]['margin_percentage']=(100-($totaltrunarray[$clientloop['client_id']]['intcost']/$totaltrunarray[$clientloop['client_id']]['unitpri'])*100);
					$totaltrunarray[$clientloop['client_id']]['sales_suggested_currency'] = $clientloop['sales_suggested_currency'];	
				
				}
			}
			else
			{
					if($year_montharray[0]==$this->_view->default_year)
					{
						if(array_key_exists($totalbyclient['month_year'],$totaltrunarray[$clientloop['client_id']]))
						{
						$totaltrunarray[$clientloop['client_id']][$totalbyclient['month_year']]=$totaltrunarray[$clientloop['client_id']][$totalbyclient['month_year']]+$totalbyclient['turnover'];
						}
						else
						{
						$totaltrunarray[$clientloop['client_id']][$totalbyclient['month_year']]=$totalbyclient['turnover'];
						}
				$totaltrunarray[$clientloop['client_id']]['turnover']=$totaltrunarray[$clientloop['client_id']]['turnover']+$totalbyclient['turnover'];
				$totaltrunarray[$clientloop['client_id']]['sales_suggested_currency'] = $clientloop['sales_suggested_currency'];	
					}	
			}
										
		}
		return $totaltrunarray;							 
	}
		
	//contract Mission details
	function clientcontractmissionloop($splitmonthturnoverclient,$clientloop,$clienttrunarray,$default_year="")
	{
		//echo "<pre>";print_r($splitmonthturnoverclient);exit;
		$request = $this->_request->getParams();
		//echo "<pre>";print_r($request);exit;
		$contract_obj = new Ep_Quote_Quotecontract();
		 $turnover_obj = new Ep_Quote_Turnover();
		$cc=0;
		if(!$default_year)
			$default_year = $this->_view->default_year;
		//$clienttrunarray=array();
		foreach($splitmonthturnoverclient as $splitbyclient)
		{ //split turnover for contract 
			
			$year_montharray=explode('-',$splitbyclient['month_year']);
			if(!in_array($splitbyclient['mission_id'],$clienttrunarray[$clientloop['quotecontractid']]))
				{
					$clienttrunarray[$clientloop['quotecontractid']][$cc]=$splitbyclient['mission_id'];
					$clienttrunarray[$clientloop['quotecontractid']]['client_id']=$clientloop['client_id'];
				}
				if($request['pm']=='true')
				{
					if($year_montharray[0]==$default_year || $year_montharray[0]<=$request['end_year'])
			{
						$end_year=$request['end_yeay'];
				if(array_key_exists($splitbyclient['month_year'],$clienttrunarray[$clientloop['quotecontractid']]))
				{
				$clienttrunarray[$clientloop['quotecontractid']][$splitbyclient['month_year']]=$clienttrunarray[$clientloop['quotecontractid']][$splitbyclient['month_year']]+$splitbyclient['turnover'];
				}
				else
				{
				$clienttrunarray[$clientloop['quotecontractid']][$splitbyclient['month_year']]=$splitbyclient['turnover'];
				}
					/*Margin calculation*/
						if($request['pm']=='true')
						{
						if(array_key_exists('margin-'.$splitbyclient['month_year'],$splitbyclient[$clientloop['client_id']]))
						{
				
						$clienttrunarray[$clientloop['quotecontractid']]['intcost-'.$splitbyclient['month_year']]=$splitbyclient['max_cost']+$clienttrunarray[$clientloop['quotecontractid']]['intcost-'.$splitbyclient['month_year']];
						$clienttrunarray[$clientloop['quotecontractid']]['unitpri-'.$splitbyclient['month_year']]=$splitbyclient['unit_price']+$clienttrunarray[$clientloop['quotecontractid']]['unitpri-'.$splitbyclient['month_year']];
						if($splitbyclient['turnover'])$clienttrunarray[$clientloop['quotecontractid']]['margin-'.$splitbyclient['month_year']]=(100-($totaltrunarray[$clientloop['quotecontractid']]['intcost-'.$splitbyclient['month_year']]/$clienttrunarray[$clientloop['quotecontractid']]['unitpri-'.$splitbyclient['month_year']])*100);
						}
						else
						{
						$clienttrunarray[$clientloop['quotecontractid']]['intcost-'.$splitbyclient['month_year']]=$splitbyclient['max_cost'];
						$clienttrunarray[$clientloop['quotecontractid']]['unitpri-'.$splitbyclient['month_year']]=$splitbyclient['unit_price'];
						if($splitbyclient['turnover'])$clienttrunarray[$clientloop['quotecontractid']]['margin-'.$splitbyclient['month_year']]=(100-($splitbyclient['max_cost']/$splitbyclient['unit_price'])*100);
						}
						$clienttrunarray[$clientloop['quotecontractid']]['intcost']=$clienttrunarray[$clientloop['client_id']]['intcost']+$splitbyclient['max_cost'];
						$clienttrunarray[$clientloop['quotecontractid']]['unitpri']=$clienttrunarray[$clientloop['client_id']]['unitpri']+$splitbyclient['unit_price'];
						if($splitbyclient['turnover'])$clienttrunarray[$clientloop['quotecontractid']]['margin_percentage']=(100-($clienttrunarray[$clientloop['quotecontractid']]['intcost']/$clienttrunarray[$clientloop['quotecontractid']]['unitpri'])*100);
						}
				$clienttrunarray[$clientloop['quotecontractid']]['turnover']=$clienttrunarray[$clientloop['quotecontractid']]['turnover']+$splitbyclient['turnover'];
				$clienttrunarray[$clientloop['quotecontractid']]['sales_suggested_currency'] = $clientloop['sales_suggested_currency'];	
			
			//contract Mission details
					 
					if(array_key_exists($splitbyclient['month_year'],$clienttrunarray[$clientloop['quotecontractid']][$splitbyclient['mission_id']]))
					{
					$clienttrunarray[$clientloop['quotecontractid']][$splitbyclient['mission_id']][$splitbyclient['month_year']]=$clienttrunarray[$clientloop['quotecontractid']][$splitbyclient['mission_id']][$splitbyclient['month_year']]+$splitbyclient['turnover'];
					
					}
					else
					{
					$clienttrunarray[$clientloop['quotecontractid']][$splitbyclient['mission_id']][$splitbyclient['month_year']]=$splitbyclient['turnover'];
					}
					if($request['pm']=='true')
					{
						if(array_key_exists('margin-'.$splitbyclient['month_year'],$splitbyclient[$clientloop['client_id']]))
						{
					
						$clienttrunarray[$clientloop['quotecontractid']][$splitbyclient['mission_id']]['intcost-'.$splitbyclient['month_year']]=$splitbyclient['max_cost']+$clienttrunarray[$clientloop['quotecontractid']]['intcost-'.$splitbyclient['month_year']];
						$clienttrunarray[$clientloop['quotecontractid']][$splitbyclient['mission_id']]['unitpri-'.$splitbyclient['month_year']]=$splitbyclient['unit_price']+$clienttrunarray[$clientloop['quotecontractid']]['unitpri-'.$splitbyclient['month_year']];
						$clienttrunarray[$clientloop['quotecontractid']][$splitbyclient['mission_id']]['margin-'.$splitbyclient['month_year']]=(100-($totaltrunarray[$clientloop['quotecontractid']][$splitbyclient['mission_id']]['intcost-'.$splitbyclient['month_year']]/$clienttrunarray[$clientloop['quotecontractid']][$splitbyclient['mission_id']]['unitpri-'.$splitbyclient['month_year']])*100);
					}
						else
						{
						$clienttrunarray[$clientloop['quotecontractid']][$splitbyclient['mission_id']]['intcost-'.$splitbyclient['month_year']]=$splitbyclient['max_cost'];
						$clienttrunarray[$clientloop['quotecontractid']][$splitbyclient['mission_id']]['unitpri-'.$splitbyclient['month_year']]=$splitbyclient['unit_price'];
						$clienttrunarray[$clientloop['quotecontractid']][$splitbyclient['mission_id']]['margin-'.$splitbyclient['month_year']]=(100-($splitbyclient['max_cost']/$splitbyclient['unit_price'])*100);
						}
						$clienttrunarray[$clientloop['quotecontractid']][$splitbyclient['mission_id']]['intcost']=$clienttrunarray[$clientloop['client_id']]['intcost']+$splitbyclient['max_cost'];
						$clienttrunarray[$clientloop['quotecontractid']][$splitbyclient['mission_id']]['unitpri']=$clienttrunarray[$clientloop['client_id']]['unitpri']+$splitbyclient['unit_price'];
						if($clienttrunarray[$clientloop['quotecontractid']][$splitbyclient['mission_id']]['intcost']!=0)
							$clienttrunarray[$clientloop['quotecontractid']][$splitbyclient['mission_id']]['margin_percentage']=(100-($clienttrunarray[$clientloop['quotecontractid']][$splitbyclient['mission_id']]['intcost']/$clienttrunarray[$clientloop['quotecontractid']][$splitbyclient['mission_id']]['unitpri'])*100);
						else
							$clienttrunarray[$clientloop['quotecontractid']][$splitbyclient['mission_id']]['margin_percentage']=0;
					}	
					//get Mission details
				$clienttrunarray[$clientloop['quotecontractid']][$splitbyclient['mission_id']]['turnover']=$clienttrunarray[$clientloop['quotecontractid']][$splitbyclient['mission_id']]['turnover']+$splitbyclient['turnover'];	
				$clienttrunarray[$clientloop['quotecontractid']][$splitbyclient['mission_id']][$splitbyclient['month_year'].'-from_followup']=$splitbyclient['from_followup'];
				
					if($this->assigned_to)
					{
						$assigned_toval=$this->assigned_to;
					}
					else
					{
						$assigned_toval=$request['assigned_to'];
					}
					$contractMissoinType= $turnover_obj->getMissionDetails($splitbyclient['mission_id'],array('product'=>$request['product'],'cid'=>$clientloop['quotecontractid'],'assigned_to'=>$assigned_toval));
					if(!$contractMissoinType)
					{
							$techMissoinType=$turnover_obj->getTechMission($splitbyclient['mission_id'],$clientloop['quotecontractid'],$assigned_toval);
					if($techMissoinType)
					{
					$clienttrunarray[$clientloop['quotecontractid']][$splitbyclient['mission_id']]['mission_type']=$techMissoinType[0]['title'];
						$clienttrunarray[$clientloop['quotecontractid']][$splitbyclient['mission_id']]['assigned_to']=$techMissoinType[0]['assigned_to'];
						$clienttrunarray[$clientloop['quotecontractid']][$splitbyclient['mission_id']]['assigned_name']=$techMissoinType[0]['first_name']." ".$techMissoinType[0]['last_name'];
					}
					
					$clientloop['expected_end_date']=date('Y-m-d', strtotime($clientloop['expected_launch_date']. ' + '.$techMissoinType[0]['delivery_time'].' days'));
							if(date("mY",strtotime($clientloop['expected_launch_date'])) == date("mY",strtotime($clientloop['expected_end_date'])))
							{
									if($this->assigned_to==$techMissoinType[0]['assigned_to'])
									$clienttrunarray[$clientloop['quotecontractid']][$splitbyclient['mission_id']]['cost-'.$splitbyclient['month_year']]=(((100-$techMissoinType[0]['margin_percentage'])/100)*$clienttrunarray[$clientloop['quotecontractid']][$splitbyclient['mission_id']][$splitbyclient['month_year']]);//$techMissoinType[0]['cost']*$techMissoinType[0]['volume'];
							}
							else
							{
								if(date("m",strtotime($clientloop['expected_launch_date']))==$year_montharray[1])
								{
									if($this->assigned_to==$techMissoinType[0]['assigned_to'])
									$clienttrunarray[$clientloop['quotecontractid']][$splitbyclient['mission_id']]['cost-'.$splitbyclient['month_year']]=(((100-$techMissoinType[0]['margin_percentage'])/100)*$clienttrunarray[$clientloop['quotecontractid']][$splitbyclient['mission_id']][$splitbyclient['month_year']]);//($techMissoinType[0]['cost']*$techMissoinType[0]['volume']/$techMissoinType[0]['delivery_time'])*($this->month_days[$year_montharray[1]]-date("d",strtotime($clientloop['expected_launch_date'])));	
								}
								elseif(date("m",strtotime($clientloop['expected_end_date']))==$year_montharray[1])
								{
									if($this->assigned_to==$techMissoinType[0]['assigned_to'])
									$clienttrunarray[$clientloop['quotecontractid']][$splitbyclient['mission_id']]['cost-'.$splitbyclient['month_year']]=(((100-$techMissoinType[0]['margin_percentage'])/100)*$clienttrunarray[$clientloop['quotecontractid']][$splitbyclient['mission_id']][$splitbyclient['month_year']]);//($techMissoinType[0]['cost']*$techMissoinType[0]['volume']/$techMissoinType[0]['delivery_time'])*(date("d",strtotime($clientloop['expected_end_date'])));	
								}else
								{
									if($this->assigned_to==$techMissoinType[0]['assigned_to'])
									$clienttrunarray[$clientloop['quotecontractid']][$splitbyclient['mission_id']]['cost-'.$splitbyclient['month_year']]=(((100-$techMissoinType[0]['margin_percentage'])/100)*$clienttrunarray[$clientloop['quotecontractid']][$splitbyclient['mission_id']][$splitbyclient['month_year']]);//($techMissoinType[0]['cost']*$techMissoinType[0]['volume']/$techMissoinType[0]['delivery_time'])*($this->month_days[$year_montharray[1]]);	
								}
						  
							}
					
					
					
					}
					else
					{
						
							$clienttrunarray[$clientloop['quotecontractid']][$splitbyclient['mission_id']]['mission_type_other'] =$contractMissoinType[0]['product_type_other'];
							if($contractMissoinType[0]['product']=='translation')
							{
							$clienttrunarray[$clientloop['quotecontractid']][$splitbyclient['mission_id']]['mission_type'] = $this->product_array[$contractMissoinType[0]['product']]." ".$this->producttype_array[$contractMissoinType[0]['product_type']]." ".$this->getCustomName("EP_LANGUAGES",$contractMissoinType[0]['language_source'])." vers ".$this->getCustomName("EP_LANGUAGES",$contractMissoinType[0]['language_dest']);
							$clienttrunarray[$clientloop['quotecontractid']][$splitbyclient['mission_id']]['product'] = $this->product_array[$contractMissoinType[0]['product']];
							$clienttrunarray[$clientloop['quotecontractid']][$splitbyclient['mission_id']]['product_type'] = $this->producttype_array[$contractMissoinType[0]['product_type']];
							}
							else
							{
							$clienttrunarray[$clientloop['quotecontractid']][$splitbyclient['mission_id']]['mission_type'] = $this->product_array[$contractMissoinType[0]['product']]." ".$this->producttype_array[$contractMissoinType[0]['product_type']]." en ".$this->getCustomName("EP_LANGUAGES",$contractMissoinType[0]['language_source']);
							$clienttrunarray[$clientloop['quotecontractid']][$splitbyclient['mission_id']]['product'] = $this->product_array[$contractMissoinType[0]['product']];
							$clienttrunarray[$clientloop['quotecontractid']][$splitbyclient['mission_id']]['product_type'] = $this->producttype_array[$contractMissoinType[0]['product_type']];
							}
							$clienttrunarray[$clientloop['quotecontractid']][$splitbyclient['mission_id']]['assigned_to'] = $contractMissoinType[0]['assigned_to'];
							$clienttrunarray[$clientloop['quotecontractid']][$splitbyclient['mission_id']]['assigned_name'] = $contractMissoinType[0]['first_name']." ".$contractMissoinType[0]['last_name'];
							$clientloop['expected_end_date']=date('Y-m-d', strtotime($clientloop['expected_launch_date']. ' + '.$contractMissoinType[0]['mission_length'].' days'));
						if(date("mY",strtotime($clientloop['expected_launch_date'])) == date("mY",strtotime($clientloop['expected_end_date'])) )
						{
						$clienttrunarray[$clientloop['quotecontractid']][$splitbyclient['mission_id']]['cost-'.$splitbyclient['month_year']]=(((100-$contractMissoinType[0]['margin_percentage'])/100)*$clienttrunarray[$clientloop['quotecontractid']][$splitbyclient['mission_id']][$splitbyclient['month_year']]);//$contractMissoinType[0]['internal_cost']*$contractMissoinType[0]['volume'];
						}
						else
						{
							if(date("m",strtotime($clientloop['expected_launch_date']))==$year_montharray[1])
							{
								if($this->assigned_to==$contractMissoinType[0]['assigned_to'])
								$clienttrunarray[$clientloop['quotecontractid']][$splitbyclient['mission_id']]['cost-'.$splitbyclient['month_year']]=(((100-$contractMissoinType[0]['margin_percentage'])/100)*$clienttrunarray[$clientloop['quotecontractid']][$splitbyclient['mission_id']][$splitbyclient['month_year']]);//($contractMissoinType[0]['internal_cost']*$contractMissoinType[0]['volume']/$contractMissoinType[0]['mission_length'])*($this->month_days[$year_montharray[1]]-date("d",strtotime($clientloop['expected_launch_date'])));	
							}
							elseif(date("m",strtotime($clientloop['expected_end_date']))==$year_montharray[1])
							{
								if($this->assigned_to==$contractMissoinType[0]['assigned_to'])
								$clienttrunarray[$clientloop['quotecontractid']][$splitbyclient['mission_id']]['cost-'.$splitbyclient['month_year']]=(((100-$contractMissoinType[0]['margin_percentage'])/100)*$clienttrunarray[$clientloop['quotecontractid']][$splitbyclient['mission_id']][$splitbyclient['month_year']]);//($contractMissoinType[0]['internal_cost']*$contractMissoinType[0]['volume']/$contractMissoinType[0]['mission_length'])*(date("d",strtotime($clientloop['expected_end_date'])));	
							}
							else
							{
								if($this->assigned_to==$contractMissoinType[0]['assigned_to'])
							$clienttrunarray[$clientloop['quotecontractid']][$splitbyclient['mission_id']]['cost-'.$splitbyclient['month_year']]=(((100-$contractMissoinType[0]['margin_percentage'])/100)*$clienttrunarray[$clientloop['quotecontractid']][$splitbyclient['mission_id']][$splitbyclient['month_year']]);//($contractMissoinType[0]['internal_cost']*$contractMissoinType[0]['volume']/$contractMissoinType[0]['mission_length'])*($this->month_days[$year_montharray[1]]);	
							}
							  
						}
							
					
						}
						$clienttrunarray[$clientloop['quotecontractid']]['totalcost'.$splitbyclient['month_year']]+=$clienttrunarray[$clientloop['quotecontractid']][$splitbyclient['mission_id']]['cost-'.$splitbyclient['month_year']];
						$cc++;
					}
				}	
					
			else
			{	
					if($year_montharray[0]==$default_year )
					{
						if(array_key_exists($splitbyclient['month_year'],$clienttrunarray[$clientloop['quotecontractid']]))
						{
						$clienttrunarray[$clientloop['quotecontractid']][$splitbyclient['month_year']]=$clienttrunarray[$clientloop['quotecontractid']][$splitbyclient['month_year']]+$splitbyclient['turnover'];
						}
						else
						{
						$clienttrunarray[$clientloop['quotecontractid']][$splitbyclient['month_year']]=$splitbyclient['turnover'];
						}
							$clienttrunarray[$clientloop['quotecontractid']]['turnover']=$clienttrunarray[$clientloop['quotecontractid']]['turnover']+$splitbyclient['turnover'];
							$clienttrunarray[$clientloop['quotecontractid']]['sales_suggested_currency'] = $clientloop['sales_suggested_currency'];	
					
							//contract Mission details
						
							if(array_key_exists($splitbyclient['month_year'],$clienttrunarray[$clientloop['quotecontractid']][$splitbyclient['mission_id']]))
							{
							$clienttrunarray[$clientloop['quotecontractid']][$splitbyclient['mission_id']][$splitbyclient['month_year']]=$clienttrunarray[$clientloop['quotecontractid']][$splitbyclient['mission_id']][$splitbyclient['month_year']]+$splitbyclient['turnover'];
							}
							else
							{
							$clienttrunarray[$clientloop['quotecontractid']][$splitbyclient['mission_id']][$splitbyclient['month_year']]=$splitbyclient['turnover'];
							}

							//get Mission details
							$clienttrunarray[$clientloop['quotecontractid']][$splitbyclient['mission_id']]['turnover']=$clienttrunarray[$clientloop['quotecontractid']][$splitbyclient['mission_id']]['turnover']+$splitbyclient['turnover'];	
							$clienttrunarray[$clientloop['quotecontractid']][$splitbyclient['mission_id']][$splitbyclient['month_year'].'-from_followup']=$splitbyclient['from_followup'];
						
							
							$contractMissoinType= $turnover_obj->getMissionDetails($splitbyclient['mission_id'],array('product'=>$request['product'],'cid'=>$clientloop['quotecontractid'],'assigned_to'=>$request['assigned_to']));
							if(!$contractMissoinType)
							{
							$techMissoinType=$turnover_obj->getTechMission($splitbyclient['mission_id'],$clientloop['quotecontractid'],$request['assigned_to']);
							if($techMissoinType)
							{
								$clienttrunarray[$clientloop['quotecontractid']][$splitbyclient['mission_id']]['mission_type']=$techMissoinType[0]['title'];
								$clienttrunarray[$clientloop['quotecontractid']][$splitbyclient['mission_id']]['assigned_to']=$techMissoinType[0]['assigned_to'];
								$clienttrunarray[$clientloop['quotecontractid']][$splitbyclient['mission_id']]['assigned_name']=$techMissoinType[0]['first_name']." ".$techMissoinType[0]['last_name'];
							}
							
							$clientloop['expected_end_date']=date('Y-m-d', strtotime($clientloop['expected_launch_date']. ' + '.$techMissoinType[0]['delivery_time'].' days'));
							
									if(date("mY",strtotime($clientloop['expected_launch_date'])) == date("mY",strtotime($clientloop['expected_end_date'])))
									{
											$clienttrunarray[$clientloop['quotecontractid']][$splitbyclient['mission_id']]['cost-'.$splitbyclient['month_year']]=(((100-$techMissoinType[0]['margin_percentage'])/100)*$clienttrunarray[$clientloop['quotecontractid']][$splitbyclient['mission_id']][$splitbyclient['month_year']]);//$techMissoinType[0]['cost']*$techMissoinType[0]['volume'];
									}
									else
									{
										if(date("m",strtotime($clientloop['expected_launch_date']))==$year_montharray[1])
										{
											$clienttrunarray[$clientloop['quotecontractid']][$splitbyclient['mission_id']]['cost-'.$splitbyclient['month_year']]=(((100-$techMissoinType[0]['margin_percentage'])/100)*$clienttrunarray[$clientloop['quotecontractid']][$splitbyclient['mission_id']][$splitbyclient['month_year']]);//($techMissoinType[0]['cost']*$techMissoinType[0]['volume']/$techMissoinType[0]['delivery_time'])*($this->month_days[$year_montharray[1]]-date("d",strtotime($clientloop['expected_launch_date'])));	
										}
										elseif(date("m",strtotime($clientloop['expected_end_date']))==$year_montharray[1])
										{
											$clienttrunarray[$clientloop['quotecontractid']][$splitbyclient['mission_id']]['cost-'.$splitbyclient['month_year']]=(((100-$techMissoinType[0]['margin_percentage'])/100)*$clienttrunarray[$clientloop['quotecontractid']][$splitbyclient['mission_id']][$splitbyclient['month_year']]);//($techMissoinType[0]['cost']*$techMissoinType[0]['volume']/$techMissoinType[0]['delivery_time'])*(date("d",strtotime($clientloop['expected_end_date'])));	
										}else
										{
											$clienttrunarray[$clientloop['quotecontractid']][$splitbyclient['mission_id']]['cost-'.$splitbyclient['month_year']]=(((100-$techMissoinType[0]['margin_percentage'])/100)*$clienttrunarray[$clientloop['quotecontractid']][$splitbyclient['mission_id']][$splitbyclient['month_year']]);//($techMissoinType[0]['cost']*$techMissoinType[0]['volume']/$techMissoinType[0]['delivery_time'])*($this->month_days[$year_montharray[1]]);	
										}
								  
									}
													
							}
							else
							{							
									$clienttrunarray[$clientloop['quotecontractid']][$splitbyclient['mission_id']]['mission_type_other'] =$contractMissoinType[0]['product_type_other'];
									if($contractMissoinType[0]['product']=='translation')
									{
									$clienttrunarray[$clientloop['quotecontractid']][$splitbyclient['mission_id']]['mission_type'] = $this->product_array[$contractMissoinType[0]['product']]." ".$this->producttype_array[$contractMissoinType[0]['product_type']]." ".$this->getCustomName("EP_LANGUAGES",$contractMissoinType[0]['language_source'])." vers ".$this->getCustomName("EP_LANGUAGES",$contractMissoinType[0]['language_dest']);
									$clienttrunarray[$clientloop['quotecontractid']][$splitbyclient['mission_id']]['product'] = $this->product_array[$contractMissoinType[0]['product']];
									$clienttrunarray[$clientloop['quotecontractid']][$splitbyclient['mission_id']]['product_type'] = $this->producttype_array[$contractMissoinType[0]['product_type']];
									}
									else
									{
									$clienttrunarray[$clientloop['quotecontractid']][$splitbyclient['mission_id']]['mission_type'] = $this->product_array[$contractMissoinType[0]['product']]." ".$this->producttype_array[$contractMissoinType[0]['product_type']]." en ".$this->getCustomName("EP_LANGUAGES",$contractMissoinType[0]['language_source']);
									$clienttrunarray[$clientloop['quotecontractid']][$splitbyclient['mission_id']]['product'] = $this->product_array[$contractMissoinType[0]['product']];
									$clienttrunarray[$clientloop['quotecontractid']][$splitbyclient['mission_id']]['product_type'] = $this->producttype_array[$contractMissoinType[0]['product_type']];
									}
									$clienttrunarray[$clientloop['quotecontractid']][$splitbyclient['mission_id']]['assigned_to'] = $contractMissoinType[0]['assigned_to'];
									$clienttrunarray[$clientloop['quotecontractid']][$splitbyclient['mission_id']]['assigned_name'] = $contractMissoinType[0]['first_name']." ".$contractMissoinType[0]['last_name'];
									
									$clientloop['expected_end_date']=date('Y-m-d', strtotime($clientloop['expected_launch_date']. ' + '.$contractMissoinType[0]['mission_length'].' days'));
								if(date("mY",strtotime($clientloop['expected_launch_date'])) == date("mY",strtotime($clientloop['expected_end_date'])) )
								{
								$clienttrunarray[$clientloop['quotecontractid']][$splitbyclient['mission_id']]['cost-'.$splitbyclient['month_year']]=(((100-$contractMissoinType[0]['margin_percentage'])/100)*$clienttrunarray[$clientloop['quotecontractid']][$splitbyclient['mission_id']][$splitbyclient['month_year']]);//$contractMissoinType[0]['internal_cost']*$contractMissoinType[0]['volume'];
								}
								else
								{
									if(date("m",strtotime($clientloop['expected_launch_date']))==$year_montharray[1])
									{
										$clienttrunarray[$clientloop['quotecontractid']][$splitbyclient['mission_id']]['cost-'.$splitbyclient['month_year']]=(((100-$contractMissoinType[0]['margin_percentage'])/100)*$clienttrunarray[$clientloop['quotecontractid']][$splitbyclient['mission_id']][$splitbyclient['month_year']]);//($contractMissoinType[0]['internal_cost']*$contractMissoinType[0]['volume']/$contractMissoinType[0]['mission_length'])*($this->month_days[$year_montharray[1]]-date("d",strtotime($clientloop['expected_launch_date'])));	
									}
									elseif(date("m",strtotime($clientloop['expected_end_date']))==$year_montharray[1])
									{
										$clienttrunarray[$clientloop['quotecontractid']][$splitbyclient['mission_id']]['cost-'.$splitbyclient['month_year']]=(((100-$contractMissoinType[0]['margin_percentage'])/100)*$clienttrunarray[$clientloop['quotecontractid']][$splitbyclient['mission_id']][$splitbyclient['month_year']]);//($contractMissoinType[0]['internal_cost']*$contractMissoinType[0]['volume']/$contractMissoinType[0]['mission_length'])*(date("d",strtotime($clientloop['expected_end_date'])));	
									}
									else
									{
									$clienttrunarray[$clientloop['quotecontractid']][$splitbyclient['mission_id']]['cost-'.$splitbyclient['month_year']]=(((100-$contractMissoinType[0]['margin_percentage'])/100)*$clienttrunarray[$clientloop['quotecontractid']][$splitbyclient['mission_id']][$splitbyclient['month_year']]);//($contractMissoinType[0]['internal_cost']*$contractMissoinType[0]['volume']/$contractMissoinType[0]['mission_length'])*($this->month_days[$year_montharray[1]]);	
									}
									  
								}
							
						
							}
			$clienttrunarray[$clientloop['quotecontractid']]['totalcost'.$splitbyclient['month_year']]+=$clienttrunarray[$clientloop['quotecontractid']][$splitbyclient['mission_id']]['cost-'.$splitbyclient['month_year']];
			
			$cc++;					
			}					
		 }
		}
		 
			
		 
		 
		 return $clienttrunarray;
	}
	
	function contractMonthlyDetailsAction($month_year,$client_id)	
	{
		$parmas = $this->_request->getParams();
		$contract_obj = new Ep_Quote_Turnover();
		$client_obj = new Ep_Quote_Client();	
			
		$clientcontractlist = $contract_obj->getClientContracts(array('client_id'=>$parmas['client_id']));
			 
		$month_year=$parmas['month'];
		$year=explode('-',$month_year);
		$default_year=$year[0];
		$default_month=$year[1];
		$month_value=$this->month_details[$default_month];
		$this->_view->default_month=$default_month;
		if($default_month==01) 
			$this->_view->prev_month=$default_month;
		else{
			if(strlen($default_month-1)==1)	 
				$this->_view->prev_month=sprintf('%02d', $default_month-1);
			else 
				$this->_view->prev_month=$default_month-1;
		}
		if($default_month==12) 
			$this->_view->next_month=$default_month;
		else{
			if(strlen($default_month+1)==1)	 
				$this->_view->next_month=sprintf('%02d',$default_month+1);
			 else 
				 $this->_view->next_month=$default_month+1;
		 }
		$this->_view->month_val=$month_value;
		$this->_view->year_val=$default_year;
		$month_daysval= $this->month_days[$default_month];
		
		foreach($clientcontractlist as $clientloop)
		{
			//client Details
			$client_details['client_name']=$clientloop['company_name'];
			$client_details['client_id']=$clientloop['client_id'];
			$client_details['client_code'] = $clientloop['client_code'];
			// sales creater info
			$salesDetails = $client_obj->getQuoteUserDetails($clientloop['sales_creator_id']);
			
			$salesownerdetail['sales_owner'] = $salesDetails[0]['first_name']." ".$salesDetails[0]['last_name'];
			$salesownerdetail['sales_creator_id'] = $clientloop['sales_creator_id'];
			$searchcontract['contract_id']=$clientloop['quotecontractid'];
			//$searchcontract['month_year']=$month_year;
			$splitmonthturnoverclient=$contract_obj->getsplitmonthexists($searchcontract);	
			//Mission Details
			$contract_details[$clientloop['quotecontractid']] = $clientloop;
			//echo "<pre>";print_r($splitmonthturnoverclient);
			if($splitmonthturnoverclient=="")
				{
			
									 $missionDetailsloop=$contract_obj->getProdSeoMissions(array('contract_id'=>$clientloop['quotecontractid'],'product'=>$parmas['product'],'product_type'=>$parmas['p_type']));
										 foreach($missionDetailsloop as $missoin)
										 {
											 $clienttrunarray[$clientloop['quotecontractid']][$missoin['qmid']]['mission_type_other']=$missoin['product_type_other'];
											 if(!in_array($missoin['qmid'],$clienttrunarray[$clientloop['quotecontractid']]))
												{
												$clienttrunarray[$clientloop['quotecontractid']][$cc]=$missoin['qmid'];
												}
											 if($missoin['product']=='translation')
												{
													$clienttrunarray[$clientloop['quotecontractid']][$missoin['qmid']]['mission_type'] = $this->product_array[$missoin['product']]." ".$this->producttype_array[$missoin['product_type']]." ".$this->getCustomName("EP_LANGUAGES",$missoin['language_source'])." vers ".$this->getCustomName("EP_LANGUAGES",$missoin['language_dest']);
												}
												else
												{
													$clienttrunarray[$clientloop['quotecontractid']][$missoin['qmid']]['mission_type'] = $this->product_array[$missoin['product']]." ".$this->producttype_array[$missoin['product_type']]." en ".$this->getCustomName("EP_LANGUAGES",$missoin['language_source']);
												}
												$clienttrunarray[$clientloop['quotecontractid']][$missoin['qmid']]['assigned_to'] = $missoin['assigned_to'];
												$clienttrunarray[$clientloop['quotecontractid']][$missoin['qmid']]['assigned_name'] = $missoin['first_name']." ".$missoin['last_name'];
											$cc++; 
										 }
										$searchParameters['identifier']= $parmas['product'];
										$searchParameters['quote_id']= $clientloop['quoteid'];
										$searchParameters['contract_id']= $clientloop['quotecontractid'];
										$searchParameters['include_final']='yes';
										if($parmas['product'] && $parmas['p_type']=='') $searchParameters['title']=str_replace("_",' ',$parmas['product']);
										elseif($parmas['product']=="" && $parmas['p_type']) $searchParameters['title']=str_replace("_",' ',$parmas['p_type']);
										$techMissionDetails=$contract_obj->getTechMissionDetails($searchParameters);
										$i=$cc;
										foreach($techMissionDetails as $techmisson)
										 {
												 if(!in_array($techmisson['identifier'],$clienttrunarray[$quotescon['quotecontractid']]))
												{
												$clienttrunarray[$clientloop['quotecontractid']][$i]=$techmisson['identifier'];
												}
												$clienttrunarray[$clientloop['quotecontractid']][$techmisson['identifier']]['mission_type'] = $techmisson['title'];
											$clienttrunarray[$clientloop['quotecontractid']][$techmisson['identifier']]['assigned_to'] = $techmisson['assigned_to'];
											$clienttrunarray[$clientloop['quotecontractid']][$techmisson['identifier']]['assigned_name'] = $techmisson['first_name']." ".$techmisson['last_name'];
											$i++; 
										 }
				}
				else
				{	
			foreach($splitmonthturnoverclient as $splitbyclient)
			{ //split turnover for contract 
				$year_montharray=explode('-',$splitbyclient['month_year']);
				if($year_montharray[0]==$default_year)
				{
					if(array_key_exists($splitbyclient['month_year'],$clienttrunarray[$clientloop['quotecontractid']]))
					{
						$clienttrunarray[$clientloop['quotecontractid']][$splitbyclient['month_year']]=$clienttrunarray[$clientloop['quotecontractid']][$splitbyclient['month_year']]+$splitbyclient['turnover'];
					}
					else
					{
					$clienttrunarray[$clientloop['quotecontractid']][$splitbyclient['month_year']]=$splitbyclient['turnover'];
					}
					$clienttrunarray[$clientloop['quotecontractid']]['turnover']=$clienttrunarray[$clientloop['quotecontractid']]['turnover']+$splitbyclient['turnover'];
					$clienttrunarray[$clientloop['quotecontractid']]['sales_suggested_currency'] = $clientloop['sales_suggested_currency'];	
				
					//contract Mission details
					if(!in_array($splitbyclient['mission_id'],$clienttrunarray[$clientloop['quotecontractid']]))
					{
						$clienttrunarray[$clientloop['quotecontractid']][$cc]=$splitbyclient['mission_id'];
					}
					if(array_key_exists($splitbyclient['month_year'],$clienttrunarray[$clientloop['quotecontractid']][$splitbyclient['mission_id']]))
					{
						$clienttrunarray[$clientloop['quotecontractid']][$splitbyclient['mission_id']][$splitbyclient['month_year']]=$clienttrunarray[$clientloop['quotecontractid']][$splitbyclient['mission_id']][$splitbyclient['month_year']]+$splitbyclient['turnover'];
					}
					else
					{
						$clienttrunarray[$clientloop['quotecontractid']][$splitbyclient['mission_id']][$splitbyclient['month_year']]=$splitbyclient['turnover'];
					}
					//get Mission details
							$contractMissoinType= $contract_obj->getMissionDetails($splitbyclient['mission_id'],array('product'=>$request['product'],'cid'=>$clientloop['quotecontractid']));		
					$clientloop['expected_end_date']=date('Y-m-d', strtotime($clientloop['expected_launch_date']. ' + '.$contractMissoinType[0]['mission_length'].' days'));
					//split month cost details
					if(date("mY",strtotime($clientloop['expected_launch_date'])) == date("mY",strtotime($clientloop['expected_end_date'])) )
					{
						$clienttrunarray[$clientloop['quotecontractid']][$splitbyclient['mission_id']]['cost'][$splitbyclient['month_year']]=(((100-$contractMissoinType[0]['margin_percentage'])/100)*$clienttrunarray[$clientloop['quotecontractid']][$splitbyclient['mission_id']][$splitbyclient['month_year']]);//($contractMissoinType[0]['internal_cost']*$contractMissoinType[0]['volume']);//*($contractMissoinType[0]['mission_length']);
					}
					else
					{
						if(date("m",strtotime($clientloop['expected_launch_date']))==$year_montharray[1])
						{
							$clienttrunarray[$clientloop['quotecontractid']][$splitbyclient['mission_id']]['cost'][$splitbyclient['month_year']]=(((100-$contractMissoinType[0]['margin_percentage'])/100)*$clienttrunarray[$clientloop['quotecontractid']][$splitbyclient['mission_id']][$splitbyclient['month_year']]);//($contractMissoinType[0]['internal_cost']*$contractMissoinType[0]['volume']/$contractMissoinType[0]['mission_length'])*($month_daysval-date("d",strtotime($clientloop['expected_launch_date'])));	
						}
						elseif(date("m",strtotime($clientloop['expected_end_date']))==$year_montharray[1])
						{
							$clienttrunarray[$clientloop['quotecontractid']][$splitbyclient['mission_id']]['cost'][$splitbyclient['month_year']]=(((100-$contractMissoinType[0]['margin_percentage'])/100)*$clienttrunarray[$clientloop['quotecontractid']][$splitbyclient['mission_id']][$splitbyclient['month_year']]);//($contractMissoinType[0]['internal_cost']*$contractMissoinType[0]['volume']/$contractMissoinType[0]['mission_length'])*(date("d",strtotime($clientloop['expected_end_date'])));	
						}
						else
						{
							$clienttrunarray[$clientloop['quotecontractid']][$splitbyclient['mission_id']]['cost'][$splitbyclient['month_year']]=(((100-$contractMissoinType[0]['margin_percentage'])/100)*$clienttrunarray[$clientloop['quotecontractid']][$splitbyclient['mission_id']][$splitbyclient['month_year']]);//($contractMissoinType[0]['internal_cost']*$contractMissoinType[0]['volume']/$contractMissoinType[0]['mission_length'])*($month_daysval);	
						}
						  
					}
					
					if(!$contractMissoinType)
					{
								$techMissoinType=$contract_obj->getTechMission($splitbyclient['mission_id'],array('cid'=>$clientloop['quotecontractid']));
						$clientloop['expected_end_date']=date('Y-m-d', strtotime($clientloop['expected_launch_date']. ' + '.$techMissoinType[0]['delivery_time'].' days'));
						$clienttrunarray[$clientloop['quotecontractid']][$splitbyclient['mission_id']]['mission_type']=$techMissoinType[0]['title'];
								$clienttrunarray[$clientloop['quotecontractid']][$splitbyclient['mission_id']]['assigned_to'] = $techMissoinType['assigned_to'];
								$clienttrunarray[$clientloop['quotecontractid']][$splitbyclient['mission_id']]['assigned_name'] = $techMissoinType['first_name']." ".$techMissoinType['last_name'];							
							if(date("mY",strtotime($clientloop['expected_launch_date'])) == date("mY",strtotime($clientloop['expected_end_date'])))
							{
									$clienttrunarray[$clientloop['quotecontractid']][$splitbyclient['mission_id']]['cost'][$splitbyclient['month_year']]=(((100-$techMissoinType[0]['margin_percentage'])/100)*$clienttrunarray[$clientloop['quotecontractid']][$splitbyclient['mission_id']][$splitbyclient['month_year']]);//($techMissoinType[0]['cost']*$techMissoinType[0]['volume']);//*($techMissoinType[0]['delivery_time']);
							}
							else
							{
								if(date("m",strtotime($clientloop['expected_launch_date']))==$year_montharray[1])
								{
									$clienttrunarray[$clientloop['quotecontractid']][$splitbyclient['mission_id']]['cost'][$splitbyclient['month_year']]=(((100-$techMissoinType[0]['margin_percentage'])/100)*$clienttrunarray[$clientloop['quotecontractid']][$splitbyclient['mission_id']][$splitbyclient['month_year']]);//($techMissoinType[0]['cost']*$techMissoinType[0]['volume']/$techMissoinType[0]['delivery_time'])*($month_daysval-date("d",strtotime($clientloop['expected_launch_date'])));	
								}
								elseif(date("m",strtotime($clientloop['expected_end_date']))==$year_montharray[1])
								{
										$clienttrunarray[$clientloop['quotecontractid']][$splitbyclient['mission_id']]['cost'][$splitbyclient['month_year']]=(((100-$techMissoinType[0]['margin_percentage'])/100)*$clienttrunarray[$clientloop['quotecontractid']][$splitbyclient['mission_id']][$splitbyclient['month_year']]);//($techMissoinType[0]['cost']*$techMissoinType[0]['volume']/$techMissoinType[0]['delivery_time'])*(date("d",strtotime($clientloop['expected_end_date'])));	
								}else
								{
									$clienttrunarray[$clientloop['quotecontractid']][$splitbyclient['mission_id']]['cost'][$splitbyclient['month_year']]=(((100-$techMissoinType[0]['margin_percentage'])/100)*$clienttrunarray[$clientloop['quotecontractid']][$splitbyclient['mission_id']][$splitbyclient['month_year']]);//($techMissoinType[0]['cost']*$techMissoinType[0]['volume']/$techMissoinType[0]['delivery_time'])*($month_daysval);	
								}
						  
							}
					}
					else
					{
						
							$clienttrunarray[$clientloop['quotecontractid']][$splitbyclient['mission_id']]['mission_type_other']=$contractMissoinType[0]['product_type_other'];
							if($contractMissoinType[0]['product']=='translation')
							{
							$clienttrunarray[$clientloop['quotecontractid']][$splitbyclient['mission_id']]['mission_type'] = $this->product_array[$contractMissoinType[0]['product']]." ".$this->producttype_array[$contractMissoinType[0]['product_type']]." ".utf8_encode($this->getCustomName("EP_LANGUAGES",$contractMissoinType[0]['language_source']))." vers ".utf8_encode($this->getCustomName("EP_LANGUAGES",$contractMissoinType[0]['language_dest']));
							}
							else
							{
							$clienttrunarray[$clientloop['quotecontractid']][$splitbyclient['mission_id']]['mission_type'] = $this->product_array[$contractMissoinType[0]['product']]." ".$this->producttype_array[$contractMissoinType[0]['product_type']]." en ".utf8_encode($this->getCustomName("EP_LANGUAGES",$contractMissoinType[0]['language_source']));
							}
									$clienttrunarray[$clientloop['quotecontractid']][$splitbyclient['mission_id']]['assigned_to'] = $contractMissoinType[0]['assigned_to'];
									$clienttrunarray[$clientloop['quotecontractid']][$splitbyclient['mission_id']]['assigned_name'] = $contractMissoinType[0]['first_name']." ".$contractMissoinType[0]['last_name'];
							
					
					}
					
						
					$clienttrunarray[$clientloop['quotecontractid']][$splitbyclient['mission_id']]['turnover']=$clienttrunarray[$clientloop['quotecontractid']][$splitbyclient['mission_id']]['turnover']+$splitbyclient['turnover'];
					
					$clienttrunarray[$clientloop['quotecontractid']][$splitbyclient['mission_id']][$splitbyclient['month_year'].'-from_followup']=$splitbyclient['from_followup'];
					
					//Contract Cost value
					if($year_montharray[1]==$default_month)
					$clienttrunarray[$clientloop['quotecontractid']]['costtotal']=$clienttrunarray[$clientloop['quotecontractid']]['costtotal']+$clienttrunarray[$clientloop['quotecontractid']][$splitbyclient['mission_id']]['cost'][$splitbyclient['month_year']];
					
				}

				$cc++;						
			}	
				}
			/* To get real turnover by prass! */
			/* Real turnover and missions */
			$turnover_obj = new Ep_Quote_Turnover();
			$quotecontract_obj = new Ep_Quote_Quotecontract();
			$cmissiondetails = $turnover_obj->getProdSeoMissions(array('contract_id'=>$clientloop['quotecontractid']));
			foreach($cmissiondetails as $contract_mission)
			{
				if($contract_mission['contractmissionid'] && $contract_mission['type']=="prod")
				{
					$years_mission = $turnover_obj->getRealTurnovers(array('contract_mission_id'=>$contract_mission['contractmissionid'],'year'=>$default_year,'month'=>$default_month));
					foreach($years_mission as $yearm)
					{
						/*$clienttrunarray[$clientloop['quotecontractid']][$contract_mission['qmid']]['real_'.$yearm['yearmonth']]  = $yearm['publishedprice'];
						$clienttrunarray[$clientloop['quotecontractid']][$contract_mission['qmid']]['realcost_'.$yearm['yearmonth']]  = $contract_mission['unit_price']*$yearm['artcount'];
						$clienttrunarray[$clientloop['quotecontractid']]['real_'.$yearm['yearmonth']] +=  $yearm['publishedprice'];
						 $clienttrunarray[$clientloop['quotecontractid']]['realturnover'] += $yearm['publishedprice'];
						$clienttrunarray[$clientloop['quotecontractid']]['realcostturnover'] += $contract_mission['unit_price']*$yearm['artcount']; */
						$real_price = $contract_mission['unit_price']*$yearm['total_packs'];
						$clienttrunarray[$clientloop['quotecontractid']][$contract_mission['qmid']]['realcost_'.$yearm['yearmonth']]  = $yearm['publishedprice'];
						$clienttrunarray[$clientloop['quotecontractid']][$contract_mission['qmid']]['real_'.$yearm['yearmonth']]  = $real_price;
						$clienttrunarray[$clientloop['quotecontractid']]['real_'.$yearm['yearmonth']] +=  $yearm['publishedprice'];
						$clienttrunarray[$clientloop['quotecontractid']]['realcostturnover'] += $yearm['publishedprice'];
						$clienttrunarray[$clientloop['quotecontractid']]['realturnover'] += $real_price;
					}
				}
				else
				{
					if($default_year==$contract_mission['validatedyear'] && $default_month==$contract_mission['validatedmonth'])
					{
						$clienttrunarray[$clientloop['quotecontractid']][$contract_mission['qmid']]['real_'.$contract_mission['validatedyearmonth']]  = $contract_mission['missionturnover'];
						$clienttrunarray[$clientloop['quotecontractid']]['real_'.$contract_mission['validatedyearmonth']] += $contract_mission['missionturnover'];
						$clienttrunarray[$clientloop['quotecontractid']]['realturnover'] += $contract_mission['missionturnover'];
					}
					$clienttrunarray[$clientloop['quotecontractid']][$contract_mission['qmid']]['type']  = "seo";
				}
			}
			//echo "<pre>";print_r($clienttrunarray);exit;
			
			/* To get tech missions and turnover */
			$searchParameters = array();
			$searchParameters['quote_id']= $clientloop['quoteid'];
			$searchParameters['contract_id']= $clientloop['quotecontractid'];
			$searchParameters['include_final']='yes';
			$techMissionDetails=$turnover_obj->getTechMissionDetails($searchParameters);
			
			foreach($techMissionDetails as $tech_mission)
			{
				if($default_year==$tech_mission['validatedyear'] && $default_month==$tech_mission['validatedmonth'])
				{
					$clienttrunarray[$clientloop['quotecontractid']][$tech_mission['identifier']]['real_'.$tech_mission['validatedyearmonth']]  = $tech_mission['turnover'];
					$clienttrunarray[$clientloop['quotecontractid']]['real_'.$tech_mission['validatedyearmonth']] += $tech_mission['turnover'];
					$clienttrunarray[$clientloop['quotecontractid']]['realturnover'] += $tech_mission['turnover'];
				}
				$clienttrunarray[$clientloop['quotecontractid']][$tech_mission['identifier']]['type']  = "tech";
				$clienttrunarray[$clientloop['quotecontractid']][$tech_mission['identifier']]['title'] = $tech_mission['title'];
				$clienttrunarray[$clientloop['quotecontractid']][$tech_mission['identifier']]['assigned_to'] = $tech_mission['assigned_to'];
				$clienttrunarray[$clientloop['quotecontractid']][$tech_mission['identifier']]['assigned_name'] = $tech_mission['first_name']." ".$tech_mission['last_name'];
			}

			$contract_details['contract_Contrat_details']=$clienttrunarray;										
		}			
		$this->_view->sales_details=$salesownerdetail;
		$this->_view->client_details=$client_details;
		$this->_view->contract_details=$contract_details;
		/* echo "<PRE>";
		print_r($contract_details);
		exit;  */
		$this->render('split-contract-monthly');
	}
	
	
	// split/month update
	
	function splitMonthTurnoverAction()
	{
			
		$contractParam=$this->_request->getParams();
		$turnover_obj = new Ep_Quote_Turnover();
		$client_obj = new Ep_Quote_Client();	
		//$client_details_obj = new Ep_User_BoUser();	
		$contract_obj = new Ep_Quote_Quotecontract();
		//default Year selection
			
		if($contractParam['year'])
		{
		$this->_view->default_year=$contractParam['year'];
		}
		else
		{
		$this->_view->default_year=date("Y");
		}
		
						
		if($contractParam['clientid'])
		{
			//client's contract list
				$contractlist = $turnover_obj->getClientContracts(array('client_id'=>$contractParam['clientid']));
				foreach($contractlist as $search_list)
				{
				
					$searchDetails = $client_obj->getQuoteUserDetails($search_list['sales_creator_id']);
					$salesownerdetail['sales_details'][$search_list['sales_creator_id']] = $searchDetails[0]['first_name']." ".$searchDetails[0]['last_name'];
				}
			
				$clientcontractlist = $turnover_obj->getClientContracts(array('client_id'=>$contractParam['clientid'],'sales_id'=>$contractParam['sales_id']));
				//echo "<pre>";print_r($clientcontractlist);exit;
				
				
					foreach($clientcontractlist as $clientloop)
						{
							//Fillters
							
							$missionDetailsloop=$turnover_obj->getProdSeoMissions(array('contract_id'=>$clientloop['quotecontractid']));
								 foreach($missionDetailsloop as $missoin)
									{
									 if($missoin['product']=='translation')
									{	
										$product_total_array[$missoin['product']] = $this->product_array[$missoin['product']];
										$procuct_type_array[$missoin['product_type']]=$this->producttype_array[$missoin['product_type']];
									}
									else
									{
										$product_total_array[$missoin['product']] = $this->product_array[$missoin['product']];
										$procuct_type_array[$missoin['product_type']]=$this->producttype_array[$missoin['product_type']];
									}
									}
							$techParameters['quote_id']= $clientloop['quoteid'];
							$techParameters['contract_id']= $clientloop['quotecontractid'];
							$techParameters['include_final']='yes';
							$techMissionDetails=$turnover_obj->getTechMissionDetails($techParameters);
							foreach($techMissionDetails as $techfilmisson)
							 {
								$keyfil=str_replace(' ','_',$techfilmisson['title']);
								$product_total_array[$keyfil] = $techfilmisson['title'];
							}
							   //client Details
								
								$client_details['client_name']=$clientloop['company_name'];
								$client_details['client_id']=$clientloop['client_id'];
								
								$salesDetails = $client_obj->getQuoteUserDetails($clientloop['sales_creator_id']);
								$salesownerdetail[$clientloop['quotecontractid']]['sales_owner'] = $salesDetails[0]['first_name']." ".$salesDetails[0]['last_name'];
								$salesownerdetail[$clientloop['quotecontractid']]['mailto'] = $salesDetails[0]['email'];
								$salesownerdetail[$clientloop['quotecontractid']]['sales_creator_id'] = $clientloop['sales_creator_id'];
								$salesownerdetail[$clientloop['quotecontractid']]['city'] = $salesDetails[0]['city'];
								$salesownerdetail[$clientloop['quotecontractid']]['state'] = $salesDetails[0]['state'];
								$salesownerdetail[$clientloop['quotecontractid']]['email'] = $salesDetails[0]['email'];
								//$client_infor=$client_details_obj->getBoUserExtraDetails($clientloop['sales_creator_id']);
								//$salesownerdetail[$clientloop['quotecontractid']]['skype_id'] = $client_infor[0]['skype_id'];
										
								//Total Turn over by user
								$searchcontract['contract_id']=$clientloop['quotecontractid'];
								
								if($contractParam['p_type']){
									$searchcontract['name']=$this->producttype_array[$contractParam['p_type']];
									}
								if($contractParam['product'])
								{
									if($this->product_array[$contractParam['product']])
									$searchcontract['product']=$this->product_array[$contractParam['product']];
									else
									$searchcontract['product']=str_replace("_",' ',$contractParam['product']);
									}
								$splitmonthturnoverclient=$turnover_obj->getSplitTurnoversclients($searchcontract);								
								
								//Mission Details
										
								$totaltrunarray=$this->clientcontracttotalloop($splitmonthturnoverclient,$clientloop,$totaltrunarray);
								$contract_details['totalclient'] =$totaltrunarray;	
							
							if(($contractParam['sales_id']!=="" && $contractParam['sales_id']==$clientloop['sales_creator_id']) ||$contractParam['sales_id']=="")
									$contract_details[$clientloop['quotecontractid']] = $clientloop;		 
							if($splitmonthturnoverclient=="")
								 {
									
								 $missionDetailsloop=$turnover_obj->getProdSeoMissions(array('contract_id'=>$clientloop['quotecontractid'],'product'=>$contractParam['product'],'product_type'=>$contractParam['p_type']));
								 //echo "<pre>";print_r($missionDetailsloop);exit;
								 $c=0;
										 foreach($missionDetailsloop as $missoin)
										 {
											$clienttrunarray[$clientloop['quotecontractid']][$missoin['qmid']]['mission_type_other']=$missoin['product_type_other'];
											if(!in_array($missoin['qmid'],$clienttrunarray[$clientloop['quotecontractid']]))
											{
											$clienttrunarray[$clientloop['quotecontractid']][$c]=$missoin['qmid'];
											}
											 if($missoin['product']=='translation')
											{
												$clienttrunarray[$clientloop['quotecontractid']][$missoin['qmid']]['mission_type'] = $this->product_array[$missoin['product']]." ".$this->producttype_array[$missoin['product_type']]." ".$this->getCustomName("EP_LANGUAGES",$missoin['language_source'])." vers ".$this->getCustomName("EP_LANGUAGES",$missoin['language_dest']);
											}
											else
											{
												$clienttrunarray[$clientloop['quotecontractid']][$missoin['qmid']]['mission_type'] = $this->product_array[$missoin['product']]." ".$this->producttype_array[$missoin['product_type']]." en ".$this->getCustomName("EP_LANGUAGES",$missoin['language_source']);
											}
											$clienttrunarray[$clientloop['quotecontractid']][$missoin['qmid']]['assigned_to'] = $missoin['assigned_to'];
											$clienttrunarray[$clientloop['quotecontractid']][$missoin['qmid']]['assigned_name'] = $missoin['first_name']." ".$missoin['last_name'];
											$c++; 
										 }
										$searchParameters['identifier']= $contractParam['product'];
										$searchParameters['quote_id']= $clientloop['quoteid'];
										$searchParameters['contract_id']= $clientloop['quotecontractid'];
										$searchParameters['include_final']='yes';
										if($contractParam['product'] && $contractParam['p_type']=='') $searchParameters['product']=str_replace("_",' ',$contractParam['product']);
										elseif($contractParam['product']=="" && $contractParam['p_type']) $searchParameters['p_type']=str_replace("_",' ',$contractParam['p_type']);
										$techMissionDetails=$turnover_obj->getTechMissionDetails($searchParameters);
										$i=$c;
										foreach($techMissionDetails as $techmisson)
										 {
											 if(!in_array($techmisson['identifier'],$clienttrunarray[$clientloop['quotecontractid']]))
											{
											$clienttrunarray[$clientloop['quotecontractid']][$i]=$techmisson['identifier'];
											}
											$clienttrunarray[$clientloop['quotecontractid']][$techmisson['identifier']]['mission_type'] = $techmisson['title'];
											$clienttrunarray[$clientloop['quotecontractid']][$techmisson['identifier']]['assigned_to'] = $techmisson['assigned_to'];
											$clienttrunarray[$clientloop['quotecontractid']][$techmisson['identifier']]['assigned_name'] = $techmisson['first_name']." ".$techmisson['last_name'];
											$i++; 
										 }
								 }	
								 else
								 {	
							$clienttrunarray=$this->clientcontractmissionloop($splitmonthturnoverclient,$clientloop,$clienttrunarray,$this->_view->default_year);
								}
								$contract_details['contract_Contrat_details']=$clienttrunarray;
								//echo "<pre>";print_r($clienttrunarray);exit;
						}
//echo "<pre>";print_r($contract_details['contract_Contrat_details']);exit;						
						natsort($procuct_type_array);
						natsort($product_total_array);
						$this->_view->monthlist=$this->month_array;							
						$this->_view->invoce="Split/Month";
						$this->_view->client_details=$client_details;
						$this->_view->procuct_type_array=$procuct_type_array;
						$this->_view->product_total_array=$product_total_array;
						
						$this->_view->contract_details=$contract_details;
					   
		}
		else //client listing Page
		{
			//search parameters
			if($contractParam['client_id']!=""){
				$searchclient['client_id']=$contractParam['client_id'];
			}
			if($contractParam['sales_id']!=""){
				$searchclient['sales_id']=$contractParam['sales_id'];
			}
			if($contractParam['year']!=""){
				$searchclient['year']=$contractParam['year'];
			}
			if($contractParam['start_date']!="" && $contractParam['end_date']=="")
			{
			$searchclient['start_date']=$contractParam['start_date'];
			}
			elseif($contractParam['end_date']!="" && $contractParam['start_date']=="")
			{
				$searchclient['end_date']=$contractParam['end_date'];
			}
			elseif($contractParam['end_date']!="" && $contractParam['start_date']!=""){
			$searchclient['start_date']=$contractParam['start_date'];
			$searchclient['end_date']=$contractParam['end_date'];	
			}
						
			if($searchclient) $quotecontractlist = $turnover_obj->getClientContracts($searchclient);
			else $quotecontractlist = $turnover_obj->getClientContracts();
		$c=0;
		$contractlist = $turnover_obj->getClientContracts();
		$totalturnoverexpeuro=0;
		$totalturnoverexppound=0;
		foreach($contractlist as $search_list)
		{
			$client_list[$search_list['client_id']]=$search_list['company_name'];
			$searchDetails = $client_obj->getQuoteUserDetails($search_list['sales_creator_id']);
			$salesownerdetail['sales_details'][$search_list['sales_creator_id']] = $searchDetails[0]['first_name']." ".$searchDetails[0]['last_name'];
			//total turnover
			$splitmonthturnovertotal=$turnover_obj->getSplitTurnovers($search_list['quotecontractid']);
			$totalclientturnover=0;
			foreach($splitmonthturnovertotal as $splitturn)
			{
				$year_total=explode('-',$splitturn['month_year']);
					if($year_total[0]==$this->_view->default_year)
					{
						if($search_list['sales_suggested_currency']=='euro')
						{
						$totalturnoverexpeuro+=$splitturn['turnover'];
						}
						else
						{
						$totalturnoverexppound+=$splitturn['turnover'];
						}

						$totalclientturnover+=$splitturn['turnover'];
					}

					if($totalclientturnover==0)
					{
					unset($salesownerdetail['sales_details'][$search_list['sales_creator_id']]);
					}
			}

		}
		foreach($quotecontractlist as $quotescon)
		{ //start
			
			
			$contract[$quotescon['client_id']]['client_code']=$quotescon['client_code'];
			$contract[$quotescon['client_id']]['client_id']=$quotescon['client_id'];
			$contract[$quotescon['client_id']]['quoteid']=$quotescon['quoteid'];
			$contract[$quotescon['client_id']]['quotecontractid']=$quotescon['quotecontractid'];
			//client details
			$client_obj=new Ep_Quote_Client();
			 
			
			//sales in chanrge
			$salesDetails = $client_obj->getQuoteUserDetails($quotescon['sales_creator_id']);
			$contract[$quotescon['client_id']]['sales_owner'] = $salesDetails[0]['first_name']." ".$salesDetails[0]['last_name'];
			$contract[$quotescon['client_id']]['mailto'] = $salesDetails[0]['email'];
			$contract[$quotescon['client_id']]['sales_creator_id'] = $quotescon['sales_creator_id']; 
			
			$contract[$quotescon['client_id']]['client_name']=$quotescon['company_name'];
			
			
			//split month total turnover
			$splitmonthturnover=$turnover_obj->getSplitTurnovers($quotescon['quotecontractid']);
			
			
				
				
			      
					foreach($splitmonthturnover as $splitturnover)
					{ //split turnover loop start
						
						$year_montharray=explode('-',$splitturnover['month_year']);
						if($year_montharray[0]==$this->_view->default_year)
						{
							
							if(array_key_exists($splitturnover['month_year'],$spliarray[$quotescon['client_id']]))
							{
							$spliarray[$quotescon['client_id']][$splitturnover['month_year']]=$spliarray[$quotescon['client_id']][$splitturnover['month_year']]+$splitturnover['turnover'];
							}
							else
							{
							$spliarray[$quotescon['client_id']][$splitturnover['month_year']]=$splitturnover['turnover'];
							}
							
							$spliarray[$quotescon['client_id']]['turnover']=$spliarray[$quotescon['client_id']]['turnover']+$splitturnover['turnover'];
							$spliarray[$quotescon['client_id']]['sales_suggested_currency'] = $quotescon['sales_suggested_currency'];	
						}
												
					 }
					 
					 //split turnover loop end
					
					 //anual overall trunover
					
								 
					$contract[$quotescon['client_id']]['splitmonth']=$spliarray;
					
					$contract[$quotescon['client_id']]['monthlist']=$this->month_array;	

					if($spliarray[$quotescon['client_id']]['turnover']==0)
					{
							unset($contract[$quotescon['client_id']]);
							unset($client_list[$quotescon['client_id']]);
					}
				
		} //endforeach
		natsort($client_list);
		$this->_view->invoce="invoice split/month";	
		$this->_view->quotecontractlist=$contract;
		$this->_view->client_list=$client_list;
		$this->_view->totalturnoverexpeuro=$totalturnoverexpeuro;
		$this->_view->totalturnoverexppound=$totalturnoverexppound;
		}
				
		$this->_view->year_list=$this->year_array;
		
		natsort($salesownerdetail['sales_details']);
		$this->_view->salesownerdetail=$salesownerdetail;
		
		$this->_view->product_type = $this->producttype_array;
		$this->_view->product_array = $this->product_array;
		
		$this->_view->render("split-month-turnover");	
		
	}
	
	
	//split per month ajax
	
	function loadclientlistAction()
	{
			$parmas = $this->_request->getParams();
			$turnover_obj = new Ep_Quote_Turnover();
			$contract_obj = new Ep_Quote_Quotecontract();
			$client_obj = new Ep_Quote_Client();	
			//$client_details_obj = new Ep_User_BoUser();	
			
		if($parmas['year'])
		{
		$this->_view->default_year=$parmas['year'];
		}
		else
		{
		$this->_view->default_year=date("Y");
		}
		
		if($parmas['clientid']){
						
			 $clientcontractlist = $turnover_obj->getClientContracts(array('client_id'=>$parmas['clientid']));
				
					foreach($clientcontractlist as $clientloop)
						{
							
							 $missionDetailsloop=$turnover_obj->getProdSeoMissions(array('contract_id'=>$clientloop['quotecontractid']));
								 foreach($missionDetailsloop as $missoin)
									{
									 if($missoin['product']=='translation')
									{	
										$product_total_array[$missoin['product']] = $this->product_array[$missoin['product']];
										$procuct_type_array[$missoin['product_type']]=$this->producttype_array[$missoin['product_type']];
									}
									else
									{
										$product_total_array[$missoin['product']] = $this->product_array[$missoin['product']];
										$procuct_type_array[$missoin['product_type']]=$this->producttype_array[$missoin['product_type']];
									}
									}
							$techParameters['quote_id']= $clientloop['quoteid'];
							$techParameters['contract_id']= $clientloop['quotecontractid'];
							$techParameters['include_final']='yes';
							$techMissionDetails=$turnover_obj->getTechMissionDetails($techParameters);
							foreach($techMissionDetails as $techfilmisson)
							 {
								$keyfil=str_replace(' ','_',$techfilmisson['title']);
								$product_total_array[$keyfil] = $techfilmisson['title'];
							}
							   //client Details
								
								$client_details['client_name']=$clientloop['company_name'];
								$client_details['client_id']=$clientloop['client_id'];
								
								$salesDetails = $client_obj->getQuoteUserDetails($clientloop['sales_creator_id']);
								$salesownerdetail['sales_details'][$clientloop['sales_creator_id']] = $salesDetails[0]['first_name']." ".$salesDetails[0]['last_name'];
								$salesownerdetail[$clientloop['quotecontractid']]['sales_owner'] = $salesDetails[0]['first_name']." ".$salesDetails[0]['last_name'];
								$salesownerdetail[$clientloop['quotecontractid']]['mailto'] = $salesDetails[0]['email'];
								$salesownerdetail[$clientloop['quotecontractid']]['sales_creator_id'] = $clientloop['sales_creator_id'];
								$salesownerdetail[$clientloop['quotecontractid']]['city'] = $salesDetails[0]['city'];
								$salesownerdetail[$clientloop['quotecontractid']]['state'] = $salesDetails[0]['state'];
								$salesownerdetail[$clientloop['quotecontractid']]['email'] = $salesDetails[0]['email'];
								//$client_infor=$client_details_obj->getBoUserExtraDetails($clientloop['sales_creator_id']);
								//$salesownerdetail[$clientloop['quotecontractid']]['skype_id'] = $client_infor[0]['skype_id'];
								
								//Total Turn over by user
								$searchcontract['contract_id']=$clientloop['quotecontractid'];
								
								if($parmas['p_type']){
									$searchcontract['name']=$this->producttype_array[$parmas['p_type']];
									}
								if($parmas['product'])
								{
									if($this->product_array[$parmas['product']])
									$searchcontract['product']=$this->product_array[$parmas['product']];
									else
									$searchcontract['product']=str_replace("_",' ',$parmas['product']);
									}
								$splitmonthturnoverclient=$turnover_obj->getSplitTurnoversclients($searchcontract);
								
								
								 //Mission Details
						$totaltrunarray=$this->clientcontracttotalloop($splitmonthturnoverclient,$clientloop,$totaltrunarray);
								$contract_details['totalclient'] =$totaltrunarray;	
								
							if(($contractParam['sales_id']!=="" && $contractParam['sales_id']==$clientloop['sales_creator_id']) ||$contractParam['sales_id']=="")
									$contract_details[$clientloop['quotecontractid']] = $clientloop;
							if($splitmonthturnoverclient=="")
								 {
									
								 $missionDetailsloop=$turnover_obj->getProdSeoMissions(array('contract_id'=>$clientloop['quotecontractid'],'product'=>$parmas['product'],'product_type'=>$parmas['p_type']));
								 $c=0;
										 foreach($missionDetailsloop as $missoin)
										 {
											$clienttrunarray[$clientloop['quotecontractid']][$missoin['qmid']]['mission_type_other']=$missoin['product_type_other'];
											
											if(!in_array($missoin['qmid'],$clienttrunarray[$clientloop['quotecontractid']]))
											{
											$clienttrunarray[$clientloop['quotecontractid']][$c]=$missoin['qmid'];
											}
											 if($missoin['product']=='translation')
											{
												$clienttrunarray[$clientloop['quotecontractid']][$missoin['qmid']]['mission_type'] = $this->product_array[$missoin['product']]." ".$this->producttype_array[$missoin['product_type']]." ".$this->getCustomName("EP_LANGUAGES",$missoin['language_source'])." vers ".$this->getCustomName("EP_LANGUAGES",$missoin['language_dest']);
											}
											else
											{
												$clienttrunarray[$clientloop['quotecontractid']][$missoin['qmid']]['mission_type'] = $this->product_array[$missoin['product']]." ".$this->producttype_array[$missoin['product_type']]." en ".$this->getCustomName("EP_LANGUAGES",$missoin['language_source']);
											}
											$c++; 
										 }
										$searchParameters['identifier']= $contractParam['product'];
										$searchParameters['quote_id']= $clientloop['quoteid'];
										$searchParameters['contract_id']= $clientloop['quotecontractid'];
										$searchParameters['include_final']='yes';
										if($parmas['product'] && $parmas['p_type']=='') $searchParameters['title']=str_replace("_",' ',$parmas['product']);
										elseif($contractParam['product']=="" && $contractParam['p_type']) $searchParameters['title']=str_replace("_",' ',$parmas['p_type']);
										$techMissionDetails=$turnover_obj->getTechMissionDetails($searchParameters);
										$i=$c;
										foreach($techMissionDetails as $techmisson)
										 {
											 if(!in_array($techmisson['identifier'],$clienttrunarray[$clientloop['quotecontractid']]))
											{
											$clienttrunarray[$clientloop['quotecontractid']][$i]=$techmisson['identifier'];
											}
											$clienttrunarray[$clientloop['quotecontractid']][$techmisson['identifier']]['mission_type'] = $techmisson['title'];
											$i++; 
										 }
								 }	
								 else
								 {	
							$clienttrunarray=$this->clientcontractmissionloop($splitmonthturnoverclient,$clientloop,$clienttrunarray,$this->_view->default_year);
								}
								$contract_details['contract_Contrat_details']=$clienttrunarray;
								
						}
												
						$this->_view->monthlist=$this->month_array;							
						$this->_view->invoce="Split/Month";
						$this->_view->client_details=$client_details;
						$this->_view->procuct_type_array=$procuct_type_array;
						$this->_view->product_total_array=$product_total_array;
						$this->_view->salesownerdetail=$salesownerdetail;
						$this->_view->contract_details=$contract_details;
						$this->render('split-turnover-contract-ajax');
					   
		}
	
	}
	
	function updateFollowpTurnoverAction()
	{
		
		if($this->_request->isPost() && $_SERVER['HTTP_X_REQUESTED_WITH']=='XMLHttpRequest')
		{
			$parmas = $this->_request->getParams();
			$contract_obj = new Ep_Quote_Quotecontract();
			$turnover_obj = new Ep_Quote_Turnover();		
			
			$update['mission_id']=$parmas['mission_id'];
			$update['month_year']=$parmas['month_year'];
			$update['turnover']=$parmas['turnover'];
			$update['contract_id']=$parmas['contract_id'];
			
			//get quotes details
			$contractDetails=$contract_obj->getContract($update['contract_id']);
			$update['quote_id']=$contractDetails[0]['quoteid'];
			
			$contractMission=$turnover_obj->getMissionDetails($update['mission_id']);
						if($contractMission[0]['product_type']!="")
						{
						$update['name']=$this->producttype_array[$contractMission[0]['product_type']];
						}elseif($contractMission[0]['product']!="")
						{
						$update['name']=$this->product_array[$contractMission[0]['product']];
						}
						else
						{
						$techMissoinType=$turnover_obj->getTechMission($update['mission_id']);
						$update['name']=$techMissoinType[0]['title'];
						}
						$update['from_followup']=1;
				
			$mission_exsist=$turnover_obj->getsplitmonthexists($update);
	
				if(!$mission_exsist)
				{
					//insert splitpermonth	
					$turnover_obj->insertSplitTurnovers($update);	
				}else
				{
					$data=array();
					$data['turnover']=$update['turnover'];
					$turnover_obj->UpdatesplitMonth($data,$update['mission_id'],$update['contract_id'],$update['month_year'],$update['quote_id']);
				}
			
			$year=explode('-',$update['month_year']);
			$url="";
			if($parmas['fil-product']) $url .='&product='.$parmas['fil-product'];
			if($parmas['fil-p_type']) $url .='&product='.$parmas['fil-p_type'];
			
			$this->_redirect("/turnover/loadclientlist?submenuId=ML13-SL3&clientid=".$parmas['clientid']."&year=".$year[0].$url);		
			
			
		 }	
		
		
	}
	
	function salesDetailsAction()
	{
		
		$parmas = $this->_request->getParams();
		
		$sales_id=$parmas['sales_id'];
		$client_obj = new Ep_Quote_Client();	
		//$client_details_obj = new Ep_User_BoUser();	
		$turnover_obj = new Ep_Quote_Turnover();
		$sales_details= $turnover_obj->clientQuotesdetails($sales_id);
		
					$salesDetails = $client_obj->getQuoteUserDetails($sales_id);
					$salesownerdetail['sales_owner'] = $salesDetails[0]['first_name']." ".$salesDetails[0]['last_name'];
					$salesownerdetail['mailto'] = $salesDetails[0]['email'];
					$salesownerdetail['sales_creator_id'] = $sales_id;
					$salesownerdetail['city'] = $salesDetails[0]['city'];
					$salesownerdetail['state'] = $salesDetails[0]['state'];
					$salesownerdetail['email'] = $salesDetails[0]['email'];
					//$client_infor=$client_details_obj->getBoUserExtraDetails($sales_id);
					//$salesownerdetail['skype_id'] = $client_infor[0]['skype_id'];
					$totalclientquotes=count($sales_details);
					
		$signed_count=0;
		foreach($sales_details as $sales){
			if($sales['sales_review']=='signed'){
					$signed_count++;
				}
		}
		$salesownerdetail['signedcount']=$signed_count;
		$salesownerdetail['averagecount']=round($signed_count/$totalclientquotes*100);
		
		$this->_view->salesownerdetail=$salesownerdetail;
		
		$this->render('split-sales-details-popup');
		
		
	}
	
	
	
	//generating report
	
	function splitMonthReportAction()
	{
	
		$parmas = $this->_request->getParams();
		$turnover_obj = new Ep_Quote_Turnover();
		$client_obj = new Ep_Quote_Client();	
		//$client_details_obj = new Ep_User_BoUser();	
			
		//default Year selection
		if($parmas['year'])
		{
		$this->default_year=$parmas['year'];
		$this->_view->default_year=$parmas['year'];
		}
		else
		{
		$this->default_year=date("Y");
		$this->_view->default_year=date("Y");
		}	
		//setlocale(LC_TIME, "fr_FR");
		if($parmas['client_id']!="")
		{
				$searchclient['client_id']=$parmas['client_id'];
				$quotecontractlist = $turnover_obj->getClientContracts($searchclient);
		}
		else
		{
			ini_set('max_execution_time','300');
			ini_set('memory_limit','512M');
				$quotecontractlist = $turnover_obj->getClientContracts();
		}
		
		$c=0;
		$tablehtml="<table cellspacing='0' cellpadding='0' border='1' ><thead> <tr> 
					<th  bgcolor='#237e9f' align='center' style='width:50px'>Code</th>
					 <th style='width:100px !important;' align='center'  bgcolor='#237e9f'>Client</th>
					<th  bgcolor='#237e9f' align='center' style='width:50px !important;word-wrap:break-all !important;'>Sales in Charge</th>";
					 foreach($this->month_array as $month)
					 {
					$tablehtml .="<th  bgcolor='#237e9f' align='center' >".ucfirst(strftime("%b", mktime(null, null, null, $month)))."</th>";	  
					}
		$tablehtml .="<th  bgcolor='#1fbba6' align='center' >TOTAL ".$this->default_year."</th></tr></thead>";
		foreach($quotecontractlist as $quotescon)
		{ //start
			
				$searchcontract['contract_id']=$quotescon['quotecontractid'];
				if($parmas['p_type'])
				{
				$searchcontract['name']=$this->producttype_array[$parmas['p_type']];
				}
				if($parmas['product'])
				{
									if($this->product_array[$parmas['product']])
				$searchcontract['product']=$this->product_array[$parmas['product']];
									else
									$searchcontract['product']=str_replace("_",' ',$parmas['product']);
				}
				$splitmonthturnoverclient=$turnover_obj->getSplitTurnoversclients($searchcontract);
								
								//Mission Details
										
								$totaltrunarray=$this->clientcontracttotalloop($splitmonthturnoverclient,$quotescon,$totaltrunarray);
								$totaltrunarray[$quotescon['client_id']]['client_name']=$quotescon['company_name'];
								$totaltrunarray[$quotescon['client_id']]['client_id']=$quotescon['client_id'];
								$totaltrunarray[$quotescon['client_id']]['client_code']=$quotescon['client_code'];
								$totaltrunarray[$quotescon['client_id']]['sales_suggested_currency']=$quotescon['sales_suggested_currency'];
								$contract_details['totalclient'] =$totaltrunarray;	
								
				
							
							   //client Details
								$salesDetails = $client_obj->getQuoteUserDetails($quotescon['sales_creator_id']);
								//Total Turn over by user
														
								//Mission Details
								if($splitmonthturnoverclient=="")
								 {
								
									 $missionDetailsloop=$turnover_obj->getProdSeoMissions(array('contract_id'=>$quotescon['quotecontractid'],'product'=>$parmas['product'],'product_type'=>$parmas['p_type']));
									 $c=0;
										 foreach($missionDetailsloop as $missoin)
										 {
											 $clienttrunarray[$quotescon['quotecontractid']][$missoin['qmid']]['mission_type_other']=$missoin['product_type_other'];
											 if(!in_array($missoin['qmid'],$clienttrunarray[$quotescon['quotecontractid']]))
												{
												$clienttrunarray[$quotescon['quotecontractid']][$c]=$missoin['qmid'];
												 $clienttrunarray[$quotescon['quotecontractid']]['client_id']= $quotescon['client_id'];
												}
											 if($missoin['product']=='translation')
												{
													$clienttrunarray[$quotescon['quotecontractid']][$missoin['qmid']]['mission_type'] = $this->product_array[$missoin['product']]." ".$this->producttype_array[$missoin['product_type']]." ".$this->getCustomName("EP_LANGUAGES",$missoin['language_source'])." vers ".$this->getCustomName("EP_LANGUAGES",$missoin['language_dest']);
												}
												else
												{
													$clienttrunarray[$quotescon['quotecontractid']][$missoin['qmid']]['mission_type'] = $this->product_array[$missoin['product']]." ".$this->producttype_array[$missoin['product_type']]." en ".$this->getCustomName("EP_LANGUAGES",$missoin['language_source']);
												}
											$c++; 
										 }
										$searchParameters['identifier']= $parmas['product'];
										$searchParameters['quote_id']= $quotescon['quoteid'];
										$searchParameters['contract_id']= $quotescon['quotecontractid'];
										$searchParameters['include_final']='yes';
										if($parmas['product'] && $parmas['p_type']=='') $searchParameters['title']=str_replace("_",' ',$parmas['product']);
										elseif($parmas['product']=="" && $parmas['p_type']) $searchParameters['title']=str_replace("_",' ',$parmas['p_type']);
										$techMissionDetails=$turnover_obj->getTechMissionDetails($searchParameters);
										$i=$c;
										foreach($techMissionDetails as $techmisson)
										 {
												 if(!in_array($techmisson['identifier'],$clienttrunarray[$quotescon['quotecontractid']]))
												{
												$clienttrunarray[$quotescon['quotecontractid']][$i]=$techmisson['identifier'];
												 $clienttrunarray[$quotescon['quotecontractid']]['client_id']= $quotescon['client_id'];
												}
												$clienttrunarray[$quotescon['quotecontractid']][$techmisson['identifier']]['mission_type'] = $techmisson['title'];
											$i++; 
										 }
								 }	
								else
								{	
								$clienttrunarray=$this->clientcontractmissionloop($splitmonthturnoverclient,$quotescon,$clienttrunarray,$this->default_year);
								}
								$clienttrunarray[$quotescon['quotecontractid']]['contractname'] = $quotescon['contractname'];		 
								$clienttrunarray[$quotescon['quotecontractid']]['contract_id'] = $quotescon['quotecontractid'];	
								$clienttrunarray[$quotescon['quotecontractid']]['sales_owner'] = $salesDetails[0]['first_name']." ".$salesDetails[0]['last_name'];	 
								$clienttrunarray[$quotescon['quotecontractid']]['sales_suggested_currency']=$quotescon['sales_suggested_currency'];
								$contract_details['contract_Contrat_details'][$quotescon['client_id']]=$clienttrunarray;
							
						
				
		} //endforeach
		
		$tablehtml .='<tbody>';
		foreach($contract_details['totalclient'] as $contractfullloop)
		{
			
						//client turnovers
				$tablehtml .="<tr>";
				$tablehtml .="<td><b>".$contractfullloop['client_code']."</b></td>";
				$tablehtml .="<td color='#267cff'><b>".$contractfullloop['client_name']."</b></td>";
				$tablehtml .="<td></td>";
				foreach($this->month_array as $clval)
				{
						if($contractfullloop[$this->default_year.'-'.$clval])
						{
						$tablehtml .="<td align='right' style='width:40px !important;word-wrap:break-all !important;'>".zero_cut($contractfullloop[$this->default_year.'-'.$clval]+0)."</td>";
						}
						else
						{
						$tablehtml .="<td align='right' style='width:40px !important;word-wrap:break-all !important;'>".($contractfullloop[$this->default_year.'-'.$clval]+0)."</td>";
						}
					
				}	
				
				
				$tablehtml .="<td style='width:50px !important;word-wrap:break-all !important;' align='right'><b>".zero_cut($contractfullloop['turnover']+0)." &".$contractfullloop['sales_suggested_currency'].";</b></td>";
				$tablehtml .="</tr>";
			
					//client contract mission
					foreach($contract_details['contract_Contrat_details'][$contractfullloop['client_id']] as $clientdetails)
					{
						if($contractfullloop['client_id']==$clientdetails['client_id'])
					{
							$tablehtml .="<tr>";
							$tablehtml .="<td></td>";
							$tablehtml .="<td color='#267cff'><b>".$clientdetails['contractname']."</b></td>";
							$tablehtml .="<td>".$clientdetails['sales_owner']."</td>";
							
							foreach($this->month_array as $conmon)
							{
											if($clientdetails[$this->default_year.'-'.$conmon])
											{
											$tablehtml .="<td align='right' style='width:40px !important;word-wrap:break-all !important;'>".zero_cut($clientdetails[$this->default_year.'-'.$conmon]+0)."</td>";
											}
											else
											{
											$tablehtml .="<td align='right' style='width:40px !important;word-wrap:break-all !important;'>".($clientdetails[$this->default_year.'-'.$conmon]+0)."</td>";	
											}
										
							}	
							
							$tablehtml .="<td style='width:50px !important;word-wrap:break-all !important;' align='right'>".zero_cut($clientdetails['turnover']+0)." &".$contractfullloop['sales_suggested_currency'].";</td>";
							$tablehtml .="</tr>";
						
						//mission details
							foreach($clientdetails as $key=>$missiondetails)
								{
									if(is_array($clientdetails[$key]))
									{			
										$tablehtml .="<tr>";
										$tablehtml .="<td></td>";
										$tablehtml .="<td color='#267cff'>".$clientdetails[$key]['mission_type']." ".htmlentities($clientdetails[$key]['mission_type_other'])."</td>";
										$tablehtml .="<td></td>";
										foreach($this->month_array as $mismon)
										{
											if($clientdetails[$key][$this->default_year.'-'.$mismon])
											{
											$tablehtml .="<td align='right' style='width:40px !important;word-wrap:break-all !important;'>".zero_cut($clientdetails[$key][$this->default_year.'-'.$mismon]+0)."</td>";	
											}
											else
											{
											$tablehtml .="<td align='right' style='width:40px !important;word-wrap:break-all !important;'>".($clientdetails[$key][$this->default_year.'-'.$mismon]+0)."</td>";	
											}
										
										}	
											
										$tablehtml .="<td style='width:40px !important;word-wrap:break-all !important;' align='right'>".zero_cut($clientdetails[$key]['turnover']+0)." &".$contractfullloop['sales_suggested_currency'].";</td>";
										$tablehtml .="</tr>";
									}
									$i++;		
								} //mission foreach
						}
						
					}//contract foreach
			}//client foreach
			$tablehtml .='</tbody>';
			$tablehtml .="</table>";
			
			//echo $tablehtml;exit;
			
					if($parmas['download']=='pdf')
					{
						
						$f_name=time()."-splitmonth.pdf";
						$path=$_SERVER['DOCUMENT_ROOT']."/BO/turnover-report/$f_name";
						chmod($path,0777);
						$this->htmltopdf($tablehtml,$path);
											
						header('Content-Description: File Transfer');
						header('Content-Type: application/octet-stream');
						header("Content-Type: application/force-download");
						header('Content-Disposition: attachment; filename=' . urlencode(basename($path)));
						// header('Content-Transfer-Encoding: binary');
						header('Expires: 0');
						header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
						header('Pragma: public');
						header('Content-Length: ' . filesize($path));
						ob_clean();
						flush();
						readfile($path);
					}
					else
					{
						$f_name=time()."-splitmonth.xlsx";
						$path=$_SERVER['DOCUMENT_ROOT']."/BO/turnover-report/$f_name";
						chmod($path,0777);
						convertHtmltableToXlsx($tablehtml,$path,True);
						//$this->_redirect("/BO/turnover-report/$f_name");
						$this->_redirect("/BO/download-turnover-report.php?type=turnover&filename=$f_name");
					} 
	}
	
	
	
	function realTurnoverReportAction()
	{
		
	$request = $this->_request->getParams();
		$turnover_obj = new Ep_Quote_Turnover();
		$contract_obj = new Ep_Quote_Quotecontract();
		$client_obj = new Ep_Quote_Client();	
		if($request['year'])
		{
			$this->_view->year = $request['year'];
			$this->default_year=$request['year'];
		$this->_view->default_year=$request['year'];
		}
		else
		{
			$request['year'] = $this->_view->year = date("Y");
		$this->default_year=date("Y");
		$this->_view->default_year=date("Y");
		}
		$this->_view->client = $request['client'];
		$realturnovers = $turnover_obj->getRealTurnovers($request);
		$monthturnoversre = array();
		foreach($realturnovers as $realturnover)
		{
			
			$monthturnoversre[$realturnover['user_id']][$realturnover['yearmonth']] = $realturnover['publishedprice'];
			$monthturnoversre[$realturnover['user_id']]['other_info'] = $realturnover;
			
			$quotecontract_obj = new Ep_Quote_Quotecontract();
			$contracts = $quotecontract_obj->getContracts(array('client_id'=>$realturnover['user_id'],'not_mulitple_status'=>"'deleted'",'sales_id'=>$request['sales_id']));
			
			foreach($contracts as $contract)
			{
				$contractturnover[$realturnover['user_id']][$contract['quotecontractid']]['contract_details'] = $contract;
				
				/* Real turnover and missions */
				$cmissiondetails = $turnover_obj->getProdSeoMissions(array('contract_id'=>$contract['quotecontractid'],'product'=>$request['product'],'product_type'=>$request['p_type']));
				foreach($cmissiondetails as $contract_mission)
				{
					$contractturnover[$realturnover['user_id']][$contract['quotecontractid']][$contract_mission['qmid']] = $contract_mission;
					$contractturnover[$realturnover['user_id']][$contract['quotecontractid']][$contract_mission['qmid']]['title_other']=$contract_mission['product_type_other'];
					if($contract_mission['product']=='translation')
					{
						$contractturnover[$realturnover['user_id']][$contract['quotecontractid']][$contract_mission['qmid']]['title'] = $this->product_array[$contract_mission['product']]." ".$this->producttype_array[$contract_mission['product_type']]." ".$this->getCustomName("EP_LANGUAGES",$contract_mission['language_source'])." vers ".$this->getCustomName("EP_LANGUAGES",$contract_mission['language_dest']);
					}
					else
					{
						$contractturnover[$realturnover['user_id']][$contract['quotecontractid']][$contract_mission['qmid']]['title'] = $this->product_array[$contract_mission['product']]." ".$this->producttype_array[$contract_mission['product_type']]." en ".$this->getCustomName("EP_LANGUAGES",$contract_mission['language_source']);
						$array['lang'] = $this->getCustomName("EP_LANGUAGES",$contract_mission['language_source']);
					}
					if($contract_mission['contractmissionid'] && $contract_mission['type']=="prod")
					{
						$years_mission = $turnover_obj->getRealTurnovers(array('contract_mission_id'=>$contract_mission['contractmissionid'],'year'=>$this->_view->year));
						foreach($years_mission as $yearm)
						{
							$real_price = $contract_mission['unit_price']*$yearm['total_packs'];
							$contractturnover[$realturnover['user_id']][$contract['quotecontractid']][$contract_mission['qmid']][$yearm['yearmonth']]  = $yearm['publishedprice'];
							$contractturnover[$realturnover['user_id']][$contract['quotecontractid']][$contract_mission['qmid']]['real_'.$yearm['yearmonth']]  = $real_price;
							$contractturnover[$realturnover['user_id']][$contract['quotecontractid']]['contract_details'][$yearm['yearmonth']] +=  $yearm['publishedprice'];
							$contractturnover[$realturnover['user_id']][$contract['quotecontractid']]['contract_details']['realturnover'] += $yearm['publishedprice'];
							$contractturnover[$realturnover['user_id']][$contract['quotecontractid']]['contract_details']['realturnover_'.$yearm['yearmonth']] += $real_price;
							$canvas_real[$yearm['yearmonth']] += $yearm['publishedprice'];
						}
					}
					else
					{
						if($this->_view->year==$contract_mission['validatedyear'])
						{
							$contractturnover[$realturnover['user_id']][$contract['quotecontractid']][$contract_mission['qmid']][$contract_mission['validatedyearmonth']]  = $contract_mission['missionturnover'];
							$contractturnover[$realturnover['user_id']][$contract['quotecontractid']]['contract_details'][$contract_mission['validatedyearmonth']] += $contract_mission['missionturnover'];
							$canvas_real[$contract_mission['validatedyearmonth']] +=  $contract_mission['missionturnover'];
						}
						$contractturnover[$realturnover['user_id']][$contract['quotecontractid']][$contract_mission['qmid']]['type']  = "seo";
					}
					/* Check edited and deleted */
					if($contract_mission['is_edited'])
					{
						$updated_at = date("Y-m",strtotime($contract_mission['updated_at']));
						$contractturnover[$realturnover['user_id']][$contract['quotecontractid']][$contract_mission['qmid']]['edited_at'.$updated_at]  ="missionEdited" ;
					} 
					if($contract_mission['cm_status']=="deleted")
					{
						$updated_at = date("Y-m",strtotime($contract_mission['cmupdated_at']));
						$contractturnover[$realturnover['user_id']][$contract['quotecontractid']][$contract_mission['qmid']]['deleted_at'.$updated_at]  ="missionDeleted" ;
					}
					if($contract_mission['year_freeze_start_date']==$request['year'] || $contract_mission['year_freeze_end_date']==$request['year'])
					{
						$contract_mission['freeze_start_date']." ".$contract_mission['freeze_end_date']."<br>";
						$date1  = $contract_mission['freeze_start_date'];
						$date2  = $contract_mission['freeze_end_date'];
						$output = array();
						$time   = strtotime($date1);
						$last   = date('m-Y', strtotime($date2));

						do 
						{
							$month = date('m-Y', $time);
							$month_year = date('Y-m', $time);
							
						$contractturnover[$realturnover['user_id']][$contract['quotecontractid']][$contract_mission['qmid']]['freezed_at'.$month_year] = "missionFreezed";

							$time = strtotime('+1 month', $time);
						} while ($month != $last);
						
						
					}
				}
				
				/* To get tech missions and turnover */
				$searchParameters = array();
				$searchParameters['quote_id']= $contract['quoteid'];
				$searchParameters['contract_id']= $contract['quotecontractid'];
				$searchParameters['include_final']='yes';
				if($request['product']) $searchParameters['product']=str_replace("_",' ',$request['product']);
				elseif($request['p_type']) $searchParameters['p_type']=str_replace("_",' ',$request['p_type']);
				$techMissionDetails=$turnover_obj->getTechMissionDetails($searchParameters);
				
				foreach($techMissionDetails as $tech_mission)
				{
					if($this->_view->year==$tech_mission['validatedyear'])
					{
						$contractturnover[$realturnover['user_id']][$contract['quotecontractid']][$tech_mission['identifier']][$tech_mission['validatedyearmonth']]  = $tech_mission['turnover'];
						$contractturnover[$realturnover['user_id']][$contract['quotecontractid']]['contract_details'][$tech_mission['validatedyearmonth']] += $tech_mission['turnover'];
						$canvas_real[$tech_mission['validatedyearmonth']] +=  $tech_mission['turnover'];
					}
					$contractturnover[$realturnover['user_id']][$contract['quotecontractid']][$tech_mission['identifier']]['type']  = "tech";
					$contractturnover[$realturnover['user_id']][$contract['quotecontractid']][$tech_mission['identifier']]['title'] = $tech_mission['title'];
				}
				
				/* To get expected turnover by Naveen */
				$searchcontract['contract_id']=$contract['quotecontractid'];
				if($request['p_type'])
				{
					$searchcontract['name']=$this->producttype_array[$request['p_type']];
					}
				if($request['product'])
				{
					$searchcontract['product']=$this->product_array[$request['product']];
					}
				$splitmonthturnoverclient=$turnover_obj->getSplitTurnoversclients($searchcontract);
				$clienttrunarray = array();
				$clienttrunarray=$this->clientcontractmissionloop($splitmonthturnoverclient,$contract,$clienttrunarray,$this->_view->year);
			 					
				foreach($clienttrunarray as $contractarray => $cvalue)
				{
					foreach($cvalue as $key => $value)
					{
						if(is_array($value))
						{
							foreach($value as $mission => $mvalue)
							{
								$contractturnover[$realturnover['user_id']][$contract['quotecontractid']][$key]['expected_'.$mission] = $mvalue;
							}
						}
						else
						{
							$contractturnover[$realturnover['user_id']][$contract['quotecontractid']]['contract_details']['expected_'.$key] = $value;
							if($key=='turnover') $monthturnoversre[$realturnover['user_id']]['other_info']['expected_'.$key]+=$value;
							
							
						}

						if(strpos($key,"-"))
						{
							$canvas_expected[$key] += $value;
						}

					}
				}
			}
			
		}
		
	//echo "<pre>"; print_r($monthturnoversre); exit;
		
		$tablehtml="<table cellspacing='0' cellpadding='0' border='1' ><thead> <tr> 
					<th  bgcolor='#237e9f' align='center' style='width:50px'>Code</th>
					 <th style='width:100px !important;' align='center'  bgcolor='#237e9f'>Client</th>
					<th  bgcolor='#237e9f' align='center' style='width:50px !important;word-wrap:break-all !important;'>Sales in Charge</th>
						 <th style='width:100px !important;' align='center'  bgcolor='#237e9f'>Launch Date</th>
						 	 <th style='width:100px !important;' align='center'  bgcolor='#237e9f'>End Date</th>";
					 foreach($this->month_array as $month)
					 {
					$tablehtml .="<th  bgcolor='#237e9f' align='center' >".ucfirst(strftime("%b", mktime(null, null, null, $month)))."</th>";	  
					$tablehtml .="<th  bgcolor='#237e9f' align='center' >Cost</th>";	  
					$tablehtml .="<th  bgcolor='#237e9f' align='center' >Margin</th>";	  
					}
		$tablehtml .="<th  bgcolor='#1fbba6' align='center' >TOTAL ".$this->_view->year." till now</th>
					<th  bgcolor='#1fbba6' align='center' >TOTAL ".$this->_view->year." Expected</th>
					<th  bgcolor='#1fbba6' align='center' >TOTAL Cost</th>
					<th  bgcolor='#1fbba6' align='center' >Margin</th>
		</tr></thead>";
		
		
		foreach($monthturnoversre as $clientDetails)
		{
			$client_id=$clientDetails['other_info']['user_id'];
			foreach($contractturnover[$client_id] as $contracttotal)
				{
					foreach($this->month_array as $monthreal)
					 {
					  $client['clirealtotal'.$this->_view->year.'-'.$monthreal]=$client['clirealtotal'.$this->_view->year.'-'.$monthreal]+$contracttotal['contract_details']['realturnover_'.$this->_view->year.'-'.$monthreal];
					  $client['cliexpected_'.$this->_view->year.'-'.$monthreal]=$client['cliexpected_'.$this->_view->year.'-'.$monthreal]+$contracttotal['contract_details']['expected_'.$this->_view->year.'-'.$monthreal];
					}
				}
					$tablehtml .="<tr>";
					$tablehtml .="<td>".$clientDetails['other_info']['client_code']."</td>";
					$tablehtml .="<td>".$clientDetails['other_info']['company_name']."</td>";
					$tablehtml .="<td></td>";
					$tablehtml .="<td></td>";
					$tablehtml .="<td></td>";
					$total=0;
					$totalexpected=0;
					$totalcosttotal=0;
					 foreach($this->month_array as $monthreal)
					 {
						$totalexpected=$client['cliexpected_'.$this->_view->year.'-'.$monthreal]+$totalexpected;
						 $tablehtml .="<td>".zero_cut($client['clirealtotal'.$this->_view->year.'-'.$monthreal],2)."</td>";
						
						$total=$client['clirealtotal'.$this->_view->year.'-'.$monthreal]+$total;
						
						$tablehtml .="<td>".zero_cut($clientDetails[$this->_view->year.'-'.$monthreal],2)."</td>";
						
						$totalcosttotal=$clientDetails[$this->_view->year.'-'.$monthreal]+$totalcosttotal;
						
						if($client['clirealtotal'.$this->_view->year.'-'.$monthreal]==0)
							$tablehtml .="<td>0%</td>";
						else
							$tablehtml .="<td>".zero_cut((1-($clientDetails[$this->_view->year.'-'.$monthreal]/$client['clirealtotal'.$this->_view->year.'-'.$monthreal]))*100,2)."</td>";
					 }
					$tablehtml .="<td><b>".zero_cut($total,2)."</b></td>";
					$tablehtml .="<td><b>".zero_cut($totalexpected,2)."</b></td>";
					$tablehtml .="<td><b>".zero_cut($totalcosttotal,2)."</b></td>";
					if($total)
						$tablehtml .="<td><b>0%</b></td>";
					else
						$tablehtml .="<td><b>".zero_cut((1-($totalcosttotal/$total))*100,2)."%</b></td>";
					$tablehtml .="</tr>";
					
					
				foreach($contractturnover[$client_id] as $contract)
				{
				
					foreach ($contract as $missionkey=>$mission)
					{
						if ($missionkey=="contract_details" )
						{
							$tablehtml .="<tr>";
							$tablehtml .="<td></td>";
							$tablehtml .="<td><b>".$mission['contractname']."</b></td>";
							$tablehtml .="<td><b>".$mission['first_name'].'&nbsp;'.$mission['last_name']."</b></td>";
							$tablehtml .="<td><b>".date('d/m/Y',strtotime($mission['expected_launch_date']))."</b></td>";
							$tablehtml .="<td><b>".date('d/m/Y',strtotime($mission['expected_end_date']))."</b></td>";
							$miscon=0;
							$concosttotal=0;
							foreach($this->month_array as $monthcon)
							 {
								 
								$tablehtml .="<td><b>".zero_cut($mission['realturnover_'.$this->_view->year.'-'.$monthcon],2)."</b></td>";
								
								
								$miscon =$miscon+$mission['realturnover_'.$this->_view->year.'-'.$monthcon];								
								
								$tablehtml .="<td><b>".zero_cut($mission[$this->_view->year.'-'.$monthcon],2)."</b></td>";
								
								$concosttotal =$concosttotal+$mission[$this->_view->year.'-'.$monthcon];
								
								if($mission['realturnover_'.$this->_view->year.'-'.$monthcon]==0)
									$tablehtml .="<td>0%</b></td>";
								else
									$tablehtml .="<td><b>".zero_cut((1-($mission[$this->_view->year.'-'.$monthcon]/$mission['realturnover_'.$this->_view->year.'-'.$monthcon]))*100,2)."%</b></td>";
								
								
								
							 }
							$tablehtml .="<td><b>".zero_cut($miscon,2).' &'.$mission['sales_suggested_currency'].";</b></td>";
							$tablehtml .="<td><b>".zero_cut($mission['expected_turnover'],2).' &'.$mission['sales_suggested_currency'].";</b></td>";
							
							$tablehtml .="<td><b>".zero_cut($concosttotal,2).' &'.$mission['sales_suggested_currency'].";</b></td>";
							if($miscon==0)
								$tablehtml .="<td><b>0% </b></td>";
							else
								$tablehtml .="<td><b>".zero_cut((1-($concosttotal/$miscon))*100,2)."% </b></td>";
							$tablehtml .="</tr>";
						}
						else
						{
							$bgcolor="";
							if($mission['from_contract']=='1') $bgcolor="bgcolor='#ff0000'";
							$tablehtml .="<tr>";
							$tablehtml .="<td $bgcolor></td>";
							$tablehtml .="<td $bgcolor>".$mission['title']." ".htmlentities($mission['title_other'])."</td>";
							$tablehtml .="<td $bgcolor></td>";
							$tablehtml .="<td $bgcolor></td>";
							$tablehtml .="<td $bgcolor></td>";
							$mistotal=0;
							$miscosttotal=0;
							foreach($this->month_array as $monmis)
							 {
								 $inside="";
								 $month_yearval=$this->_view->year.'-'.$monmis;
								 if($mission['edited_at'.$month_yearval])	 $inside="bgcolor='#5be7a9'";
								 elseif($mission['deleted_at'.$month_yearval]) $inside="bgcolor='#f39c12'";
								 elseif($mission['freezed_at'.$month_yearval]) $inside="bgcolor='#288fb4'";
								 
								
								 	$tablehtml .="<td $inside $bgcolor>".zero_cut($mission['real_'.$month_yearval],2)."</td>";
								
								$mistotal =$mistotal+$mission['real_'.$month_yearval];
								
								
								$tablehtml .="<td $inside $bgcolor>".zero_cut($mission[$month_yearval],2)."</b></td>";
								
								$miscosttotal =$miscosttotal+$mission[$month_yearval];
								
								if($mission['real_'.$month_yearval]==0)
								{
									$tablehtml .="<td $inside $bgcolor>0%</td>";
								}
								else
								{
									$tablehtml .="<td $inside $bgcolor>".zero_cut((1-($mission[$month_yearval]/$mission['real_'.$month_yearval]))*100,2)."%</td>";	
								}
								
							 }

							$tablehtml .="<td $bgcolor>".zero_cut($mistotal,2)."</td>";
							$tablehtml .="<td $bgcolor>".zero_cut($mission['expected_turnover'],2)."</td>";
							$tablehtml .="<td $bgcolor>".zero_cut($miscosttotal,2)."</td>";
							if($mistotal==0)
								$tablehtml .="<td $bgcolor>0</td>";
							else
								$tablehtml .="<td $bgcolor>".zero_cut((1-($miscosttotal/$mistotal))*100,2)."</td>";
							$tablehtml .="</tr>";
						}
					}
						
				}	
					
		} //client details End
		
	
	$tablehtml .="</table>";
	//echo $tablehtml; exit;
	
	//compare sheet xlsx
		$html1="<table cellspacing='0' cellpadding='0' border='1' ><thead> <tr> 
					<th  bgcolor='#237e9f' align='center' style='width:50px'>Code</th>
					 <th style='width:100px !important;' align='center'  bgcolor='#237e9f'>Client</th>
					<th  bgcolor='#237e9f' align='center' style='width:50px !important;word-wrap:break-all !important;'>Sales in Charge</th>
						 <th style='width:100px !important;' align='center'  bgcolor='#237e9f'>Launch Date</th>
						 	 <th style='width:100px !important;' align='center'  bgcolor='#237e9f'>End Date</th>";
					 foreach($this->month_array as $month)
					 {
					$html1 .="<th  bgcolor='#237e9f' align='center' >".ucfirst(strftime("%b", mktime(null, null, null, $month)))."</th>";	  
					$html1 .="<th  bgcolor='#237e9f' align='center' >".ucfirst(strftime("%b", mktime(null, null, null, $month)))."</th>";	  
					$html1 .="<th  bgcolor='#237e9f' align='center' >Difference</th>";	  
					}
		$html1 .="<th  bgcolor='#1fbba6' align='center' >TOTAL ".$this->_view->year." till now</th>
					<th  bgcolor='#1fbba6' align='center' >TOTAL ".$this->_view->year." Expected</th>
					<th  bgcolor='#1fbba6' align='center' >Difference</th>
		</tr></thead>";
		
		foreach($monthturnoversre as $clientDetails)
		{
			$client_id=$clientDetails['other_info']['user_id'];
			foreach($contractturnover[$client_id] as $contracttotal)
				{
					foreach($this->month_array as $monthreal)
					 {
					  $client['clirealtotal'.$this->_view->year.'-'.$monthreal]=$client['clirealtotal'.$this->_view->year.'-'.$monthreal]+$contracttotal['contract_details']['real_'.$this->_view->year.'-'.$monthreal];
					  $client['cliexpectotal'.$this->_view->year.'-'.$monthreal]=$client['cliexpectotal'.$this->_view->year.'-'.$monthreal]+$contracttotal['contract_details']['expected_'.$this->_view->year.'-'.$monthreal];
					}
				}
					$html1 .="<tr>";
					$html1 .="<td>".$clientDetails['other_info']['client_code']."</td>";
					$html1 .="<td>".$clientDetails['other_info']['company_name']."</td>";
					$html1 .="<td></td>";
					$html1 .="<td></td>";
					$html1 .="<td></td>";
					$totalexpec=0;
					$total=0;
					 foreach($this->month_array as $monthreal)
					 {
					$html1 .="<td>".zero_cut($client['cliexpectotal'.$this->_view->year.'-'.$monthreal],2)."</td>";
						$totalexpec=$client['cliexpectotal'.$this->_view->year.'-'.$monthreal]+$totalexpec;
					$html1 .="<td>".zero_cut($client['clirealtotal'.$this->_view->year.'-'.$monthreal],2)."</td>";
						$total=$client['clirealtotal'.$this->_view->year.'-'.$monthreal]+$total;
					$html1 .="<td>".zero_cut($client['clirealtotal'.$this->_view->year.'-'.$monthreal]-$client['cliexpectotal'.$this->_view->year.'-'.$monthreal],2)."</td>";
					 }
					$html1 .="<td><b>".zero_cut($totalexpec,2)."</b></td>";
					$html1 .="<td><b>".zero_cut($total,2)."</b></td>";
					$html1 .="<td><b>".zero_cut($total-$totalexpec,2)."</b></td>";
					$html1 .="</tr>";
				foreach($contractturnover[$client_id] as $contract)
				{
					foreach ($contract as $missionkey=>$mission)
					{
						if ($missionkey=="contract_details" )
						{
							$html1 .="<tr>";
							$html1 .="<td></td>";
							$html1 .="<td><b>".$mission['contractname']."</b></td>";
							$html1 .="<td><b>".$mission['first_name'].'&nbsp;'.$mission['last_name']."</b></td>";
							$html1 .="<td><b>".date('d/m/Y',strtotime($mission['expected_launch_date']))."</b></td>";
							$html1 .="<td><b>".date('d/m/Y',strtotime($mission['expected_end_date']))."</b></td>";
							$miscon=0;
							$conexptotal=0;
							foreach($this->month_array as $monthcon)
							 {
								 $html1 .="<td><b>".zero_cut($mission['expected_'.$this->_view->year.'-'.$monthcon],2)."</b></td>";
								$conexptotal =$conexptotal+$mission['expected_'.$this->_view->year.'-'.$monthcon];	
								$html1 .="<td><b>".zero_cut($mission['realturnover_'.$this->_view->year.'-'.$monthcon],2)."</b></td>";
								$miscon =$miscon+$mission['realturnover_'.$this->_view->year.'-'.$monthcon];
								$html1 .="<td><b>".zero_cut($mission['realturnover_'.$this->_view->year.'-'.$monthcon]-$mission['expected_'.$this->_view->year.'-'.$monthcon],2)."</b></td>";
							 }
							$html1 .="<td><b>".zero_cut($conexptotal,2).' &'.$mission['sales_suggested_currency'].";</b></td>";
							$html1 .="<td><b>".zero_cut($miscon,2).' &'.$mission['sales_suggested_currency'].";</b></td>";
							
							$html1 .="<td><b>".zero_cut($miscon-$mission['expected_turnover'],2).' &'.$mission['sales_suggested_currency'].";</b></td>";
							$html1 .="</tr>";
						}
						else
						{
							$bgcolor="";
							if($mission['from_contract']=='1') $bgcolor="bgcolor='#ff0000'";
							$html1 .="<tr>";
							$html1 .="<td $bgcolor></td>";
							$html1 .="<td $bgcolor>".$mission['title']."</td>";
							$html1 .="<td $bgcolor></td>";
							$html1 .="<td $bgcolor></td>";
							$html1 .="<td $bgcolor></td>";
							$mistotal=0;
							$miscosttotal=0;
							foreach($this->month_array as $monmis)
							 {
								 $inside="";
								 $month_yearval=$this->_view->year.'-'.$monmis;
								 if($mission['edited_at'.$month_yearval])	 $inside="bgcolor='#5be7a9'";
								 elseif($mission['deleted_at'.$month_yearval]) $inside="bgcolor='#f39c12'";
								 elseif($mission['freezed_at'.$month_yearval]) $inside="bgcolor='#288fb4'";
								 	$html1 .="<td $inside $bgcolor>".zero_cut($mission['expected_'.$month_yearval],2)."</td>";
								$html1 .="<td $inside $bgcolor>".zero_cut($mission['real_'.$month_yearval],2)."</b></td>";
								$mistotal =$mistotal+$mission['real_'.$month_yearval];
								
								$html1 .="<td $inside $bgcolor>".zero_cut($mission['real_'.$month_yearval]-$mission['expected_'.$month_yearval],2)."</td>";
							 }
							$html1 .="<td $bgcolor>".zero_cut($mission['expected_turnover'],2)."</td>";
							$html1 .="<td $bgcolor>".zero_cut($mistotal,2)."</td>";
							$html1 .="<td $bgcolor>".zero_cut($mistotal-$mission['expected_turnover'],2)."</td>";
							$html1 .="</tr>";
						}
					}
				}	
		}
	$html1 .="</table>";
	$time=time();
	//compare sheet 
	$compare_name=$time."-compare.xlsx";
	$comparepath=$_SERVER['DOCUMENT_ROOT']."/BO/turnover-report/$compare_name";
					if($html1!="" && $request['download']!='pdf')
					{
						ini_set('max_execution_time','300');
						ini_set('memory_limit','512M');
						chmod($comparepath,0777);
						convertHtmltableToXlsx($html1,$comparepath,True);
					}
	 //split month sheet
	 //setlocale(LC_TIME, "fr_FR");
	 $searchsplitclient['client_id']=$request['client'];
	 if($request['sales_id'])  $searchsplitclient['sales_id']=$request['sales_id'];
	$quotecontractsplilist = $turnover_obj->getClientContracts($searchsplitclient);
	$c=0;
		$splithtml="<table cellspacing='0' cellpadding='0' border='1' ><thead> <tr> 
					<th  bgcolor='#237e9f' align='center' style='width:50px'>Code</th>
					 <th style='width:100px !important;' align='center'  bgcolor='#237e9f'>Client</th>
					<th  bgcolor='#237e9f' align='center' style='width:50px !important;word-wrap:break-all !important;'>Sales in Charge</th>";
					 foreach($this->month_array as $month)
					 {
					$splithtml .="<th  bgcolor='#237e9f' align='center' >".ucfirst(strftime("%b", mktime(null, null, null, $month)))."</th>";	  
					}
		$splithtml .="<th  bgcolor='#1fbba6' align='center' >TOTAL ".$this->_view->year."</th></tr></thead>";
		foreach($quotecontractsplilist as $quotesconsplit)
		{ //start
				$searchcontract['contract_id']=$quotesconsplit['quotecontractid'];
				if($request['p_type'])
				{
				$searchcontract['name']=$this->producttype_array[$request['p_type']];
				}
				if($request['product'])
				{
									if($this->product_array[$request['product']])
									$searchcontract['product']=$this->product_array[$request['product']];
									else
									$searchcontract['product']=str_replace("_",' ',$request['product']);
				}
						$splitmonthturnoverreclient=$turnover_obj->getSplitTurnoversclients($searchcontract);
								//Mission Details
								$totaltrunarray=$this->clientcontracttotalloop($splitmonthturnoverreclient,$quotesconsplit,$totaltrunarray);
								$totaltrunarray[$quotesconsplit['client_id']]['client_name']=$quotesconsplit['company_name'];
								$totaltrunarray[$quotesconsplit['client_id']]['client_id']=$quotesconsplit['client_id'];
								$totaltrunarray[$quotesconsplit['client_id']]['client_code']=$quotesconsplit['client_code'];
								$totaltrunarray[$quotesconsplit['client_id']]['sales_suggested_currency']=$quotesconsplit['sales_suggested_currency'];
								$contract_details['totalclient'] =$totaltrunarray;	
							   //client Details
								$salesDetails = $client_obj->getQuoteUserDetails($quotesconsplit['sales_creator_id']);
								//Total Turn over by user
								//Mission Details
								if($turnover_obj->getSplitTurnoversclients($searchcontract)=="")
								 {
									 $missionDetailsloop=$turnover_obj->getProdSeoMissions(array('contract_id'=>$quotesconsplit['quotecontractid'],'product'=>$request['product'],'product_type'=>$request['p_type']));
									 $c=0;
										 foreach($missionDetailsloop as $missoin)
										 {
											 $clienttrunarray[$quotesconsplit['quotecontractid']][$missoin['qmid']]['mission_type_other']=$missoin['product_type_other'];
											 
											 if(!in_array($missoin['qmid'],$clienttrunarray[$quotesconsplit['quotecontractid']]))
												{
												$clienttrunarrayre[$quotesconsplit['quotecontractid']][$c]=$missoin['qmid'];
												}
											 if($missoin['product']=='translation')
												{
													$clienttrunarrayre[$quotesconsplit['quotecontractid']][$missoin['qmid']]['mission_type'] = $this->product_array[$missoin['product']]." ".$this->producttype_array[$missoin['product_type']]." ".$this->getCustomName("EP_LANGUAGES",$missoin['language_source'])." vers ".$this->getCustomName("EP_LANGUAGES",$missoin['language_dest']);
												}
												else
												{
													$clienttrunarrayre[$quotesconsplit['quotecontractid']][$missoin['qmid']]['mission_type'] = $this->product_array[$missoin['product']]." ".$this->producttype_array[$missoin['product_type']]." en ".$this->getCustomName("EP_LANGUAGES",$missoin['language_source']);
												}
											$c++; 
										 }
										$searchParameters['identifier']= $parmas['product'];
										$searchParameters['quote_id']= $quotesconsplit['quoteid'];
										$searchParameters['contract_id']= $quotesconsplit['quotecontractid'];
										$searchParameters['include_final']='yes';
										if($request['product'] ) $searchParameters['product']=str_replace("_",' ',$request['product']);
										elseif($request['p_type']) $searchParameters['p_type']=str_replace("_",' ',$request['p_type']);
										$techMissionDetails=$turnover_obj->getTechMissionDetails($searchParameters);
										$i=$c;
										foreach($techMissionDetails as $techmisson)
										 {
												 if(!in_array($techmisson['identifier'],$clienttrunarray[$quotesconsplit['quotecontractid']]))
												{
												$clienttrunarrayre[$quotesconsplit['quotecontractid']][$i]=$techmisson['identifier'];
												}
												$clienttrunarrayre[$quotesconsplit['quotecontractid']][$techmisson['identifier']]['mission_type'] = $techmisson['title'];
											$i++; 
										 }
								 }	
								else
								{	
								$clienttrunarrayre=$this->clientcontractmissionloop($splitmonthturnoverreclient,$quotesconsplit,$clienttrunarrayre,$this->_view->year);
								}
								$clienttrunarrayre[$quotesconsplit['quotecontractid']]['contractname'] = $quotesconsplit['contractname'];		 
								$clienttrunarrayre[$quotesconsplit['quotecontractid']]['contract_id'] = $quotesconsplit['quotecontractid'];	
								$clienttrunarrayre[$quotesconsplit['quotecontractid']]['sales_owner'] = $salesDetails[0]['first_name']." ".$salesDetails[0]['last_name'];	 
								$clienttrunarrayre[$quotesconsplit['quotecontractid']]['sales_suggested_currency']=$quotesconsplit['sales_suggested_currency'];
								$contract_details['contract_Contrat_details'][$quotesconsplit['client_id']]=$clienttrunarrayre;
		} //endforeach
		$splithtml .='<tbody>';
		foreach($contract_details['totalclient'] as $contractfullloop)
		{
						//client turnovers
				$splithtml .="<tr>";
				$splithtml .="<td><b>".$contractfullloop['client_code']."</b></td>";
				$splithtml .="<td color='#267cff'><b>".$contractfullloop['client_name']."</b></td>";
				$splithtml .="<td></td>";
				foreach($this->month_array as $clval)
				{
						if($contractfullloop[$this->_view->year.'-'.$clval])
						{
						$splithtml .="<td align='right' style='width:40px !important;word-wrap:break-all !important;'>".zero_cut($contractfullloop[$this->_view->year.'-'.$clval]+0,2)."</td>";
						}
						else
						{
						$splithtml .="<td align='right' style='width:40px !important;word-wrap:break-all !important;'>".zero_cut($contractfullloop[$this->_view->year.'-'.$clval]+0,2)."</td>";
						}
				}	
				$splithtml .="<td style='width:50px !important;word-wrap:break-all !important;' align='right'><b>".zero_cut($contractfullloop['turnover']+0,2)." &".$contractfullloop['sales_suggested_currency'].";</b></td>";
				$splithtml .="</tr>";
					//client contract mission
					foreach($contract_details['contract_Contrat_details'][$contractfullloop['client_id']] as $clientdetails)
					{
							$splithtml .="<tr>";
							$splithtml .="<td></td>";
							$splithtml .="<td color='#267cff'><b>".$clientdetails['contractname']."</b></td>";
							$splithtml .="<td><b>".$clientdetails['sales_owner']."</b></td>";
							foreach($this->month_array as $conmon)
							{
											if($clientdetails[$this->_view->year.'-'.$conmon])
											{
											$splithtml .="<td align='right' style='width:40px !important;word-wrap:break-all !important;'><b>".zero_cut($clientdetails[$this->_view->year.'-'.$conmon]+0,2)."</b></td>";
											}
											else
											{
											$splithtml .="<td align='right' style='width:40px !important;word-wrap:break-all !important;'><b>".zero_cut($clientdetails[$this->_view->year.'-'.$conmon]+0,2)."</b></td>";	
											}
							}	
							$splithtml .="<td style='width:50px !important;word-wrap:break-all !important;' align='right'><b>".zero_cut($clientdetails['turnover']+0,2)." &".$contractfullloop['sales_suggested_currency']."; </b></td>";
							$splithtml .="</tr>";
						//mission details
							foreach($clientdetails as $key=>$missiondetails)
								{
									if(is_array($clientdetails[$key]) && $clientdetails[$key]['mission_type'])
									{			
										$splithtml .="<tr>";
										$splithtml .="<td></td>";
										$splithtml .="<td color='#267cff'>".$clientdetails[$key]['mission_type']."</td>";
										$splithtml .="<td></td>";
										foreach($this->month_array as $mismon)
										{
											if($clientdetails[$key][$this->_view->year.'-'.$mismon])
											{
											$splithtml .="<td align='right' style='width:40px !important;word-wrap:break-all !important;'>".zero_cut($clientdetails[$key][$this->_view->year.'-'.$mismon]+0,2)."</td>";	
											}
											else
											{
											$splithtml .="<td align='right' style='width:40px !important;word-wrap:break-all !important;'>".zero_cut($clientdetails[$key][$this->_view->year.'-'.$mismon]+0,2)."</td>";	
											}
										}	
										$splithtml .="<td style='width:40px !important;word-wrap:break-all !important;' align='right'>".zero_cut($clientdetails[$key]['turnover']+0,2)." &".$contractfullloop['sales_suggested_currency'].";</td>";
										$splithtml .="</tr>";
									}
									$i++;		
								} //mission foreach
					}//contract foreach
			}//client foreach
			$splithtml .='</tbody>';
			$splithtml .="</table>";
			$split_name=$time."-splitmonth.xlsx";
			$splitpath=$_SERVER['DOCUMENT_ROOT']."/BO/turnover-report/$split_name";
			if($splithtml!="" && $request['download']!='pdf')
					{
						chmod($splitpath,0777);
						convertHtmltableToXlsx($splithtml,$splitpath,True);
					}
	if($request['download']=='pdf')
					{
					ini_set('max_execution_time','300');
					ini_set('memory_limit','512M');
						$f_name=$time."-realmonth.pdf";
						$path=$_SERVER['DOCUMENT_ROOT']."/BO/turnover-report/$f_name";
						chmod($path,0777);
						$this->htmltopdf($tablehtml,$path);
											
						header('Content-Description: File Transfer');
						header('Content-Type: application/octet-stream');
						header("Content-Type: application/force-download");
						header('Content-Disposition: attachment; filename=' . urlencode(basename($path)));
						// header('Content-Transfer-Encoding: binary');
						header('Expires: 0');
						header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
						header('Pragma: public');
						header('Content-Length: ' . filesize($path));
						ob_clean();
						flush();
						readfile($path);
					}
					else
					{
						
						$f_name=$time."-realmonth.xlsx";
						$path=$_SERVER['DOCUMENT_ROOT']."/BO/turnover-report/$f_name";
						chmod($path,0777);
						convertHtmltableToXlsx($tablehtml,$path,True);
						//$this->_redirect("/BO/turnover-report/$f_name");
						$this->combinesheet($compare_name,$f_name,$split_name,$time);
						$contractsheet="contract-sheet-".$time.'.xlsx';
						$this->_redirect("/BO/download-turnover-report.php?type=turnover&filename=$contractsheet");
					} 
	
	
	}
	
	
	
	
	//pdf file generation
	function htmltopdf($html,$file_path)
	{
				require_once(APP_PATH_ROOT.'dompdf/dompdf_config.inc.php');
				if ( get_magic_quotes_gpc() )
				 $html = stripslashes($html);
				  
				//echo $html;exit;
				$dompdf = new DOMPDF();
				$dompdf->load_html( $html);
				$dompdf->set_paper("a4", 'landscape');
				$dompdf->render();
				//$dompdf->stream($file_name.".pdf");
				$pdf = $dompdf->output();
				file_put_contents($file_path, $pdf);	
	}
	
	
	function combinesheet($file_name1,$file_name2,$file_name3,$time)
	{
		require_once APP_PATH_ROOT.'nlibrary/tools/PHPExcel.php';
			$objPHPExcel1 = PHPExcel_IOFactory::load($_SERVER['DOCUMENT_ROOT']."/BO/turnover-report/".$file_name1);
			$sheet1 = $objPHPExcel1->getActiveSheet()->setTitle('compare-sheet');
			$objPHPExcel2 = PHPExcel_IOFactory::load($_SERVER['DOCUMENT_ROOT']."/BO/turnover-report/".$file_name2);					
			$sheet = $objPHPExcel2->getActiveSheet()->setTitle('real-turnover');
			$objPHPExcel3 = PHPExcel_IOFactory::load($_SERVER['DOCUMENT_ROOT']."/BO/turnover-report/".$file_name3);					
			$sheet2 = $objPHPExcel3->getActiveSheet()->setTitle('split-month-turnover');
			$objPHPExcel3->addExternalSheet($sheet);
			$objPHPExcel3->addExternalSheet($sheet1);
			$writer = PHPExcel_IOFactory::createWriter($objPHPExcel3, "Excel2007");
			$file_creation_date = $time;
			// name of file, which needs to be attached during email sending
			$saving_name = "contract-sheet-". $file_creation_date.'.xlsx';
			// save file at some random location
			$writer->save($_SERVER['DOCUMENT_ROOT']."/BO/turnover-report/".$saving_name);
	}
	
	
	/**calculate based on pm**/
	function theoricalTurnoverReportAction()
	{
		
		$parmas = $this->_request->getParams();
		$turnover_obj = new Ep_Quote_Turnover();
		$client_obj = new Ep_Quote_Client();
		$parmas['pm']='true';	
		//$client_details_obj = new Ep_User_BoUser();	
			
		//default Year selection
		if($parmas['year'])
		{
		$this->default_year=$parmas['year'];
		$this->_view->default_year=$parmas['year'];
		}
		else
		{
		$this->default_year=date("Y");
		$this->_view->default_year=date("Y");
		}	

		//setlocale(LC_TIME, "fr_FR");
		if($parmas['client_id']!="")
		{
				$searchclient['client_id']=$parmas['client_id'];

				if($parmas['sales_id']!=""){
					$searchclient['sales_id']=$parmas['sales_id'];
				}
				$quotecontractlist = $turnover_obj->getClientContractMissions($searchclient);
		}
		else
		{
			ini_set('max_execution_time','3000');
			ini_set('memory_limit','-1');
				error_reporting(E_ERROR);
			if($parmas['sales_id']!=""){
					$searchclient['sales_id']=$parmas['sales_id'];
				}
				$quotecontractlist = $turnover_obj->getClientContractMissions($searchclient);
		}
		foreach($quotecontractlist as $contractlist)
		{
			$contract['contract_id']=$contractlist['quotecontractid'];
			$contract['pm']=$parmas['pm'];
			$splitmonthturnover=$turnover_obj->getSplitTurnoversclients($contract);
			foreach($splitmonthturnover as $val)
			{
				if($val['assigned_to'])
				$assigned_user[$val['assigned_to']]=$val['first_name'].' '.$val['last_name'];
			}
			if($splitmonthturnover=="")
			{
				$Parameters['quote_id']= $contractlist['quoteid'];
				$Parameters['contract_id']= $contractlist['quotecontractid'];
				$Parameters['include_final']='yes';
				$techMissions=$turnover_obj->getTechMissionDetails($Parameters);
										
			foreach($techMissions as $techmisson)
			 {
			 	if($techmisson['assigned_to'])
			$assigned_user[$techmisson['assigned_to']]=$techmisson['first_name'].' '.$techmisson['last_name'];
			 }
			 $missionDetails=$turnover_obj->getProdSeoMissions(array('contract_id'=>$contractlist['quotecontractid']));
									 
						 foreach($missionDetails as $missoin)
						 {
						 	if($missoin['assigned_to'])
						 $assigned_user[$missoin['assigned_to']]=$missoin['first_name'].' '.$missoin['last_name'];
						 }

			}
			
		}
		
		$year_array[]=$this->default_year;
		if($parmas['end_year'])
		{
			$diff=$parmas['end_year']-$parmas['year'];
			for($y=1;$y<=$diff;$y++)
			{
			$year_array[]=$parmas['year']+$y;	
			}
			
		} 
		$c=0;
		$contract_ass=array();
		foreach($assigned_user as $ass_key=>$assign_val)
		{
			$clienttrunarray=array();
			$contract_details=array();
			foreach($quotecontractlist as $quotescon)
			{ //start
						
				$searchcontract['contract_id']=$quotescon['quotecontractid'];
				if($parmas['p_type'])
				{
				$searchcontract['name']=$this->producttype_array[$parmas['p_type']];
				}
				if($parmas['product'])
				{
									if($this->product_array[$parmas['product']])
									$searchcontract['product']=$this->product_array[$parmas['product']];
									else
									$searchcontract['product']=str_replace("_",' ',$parmas['product']);
				}
				if($parmas['pm']=='true')
				{
					$searchcontract['pm']=$parmas['pm'];
					$searchcontract['assigned_to']=$ass_key;
				}
				

				$splitmonthturnoverclient=$turnover_obj->getSplitTurnoversclients($searchcontract);
				//echo "<pre>"; print_r($splitmonthturnoverclient); exit;
									//Mission Details
										
								$totaltrunarray=$this->clientcontracttotalloop($splitmonthturnoverclient,$quotescon,$totaltrunarray);
								
								$totaltrunarray[$quotescon['client_id']]['client_name']=$quotescon['company_name'];
								$totaltrunarray[$quotescon['client_id']]['client_id']=$quotescon['client_id'];
								$totaltrunarray[$quotescon['client_id']]['client_code']=$quotescon['client_code'];
								$totaltrunarray[$quotescon['client_id']]['sales_suggested_currency']=$quotescon['sales_suggested_currency'];
								$contract_details['totalclient'] =$totaltrunarray;	
								
											   //client Details
								$salesDetails = $client_obj->getQuoteUserDetails($quotescon['sales_creator_id']);
								//Total Turn over by user
														
								//Mission Details
								if($splitmonthturnoverclient=="")
								{

									 $missionDetailsloop=$turnover_obj->getProdSeoMissions(array('contract_id'=>$quotescon['quotecontractid'],'product'=>$parmas['product'],'product_type'=>$parmas['p_type'],'assigned_to'=>$ass_key));
									 
									 $c=0;
										 foreach($missionDetailsloop as $missoin)
										 {
										 	
											$clienttrunarray[$quotescon['quotecontractid']][$missoin['qmid']]['mission_type_other']=$missoin['product_type_other'];
											if(!in_array($missoin['qmid'],$clienttrunarray[$quotescon['quotecontractid']]))
												{
												$clienttrunarray[$quotescon['quotecontractid']][$c]=$missoin['qmid'];
												$clienttrunarray[$quotescon['quotecontractid']]['client_id']= $quotescon['client_id'];
												}
											 if($missoin['product']=='translation')
												{
													
													$clienttrunarray[$quotescon['quotecontractid']][$missoin['qmid']]['mission_type'] = $this->product_array[$missoin['product']]." ".$this->producttype_array[$missoin['product_type']]." ".$this->getCustomName("EP_LANGUAGES",$missoin['language_source'])." vers ".$this->getCustomName("EP_LANGUAGES",$missoin['language_dest']);
												}
												else
												{
													$clienttrunarray[$quotescon['quotecontractid']][$missoin['qmid']]['mission_type'] = $this->product_array[$missoin['product']]." ".$this->producttype_array[$missoin['product_type']]." en ".$this->getCustomName("EP_LANGUAGES",$missoin['language_source']);
													
												}
											$c++; 
										 }
										$searchParameters['identifier']= $parmas['product'];
										$searchParameters['quote_id']= $quotescon['quoteid'];
										$searchParameters['contract_id']= $quotescon['quotecontractid'];
										$searchParameters['assigned_to']= $ass_key;
										$searchParameters['include_final']='yes';
										if($parmas['product'] && $parmas['p_type']=='') $searchParameters['title']=str_replace("_",' ',$parmas['product']);
										elseif($parmas['product']=="" && $parmas['p_type']) $searchParameters['title']=str_replace("_",' ',$parmas['p_type']);
										
										$techMissionDetails=$turnover_obj->getTechMissionDetails($searchParameters);
										
										$i=$c;
										foreach($techMissionDetails as $techmisson)
										 {
										 	
												if(!in_array($techmisson['identifier'],$clienttrunarray[$quotescon['quotecontractid']]))
												{
												$clienttrunarray[$quotescon['quotecontractid']][$i]=$techmisson['identifier'];
												$clienttrunarray[$quotescon['quotecontractid']]['client_id']= $quotescon['client_id'];
												 
												}
												$clienttrunarray[$quotescon['quotecontractid']][$techmisson['identifier']]['mission_type'] = $techmisson['title'];
																		
											$i++; 
										 }
										 
								 }	
								else
								{	
								$this->assigned_to=$ass_key;
								$clienttrunarray=$this->clientcontractmissionloop($splitmonthturnoverclient,$quotescon,$clienttrunarray,$this->default_year);
								}

				$clienttrunarray[$quotescon['quotecontractid']]['contractname'] = $quotescon['contractname'];		 
				$clienttrunarray[$quotescon['quotecontractid']]['contract_id'] = $quotescon['quotecontractid'];	
				$clienttrunarray[$quotescon['quotecontractid']]['sales_owner'] = $salesDetails[0]['first_name']." ".$salesDetails[0]['last_name'];	 
				$clienttrunarray[$quotescon['quotecontractid']]['sales_suggested_currency']=$quotescon['sales_suggested_currency'];
				//$clienttrunarray[$quotescon['client_id']]=$clienttrunarray;
				$contract_details['contract_Contrat_details'][$quotescon['client_id']]=$clienttrunarray;
		
			} //endforeach
			$contract_ass[$ass_key]=$contract_details;

		}
		//echo "<pre>";print_r($assigned_user);exit;
		foreach($assigned_user as $assigned_key=>$assign_val)
		{
			//echo "<pre>"; print_r($contract_ass[$assigned_key]);exit;
			$checkingContract=$turnover_obj->checkingContract($assigned_key);
			$checkContract=array();
			foreach($checkingContract as $val)
			{
				$checkContract['contract_id'][]=$val['contract_id'];
				$checkContract['client_id'][]=$val['client_id'];
			}
			
			$tablehtml="<table cellspacing='0' cellpadding='0' border='1' ><thead>
			<tr> 
					<th  bgcolor='#237e9f' align='center' style='width:50px'>Code</th>
					 <th style='width:100px !important;' align='center'  bgcolor='#237e9f'>Client</th>
					  <th style='width:100px !important;' align='center'  bgcolor='#237e9f'>Contract name</th>
					   <th style='width:100px !important;' align='center'  bgcolor='#237e9f'>Mission Name</th>
					   <th style='width:100px !important;' align='center'  bgcolor='#237e9f'>Mission Type</th>
					   <th style='width:100px !important;' align='center'  bgcolor='#237e9f'>Product Type</th>";
					  foreach($year_array as $value)
					  {
							foreach($this->month_array as $month)
							{
							$tablehtml .="<th  bgcolor='#237e9f' align='center' >".ucfirst(strftime("%b", mktime(null, null, null, $month))).' '.$value."</th>";	  
							$tablehtml .="<th bgcolor='#237e9f' align='center' >Margin %</th>";
							}
						 }
				//$tablehtml .="<th  bgcolor='#1fbba6' align='center' >TOTAL ".$this->default_year."</th><th  bgcolor='#1fbba6' align='center' >Margin % ".$this->default_year."</th>
						$tablehtml .="</tr></thead>";
					$tablehtml .='<tbody>';
		
					foreach($contract_ass[$assigned_key]['totalclient'] as $contractfullloop)
					{
						if(in_array($contractfullloop['client_id'],$checkContract['client_id']))
					{
									//client turnovers
						$tablehtml .="<tr>";
						$tablehtml .="<td><b>".$contractfullloop['client_code']."</b></td>";
						$tablehtml .="<td color='#267cff'><b>".$contractfullloop['client_name']."</b></td>";
						$tablehtml .="<td></td><td></td><td></td><td></td>";
							foreach($contract_ass[$assigned_key]['contract_Contrat_details'][$contractfullloop['client_id']] as $contractclienttotal)
							{
								//echo "<pre>"; print_r($contractclienttotal); exit;
								if($contractfullloop['client_id']==$contractclienttotal['client_id'] && (in_array($contractclienttotal['contract_id'], $checkContract['contract_id'])))
								{
						foreach($year_array as $value)
							  {
										foreach($this->month_array as $conmon)
								{
											$contractfullloop['expected-'.$value.'-'.$conmon]+=$contractclienttotal[$value.'-'.$conmon];
								 			 $contractfullloop['cost'.$value.'-'.$conmon]+=$contractclienttotal['totalcost'.$value.'-'.$conmon];
										}	
									}

										}
							}
							//echo "<pre>"; print_r($contractfullloop); exit;
							foreach($year_array as $value)
										{
									foreach($this->month_array as $clval)
									{	
											$tablehtml .="<td align='right' style='width:40px !important;word-wrap:break-all !important;'>".zero_cut($contractfullloop['expected-'.$value.'-'.$clval],2)."</td>";
											$margin_total=$contractfullloop['expected-'.$value.'-'.$clval]-$contractfullloop['cost'.$value.'-'.$clval];
											$tablehtml .="<td align='right' style='width:40px !important;word-wrap:break-all !important;'>".zero_cut(($margin_total/$contractfullloop['expected-'.$value.'-'.$clval])*100,2)."%</td>";
									
								}	
							}
					
						//$tablehtml .="<td style='width:50px !important;word-wrap:break-all !important;' align='right'><b>".zero_cut($contractfullloop['turnover']+0)." &".$contractfullloop['sales_suggested_currency'].";</b></td>";
						//$tablehtml .="<td align='right' style='width:40px !important;word-wrap:break-all !important;'>".zero_cut($contractfullloop['margin_percentage']+0)."%</td>";
						$tablehtml .="</tr>";
			
						//client contract mission
						
						foreach($contract_ass[$assigned_key]['contract_Contrat_details'][$contractfullloop['client_id']] as $clientdetails)
						{
				
								if($contractfullloop['client_id']==$clientdetails['client_id'] && (in_array($clientdetails['contract_id'], $checkContract['contract_id'])))
							{
									//echo '<pre>'; print_r($clientdetails); exit;
								$tablehtml .="<tr>";
								$tablehtml .="<td></td><td></td>";
								$tablehtml .="<td color='#267cff'><b>".$clientdetails['contractname']."</b></td><td></td><td></td><td></td>";
							
								foreach($year_array as $value)
						  		{
									foreach($this->month_array as $conmon)
									{
																								
												$tablehtml .="<td align='right' style='width:40px !important;word-wrap:break-all !important;'>".zero_cut($clientdetails[$value.'-'.$conmon]+0)."</td>";
														$margincontract=$clientdetails[$value.'-'.$conmon]-$clientdetails['totalcost'.$value.'-'.$conmon];
														$tablehtml .="<td align='right' style='width:40px !important;word-wrap:break-all !important;'>".zero_cut(($margincontract/$clientdetails[$value.'-'.$conmon])*100,2)."%</td>";
												
											
									}	
								}
							
								//$tablehtml .="<td style='width:50px !important;word-wrap:break-all !important;' align='right'>".zero_cut($clientdetails['turnover']+0)." &".$contractfullloop['sales_suggested_currency'].";</td>";
								//$tablehtml .="<td align='right' style='width:50px !important;word-wrap:break-all !important;'>".zero_cut($clientdetails['margin_percentage']+0)."%</td>";
								$tablehtml .="</tr>";
						
								//mission details
								foreach($clientdetails as $key=>$missiondetails)
									{							
										
										if(is_array($clientdetails[$key]) && $clientdetails[$key]['assigned_to']) 
										{			
											$tablehtml .="<tr>";
											$tablehtml .="<td></td><td></td><td></td>";
											$tablehtml .="<td color='#267cff'>".$clientdetails[$key]['mission_type']." ".htmlentities($clientdetails[$key]['mission_type_other'])."</td>";
											$tablehtml .="<td color='#267cff'>".$clientdetails[$key]['product']."</td>";
											$tablehtml .="<td color='#267cff'>".$clientdetails[$key]['product_type']."</td>";
											foreach($year_array as $value)
						  					{
												foreach($this->month_array as $mismon)
												{
													
													$tablehtml .="<td align='right' style='width:40px !important;word-wrap:break-all !important;'>".zero_cut($clientdetails[$key][$value.'-'.$mismon]+0)."</td>";	
													$margin=$clientdetails[$key][$value.'-'.$mismon]-$clientdetails[$key]['cost-'.$value.'-'.$mismon];
													$tablehtml .="<td align='right' style='width:40px !important;word-wrap:break-all !important;'>".zero_cut(($margin/$clientdetails[$key][$value.'-'.$mismon])*100,2)."%</td>";
												
												}	
											}
												
											//$tablehtml .="<td style='width:40px !important;word-wrap:break-all !important;' align='right'>".zero_cut($clientdetails[$key]['turnover']+0)." &".$contractfullloop['sales_suggested_currency'].";</td>";
											//$tablehtml .="<td align='right' style='width:40px !important;word-wrap:break-all !important;'>".zero_cut($clientdetails[$key]['margin_percentage']+0)."%</td>";//
											$tablehtml .="</tr>";
										}	
									
									
										$i++;		
									} //mission foreach
							}
						}//contract foreach
						}	
					}//client foreach
			$tablehtml .='</tbody>';
			$tablehtml .="</table>";
			
			//echo $tablehtml."$assign_val--------------<br>";
					$assigned_name="PM-$assigned_key-$assign_val-theorical-turnover-".$parmas['year'].'-'.$parmas['end_year'].".xlsx";
					//$assigned_name=frenchCharsToEnglish($assigned_name);
					$assigned_path=$_SERVER['DOCUMENT_ROOT']."/BO/turnover-report/pm-theorical-report/$assigned_name";
					chmod($assigned_path,0777);					
					//convertHtmltableToXlsx($tablehtml,$assigned_path,True);
					$assigned_name_html="PM-$assigned_key-$assign_val-theorical-turnover-".$parmas['year'].'-'.$parmas['end_year'].".html";
					//$assigned_name_html=frenchCharsToEnglish($assigned_name_html);
					$assigned_path_html=$_SERVER['DOCUMENT_ROOT']."/BO/turnover-report/pm-theorical-report/$assigned_name_html";
					$fh = fopen($assigned_path_html, 'wb'); // or die("error");  					
					fwrite($fh, $tablehtml);
					fclose($fh);
					
			}
			//exit;
									
					//$this->combinesheetmultiple($assigned_user,$parmas['year'],$parmas['end_year']);
						
	}
	function theoricalHtmltoXlsxAction()
	{
		$path = $_SERVER['DOCUMENT_ROOT']."/BO/turnover-report/pm-theorical-report/";

		if ($handle = opendir($path)) {
			while (false !== ($file = readdir($handle))) {
				if ('.' === $file) continue;
				if ('..' === $file) continue;

				// do something with the filee
				$file_details=pathinfo($file);
				$file_path=$path.$file;
				
				$excel_name=$file_details['filename'].".xlsx";	
				$excel_file_path=$path.$excel_name;			
				
				if(!file_exists($excel_file_path))
				{					
					$htmlTable=file_get_contents($file_path);
					//echo $file_path."--".$htmlTable;exit;					
					convertHtmltableToXlsx($htmlTable,$excel_file_path,True);
				}
				
			}
			closedir($handle);
		}
	}
	function realHtmltoXlsxAction()
	{
		$path = $_SERVER['DOCUMENT_ROOT']."/BO/turnover-report/pm-real-report/";

		if ($handle = opendir($path)) {
			while (false !== ($file = readdir($handle))) {
				if ('.' === $file) continue;
				if ('..' === $file) continue;

				// do something with the filee
				$file_details=pathinfo($file);
				$file_path=$path.$file;
				
				$excel_name=$file_details['filename'].".xlsx";	
				$excel_file_path=$path.$excel_name;			
				//echo $file_path."--".$htmlTable;exit;
				if(!file_exists($excel_file_path))
				{					
					$htmlTable=file_get_contents($file_path);
					convertHtmltableToXlsx($htmlTable,$excel_file_path,True);
				}
				
			}
			closedir($handle);
		}
	}

	function combinesheetmultiple($assigned_user=array(),$year,$end_year="")
	{
		
		require_once APP_PATH_ROOT.'nlibrary/tools/PHPExcel.php';
		
		foreach($assigned_user as $key=>$assigned_val)
		{
			$i=1;
			if($end_year!="")
			{


					if($i==1)
					{
						$objPHPExcel1 = PHPExcel_IOFactory::load($_SERVER['DOCUMENT_ROOT']."/BO/turnover-report/pm-report/PM-".$key."-theorical-turnover-".$year.'-'.$end_year.".xlsx");
						$sheet1 = $objPHPExcel1->getActiveSheet()->setTitle(substr($assigned_val,0,30));		
					}
					else
					{
					$obj_name='objPHPExcel'.$i;
					$sheet_obj='sheet'.$i;
					$$obj_name = PHPExcel_IOFactory::load($_SERVER['DOCUMENT_ROOT']."/BO/turnover-report/pm-report/PM-".$key."-theorical-turnover-".$year.'-'.$end_year.".xlsx");
					$$sheet_obj = $$obj_name->getActiveSheet()->setTitle(substr($assigned_val,0,30));
					$objPHPExcel1->addExternalSheet($$sheet_obj);
					}
			}
			else
			{
				if($i==1)
					{
						$objPHPExcel1 = PHPExcel_IOFactory::load($_SERVER['DOCUMENT_ROOT']."/BO/turnover-report/pm-report/PM-".$key."-real-turnover-".$year.".xlsx");
						$sheet1 = $objPHPExcel1->getActiveSheet()->setTitle(substr($assigned_val,0,30));		
					}
					else
					{					
					$obj_name='objPHPExcel'.$i;
					$sheet_obj='sheet'.$i;
					$$obj_name = PHPExcel_IOFactory::load($_SERVER['DOCUMENT_ROOT']."/BO/turnover-report/pm-report/PM-".$key."-real-turnover-".$year.".xlsx");
					$$sheet_obj = $$obj_name->getActiveSheet()->setTitle(substr($assigned_val,0,30));
					$objPHPExcel1->addExternalSheet($$sheet_obj);
					}

			}
			

			$i++;
			
			
		}
			
			$writer = PHPExcel_IOFactory::createWriter($objPHPExcel1, "Excel2007");
			if($end_year)
			$file_creation_date = 'theorocal-turnover-'.$year.'-'.$end_year;
			else
			$file_creation_date = 'real-turnover-'.$year;	
			// name of file, which needs to be attached during email sending
			$saving_name = "pm-sheet-". $file_creation_date.'.xlsx';

			// save file at some random location
			$writer->save($_SERVER['DOCUMENT_ROOT']."/BO/turnover-report/".$saving_name);

			//$this->_redirect($_SERVER['DOCUMENT_ROOT']."/BO/turnover-report/".$saving_name);
				echo "success"; 

	}

	

	/**Real Pm calculation**/
	/**calculate based on pm**/
	function realPmTurnoverReportAction()
	{

		$request = $this->_request->getParams();
		$turnover_obj = new Ep_Quote_Turnover();
		$contract_obj = new Ep_Quote_Quotecontract();
		$client_obj = new Ep_Quote_Client();	
		if($request['year'])
		{
			$this->_view->year = $request['year'];
			$this->default_year=$request['year'];
		$this->_view->default_year=$request['year'];
		}
		else
		{
		$request['year'] = $this->_view->year = date("Y");
		$this->default_year=date("Y");
		$this->_view->default_year=date("Y");
		}
		$this->_view->client = $request['client'];

		ini_set('max_execution_time','0');
		ini_set('memory_limit','-1');
		$realturnovers = $turnover_obj->getRealTurnovers($request);
		foreach($realturnovers as $real)
		{
			$quotecontract_obj = new Ep_Quote_Quotecontract();
			$contractsreal = $quotecontract_obj->getContracts(array('client_id'=>$real['user_id'],'not_mulitple_status'=>"'deleted'",'sales_id'=>$request['sales_id']));
			foreach($contractsreal as $contract)
			{
				$cmissiondetailsreal = $turnover_obj->getProdSeoMissions(array('contract_id'=>$contract['quotecontractid'],'product'=>$request['product'],'product_type'=>$request['p_type'],'assigned_to'=>$request['assigned_to']));
				foreach($cmissiondetailsreal as $contract_mission)
				{
			if($contract_mission['assigned_to']){
				$assigned_user[$contract_mission['assigned_to']] = $contract_mission['first_name']." ".$contract_mission['last_name'];
					$contractChecking['contract_id'][$contract_mission['assigned_to']][]=$contract['quotecontractid'];
					$contractChecking['client_id'][$contract_mission['assigned_to']][]=$real['user_id'];
				}
				}
			}	
		}
		
		$realturnoversseo = $turnover_obj->getRealSeoMission($request);
		foreach($realturnoversseo as $real)
		{
			$quotecontract_obj = new Ep_Quote_Quotecontract();
			$contractsreal = $quotecontract_obj->getContracts(array('client_id'=>$real['user_id'],'not_mulitple_status'=>"'deleted'",'sales_id'=>$request['sales_id']));
			foreach($contractsreal as $contract)
			{
				$cmissiondetailsreal = $turnover_obj->getProdSeoMissions(array('contract_id'=>$contract['quotecontractid'],'product'=>$request['product'],'product_type'=>$request['p_type'],'assigned_to'=>$request['assigned_to']));
				foreach($cmissiondetailsreal as $contract_mission)
				{
				if($contract_mission['assigned_to']){
				$assigned_user[$contract_mission['assigned_to']] = $contract_mission['first_name']." ".$contract_mission['last_name'];
					$contractChecking['contract_id'][$contract_mission['assigned_to']][]=$contract['quotecontractid'];
					$contractChecking['client_id'][$contract_mission['assigned_to']][]=$real['user_id'];
					}
				}
			}	
		}
		
		$realturnoverstech= $turnover_obj->getRealTechMission($request);
		foreach($realturnoverstech as $real)
		{
			$quotecontract_obj = new Ep_Quote_Quotecontract();
			$contractsreal = $quotecontract_obj->getContracts(array('client_id'=>$real['user_id'],'not_mulitple_status'=>"'deleted'",'sales_id'=>$request['sales_id']));
			foreach($contractsreal as $contract)
			{
				$searchParameters = array();
				$searchParameters['quote_id']= $contract['quoteid'];
				$searchParameters['contract_id']= $contract['quotecontractid'];
				$searchParameters['include_final']='yes';
							
				$techMissionDetails=$turnover_obj->getTechMissionDetails($searchParameters);
				foreach($cmissiondetailsreal as $contract_mission)
				{
				if($contract_mission['assigned_to']){
				$assigned_user[$contract_mission['assigned_to']] = $contract_mission['first_name']." ".$contract_mission['last_name'];
					$contractChecking['contract_id'][$contract_mission['assigned_to']][]=$contract['quotecontractid'];
					$contractChecking['client_id'][$contract_mission['assigned_to']][]=$real['user_id'];
					}	
				}
			}	
		}
			//echo '<pre>'; print_r($assigned_user); exit;
			//$realturnovers = $turnover_obj->getRealTechMission($request);
			//echo "<pre>"; print_r($realturnovers);	exit;
		$mergerarray1=array_merge($realturnoverstech,$realturnovers);
		$realarraymerge=array_merge($realturnoversseo,$mergerarray1);

			foreach($assigned_user as $assigned_key=>$assigned_val)
			{
					foreach($realarraymerge as $realturnover)
					{
					
						$monthturnoversre[$realturnover['user_id']][$realturnover['yearmonth']] = $realturnover['publishedprice'];
						$monthturnoversre[$realturnover['user_id']]['other_info'] = $realturnover;
						
						$quotecontract_obj = new Ep_Quote_Quotecontract();
						$contracts = $quotecontract_obj->getContracts(array('client_id'=>$realturnover['user_id'],'not_mulitple_status'=>"'deleted'",'sales_id'=>$request['sales_id']));
						
						foreach($contracts as $contract)
						{
						$contractturnover[$realturnover['user_id']][$contract['quotecontractid']]['contract_details'] = $contract;
						
						/* Real turnover and missions */
						if($assigned_key)
						{
							$assigned_toval=$assigned_key;
						}
						else
						{
							$assigned_toval=$request['assigned_to'];
						}
						$cmissiondetails = $turnover_obj->getProdSeoMissions(array('contract_id'=>$contract['quotecontractid'],'product'=>$request['product'],'product_type'=>$request['p_type'],'assigned_to'=>$assigned_toval));
						foreach($cmissiondetails as $contract_mission)
						{
							$contractturnover[$realturnover['user_id']][$contract['quotecontractid']][$contract_mission['qmid']] = $contract_mission;
							$contractturnover[$realturnover['user_id']][$contract['quotecontractid']][$contract_mission['qmid']]['title_other']=$contract_mission['product_type_other'];
							
							if($contract_mission['product']=='translation')
							{
								$contractturnover[$realturnover['user_id']][$contract['quotecontractid']][$contract_mission['qmid']]['title'] = $this->product_array[$contract_mission['product']]." ".$this->producttype_array[$contract_mission['product_type']]." ".$this->getCustomName("EP_LANGUAGES",$contract_mission['language_source'])." vers ".$this->getCustomName("EP_LANGUAGES",$contract_mission['language_dest']);
								$contractturnover[$realturnover['user_id']][$contract['quotecontractid']][$contract_mission['qmid']]['mission_type']=$this->product_array[$contract_mission['product']];
								$contractturnover[$realturnover['user_id']][$contract['quotecontractid']][$contract_mission['qmid']]['prod_type']=$this->producttype_array[$contract_mission['product_type']];
							}
							else
							{
								$contractturnover[$realturnover['user_id']][$contract['quotecontractid']][$contract_mission['qmid']]['title'] = $this->product_array[$contract_mission['product']]." ".$this->producttype_array[$contract_mission['product_type']]." en ".$this->getCustomName("EP_LANGUAGES",$contract_mission['language_source']);
								$contractturnover[$realturnover['user_id']][$contract['quotecontractid']][$contract_mission['qmid']]['mission_type']=$this->product_array[$contract_mission['product']];
								$contractturnover[$realturnover['user_id']][$contract['quotecontractid']][$contract_mission['qmid']]['prod_type']=$this->producttype_array[$contract_mission['product_type']];
								$array['lang'] = $this->getCustomName("EP_LANGUAGES",$contract_mission['language_source']);
							}
							if($contract_mission['contractmissionid'] && $contract_mission['type']=="prod" &&  $contract_mission['assigned_to']==$assigned_key)
							{
								$years_mission = $turnover_obj->getRealTurnovers(array('contract_mission_id'=>$contract_mission['contractmissionid'],'year'=>$this->_view->year));
								foreach($years_mission as $yearm)
								{
									$real_price = $contract_mission['unit_price']*$yearm['total_packs'];
									$contractturnover[$realturnover['user_id']][$contract['quotecontractid']][$contract_mission['qmid']][$yearm['yearmonth']]  = $yearm['publishedprice'];
									$contractturnover[$realturnover['user_id']][$contract['quotecontractid']][$contract_mission['qmid']]['real_'.$yearm['yearmonth']]  = $real_price;
									$contractturnover[$realturnover['user_id']][$contract['quotecontractid']]['contract_details'][$yearm['yearmonth']] +=  $yearm['publishedprice'];
									$contractturnover[$realturnover['user_id']][$contract['quotecontractid']]['contract_details']['realcostturnover'] += $yearm['publishedprice'];
									$contractturnover[$realturnover['user_id']][$contract['quotecontractid']]['contract_details']['realturnover_'.$yearm['yearmonth']] += $real_price;
									$canvas_real[$yearm['yearmonth']] += $yearm['publishedprice'];
								}
							}
							else
							{
								if($this->_view->year==$contract_mission['validatedyear'] &&  $contract_mission['assigned_to']==$assigned_key)
								{
									/*$contractturnover[$realturnover['user_id']][$contract['quotecontractid']][$contract_mission['qmid']][$contract_mission['validatedyearmonth']]  = $contract_mission['missionturnover'];
									$contractturnover[$realturnover['user_id']][$contract['quotecontractid']]['contract_details'][$contract_mission['validatedyearmonth']] += $contract_mission['missionturnover'];*/
									$contractturnover[$realturnover['user_id']][$contract['quotecontractid']][$contract_mission['qmid']]['real_'.$contract_mission['validatedyearmonth']]  = $contract_mission['missionturnover'];
									$contractturnover[$realturnover['user_id']][$contract['quotecontractid']][$contract_mission['qmid']]['realturnover'] += $contract_mission['missionturnover'];
									$contractturnover[$realturnover['user_id']][$contract['quotecontractid']]['contract_details']['realturnover_'.$contract_mission['validatedyearmonth']] += $contract_mission['missionturnover'];
									$canvas_real[$contract_mission['validatedyearmonth']] +=  $contract_mission['missionturnover'];
								}
								$contractturnover[$realturnover['user_id']][$contract['quotecontractid']][$contract_mission['qmid']]['type']  = "seo";
							}
							/* Check edited and deleted */
							if($contract_mission['is_edited'])
							{
								$updated_at = date("Y-m",strtotime($contract_mission['updated_at']));
								$contractturnover[$realturnover['user_id']][$contract['quotecontractid']][$contract_mission['qmid']]['edited_at'.$updated_at]  ="missionEdited" ;
							} 
							if($contract_mission['cm_status']=="deleted")
							{
								$updated_at = date("Y-m",strtotime($contract_mission['cmupdated_at']));
								$contractturnover[$realturnover['user_id']][$contract['quotecontractid']][$contract_mission['qmid']]['deleted_at'.$updated_at]  ="missionDeleted" ;
							}
							if($contract_mission['year_freeze_start_date']==$request['year'] || $contract_mission['year_freeze_end_date']==$request['year'])
							{
								$contract_mission['freeze_start_date']." ".$contract_mission['freeze_end_date']."<br>";
								$date1  = $contract_mission['freeze_start_date'];
								$date2  = $contract_mission['freeze_end_date'];
								$output = array();
								$time   = strtotime($date1);
								$last   = date('m-Y', strtotime($date2));

								do 
								{
									$month = date('m-Y', $time);
									$month_year = date('Y-m', $time);
									
								$contractturnover[$realturnover['user_id']][$contract['quotecontractid']][$contract_mission['qmid']]['freezed_at'.$month_year] = "missionFreezed";

									$time = strtotime('+1 month', $time);
								} while ($month != $last);
								
								
							}
						}
						
						/* To get tech missions and turnover */
						$searchParameters = array();
						$searchParameters['quote_id']= $contract['quoteid'];
						$searchParameters['contract_id']= $contract['quotecontractid'];
						$searchParameters['include_final']='yes';
						if($request['assigned_to'])
						$searchParameters['assigned_to']=$request['assigned_to'];
						//else
						//$searchParameters['assigned_to']=$assigned_key;

						if($request['product']) $searchParameters['product']=str_replace("_",' ',$request['product']);
						elseif($request['p_type']) $searchParameters['p_type']=str_replace("_",' ',$request['p_type']);
					
						$techMissionDetails=$turnover_obj->getTechMissionDetails($searchParameters);
						
						foreach($techMissionDetails as $tech_mission)
						{
							if($this->_view->year==$tech_mission['validatedyear'] && $techMissionDetails['assigned_to']==$assigned_key)
							{
								$contractturnover[$realturnover['user_id']][$contract['quotecontractid']][$tech_mission['identifier']][$tech_mission['validatedyearmonth']]  = $tech_mission['turnover'];
								$contractturnover[$realturnover['user_id']][$contract['quotecontractid']]['contract_details'][$tech_mission['validatedyearmonth']] += $tech_mission['turnover'];
								$canvas_real[$tech_mission['validatedyearmonth']] +=  $tech_mission['turnover'];
								$contractturnover[$realturnover['user_id']][$contract['quotecontractid']][$tech_mission['identifier']]['real_'.$tech_mission['validatedyearmonth']]  = $tech_mission['turnover'];
								$contractturnover[$realturnover['user_id']][$contract['quotecontractid']]['real_'.$tech_mission['validatedyearmonth']] += $tech_mission['turnover'];
								$contractturnover[$realturnover['user_id']][$contract['quotecontractid']]['contract_details']['realturnover'] += $tech_mission['turnover'];
								$contractturnover[$realturnover['user_id']][$contract['quotecontractid']]['contract_details']['realturnover_'.$tech_mission['validatedyearmonth']] += $tech_mission['turnover'];
							}
							$contractturnover[$realturnover['user_id']][$contract['quotecontractid']][$tech_mission['identifier']]['type']  = "tech";
							$contractturnover[$realturnover['user_id']][$contract['quotecontractid']][$tech_mission['identifier']]['title'] = $tech_mission['title'];
						}
						
						/* To get expected turnover by Naveen */
						$searchcontract['contract_id']=$contract['quotecontractid'];
						if($request['p_type'])
						{
						$searchcontract['name']=$this->producttype_array[$request['p_type']];
						}
						if($request['product'])
						{
						$searchcontract['product']=$this->product_array[$request['product']];
						}
						$searchcontract['pm']=$request['pm'];
						if($request['assigned_to'])
						$searchcontract['assigned_to']=$request['assigned_to'];
						else
						$searchcontract['assigned_to']=$assigned_key;
						$splitmonthturnoverclient=$turnover_obj->getSplitTurnoversclients($searchcontract);
						//echo "<pre>"; print_r($splitmonthturnoverclient); exit;
						$clienttrunarray = array();
						$this->assigned_to=$assigned_key;
						$clienttrunarray=$this->clientcontractmissionloop($splitmonthturnoverclient,$contract,$clienttrunarray,$this->_view->year);
					 					
							foreach($clienttrunarray as $contractarray => $cvalue)
							{
								foreach($cvalue as $key => $value)
								{
									if(is_array($value))
									{
										foreach($value as $mission => $mvalue)
										{
											$contractturnover[$realturnover['user_id']][$contract['quotecontractid']][$key]['expected_'.$mission] = $mvalue;
										}
									}
									else
									{
										$contractturnover[$realturnover['user_id']][$contract['quotecontractid']]['contract_details']['expected_'.$key] = $value;
										if($key=='turnover') $monthturnoversre[$realturnover['user_id']]['other_info']['expected_'.$key]+=$value;
										
										
									}

									if(strpos($key,"-"))
									{
										$canvas_expected[$key] += $value;
									}

								}
							}
						}
					}
					//echo "<pre>"; print_r($contractturnover); exit;
				$monthturnoversre[$assigned_key]=$monthturnoversre;
				$contractturnover[$assigned_key]=$contractturnover;
				//echo "asdasdadsasdsad <br >"; print_r($contractturnover[$assigned_key]);  exit;
			}
		
		//echo "asdasd<pre>";  print_r($contractturnover); exit;
		foreach($assigned_user as $assigned_key=>$assigned_value)
		{
			//echo "<pre>"; print_r($contractturnover['140774945625883']); exit;
		$tablehtml="<table cellspacing='0' cellpadding='0' border='1' ><thead> 
				<tr> 	<th  bgcolor='#237e9f' align='center' style='width:50px'>Code</th>
					 <th style='width:100px !important;' align='center'  bgcolor='#237e9f'>Client</th>
					<th style='width:100px !important;' align='center'  bgcolor='#237e9f'>Contract name</th>
					   <th style='width:100px !important;' align='center'  bgcolor='#237e9f'>Mission Name</th>
					   <th style='width:100px !important;' align='center'  bgcolor='#237e9f'>Mission Type</th>
					   <th style='width:100px !important;' align='center'  bgcolor='#237e9f'>Product Type</th>";
						 
					 foreach($this->month_array as $month)
					 {
					$tablehtml .="<th  bgcolor='#237e9f' align='center' >".ucfirst(strftime("%b", mktime(null, null, null, $month))).' '.$this->_view->year."</th>";	  
						$tablehtml .="<th  bgcolor='#237e9f' align='center' >Margin</th>";	  
					}
	/*	$tablehtml .="<th  bgcolor='#1fbba6' align='center' >TOTAL ".$this->_view->year." till now</th>
					<th  bgcolor='#1fbba6' align='center' >TOTAL ".$this->_view->year." Expected</th>
					<th  bgcolor='#1fbba6' align='center' >TOTAL Cost</th>
					<th  bgcolor='#1fbba6' align='center' >Margin</th>*/
		$tablehtml .="</tr></thead>";
		
			
		
		foreach($monthturnoversre[$assigned_key] as $clientDetails)
		{
			$client_id=$clientDetails['other_info']['user_id'];
			//echo "<pre>"; print_r($contractturnover[$assigned_key][$client_id]); 	exit;
			if(in_array($client_id,$contractChecking['client_id'][$assigned_key]))
			{
			//echo "<pre>"; print_r($contractturnover[$assigned_key][$client_id]); exit;
				foreach($contractturnover[$assigned_key][$client_id] as $contracttotal)
					{
						//echo "<pre>"; print_r($contracttotal); 
						if(in_array($contracttotal['contract_details']['quotecontractid'],$contractChecking['contract_id'][$assigned_key]))
						{
						foreach($this->month_array as $monthreal)
						 {
								  $client[$assigned_key][$client_id]['clirealtotal'.$this->_view->year.'-'.$monthreal]+=$contracttotal['contract_details']['realturnover_'.$this->_view->year.'-'.$monthreal];
								  $client[$assigned_key][$client_id]['cliexpected_'.$this->_view->year.'-'.$monthreal]+=$contracttotal['contract_details'][$this->_view->year.'-'.$monthreal];
								}
						}
					}
					if($clientDetails['other_info']['company_name']!="")
						{
					$tablehtml .="<tr>";
					$tablehtml .="<td>".$clientDetails['other_info']['client_code']."</td>";
					$tablehtml .="<td>".$clientDetails['other_info']['company_name']."</td><td></td>";
					$tablehtml .="<td></td>";
					$tablehtml .="<td></td>";
					$tablehtml .="<td></td>";
					$total=0;
					$totalexpected=0;
					$totalcosttotal=0;
					 foreach($this->month_array as $monthreal)
					 {
						$totalexpected=$client[$client_id][$assigned_key]['cliexpected_'.$this->_view->year.'-'.$monthreal]+$totalexpected;
						 $tablehtml .="<td>".zero_cut($client[$assigned_key][$client_id]['clirealtotal'.$this->_view->year.'-'.$monthreal],2)."</td>";
						
						
												
						$margin_clint=$client[$assigned_key][$client_id]['clirealtotal'.$this->_view->year.'-'.$monthreal]-$client[$assigned_key][$client_id]['cliexpected_'.$this->_view->year.'-'.$monthreal];
							$tablehtml .="<td>".zero_cut(($margin_clint/$client[$assigned_key][$client_id]['clirealtotal'.$this->_view->year.'-'.$monthreal])*100,2)."%</td>";
						
					 }
					
					$tablehtml .="</tr>";
					
					
					}
					foreach($contractturnover[$assigned_key][$client_id] as $contract)
					{
				
						foreach ($contract as $missionkey=>$mission)
						{
							if ($missionkey=="contract_details" && in_array($mission['quotecontractid'],$contractChecking['contract_id'][$assigned_key]) )
							{
								$tablehtml .="<tr>";
							$tablehtml .="<td></td><td></td>";
							$tablehtml .="<td><b>".$mission['contractname']."</b></td><td></td>";
							//$tablehtml .="<td><b>".$mission['first_name'].'&nbsp;'.$mission['last_name']."</b></td>";
							$tablehtml .="<td></td>";
							$tablehtml .="<td></td>";
							$miscon=0;
							$concosttotal=0;
							foreach($this->month_array as $monthcon)
							 {
								 
								 	
								$tablehtml .="<td><b>".zero_cut($mission['realturnover_'.$this->_view->year.'-'.$monthcon],2)."</b></td>";
								
								$margin_cost=$mission['realturnover_'.$this->_view->year.'-'.$monthcon]-$mission[$this->_view->year.'-'.$monthcon];
								
								
									$tablehtml .="<td><b>".zero_cut(($margin_cost/$mission['realturnover_'.$this->_view->year.'-'.$monthcon])*100,2)."%</b></td>";
								
								
								
							 }
							
							$tablehtml .="</tr>";
						}
						else
						{
							if($mission['assigned_to']==$assigned_key && $mission['assigned_to']!='')
							{
								//echo "<pre>"; print_r($mission); exit;
									$bgcolor="";
										if($mission['from_contract']=='1') $bgcolor="bgcolor='#ff0000'";
										$tablehtml .="<tr>";
										$tablehtml .="<td $bgcolor></td><td $bgcolor></td><td $bgcolor></td>";
										$tablehtml .="<td $bgcolor>".$mission['title']." ".htmlentities($mission['title_other'])."</td>";
										$tablehtml .="<td $bgcolor>".$mission['mission_type']."</td>";
										$tablehtml .="<td $bgcolor>".$mission['prod_type']."</td>";
										$mistotal=0;
										$miscosttotal=0;
										foreach($this->month_array as $monmis)
										 {
											 $inside="";
											 $month_yearval=$this->_view->year.'-'.$monmis;
											 if($mission['edited_at'.$month_yearval])	 $inside="bgcolor='#5be7a9'";
											 elseif($mission['deleted_at'.$month_yearval]) $inside="bgcolor='#f39c12'";
											 elseif($mission['freezed_at'.$month_yearval]) $inside="bgcolor='#288fb4'";
											 
											
											 	$tablehtml .="<td $inside $bgcolor>".zero_cut($mission['real_'.$month_yearval],2)."</td>";
											
											
											
											
											$miscosttotal =$miscosttotal+$mission[$month_yearval];
											$margin=$mission['real_'.$this->_view->year.'-'.$monmis]-$mission[$month_yearval];
												$tablehtml .="<td $inside $bgcolor>".zero_cut(($margin/$mission['real_'.$month_yearval])*100,2)."%</td>";
											
										}
							
							$tablehtml .="</tr>";
							}
						}
					}
						
				}	
			}
					
		} //client details End
		$tablehtml .="</table>";

//echo $tablehtml;
		
				$assigned_name="PM-$assigned_key-$assigned_value-real-turnover-".$request['year'].".xlsx";
					//$assigned_name=frenchCharsToEnglish($assigned_name);
					$assigned_path=$_SERVER['DOCUMENT_ROOT']."/BO/turnover-report/pm-real-report/$assigned_name";
						chmod($assigned_path,0777);
							//	convertHtmltableToXlsx($tablehtml,$assigned_path,True);
					$assigned_name_html="PM-$assigned_key-$assigned_value-real-turnover-".$request['year'].".html";
					//$assigned_name_html=frenchCharsToEnglish($assigned_name_html);
					$assigned_path_html=$_SERVER['DOCUMENT_ROOT']."/BO/turnover-report/pm-real-report/$assigned_name_html";
					$fh = fopen($assigned_path_html, 'wb'); // or die("error");  					
					fwrite($fh, $tablehtml);
					fclose($fh);
	}
	//exit;
	//$this->combinesheetmultiple($assigned_user,$request['year'],'');	
							
	}
	
	
}
?>
