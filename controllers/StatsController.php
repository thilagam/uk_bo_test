<?php

/**
 * statsController - The controller class for statistics main menu
 *
 * @author
 * @version
 */
class StatsController extends Ep_Controller_Action
{
	private $text_admin;
	public function init()
	{
		parent::init();
		$this->_view->lang = $this->_lang;
		$this->adminLogin = Zend_Registry::get('adminLogin');
        $this->searchSession = Zend_Registry::get('searchSession');
        $this->sid = session_id();
        $this->commonAction();//////////including main menu and left panel content

        ////////////////////////////////////////////////////////////////////////////////
        $category=$this->_arrayDb->loadArrayv2("EP_ARTICLE_CATEGORY", $this->_lang);
        array_unshift($category, "S&eacute;lectionner");
        $this->_view->categories_array = $category;
		$nationality=$this->_arrayDb->loadArrayv2("Nationality", $this->_lang);
         array_unshift($nationality, "S&eacute;lectionner");
        for($i=-1; $i<=count($nationality); $i++)
        {
            if($i == -1)
            {
                $nationality1[-1] = "S&eacute;lectionner";
            }
            $nationality1[$i] = $nationality[$i+1];
        }  //print_r($nationality1);
        $this->_view->nationality_array = $nationality1;
        $languages=$this->_arrayDb->loadArrayv2("EP_LANGUAGES", $this->_lang);
        asort($languages);
        array_unshift($languages, "S&eacute;lectionner");
        $this->_view->languages_array = $languages;

	}

    public function contribPaymentsListAction()
	{
        $invoice_obj = new Ep_Payment_Invoice();
        $this->_view->currentdate = date("d-m-Y");
        $pay_params=$this->_request->getParams();	
		
		
        if(isset($pay_params['start_date']) || isset($pay_params['end_date']) || isset($pay_params['sel_type']) || isset($pay_params['invoicename']) || isset($pay_params['contribname']))
         {
           $start_date = $pay_params['start_date'];
           $end_date = $pay_params['end_date'];
           $sel_type = $pay_params['sel_type'];
           $invoicename = $pay_params['invoicename'];
           $contribname = $pay_params['contribname'];
           $paid_type=$pay_params['paid_type'];   
		   

            $this->_view->sel_type=$sel_type;
             $this->_view->paid_type=$paid_type;
            $this->_view->start_date=$start_date;
            $this->_view->end_date=$end_date;
            $this->_view->contribname=$contribname;
            $this->_view->invoicename=$invoicename;
              $where = '';
            if($invoicename!='')
            {
                $where.= " AND i.invoiceId='ep_invoice_".$invoicename."'";
            }
            if(!is_null($contribname) &&  $contribname!=0)
            {
                //$where = " up.first_name LIKE '%".$contribname."%'";
                $where.= " AND up.user_id ='".$contribname."'";
            }
            if($sel_type!='All' && $sel_type!='')
            {
                $where.= " AND i.payment_type='".$sel_type."'";
            }
            if($start_date!='' && $end_date!='')
            {
                $start_date = str_replace('/','-',$start_date);
                $end_date = str_replace('/','-',$end_date);
                $start_date = date('Y-m-d', strtotime($start_date));
                $end_date = date('Y-m-d', strtotime($end_date));
                $where.= " AND DATE_FORMAT(i.created_at, '%Y-%m-%d')  BETWEEN '".$start_date."' AND '".$end_date."'";
            }
             if($paid_type=='All' || $paid_type=='')
             {
                 $where.= " AND i.status NOT IN ('paid','inprocess')";
             }
             else
             {
                 if($paid_type=='notpaid')
                      $where.= " AND i.status='Not paid'";
                 else if($paid_type=='refuse')
                      $where.= " AND i.status='refuse'";
             }
            $res= $invoice_obj->contribsPayableList($where);
        }
        else{
            $where.= " AND i.status NOT IN ('paid','inprocess','refuse')";
            $res= $invoice_obj->contribsPayableList($where);
        }if($_REQUEST['where']){exit($where);}
        if($res!="NO")
		{
			$j=0;$paidcount=0;$notpaidcount=0;$paypal=0;$virement=0;$cheque=0;
            foreach($res as $invoice)
            {
                if($invoice['refuse_count']>0)
                {
                    if($invoice['refuse_count']>1)
                    {
                        $rcnt="R".(int)($invoice['refuse_count']-1);
                    }
                    else
                         $rcnt="R";
                    $invoiceId_array=explode("-",$invoice['invoiceId']);

                    //$invoiceId_new=$invoiceId_array[0]."-".$invoiceId_array[1]."-".$invoiceId_array[2].$rcnt."-".$invoiceId_array[3];
                    $invoiceId_new=$invoiceId_array[2].$rcnt."/".$invoiceId_array[1]."/".$invoiceId_array[0]."-".$invoiceId_array[3];
                    $res[$j]['invoiceId_new']=$invoiceId_new;
                }
                else
                {
                   $invoiceId_array=explode("-",$invoice['invoiceId']);
                   $invoiceId_new=$invoiceId_array[2]."/".$invoiceId_array[1]."/".$invoiceId_array[0]."-".$invoiceId_array[3];

                   $res[$j]['invoiceId_new']=$invoiceId_new;
                   //$res[$j]['invoiceId_new']=$invoice['invoiceId'];
                }
                if($res[$j]['status'] == "Paid")
                {
                    $paidcount++;
                }
                if($res[$j]['status'] == "Not paid")
                {
                    $notpaidcount++;
                }
				if($res[$j]['payment_type'] == "cheque")
                {
                    $cheque++;
                }
				if($res[$j]['payment_type'] == "virement")
                {
                    $virement++;
                }
				if($res[$j]['payment_type'] == "paypal")
                {
                    $paypal++;
                }
				
				if($invoice['pay_later_month'])	
                      $res[$j]['pay_later_month_name']=strftime( '%B', strtotime( '+'.$invoice['pay_later_month'].' month', strtotime($invoice['created_at'])));
                $j++;
            }
            foreach ($res as $key => $value) {
                $res[$key]['total_invoice_paid'] = number_format($res[$key]['total_invoice_paid'],2,","," ");
            }
            $this->_view->paginator = $res;
			$this->_view->paidinvoice = $paidcount;
            $this->_view->notpaidinvoice = $notpaidcount;
			$this->_view->cheque = $cheque;
			$this->_view->virement = $virement;
			$this->_view->paypal = $paypal;
		}
		else
		{
			$this->_view->nores = "true";
		}
		$this->_view->render("stats_contribpayablelist");
	}
	
	public function inprocessInvoicesListAction()
	{
        $invoice_obj = new Ep_Payment_Invoice();
        $this->_view->currentdate = date("d-m-Y");
        $pay_params=$this->_request->getParams();
        if(isset($pay_params['start_date']) || isset($pay_params['end_date']) || isset($pay_params['sel_type']) || isset($pay_params['invoicename']) || isset($pay_params['contribname']))
         {
           $start_date = $this->_request->getParam('start_date');
           $end_date = $this->_request->getParam('end_date');
           $sel_type = $this->_request->getParam('sel_type');
           $invoicename = $this->_request->getParam('invoicename');
           $contribname = $this->_request->getParam('contribname');
           $paid_type=$this->_request->getParam('paid_type');

            $this->_view->sel_type=$sel_type;
             $this->_view->paid_type=$paid_type;
            $this->_view->start_date=$start_date;
            $this->_view->end_date=$end_date;
            $this->_view->contribname=$contribname;
            $this->_view->invoicename=$invoicename;
              $where = '';
            if($invoicename!='')
            {
                $where.= " AND i.invoiceId='ep_invoice_".$invoicename."'";
            }
            if(!is_null($contribname) &&  $contribname!=0)
            {
                //$where = " up.first_name LIKE '%".$contribname."%'";
                $where.= " AND up.user_id ='".$contribname."'";
            }
            if($sel_type!='All' && $sel_type!='')
            {
                $where.= " AND i.payment_type='".$sel_type."'";
            }
            if($start_date!='' && $end_date!='')
            {
                $start_date = str_replace('/','-',$start_date);
                $end_date = str_replace('/','-',$end_date);
                $start_date = date('Y-m-d', strtotime($start_date));
                $end_date = date('Y-m-d', strtotime($end_date));
                $where.= " AND DATE_FORMAT(i.created_at, '%Y-%m-%d')  BETWEEN '".$start_date."' AND '".$end_date."'";
            }
             
            $where.= " AND i.status IN ('inprocess')";
             
            $res= $invoice_obj->contribsPayableList($where);
        }
        else{
            $where.= " AND i.status IN ('inprocess')";
            $res= $invoice_obj->contribsPayableList($where);
        }if($_REQUEST['where']){exit($where);}
        if($res!="NO")
		{
			$j=0;$paidcount=0;$notpaidcount=0;$paypal=0;$virement=0;$cheque=0;
            foreach($res as $invoice)
            {
                if($invoice['refuse_count']>0)
                {
                    if($invoice['refuse_count']>1)
                    {
                        $rcnt="R".(int)($invoice['refuse_count']-1);
                    }
                    else
                         $rcnt="R";
                    $invoiceId_array=explode("-",$invoice['invoiceId']);

                    //$invoiceId_new=$invoiceId_array[0]."-".$invoiceId_array[1]."-".$invoiceId_array[2].$rcnt."-".$invoiceId_array[3];
                    $invoiceId_new=$invoiceId_array[2].$rcnt."/".$invoiceId_array[1]."/".$invoiceId_array[0]."-".$invoiceId_array[3];
                    $res[$j]['invoiceId_new']=$invoiceId_new;
                }
                else
                {
                   $invoiceId_array=explode("-",$invoice['invoiceId']);
                   $invoiceId_new=$invoiceId_array[2]."/".$invoiceId_array[1]."/".$invoiceId_array[0]."-".$invoiceId_array[3];

                   $res[$j]['invoiceId_new']=$invoiceId_new;
                   //$res[$j]['invoiceId_new']=$invoice['invoiceId'];
                }

                if($res[$j]['status'] == "Paid")
                {
                    $paidcount++;
                }
                if($res[$j]['status'] == "Not paid")
                {
                    $notpaidcount++;
                }
				if($res[$j]['payment_type'] == "cheque")
                {
                    $cheque++;
                }
				if($res[$j]['payment_type'] == "virement")
                {
                    $virement++;
                }
				if($res[$j]['payment_type'] == "paypal")
                {
                    $paypal++;
                }
                if($invoice['pay_later_month']>0)	
                      $res[$j]['pay_later_month_name']=strftime( '%B', strtotime( 'last day of +'.$invoice['pay_later_month'].' month', strtotime($invoice['created_at'])));
				else	  
					$res[$j]['pay_later_month_name']=0;
                $j++;
            }
            foreach ($res as $key => $value) {
                $res[$key]['total_invoice_paid'] = number_format($res[$key]['total_invoice_paid'],2,","," ");
            }
            $this->_view->paginator = $res;
			$this->_view->paidinvoice = $paidcount;
            $this->_view->notpaidinvoice = $notpaidcount;
			$this->_view->cheque = $cheque;
			$this->_view->virement = $virement;
			$this->_view->paypal = $paypal;
		}
		else
		{
			$this->_view->nores = "true";
		}
		$this->_view->render("contrib_inprocess_invoice_list");
	}

