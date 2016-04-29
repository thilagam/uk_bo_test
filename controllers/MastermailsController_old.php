<?php
/**
 * Master Mail controller
 *
 * @author : Arun
 *
 * @version
 */
//require_once 'Zend/Controller/Action.php';
class MastermailsController extends Ep_Controller_Action
{
	public function init()
	{
		parent::init();
		$this->_view->lang = $this->_lang;
		$this->adminLogin = Zend_Registry::get('adminLogin');
        $this->attachment_path="/home/sites/site4/web/FO/attachments/";
        $this->sid = session_id();
        $this->commonAction();//////////including main menu and left panel content
           ////////reading the file of dynamic text to distplay//////////////////
        $data = file_get_contents('/home/sites/site4/web/BO/documents/dynamic.txt');
        $data = utf8_encode($data);
        $data = json_decode($data, true);
        for($i=0; $i<count($data); $i++)
        {
            $this->nodes[$i] = utf8_decode($data[$i]);
        }
        $this->_view->nodes = $this->nodes;
        //////////////////////////////////////////////////
        ////////////fetching configurtion value///////////
        $this->_view->paginationlimit = $this->getConfiguredval("pagination_bo");
        $mail_from = $this->getConfiguredval("mail_from");
	}
   /**
	 * The default action - show the home page
     */
    /////////displays inbox mails//////////////
	public function masterinboxAction()
	{
        $ticket_obj = new Ep_Message_Ticket();
        $user_id =$this->adminLogin->userId ;
        $user_type= $this->adminLogin->type ;
        $inbox_messages= $ticket_obj->getMasterInbox($_GET['getuserinbox']);
        //print_r($inbox_messages);
        if(is_array($inbox_messages) && count($inbox_messages)>0)
         {
                $i=0;
                foreach($inbox_messages as $message)
                {
                    $inbox_messages[$i]['sendername']=$ticket_obj->getUserName($message['userid']);
                    $inbox_messages[$i]['recipientid']=$message['receiverId'];
                    $inbox_messages[$i]['senderid']=$message['userid'];
                    $inbox_messages[$i]['recipient']=$ticket_obj->getUserName($message['receiverId']);
                    $i++;
                }
                $page = $this->_getParam('page',1);
                $paginator = Zend_Paginator::factory($inbox_messages);
                $paginator->setItemCountPerPage(10);
                $paginator->setCurrentPageNumber($page);
                //$this->_view->pagination=print_r($paginator->getPages(),true);
                //$patterns='/&page=[\d{1,2}]/';
                $patterns='/[? &]page=[0-9]{1,2}/';
                $replace="";
                $this->_view->paginator = $paginator;
                $this->_view->pages = $paginator->getPages();
                $this->_view->pageURL=preg_replace($patterns, $replace,$_SERVER['REQUEST_URI']);
          }
          else
                $this->_view->Inbox_Messages="Vous n'avez aucun message";
        /**Client Contacts**/
        $get_contacts=$ticket_obj->getContacts('client');
        foreach($get_contacts as $contact)
        {
            if(trim($contact['contact_name'])!=NULL)
                $clients_contacts[$contact['identifier']]=$contact['contact_name'];
            else
            {
                $contact['email']=explode("@",$contact['email']);
                $clients_contacts[$contact['identifier']]=$contact['email'][0];
            }
        }
        /**Contributor Contacts**/
        $get_contrib_contacts=$ticket_obj->getContacts('contributor');
        foreach($get_contrib_contacts as $contact)
        {
            if(trim($contact['contact_name'])!=NULL)
                $Contributor_contacts[$contact['identifier']]=$contact['contact_name'];
            else
            {
                $contact['email']=explode("@",$contact['email']);
                $Contributor_contacts[$contact['identifier']]=$contact['email'][0];
            }
        }
        /**Edit-Place Contacts**/
                $get_EP_contacts=$ticket_obj->getEPContacts('"salesuser","partner","customercare","facturation"');
                foreach($get_EP_contacts as $contact)
                {
                    if($contact['contact_name']!=NULL)
                        $EP_contacts[$contact['identifier']]=$contact['contact_name'];
                    else
                    {
                        $contact['email']=explode("@",$contact['email']);
                        $EP_contacts[$contact['identifier']]=$contact['email'];
                    }
                }
        if($EP_contacts!=='Not Exists')
                    $this->_view->EP_contacts=$EP_contacts;
         if($Contributor_contacts!=='Not Exists')
            $this->_view->Contributor_contacts=$Contributor_contacts;
        if($clients_contacts!=='Not Exists')
            $this->_view->Cients_contacts=$clients_contacts;
        $this->_view->selectuser=$_GET['getuserinbox'];
		$this->_view->render("master_mails_inbox");
	}
    /////////displays inbox of EP Users mails//////////////
	public function masterInboxEpAction()
	{
        $ticket_obj = new Ep_Message_Ticket();
        $user_id =$this->adminLogin->userId ;
        $user_type= $this->adminLogin->type ;
        $inbox_messages= $ticket_obj->getMasterInboxEP($_GET['getuserinbox']);
        //print_r($inbox_messages);
        if(is_array($inbox_messages) && count($inbox_messages)>0)
         {
                $i=0;
                foreach($inbox_messages as $message)
                {
                    $inbox_messages[$i]['sendername']=$ticket_obj->getUserName($message['userid']);
                    $inbox_messages[$i]['recipientid']=$message['receiverId'];
                    $inbox_messages[$i]['senderid']=$message['userid'];
                    $inbox_messages[$i]['recipient']=$ticket_obj->getUserName($message['receiverId']);
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
        /**Client Contacts**/
        $get_contacts=$ticket_obj->getContacts('client');
        foreach($get_contacts as $contact)
        {
            if(trim($contact['contact_name'])!=NULL)
                $clients_contacts[$contact['identifier']]=$contact['contact_name'];
            else
            {
                $contact['email']=explode("@",$contact['email']);
                $clients_contacts[$contact['identifier']]=$contact['email'][0];
            }
        }
        /**Contributor Contacts**/
        $get_contrib_contacts=$ticket_obj->getContacts('contributor');
        foreach($get_contrib_contacts as $contact)
        {
            if(trim($contact['contact_name'])!=NULL)
                $Contributor_contacts[$contact['identifier']]=$contact['contact_name'];
            else
            {
                $contact['email']=explode("@",$contact['email']);
                $Contributor_contacts[$contact['identifier']]=$contact['email'][0];
            }
        }
        /**Edit-Place Contacts**/
                $get_EP_contacts=$ticket_obj->getEPContacts('"salesuser","partner","customercare","facturation"');
                foreach($get_EP_contacts as $contact)
                {
                    if($contact['contact_name']!=NULL)
                        $EP_contacts[$contact['identifier']]=$contact['contact_name'];
                    else
                    {
                        $contact['email']=explode("@",$contact['email']);
                        $EP_contacts[$contact['identifier']]=$contact['email'];
                    }
                }
        if($EP_contacts!=='Not Exists')
                    $this->_view->EP_contacts=$EP_contacts;
         if($Contributor_contacts!=='Not Exists')
            $this->_view->Contributor_contacts=$Contributor_contacts;
        if($clients_contacts!=='Not Exists')
            $this->_view->Cients_contacts=$clients_contacts;
        $this->_view->selectuser=$_GET['getuserinbox'];
        $this->_view->identifier=$user_id;
		$this->_view->render("master_mails_inbox_ep");
	}
    /////////displays inbox of FO Users mails//////////////
	public function masterInboxFousersAction()
	{
        $ticket_obj = new Ep_Message_Ticket();
        $user_id =$this->adminLogin->userId ;
        $user_type= $this->adminLogin->type ;
        $inbox_messages= $ticket_obj->getMasterInboxFOUsers($_GET['getuserinbox']);
        //print_r($inbox_messages);
        if(is_array($inbox_messages) && count($inbox_messages)>0)
         {
                $i=0;
                foreach($inbox_messages as $message)
                {
                    $inbox_messages[$i]['sendername']=$ticket_obj->getUserName($message['userid']);
                    $inbox_messages[$i]['recipientid']=$message['receiverId'];
                    $inbox_messages[$i]['senderid']=$message['userid'];
                    $inbox_messages[$i]['recipient']=$ticket_obj->getUserName($message['receiverId']);
                    $i++;
                }
				$this->_view->paginator = $inbox_messages;
          }
          else
                $this->_view->Inbox_Messages="Vous n'avez aucun message";
        /**Client Contacts**/
        $get_contacts=$ticket_obj->getContacts('client');
        foreach($get_contacts as $contact)
        {
            if(trim($contact['contact_name'])!=NULL)
                $clients_contacts[$contact['identifier']]=$contact['contact_name'];
            else
            {
                $contact['email']=explode("@",$contact['email']);
                $clients_contacts[$contact['identifier']]=$contact['email'][0];
            }
        }
        /**Contributor Contacts**/
        $get_contrib_contacts=$ticket_obj->getContacts('contributor');
        foreach($get_contrib_contacts as $contact)
        {
            if(trim($contact['contact_name'])!=NULL)
                $Contributor_contacts[$contact['identifier']]=$contact['contact_name'];
            else
            {
                $contact['email']=explode("@",$contact['email']);
                $Contributor_contacts[$contact['identifier']]=$contact['email'][0];
            }
        }
        /**Edit-Place Contacts**/
                $get_EP_contacts=$ticket_obj->getEPContacts('"salesuser","partner","customercare","facturation"');
                foreach($get_EP_contacts as $contact)
                {
                    if($contact['contact_name']!=NULL)
                        $EP_contacts[$contact['identifier']]=$contact['contact_name'];
                    else
                    {
                        $contact['email']=explode("@",$contact['email']);
                        $EP_contacts[$contact['identifier']]=$contact['email'];
                    }
                }
        if($EP_contacts!=='Not Exists')
                    $this->_view->EP_contacts=$EP_contacts;
         if($Contributor_contacts!=='Not Exists')
            $this->_view->Contributor_contacts=$Contributor_contacts;
        if($clients_contacts!=='Not Exists')
            $this->_view->Cients_contacts=$clients_contacts;
        $this->_view->selectuser=$_GET['getuserinbox'];
		$this->_view->render("master_mails_inbox_fo");
	}
     /////////displays inbox of FO Users inter mails//////////////
	public function masterInboxFoInterAction()
	{
        $ticket_obj = new Ep_Message_Ticket();
        $user_id =$this->adminLogin->userId ;
        $user_type= $this->adminLogin->type ;
        $inbox_messages= $ticket_obj->getMasterInboxFOUsersInter($_GET['getuserinbox']);
        //print_r($inbox_messages);
        if(is_array($inbox_messages) && count($inbox_messages)>0)
         {
                $i=0;
                foreach($inbox_messages as $message)
                {
                    $inbox_messages[$i]['sendername']=$ticket_obj->getUserName($message['userid']);
                    $inbox_messages[$i]['recipientid']=$message['receiverId'];
                    $inbox_messages[$i]['senderid']=$message['userid'];
                    $inbox_messages[$i]['recipient']=$ticket_obj->getUserName($message['receiverId']);
                    $i++;
                }
				$this->_view->paginator = $inbox_messages;
          }
          else
                $this->_view->Inbox_Messages="Vous n'avez aucun message";
        /**Client Contacts**/
        $get_contacts=$ticket_obj->getContacts('client');
        foreach($get_contacts as $contact)
        {
            if(trim($contact['contact_name'])!=NULL)
                $clients_contacts[$contact['identifier']]=$contact['contact_name'];
            else
            {
                $contact['email']=explode("@",$contact['email']);
                $clients_contacts[$contact['identifier']]=$contact['email'][0];
            }
        }
        /**Contributor Contacts**/
        $get_contrib_contacts=$ticket_obj->getContacts('contributor');
        foreach($get_contrib_contacts as $contact)
        {
            if(trim($contact['contact_name'])!=NULL)
                $Contributor_contacts[$contact['identifier']]=$contact['contact_name'];
            else
            {
                $contact['email']=explode("@",$contact['email']);
                $Contributor_contacts[$contact['identifier']]=$contact['email'][0];
            }
        }
        /**Edit-Place Contacts**/
                $get_EP_contacts=$ticket_obj->getEPContacts('"salesuser","partner","customercare","facturation"');
                foreach($get_EP_contacts as $contact)
                {
                    if($contact['contact_name']!=NULL)
                        $EP_contacts[$contact['identifier']]=$contact['contact_name'];
                    else
                    {
                        $contact['email']=explode("@",$contact['email']);
                        $EP_contacts[$contact['identifier']]=$contact['email'];
                    }
                }
        if($EP_contacts!=='Not Exists')
                    $this->_view->EP_contacts=$EP_contacts;
         if($Contributor_contacts!=='Not Exists')
            $this->_view->Contributor_contacts=$Contributor_contacts;
        if($clients_contacts!=='Not Exists')
            $this->_view->Cients_contacts=$clients_contacts;
        $this->_view->selectuser=$_GET['getuserinbox'];
		$this->_view->render("master_mails_inbox_fo_inter");
	}
   /////////displays sent mails//////////////
	public function mastersentmailsAction()
	{
		$ticket_obj = new Ep_Message_Ticket();
        $user_id =$this->adminLogin->userId ;
        $user_type= $this->adminLogin->type ;
		$sent_messages= $ticket_obj->getUserSentBox($user_type, $user_id);
		if(is_array($sent_messages) && count($sent_messages)>0)
        {
            $i=0;
            foreach($sent_messages as $message)
            {
                $sent_messages[$i]['sendername']=$ticket_obj->getUserName($message['userid']);
                $i++;
            }
            $page = $this->_getParam('page',1);
            $paginator = Zend_Paginator::factory($sent_messages);
            $paginator->setItemCountPerPage(10);
            $paginator->setCurrentPageNumber($page);
            //$this->_view->pagination=print_r($paginator->getPages(),true);
            //$patterns='/&page=[\d{1,2}]/';
            $patterns='/[? &]page=[0-9]{1,2}/';
            $replace="";
            $this->_view->paginator = $paginator;
            $this->_view->pages = $paginator->getPages();
            $this->_view->pageURL=preg_replace($patterns, $replace,$_SERVER['REQUEST_URI']);
        }
        else
            $this->_view->sent_messages="Vous n'avez aucun message";
		$this->_view->render("mails_sentmails");
	}
     /////////to compose mail and send//////////////
	public function mastercomposeAction()
	{
        $mail=new Ep_Message_Ticket();
        $get_contacts=$mail->getContacts('client');
        foreach($get_contacts as $contact)
        {
            if(trim($contact['contact_name'])!=NULL)
                $clients_contacts[$contact['identifier']]=$contact['contact_name'];
            else
            {
                $contact['email']=explode("@",$contact['email']);
                $clients_contacts[$contact['identifier']]=$contact['email'][0];
            }
        }
        /**Edit-Place Contacts**/
        $get_contrib_contacts=$mail->getContacts('contributor');
        foreach($get_contrib_contacts as $contact)
        {
            if(trim($contact['contact_name'])!=NULL)
                $Contributor_contacts[$contact['identifier']]=$contact['contact_name'];
            else
            {
                $contact['email']=explode("@",$contact['email']);
                $Contributor_contacts[$contact['identifier']]=$contact['email'][0];
            }
        }
        /**Ep Contacts**/
        $get_EP_contacts=$mail->getEPContactsMaster('"salesuser","partner","customercare","facturation"');
        if($get_EP_contacts!="Not Exists")
        {
            foreach($get_EP_contacts as $contact)
            {
                if($contact['contact_name']!=NULL)
                    $EP_contacts[$contact['identifier']]=$contact['contact_name'];
                else
                {
                    $contact['email']=explode("@",$contact['email']);
                    $EP_contacts[$contact['identifier']]=$contact['email'];
                }
            }
        }
         if($Contributor_contacts!=='Not Exists')
            $this->_view->Contributor_contacts=$Contributor_contacts;
        if($clients_contacts!=='Not Exists')
            $this->_view->Cients_contacts=$clients_contacts;
        if($get_EP_contacts!=='Not Exists')
                    $this->_view->EP_contacts=$EP_contacts;
        $this->_view->render("master_composemail");
	}
    public function mastersendcomposemailAction()
    {
        if($this->_request-> isPost())
        {
            //$sender=$this->adminLogin->userId;
            $ticket_params=$this->_request->getParams();
            $ticket=new Ep_Message_Ticket();
            //$ticket->sender_id=$sender;
            if($ticket_params["from_client_contact"])
                 $ticket->sender_id=$ticket_params["from_client_contact"];
            else if($ticket_params["from_contributor_contact"])
                $ticket->sender_id=$ticket_params["from_contributor_contact"];
            else if($ticket_params["from_ep_contact"])
                $ticket->sender_id=$ticket_params["from_ep_contact"];
            if($ticket_params["client_contact"])
             $ticket->recipient_id=$ticket_params["client_contact"];
            else if($ticket_params["contributor_contact"])
                $ticket->recipient_id=$ticket_params["contributor_contact"];
            else if($ticket_params["recipient"])///this is from popup for resusal reason
                 $ticket->recipient_id=$ticket_params["recipient"];
            $ticket->title=str_replace("é",'&eacute;',$ticket_params['msg_object']);
            $ticket->status='0';
            $ticket->created_at=date("Y-m-d H:i:s", time());
            try
            {
              //print_r($ticket);exit;
               if($ticket->insert())
               {
                    $ticket_id=$ticket->getIdentifier();
                    $message=new Ep_Message_Message();
                    $message->ticket_id=$ticket_id;
                    $message->content=nl2br(str_replace("é",'&eacute;',$ticket_params["mail_message"]));
                    $message->type='0' ;
                    $message->status='0';
                    $message->created_at=$ticket->created_at;
                    $message->approved='yes';
                    $message->auto_mail='no';
                    if($_FILES['attachment']['name'][0]!=NULL)
					{
						$file_attachemnts='';
						$cnt=1;
						foreach($_FILES['attachment']['name'] as $file)
						{
							$file_attachemnt[$cnt-1]=$message->getIdentifier()."_".$cnt."_".$this->utf8dec($file);
							$file_attachemnts.= $message->getIdentifier()."_".$cnt."_".$this->utf8dec($file)."|";
							$cnt++;
						}
					   $file_attachemnts=substr($file_attachemnts,0,-1);
					   $message->attachment=$file_attachemnts;
					 }
                     //print_r($message);
                    if($message->insert())
                    {
                        /**Sending notification mail to personal email**/
                        $auto_mail=new Ep_Message_AutoEmails();
                        $auto_mail->sendAutoPersonalEmail($ticket->recipient_id,$ticket->title,$message->getIdentifier(),$ticket_id);
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
                        if($ticket_params["recipient"])///reasons posted in popup
                        {
                            $message_obj = new Ep_Message_Message();
                            $data['approved']='no';
                            $message_obj->approveMessage($ticket_params['messageId'],$data);
                        }
                        $this->_helper->FlashMessenger('Message envoy&eacute;.');
                        $this->_redirect("/mastermails/master-inbox-fousers?submenuId=ML6-SL4");
                    }
               }
            }
            catch(Exception $e)
            {
                echo $e->getMessage();
            }
        }
    }
    /**UTF8 DECODE function work for msword character also**/
    public function utf8dec($s_String)
    {
         $s_String = html_entity_decode(htmlentities($s_String." ", ENT_COMPAT, 'UTF-8'));
         return substr($s_String, 0, strlen($s_String)-1);
    }
    public function masterviewmailAction()
    {
        $mail_params=$this->_request->getParams();
        if($mail_params['message']!='' && $mail_params['ticket']!='')
        {
            $ticket=new Ep_Message_Ticket();
            $message=new Ep_Message_Message();
            $messageId=$mail_params['message'];
            $ticketId=$mail_params['ticket'];
             if($mail_params['mailaction']=='inboxview')
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
                                        if($reply_message['bo_replied_user'])
                                        {
                                            $login_name=$ticket->getLoginName($reply_message['bo_replied_user']);
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
                                //$this->_view->Identifier= $identifier;
                    $message->updateMessageStatus($messageId);
					//$message->updateMessageStatus($messageId);
                    if(is_array($viewMessage) && count($viewMessage)>0)
                    {
                        $viewMessage[0]['sendername']=$ticket->getUserName($viewMessage[0]['userid']);
                        $viewMessage[0]['text_message']=stripslashes ($viewMessage[0]['content']);
                        /* if( $viewMessage[0]['attachment']!='')
                        {
                            if(file_exists($this->attachment_path.$viewMessage[0]['attachment']))
                            {
                                //$attachment_file=str_replace(APP_PATH_ROOT,"http://edit-place.oboulo.com/FO/",$this->attachment_path.$viewMessage[0]['attachment']);
                                //echo  $attachment_file;
                                $attachment_name=str_replace($messageId."_",'',$viewMessage[0]['attachment']);
                                $viewMessage[0]['attachment_name']=$attachment_name;
                            }
                        } */
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
                    $this->_redirect("/mastermails/masterinbox?submenuId=ML6-SL2");
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
                    if(($file=$message->getAttachmentName( $messageId))!=NULL)
                    {
                        if(file_exists($this->attachment_path.$file[0]['attachment']))
                        {
                           $attachment->downloadAttachment($this->attachment_path.$file[0]['attachment'],$display);
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
            $this->_redirect("/mails/inbox?submenuId=ML4-SL2");
            exit;
        }
    }
    public function masterreplymailAction()
    {
        $mail_params=$this->_request->getParams();
        if($mail_params['mailaction']=='reply' && $mail_params['ticket']!='')
        {
                    $ticket_Identifier=$mail_params['ticket'];
                    //$identifier= $this->adminLogin->userId;
                    $identifier= $mail_params['recipientid'];
                    $ticket=new Ep_Message_Ticket();
                    if(($ticket_details=$ticket->getTicketDetails($ticket_Identifier,$identifier))!="NO")
                    {
						 $reply_messages= $ticket->getMasterUserReplyMails($ticket_Identifier);
                            //$inbox_messages=print_r($inbox_messages,true);
                            if(is_array($reply_messages) && count($reply_messages)>0)
                            {
                                $i=0;
                                foreach($reply_messages as $message)
                                {
                                    $reply_messages[$i]['sendername']=$ticket->getUserName($message['userid']);
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
                         $this->_view->from_contact_name=$ticket->getUserName($mail_params['recipientid']);
                        $this->_view->object=$ticket_details[0]['Subject'];
                        $this->_view->ticketid=$ticket_details[0]['ticketid'];
                        $this->_view->recipientid=$identifier;
                    }
                    else
                    {
                         $this->_redirect("/mails/inbox?submenuId=ML4-SL2");
                    }
        }
        else
             $this->_redirect("/mastermails/masterinbox?submenuId=ML4-SL2");
        $this->_view->render("master_mails_replymail");
    }
    public function mastersendreplymailAction()
    {
        if($this->_request-> isPost())
        {
            $ticket_params=$this->_request->getParams();
            //print_r($ticket_params);exit;
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
                            $file_attachemnt[$cnt-1]=$message->getIdentifier()."_".$cnt."_".$this->utf8dec($file);
                            $file_attachemnts.= $message->getIdentifier()."_".$cnt."_".$this->utf8dec($file)."|";
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
                        $this->_redirect("/mastermails/masterinbox?submenuId=ML6-SL2");
                    }
                }
                catch(Exception $e)
                {
                        echo $e->getMessage();
                }
            }
            else
                $this->_redirect("/mastermails/composemail?submenuId=ML4-SL1");
        }
        else
            $this->_redirect("/mastermails/composemail?submenuId=ML4-SL1");
    }
    public function getmessagecontentAction()
    {
        $mail_params=$this->_request->getParams();
        //print_r($mail_params);
        if($mail_params['type'] == 'approve')
        {
            if(isset($mail_params['messageId']))
            {
                $message_obj=new Ep_Message_Message();
                $msg_details=$message_obj->getMessage($mail_params['messageId']);
                $this->_view->msgContent= utf8_encode(strip_tags($msg_details[0]['content']));
                $this->_view->msgId= $mail_params['messageId'];
                $this->_view->pageId= $mail_params['page'];
                $this->_view->type= "approve";
            }
        }
        elseif($mail_params['type'] == 'refuse')
        {
            if(isset($mail_params['messageId']))
            {
                $message_obj=new Ep_Message_Message();
                $msg_details=$message_obj->getRefuseMessageDetails($mail_params['messageId']);
                $this->_view->msgSubject= utf8_encode(strip_tags($msg_details[0]['title']));
                $this->_view->msgId= $mail_params['messageId'];
                $this->_view->pageId= $mail_params['page'];
                $this->_view->type= "refuse";
                $this->_view->recipient= $msg_details[0]['first_name']." ".$msg_details[0]['last_name'];
                 $this->_view->recipientId= $msg_details[0]['sender_id'];
            }
        }
        $this->_view->render("mails_emailacceptpopup");
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
    public function masterclassifymailAction()
    {
        $ticket_params=$this->_request->getParams();
        if($ticket_params['ticket']!='')
        {
            $ticket_Identifier=$ticket_params['ticket'];
            $identifier=$this->adminLogin->userId;
            $ticket=new Ep_Message_Ticket();
            if(($ticket_details=$ticket->getUserTypeTicket($ticket_Identifier,$identifier))!="NO")
            {
                if($ticket_details[0]['usertype']=='recipient')
                    $update_ticket['status']='3';
                else
                    $update_ticket['status']='2';
                $update_ticket['classified_by']=$identifier;
                $update_ticket['updated_at']=date("Y-m-d H:i:s", time());
                $ticket->updateTicketStatus($ticket_Identifier,$update_ticket);
                $this->_helper->FlashMessenger('Message class&eacute;.');
                $this->_redirect("/mastermails/master-inbox-ep?submenuId=ML6-SL2");
            }
        }
    }
}
