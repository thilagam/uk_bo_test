<?php

class TemplateController extends Ep_Controller_Action
{
	private $text_admin;
	public function init()
	{
		parent::init();
		$this->_view->lang = $this->_lang;
		$this->adminLogin = Zend_Registry::get('adminLogin');
        $this->sid = session_id();
        ////////reading the file of dynamic text to distplay//////////////////
        $data = file_get_contents('/home/sites/site4/web/BO/documents/dynamic.txt');
        $data = utf8_encode($data);
        $data = json_decode($data, true);
        for($i=0; $i<count($data); $i++)
        {
            $this->nodes[$i] = utf8_decode($data[$i]);
        }
        $this->_view->nodes = $this->nodes;
    }
   
    /******************************************* Validation Templates **************************************/
	public function validationtemplatesAction()
	{
        $template_obj = new Ep_Message_Template();
        
		//Writing
		$reswriting= $template_obj->validationTemplates('redaction');
		if($reswriting!="NO")
			$this->_view->writingtemplates = $reswriting;
		
		//Translation
		$restranslation= $template_obj->validationTemplates('translation');
		if($restranslation!="NO")
			$this->_view->translationtemplates = $restranslation;
			
		$this->_view->render("template_validationtemplates");
	}
	
	// add or edit validation templates
	public function addvalidationtemplateAction()
	{
        $template_obj = new Ep_Message_Template();
        $valTempParams=$this->_request->getParams();
        
		//details for edit validation templates
		if($valTempParams['valtempId']!='new')
            $this->_view->valTempDetails=$template_obj->getValTempDetails($valTempParams['valtempId']);
		
       $this->render("template_validtemppopup");
    }

	// Saving validation templates
    public function savevalidationtemplateAction()
    {
        $prevurl = getenv("HTTP_REFERER");
        $template_obj = new Ep_Message_Template();
		$templateParams=$this->_request->getParams(); 
        
		if($templateParams['templateid'] != '')
        {
            //Update validation templates
			$data = array("templatetype"=>$templateParams["templatetype"], 
							"type"=>$templateParams["type"], 
							"title"=>$templateParams["title"],
                            "content"=>$templateParams["content"], 
							"active"=>$templateParams["active"]);
            $query = "identifier= '".$templateParams['templateid']."'";
			$template_obj->updateTemplate($data,$query);
			
            $this->_redirect($prevurl);
        }
        else if($templateParams['valtempId'] == '')
        {
            //Insert new validation templates
             $template_obj->templatetype=$templateParams["templatetype"] ;
             $template_obj->type=$templateParams["type"] ;
             $template_obj->maintype='validation' ;
             $template_obj->title=$templateParams["title"] ;
             $template_obj->content=$templateParams["content"];
             $template_obj->active=$templateParams["active"] ;
             $template_obj->insert();
             
			 $this->_redirect($prevurl);
        }
    }
	
	public function defaultchecktemplateAction()
	{
		$template_obj = new Ep_Message_Template();
		$checktemplateParams=$this->_request->getParams(); 
		
		$data = array("selected"=>$checktemplateParams["checked"]);
        $query = "identifier= '".$checktemplateParams['templateid']."'";
		$template_obj->updateTemplate($data,$query);
	}
	
    /////////displays the validation template list//////////////
	public function emailtemplatesAction()
	{
        $template_obj = new Ep_Message_Template();
        $res= $template_obj->emailTemplates();
		if($res!="NO")
		{
			$this->_view->paginator = $res;
		}
		else
		{
			$this->_view->nores = "true";
		}
		$this->_view->render("template_emailtemplates");
	}

   /////////displays the validation template list//////////////
	public function validtemplatedetailsAction()
	{
        $template_obj = new Ep_Message_Template();
        $valTempParams=$this->_request->getParams();
        if($valTempParams['valtempId']!='new')
        {
            $tempIdentifier=$valTempParams['valtempId'];
            $valTempDetails=$template_obj->getValTempDetails($tempIdentifier);
            ///////////////////////////////////////////
            //$this->_view->emailTempDetails=$emailTempDetails;
            $this->_view->text = utf8_encode(stripslashes(strip_tags($valTempDetails[0]['content'])));
            $this->_view->title =  utf8_encode(stripslashes(strip_tags($valTempDetails[0]['title'])));
            //$this->_view->text = $emailTempDetails[0]['subject'];
            //$this->_view->status =  $emailTempDetails[0]['parameters'];
            $this->_view->identifier =  $valTempDetails[0]['identifier'];
            $this->_view->type =  $valTempDetails[0]['type'];
            $this->_view->status =  $valTempDetails[0]['active'];
            $this->render("template_validtemppopup");
            /////////////////////////////////////////////
        }
        else
         $this->render("template_validtemppopup");
       // echo json_encode($valTempDetails);
	}

