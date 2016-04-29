<?php
/**
 * Master Mail controller
 * @author : Arun
 * @version 2
 */
class MastermailsController extends Ep_Controller_Action
{
	public function init()
	{
		parent::init();
		$this->_view->lang = $this->_lang;
		$this->adminLogin = Zend_Registry::get('adminLogin');
		$this->user_id =$this->adminLogin->userId ;
        $this->user_type= $this->adminLogin->type ;
        $this->attachment_path="/home/sites/site7/web/FO/attachments/";
        $this->sid = session_id();        

         ////if session expires/////
        /* if($this->adminLogin->loginName == '') {
           $this->_redirect("http://admin-ep-test.edit-place.com/index/processtest");
        } */
		
		$this->_view->user_type=$this->user_type;
		$this->_view->admin_user_id=$this->user_id;
		$this->_view->loginName=$this->adminLogin->loginName;
		//unread message count
		$ticket_obj=new Ep_Message_Ticket();
		$this->_view->unreadcount=$ticket_obj->getUnreadCount($this->user_type,$this->user_id);
		
        ////////////fetching configurtion value///////////
        $this->_view->paginationlimit = $this->getConfiguredval("pagination_bo");
        $this->mail_from = $this->getConfiguredval("mail_from");
	}
	//Fetching Configuration
    public function getConfiguredval($constraint=NULL)
    {
        $conf_obj=new Ep_Delivery_Configuration();
        $conresult=$conf_obj->getConfiguration($constraint);
        return $conresult;
    }   
    /////////displays inbox of EP Users mails//////////////
	public function inboxEpAction()
	{
        $inbox_parmas=$this->_request->getParams();
		$ticket_obj = new Ep_Message_Ticket();
        //$user_id =$this->adminLogin->userId ;
        //$user_type= $this->adminLogin->type ;
        if($this->user_type=='superadmin' ||  $this->adminLogin->loginName=='akeslassy' ||  $this->adminLogin->loginName=='mfouris' ||  $this->adminLogin->loginName=='julien' )
		{
			$ep_user_id=$inbox_parmas['ep_user_id'];
			if(!$ep_user_id)$ep_user_id=$this->user_id;
			if($ep_user_id && $ep_user_id!='all')
				$inbox_messages= $ticket_obj->getMasterInboxEP($ep_user_id);
			else	
				$inbox_messages= $ticket_obj->getMasterInboxEP();
				
			$this->_view->ep_user_id=$ep_user_id;	
		}	
		else	
			$inbox_messages= $ticket_obj->getMasterInboxEP($this->user_id);
			
			//echo $ticket_obj->getUnreadCount($user_type,$this->user_id);
        //echo "<pre>";print_r($inbox_messages);
        if(is_array($inbox_messages) && count($inbox_messages)>0)
         {
                $i=0;
                foreach($inbox_messages as $message)
                {
                    $inbox_messages[$i]['sendername']=$ticket_obj->getUserName($message['userid']);
                    $inbox_messages[$i]['recipientid']=$message['receiverId'];
                    $inbox_messages[$i]['senderid']=$message['userid'];
                    $inbox_messages[$i]['recipient']=$ticket_obj->getUserName($message['receiverId']);
					$inbox_messages[$i]['size']=formatSizeUnits(mb_strlen($message['content']));
                    if($message['locked_user'])
                    {
                        $login_name=$ticket_obj->getLoginName($message['locked_user']);
                        $locked_user=$login_name[0]['login'];
                        $inbox_messages[$i]['locked_user_name']="Lock&eacute; par ".$locked_user;
                    }
                    $i++;
                }
                $this->_view->paginator = $inbox_messages;
          }
          else
                $this->_view->Inbox_Messages="Vous n'avez aucun message";
        
        /**Edit-Place Contacts**/
                $get_EP_contacts=$ticket_obj->getEPContacts('"salesuser","partner","customercare","facturation"');
			if(count($get_EP_contacts)>0)
			{	
                $EP_contacts['all']='All';
				foreach($get_EP_contacts as $contact)
                {
                    if($contact['contact_name']!=NULL)
					{
                        $EP_contacts[$contact['identifier']]=$contact['contact_name'];
						$assign_contacts[$contact['identifier']]=$contact['contact_name'];
					}	
                    else
                    {
                        $contact['email']=explode("@",$contact['email']);
                        $EP_contacts[$contact['identifier']]=$contact['email'][0];
						$assign_contacts[$contact['identifier']]=$contact['email'][0];
                    }
                }
			}	
        if($EP_contacts!=='Not Exists')
                    $this->_view->EP_contacts=$EP_contacts;
		if($assign_contacts!=='Not Exists')
                    $this->_view->assign_contacts=$assign_contacts;
         if($Contributor_contacts!=='Not Exists')
            $this->_view->Contributor_contacts=$Contributor_contacts;
        if($clients_contacts!=='Not Exists')
            $this->_view->Cients_contacts=$clients_contacts;
		$this->_view->render("mastermail_inbox_ep");
	}
	public function viewMailAction()
    {
        $mail_params=$this->_request->getParams();
        if($mail_params['message']!='' && $mail_params['ticket']!='')
        {
            $ticket=new Ep_Message_Ticket();
            $message=new Ep_Message_Message();
            $messageId=$mail_params['message'];
            $ticketId=$mail_params['ticket'];
        //if($_REQUEST['debug']){echo '<pre>';    print_r($ticket->getMasterUserReplyMails($ticketId));}
            if($mail_params['mailaction']=='inboxview' || $mail_params['mailaction']=='sentboxview' )
            {
                $identifier=$this->adminLogin->userId;
                if(($viewMessage=$message->MastercheckMessageInbox($messageId,$ticketId))!=NULL)
                {
                    $reply_messages= $ticket->getMasterUserReplyMails($ticketId);
                            //$inbox_messages=print_r($inbox_messages,true);
                                if(is_array($reply_messages) && count($reply_messages)>0)
                                {
                                    $i=0;
									 $this->_view->Identifier=$reply_messages[0]['userid'];
                                    foreach($reply_messages as $reply_message)
                                    {
                                        $reply_messages[$i]['sendername']=$ticket->getUserName($reply_message['userid']);
										$reply_messages[$i]['recipient']=$ticket->getUserName($reply_message['receiverId']);
                                        if($reply_message['bo_replied_user'])
                                        {
                                            $login_name=$ticket->getLoginName($reply_message['bo_replied_user']);
                                            $sendername=$login_name[0]['login']." - ".$login_name[0]['email'];
                                            $reply_messages[$i]['sendername'].= "( ".$sendername." )";
                                        }
                                        if( $reply_messages[$i]['attachment']!='' )
                                        {
                                            $file_attachments=explode("|", $reply_messages[$i]['attachment']);

                                            $count=1;
                                            foreach($file_attachments as $file_attachment)
                                            {
                                                if(file_exists($this->attachment_path.$file_attachment) && !is_dir($this->attachment_path.$file_attachment))
                                                {
                                                    //$attachment_file=str_replace(APP_PATH_ROOT,"http://edit-place.oboulo.com/FO/",$this->attachment_path.$viewMessage[0]['attachment']);
                                                    //echo  $attachment_file;
                                                    $reply_messages[$i]['attachment_files'][]=str_replace($reply_messages[$i]['messageId']."_".$count."_",'',$file_attachment);
                                                    $count++;
                                                }
                                            }
                                            $reply_messages[$i]['attachment_name']=$reply_messages[$i]['attachment'];
                                        }
                                        $i++;
                                    }
                                }
                                $this->_view->replyMessages= $reply_messages;
                                //$this->_view->Identifier= $identifier;
//if($_REQUEST['debug']){echo '<pre>';    print_r($reply_messages); exit;}
					
					//updated message read status
					if($mail_params['mailaction']=='inboxview')
						$message->updateMessageStatus($messageId);
					
					//$message->updateMessageStatus($messageId);
                    if(is_array($viewMessage) && count($viewMessage)>0)
                    {
                        $viewMessage[0]['sendername']=$ticket->getUserName($viewMessage[0]['userid']);
                        $viewMessage[0]['text_message']=stripslashes ($viewMessage[0]['content']);                        
						 if( $viewMessage[0]['attachment']!='')
                            {
                                $file_attachments=explode("|",$viewMessage[0]['attachment']);
                                $count=1;
                                foreach($file_attachments as $file_attachment)
                                {
                                    if(file_exists($this->attachment_path.$file_attachment) && !is_dir($this->attachment_path.$file_attachment))
                                    {
                                        //$attachment_file=str_replace(APP_PATH_ROOT,"http://edit-place.oboulo.com/FO/",$this->attachment_path.$viewMessage[0]['attachment']);
                                        //echo  $attachment_file;
                                        $attachment_name[]=str_replace($messageId."_".$count."_",'',$file_attachment);
                                        $count++;
                                        $viewMessage[0]['attachment_name']=$attachment_name;
                                    }
                                }
                            }
                    }
                    $this->_view->identifier= $identifier;
                }
                else
                {
                    $this->_redirect("/mastermails/inbox-ep?submenuId=ML6-SL2");
                    exit;
                }
            }
			$this->_view->attachments=$attachment_name;
            $this->_view->viewMessage = $viewMessage;
            $this->_view->render("master_view_mail");
        }
        else if($mail_params['mailaction']=="viewattachment" && $mail_params['attachment']!='' && $mail_params['display']!='')
        {
                    $attachment=new Ep_Message_Attachment();
                    $message=new Ep_Message_Message();
                    $identifier= $this->adminLogin->userId;
                    $messageId=$mail_params['attachment'];
                    $display=$mail_params['display'];
                    if(($file=$message->getAttachmentName($messageId))!=NULL)
                    {                         
						if($mail_params['index'])
							  $index=$mail_params['index'];
						  else
							  $index=0;
						  $view_files=explode("|",$file[0]['attachment']);
						  $file[0]['attachment']=$view_files[$index];

						if(file_exists($this->attachment_path.$file[0]['attachment']))
                        {
                            header('Location:/BO/download_attachment.php?m=' . $mail_params['attachment']) ;
                            exit;
                            
						   //$attachment->downloadAttachment($this->attachment_path.$file[0]['attachment'],$display);
                           //$attachment_file=str_replace(APP_PATH_ROOT,"http://edit-place.oboulo.com/FO/",$this->attachment_path.$viewMessage[0]['attachment']);
                        }
                    }
                    else
                    {
                        echo "File Not Found";
                    }
        }
        else
        {
            $this->_redirect("/mastermails/inbox-ep?submenuId=ML6-SL2");
            exit;
        }
    }
	//classify emails
	public function classifymailAction()
    {
        $ticket_params=$this->_request->getParams();
        if($ticket_params['ticket']!='')
        {
            $ticket_Identifier=$ticket_params['ticket'];
            $identifier=$this->adminLogin->userId;
            $ticket=new Ep_Message_Ticket();
			$automail=new Ep_Message_AutoEmails();
			
			//getting ticket details
			$ticket_details=$ticket->getTicket($ticket_Identifier);
			
			
			
			
            if(($ticket_details=$ticket->getUserTypeTicket($ticket_Identifier,$identifier))!="NO")
            {
                if($ticket_details[0]['usertype']=='recipient')
				{
                    $update_ticket['status']='3';
					
					$email_user=$ticket_details[0]['sender_id'];
				}	
                else
				{
                    $update_ticket['status']='2';
					$email_user=$ticket_details[0]['recipient_id'];
				}	
				if($email_user)
				{					
					$contributor_details=$automail->getUserDetails($email_user);
					$contributor=$contributor_details[0]['username'];
					$bo_user=$this->adminLogin->loginName;
					
					$params['archive_email_link']='/contrib/classify-messages?ticket='.$ticket_Identifier;
					$params['contributor_name']=$contributor;
					$params['bo_user']=$bo_user;
					
					$automail->messageToEPMail($email_user,123,$params);
				
				}
				
				
				
                $update_ticket['classified_by']=$identifier;
                $update_ticket['updated_at']=date("Y-m-d H:i:s", time());
                $ticket->updateTicketStatus($ticket_Identifier,$update_ticket);
                $this->_helper->FlashMessenger('Message class&eacute;.');
                $this->_redirect("/mastermails/archieve-tickets?submenuId=ML6-SL4");
            }
        }
    }
	/////////displays sent mails//////////////
	public function sentMailsAction()
	{
		$sentbox_parmas=$this->_request->getParams();
        //language array list
        $language_array=$this->_arrayDb->loadArrayv2("EP_LANGUAGES", $this->_lang);
        natsort($language_array);
        $this->_view->ep_language_list=$language_array;
        
        $categories_array=$this->_arrayDb->loadArrayv2("EP_ARTICLE_CATEGORY", $this->_lang);
        natsort($categories_array);
        $this->_view->ep_categories_list=$categories_array;
        //echo "<pre>";print_r($sentbox_parmas);print_r($cates);exit;
        
        $this->_view->type= (!empty($sentbox_parmas['type']) ? $sentbox_parmas['type'] : '');
        if($sentbox_parmas['type']=='contributor')
        {
            @$categ = $sentbox_parmas['categ'] ;
            if(sizeof($categ)>0)
            {
                foreach ($categ as $key => $value) {
                    $cates[$key] = $categories_array[$key];
                }
            }
            $this->_view->language= (!empty($sentbox_parmas['language']) ? $sentbox_parmas['language'] : '');
            $this->_view->category= (!empty($sentbox_parmas['category']) ? $sentbox_parmas['category'] : '');
            $this->_view->categ= ((sizeof($cates)>0) ? $cates : '');
            $this->_view->wrtype= (!empty($sentbox_parmas['wrtype']) ? $sentbox_parmas['wrtype'] : '');
            $this->_view->crtype= (!empty($sentbox_parmas['crtype']) ? $sentbox_parmas['crtype'] : '');
            $this->_view->wrtype1= (!empty($sentbox_parmas['wrtype'][0]) ? 'selected' : '');
            $this->_view->wrtype2= (!empty($sentbox_parmas['wrtype'][1]) ? 'selected' : '');
            $this->_view->wrtype3= (!empty($sentbox_parmas['wrtype'][2]) ? 'selected' : '');
            $this->_view->crtype1= (!empty($sentbox_parmas['crtype'][0]) ? 'selected' : '');
            $this->_view->crtype2= (!empty($sentbox_parmas['crtype'][1]) ? 'selected' : '');
            //echo "<pre>";print_r($this->_view->wrtype);
            //exit($this->_view->wrtype);
        }
        else {
            $this->_view->language= '';
            $this->_view->category= '';
            $this->_view->wrtype= '';
            $this->_view->crtype= '';
        }
        
		
		$ticket_obj = new Ep_Message_Ticket();
        //$user_id =$this->adminLogin->userId ;
        //$user_type= $this->adminLogin->type ;
		if($this->user_type=='superadmin')
		{
			if(!$sentbox_parmas['ep_user_id'])			
			{
				$sentbox_parmas['ep_user_id']=$this->user_id;
			}
			
			$sent_messages= $ticket_obj->getMasterInboxFOUsersFiltered($sentbox_parmas,0, $this->user_id);
			
			/*if($ep_user_id && $ep_user_id!='all')
				$sent_messages= $ticket_obj->getMasterInboxFOUsers($ep_user_id);
			else	
				$sent_messages= $ticket_obj->getMasterInboxFOUsers();*/
				
			//$this->_view->ep_user_id=$ep_user_id;
			$this->_view->sender= $sentbox_parmas['ep_user_id'] ;
		}	
		else
        {
            $sent_messages= $ticket_obj->getMasterInboxFOUsersFiltered($sentbox_parmas, $this->user_type, $this->user_id);
            
        }
			
			//$sent_messages= $ticket_obj->getMasterSentBox($this->user_type, $this->user_id);
		
		
        //print_r($inbox_messages);
        if(is_array($sent_messages) && count($sent_messages)>0)
         {
                $i=0;
                foreach($sent_messages as $message)
                {
                    $sent_messages[$i]['sendername']=$ticket_obj->getUserName($message['userid']);
                    $sent_messages[$i]['recipientid']=$message['receiverId'];
                    $sent_messages[$i]['senderid']=$message['userid'];
                    $sent_messages[$i]['recipient']=$ticket_obj->getUserName($message['receiverId']);
                    $i++;
                }
				$this->_view->paginator = $sent_messages;
          }
          else
                $this->_view->sent_messages="Vous n'avez aucun message";
				
		/**Edit-Place Contacts**/
                $get_EP_contacts=$ticket_obj->getEPContacts('"salesuser","partner","customercare","facturation"');
			if(count($get_EP_contacts)>0)
			{	
                $EP_contacts['all']='All';
				foreach($get_EP_contacts as $contact)
                {
                    if($contact['contact_name']!=NULL)
                        $EP_contacts[$contact['identifier']]=$contact['contact_name'];
                    else
                    {
                        $contact['email']=explode("@",$contact['email']);
                        $EP_contacts[$contact['identifier']]=$contact['email'][0];
                    }
                }
			}	
        if($EP_contacts!=='Not Exists')
                    $this->_view->EP_contacts=$EP_contacts;	
		
		
	
			
			$this->_view->render("mastermail_sentbox");
	} 
   