    public function generatepdfAction()
    {
            /***Profile Info***/            
            $date_invoice_full= strftime("%e %B %Y");
            $date_invocie = date("d-m-Y");
            $date_invoice_ep=date("Y/m");
            $profileplus_obj = new Ep_User_UserPlus();
            $invoice_id = $_GET['invoiceid'];
            $profileContrib_obj = new EP_User_Contributor();
            //$invoiceuser=new Ep_Royalty_Invoice();
            $invoiceuser=new Ep_Payment_Invoice();
            $contrib_identifier = $invoiceuser->getContributorId($invoice_id);
            $contrib_identifier = $contrib_identifier[0]['user_id'];
            if($profileplus_obj->checkProfileExist($contrib_identifier)!='NO')
            {

                $profile_identifier_info=$profileplus_obj->checkProfileExist($contrib_identifier);
                $profile_identifier=$profile_identifier_info[0]['user_id'];
                $profile_contribinfo=$profileContrib_obj->getProfileInfo($profile_identifier);
                 /**iNOVICE inFO ***/
                $this->_view->ep_contrib_profile_pay_info_type=$profile_contribinfo[0]['pay_info_type'];
                $this->_view->ep_contrib_profile_SSN=$profile_contribinfo[0]['SSN'];
                $this->_view->ep_contrib_profile_company_number=$profile_contribinfo[0]['company_number'];
                $this->_view->ep_contrib_profile_vat_check=$profile_contribinfo[0]['vat_check'];
                $this->_view->ep_contrib_profile_VAT_number=$profile_contribinfo[0]['VAT_number'];

                $profileinfo=$profileplus_obj->getProfileInfo($profile_identifier);
                
                $address='<b>'.$profileinfo[0]['first_name'].' '.$profileinfo[0]['last_name'].'</b><br><br>';
                $address.=$profileinfo[0]['address'].'<br>';
                $address.=$profileinfo[0]['zipcode'].'  '.$profileinfo[0]['city'].'  '.$this->getCountryName($profileinfo[0]['country']).'<br>';
                //$address.='Phone :'.$profileinfo[0]['phone_number'].'<br>';
                //$address.='Email :'.$this->EP_Contrib_reg->clientemail;
                $full_name='<b>'.$profileinfo[0]['first_name'].' '.$profileinfo[0]['last_name'].'</b>';
            }

            /**ENDED**/
            $identifier= $contrib_identifier;
            $royalty=new Ep_Payment_Royalties();
			$ticket_obj=new Ep_Message_Ticket();
            $invoiceDetails=$royalty->getInvoiceDetails2($identifier, $invoice_id);
			$invoice_details_pdf='<table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Description</th>
                                                <th>Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody>';

            if(count($invoiceDetails)>0 && is_array($invoiceDetails))
            {
                $total=0;
                foreach( $invoiceDetails as $details)
                {
                    $total+=$details['price'];
					
		    $client_id= $details['client_id'];
                    $client_name=$ticket_obj->getUserName($client_id);
                    $article_created_date=ucfirst(strftime("%b %Y",strtotime($details['article_created_date'])));
					
                    $invoice_details_pdf.='<tr>
                                            <td>'.$details['AOTitle'].'</td>
                                            <td class="change_order_total_col">'.number_format($details['price'],2).'</td>
                                            </tr>';
                }
                $invoice_details_pdf.='<tr>
                                        <td>Total</td>
                                    <td class="change_order_total_col">'.number_format($total,2).(($details['currency']=='pound') ? '&#163;' : '&#x80;' ).'</td>
                                        </tr>
                                    </tbody>
                                </table>';  
                                
               /**Total Invoice*/
                $total=number_format($total,2,'.','');
                $this->_view->totalInvoice=$total;

                /**Tax Calculation */
                $totalTax=0;
                $tax_details_pdf='';
                $payinfo_number='';

		if($invoiceDetails[0]['payment_info_type']=='ep_admin') //Added w.r.t new admin fees
                  {
                       $admin_fee_percentage=$invoiceDetails[0]['ep_admin_fee_percentage'];
					  $epTax=number_format((($total*$admin_fee_percentage)/100),2,'.','');
                      $totalTax=$epTax;

                      $tax_details_pdf='<table class="table table-bordered">                                            
                                              <tr class="alert alert-danger">
                            <td style="width: 67%;">Transfer and administrative charges : '.$admin_fee_percentage.'%</td>
                            
                            <td>'.number_format((($total*$admin_fee_percentage)/100),2,',','').' '.(($details['currency']=='pound') ? '&#163;' : '&#x80;' ).'</td>
                                              </tr>                                            
                                              </table>';
                  }
                  if($invoiceDetails[0]['ep_admin_fee']=='yes' && $invoiceDetails[0]['pay_later_month']) //added w.r.t new admin fees
                  {
                      $period_month=$invoiceDetails[0]['pay_later_month'];
                      $fees_percentage=$invoiceDetails[0]['pay_later_percentage'];
                      $invoice_date=$invoiceDetails[0]['requested_date'];

                     if($period_month>0)
                        $fees_paid_month=strftime( '%B', strtotime( 'last day of +'.$period_month.' month', strtotime($invoice_date)));

                      /*if($period_month==1)
                      {                          
                          $fees_paid_month=strftime( '%B', strtotime( '+1 month', time() ) );
                      }    
                      if($period_month==2)
                      {                          
                           $fees_paid_month=strftime( '%B', strtotime( '+2 month',time() ) );
                      }*/ 

                       $period_fees=number_format(((($total-$totalTax)*$fees_percentage)/100),2,'.',''); 
                       
                        $tax_details_pdf.='<table class="table table-bordered">
						<tr class="alert alert-danger">
                            <td style="width: 67%;">Advance payment charge : '.$fees_percentage.'% (payment on the 15th '.$fees_paid_month.' )</td>
                            
                            <td>'.number_format($period_fees,2,',','').' '.(($details['currency']=='pound') ? '&#163;' : '&#x80;' ).'</td>
                                              </tr></table>'; 

                        $totalTax=$totalTax+$period_fees;                      

                  } 
				
                $this->_view->totalTax=$totalTax;
                if($invoiceDetails[0]['payment_info_type']=='ssn')
                    $this->_view->FinaltotalInvoice=number_format(($total-$totalTax),2,'.','');
                else if($invoiceDetails[0]['payment_info_type']=='comp_num' && $profile_contribinfo[0]['vat_check']=='YES' )
                    $this->_view->FinaltotalInvoice=number_format(($total+$totalTax),2,'.','');
				else if($invoiceDetails[0]['payment_info_type']=='ep_admin')
                    $this->_view->FinaltotalInvoice=number_format(($total-$totalTax),2,'.','');
                else
                    $this->_view->FinaltotalInvoice=number_format($total,2,'.','');

                $final_invoice_amount='<table class="change_order_items table" width="100%">
                                            <tr class="alert alert-info">
                          <td style="width: 67%;"><strong>Total Amount payable to the writer</strong></td>
                          <td><strong>'.number_format($this->_view->FinaltotalInvoice,2).' '.(($details['currency']=='pound') ? '&#163;' : '&#x80;' ).'</strong></td>
                                            </tr>
                                        </table>';
            }
            /**Wire OR paypal info**/
                $total_transfer_amount='';
                $bank_transfer_price='';
                       if($invoiceDetails[0]['payment_type']=="paypal")
                       {
                           $bank_charges=0;
                           $total_transfer_amount_final=$this->_view->FinaltotalInvoice+$bank_charges;
                           $total_transfer_amount='<table class="change_order_items table" width="100%">
                                                        <tr class="alert alert-info">
                                      <td><strong>Final Amount</strong></td>
                                      <td><strong>'.number_format($total_transfer_amount_final,2).' '.(($details['currency']=='pound') ? '&#163;' : '&#x80;' ).'</strong></td>
                                                        </tr>
                                                    </table>';
                           $remuneration="Paypal : ".$invoiceDetails[0]['payment_info_id']."<br>";
                           $mode="Mode de paiement : <strong>PAYPAL</strong> ";
                       }
                       else if($invoiceDetails[0]['payment_type']=="virement")
                       {
                           if($invoiceDetails[0]['pay_info_type']=='out_uk')
                           {
                               $bank_charges=0;
                               $total_transfer_amount_final=$this->_view->FinaltotalInvoice+$bank_charges;
                                
                               //$bank_codes=explode("|",$profile_contribinfo[0]['rib_id']);  						
							    $bank_codes=explode(" ",$invoiceDetails[0]['payment_info_id']);      
								//$remuneration="IBAN  : ".$bank_codes[1]."<br>";
								//$remuneration.="BIC swift Bank Identification Code  : ".$bank_codes[0];	
								$remuneration="BIC : ".$bank_codes[0]."&nbsp;&nbsp;&nbsp;IBAN : ".$bank_codes[1]."<br>"; 								
								//$profile_contribinfo[0]['rib_id']= str_ireplace("|",' ',$profile_contribinfo[0]['rib_id']);                
								//$profile_contribinfo[0]['payment_info_id']=$profile_contribinfo[0]['rib_id'];
                           }
                           else
							{
								//$remuneration="RIB : ".$invoiceDetails[0]['payment_info_id']."<br>";
								
								$bank_codes=explode(" ",$invoiceDetails[0]['payment_info_id']);
								if(count($bank_codes)<5)
								$remuneration="BIC : ".$bank_codes[0]."&nbsp;&nbsp;&nbsp;IBAN : ".$bank_codes[1];
								else 
									$remuneration="RIB : ".str_ireplace("|",' ',$profile_contribinfo[0]['rib_id']);
							}
							//$invoiceDetails[0]['payment_info_id']=$invoiceDetails[0]['rib_id'];
							$bank_account_name="Account name : ".$invoiceDetails[0]['bank_account_name']."<br>";
							$mode="Mode de paiement : <strong>VIREMENT</strong>";
                       }
                       else
                       {
                           $remuneration="";
                           $mode="Mode de paiement : <strong>CHEQUE</strong>";
                       }
                       
                        if($invoiceDetails[0]['refuse_count']>0)
                        {
                            if($invoiceDetails[0]['refuse_count']>1)
                            {
                                $rcnt="R".(int)($invoiceDetails[0]['refuse_count']-1);
                            }
                            else
                                 $rcnt="R";
                            $invoiceId_array=explode("-",$invoice_id);
                            $invoiceId_new=$invoiceId_array[0]."-".$invoiceId_array[1]."-".$invoiceId_array[2].$rcnt."-".$invoiceId_array[3];
                        }
                        else
                            $invoiceId_new=$invoice_id;

               $this->_view->invoice_details_pdf = $invoice_details_pdf;
               $this->_view->tax_details_pdf = $tax_details_pdf;
               $this->_view->final_invoice_amount = $final_invoice_amount;
               $this->_view->date_invoice_full = strftime("%e %B %Y",strtotime($invoiceDetails[0]['requested_date']));
                $this->_view->date_invoice = date("d-m-Y",strtotime($invoiceDetails[0]['requested_date']));
               $this->_view->address = $address;
               $this->_view->payinfo_number = $payinfo_number;
               $this->_view->date_invoice_ep = date("Y/m",strtotime($invoiceDetails[0]['invoiceDate']));
               $this->_view->invoice_identifier = $invoice_id;
               $this->_view->invoiceId_new = $invoiceId_new;
               $this->_view->remuneration = $remuneration;
               $this->_view->mode = $mode;
	       $this->_view->bank_account_name = $bank_account_name;
               $this->_view->total_transfer_amount = $total_transfer_amount;
               $this->_view->bank_transfer_price = $bank_transfer_price;
               $this->_view->full_name = $full_name;

               $this->render(($_GET['print'] == "yes") ? "stats_invoiceprint" : "stats_invoice");
   }
	