   //////////when a validation template is updated through the popup/////////////////
    public function editvalidationtemplateAction()
    {
        $prevurl = getenv("HTTP_REFERER");
        $template_obj = new Ep_Message_Template();
        $valTempParams=$this->_request->getParams();  // print_r($valTempParams); exit;
        $template_id = $valTempParams['valtempId'];
        if($valTempParams['valtempId'] != '')
        {
           ////udate teplate table for changes///////
            $template_obj->type=$valTempParams["valtemp_type"] ;
            $template_obj->maintype=$valTempParams["valtemp_maintype"] ;
            $template_obj->title=$valTempParams["valtemp_title"] ;
            $template_obj->content=$valTempParams["valtemp_content"] ;
            $template_obj->active=$valTempParams["valtemp_active"] ;


            $data = array("type"=>$template_obj->type, "title"=>$template_obj->title,
                                    "content"=>$template_obj->content, "active"=>$template_obj->active);
            $query = "identifier= '".$template_id."'";

            $template_obj->updateTemplate($data,$query);
            $this->_redirect($prevurl);
        }
        else if($valTempParams['valtempId'] == '')
        {
            ////Add new teplate to the template tablechanges///////
             $template_obj->type=$valTempParams["valtemp_type"] ;
             $template_obj->maintype='validation' ;
             $template_obj->title=$valTempParams["valtemp_title"] ;
             $template_obj->content=$valTempParams["valtemp_content"];
             $template_obj->active=$valTempParams["valtemp_active"] ;
             $template_obj->insert();
             $this->_redirect($prevurl);
        }
    }

   ////////displays the email template list//////////////
	public function emailtemplatedetailsAction()
	{
        $template_obj = new Ep_Message_Template();
        $emailTempParams=$this->_request->getParams();
        if($emailTempParams['emailtempId']!='new')
        {
            $tempIdentifier=$emailTempParams['emailtempId'];
            $emailTempDetails=$template_obj->getEmailTempDetails($tempIdentifier);
            $this->_view->emailTempDetails=$emailTempDetails;
            $this->_view->msg = utf8_encode(stripslashes(strip_tags($emailTempDetails[0]['content'])));
            $this->_view->title =  $emailTempDetails[0]['title'];
            $this->_view->object = $emailTempDetails[0]['subject'];
            $this->_view->params =  $emailTempDetails[0]['parameters'];
            $this->_view->identifier =  $emailTempDetails[0]['identifier'];
            $this->_view->type =  $emailTempDetails[0]['type'];
            $this->_view->active =  $emailTempDetails[0]['active'];
            $this->render("template_emailtemppopup");
        }
        else if($emailTempParams['emailtempId']=='new')
        {
            $this->render("template_emailtemppopup");
        }
        //header("Contenttype:application/json; charset=utf-8");
        //echo json_encode($emailTempDetails);
	}