     /////////to compose mail and send//////////////
	public function composeAction()
	{
        $contrib_obj = new Ep_User_Contributor();
        $mail=new Ep_Message_Ticket();
        
        //language array list
        $language_array=$this->_arrayDb->loadArrayv2("EP_LANGUAGES", $this->_lang);
        natsort($language_array);
        $this->_view->ep_language_list=$language_array;
        
        $categories_array=$this->_arrayDb->loadArrayv2("EP_ARTICLE_CATEGORY", $this->_lang);
        natsort($categories_array);
        $this->_view->ep_categories_list=$categories_array;
        
        $get_contacts=$mail->getContacts('client');
        foreach($get_contacts as $contact)
        {
            $contact['contact_name'] = trim($contact['contact_name']) ;
            $contact['email'] = trim($contact['email']) ;
            if($contact['contact_name']==NULL)
                $eml=explode("@",$contact['email']);
            
            $clients_contacts[$contact['identifier']] = (($contact['contact_name']!=NULL) ? ($contact['contact_name']) : $eml[0]) . " (" . $contact['email'] . ")" ;
        }
        /**Edit-Place Contacts**/
        $get_contrib_contacts=$mail->getContacts('contributor');
        foreach($get_contrib_contacts as $contact)
        {
            $contact['contact_name'] = trim($contact['contact_name']) ;
            $contact['email'] = trim($contact['email']) ;
            if($contact['contact_name']==NULL)
                $eml=explode("@",$contact['email']);
            
            $Contributor_contacts[$contact['identifier']] = (($contact['contact_name']!=NULL) ? ($contact['contact_name']) : $eml[0]) . " (" . $contact['email'] . ")" ;
        }
        /**Ep Contacts**/
        $get_EP_contacts=$mail->getEPContactsMaster('"salesuser","partner","customercare","facturation"');
        if($get_EP_contacts!="Not Exists")
        {
            foreach($get_EP_contacts as $contact)
            {
                $contact['contact_name'] = trim($contact['login_name']) ;
                $contact['email'] = trim($contact['email']) ;
                if($contact['contact_name']==NULL)
                    $eml=explode("@",$contact['email']);
                
                $EP_contacts[$contact['identifier']] = (($contact['contact_name']!=NULL) ? ($contact['contact_name']) : $eml[0]) . " (" . $contact['email'] . ")" ;
            }
        }
         if($Contributor_contacts!=='Not Exists')
            $this->_view->Contributor_contacts=$Contributor_contacts;
        if($clients_contacts!=='Not Exists')
            $this->_view->Cients_contacts=$clients_contacts;
        if($get_EP_contacts!=='Not Exists')
                    $this->_view->EP_contacts=$EP_contacts;

        $this->_view->sc_count=$contrib_obj->getContributorcount('senior');
        $this->_view->jc_count=$contrib_obj->getContributorcount('junior');
        $this->_view->jc0_count=$contrib_obj->getContributorcount('sub-junior');
        $this->_view->csc_count=$contrib_obj->getWritercount('senior');
        $this->_view->cjc_count=$contrib_obj->getWritercount('junior');
                    
        $this->_view->sender= $this->user_id ;
        $this->_view->render("master_composemail");
	}
    public function sendcomposemailAction()
    {
        if($this->_request-> isPost())
        {
            $ticket_params=$this->_request->getParams();
            $message=new Ep_Message_Message();
			//echo "<pre>";print_r($_FILES);echo "<pre>";print_r($ticket_params);exit;
			
            /*if($ticket_params["from_client_contact"])
                $ticket_params['sender_id']=$ticket_params["from_client_contact"];
            else if($ticket_params["from_contributor_contact"])
                $ticket_params['sender_id']=$ticket_params["from_contributor_contact"];
            else if($ticket_params["from_ep_contact"])
                $ticket_params['sender_id']=$ticket_params["from_ep_contact"];*/
            
            if($ticket_params["from_ep_contact"])
                $ticket_params['sender_id']=$ticket_params["from_ep_contact"];

            $ticket_params['title']=str_replace("é",'&eacute;',$ticket_params['msg_object']);

            if($_FILES['attachment']['name'][0]!=NULL)
            {
                $file_attachemnts='';
                $cnt=1;
                foreach($_FILES['attachment']['name'] as $file)
                {
                    $file_attachemnt[$cnt-1]=$message->getIdentifier()."#".$cnt."#".utf8dec($file);
                    $file_attachemnts.= $message->getIdentifier()."#".$cnt."#".utf8dec($file)."|";
                    $cnt++;
                }
               $file_attachemnts=substr($file_attachemnts,0,-1);
             }
            
            //if($this->sendcomposemailprocess($ticket_params, $file_attachemnts))
           // {
                $attachment=new Ep_Message_Attachment();
                if($_FILES['attachment']['name'][0]!=NULL)
                {
                   $fileCount=0;
                    foreach($_FILES['attachment']['tmp_name'] as $file)
                    {
                        $attachFile['tmp_name']=$file;
                        $attachment->uploadAttachment($this->attachment_path,$attachFile,$file_attachemnt[$fileCount]);
                        $fileCount++;
                    }
                }
				$this->sendcomposemailprocess($ticket_params, $file_attachemnts);
                $this->_helper->FlashMessenger('Message envoy&eacute;.');
                $this->_redirect("/mastermails/sent-mails?submenuId=ML6-SL3");
           // }
        }
    }

