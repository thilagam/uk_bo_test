<?php
/**
 * Contract controller
 *
 * @author : Arun
 *
 * @version
 */
class ContractController extends Ep_Controller_Action
{
    public function init()
    {
        parent::init();
        $this->_view->lang = $this->_lang;
        $this->adminLogin = Zend_Registry::get('adminLogin');
		$this->spec_path="/home/sites/site8/web/BO/contract_spec/";		
        $this->article_path="/home/sites/site8/web/BO/contract_articles/";
        $this->xml_path="/home/sites/site8/web/BO/clients/";
        $this->sid = session_id();  
		$this->_view->dateformat = $this->getConfiguredval("timeformat_bo");
        $this->_view->paginationlimit = $this->getConfiguredval("pagination_bo");	
		if($this->_helper->FlashMessenger->getMessages()) {
	            $this->_view->actionmessages=$this->_helper->FlashMessenger->getMessages();	     
	    }	
    }
	 //Getting all contracts
    public function contractListAction()
    {                
        $contract_obj=new Ep_Contract_Contract();
        $contract_details=$contract_obj->getAllContracts();
        if($contract_details!='NO')
        {
            $this->_view->paginator = $contract_details;
        }       
        $this->_view->render("contract_list");
    }
	//create contract
    public function createContractAction()
    {

        $contract_obj=new Ep_Contract_Contract();
        //language array list
        $language_array=$this->_arrayDb->loadArrayv2("EP_LANGUAGES", $this->_lang);
        natsort($language_array);
        $this->_view->ep_language_list=$language_array;


        if($this->_request-> isPost())
        {
            $contract_params=$this->_request->getParams();

			if($contract_params['client_id'] && $contract_params['contract_name']  && $contract_params['contract_date'] && $contract_params['lang_id'])
			{
				$contract_obj->client_id=$contract_params['client_id'];
				$contract_obj->title=$contract_params['contract_name'];
				$contract_obj->contract_date=date("Y-m-d",strtotime($contract_params['contract_date']));
				$contract_obj->lang=$contract_params['lang_id'];
				$contract_obj->created_at= date("Y-m-d H:i:s", time());

				//update contract
				if($contract_params['contract_id'])
				{
					$contract_id=$contract_params['contract_id'];
					$contract_details=$contract_obj->getContractDetails($contract_id);
					if($contract_details!='Not Exists')
					{
						$contract_array=array("client_id"=>$contract_obj->client_id,"title"=>$contract_obj->title,
											  "contract_date"=>$contract_obj->contract_date,"lang"=>$contract_obj->lang
												);
						$contract_obj->updateContract($contract_array,$contract_id) ;
						$this->_helper->FlashMessenger("Contract Updated");
						$this->_redirect("/contract/contract-list?submenuId=ML7-SL1");
					}
				}


				//echo "<pre>";print_r($contract_obj) ;echo "</pre>";exit;
				try
				{
							$contract_obj->insert();
							$this->_helper->FlashMessenger("Contract Created");
							$this->_redirect("/contract/contract-list?submenuId=ML7-SL1");
				}
				catch(Zend_Exception $e)
				{
					$this->_view->error_msg =$e->getMessage();
					echo     $this->_view->error_msg;
					$this->render("contract_create");
					exit;

				}
			}
			else
			{
				$this->_helper->FlashMessenger("Please enter data in all required fields");
				$this->_helper->FlashMessenger("error");
				$this->_redirect("/contract/create-contract?submenuId=ML7-SL1");
				exit;
			}


        }
        elseif($_GET['action']=='edit' && $_GET['cid']!='')
        {
            $contract_id=$_GET['cid'];
            $contract_details=$contract_obj->getContractDetails($contract_id);
            if($contract_details!='Not Exists')
            {
                $this->_view->clientid=$contract_details[0]['client_id'];
                $this->_view->contract_name=$contract_details[0]['title'];
                $this->_view->contract_date=date("d-m-Y",strtotime($contract_details[0]['contract_date']));
                $this->_view->lang_id=$contract_details[0]['lang'];
                $this->_view->contract_id=$contract_id;

            }
        }
        $this->_view->render("contract_create");
    }
	 //Getting all Deliveries
    public function deliveryListAction()
    {        
        $delivery_obj=new Ep_Contract_Delivery();
        $delivery_details=$delivery_obj->getAllDeliveries();
        if($delivery_details!='NO')
        {
            $this->_view->paginator = $delivery_details;
        }        
        $this->_view->render("delivery_list");
    }    
    //delivery creation
    public function createDeliveryAction()
    {
        $contract_obj=new Ep_Contract_Contract();
        $delivery_obj=new Ep_Contract_Delivery();
        
        $get_editors=$contract_obj->getContacts('chiefeditor');

        /**Chief Editors List**/

        foreach($get_editors as $ceditor)
        {
            if(trim($ceditor['contact_name'])!=NULL && trim($ceditor['contact_name'])!='')
                $ceditor_list[$ceditor['identifier']]=$ceditor['contact_name'];

            else
            {
                $ceditor['email']=explode("@",$ceditor['email']);
                $ceditor_list[$contact['identifier']]=$ceditor['email'][0];

            }
        }
        if($ceditor_list!=='Not Exists')
            $this->_view->ceditor_list=$ceditor_list;

           //inserting Delivery
        if($this->_request-> isPost())
        {
            $delivery_params=$this->_request->getParams();
			
			if($delivery_params['client_id'] && $delivery_params['contract_id'] && $delivery_params['delivery_name'] && $delivery_params['delivery_date'] )
			{			
				/**Spec Files**/
				$spec_files='';
				if($_FILES['spec1']['name'] && $_FILES['spec1']['error']==UPLOAD_ERR_OK )
				{
					$tmp_name = $_FILES["spec1"]["tmp_name"];
					$file=pathinfo($_FILES["spec1"]["name"]);
					$name = str_replace(" ","_",$file['filename'])."_".$delivery_params['contract_id'];
					$extension=$file['extension'];
					$name=$name.".".$extension;
					$spec_files=$name;
					move_uploaded_file($tmp_name, $this->spec_path.$name);
				}
				if($_FILES['spec2']['name'] && $_FILES['spec2']['error']==UPLOAD_ERR_OK )
				{
					if($spec_files)
						$spec_files.='|';

					$tmp_name = $_FILES["spec2"]["tmp_name"];
					$file=pathinfo($_FILES["spec2"]["name"]);
					$name = str_replace(" ","_",$file['filename'])."_".$delivery_params['contract_id'];
					$extension=$file['extension'];
					$name=$name.".".$extension;
					$spec_files.=$name;
					move_uploaded_file($tmp_name, $this->spec_path.$name);
				}


				$delivery_obj->client_id=$delivery_params['client_id'];
				$delivery_obj->contract_id=$delivery_params['contract_id'];
				$delivery_obj->title=$delivery_params['delivery_name'];
				$delivery_obj->delivery_date=date("Y-m-d",strtotime($delivery_params['delivery_date']));
				$delivery_obj->chief_editor=$delivery_params['editor_id'];
				$delivery_obj->spec_file_path=$spec_files;
				$delivery_obj->created_at= date("Y-m-d H:i:s", time());

				//echo "<pre>";print_r($delivery_obj) ;echo "</pre>";exit;

				//update delivery
				if($delivery_params['delivery_id'])
				{
					$delivery_id=$delivery_params['delivery_id'];
					$delivery_details=$delivery_obj->getDeliveryDetails($delivery_id);
					if($delivery_details!='NO')
					{
						if(!$delivery_obj->spec_file_path)
							$delivery_obj->spec_file_path=$delivery_details[0]['spec_file_path'];
						$delivery_array=array("client_id"=>$delivery_obj->client_id,"title"=>$delivery_obj->title,
							"contract_id"=>$delivery_obj->contract_id,"chief_editor"=>$delivery_obj->chief_editor,
							"delivery_date"=>$delivery_obj->delivery_date,"spec_file_path"=>$delivery_obj->spec_file_path
						);
						$delivery_obj->updateDelivery($delivery_array,$delivery_id) ;
						$this->_helper->FlashMessenger("Delivery Updated");
						$this->_redirect("/contract/delivery-list?submenuId=ML7-SL2");
						exit;
					}
				}


				try
				{
					$delivery_obj->insert();
					$this->_helper->FlashMessenger("Delivery Created");
					$this->_redirect("/contract/delivery-list?submenuId=ML7-SL2");
				}
				catch(Zend_Exception $e)
				{
					$this->_view->error_msg =$e->getMessage();
					echo     $this->_view->error_msg;
					$this->render("delivery_create");
					exit;

				}
			}
			else
			{
				$this->_helper->FlashMessenger("Please enter data in all required fields");
				$this->_helper->FlashMessenger("error");
				$this->_redirect("/contract/create-delivery?submenuId=ML7-SL2");
				exit;
			}

        }
        if($_GET['action']=='edit' && $_GET['did']!='')
        {
            $delivery_id=$_GET['did'];
            $delivery_details=$delivery_obj->getDeliveryDetails($delivery_id)   ;
            if($delivery_details!='NO')
            {
                $this->_view->clientid=$delivery_details[0]['client_id'];
                $this->_view->contract_id=$delivery_details[0]['contract_id'];
                $this->_view->delivery_name=$delivery_details[0]['title'];
                $this->_view->delivery_date=date("d-m-Y",strtotime($delivery_details[0]['delivery_date']));
                $this->_view->editorId=$delivery_details[0]['chief_editor'];
                $this->_view->delivery_id=$delivery_details[0]['id'];


                $clientId=$delivery_details[0]['client_id'];
                $contract_list=$contract_obj->getContractList($clientId);
                $options='';
                if($contract_list!='Not Exists')
                {
                    foreach($contract_list as $contract)
                    {
                        if($contract['id']==$delivery_details[0]['contract_id'])
                                $select=' selected';
                        else
                                $select='';

                        $options.='<option value="'.$contract['id'].'" '.$select.'>'.$contract['title'].'</option>';
                    }

                    $this->_view->contract_list=$options;
                }

                if($delivery_details[0]['spec_file_path'])
                {
                    $spec_files=explode("|",$delivery_details[0]['spec_file_path']);

                    if($spec_files[0])
                    {

                        if(file_exists($this->spec_path.$spec_files[0]))
                        {
                           $this->_view->spec1="exists";
                        }
                    }
                    if($spec_files[1])
                    {

                        if(file_exists($this->spec_path.$spec_files[1]))
                        {
                            $this->_view->spec2="exists";
                        }
                    }
                }



            }
        }

        $this->_view->render("delivery_create");
    }
	//Getting all Articles
    public function articleListAction()
    {        
        $article_obj=new Ep_Contract_Article();
        $article_details=$article_obj->getAllArticles();

        if($article_details!='NO')
        {
            $this->_view->paginator = $article_details;
        }

        $this->_view->pagelimit=$this->getConfiguredval('pagination_bo');
        $this->_view->render("article_list");
    }
    public function createArticleAction()
    {

        $contract_obj=new Ep_Contract_Contract();
        $delivery_obj=new Ep_Contract_Delivery();
        $article_obj=new Ep_Contract_Article();
        $get_clients=$contract_obj->getContacts('client');        

        $categories_array=$this->_arrayDb->loadArrayv2("EP_ARTICLE_CATEGORY", $this->_lang);
        natsort($categories_array);
        $this->_view->ep_categories_list=$categories_array;


        $types_array=$this->_arrayDb->loadArrayv2("EP_ARTICLE_TYPE", $this->_lang);
        natsort($types_array);
        $this->_view->ep_type_list=$types_array;


        if(($_GET['action']=='edit' && $_GET['aid']!='') ||($_GET['action']=='clone' && $_GET['aid']!=''))
        {
           $article_id=$_GET['aid'];
            $article_details=$article_obj->getArticleDetails($article_id)   ;
            if($article_details!='Not Exists')
            {
                $this->_view->clientid=$article_details[0]['client_id'];
                $this->_view->contract_id=$article_details[0]['contract_id'];
                $this->_view->article_name=$article_details[0]['title'];
                $this->_view->type=$article_details[0]['type'];
                $this->_view->category=$article_details[0]['category'];
                $this->_view->words=$article_details[0]['words'];
                $this->_view->author=$article_details[0]['author'];
                if($_GET['action']=='edit')
                    $this->_view->article_id=$article_details[0]['id'];
                $this->_view->delivery_date=date("d-m-Y",strtotime($article_details[0]['delivery_date']));


                $clientId=$article_details[0]['client_id'];
                $delivery_list=$delivery_obj->getDeliveryList($clientId);
                $options='';
                if($delivery_list!='NO')
                {
                    foreach($delivery_list as $delivery)
                    {
                        if($delivery['id']==$article_details[0]['delivery_id'])
                            $select=' selected';
                        else
                            $select='';

                        $options.='<option value="'.$delivery['id'].'" '.$select.'>'.$delivery['title'].'</option>';
                    }

                    $this->_view->delivery_list=$options;
                }
                if($article_details[0]['article_path'])
                {

                        if(!is_dir($this->article_path.$article_details[0]['article_path']) && file_exists($this->article_path.$article_details[0]['article_path']))
                        {
                            if($_GET['action']=='edit')
                                $this->_view->article_path="exists";
                        }
                }

            }
        }



        $this->_view->render("article_create");
    }
    public function saveArticleAction()
    {
        $contract_obj=new Ep_Contract_Contract();
        $delivery_obj=new Ep_Contract_Delivery();
        $article_obj=new Ep_Contract_Article();
        //inserting Article
        if($this->_request-> isPost())
        {
            $article_params=$this->_request->getParams();

            if($article_params['client_id'] && $article_params['delivery_id'] && $article_params['article_name'] 
                && $article_params['type']  && $article_params['category']  && $article_params['words'] && $article_params['author']
                && $article_params['delivery_date']
                 )
            {
                $article_obj->client_id=$article_params['client_id'];
                $article_obj->delivery_id=$article_params['delivery_id'];
                $article_obj->title=$article_params['article_name'];
                $article_obj->type=$article_params['type'];
                $article_obj->category=$article_params['category'];
                $article_obj->words=$article_params['words'];
                $article_obj->author=$article_params['author'];
                $article_obj->created_at= date("Y-m-d H:i:s", time());
                $article_obj->delivery_date=date("Y-m-d",strtotime($article_params['delivery_date']));

                $rand=rand(100,99999);
                if($_FILES['upload_article']['name'] && $_FILES['upload_article']['error']==UPLOAD_ERR_OK )
                {
                    $tmp_name = $_FILES["upload_article"]["tmp_name"];
                    $file=pathinfo($_FILES["upload_article"]['name']);
                    $extension=$file['extension'];
                    $name=str_replace(" ","_",$file['filename'])."_".$rand.".".$extension;


                    $articleDir=$this->article_path.$article_params['delivery_id']."/";

                    if(!is_dir($articleDir))
                        mkdir($articleDir,TRUE);
                    chmod($articleDir,0777);



                    $article_path=$articleDir.$name;



                    if(move_uploaded_file($tmp_name, $article_path))
                    {
                      $article_obj->article_path=$article_obj->delivery_id."/".$name;
                      $path_array["article_path"]=$article_obj->delivery_id."/".$name;

                    }

                    chmod($article_path,0777);
                }

                //echo "<pre>";print_r($article_obj) ;echo "</pre>";exit;
                //update article
                if($article_params['article_id'])
                {
                    $article_id=$article_params['article_id'];
                    $article_details=$article_obj->getArticleDetails($article_id);
                    //print_r($article_details);
                    //exit;
                    if($article_details!='Not Exists')
                    {
                        $article_array=array("client_id"=>$article_obj->client_id,"title"=>$article_obj->title,
                            "delivery_id"=>$article_obj->delivery_id,"type"=>$article_obj->type,
                            "category"=>$article_obj->category,"words"=>$article_obj->words,
                            "delivery_date"=>$article_obj->delivery_date,
                            "author"=>$article_obj->author
                        );
                        $article_obj->updateArticle($article_array,$article_id) ;
                        if($_FILES['upload_article']['name'] && $_FILES['upload_article']['error']==UPLOAD_ERR_OK )
                        {
                            $article_obj->updateArticle($path_array,$article_id) ;
                        }
                        $this->_helper->FlashMessenger("Article Updated");
                        $this->_redirect("/contract/article-list?submenuId=ML7-SL3");
                        exit;
                    }
                }

                try
                {
                    $article_id=$article_obj->insert();
                    //echo $article_id;


                    $this->_helper->FlashMessenger("Article Created");
                    $this->_redirect("/contract/article-list?submenuId=ML7-SL3");
                }
                catch(Zend_Exception $e)
                {
                    $this->_view->error_msg =$e->getMessage();
                    echo     $this->_view->error_msg;
                    $this->render("article_create");
                    exit;

                }
            }
            else
            {
                $this->_helper->FlashMessenger("Please enter data in all required fields");
                $this->_helper->FlashMessenger("error");
                $this->_redirect("/contract/create-article?submenuId=ML7-SL3");
                exit;
            }    


        }
        else{
            $this->_redirect("/contract/create-article?submenuId=ML7-SL3");
        }
    }