    //////////when a validation template is updated through the popup/////////////////
    public function editemailtemplateAction()
    {
        //$url = getenv("REQUEST_URI");//page url where the request comes//
        $prevurl = getenv("HTTP_REFERER");
        $template_obj = new Ep_Message_Template();
        $emailTempParams=$this->_request->getParams();
       // print_r($emailTempParams);
        $template_id = $emailTempParams['emailtempId'];
        if($_POST['emailtemp_submit'] == 'Update')
        {
           ////udate teplate table for changes///////
            $template_obj->type=$emailTempParams["emailtemp_type"] ;
            $template_obj->maintype=$emailTempParams["emailtemp_maintype"] ;
            $template_obj->title=$emailTempParams["emailtemp_title"] ;
            $template_obj->subject=$emailTempParams["emailtemp_subject"];
            $template_obj->content=$emailTempParams["emailtemp_content"];
            $template_obj->active=$emailTempParams["emailtemp_active"] ;
            $template_obj->parameters=$emailTempParams["emailtemp_param"] ;
            //print_r($template_obj);exit;
            $data = array("type"=>$template_obj->type, "title"=>$template_obj->title, "subject"=>$template_obj->subject,
                                    "content"=>$template_obj->content, "active"=>$template_obj->active, "parameters"=>$template_obj->parameters);
            $query = "identifier= '".$template_id."'";
            $template_obj->updateTemplate($data,$query);
            $this->_redirect($prevurl);
        }
        else if($_POST['emailtemp_submit'] == 'Add')
        {
           // echo "add"; exit;
            ////Add new teplate to the template tablechanges///////
            $template_obj->type=$emailTempParams["emailtemp_type"] ;
            $template_obj->maintype='email' ;
            $template_obj->title=$emailTempParams["emailtemp_title"] ;
            //$template_obj->subject=str_replace("’","'",$this->utf8dec($emailTempParams["emailtemp_subject"])) ;
            $template_obj->subject=$emailTempParams["emailtemp_subject"] ;
            $template_obj->content=$emailTempParams["emailtemp_content"];
            $template_obj->active=$emailTempParams["emailtemp_active"] ;
            $template_obj->parameters=$emailTempParams["emailtemp_param"] ;
            $template_obj->insert();
            $this->_redirect($prevurl);
        }
    }

    ////////displays the corresponding valid template on selecting dissaprove list//////////////
	public function getrefusevalidtempAction()
	{
        $template_obj = new Ep_Message_Template();
        $validTempParams=$this->_request->getParams();
        if($validTempParams['valtempId']!=NULL)
        {
            $tempIdentifier=$validTempParams['valtempId'];
            $validTempDetails=$template_obj->refuseValidTemplatesOnId($tempIdentifier);
        }
        $validTempDetails[0]['content'] = stripslashes(strip_tags($validTempDetails[0]['content']));
        echo utf8_encode($validTempDetails[0]['content']);
	}
	
	
	/******************************************** JOBS **********************************************/
	public function jobsAction()
	{
		$job_obj=new Ep_User_Jobs();
		
		//Insert or update jobs
		if($_POST['submit_add']!="")
		{
			$_POST['created_by']=$this->adminLogin->userId;
			$job_obj->createJob($_POST);
		}
		
		$this->_view->joblist=$job_obj->getJobs();
		$this->render("template_joblist");
	}
	
	public function addjobAction()
	{
		$job_obj=new Ep_User_Jobs();
		
		//Edit jobs
		if($_GET['act']=='edit')
		{
			$this->_view->jobdetail=$job_obj->getJobs($_GET['id']);
		}
		
		$this->render("template_addjob");  
	}
	
	/******************************************** THE TEAM **********************************************/
	
	public function theteamAction()
	{
		$team_obj=new Ep_User_Theteam();
		
		if($_POST['team_submit']!="")
		{
			if($_POST['name']!="" && $_POST['designation']!="")
			{
				$_POST['created_by']=$this->adminLogin->userId;
				$team_obj->createTeam($_POST);
			}
		}
		
		if($_GET['act']=='edit')
			$this->_view->editteam=$team_obj->getTeam($_GET['id']);
			
		$this->_view->teamlist=$team_obj->getTeam();
		
		$this->render("template_teamlist");  
	}
	
	/******************************************** OUR PARTNERS **********************************************/
	
	public function partnersAction()
	{
		$part_obj=new Ep_User_Partners();
		
		if($_POST['partner_submit']!="")
		{
			if($_POST['name']!="")
			{
				$_POST['created_by']=$this->adminLogin->userId;
				$pid=$part_obj->createPartner($_POST);
			}
		}
		
		if($_GET['act']=='edit')
			$this->_view->editpartner=$part_obj->getPartner($_GET['id']);
			
		$this->_view->partnerlist=$part_obj->getPartner();
		
		
		$this->render("template_partners");  
	}
	