    //Fetching Configuration
    public function sendcomposemailprocess($ticket_params, $file_attachemnts)
    {
        $auto_mail=new Ep_Message_AutoEmails();
        $message_obj = new Ep_Message_Message();
        $d = new Date();
        $group_id = $d->getSubDate(5,14).(mt_rand(100000,999999)+5);
        //echo "<pre>";print_r($ticket_params);exit($group_id);
        if($ticket_params['user_type']==2)
            $recievers = $ticket_params['contributor_contact'];
        else
            $recievers = $ticket_params['client_contact'];
        //{
        foreach ($recievers as $reciever) {
            
            $ticket=new Ep_Message_Ticket();

            $ticket->sender_id=$ticket_params['sender_id'];
            $ticket->recipient_id=$reciever;
            $ticket->title=$ticket_params['title'];
            $ticket->group_id=$group_id;
            $ticket->status='0';
            
            $ticket->created_at=date("Y-m-d H:i:s", time());
            $ticket->id = $d->getSubDate(5,14).mt_rand(100000,999999);
 	  //echo "<pre>";print_r($ticket);exit($group_id);
            try
            {
               if($ticket->insert())
               {
                    $ticket_id=$ticket->getIdentifier();
                    $message=new Ep_Message_Message();
                    $message->ticket_id=$ticket_id;
                    $message->content=$ticket_params["mail_message"];
                    $message->type='0' ;
                    $message->status='0';
                    $message->created_at=$ticket->created_at;
                    $message->approved='yes';
                    $message->auto_mail='no';

                    $message->attachment=$file_attachemnts;
                    	//echo "<pre>";print_r($message);exit;
                    if($message->insert())
                    {
                        /**Sending notification mail to personal email**/
                        $this->sendAutoPersonalEmail($ticket->recipient_id,$ticket->title,$message->getIdentifier(),$ticket_id,$ticket_params['sender_id'],$ticket_params["mail_message"],$file_attachemnts);
                        
                        if($ticket_params["recipient"])///reasons posted in popup
                        {
                            $data['approved']='no';
                            $message_obj->approveMessage($ticket_params['messageId'],$data);
                        }
                    }
               }
            }
            catch(Exception $e)
            {
                echo $e->getMessage();
            }
            unset($ticket);unset($message);
        }
        return $group_id ;
    }
	//reply master mail
    public function replyMailAction()
    {
        $mail_params=$this->_request->getParams();
        if($mail_params['mailaction']=='reply' && $mail_params['ticket']!='')
        {
                    $ticket_Identifier=$mail_params['ticket'];
					$message_Identifier=$mail_params['reply_message'];
                    //$identifier= $this->adminLogin->userId;
                    $identifier= $mail_params['recipientid'];
                    
					$ticket=new Ep_Message_Ticket();
					$message_obj=new Ep_Message_Message();
					
                    if(($ticket_details=$ticket->getTicketDetails($ticket_Identifier,$identifier))!="NO")
                    {
						//updated message read status						
						$message_obj->updateMessageStatus($message_Identifier);
						 
						 
						 $reply_messages= $ticket->getMasterUserReplyMails($ticket_Identifier);
                            //$inbox_messages=print_r($inbox_messages,true);
                            if(is_array($reply_messages) && count($reply_messages)>0)
                            {
                                $i=0;
                                foreach($reply_messages as $message)
                                {
                                    $reply_messages[$i]['sendername']=$ticket->getUserName($message['userid']);
									$reply_messages[$i]['recipient']=$ticket->getUserName($message['receiverId']);
                                    if($message['bo_replied_user'])
                                    {
                                        $login_name=$ticket->getLoginName($message['bo_replied_user']);
                                        $sendername=$login_name[0]['login']." - ".$login_name[0]['email'];
                                        $reply_messages[$i]['sendername'].= "( ".$sendername." )";
                                    }
                                    if( $reply_messages[$i]['attachment']!='')
                                    {
                                        $file_attachments=explode("|",$reply_messages[$i]['attachment']);
                                        $count=1;
                                        foreach($file_attachments as $file_attachment)
                                        {
                                            if(file_exists($this->attachment_path.$file_attachment) && !is_dir($this->attachment_path.$file_attachment))
                                            {
                                                //$attachment_file=str_replace(APP_PATH_ROOT,"http://edit-place.oboulo.com/FO/",$this->attachment_path.$viewMessage[0]['attachment']);
                                                //echo  $attachment_file;
                                                $reply_messages[$i]['attachment_files'][]=str_replace($reply_messages[$i]['messageId']."_".$count."_",'',$file_attachment);
                                                $count++;
                                            }
                                        }
                                        $reply_messages[$i]['attachment_name']=$reply_messages[$i]['attachment'];
                                    }
                                    $i++;
                                }
                            }
                            $this->_view->replyMessages= $reply_messages;
                            $this->_view->Identifier= $identifier;
                        if($ticket_details[0]['username']=='')
                            $this->_view->to_contact_name=$ticket_details[0]['email'];
                        else
                            $this->_view->to_contact_name=$ticket_details[0]['username'];
						
						$this->_view->to_contact_id=$ticket_details[0]['identifier'];	
						$this->_view->to_user_type=$ticket_details[0]['user_type'];	
						
                        $this->_view->from_contact_name=$ticket->getUserName($mail_params['recipientid']);
                        $this->_view->object=$ticket_details[0]['Subject'];
                        $this->_view->ticketid=$ticket_details[0]['ticketid'];
                        $this->_view->recipientid=$identifier;
                    }
                    else
                    {
                         $this->_redirect("/mastermails/inbox-ep?submenuId=ML6-SL2");
                    }
        }
        else
             $this->_redirect("/mastermails/inbox-ep?submenuId=ML6-SL2");
        $this->_view->render("master_replymail");
    }
	//send reply mail
    public function sendreplymailAction()
    {
        if($this->_request-> isPost())
        {
            $ticket_params=$this->_request->getParams();
            
			//echo "<pre>";print_r($ticket_params);exit;
            
			//$identifier=$this->adminLogin->userId;
            $identifier=$ticket_params['recipientid'];
            $ticket_Identifier=$ticket_params['ticket_id'];
            
			/**added w.r.t message not be in inbox once bo replied**/
            $reply_message_id=$ticket_params['reply_message'];
            $reply=new Ep_Message_Message();
            $data_reply['bo_replied_staus']='yes';
            $data_reply['locked_user']=NULL;
            if($reply_message_id!='')
            $reply->updateBoReplyStatus($reply_message_id, $data_reply);
            /**ENDED**/
			
            $ticket=new Ep_Message_Ticket();
            if(($ticket_details=$ticket->getUserTypeTicket($ticket_Identifier,$identifier))!="NO")
            {
                /*if($ticket_details[0]['usertype']=='recipient')
                    $update_ticket['status']='1';
                else
                    $update_ticket['status']='0';
                */
                $update_ticket['approved']='yes';
                $ticket->MasterupdateMessageStatus($ticket_Identifier,$update_ticket);
                try
                {
                    $message=new Ep_Message_Message();
                    $message->ticket_id=$ticket_Identifier;
                    $message->content=nl2br(str_replace("é",'&eacute;',$ticket_params["mail_message"]));
                    if($ticket_details[0]['usertype']=='recipient')
                        $message->type='1' ;
                    else
                        $message->type='0' ;
                    $message->status='0';
                    $message->created_at=$ticket->created_at;
                    $message->approved='yes';
                    $message->auto_mail='no';
                    $message->bo_replied_user=$identifier;					
					
					
                    if($_FILES['attachment']['name'][0]!=NULL)
                    {
                        $file_attachemnts='';
                        $cnt=1;
                        foreach($_FILES['attachment']['name'] as $file)
                        {
                            $file_attachemnt[$cnt-1]=$message->getIdentifier()."_".$cnt."_".utf8dec($file);
                            $file_attachemnts.= $message->getIdentifier()."_".$cnt."_".utf8dec($file)."|";
                            $cnt++;
                        }
                       $file_attachemnts=substr($file_attachemnts,0,-1);
                       $message->attachment=$file_attachemnts;
                     }
                    if($message->insert())
                    {                        
						/**Sending notification mail to personal email**/
                        $message_id=$message->getIdentifier();
                        $auto_recipient=$message->getRecipientId($message_id);
                        if($auto_recipient)
                        {
                            $auto_mail=new Ep_Message_AutoEmails();
                            $auto_mail->sendAutoPersonalEmail($auto_recipient,$ticket_details[0]['title'],$message_id,$ticket_Identifier);
                        }
                        $attachment=new Ep_Message_Attachment();
                        if($_FILES['attachment']['name'][0]!=NULL)
                        {
                           $fileCount=0;
                            foreach($_FILES['attachment']['tmp_name'] as $file)
                            {
                                $attachFile['tmp_name']=$file;
                                $attachment->uploadAttachment($this->attachment_path,$attachFile,$file_attachemnt[$fileCount]);
                                 $fileCount++;
                             }
                        }
                        $this->_helper->FlashMessenger('Message envoy&eacute;.');
                        $this->_redirect("/mastermails/sent-mails?submenuId=ML6-SL3");
                    }
                }
                catch(Exception $e)
                {
                        echo $e->getMessage();
                }
            }
            else
                $this->_redirect("/mastermails/compose?submenuId=ML6-SL1");
        }
        else
            $this->_redirect("/mastermails/compose?submenuId=ML6-SL1");
    }