	//change status to inprocess
	public function payContributorProcessAction()
    {
		$invoice_obj = new Ep_Payment_Invoice();
        $invoice_params=$this->_request->getParams();
		
		$updated_by=$this->adminLogin->userId;
		
        if(isset($invoice_params['invoice_id']))
        {
            $updated_at=date("Y-m-d %h:%i:%s");
            $data = array("status"=>'inprocess',"updated_at"=>$updated_at,"updated_by"=>$updated_by);////////updating
            $query = "invoiceId= 'ep_invoice_".$invoice_params['invoice_id']."'";
            $invoice_obj->updateInvoice($data,$query);
			$this->_helper->FlashMessenger(utf8_decode("Paid succ&egrave;s."));
            $this->_redirect("/stats/contrib-payments-list?submenuId=ML5-SL1");
		}	
	
	}
	//change status to inprocess for multipleple invoices
	public function payMultipleContributorProcessAction()
    {
		$invoice_obj = new Ep_Payment_Invoice();
        $invoice_params=$this->_request->getParams();
        $invoiceIds=explode(',', $invoice_params['invoice_ids']);
		$updated_by=$this->adminLogin->userId;
        //print_r($invoiceIds);exit;
        foreach($invoiceIds as $invoiceId)
        {
            echo '#'.$invoiceId.'#<br>';
            $updated_at=date("Y-m-d %h:%i:%s");
            $data = array("status"=>'inprocess',"updated_at"=>$updated_at,"updated_by"=>$updated_by);
            $query = "invoiceId= 'ep_invoice_".$invoiceId."'";
            $invoice_obj->updateInvoice($data,$query);            
        }
        $this->_helper->FlashMessenger(utf8_decode("Factures pay&#233;es avec succ&#232;s"));
        $this->_redirect("/stats/contrib-payments-list?submenuId=ML5-SL1");	
	
	}
	
    //////////pay the contributor /////////////////
    public function paycontributorAction()
    {
        $invoice_obj = new Ep_Payment_Invoice();
        $invoice_params=$this->_request->getParams();
		$updated_by=$this->adminLogin->userId;
        if(isset($invoice_params['invoice_id']))
        {
            $updated_at=date("Y-m-d %h:%i:%s");
            $data = array("status"=>'Paid',"updated_at"=>$updated_at,"updated_by"=>$updated_by);////////updating
            $query = "invoiceId= 'ep_invoice_".$invoice_params['invoice_id']."'";
            $invoice_obj->updateInvoice($data,$query);

            /* *sending mail to contributor**/
            $autoEmails=new Ep_Message_AutoEmails();
            $royalty=new Ep_Payment_Royalties();
            $details=$royalty->getInvoiceDetails('ep_invoice_'.$invoice_params['invoice_id']);

            $parameters['invoice_id']=$invoice_params['invoice_id'];
            $parameters['contributor_name']=$details[0]['first_name'];
            $autoEmails->messageToEPMail($details[0]['user_id'],67,$parameters);		
			

            $this->_helper->FlashMessenger(utf8_decode("Paid succes."));
            $this->_redirect("/stats/inprocess-invoices-list?submenuId=ML5-SL2");
        }
    }
    //////////pay the contributors /////////////////
    public function paycontributorsAction()
    {
        $invoice_obj = new Ep_Payment_Invoice();
        $invoice_params=$this->_request->getParams();
        $invoiceIds=explode(',', $invoice_params['invoice_ids']);
		$updated_by=$this->adminLogin->userId;
        //print_r($invoiceIds);exit;
        foreach($invoiceIds as $invoiceId)
        {
            echo '#'.$invoiceId.'#<br>';
            $updated_at=date("Y-m-d %h:%i:%s");
            $data = array("status"=>'Paid',"updated_at"=>$updated_at,"updated_by"=>$updated_by);
            $query = "invoiceId= 'ep_invoice_".$invoiceId."'";
            $invoice_obj->updateInvoice($data,$query);

            /* *sending mail to contributor**/
            $autoEmails=new Ep_Message_AutoEmails();
            $royalty=new Ep_Payment_Royalties();
            $details=$royalty->getInvoiceDetails('ep_invoice_'.$invoiceId);

            $parameters['invoice_id']=$invoiceId;
            $parameters['contributor_name']=$details[0]['first_name'];
            $autoEmails->messageToEPMail($details[0]['user_id'],67,$parameters);
        }
        $this->_helper->FlashMessenger(utf8_decode("Paid succes"));
        $this->_redirect("/stats/inprocess-invoices-list?submenuId=ML5-SL2");
    }    

