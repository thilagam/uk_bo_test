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
            else if($sel_type!='All' && $sel_type!='')
            {
                $where.= " AND i.payment_type='".$sel_type."'";
            }
            else if($start_date!='' && $end_date!='')
            {
                $start_date = str_replace('/','-',$start_date);
                $end_date = str_replace('/','-',$end_date);
                $start_date = date('Y-m-d', strtotime($start_date));
                $end_date = date('Y-m-d', strtotime($end_date));
                $where.= " AND DATE_FORMAT(i.created_at, '%Y-%m-%d')  BETWEEN '".$start_date."' AND '".$end_date."'";
            }
             if($paid_type=='All' || $paid_type=='')
             {
                 $where.= " AND i.status!='paid'";
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
            $where.= " AND i.status!='Paid'";
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

                    $invoiceId_new=$invoiceId_array[0]."-".$invoiceId_array[1]."-".$invoiceId_array[2].$rcnt."-".$invoiceId_array[3];
                    $res[$j]['invoiceId_new']=$invoiceId_new;
                }
                else
                    $res[$j]['invoiceId_new']=$invoice['invoiceId'];
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
                $j++;
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

    public function generatepdfAction()
    {
            /***Profile Info***/
            setlocale(LC_TIME, 'fr_FR');
            $date_invoice_full= strftime("%e %B %Y");
            $date_invocie = date("d/m/Y");
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

                $address=$profileinfo[0]['first_name'].' '.$profileinfo[0]['last_name'].'<br>';
                $address.=$profileinfo[0]['address'].'<br>';
                $address.=$profileinfo[0]['zipcode'].'  '.$profileinfo[0]['city'].'  '.$this->getCountryName($profileinfo[0]['country']).'<br>';
            }

            /**ENDED**/
            $identifier= $contrib_identifier;
            //$royalty=new Ep_Royalty_Royalties();
            $royalty=new Ep_Payment_Royalties();
            $invoiceDetails=$royalty->getInvoiceDetails2($identifier, $invoice_id);
			
			//echo "<pre>";print_r($invoiceDetails);
            $invoice_details_pdf='<thead>
                                         <th colspan="2">DESIGNATION</th>
                                          <th>MONTANT</th>
                                        </thead><tbody>';
            if(count($invoiceDetails)>0 && is_array($invoiceDetails))
            {
                $total=0;
                foreach( $invoiceDetails as $details)
                {
                    $total+=$details['price'];
                    $invoice_details_pdf.='<tr>
                                            <td colspan="2">'.$details['AOTitle'].'</td>
                                            <td >'.number_format($details['price'],2,',','').(($details['currency']=='pound') ? '&#163;' : '&#x80;' ).'</td>
                                            </tr>';
                }
                $invoice_details_pdf.='<tr class="alert alert-info">
                                        <td colspan="2"><strong>Total de la prestation</strong></td>
                                        <td ><strong>'.number_format($total,2,',','').(($details['currency']=='pound') ? '&#163;' : '&#x80;' ).'</strong></td>
                                        </tr>';

               /**Total Invoice*/
                $total=number_format($total,2,'.','');
                $this->_view->totalInvoice=$total;

                /**Tax Calculation */
                $totalTax=0;
                $tax_details_pdf='';
                $payinfo_number='';
                if($invoiceDetails[0]['payment_info_type']=='ssn')
                {
                    $veuvage=number_format((($total*0.85)/100),2,'.','');
                    $csg=number_format((($total*7.36875)/100),2,'.','');
                    $crds=number_format((($total*0.49125)/100),2,'.','');
                    $formation=number_format((($total*0.35)/100),2,'.','');

                    $tax_date=$invoiceDetails[0]['invoiceDate'];
                    if($tax_date < date("Y-m-d",strtotime('2012-06-22')))
                    {
                        $formation=0;
                    }
                    $totalTax=$veuvage+$csg+$crds+$formation;
                    $tax_details_pdf='<tr>
                                        <td style="text-align:left" colspan="3"><strong>Pr&eacute;compte : Montant vers&eacute; pour vous par Edit-place</strong></td>                                        
                                        </tr>
                                        <tr>
                                        <td width="33%">Cotisation maladie veuvage</td>
                                        <td width="33%">taux : 0,85%</td>
                                        <td width="33%" >'.number_format((($total*0.85)/100),2,',','').(($details['currency']=='pound') ? '&#163;' : '&#x80;' ).'</td>
                                        </tr>
                                        <tr>
                                        <td>CSG</td>
                                        <td>taux : 7,36875%</td>
                                        <td >'.number_format((($total*7.36875)/100),2,',','').(($details['currency']=='pound') ? '&#163;' : '&#x80;' ).'</td>
                                        </tr>
                                        <tr>
                                        <td>CRDS</td>
                                        <td>taux : 0,49125% </td>
                                        <td >'.number_format((($total*0.49125)/100),2,',','').(($details['currency']=='pound') ? '&#163;' : '&#x80;' ).'</td>
                                        </tr>';
                                            
                    if($tax_date >= date("Y-m-d",strtotime('2012-06-22')))
                    {
                         $tax_details_pdf.='<tr>
                                            <td>Formation Professionnelle</td>
                                            <td>taux : 0,35% </td>
                                            <td>'.number_format((($total*0.35)/100),2,',','').(($details['currency']=='pound') ? '&#163;' : '&#x80;' ).'</td>
                                            </tr>';
                    }
                          $tax_details_pdf.='<tr class="alert alert-danger">
                                        <td></td>
										<td><strong>A VERSER A L\'AGESSA</strong></td>
                                        <td><strong>'.number_format($totalTax,2,',','').(($details['currency']=='pound') ? '&#163;' : '&#x80;' ).'</strong></td>
                                        </tr>';
                    $payinfo_number="N&deg; de  S&eacute;curit&eacute; sociale : ".$profile_contribinfo[0]['SSN']."<br>";
                }
                else if($invoiceDetails[0]['payment_info_type']=='comp_num' && $invoiceDetails[0]['vat_check']=='YES' )
                {
                    $TVA=number_format((($total*19.6)/100),2,'.','');
                    $totalTax=$TVA;
                    $tax_details_pdf='<tr>
                                        <td style="text-align:left" colspan="3"><strong>Pr&eacute;compte : Montant vers&eacute; pour vous par Edit-place</strong></td>                                        
                                        </tr>
                                        <tr>
                                        <td>TVA</td>
                                        <td>taux : 19,6%</td>
                                        <td >'.number_format((($total*19.6)/100),2,',','').(($details['currency']=='pound') ? '&#163;' : '&#x80;' ).'</td>
                                        </tr>
                                        <tr class="alert alert-info">
                                        <td></td>
										<td><strong>A VERSER A L\'AGESSA</strong></td>
                                        <td><strong>'.number_format($totalTax,2,',','').(($details['currency']=='pound') ? '&#163;' : '&#x80;' ).'</strong></td>
                                        </tr>';
                }
                if($invoiceDetails[0]['payment_info_type']=='comp_num')
                    $payinfo_number="Siret : ".$profile_contribinfo[0]['company_number']."<br>";
                $this->_view->totalTax=$totalTax;
                if($invoiceDetails[0]['payment_info_type']=='ssn')
                    $this->_view->FinaltotalInvoice=number_format(($total-$totalTax),2,'.','');
                else if($invoiceDetails[0]['payment_info_type']=='comp_num' && $profile_contribinfo[0]['vat_check']=='YES' )
                    $this->_view->FinaltotalInvoice=number_format(($total+$totalTax),2,'.','');
                else
                    $this->_view->FinaltotalInvoice=number_format($total,2,'.','');

               $final_invoice_amount='<tr>
                                        <td style="text-align:right;" colspan="2"><h4>Montant &agrave; verser &agrave; l\'auteur</h4></td>
                                        <td><h4>'.number_format($this->_view->FinaltotalInvoice,2,',','').(($details['currency']=='pound') ? '&#163;' : '&#x80;' ).'</h4></td>
                                        </tr>';
            }
            /**Wire OR paypal info**/
                $total_transfer_amount='';
                $bank_transfer_price='';
                       if($invoiceDetails[0]['payment_type']=="paypal")
                       {
                           $bank_charges=0;
                            $total_transfer_amount_final=$this->_view->FinaltotalInvoice+$bank_charges;
                           /* $total_transfer_amount='<tr>
                                                        <td>MONTANT FINAL</td>
                                                        <td>'.number_format($total_transfer_amount_final,2,',','').' &#x80;</td>
                                                        </tr>';*/
                           $remuneration="Paypal : ".$invoiceDetails[0]['payment_info_id']."<br>";
                           $mode="Mode de paiement : <strong>PAYPAL</strong> ";
                       }
                       else if($invoiceDetails[0]['payment_type']=="virement")
                       {
                            if($invoiceDetails[0]['payment_info_type']=='out_france')
                            {
                                $bank_charges=0;
                                $total_transfer_amount_final=$this->_view->FinaltotalInvoice+$bank_charges;
                               /* $total_transfer_amount='<tr>
                                                            <td>MONTANT FINAL</td>
                                                            <td>'.number_format($total_transfer_amount_final,2,',','').' &#x80;</td>
                                                            </tr>';*/

                            }
                           $remuneration="RIB : ".$invoiceDetails[0]['payment_info_id'];
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
               $this->_view->date_invoice_full = strftime("%e %B %Y",strtotime($invoiceDetails[0]['invoiceDate']));
                $this->_view->date_invoice = date("d/m/Y",strtotime($invoiceDetails[0]['invoiceDate']));
               $this->_view->address = $address;
               $this->_view->payinfo_number = $payinfo_number;
               $this->_view->date_invoice_ep = date("Y/m",strtotime($invoiceDetails[0]['invoiceDate']));
               $this->_view->invoice_identifier = $invoice_id;
               $this->_view->invoiceId_new = $invoiceId_new;
               $this->_view->remuneration = $remuneration;
               $this->_view->mode = $mode;
               $this->_view->total_transfer_amount = $total_transfer_amount;
               $this->_view->bank_transfer_price = $bank_transfer_price;

               $this->render(($_GET['print'] == "yes") ? "stats_invoiceprint" : "stats_invoice");
   }

    //////////pay the contributor /////////////////
    public function paycontributorAction()
    {
        $invoice_obj = new Ep_Payment_Invoice();
        $invoice_params=$this->_request->getParams();
        if(isset($invoice_params['invoice_id']))
        {
            $updated_at=date("Y-m-d %h:%i:%s");
            $data = array("status"=>'Paid',"updated_at"=>$updated_at);////////updating
            $query = "invoiceId= 'ep_invoice_".$invoice_params['invoice_id']."'";
            $invoice_obj->updateInvoice($data,$query);

            /**sending mail to contributor**/
            $royalty=new Ep_Payment_Royalties();
            $details=$royalty->getInvoiceDetails('ep_invoice_'.$invoice_params['invoice_id']);

            $parameters['invoice_id']=$invoice_params['invoice_id'];
            $this->messageToEPMail($details[0]['user_id'],67,$parameters);

            $this->_helper->FlashMessenger(utf8_decode("Paid succes."));
            $this->_redirect("/stats/contrib-payments-list?submenuId=ML5-SL1");
        }
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
            if($status=='Not paid')
            {				
                $refuse_count="refuse_count+1";
                $data = array("status"=>'refuse',"updated_at"=>$updated_at,"refuse_count"=> new Zend_Db_Expr('refuse_count+1'));////////updating
                $query = "invoiceId= 'ep_invoice_".$invoice_params['invoice_id']."'";
                $invoice_obj->updateInvoice($data,$query);

                /**sending mail to contributor**/
                $royalty=new Ep_Payment_Royalties();
                $details=$royalty->getInvoiceDetails('ep_invoice_'.$invoice_params['invoice_id']);

                $parameters['invoice_id']=$invoice_params['invoice_id'];
                $this->messageToEPMail($details[0]['user_id'],68,$parameters);

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

        $invoice_path=$royalty->getInvoicePDFPath($invoiceParams['invoiceid']);
        //echo $invoice_path;exit;
        if($invoice_path!='NOT EXIST')
        {
            $invoicePDFPath="/home/sites/site9/web/FO/invoice/".$invoice_path;
            //echo $invoicePDFPath;exit;
            if(file_exists($invoicePDFPath))
            {
                $attachment->downloadAttachment($invoicePDFPath,"attachment");
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
           $invoicePDFPath[$j]="/home/sites/site9/web/FO/invoice/".$invoice_path[$j];
           $files_array[$j]=$invoicePDFPath[$j];
        }
        $filename="/home/sites/site9/web/FO/invoice/zip/invoice_".rand(1000, 9999).".zip";
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

                    /**sending mail to contributor**/
                    $royalty=new Ep_Payment_Royalties();
                    $details=$royalty->getInvoiceDetails('ep_invoice_'.$invoiceids[$j]);

                    $parameters['invoice_id']=$invoiceids[$j];
                    $this->messageToEPMail($details[0]['user_id'],21,$parameters);

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
    
    /**Send mail to EP mail box**/
    public function messageToEPMail($receiverId,$mailid,$parameters)
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
        /**Inserting into EP mail Box**/
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
                    $object="Vous avez reçu un email-Edit-place";

                 $object=strip_tags($object);

                if($UserDetails[0]['type']=='client')
                {
                    $text_mail="<p>Cher client, ch&egrave;re  cliente,<br><br>
                                    Vous avez re&ccedil;u un  email d'Edit-place&nbsp;!<br><br>
                                    Merci de cliquer <a href=\"http://mmm-new.edit-place.com/user/email-login?user=".MD5('ep_login_'.$email)."&hash=".MD5('ep_login_'.$password)."&type=".$type."&message=".$messageId."&ticket=".$ticket_id."\">ici</a> pour le lire.<br><br>
                                    Cordialement,<br>
                                    <br>
                                    Toute l'&eacute;quipe d&rsquo;Edit-place</p>"
                                ;
                }
                else if($UserDetails[0]['type']=='contributor')
                {
                    $text_mail="<p>Cher contributeur,  ch&egrave;re contributrice,<br><br>
                                    Vous avez re&ccedil;u un  email d'Edit-place&nbsp;!<br><br>
                                    Merci de cliquer <a href=\"http://mmm-new.edit-place.com/user/email-login?user=".MD5('ep_login_'.$email)."&hash=".MD5('ep_login_'.$password)."&type=".$type."&message=".$messageId."&ticket=".$ticket_id."\">ici</a> pour le lire.<br><br>
                                    Cordialement,<br>
                                    <br>
                                    Toute l'&eacute;quipe d&rsquo;Edit-place</p>"
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
    }

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

        if(isset($paid_params['start_date']) || isset($paid_params['end_date']) || isset($paid_params['sel_type']) || isset($paid_params['invoicename']) || isset($paid_params['contribname']))
        {
            $start_date = $this->_request->getParam('start_date');
            $end_date = $this->_request->getParam('end_date');
            $sel_type = $this->_request->getParam('sel_type');
            $invoicename = $this->_request->getParam('invoicename');
            $contribname = $this->_request->getParam('contribname');

            $this->_view->sel_type=$sel_type;
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
            else if($start_date!='' && $end_date!='')
            {
                $start_date = str_replace('/','-',$start_date);
                $end_date = str_replace('/','-',$end_date);
                $start_date = date('Y-m-d', strtotime($start_date));
                $end_date = date('Y-m-d', strtotime($end_date));
                $where.= " AND DATE_FORMAT(i.created_at, '%Y-%m-%d')  BETWEEN '".$start_date."' AND '".$end_date."'";
            }
            $res= $invoice_obj->paidInvoices($where);
        }
        else{
            //$where = " 1=1";
            $res= $invoice_obj->paidInvoices($where);
        }
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

}