		 /* Upload Profile Photo*/
    public function uploadpartnerpicAction()
    {
        error_reporting(E_ERROR | E_PARSE);
        $path=pathinfo($_FILES['uploadpic']['name']);
        $uploadpicname=$_FILES['uploadpic']['name'];
        $ext="jpg";//$path['extension'];//$this->findexts($uploadpicname);
		//$ext=$path['extension'];
		
		$uni=uniqid();
        if($_REQUEST['from']=='reference')
		{
			$uploadpicdir = "/home/sites/site7/web/FO/images/logos/references/";
			$partner_picture=$uploadpicdir."ReferenceLogo_".$uni.".".$ext;
			$fologopath="images/logos/references/ReferenceLogo_".$uni.".".$ext;
		}
		else
		{
			$uploadpicdir = "/home/sites/site7/web/FO/images/logos/partners/";
			$partner_picture=$uploadpicdir."PartnerLogo_".$uni.".".$ext;
			$fologopath="images/logos/partners/PartnerLogo_".$uni.".".$ext;
		}
        if(!is_dir($uploadpicdir))
            mkdir($uploadpicdir,TRUE);
        chmod($uploadpicdir,0777);
        
		
		
		
        list($width, $height)  = getimagesize($_FILES['uploadpic']['tmp_name']);
        if($width>=60 && $height>=30)
        {
            if (move_uploaded_file($_FILES['uploadpic']['tmp_name'], $partner_picture))
            {
                chmod($partner_picture,0777);
				
				/*Image for cropping**/
                $newimage_crop= new Ep_User_Image();
                $newimage_crop->load($partner_picture);
                list($width, $height) = getimagesize($partner_picture);
                if($width>400)
                    $newimage_crop->resizeToWidth(400);
                elseif($height>600)
                    $newimage_crop->resizeToHeight(600);
                else
                    $newimage_crop->resize($width,$height);
                $newimage_crop->save($partner_picture);
                chmod($partner_picture,0777);
				
                $array=array("status"=>"success","identifier"=>$uni,"path"=>$fologopath,"ext"=>$ext);
                echo json_encode($array);
                //echo "success";
            }
            else
            {
                $array=array("status"=>"error"  );
                echo json_encode($array);
            }
        }
        else
        {
            $array=array("status"=>"smallfile"  );
            echo json_encode($array);
        }
    }
    /* Cropping Profile images**/
    public function cropprofilepicAction()
    {
        if($this->_request-> isPost())
        {
            $image_params=$this->_request->getParams();
            $function=$image_params['function'];
            $new_x=$image_params['x'];
            $new_y=$image_params['y'];
            $post_width=$image_params['w'];
            $post_height=$image_params['h'];
            $ext="jpg";
           // $ext=$image_params['et'];
			$uni=$image_params['identy'];
			if($image_params['from']=='reference')
			{	
				$uploadpicdir = "/home/sites/site7/web/FO/images/logos/references/";
				$partner_picture=$uploadpicdir."ReferenceLogo_".$uni.".".$ext;
				$fologopath="images/logos/references/ReferenceLogo_".$uni.".".$ext;
			}
			else
			{
				$uploadpicdir = "/home/sites/site7/web/FO/images/logos/partners/";
				$partner_picture=$uploadpicdir."PartnerLogo_".$uni.".".$ext;
				$fologopath="images/logos/partners/PartnerLogo_".$uni.".".$ext;
			}
			if(!is_dir($uploadpicdir))
				mkdir($uploadpicdir,TRUE);  
			chmod($uploadpicdir,0777);
		
		
			
            
			if($function=="saveimage")
            { 
                /* Contrib Profile image with width 90**/
                $newimage_p= new Ep_User_Image();
                $newimage_p->load($partner_picture);
                list($width, $height) = getimagesize($partner_picture);
                $ao_image_height=(($height/$width)*90);
                $newimage_p->cropImage($new_x,$new_y,90,$ao_image_height,$post_width,$post_height);
                $newimage_p->save($partner_picture);
                //chmod($contrib_picture_offer,777);
                unset($newimage_p);
            }
            elseif($function=="original")
            {
                $newimage_p= new Ep_User_Image();
                $newimage_p->load($partner_picture);
                list($width, $height) = getimagesize($partner_picture);
                $ao_image_height=(($height/$width)*90);
                $newimage_p->resize(90,$ao_image_height);
                $newimage_p->save($partner_picture);
                // chmod($contrib_picture_offer,0777);
                unset($newimage_p);
            }
            /* Unlink the Original file**/
            /*if(file_exists($partner_picture) && !is_dir($partner_picture))
                unlink($partner_picture);*/
				
			
            $array=array("path"=>$fologopath,"ext"=>$ext);
            echo json_encode($array);
        }
    }
	
	
	/******************************************** OUR REFERENCES **********************************************/
	public function referencesAction()
	{
		$refer_obj=new Ep_User_References();
		
		if($_POST['reference_submit']!="")
		{
			if($_POST['name']!="")
			{
				$_POST['created_by']=$this->adminLogin->userId;
				$pid=$refer_obj->createReference($_POST);
			}
		}
		
		if($_GET['act']=='edit')
			$this->_view->editreference=$refer_obj->getReference($_GET['id']);
		
		$this->_view->referencelist=$refer_obj->getReference(); 
		
		
		$this->render("template_references");  
	}
	