    public function getContribSelectAction()
    {
        error_reporting(0);
        $params=$this->_request->getParams();
        $contrib_obj = new Ep_User_Contributor();
        //exit(implode(',', $contrib_obj->getContribIds($params)));
        $contrib_counts = $contrib_obj->getContribsCount($params,0);
        $corrector_counts = $contrib_obj->getContribsCount($params,1);//print_r($contrib_count);exit;
        $client_info = $contrib_obj->getContribsList($params);
        $client_list = array();
        
        if(sizeof($contrib_counts)>0){
            foreach ($contrib_counts as $contrib_count) {
                $contribcount[$contrib_count['profile_type']] = $contrib_count['count'];
            }
        }
        
        if(sizeof($corrector_counts)>0){
            foreach ($corrector_counts as $corrector_count) {
                $correctorcount[$corrector_count['profile_type']] = $corrector_count['count'];
            }
        }
        
        for ($c = 0; $c < count($client_info); $c++) {
            $client_list[$c]['identifier'] = $client_info[$c]['identifier'];

            $name = $client_info[$c]['email'];
            $nameArr = array($client_info[$c]['company_name'], $client_info[$c]['first_name'], $client_info[$c]['last_name']);
            $nameArr = array_filter($nameArr);
            if (count($nameArr) > 0)
                $name = implode(" ", $nameArr) . "(" . $name . ")";

            $client_list[$c]['name'] = strtoupper(utf8_encode($name));
        }
        asort($client_list);
        $options = '<select multiple="multiple" id="contributor_list" name="contributor_contact[]" data-placeholder="S&eacute;lectionner contributor" class="span6" onchange="fnChangeContact('."'contributor'".',this.value);" style="min-width:300px;">';

        if(sizeof($client_list)>0)
        {
            $i=0;
            foreach ($client_list as $key => $value) {
                if($params['sel'])
                {
                    if($value['identifier']==$params['sel'])    $def_cl_text=$value['name'];
                    $options .= '<option value="' . $value['identifier'] . '"' . (($value['identifier']==$params['sel']) ? " selected" : "") . '>' . $value['name'] . '</option>';
                }
                else
                {
                    $options .= '<option value="' . $value['identifier'] . '">' . $value['name'] . '</option>';
                }
                $i++;
            }
        }
        exit($options.'</select>#'.
        ($contribcount['senior'] ? $contribcount['senior'] : 0).'|'.
        ($contribcount['junior'] ? $contribcount['junior'] : 0).'|'.
        ($contribcount['sub-junior'] ? $contribcount['sub-junior'] : 0).'|'.
        ($correctorcount['senior'] ? $correctorcount['senior'] : 0).'|'.
        ($correctorcount['junior'] ? $correctorcount['junior'] : 0));
    }