    //getting contract list through ajax
    public function  ajaxGetContractsAction()
    {
        $ajax_params=$this->_request->getParams();
        if($ajax_params['clientId'])
        {
            $contract_obj=new Ep_Contract_Contract();

            $clientId=$ajax_params['clientId'];
            $contract_list=$contract_obj->getContractList($clientId);
            $options='<select name="contract_id" id="contract_list" data-placeholder="S&eacute;lectionner contract"><option value=""></option>';
            if($contract_list!='NO')
            {
                   foreach($contract_list as $contract)
                   {
                        $options.='<option value="'.$contract['id'].'">'.$contract['title'].'</option>';
                   }
                 $options.='</select>';
                    echo $options;
            }
            else{
                    echo trim('NO');
					exit;
            }

        }
    }
    //getting Delivery list through ajax
    public function  ajaxGetDeliveryAction()
    {
        $ajax_params=$this->_request->getParams();
        if($ajax_params['clientId'])
        {
            $delivery_obj=new Ep_Contract_Delivery();

            $clientId=$ajax_params['clientId'];
            $delivery_list=$delivery_obj->getDeliveryList($clientId);
            $options='<select name="delivery_id" id="delivery_list" data-placeholder="S&eacute;lectionner delivery"><option value=""></option>';
            if($delivery_list!='NO')
            {

                foreach($delivery_list as $delivery)
                {
                    $options.='<option value="'.$delivery['id'].'">'.$delivery['title'].'</option>';
                }
                $options.='</select><span id="delivery_err"></span>';
                echo $options;
            }
            else{
                echo 'NO';
            }

        }
    }   
   