    //refuse invoice of a contributor
     public function refuseinvoiceAction()
    {
        $invoice_obj = new Ep_Payment_Invoice();
        $invoice_params=$this->_request->getParams();
        if(isset($invoice_params['invoice_id']))
        {
            $updated_at=date("Y-m-d %h:%i:%s");
            $status=$invoice_obj->getInvoiceStatus($invoice_params['invoice_id']);
            if($status=='Not paid' || $status=='inprocess' )
            {

                $refuse_count="refuse_count+1";
                $data = array("status"=>'refuse',"updated_at"=>$updated_at,"refuse_count"=> new Zend_Db_Expr('refuse_count+1'));////////updating
                $query = "invoiceId= 'ep_invoice_".$invoice_params['invoice_id']."'";
                $invoice_obj->updateInvoice($data,$query);

                /* *sending mail to contributor**/
                $autoEmails=new Ep_Message_AutoEmails();
                $royalty=new Ep_Payment_Royalties();
                $details=$royalty->getInvoiceDetails('ep_invoice_'.$invoice_params['invoice_id']);
                $parameters['contributor_name']=$details[0]['first_name'];
                $parameters['invoice_id']=$invoice_params['invoice_id'];
                $autoEmails->messageToEPMail($details[0]['user_id'],68,$parameters);

                $this->_helper->FlashMessenger(utf8_decode("Invoice refused."));
            }

            $this->_redirect("/stats/contrib-payments-list?submenuId=ML5-SL1");
        }
    }

    public function invoicedetailsAction()
    {
        $royalty=new Ep_Payment_Royalties();
        if($this->_request->getParam('invoiceid'))
        {
            $invoiceId=$this->_request->getParam('invoiceid');
            $invoiceId='ep_invoice_'.$invoiceId;
        }
        $invoiceDetails=$royalty->getInvoiceDetails($invoiceId);
        if(count($invoiceDetails)>0 && is_array($invoiceDetails))
        {
            $total=0;
            foreach( $invoiceDetails as $details)
            {
                $total+=$details['price'];
            }
            if($invoiceDetails[0]['payment_info_type'])
            {
                 $profile_contribinfo[0]['pay_info_type']=$invoiceDetails[0]['payment_info_type'];
            }
            if($invoiceDetails[0]['vat_check'])
            {
                 $profile_contribinfo[0]['vat_check']=$invoiceDetails[0]['vat_check'];
            }
                  /**Total Invoice*/
            $total=number_format($total,2);
            $this->_view->totalInvoice=$total;
            /**Tax Calculation */
            $totalTax=0;
            if($profile_contribinfo[0]['pay_info_type']=='ssn')
            {
                $veuvage=number_format((($total*0.85)/100),2);
                $csg=number_format((($total*7.275)/100),2);
                $crds=number_format((($total*0.485)/100),2);

                $totalTax=$veuvage+$csg+$crds;
            }
            else if($profile_contribinfo[0]['pay_info_type']=='comp_num'&& $profile_contribinfo[0]['vat_check']=='YES' )
            {
                $TVA=number_format((($total*19.6)/100),2);
                $totalTax=$TVA;
            }
             $this->_view->totalTax=$totalTax;
            if($profile_contribinfo[0]['pay_info_type']=='ssn')

            $this->_view->FinaltotalInvoice=number_format(($total-$totalTax),2);
            else if($profile_contribinfo[0]['pay_info_type']=='comp_num'&& $profile_contribinfo[0]['vat_check']=='YES' )

            $this->_view->FinaltotalInvoice=number_format(($total+$totalTax),2);
            else
                $this->_view->FinaltotalInvoice=number_format($total,2);

            if($this->_view->FinaltotalInvoice >=20 &&!$invoiceDetails[0]['invoice_path'])
            {
                $this->_view->getpaid="YES";
            }
            else if($invoiceDetails[0]['invoice_path'])
            {
                $this->_view->downloadPDF="YES";
                $this->_view->invoiceId=$invoiceId;
            }
            $this->_view->invoiceDetails=$invoiceDetails;
        }
        else
            $this->_redirect("/stats/contrib-payments-list?submenuId=ML5-SL1");
            $this->_view->ep_contrib_profile_pay_info_type=$profile_contribinfo[0]['pay_info_type'];
            $this->_view->ep_contrib_profile_SSN=$profile_contribinfo[0]['SSN'];
            $this->_view->ep_contrib_profile_company_number=$profile_contribinfo[0]['company_number'];
            $this->_view->ep_contrib_profile_vat_check=$profile_contribinfo[0]['vat_check'];
            $this->_view->ep_contrib_profile_VAT_number=$profile_contribinfo[0]['VAT_number'];
            $this->_view->meta_title="Contributor-Invoice";
            $this->render("stats_invoicedetails");
    }