	//archive mails
	public function archieveTicketsAction()
    {
        
		$ticket_obj=new Ep_Message_Ticket();
        $user_identifier= $this->user_id;
		$archieve_parmas=$this->_request->getParams();
        
		if($this->user_type=='superadmin')
		{		
			$ep_user_id=$archieve_parmas['ep_user_id'];
			if(!$ep_user_id)$ep_user_id=$user_identifier;
			if($ep_user_id && $ep_user_id!='all')
				$archieve_ticket= $ticket_obj->MasterArchieveTicket($ep_user_id);
			else	
				$archieve_ticket= $ticket_obj->MasterArchieveTicket();
				
			$this->_view->ep_user_id=$ep_user_id;		
		}
		else		
			$archieve_ticket= $ticket_obj->MasterArchieveTicket($user_identifier);
        
		//echo "<pre>";print_r($archieve_ticket);exit;
		
        if(is_array($archieve_ticket) && count($archieve_ticket)>0)
        {
            $i=0;
            foreach($archieve_ticket as $ticket)
            {
                $classified_user='';
                if($ticket['classified_by']!=NULL)
                {
                    $classified_user=$ticket['classified_by'];
                }
                elseif($ticket['status']==2)
                {
                    $classified_user=$ticket['sender_id'];
                }
                else
                {
                    $classified_user=$ticket['recipient_id'];
                }
                $name =$ticket_obj->getLoginName($classified_user);
                if($name[0]['type']!='client' && $name[0]['type']!='contributor' )
                    $archieve_ticket[$i]['classify_user_name']=$name[0]['login']." - ".$name[0]['email'];
                else
                    $archieve_ticket[$i]['classify_user_name']=$name[0]['sendername'];
                $i++;
            }
			$this->_view->archieve_ticket = $archieve_ticket;
        }
        else
            $this->_view->ticket_classes="Vous n'avez aucun message";
			
		/**Edit-Place Contacts**/
                $get_EP_contacts=$ticket_obj->getEPContacts('"salesuser","partner","customercare","facturation"');
			if(count($get_EP_contacts)>0)
			{	
                $EP_contacts['all']='All';
				foreach($get_EP_contacts as $contact)
                {
                    if($contact['contact_name']!=NULL)
                        $EP_contacts[$contact['identifier']]=$contact['contact_name'];
                    else
                    {
                        $contact['email']=explode("@",$contact['email']);
                        $EP_contacts[$contact['identifier']]=$contact['email'][0];
                    }
                }
			}
		//echo "<pre>";print_r($EP_contacts);			
        if($EP_contacts!=='Not Exists')
                    $this->_view->EP_contacts=$EP_contacts;	
		
		$this->_view->render("master_archieve_tickets");
       
    }
	public function archieveMailsAction()
    {       
        $ticket_params=$this->_request->getParams();
        if($ticket_params['ticket']!='')
        {
            $ticket=new Ep_Message_Ticket();
            $user_identifier= $this->user_id;
            $ticketId=$ticket_params['ticket'];
			
			if($this->user_type=='superadmin')
			{
				$archieve_messages= $ticket->MasterArchieveMails($ticketId);
			}
			else
				$archieve_messages= $ticket->MasterArchieveMails($ticketId,$user_identifier);
            //$inbox_messages=print_r($inbox_messages,true);
            if(is_array($archieve_messages) && count($archieve_messages)>0)
            {
                $i=0;
                foreach($archieve_messages as $message)
                {                  
                    $archieve_messages[$i]['sendername']=$ticket->getUserName($message['userid']);
					$archieve_messages[$i]['recipient']=$ticket->getUserName($message['receiverId']);
                    $this->_view->Identifier=$message['userid'];
                    $i++;
                }                
                $this->_view->archieve_messages = $archieve_messages;
                
            }
            else
                $this->_view->ticket_classes="Vous n'avez aucun message";
            
            $this->_view->render("master_archieve_mails");
        }
        else
             $this->_redirect("/mastermails/archieve-tickets?submenuId=ML6-SL4");
    }
	