	/******************************************** CGU **********************************************/
	public function cguAction()
	{
		if($_POST['cgu_submit']!="")
		{
			if($_POST['title']!="" && $_POST['content']!="")
			{
				$doc = new DOMDocument('1.0');
				// we want a nice output
				$doc->formatOutput = true;

				$root = $doc->createElement('book');
				$root = $doc->appendChild($root);

				$title = $doc->createElement('title');
				$title = $root->appendChild($title);

				$text = $doc->createTextNode(utf8_encode($_POST['title']));
				$text = $title->appendChild($text);
				
				$content = $doc->createElement('content');
				$content = $root->appendChild($content);
				
				$contenttext = $doc->createTextNode($_POST['content']);
				$contenttext = $content->appendChild($contenttext);
						
				$doc->save("/home/sites/site7/web/FO/cgu/cgu.xml"); 
			}
		}
		
		//get data
			$dom = new DOMDocument;
			$dom->load("/home/sites/site7/web/FO/cgu/cgu.xml");
			
			$titles = $dom->getElementsByTagName('title');
			foreach ($titles as $title) 
				$this->_view->cgutitle=$title->nodeValue;
			
			$contents = $dom->getElementsByTagName('content');
			foreach ($contents as $content) 
				$this->_view->cgucontent=$content->nodeValue; 

		$this->render("template_cgu");  
	}
	
	
	/******************************************** Terms **********************************************/
	public function termsAction()
	{
		if($_POST['term_submit']!="")
		{
			if($_POST['title']!="" && $_POST['content']!="")
			{
				$doc = new DOMDocument('1.0');
				// we want a nice output
				$doc->formatOutput = true;

				$root = $doc->createElement('book');
				$root = $doc->appendChild($root);

				$title = $doc->createElement('title');
				$title = $root->appendChild($title);

				$text = $doc->createTextNode(utf8_encode($_POST['title']));
				$text = $title->appendChild($text);
				
				$content = $doc->createElement('content');
				$content = $root->appendChild($content);
				
				$contenttext = $doc->createTextNode($_POST['content']);
				$contenttext = $content->appendChild($contenttext);
						
				$doc->save("/home/sites/site7/web/FO/cgu/terms.xml"); 
			}
		}
		
		//get data
			$dom = new DOMDocument;
			$dom->load("/home/sites/site7/web/FO/cgu/terms.xml");
			
			$titles = $dom->getElementsByTagName('title');
			foreach ($titles as $title) 
				$this->_view->termtitle=$title->nodeValue;
			
			$contents = $dom->getElementsByTagName('content');
			foreach ($contents as $content) 
				$this->_view->termcontent=$content->nodeValue; 

		$this->render("template_terms");  
	}
	
	/******************************************** Contact **********************************************/
	public function contacttextAction()
	{
		if($_POST['contact_submit']!="")
		{
			if($_POST['content']!="")
			{
				$doc = new DOMDocument('1.0');
				// we want a nice output
				$doc->formatOutput = true;

				$root = $doc->createElement('book');
				$root = $doc->appendChild($root);

				$content = $doc->createElement('content');
				$content = $root->appendChild($content);
				
				$contenttext = $doc->createTextNode($_POST['content']);
				$contenttext = $content->appendChild($contenttext);
						
				$doc->save("/home/sites/site7/web/FO/cgu/contact.xml"); 
			}
		}
		
		//get data
			$dom = new DOMDocument;
			$dom->load("/home/sites/site7/web/FO/cgu/contact.xml");
			
			$contents = $dom->getElementsByTagName('content');
			foreach ($contents as $content) 
				$this->_view->contacttext=$content->nodeValue; 

		$this->render("template_contacttext");  
	}
	
    /**UTF8 DECODE function work for msword character also**/
    public function utf8dec($string)
    {
            //$string = html_entity_decode(htmlentities($string." ", ENT_COMPAT, 'UTF-8'));
            $string = html_entity_decode($string);
            //echo $string;
            return substr($string, 0, strlen($string)-1);
    }

}