    /**Download Invoice PDF***/
    public function downloadinvoiceAction()
    {
        $invoiceParams=$this->_request->getParams();
        $attachment=new Ep_Message_Attachment();
        $royalty=new Ep_Payment_Royalties();
		$invoice_obj=new Ep_Payment_Invoice();
		$ticket_obj=new Ep_Message_Ticket();

        $invoice_path=$royalty->getInvoicePDFPath($invoiceParams['invoiceid']);
		
		$invoice_name=basename($invoice_path);
		
		$contributor_details=$invoice_obj->getContributorId($invoiceParams['invoiceid']);
		$contributor_id=$contributor_details[0]['user_id'];
		$user_full_name=str_replace(" ","_",trim($ticket_obj->getUserName($contributor_id,TRUE)));      
		
		
		//Added for invoice id date changes
        $invoiceId_array=explode("-",$invoice_name);
        $invoice_name=$invoiceId_array[2]."-".$invoiceId_array[1]."-".$invoiceId_array[0]."-".$invoiceId_array[3];
        //ended

        $invoice_name=$user_full_name."_".$invoice_name;
		
		//echo $user_full_name."--".$contributor_id."--".$invoice_name;exit;
		
        if($invoice_path!='NOT EXIST')
        {
            $invoicePDFPath="/home/sites/site7/web/FO/invoice/".$invoice_path;
            //echo $invoicePDFPath;exit;
            if(file_exists($invoicePDFPath))
            {
                $attachment->downloadAttachment($invoicePDFPath,"attachment",$invoice_name);
				exit;
            }
        }
        else
            $this->_redirect("/stats/invoicedetails?submenuId=ML5-SL1");

    }
    public function invoicezipdownloadAction()
    {
        $invoiceParams=$this->_request->getParams();
        if(isset($invoiceParams['search']))
        {
            $this->contribpayablelist();
        }
        //////////////////////////////////////
      if(isset($invoiceParams["selectall"]))
      {
        $attachment=new Ep_Message_Attachment();
        $royalty=new Ep_Payment_Royalties();
        $ckecks = explode(',', $invoiceParams['hide_total']);

        /*for($i=0; $i<count($ckecks); $i++)
        {
            $chks = explode('_', $ckecks[$i]);
            $invoiceids[$i]=$chks[1];
        }*/
        $i=0;
        foreach($_POST as $key=>$value)
        {
            $invoiceids[$i]=$value;
            $i++;
        }  //print_r($invoiceids);
        if(isset($invoiceParams['select_all']) && $invoiceParams['select_all']=='all')
        {
            $countvar = 1;
        }
        else
        {
            $countvar = 0;
        }
        for($j=$countvar; $j<count($invoiceids); $j++)
        {
           $invoice_path[$j]=$royalty->getInvoicePDFPath($invoiceids[$j]);
           $invoicePDFPath[$j]="/home/sites/site7/web/FO/invoice/".$invoice_path[$j];
           $files_array[$j]=$invoicePDFPath[$j];
        }
        $filename="/home/sites/site7/web/FO/invoice/zip/invoice_".rand(1000, 9999).".zip";
        $result = $this->create_zip($files_array,$filename);

        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename='.basename($filename));
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filename));
        readfile($filename);
      }
      else if(isset($invoiceParams["refuseall"]))
      {
            $invoice_obj = new Ep_Payment_Invoice();

            $ckecks = explode(',', $invoiceParams['hide_total']);

            for($i=0; $i<count($ckecks); $i++)
            {
                $chks = explode('_', $ckecks[$i]);
                if($chks[1]!='all')
                    $invoiceids[$i]=$chks[1];
            }
            if(isset($invoiceParams['select_all']) && $invoiceParams['select_all']=='all')
            {
                $countvar = 1;
            }
            else
            {
                $countvar = 0;
            }
            for($j=$countvar; $j<=count($invoiceids); $j++)
            {
               $updated_at=date("Y-m-d %h:%i:%s");
                $status=$invoice_obj->getInvoiceStatus($invoiceids[$j]);
                if($status=='Not paid')
                {

                    $refuse_count="refuse_count+1";
                    $data = array("status"=>'refuse',"updated_at"=>$updated_at,"refuse_count"=> new Zend_Db_Expr('refuse_count+1'));////////updating
                    $query = "invoiceId= 'ep_invoice_".$invoiceids[$j]."'";
                    $invoice_obj->updateInvoice($data,$query);

                    /* *sending mail to contributor**/
                    $autoEmails=new Ep_Message_AutoEmails();
                    $royalty=new Ep_Payment_Royalties();
                    $details=$royalty->getInvoiceDetails('ep_invoice_'.$invoiceids[$j]);

                    $parameters['invoice_id']=$invoiceids[$j];
                    $autoEmails->messageToEPMail($details[0]['user_id'],21,$parameters);

                    $this->_helper->FlashMessenger(utf8_decode("Invoice refusès."));
                }
            }

            $this->_redirect("/stats/contrib-payments-list?submenuId=ML5-SL1");
      }
    }

    public function create_zip($files = array(),$destination = '',$overwrite = true)
    {
      //if the zip file already exists and overwrite is false, return false
      if(file_exists($destination) && !$overwrite) { return false; }
      //vars
      $valid_files = array();
      //if files were passed in...
      if(is_array($files)) {
        //cycle through each file
        foreach($files as $file) {
          //make sure the file exists
          if(file_exists($file)) {
            $valid_files[] = $file;
          }
        }
      }
      //if we have good files...
      if(count($valid_files)) {
        //create the archive
        $zip = new ZipArchive();
        if($zip->open($destination,$overwrite ? ZIPARCHIVE::OVERWRITE : ZIPARCHIVE::CREATE) !== true) {
          return false;
        }
        //add the files
        foreach($valid_files as $file) {
          //$zip->addFile($file,$file);
          $zip->addFile($file, basename($file));
        }
        //debug
        //echo 'The zip archive contains ',$zip->numFiles,' files with a status of ',$zip->status;
        //close the zip -- done!
        $zip->close();
        //check to make sure the file exists
        return file_exists($destination);
      }
      else
      {
        return false;
      }
    }
     /**function to get the Article type name**/
    public function getCountryName($country_value)
    {
        $country_array=$this->_arrayDb->loadArrayv2("countryList", $this->_lang);
        return $country_array[$country_value];
    }
    
    /* *Send mail to EP mail box**/
    /* public function messageToEPMail($receiverId,$mailid,$parameters)
    {
        $automail=new Ep_Message_AutoEmails();

        $AO_Creation_Date='<b>'.$parameters['created_date'].'</b>';
        $link='<a href="http://mmm-new.edit-place.com'.$parameters['document_link'].'">Cliquez ici </a>';
        $contributor='<b>'.$parameters['contributor_name'].'</b>';
        $AO_title="<b>".$parameters['AO_title']."</b>";
        $submitdate_bo="<b>".date("d/m/Y",strtotime($parameters['submitdate_bo']))."</b>";
        $total_articles="<b>".$parameters['noofarts']."</b>";
        $invoicelink='<a href="http://mmm-new.edit-place.com'.$parameters['invoice_link'].'">cliquant ici</a>';
        $article_link='<a href="'.$parameters['article_link'].'">Cliquez-ici</a>';
        $client='<b>'.$parameters['client_name'].'</b>';
        $royalty='<b>'.$parameters['royalty'].'</b>';
        $ongoinglink='<a href="http://mmm-new.edit-place.com'.$parameters['ongoinglink'].'">Cliquez ici </a>';
        $AO_end_date='<b>'.$parameters['AO_end_date'].'</b>';
        $article='<b>'.stripslashes($parameters['article_title']).'</b>';
        $AO_title='<b>'.stripslashes($parameters['AO_title']).'</b>';
        $inovice_id=$parameters['invoice_id'];

        $email=$automail->getAutoEmail($mailid);
        $Object=$email[0]['Object'];
        eval("\$Object= \"$Object\";");
        $Message=$email[0]['Message'];
        eval("\$Message= \"$Message\";");
        //echo $Object;exit;
        /* *Inserting into EP mail Box* * /
           $this->sendMailEpMailBox($receiverId,$Object,$Message);
    }

    public function sendMailEpMailBox($receiverId,$object,$content)
    {
        //////////configuration object///////////////
        $configobj = new Ep_Delivery_Configuration();
        //$configdetails = $configobj->ListConfiguration();
        $configdetails = $configobj->getAllConfigurations();

        $sender=$this->adminLogin->userId;
        $sender='111201092609847';
        $ticket=new Ep_Message_Ticket();
        $ticket->sender_id=$sender;
        $ticket->recipient_id=$receiverId;

        $ticket->title=$object;
        $ticket->status='0';
        $ticket->created_at=date("Y-m-d H:i:s", time());
        try
        {
            if($ticket->insert())
           {
                $ticket_id=$ticket->getIdentifier();
                $message=new Ep_Message_Message();
                $message->ticket_id=$ticket_id;
                $message->content=$content;
                $message->type='0' ;
                $message->status='0';
                $message->created_at=$ticket->created_at;
                $message->approved='yes';
                $message->auto_mail='yes';
                $message->insert();
                $messageId=$message->getIdentifier();

                $automail=new Ep_Message_AutoEmails();
                $UserDetails=$automail->getUserType($receiverId);

                 if(!$object)
                    $object="You have received an Edit-place email";

                 $object=strip_tags($object);

                if($UserDetails[0]['type']=='client')
				{
					$text_mail="<p>Dear Client,<br><br>
										You have received an email from Edit-Place!<br><br>
										Thank you to <a href=\"https://uk-secure.edit-place.co.uk/user/email-login?user=".MD5('ep_login_'.$email)."&hash=".MD5('ep_login_'.$password)."&type=".$type."&message=".$messageId."&ticket=".$ticket_id."\">click</a> here to read it.<br><br>
										Regards,<br>
										<br>
										All Edit-Place team</p>"
					;
				}
				else if($UserDetails[0]['type']=='contributor')
				{
					$text_mail="<p>Dear writer,<br><br>
										You have received an email from Edit-Place!<br><br>
										Thank you to <a href=\"https://uk-secure.edit-place.co.uk/user/email-login?user=".MD5('ep_login_'.$email)."&hash=".MD5('ep_login_'.$password)."&type=".$type."&message=".$messageId."&ticket=".$ticket_id."\">click</a> here to read it.<br><br>
										Regards,<br>
										<br>
										All Edit-Place team</p>"
					;
				}
                $mail = new Zend_Mail();
                $mail->addHeader('Reply-To',$configdetails['mail_from']);
                $mail->setBodyHtml($text_mail)
                     ->setFrom($configdetails["mail_from"])
                     ->addTo($UserDetails[0]['email'])
                     ->setSubject(utf8_decode($object));
                if($mail->send())
                    return true;
           }
        }
        catch(Exception $e)
        {
            echo $e->getMessage();
        }
    }*/

    public function newsletterStatsAction()
    {
        $newsletterObj  =   new Ep_Statistics_Stats() ;
        $newsletters    =   $newsletterObj->newsletters() ;
        $this->_view->newsletters = $newsletters;
        $this->_view->render("stats_newsletter");
        //echo '<pre>'; print_r($newsletters);
    }

    public function newsletterDetailsAction()
    {
        $newsletter_params  =   $this->_request->getParams();
        $newsletterObj  =   new Ep_Statistics_Stats() ;
        //$this->_view->newsletters    =   $newsletterObj->getUserNewsletter(($newsletter_params['userid'] ? $newsletter_params['userid'] : $newsletter_params['user_id']), $newsletter_params['id']) ;
        $this->_view->newsletters    =   $newsletterObj->getUserNewsletter($newsletter_params['user_id'], $newsletter_params['id']) ;
        $this->_view->users = $newsletterObj->getNewsletterUserOptions($newsletter_params['user_id']);
        $this->_view->userinfo = $newsletterObj->getUserInfo($newsletter_params['user_id']);
        $this->_view->render("stats_newsletter_details");
        //echo '<pre>'; print_r($newsletters);
    }
    
    /////////displays the invoices of contributors  which are already  paid for their contribition//////////////
    public function paidInvoicesAction()
    {
        $invoice_obj = new Ep_Payment_Invoice();
        $this->_view->currentdate = date("d-m-Y");
        $paid_params=$this->_request->getParams();

        if($paid_params['search'] == 'search')
        {
            $condition['searchsubmit'] = $paid_params['search'];
            $condition['start_date'] = $paid_params['start_date'];
            $condition['end_date'] = $paid_params['end_date'];
            $condition['pdstart_date'] = $paid_params['pdstart_date'];
            $condition['pdend_date'] = $paid_params['pdend_date'];
            $condition['sel_type'] = $paid_params['sel_type'];
            $condition['invoicename'] = $paid_params['invoicename'];
            $condition['contribname'] = $paid_params['contribname'];
            $condition['paid_type'] = $paid_params['paid_type'];
            // $condition['selfdetails'] =  trim(urldecode($contrib_params['contrib_self_details']));
            $sLimit1 = '';
            $res= $invoice_obj->paidInvoices($sWhere, $sOrder, $sLimit1, $condition);
        }
        else{
            $condition = " 1=1";
            $sLimit1 = '';
            $res= $invoice_obj->paidInvoices($sWhere, $sOrder, $sLimit1, $condition);
        }
		
		if($res!='NO')
		{
			foreach ($res as $key => $value) {
				$res[$key]['total_invoice_paid'] = number_format(($res[$key]['total_invoice_paid']),2,","," ");
			}
		}	
        if($_REQUEST['debug']){echo '<pre>';print_r($res);exit;}
        if($res!="NO")
        {
            $this->_view->paginator = $res;
        }
        else
        {
            $this->_view->nores = "true";
        }
        $this->_view->render("stats_paidinvoices");
    }
    public function loadpaidinvoicesAction()
    {
        $invoice_obj = new Ep_Payment_Invoice();
        $this->_view->currentdate = date("d-m-Y");
        $paid_params=$this->_request->getParams();
        $aColumns = array('identifier', 'user_id','invoiceId','first_name','invoicedate','paiddate','total_invoice_paid','payment_type','status');
        /* * Paging	 */
        $sLimit = "";
        if ( isset( $_GET['iDisplayStart'] ) && $_GET['iDisplayLength'] != '-1' )
        {
            $sLimit = "LIMIT ".intval( $_GET['iDisplayStart'] ).", ".
                intval( $_GET['iDisplayLength'] );
        }
        /* 	 * Ordering   	 */
        $sOrder = "";
        if ( isset( $_GET['iSortCol_0'] ) )
        {
            $sOrder = "ORDER BY  ";
            for ( $i=0 ; $i<intval( $_GET['iSortingCols'] ) ; $i++ )
            {
                if($aColumns[$i] == 'status')
                    $aColumns[$i] = 'i.status';
                if($aColumns[$i] == 'paiddate')
                    $aColumns[$i] = 'i.updated_at';
                if($aColumns[$i] == 'invoicedate')
                    $aColumns[$i] = 'i.created_at';
                if ( $_GET[ 'bSortable_'.intval($_GET['iSortCol_'.$i]) ] == "true" )
                {
                    $sOrder .= "`".$aColumns[ intval( $_GET['iSortCol_'.$i] ) ]."` ".
                        ($_GET['sSortDir_'.$i]==='asc' ? 'asc' : 'desc') .", ";
                }
            }

            $sOrder = substr_replace( $sOrder, "", -2 );
            if ( $sOrder == "ORDER BY" )
            {
                $sOrder = "";
            }
        }
        $sWhere = "";
        if ( isset($_GET['sSearch']) && $_GET['sSearch'] != "" )
        {
            $sWhere = " HAVING (";
            for ( $i=0 ; $i<count($aColumns) ; $i++ )
            {
                
                if($aColumns[$i] == 'updated_at')
                    $aColumns[$i] = 'i.updated_at';


                $keyword=addslashes($_GET['sSearch']);
                $keyword = preg_replace('/\s*$/','',$keyword);
                $keyword=preg_replace('/\(|\)/','',$keyword);
                $words=explode(" ",$keyword);
                if(count($words)>1)
                {
                    $sWhere.=$aColumns[$i]." like '%".utf8_decode($keyword)."%' OR ";
                    foreach($words as $key=>$word)
                    {
                        $word=trim($word);
                        if($word!='')
                        {
                            $sWhere .= "".$aColumns[$i]." LIKE '%".utf8_decode($word)."%' OR ";
                        }
                    }
                }
                else
                    $sWhere .= "".$aColumns[$i]." LIKE '%".utf8_decode($keyword)."%' OR ";
            }
            $sWhere = substr_replace( $sWhere, "", -3 );
            $sWhere .= ')';
        }

        /* Individual column filtering */
        for ( $i=0 ; $i<count($aColumns) ; $i++ )
        {
            if ( isset($_GET['bSearchable_'.$i]) && $_GET['bSearchable_'.$i] == "true" && $_GET['sSearch_'.$i] != '' )
            {
                if ( $sWhere == "" )
                {
                    $sWhere = " WHERE  ";
                }
                else
                {
                    $sWhere .= " AND  ";
                }
                $sWhere .= "`".$aColumns[$i]."` LIKE '%".$_GET['sSearch_'.$i]."%' ";
            }
        }
        ///////////////////contributor details in search and normal grid display ////////
        $paid_params=$this->_request->getParams();
        if($paid_params['search'] == 'search')
        {
            $condition['searchsubmit'] = $paid_params['search'];
            $condition['start_date'] = $paid_params['start_date'];
            $condition['end_date'] = $paid_params['end_date'];
            $condition['pdstart_date'] = $paid_params['pdstart_date'];
            $condition['pdend_date'] = $paid_params['pdend_date'];
            $condition['sel_type'] = $paid_params['sel_type'];
            $condition['invoicename'] = $paid_params['invoicename'];
            $condition['contribname'] = $paid_params['contribname'];
            $condition['paid_type'] = $paid_params['paid_type'];
            // $condition['selfdetails'] =  trim(urldecode($contrib_params['contrib_self_details']));
        }
        //$sOrder = '';
        $rResult  = $invoice_obj->paidInvoices($sWhere, $sOrder, $sLimit, $condition);
        $rResultcount = count($rResult);
        /////total count
        $sLimit = "";
        $countcontribs  = $invoice_obj->paidInvoices($sWhere, $sOrder, $sLimit, $condition);
        $iTotal = count($countcontribs);

        $output = array(
            "sEcho" => intval($_GET['sEcho']),
            "iTotalRecords" => $iTotal,
            "iTotalDisplayRecords" => $iTotal,
            "aaData" => array()
        );
        $count = 1;
        if($rResult != 'NO')
        {
            for( $i=0 ; $i<$rResultcount; $i++)
            {
                $row = array();
                for ( $j=0 ; $j<count($aColumns) ; $j++ )
                {
                    if($j == 1)
                        $row[] = $count;
                    else
                    {
                        if($aColumns[$j] == 'first_name')
                            $row[] = '<a style="cursor: pointer" href="/user/contributor-edit?submenuId=ML3-SL6&tab=viewcontrib&userId='.$rResult[$i]['user_id'].'" onclick="return getParticipateUserInfo('.utf8_encode($rResult[$i]["first_name"]).', '.utf8_encode($rResult[$i]["first_name"]).');" target="_userparticipateinfo">'.utf8_encode($rResult[$i]["first_name"]).'</a>';
                        elseif($aColumns[$j] == 'invoicedate' || $aColumns[$j] == 'i.created_at')
                            $row[] = date("d-m-Y", strtotime($rResult[$i]['invoicedate']));
                        elseif($aColumns[$j] == 'paiddate' || $aColumns[$j] == 'i.updated_at')
                        {
                            if($rResult[$i]['paiddate'] != '0000-00-00 00:00:00')
                                $row[] = date("d-m-Y", strtotime($rResult[$i]['paiddate']));
                            else
                                $row[] = "NA";
                        }
                        elseif($aColumns[$j] == 'total_invoice_paid'){
							if($rResult[$i]["currency"] == 'pound') $currnecy = "&#163;"; else $currnecy = "&#8364;";
                            $row[] = '<span class="label label-inverse pull-right">'.$rResult[$i]["total_invoice_paid"].' '.$currnecy.'</span>';
					    }
                        elseif($aColumns[$j] == 'payment_type')
                            $row[] = $rResult[$i]['payment_type'];
                        elseif($aColumns[$j] == 'status')
                            $row[] = utf8_encode($rResult[$i]['status']);
                        elseif($aColumns[$j] == 'identifier'){
                            $invoiceid = str_replace("ep_invoice_", "", $rResult[$i]['invoiceId']);
                            $row[] = '<input type=checkbox name='.$invoiceid.' id=row_sel'.$count.' value='.$rResult[$i]["total_invoice_paid"].' class="uni_style" onclick="calculateTotal();selectALL();" />';
                        }
                        elseif($aColumns[$j] == 'invoiceId'){
                            $contribname = str_replace(" ", "_", utf8_encode($rResult[$i]['first_name']));

                            $invoiceid = str_replace("ep_invoice_", "", $rResult[$i]['invoiceId']);

                           $invoiceId_array=explode("-",$invoiceid);
                           $invoiceId_new=$invoiceId_array[2]."/".$invoiceId_array[1]."/".$invoiceId_array[0]."-".$invoiceId_array[3];                           
                           $fullinvoiceid = $contribname."_".$invoiceId_new;
                           
                            $row[] = '<a href="/stats/generatepdf?submenuId=ML5-SL3&invoiceid='.$invoiceid.'">'.$fullinvoiceid.'</a>';
                        }
                        else
                            $row[] = $rResult[$i][ $aColumns[$j] ];
                    }
                }
                $output['aaData'][] = $row;
                $count++;
            }
        }
        echo json_encode( $output );
    }
	
	 function statsAction(){
        $stat_obj=new Ep_Statistics_Stats() ;
        $data=array();
        //print_r($_GET);
        if($_GET ){
            $start_date=date('Y-m-d H:i:s',strtotime($_GET['start_date']));
            $end_date=date('Y-m-d H:i:s',strtotime($_GET['end_date']));
            $search_type=$_GET['search_type'];
            $type=$_GET['type'];
            
            if(isset($_GET['start_date']) && isset($_GET['end_date']) && isset($_GET['search_type']) && isset($_GET['type'])){
                $data['search_type']=$search_type;
                $data['result']=$stat_obj->payment_stats($start_date,$end_date,$type,$search_type);
            }
            if($search_type==7){
				$total=0;
				$paid=0;
				$tax=0;
				//$nationality_array=$this->_arrayDb->loadArrayv2("Nationality", $this->_lang);
				//print_r($nationality);exit;
				//echo $this->nationality_array[38];exit;
				$data['nationality']=$this->nationality_array;
				foreach($data['result'][1] as $key => $value){
					$total=$total+$value['total'];
					$paid=$paid+$value['paid'];
					$tax=$tax+$value['tax'];
				}
				$count=count($data['result'][1]);
				$data['total']=array(
						'nationality'=>'TOTAL',
						'total'=>$total,
						'paid'=>$paid,
						'tax'=>$tax
					);
			}
            //echo'<pre>';print_r($data);exit;
        }
        $this->_view->data = $data;
		$this->_view->render("stats_stats");
	}
	
	/* by naseer on 17-07-2015 */
    public function clientInvoicesAction(){
        $params = $this->_request->getParams();
        if($params['search'] != '') {
            
			session_start();
            $invoice_obj = new Ep_Payment_Invoice();
            $this->_view->currentdate = date("d-m-Y");
            $_SESSION["params"] = $params;
            $conditions = $params;
            $conditions['search'] = ($params['search'] == 'search') ? 'search' : 'default';
            $res = $invoice_obj->clientInvoices($conditions);
            $client_obj = new Ep_User_Client();
           // $client_obj->getclientInvoicedList();
            $x = $client_obj->getclientInvoicedList();
            asort($x);
            $this->_view->client_invoiced = $x;
            if ($res != "NO") {
                $this->_view->search = ($params['search'] == 'search') ? 'search' : 'default';
                $this->_view->paginator = $res;

            } else {
                $this->_view->nores = "true";
            }
            $this->_view->render("stats_clientinvoices");

        }
        else{
            $this->_view->render("stats_clientinvoices");
        }
    }
    public function downloadClientInvoicesXlsAction(){
        $invoice_obj = new Ep_Payment_Invoice();
        $this->_view->currentdate = date("d-m-Y");
        $params= $_SESSION["params"];
        $conditions = $params;
        $conditions['search'] = ($params['search'] == 'search') ? 'search' : 'default' ;
        $res= $invoice_obj->clientInvoices($conditions);
        if($res!="NO")
        {
            error_reporting(E_ALL);
            ini_set('display_errors', TRUE);
            ini_set('display_startup_errors', TRUE);
            date_default_timezone_set('Europe/London');

            require_once APP_PATH_ROOT.'nlibrary/tools/PHPExcel.php';
            require_once APP_PATH_ROOT.'nlibrary/tools/PHPExcel/Writer/Excel2007.php';
           // $file_name = time()."excel_file.xlsx";
		    $file_name = $conditions['pdstart_date']."_to_".$conditions['pdend_date']."_".time().".xlsx";
            $file = "/home/sites/site5/web/FO/invoice/client/xls/".$file_name;
            $objPHPExcel = new PHPExcel();
            $objPHPExcel->setActiveSheetIndex(0);
            if($conditions['search'] == 'search') {
                $rowCount = 1;
                $objPHPExcel->getActiveSheet()->mergeCells('A1:F1');
                $styleArray = array(
                    'font'  => array(
                    'bold'  => true,
                    'size'  => 14
                ));
                $styleheadArray = array(
                    'font'  => array(
                    'bold'  => true,
                    'size'  => 12
                ));
                $styletotalArray= array(
                    'font'  => array(
                    'bold'  => true,
                    'color'  => array('rgb' => 'FF0000'),
                    'size'  => 12
                ));
                $objPHPExcel->getActiveSheet()->getStyle('A1')->applyFromArray($styleArray);
                $objPHPExcel->getActiveSheet()->SetCellValue('A' . $rowCount, 'Cleint invoices for the period of '.$conditions['pdstart_date'].' to '.$conditions['pdend_date']);

                $foo =0;
                $total=0;
                $grand=0;
                $count=1;
                foreach ($res as $details){
                    if($foo != $details['user_id']){
                       if($total != 0){
                           $rowCount++;
                           $objPHPExcel->getActiveSheet()->getStyle('A'. $rowCount)->applyFromArray($styletotalArray);
                           $objPHPExcel->getActiveSheet()->getStyle('F'. $rowCount)->applyFromArray($styletotalArray);
                           $objPHPExcel->getActiveSheet()->mergeCells('A'.$rowCount.':E'.$rowCount);
                           $objPHPExcel->getActiveSheet()->SetCellValue('A' . $rowCount, 'Total');
                           $objPHPExcel->getActiveSheet()->SetCellValue('F' . $rowCount, $total);
                           $total = 0;
                           $count = 1;
                       }


                        $rowCount++;
                        $objPHPExcel->getActiveSheet()->getStyle('A'. $rowCount)->applyFromArray($styleArray);
                        $objPHPExcel->getActiveSheet()->mergeCells('A'.$rowCount.':F'.$rowCount);
                        $objPHPExcel->getActiveSheet()->SetCellValue('A' . $rowCount, ' Cleint : '.$details['client_name'].' Email : '.$details['email']);
                        //new line
                        $rowCount++;

                        $objPHPExcel->getActiveSheet()->getStyle('A'. $rowCount)->applyFromArray($styleheadArray);
                        $objPHPExcel->getActiveSheet()->getStyle('B'. $rowCount)->applyFromArray($styleheadArray);
                        $objPHPExcel->getActiveSheet()->getStyle('C'. $rowCount)->applyFromArray($styleheadArray);
                        $objPHPExcel->getActiveSheet()->getStyle('D'. $rowCount)->applyFromArray($styleheadArray);
                        $objPHPExcel->getActiveSheet()->getStyle('E'. $rowCount)->applyFromArray($styleheadArray);
                        $objPHPExcel->getActiveSheet()->getStyle('F'. $rowCount)->applyFromArray($styleheadArray);
                        $objPHPExcel->getActiveSheet()->SetCellValue('A' . $rowCount, 'SL.No.');
                        $objPHPExcel->getActiveSheet()->SetCellValue('B' . $rowCount, 'Article title');
                        $objPHPExcel->getActiveSheet()->SetCellValue('C' . $rowCount, 'Paid Date');
                        $objPHPExcel->getActiveSheet()->SetCellValue('D' . $rowCount, 'Article cost');
                        $objPHPExcel->getActiveSheet()->SetCellValue('E' . $rowCount, 'Tax(%)');
                        $objPHPExcel->getActiveSheet()->SetCellValue('F' . $rowCount, 'Total Cost');

                    }
                        $rowCount++;
                        $objPHPExcel->getActiveSheet()->SetCellValue('A' . $rowCount, $count);
                        $objPHPExcel->getActiveSheet()->SetCellValue('B' . $rowCount, utf8_encode($details['article_title']));
                        $objPHPExcel->getActiveSheet()->SetCellValue('C' . $rowCount, $details['created_at']);
                        $objPHPExcel->getActiveSheet()->SetCellValue('D' . $rowCount, $details['amount']);
                        $objPHPExcel->getActiveSheet()->SetCellValue('E' . $rowCount, $details['tax'].'(%)');
                        $objPHPExcel->getActiveSheet()->SetCellValue('F' . $rowCount, $details['amount_paid']);

                    $foo = $details['user_id'];
                    $total += $details['amount_paid'];
                    $grand += $details['amount_paid'];
                    $count += 1;

                }
                $rowCount++;
                $objPHPExcel->getActiveSheet()->mergeCells('A'.$rowCount.':E'.$rowCount);
                $objPHPExcel->getActiveSheet()->getStyle('A'. $rowCount)->applyFromArray($styletotalArray);
                $objPHPExcel->getActiveSheet()->getStyle('F'. $rowCount)->applyFromArray($styletotalArray);
                $objPHPExcel->getActiveSheet()->SetCellValue('A' . $rowCount, 'Total');
                $objPHPExcel->getActiveSheet()->SetCellValue('F' . $rowCount, $total);

                $rowCount++;
                $objPHPExcel->getActiveSheet()->mergeCells('A'.$rowCount.':F'.$rowCount);
                $objPHPExcel->getActiveSheet()->SetCellValue('A' . $rowCount, '');

                $rowCount++;
                $objPHPExcel->getActiveSheet()->mergeCells('A'.$rowCount.':E'.$rowCount);
                $objPHPExcel->getActiveSheet()->getStyle('A'. $rowCount)->applyFromArray($styletotalArray);
                $objPHPExcel->getActiveSheet()->getStyle('F'. $rowCount)->applyFromArray($styletotalArray);
                $objPHPExcel->getActiveSheet()->SetCellValue('A' . $rowCount, 'Grand Total');
                $objPHPExcel->getActiveSheet()->SetCellValue('F' . $rowCount, $grand);

            }
            /* for loop to resize all the width of cell*/
            foreach(range('A','G') as $columnID)
            {
                $objPHPExcel->getActiveSheet()->getColumnDimension($columnID)->setAutoSize(true);
            }
            $objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel);
            $objWriter->save($file);
            $_SESSION['file']=$file;
            // code to download xlxs file automatically xlsx files have to be downloaded with a php script writen in web directory//
            include (APP_PATH_ROOT.'download-xlsx.php');
            //downloding for tempoary//
            exit;
        }
    }
    public function downloadClientInvoicesZipAction(){
        $invoice_obj = new Ep_Payment_Invoice();
        $this->_view->currentdate = date("d-m-Y");
        $params=$_SESSION['params'];
        //echo "<pre>";print_r($params);exit;
        $conditions = $params;
        $conditions['search'] = ($params['search'] == 'search') ? 'search' : 'default' ;
        $res= $invoice_obj->clientInvoices($conditions);
        if($res!="NO")
        {
            $baseDir = "/home/sites/site5/web/FO/invoice/zip/";
            $clientDir = "/home/sites/site5/web/FO/invoice/client";

            $name = date('F-Y').'-temp-'.time();
            $tempDir = $baseDir.$name;
            mkdir($tempDir);
            foreach( $res as $details)
            {
                mkdir($tempDir."/".$details['email']);
                $cpsource = $clientDir."/".$details['user_id']."/".$details['article_id'].".pdf";
                $cpdestination = $tempDir."/".$details['email']."/".$details['article_id'].".pdf";

                if(file_exists($cpsource)) {
                    copy($cpsource, $cpdestination);
                }
                else{
                    //echo file_get_contents("http://ep-test.edit-place.com/client/downloadinvoice?id=".$details['id']."&user_id=".$details['user_id']);
					$this->generateInvoice($details['article_id']);
                    copy($cpsource, $cpdestination);
                }
            }
			//exit;
            $file = $name.".zip";
            $source = $tempDir;
            $destination = $baseDir.$file;
            $this->Zip($source, $destination);
            // download the zip file//
            header("Content-Type: application/zip");
            header("Content-Disposition: attachment; filename=".$file.";" );
            header('Pragma: no-cache');
            readfile($destination);
        }
    }
    public function Zip($source, $destination)
    {
        if (!extension_loaded('zip') || !file_exists($source)) {
            return false;
        }

        $zip = new ZipArchive();
        if (!$zip->open($destination, ZIPARCHIVE::CREATE)) {
            return false;
        }

        $source = str_replace('\\', '/', realpath($source));

        if (is_dir($source) === true)
        {
            $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source), RecursiveIteratorIterator::SELF_FIRST);

            foreach ($files as $file)
            {
                $file = str_replace('\\', '/', $file);

                // Ignore "." and ".." folders
                if( in_array(substr($file, strrpos($file, '/')+1), array('.', '..')) )
                    continue;

                $file = realpath($file);

                if (is_dir($file) === true)
                {
                    $zip->addEmptyDir(str_replace($source . '/', '', $file . '/'));
                }
                else if (is_file($file) === true)
                {
                    $zip->addFromString(str_replace($source . '/', '', $file), file_get_contents($file));
                }
            }
        }
        else if (is_file($source) === true)
        {
            $zip->addFromString(basename($source), file_get_contents($source));
        }

        return $zip->close();
    }
	
	public function generateInvoice($article)
	{
		$invoiceid= $article;
		ob_start();
			$payment_obj = new Ep_Payment_PaymentArticle();
			$country_array=$this->_arrayDb->loadArrayv2("countryList", $this->_lang);

			//Payment details
			$payment=$payment_obj->getpaymentdetails($invoiceid);
			$invoicedir='/home/sites/site5/web/FO/invoice/client/'.$payment[0]['user_id'].'/';

			//Dates
			setlocale(LC_TIME, 'fr_FR');
			$date_invoice_full= strftime("%e %B %Y",strtotime($payment[0]['delivery_date']));
			$date_invocie = date("d/m/Y",strtotime($payment[0]['delivery_date']));
			$date_invoice_ep=date("Y/m",strtotime($payment[0]['delivery_date']));

		   //Address
			$profileinfo=$payment_obj->getClientdetails($payment[0]['user_id']);
			$address=$profileinfo[0]['company_name'].'<br>';
			$address.=$profileinfo[0]['address'].'<br>';
			$address.=$profileinfo[0]['zipcode'].'  '.$profileinfo[0]['city'].'  '.$country_array[$profileinfo[0]['country']].'<br>';

			//Invoice details
			$invoice_details_pdf='
				<div align="center" style="font-size:16pt;"><b>Appel d\'offres : '.$payment[0]['title'].'</b></div>
					<table class="change_order_items">
									<tbody>
										<tr>
											<th>DESIGNATION</th>
											<th>MONTANT</th>
											<th>MONTANT PAY&Eacute;</th>
										</tr>';

				$total=0;
				$total=number_format($payment[0]['amount'],2);

				$invoice_details_pdf.='<tr>
											<td>'.$payment[0]['title'].'</td>
											<td class="change_order_total_col">'.number_format($total,2,',','').'</td>
											<td class="change_order_total_col">'.number_format($total,2,',','').'</td>
											</tr>';

				$invoice_details_pdf.='<tr>
											<td style="border-top:1pt solid black;text-align:right;margin-right:10px;font-size: 12pt;" colspan="2">
												Total de la prestation HT
											</td>
											<td style="border-top:1pt solid black;font-size: 12pt;" class="change_order_total_col" >
												'.number_format($total,2,',','').'
											</td>
										</tr>
									</tbody>
								</table>';

			//Pay info number
			$payinfo_number="";

			if($payment[0]['amount']!="" && $payment[0]['client_type']!="personal")
			{
			  //Tax details
			   $tax=(($total*$payment[0]['tax'])/100);
			   $tax_details_pdf='<table class="change_order_items">
												<tbody>
													<tr>
														<td>TVA</td>
														<td>taux : '.str_replace('.', ',',$payment[0]['tax']).'%</td>
														<td class="change_order_total_col" style="border-top:1pt solid black;text-align:right;font-size: 12pt;">'.number_format($tax,2,',','').' &#x80; </td>
													</tr>
												</tbody>
												</table>';
			}
			else
				$tax=0;

			/**Final Total**/
			$final_invoice_amount='<table class="change_order_items" width="100%">
										<tr>
											<td  style="width:82%;font-size:12pt;font-weight:bold;background-color:#BDBDBD;border-top:1pt solid black;border-right:1pt solid black;text-align:right;padding:0.5em 1.5em 0.5em 0.5em;">Montant TTC</td>
											<td style="width:18%;font-weight:bold;border-top:1pt solid black;padding:0.5em;padding-right:10pt;font-size: 12pt;text-align: right" >'.number_format(($total+$tax),2,',','').' &#x80;</td>
										</tr>
									</table>';
			if(!is_dir($invoicedir))
			{
			   mkdir($invoicedir,0777);
			   chmod($invoicedir,0777);
			}
			include('/home/sites/site5/web/FO/dompdf/dompdf_config.inc.php');
			$html=file_get_contents('/home/sites/site5/web/FO/views/scripts/Client/Client_invoice_pdf.phtml');
			$html=str_replace('$$$$invoice_details_pdf$$$$',$invoice_details_pdf,$html);
			$html=str_replace('$$$$tax_details_pdf$$$$',$tax_details_pdf,$html);
			$html=str_replace('$$$$final_invoice_amount$$$$',$final_invoice_amount,$html);
			$html=str_replace('$$$$date_invoice_full$$$$',$date_invoice_full,$html);
			$html=str_replace('$$$$date_invoice$$$$',$date_invocie,$html);
			$html=str_replace('$$$$address$$$$',$address,$html);
			$html=str_replace('$$$$payinfo_number$$$$',$payinfo_number,$html);
			$html=str_replace('$$$$date_invoice_ep$$$$',$date_invoice_ep,$html);
			$html=str_replace('$$$$invoice_identifier$$$$',$payment[0]['payid'],$html);

				   if ( get_magic_quotes_gpc() )
					   $html = stripslashes($html);

					//echo  $html;//exit;

					$dompdf = new DOMPDF();
					$dompdf->load_html( $html);
					$dompdf->set_paper("a4");
					$dompdf->render();error_reporting(0);

					$pdf = $dompdf->output();
//echo $invoicedir.$invoiceid;exit;
			file_put_contents($invoicedir.$invoiceid.'.pdf', $pdf);
			ob_clean();
			//flush();
			//exit;

	}

}