	//Assign Ticket to other users
	public function assignTicketAction()
	{
		if($this->_request-> isPost())
        {
            $ticket_params=$this->_request->getParams();
			
			$ticket_id=$ticket_params['ticket_id'];
			$ep_user_id=$ticket_params['ep_user_id'];
			
			if($ticket_id && $ep_user_id && ($this->user_type=='superadmin' ||  $this->adminLogin->loginName=='akeslassy' ||  $this->adminLogin->loginName=='mfouris' ||  $this->adminLogin->loginName=='julien' ))
			{
				$ticket_obj=new Ep_Message_Ticket();
				$ticket_details=$ticket_obj->getTicket($ticket_id);
				
				if($ticket_details!="NO")
				{
					$assined_before=array();
					if($ticket_details[0]['assigned_before'])
					{
						$assined_before=explode(",",$ticket_details[0]['assigned_before']);						
					}
					$assined_before[]=$ep_user_id;
					$assined_before=array_values(array_unique($assined_before));
					
					$update_assined_before=implode(",",$assined_before);
					
					if($ticket_details[0]['recipient_id']!=$ep_user_id)
					{
						$update_ticket['recipient_id']=$ep_user_id;
						$update_ticket['updated_at']=date("Y-m-d H:i:s", time());
						$update_ticket['assigned_before']=$update_assined_before;
						$ticket_obj->updateTicketStatus($ticket_id,$update_ticket);
						
						$this->_helper->FlashMessenger('Ticket Successfully Assigned');
						
						//sending notification email to assigned user
						$ep_user_details=$ticket_obj->getLoginName($ep_user_id);
						$admin_user_details=$ticket_obj->getLoginName($this->user_id);
						
						$to=$ep_user_details[0]['email'];
						
						$admin_user_name=$admin_user_details[0]['sendername'];
						
						$object="Nouvel email en service client";						   

							  $text_mail="<p>Cher chef de projet,</p>
										<p><b>$admin_user_name</b> vous a assign&eacute; un message car il a consid&eacute;r&eacute; que vous &eacute;tiez plus en mesure d'y r&eacute;pondre. <a href=\"http://admin-test.edit-place.co.uk/mastermails/inbox-ep?submenuId=ML6-SL2\">Cliquez-ci</a> pour le consulter. </p>
										<p>Bien cordialement,</p>
										<p>L'&eacute;quipe d'Edit-Place.</p>"; 
										
								
								$from=$this->mail_from;			
										
								$mail = new Zend_Mail();
								$mail->addHeader('Reply-To',$from);
								$mail->setBodyHtml($text_mail)
									 ->setFrom($from);
								$mail->addTo($to);	 
								$mail->setSubject($object);
								$mail->send();					
						
						echo json_encode(array('status'=>'success'));
					}
					else	
						echo json_encode(array('status'=>'same_user'));
					
				}	
			}			
			
		}	
	}
    