    //Fetching Configuration
    public function getConfiguredval($constraint=NULL)
    {
        $conf_obj=new Ep_Delivery_Configuration();
        $conresult=$conf_obj->getConfiguration($constraint);
        return $conresult;
    }
    public function downloadFileAction()
    {
        $attachment=new Ep_Message_Attachment();
        $delivery_obj=new Ep_Contract_Delivery();
        $article_obj=new Ep_Contract_Article();

        $params=$this->_request->getParams();

        if($params['type'] && $params['type']=='spec' && $params['did'])
        {
            $delivery_id=$params['did'];
            $index=$params['index']-1;
            $delivery_details=$delivery_obj->getDeliveryDetails($delivery_id);
            if($delivery_details[0]['spec_file_path'])
            {
                $spec_files=explode("|",$delivery_details[0]['spec_file_path']);

                if($spec_files[$index])
                {

                    if(file_exists($this->spec_path.$spec_files[$index]))
                    {
                        $attachment->downloadAttachment($this->spec_path.$spec_files[$index]);
                    }
                }
                if($spec_files[0])
                {

                    if(file_exists($this->spec_path.$spec_files[0]))
                    {
                        $attachment->downloadAttachment($this->spec_path.$spec_files[$index]);
                    }
                }
            }
        }
       elseif($params['type'] && $params['type']=='article' && $params['aid'])
        {
            $article_id=$params['aid'];

            $article_details=$article_obj->getArticleDetails($article_id);
            if($article_details[0]['article_path'])
            {

                if(!is_dir($this->article_path.$article_details[0]['article_path']) && file_exists($this->article_path.$article_details[0]['article_path']))
                {
                    $attachment->downloadAttachment($this->article_path.$article_details[0]['article_path']);
                }
            }
        }
        elseif ($params['type'] && $params['type']=='zip' && $params['did'])
        {
            $delivery_id=$params['did'];
            $delivery_details=$delivery_obj->getDeliveryDetails($delivery_id);
            if($delivery_details[0]['zip_file_path'])
            {
                   if(file_exists($this->xml_path.$delivery_details[0]['zip_file_path']) && !is_dir($this->xml_path.$delivery_details[0]['zip_file_path']))
                    {
                        $attachment->downloadAttachment($this->xml_path.$delivery_details[0]['zip_file_path']);
                    }

            }
        }

    }
    /**function to get the Article type name**/
    public function getArticleTypeName($type_value)
    {
        $article_array=$this->_arrayDb->loadArrayv2("EP_ARTICLE_TYPE", $this->_lang);
        return $article_array[$type_value];
    }
    /**function to get the category type name**/
    public function getCategoryName($value)
    {
        $category_array=$this->_arrayDb->loadArrayv2("EP_ARTICLE_CATEGORY", $this->_lang);
        return $category_array[$value];
    }
    //Delivery info
    public function viewDeliveryAction()
    {
        $contract_obj=new Ep_Contract_Contract();
        $delivery_obj=new Ep_Contract_Delivery();
        $article_obj=new Ep_Contract_Article();
        $get_clients=$contract_obj->getContacts('client');
        $get_editors=$contract_obj->getContacts('chiefeditor');
        $delivery_params=$this->_request->getParams();

        /**client List**/
        foreach($get_clients as $contact)
        {
            if(trim($contact['contact_name'])!=NULL && trim($contact['contact_name'])!='')
                $client_list[$contact['identifier']]=$contact['contact_name'];

            else
            {
                $contact['email']=explode("@",$contact['email']);
                $client_list[$contact['identifier']]=$contact['email'][0];

            }
        }
        if($client_list!=='Not Exists')
            $this->_view->client_list=$client_list;

        /**Chief Editors List**/

        foreach($get_editors as $ceditor)
        {
            if(trim($ceditor['contact_name'])!=NULL && trim($ceditor['contact_name'])!='')
                $ceditor_list[$ceditor['identifier']]=$ceditor['contact_name'];

            else
            {
                $ceditor['email']=explode("@",$ceditor['email']);
                $ceditor_list[$contact['identifier']]=$ceditor['email'][0];

            }
        }
        if($ceditor_list!=='Not Exists')
            $this->_view->ceditor_list=$ceditor_list;


        if($delivery_params['info']=='view' && $delivery_params['did']!='')
        {
            $delivery_id=$delivery_params['did'];
            $delivery_details=$delivery_obj->getDeliveryDetails($delivery_id)   ;
            if($delivery_details!='NO')
            {
                $this->_view->client_id=$delivery_details[0]['client_id'];
                $this->_view->client=$client_list[$delivery_details[0]['client_id']];
                $this->_view->contract_id=$delivery_details[0]['contract_id'];
                $this->_view->delivery_name=$delivery_details[0]['title'];
                $this->_view->delivery_date=$delivery_details[0]['delivery_date'];
                $this->_view->editorId=$delivery_details[0]['chief_editor'];
                $this->_view->chiefEditor= $ceditor_list[$delivery_details[0]['chief_editor']];
                $this->_view->delivery_id=$delivery_details[0]['id'];
                $this->_view->status=$delivery_details[0]['status'];



                $clientId=$delivery_details[0]['client_id'];
                $contract_list=$contract_obj->getContractList($clientId);
                $options='';
                if($contract_list!='Not Exists')
                {
                    foreach($contract_list as $contract)
                    {
                        if($contract['id']==$delivery_details[0]['contract_id'])
                            $this->_view->contract_name=$contract['title'];

                    }
                }

                if($delivery_details[0]['spec_file_path'])
                {
                    $spec_files=explode("|",$delivery_details[0]['spec_file_path']);

                    if($spec_files[0])
                    {

                        if(file_exists($this->spec_path.$spec_files[0]))
                        {
                            $this->_view->spec1="exists";
                        }
                    }
                    if($spec_files[1])
                    {

                        if(file_exists($this->spec_path.$spec_files[1]))
                        {
                            $this->_view->spec2="exists";
                        }
                    }
                }
                if($delivery_details[0]['zip_file_path'])
                {
                    if(file_exists($this->xml_path.$delivery_details[0]['zip_file_path']) && !is_dir($this->xml_path.$delivery_details[0]['zip_file_path']))
                    {
                        $this->_view->zip_xml="exists";
                    }
                }


                /**Get Article Info**/
                $article_details=$article_obj->getArticleList($delivery_id);

                if($article_details!='Not Exists')
                {
                    $cnt=0;
                    foreach($article_details as $article)
                    {
                        if($article['article_path']!=NULL)
                        {

                            if(!is_dir($this->article_path.$article['article_path']) && file_exists($this->article_path.$article['article_path']))
                            {
                                $article_details[$cnt]['article_exist']="exists";
                            }
                            else{

                                $article_details[$cnt]['article_exist']="not exists";
                            }
                        }
                        else
                        {
                            $article_details[$cnt]['article_exist']="not exists";
                        }
                        if($article['xml_path']!=NULL)
                        {

                            if(!is_dir($article['xml_path']) && file_exists($article['xml_path']))
                            {
                                $article_details[$cnt]['xml_exist']="exists";
                            }
                            else{

                                $article_details[$cnt]['xml_exist']="not exists";
                            }
                        }
                        else
                        {
                            $article_details[$cnt]['xml_exist']="not exists";
                        }
                        $cnt++;

                    }
                }
                else{
                    $article_details=array();
                }
                $this->_view->articleDetails=$article_details;


            }
           else {
                $this->_redirect("/contract/delivery-list?submenuId=ML7-SL2");
            }
        }
        else{
            $this->_redirect("/contract/delivery-list?submenuId=ML7-SL2");
        }

        $this->_view->render("view_delivery");
    }
    public function xmlCreationPopupAction()
    {
        $contract_obj=new Ep_Contract_Contract();
        $delivery_obj=new Ep_Contract_Delivery();
        $article_obj=new Ep_Contract_Article();
        $get_clients=$contract_obj->getContacts('client');
        $get_editors=$contract_obj->getContacts('chiefeditor');
        $delivery_params=$this->_request->getParams();
         $delivery_id=$delivery_params['deliveryId'];
        /**Get Article Info**/
        $article_details=$article_obj->getArticleList($delivery_id);
        if($article_details!='Not Exists')
        {
            $cnt=0;
            foreach($article_details as $article)
            {
                if($article['article_path']!=NULL)
                {

                    if(!is_dir($this->article_path.$article['article_path']) && file_exists($this->article_path.$article['article_path']))
                    {
                        $article_details[$cnt]['article_exist']="exists";
                    }
                    else{

                        $article_details[$cnt]['article_exist']="not exists";
                    }
                }
                else
                {
                    $article_details[$cnt]['article_exist']="not exists";
                }
                $article_details[$cnt]['category']=$this->getCategoryName($article['category']);
                $cnt++;

            }
            $this->_view->delivery_id=$delivery_id;
        }
        $this->_view->articleDetails=$article_details;

        $this->_view->render("createxml_popup");
    }
    public function createXmlAction()
    {
        if($this->_request-> isPost())
        {
            $contract_obj=new Ep_Contract_Contract();
            $delivery_obj=new Ep_Contract_Delivery();
            $article_obj=new Ep_Contract_Article();
            /**client List**/
            $get_clients=$contract_obj->getContacts('client');
            foreach($get_clients as $contact)
            {
                   $contact['email']=explode("@",$contact['email']);
                   $client_list[$contact['identifier']]=$contact['email'][0];
            }

            $xml_params=$this->_request->getParams();

            $delivery_id=$xml_params['delivery_id'];
            /**Get Article Info**/
            $article_details=$article_obj->getArticleList($delivery_id);
            $delivery_details=$delivery_obj->getDeliveryDetails($delivery_id);
            $xml_paths=array();

            if($article_details!='Not Exists' && $delivery_id )
            {
                $cnt=0;
                foreach($article_details as $article)
                {
                    $aid=$article['id'];
                    $article['summary']=$summary=($xml_params["summary_".$aid]);
                    $article['content']= $content=($xml_params["content_".$aid]);
                    $article['client']=$client=$client_list[$article['client_id']];

                    /**update summary and content in article table**/
                    $article_array=array(
                        "summary"=>$summary,"content"=>$content
                    );
                    $article_obj->updateArticle($article_array,$aid) ;

                    /**Create XML File**/
                    $xml_path=$this->writeXML($article);
                   $xml_paths[]= $xml_path;

                    /**update summary and content in article table**/
                    $article_array=array(
                        "xml_path"=>$xml_path
                    );
                    $article_obj->updateArticle($article_array,$aid) ;


                    $cnt++;



                }

                if(count($xml_paths)>0)
                {
                    $zip_path=$this->xml_path.$article['client']."/zip/";
                    if(!is_dir($zip_path))
                        mkdir($zip_path,TRUE);
                    chmod($zip_path,0777);

                    $delivery_title=str_replace(" ","_",$delivery_details[0]['title']);
                    $zip_file=$delivery_title.".zip";

                    $filename=$zip_path.$zip_file;

                    $update_delivery=array("zip_file_path"=>$article['client']."/zip/".$zip_file)  ;
                    $delivery_obj->updateDelivery($update_delivery,$delivery_id)  ;

                    $result = $this->create_zip($xml_paths,$filename);
                    if($result)
                    {
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
                }

                //$this->_redirect("/contract/view-delivery?submenuId=ML7-SL2&info=view&did=".$delivery_id);
                exit;

            }
            else {
                $this->_redirect("/contract/view-delivery?submenuId=ML7-SL2&info=view&did=".$delivery_id);
            }
            exit;
        }
    }
    public function writeXML($article)
    {
        $article['date'] =date("d/m/Y",strtotime($article['delivery_date']));
        $year=date("Y",strtotime($article['delivery_date']));
        $month=date("m",strtotime($article['delivery_date']));
        $day=date("d",strtotime($article['delivery_date']));
        $client_name= $article['client'];


        $xml_dir=$this->xml_path.$client_name."/".$year."/".$month;
        $xml_file=$day.".".$article['id'].".xml";

        $xmldoc=$xml_dir."/".$xml_file;

        if(!is_dir($xml_dir))
             mkdir($xml_dir, 0777,TRUE);
         chmod($xml_dir,0777);

        if($fp=fopen($xmldoc,'w'))
        {
            /*fwrite($fp,"<?xml version='1.0' encoding='ISO-8859-1' ?>\n"); */
            fwrite($fp,"<?xml version='1.0' encoding='UTF-8' ?>\n");
            fwrite($fp,"<articles>\n");
            fwrite($fp,"<article>\n");
            fwrite($fp,"<id>".$article['id']."</id>\n");
            fwrite($fp,"<category><![CDATA[ ".$this->_convert($this->getCategoryName($article['category']))."]]></category>\n");
            fwrite($fp,"<title><![CDATA[ ".$this->_convert($article['title'])."]]></title>\n");
            fwrite($fp,"<date> ".$article['date']."</date>\n");
            fwrite($fp,"<author><![CDATA[<a href='http://www.edit-place.com/'>Edit-Place</a>]]></author>\n");
            fwrite($fp,"<summary><![CDATA[ ".$this->_convert($article['summary'])."]]></summary>\n");
            fwrite($fp,"<content><![CDATA[ ".$this->_convert($article['content'])." ]]></content>\n");
            fwrite($fp,"</article>\n");
            fwrite($fp,"</articles>");
            fclose($fp);
            chmod(@$xmldoc,0777);
        }
        return $xmldoc;

    }

    public function _convert($string)
    {
        $string=stripslashes($string);
        $string=str_replace("’","'",$string);
        $string=utf8_encode($string);
        return $string;
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
    public function parseXmlAction()
    {
        $xml_params=$this->_request->getParams();
        $articleId=$xml_params['aid'];
        if($articleId)
        {
            $article_obj=new Ep_Contract_Article();
            $article_details=$article_obj->getArticleDetails($articleId);
            if($article_details!='Not Exists')
            {
                $xml_path=$article_details[0]['xml_path'];
                if(file_exists($xml_path))
                {
                    $this->XMLParser($xml_path);
                }
                else{
                    $this->_redirect("/contract/delivery-list?submenuId=ML7-SL2");
                }
            }
        }
        else{
            $this->_redirect("/contract/delivery-list?submenuId=ML7-SL2");
        }
    }
    public function XMLParser($file)
    {
        header ('Content-type: text/html; charset=utf-8');
         //Initialize the XML parser
           $parser=xml_parser_create();

            //Specify element handler
            xml_set_element_handler($parser,array(&$this,"start"),array(&$this,"stop"));
            //Specify data handler
            xml_set_character_data_handler($parser,array(&$this,"char"));

                 //Open XML file
            $fp=fopen($file,"r");

        //Read data
            while ($data=fread($fp,4096))
            {
                 //$data=utf8_encode($data);
                xml_parse($parser,$data,feof($fp)) or
                die (sprintf("XML Error: %s at line %d",
                xml_error_string(xml_get_error_code($parser)),
                xml_get_current_line_number($parser)));
            }


        //Free the XML parser
                xml_parser_free($parser);
    }
    //Function to use at the start of an element
    public function start($parser,$element_name,$element_attrs)
    {
        echo "<b><u>$element_name </u></b>: ";
        switch($element_name)
        {
            case "NOTE":
                echo "-- Note --<br />";
                break;
            case "TO":
                echo "To: ";
                break;
            case "FROM":
                echo "From: ";
                break;
            case "HEADING":
                echo "Heading: ";
                break;
            case "BODY":
                echo "Message: ";
        }
    }

//Function to use at the end of an element
   public function stop($parser,$element_name)
    {
        echo "<br />";
    }
    //Function to use when finding character data
    public function char($parser,$data)
    {
        echo $data;
    }

    //update validated status in delivery
    public function updateStatusAction()
    {

        if($this->_request-> isPost())
        {
            $delivery_obj=new Ep_Contract_Delivery();
            $validate_params=$this->_request->getParams();
            if($validate_params['deliveryId'])
            {
                $delivery_id=$validate_params['deliveryId'];
                $validate_array=array("status"=>"validated");
                $delivery_obj->updateDelivery($validate_array,$delivery_id);
                echo '<span class="label label-success">Validated</span>';
                exit;
            }
        }
    }



}