    //Lock and Unlock functions
    public function messageLockAction()
    {
        $params=$this->_request->getParams();
        $messageId=$params['messageId'];
        $change_status=$params['status'];
        $usertype=$this->adminLogin->type;
        if($messageId && $change_status=='lock')
        {
            $message=new Ep_Message_Message();
            $status=$message->checkMasterLockstatus($messageId);
            if($status=='unlocked')
            {
                $data['locked_user']=$this->adminLogin->userId;
                $message->updateLockstatus($messageId,$data);
                $this->_redirect("/mastermails/master-inbox-ep?submenuId=ML6-SL2");
            }
            else
                $this->_redirect("/mastermails/master-inbox-ep?submenuId=ML6-SL2");
        }
        elseif($messageId && $change_status=='unlock')
        {
            $message=new Ep_Message_Message();
            $status=$message->checkMasterLockstatus($messageId);
            if($status!='unlocked')
            {
                $data['locked_user']=NULL;
                $message->updateLockstatus($messageId,$data);
                $this->_redirect("/mastermails/master-inbox-ep?submenuId=ML6-SL2");
            }
            else
                $this->_redirect("/mastermails/master-inbox-ep?submenuId=ML6-SL2");
        }
    }    
	
	public function sendAutoPersonalEmail($receiverId,$object=NULL,$messageId=NULL,$ticketId=NULL,$sender,$Message,$files)
    {
        $auto_mail=new Ep_Message_AutoEmails();
		$UserDetails=$auto_mail->getUserType($receiverId);

        $login=$UserDetails[0]['login'];
        $password=$UserDetails[0]['password'];
        $type=$UserDetails[0]['type'];

        if(!$object)
            $object="Vous avez reçu un email-Edit-place";
		
        if($UserDetails[0]['type']=='client')
        {
            $email=$UserDetails[0]['email'];
            $text_mail="<p>Cher client, ch&egrave;re  cliente,<br><br>
                            Vous avez re&ccedil;u un email d'Edit-place&nbsp;!<br><br>
                            Merci de cliquer <a href=\"http://ep-test.edit-place.co.uk/user/email-login?user=".MD5('ep_login_'.$email)."&hash=".MD5('ep_login_'.$password)."&type=".$type."&message=".$messageId."&ticket=".$ticket_id."\">ici</a> pour le lire.<br><br>
                            Cordialement,<br>
                            <br>
                            Toute l'&eacute;quipe d&rsquo;Edit-place<br><br>
							You do not wish to receive notifications ? <a href=\"http://ep-test.edit-place.co.uk/user/alert-unsubscribe?uaction=unsubscribe&user=".MD5('ep_login_'.$email)."\">Click here</a>.<br><br></p>"
                        ;

        }
        else if($UserDetails[0]['type']=='contributor')
        {
            $email=$UserDetails[0]['email'];
            $text_mail="<p>Cher contributeur,  ch&egrave;re contributrice,<br><br>
                            Vous avez re&ccedil;u un email d'Edit-place&nbsp;!<br><br>
                            Merci de cliquer <a href=\"http://ep-test.edit-place.co.uk/user/email-login?user=".MD5('ep_login_'.$email)."&hash=".MD5('ep_login_'.$password)."&type=".$type."&message=".$messageId."&ticket=".$ticket_id."\">ici</a> pour le lire.<br><br>
                            Cordialement,<br>
                            <br>
                            Toute l'&eacute;quipe d&rsquo;Edit-place<br><br>
						    You do not wish to receive notifications ? <a href=\"http://ep-test.edit-place.co.uk/user/alert-unsubscribe?uaction=unsubscribe&user=".MD5('ep_login_'.$email)."\">Click here</a>.<br><br></p>"
                        ;

        }
        
        
		$user_obj=new Ep_User_User();
		$todetail=$user_obj->getEmailUser($sender);
		$mail_from=$todetail[0]['email'];
		if($todetail[0]['first_name']!="")
			$from_name=$todetail[0]['first_name'].' '.$todetail[0]['last_name'];
		else
			$from_name=$todetail[0]['login'];
			
        if($UserDetails[0]['alert_subscribe']=='yes')
        {
             if($files!="")
				$filearray=explode("|",$files);
			if(count($filearray)>0)
				$this->mail_attachment($filearray,$this->attachment_path,$email,$mail_from,$object,$Message,$from_name);
			else
			{
				if($this->getConfiguredval('critsend') == 'yes')
				{
					critsendMail($mail_from, $email, $object, $Message);
					return true;
				}
				else
				{
					$mail = new Zend_Mail();
					$mail->addHeader('Reply-To',$mail_from);
					$mail->setBodyHtml($Message)
						->setFrom($mail_from,$from_name)
						->addTo($email)
						->setSubject($object);
					if($mail->send())
						return true;
				}
			}
        }
    }	
	
	public function mail_attachment($files, $path, $mailto, $from_mail, $subject, $message,$from_name) 
	{
        $uid = md5(uniqid(time()));
        $header = "From: ".$from_name." <".$from_mail.">\r\n";
        $header .= "Reply-To: ".$from_mail."\r\n";
        $header .= "MIME-Version: 1.0\r\n";
        $header .= "Content-Type: multipart/mixed;\r\n\tboundary=\"$uid\"\r\n\r\n";
        $header .= "This is a multi-part message in MIME format.\r\n";
        $header .= "--".$uid."\r\n";
        $header .= "Content-type:text/html; charset=iso-8859-1\r\n";
        $header .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
        $header .= $message."\r\n\r\n";
			
			foreach ($files as $filename) 
			{
				$fileinfo=pathinfo($filename);
				$file = $path.$filename;
				$name = basename($file);
				$file_size = filesize($file);
				$handle = fopen($file, "r");
				$content = fread($handle, $file_size);
				fclose($handle);
				$content = chunk_split(base64_encode($content)); 
				$file_name =  explode("#",$filename);
				$header .= "--".$uid."\r\n";
				$header .= "Content-Type: application/octet-stream\r\n"; // use different content types here
				$header .= "Content-Transfer-Encoding: base64\r\n"; 
				$header .= "Content-Disposition: attachment; filename=\"".$file_name[2]."\"\r\n\r\n";
				$header .= $content."\r\n\r\n";
			}
        $header .= "--".$uid."--";
        return mail($mailto, $subject, "", $header);
    }	
}
