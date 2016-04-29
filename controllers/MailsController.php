<?php

class MailsController extends Ep_Controller_Action
{
	private $text_admin;
	public function init()
	{
		parent::init();
		$this->_view->lang = $this->_lang;
		$this->adminLogin = Zend_Registry::get('adminLogin');
        $this->searchSession = Zend_Registry::get('searchSession');
        $this->attachment_path="/home/sites/site7/web/FO/attachments/";
        $this->attachment_path_newsletter="/home/sites/site8/web/BO/attachments/";
        $this->sid = session_id();        
  	}
   /**
	 * The default action - show the home page
     */
    ///logic for split search /////////////
    public function splitSearch($keyname, $value)
    {
       $value = preg_replace('/\s*$/','',$value);
            $words=explode(" ",$value);
           if(count($words)>1)
           {
                $addQuery.=" (".$keyname." like '%".($value)."%' or ";
                foreach($words as $key=>$word)
                {
                   if($word!='')
                   {
                       $addQuery.=$keyname." like '%".($word)."%'";
                       if($key!=(count($words))-1)
                            $addQuery.=" or ";
                   }
                }
                $addQuery.=")";
            }
            else
                $addQuery.=$keyname." like '%".($value)."%'";
        return $addQuery;
    }
    /////////displays inbox mails//////////////
	public function inboxAction()
	{
        $ticket_obj = new Ep_Message_Ticket();
        $url = getenv('REQUEST_URI');
        if($url == "/mails/inbox?submenuId=ML4-SL2")
        {
            $this->searchSession->start_date = '';
            $this->searchSession->end_date = '';
            $this->searchSession->sendername = '';
        }
        $user_id =$this->adminLogin->userId ;
        $user_type= $this->adminLogin->type ;
        $inbox_params=$this->_request->getParams();
        if(isset($inbox_params['search_button']) || $inbox_params['page']!='')
        {
           if(isset($inbox_params['start_date']) || isset($inbox_params['end_date']) || isset($inbox_params['sendername']))
           {
               $this->searchSession->start_date = $this->_request->getParam('start_date');
               $this->searchSession->end_date = $this->_request->getParam('end_date');
               $this->searchSession->sendername = $this->_request->getParam('sendername');
           }
           else
           {
                $where = " 1=1";
                $inbox_messages= $ticket_obj->getUserInbox($user_type, $user_id, $where);
           }
            $start_date = $this->_request->getParam('start_date');
            $end_date = $this->_request->getParam('end_date');
            $sendername = $this->_request->getParam('sendername');
            $this->_view->start_date=$start_date;
            $this->_view->end_date=$end_date;
            $this->_view->sendername=$sendername;
            if($sendername!='' && $start_date=='' && $end_date=='')
            {
                //$where = " up.first_name LIKE '%".$contribname."%'";
                $keyname = "up.first_name";
                $condition=$this->splitSearch($keyname, $sendername);
                $where="(".$condition.")";
            }
            else if($start_date!='' && $end_date!='' && $sendername=='')
            {
                $start_date = str_replace('/','-',$start_date);
                $end_date = str_replace('/','-',$end_date);
                $start_date = date('Y-m-d', strtotime($start_date));
                $end_date = date('Y-m-d', strtotime($end_date));
                $where = " m.created_at BETWEEN '".$start_date."' AND '".$end_date."'";
            }
            else if($sendername!='' && $start_date!='' && $end_date!='')
            {
                //$where = " up.first_name LIKE '%".$contribname."%'";
                $start_date = str_replace('/','-',$start_date);
                $end_date = str_replace('/','-',$end_date);
                $start_date = date('Y-m-d', strtotime($start_date));
                $end_date = date('Y-m-d', strtotime($end_date));
                $keyname = "up.first_name";
                $condition=$this->splitSearch($keyname, $sendername);
                $where="(".$condition.")";
                $where.= " AND (m.created_at BETWEEN '".$start_date."' AND '".$end_date."')";
            }
            $inbox_messages= $ticket_obj->getUserInbox($user_type, $user_id, $where);
        }
        else{
            $where = " 1=1";
            $inbox_messages= $ticket_obj->getUserInbox($user_type, $user_id, $where);
        }
        if(is_array($inbox_messages) && count($inbox_messages)>0)
         {
            $i=0;
            foreach($inbox_messages as $message)
            {
                if($message['locked_user'])
                {
                    $login_name=$ticket_obj->getLoginName($message['locked_user']);
                    $locked_user=$login_name[0]['login'];
                    $inbox_messages[$i]['locked_user_name']="Lock&eacute; par ".$locked_user;
                }
                $inbox_messages[$i]['sendername']=$ticket_obj->getUserName($message['userid']);
                $i++;
            }
            //echo "<pre>";print_r($inbox_messages);echo "</pre>";
            $this->_view->paginator = $inbox_messages;
          }
          else
                $this->_view->Inbox_Messages="Vous n'avez aucun message";
		$this->_view->render("mails_inbox");
	}
   /////////displays sent mails//////////////
	public function sentmailsAction()
	{
		$ticket_obj = new Ep_Message_Ticket();
        $url = getenv('REQUEST_URI');
        if($url == "/mails/sentmails?submenuId=ML4-SL3")
        {
            $this->searchSession->start_date = '';
            $this->searchSession->end_date = '';
            $this->searchSession->recievername = '';
        }
        $user_id =$this->adminLogin->userId ;
        $user_type= $this->adminLogin->type ;
		$sent_params=$this->_request->getParams();
        if(isset($sent_params['search_button'])|| $sent_params['page']!='')
        {
           if(isset($sent_params['start_date']) || isset($sent_params['end_date']) || isset($sent_params['recievername']))
           {
               $this->searchSession->start_date = $this->_request->getParam('start_date');
               $this->searchSession->end_date = $this->_request->getParam('end_date');
               $this->searchSession->recievername = $this->_request->getParam('recievername');
           }
           else
           {
               $where = " 1=1";
               $sent_messages= $ticket_obj->getUserSentBox($user_type, $user_id, $where);
           }
            $start_date = $this->_request->getParam('start_date');
            $end_date = $this->_request->getParam('end_date');
            $recievername = $this->_request->getParam('recievername');
            $this->_view->start_date=$start_date;
            $this->_view->end_date=$end_date;
            $this->_view->recievername=$recievername;
            if($recievername!='' && $start_date=='' && $end_date=='')
            {
                //$where = " up.first_name LIKE '%".$contribname."%'";
                $keyname = "up.first_name";
                $condition=$this->splitSearch($keyname, $recievername);
                $where="(".$condition.")";
            }
            else if($start_date!='' && $end_date!='' && $recievername=='')
            {
                $start_date = str_replace('/','-',$start_date);
                $end_date = str_replace('/','-',$end_date);
                $start_date = date('Y-m-d', strtotime($start_date));
                $end_date = date('Y-m-d', strtotime($end_date));
                $where = " m.created_at BETWEEN '".$start_date."' AND '".$end_date."'";
            }
            else if($recievername!='' && $start_date!='' && $end_date!='')
            {
                //$where = " up.first_name LIKE '%".$contribname."%'";
                $start_date = str_replace('/','-',$start_date);
                $end_date = str_replace('/','-',$end_date);
                $start_date = date('Y-m-d', strtotime($start_date));
                $end_date = date('Y-m-d', strtotime($end_date));
                $keyname = "up.first_name";
                $condition=$this->splitSearch($keyname, $recievername);
                $where="(".$condition.")";
                $where.= " AND (m.created_at BETWEEN '".$start_date."' AND '".$end_date."')";
            }
            $sent_messages= $ticket_obj->getUserSentBox($user_type, $user_id, $where);
        }
        else{
            $where = " 1=1";
            $sent_messages= $ticket_obj->getUserSentBox($user_type, $user_id, $where);
        }
		if(is_array($sent_messages) && count($sent_messages)>0)
        {
            $i=0;
            foreach($sent_messages as $message)
            {
                $sent_messages[$i]['sendername']=$ticket_obj->getUserName($message['userid']);
                $i++;
            }
            $this->_view->paginator = $sent_messages;
        }
        else
            $this->_view->sent_messages="Vous n'avez aucun message";
		$this->_view->render("mails_sentmails");
	}
    /////////displays Approved mails//////////////
	public function approvemailsAction()
	{
		$ticket_obj = new Ep_Message_Ticket();
        $user_id =$this->adminLogin->userId ;
        $user_type= $this->adminLogin->type ;
		$approve_messages= $ticket_obj->getApproveMails();
		if(is_array($approve_messages) && count($approve_messages)>0)
        {
            $i=0;
            foreach($approve_messages as $message)
            {
                $approve_messages[$i]['sendername']=$ticket_obj->getUserName($message['senderId']);
                $approve_messages[$i]['recipientname']=$ticket_obj->getUserName($message['recipientID']);
                $i++;
            }
		  $this->_view->paginator = $approve_messages;
        }
        else
            $this->_view->sent_messages="Vous n'avez aucun message";
		$this->_view->render("approve_mails");
	}
    /**Approve mail **/
    public function mailapproveAction()
    {
        $message_params=$this->_request->getParams();
        $message_obj = new Ep_Message_Message();
        //echo $message_params['messageId'];exit;
        if($message_params['messageId']!=NULL)
        {
            $data['approved']='yes';
            if($message_params['mailaccept_message']!='')
            $data['content']=$message_params['mailaccept_message'];
            //print_r($data);exit;
            $message_obj->approveMessage($message_params['messageId'],$data);
            /**Sending notification mail to personal email**/
            $message_id=$message_params['messageId'];
            $auto_recipient=$message_obj->getRecipientId($message_id);
            if($auto_recipient)
            {
                $auto_mail=new Ep_Message_AutoEmails();
                $auto_mail->sendAutoPersonalEmail($auto_recipient);
            }
            $this->_redirect("/mails/approvemails?submenuId=ML4-SL5&page=".$message_params['page']);
        }
        else
            $this->_redirect("/mails/approvemails?submenuId=ML4-SL5".$message_params['page']);
    }
     /////////to compose mail and send//////////////
	public function composemailAction()
	{
        $mail=new Ep_Message_Ticket();
        $get_contacts=$mail->getContacts('client');
        foreach($get_contacts as $contact)
        {
           if(trim($contact['contact_name'])!=NULL && trim($contact['contact_name'])!='')
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
            if(trim($contact['contact_name'])!=NULL && trim($contact['contact_name'])!='')
                $Contributor_contacts[$contact['identifier']]=$contact['contact_name'];
            else
            {
                $contact['email']=explode("@",$contact['email']);
                $Contributor_contacts[$contact['identifier']]=$contact['email'][0];
            }
        }
         if($Contributor_contacts!=='Not Exists')
            $this->_view->Contributor_contacts=$Contributor_contacts;
        if($clients_contacts!=='Not Exists')
            $this->_view->Cients_contacts=$clients_contacts;
        $this->_view->render("mails_composemail");
	}
    public function sendcomposemailAction()
    {
        if($this->_request-> isPost())
        {
            $sender=$this->adminLogin->userId;
            $ticket_params=$this->_request->getParams();
            //print_r($ticket_params);exit;
            $ticket=new Ep_Message_Ticket();
            $ticket->sender_id=$sender;
            $user_type= $this->adminLogin->type;
            if($user_type=='ceouser' || $user_type=='superadmin')
            {
                $sender=$ticket->getServiceClient();
                if($sender!="NO")
                $ticket->sender_id=$sender;
            }
            if($ticket_params["client_contact"])
            $ticket->recipient_id=$ticket_params["client_contact"];
            else if($ticket_params["contributor_contact"])
            $ticket->recipient_id=$ticket_params["contributor_contact"];
            else if($ticket_params["recipient"])///this is from popup for resusal reason
            $ticket->recipient_id=$ticket_params["recipient"];
            $ticket->title=str_replace("é",'&eacute;',$ticket_params['msg_object']);
            $ticket->status='0';
            $ticket->created_at=date("Y-m-d H:i:s", time());
            $ticket->bo_user_action_type='sender';
            try
            {
               //print_r($ticket);
               if($ticket->insert())
               {
                    $ticket_id=$ticket->getIdentifier();
                    $message=new Ep_Message_Message();
                    $message->ticket_id=$ticket_id;
                    $message->content=str_replace("é",'&eacute;',$ticket_params["mail_message"]);
                    $message->type='0' ;
                    $message->status='0';
                    $message->created_at=$ticket->created_at;
                    $message->approved='yes';
                    $message->auto_mail='no';
                   /* if($_FILES['attachment']['name']!='')
                    {
                        $_FILES['attachment']['name']=str_replace("é",'&eacute;',$_FILES['attachment']['name']);
                        $message->attachment=$message->getIdentifier()."_".$_FILES['attachment']['name'];
                    }*/
                    if($_FILES['attachment']['name'][0]!=NULL)
                    {
                        $file_attachemnts='';
                        $cnt=1;
                        foreach($_FILES['attachment']['name'] as $file)
                        {
                            $file=str_replace("é",'&eacute;',$file);
                            $file_attachemnt[$cnt-1]=$message->getIdentifier()."_".$cnt."_".$file;
                            $file_attachemnts.= $message->getIdentifier()."_".$cnt."_".$file."|";
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
                       // $attachment->uploadAttachment($this->attachment_path,$_FILES['attachment'],$message->attachment);
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
                        $this->_redirect("/mails/sentmails?submenuId=ML4-SL3");
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
    public function viewmailAction()
    {
        $mail_params=$this->_request->getParams();
        if($mail_params['message']!='' && $mail_params['ticket']!='')
        {
            $ticket=new Ep_Message_Ticket();
            $message=new Ep_Message_Message();
            $identifier=$this->adminLogin->userId;
            $messageId=$mail_params['message'];
            $ticketId=$mail_params['ticket'];
            if($mail_params['mailaction']=='sentview')
            {
                if(($viewMessage=$message->checkMessageSentbox($identifier,$messageId,$ticketId))!=NULL)
                {
                    if(is_array($viewMessage) && count($viewMessage)>0)
                    {
                        $viewMessage[0]['sendername']=$ticket->getUserName($viewMessage[0]['userid']);
                        $viewMessage[0]['text_message']=stripslashes ($viewMessage[0]['content']);
                        /*if( $viewMessage[0]['attachment']!='')
                        {
                            if(file_exists($this->attachment_path.$viewMessage[0]['attachment']))
                            {
                                //$attachment_file=str_replace(APP_PATH_ROOT,"http://edit-place.oboulo.com/FO/",$this->attachment_path.$viewMessage[0]['attachment']);
                                //echo  $attachment_file;
                                $attachment_name=str_replace($messageId."_",'',$viewMessage[0]['attachment']);
                                $viewMessage[0]['attachment_name']=$attachment_name;
                            }
                        }*/
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
                }
                else
                {
                    $this->_redirect("/mails/sentmails?submenuId=ML4-SL3");
                    exit;
                }
            }
            else if($mail_params['mailaction']=='inboxview')
            {
                if(($viewMessage=$message->checkMessageInbox($identifier,$messageId,$ticketId))!=NULL)
                {
                    $reply_messages= $ticket->getUserReplyMails($ticketId,$identifier);
                            //$inbox_messages=print_r($inbox_messages,true);
                                if(is_array($reply_messages) && count($reply_messages)>0)
                                {
                                    $i=0;
                                    foreach($reply_messages as $reply_message)
                                    {
                                        $reply_messages[$i]['sendername']=$ticket->getUserName($reply_message['userid']);
                                        if($reply_message['bo_replied_user'])
                                        {
                                            $login_name=$ticket->getLoginName($reply_message['bo_replied_user']);
                                            $sendername=$login_name[0]['login']." - ".$login_name[0]['email'];
                                            $reply_messages[$i]['sendername'].= "( ".$sendername." )";
                                        }
                                        /* if( $reply_messages[$i]['attachment']!='')
                                        {
                                           if(file_exists($this->attachment_path.$reply_messages[$i]['attachment']))
                                          {
                                            //$attachment_file=str_replace(APP_PATH_ROOT,"http://edit-place.oboulo.com/FO/",$this->attachment_path.$viewMessage[0]['attachment']);
                                            //echo  $attachment_file;
                                            $attachment_name=str_replace($reply_messages[$i]['messageId']."_",'',$reply_messages[$i]['attachment']);
                                            $reply_messages[$i]['attachment_name']=$attachment_name;
                                          }
                                        }*/
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
                                        $this->_view->Identifier= $reply_message['userid'];
                                    }
                                }
                                $this->_view->replyMessages= $reply_messages;
                    $message->updateMessageStatus($messageId);
                    if(is_array($viewMessage) && count($viewMessage)>0)
                    {
                        $viewMessage[0]['sendername']=$ticket->getUserName($viewMessage[0]['userid']);
                        $viewMessage[0]['text_message']=stripslashes ($viewMessage[0]['content']);
                        /*if( $viewMessage[0]['attachment']!='')
                        {
                            if(file_exists($this->attachment_path.$viewMessage[0]['attachment']))
                            {
                                //$attachment_file=str_replace(APP_PATH_ROOT,"http://edit-place.oboulo.com/FO/",$this->attachment_path.$viewMessage[0]['attachment']);
                                //echo  $attachment_file;
                                $attachment_name=str_replace($messageId."_",'',$viewMessage[0]['attachment']);
                                $viewMessage[0]['attachment_name']=$attachment_name;
                            }
                        }*/
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
                }
                else
                {
                    $this->_redirect("/mails/inbox?submenuId=ML4-SL2");
                    exit;
                }
            }
           else if($mail_params['mailaction']=='classview')
            {
                if(($viewMessage=$message->checkMessageClass($identifier,$messageId,$ticketId))!=NULL)
                {
                    if(is_array($viewMessage) && count($viewMessage)>0)
                    {
                        $viewMessage[0]['sendername']=$ticket->getUserName($viewMessage[0]['userid']);
                        $viewMessage[0]['text_message']=stripslashes ($viewMessage[0]['content']);
                        /*if( $viewMessage[0]['attachment']!='')
                        {
                            if(file_exists($this->attachment_path.$viewMessage[0]['attachment']))
                            {
                                //$attachment_file=str_replace(APP_PATH_ROOT,"http://edit-place.oboulo.com/FO/",$this->attachment_path.$viewMessage[0]['attachment']);
                                //echo  $attachment_file;
                                $attachment_name=str_replace($messageId."_",'',$viewMessage[0]['attachment']);
                                $viewMessage[0]['attachment_name']=$attachment_name;
                            }
                        }*/
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
                }
                else
                {
                    $this->_redirect("/mails/classifyticket?submenuId=ML4-SL4");
                    exit;
                }
            }
            else if($mail_params['mailaction']=='approveview')
            {
                if(($viewMessage=$message->checkMessageApprove($messageId,$ticketId))!=NULL)
                {
                    if(is_array($viewMessage) && count($viewMessage)>0)
                    {
                        $viewMessage[0]['sendername']=$ticket->getUserName($viewMessage[0]['senderId']);
                        $viewMessage[0]['recipientname']=$ticket->getUserName($viewMessage[0]['recipientID']);
                        $viewMessage[0]['text_message']=stripslashes ($viewMessage[0]['content']);
                        /*if( $viewMessage[0]['attachment']!='')
                        {
                            if(file_exists($this->attachment_path.$viewMessage[0]['attachment']))
                            {
                                //$attachment_file=str_replace(APP_PATH_ROOT,"http://edit-place.oboulo.com/FO/",$this->attachment_path.$viewMessage[0]['attachment']);
                                //echo  $attachment_file;
                                $attachment_name=str_replace($messageId."_",'',$viewMessage[0]['attachment']);
                                $viewMessage[0]['attachment_name']=$attachment_name;
                            }
                        }*/
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
                }
                else
                {
                    $this->_redirect("/mails/approvemails?submenuId=ML4-SL5");
                    exit;
                }
            }
             $this->_view->attachments=$attachment_name;
            $this->_view->viewMessage = $viewMessage;
            $this->_view->render("mails_viewmail");
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
               /* if(file_exists($this->attachment_path.$file[0]['attachment']))
                {
                   $attachment->downloadAttachment($this->attachment_path.$file[0]['attachment'],$display);
                }*/
                if($mail_params['index'])
                            $index=$mail_params['index'];
                else
                    $index=0;
                $view_files=explode("|",$file[0]['attachment']);
                $file[0]['attachment']=$view_files[$index];
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
        else if($mail_params['mailaction']=='contactusmailreply' && $mail_params['contactusmsgId']!='')////////view the mail from contact us page////
        {
             $contactus_obj = new EP_Message_ContactUs();
             $contactus_messages= $contactus_obj->contactUsDetails();
                $msg_details=$contactus_obj->getContactUsMessage($mail_params['contactusmsgId']);
                   $viewMessage=array();
                    if(is_array($viewMessage) && count($viewMessage)>=0)
                    {
                        $viewMessage[0]['receivedDate']=date("d-m-Y H:i", strtotime($msg_details[0]['created_at']));
                        $viewMessage[0]['Subject']=$msg_details[0]['msg_object'];
                        $viewMessage[0]['email']=$msg_details[0]['email'];
                        $viewMessage[0]['contactusmailId']=$msg_details[0]['identifier'];
                        $viewMessage[0]['sendername']=$msg_details[0]['name'];
                        $viewMessage[0]['recipientname']=$msg_details[0]['email'];
                        $viewMessage[0]['text_message']=utf8_encode(stripslashes($msg_details[0]['message']));
                    }
                //////////////////////////
                $contusmsg->id=$mail_params['contactusmsgId'];
                $data = array("status"=>'1');////////updating
                $query = "identifier= '".$contusmsg->id."'";
                $contactus_obj->updateContactUs($data,$query);
            $this->_view->viewMessage = $viewMessage;
            $this->_view->render("mails_viewmail");
            exit;
        }
        else
        {
            $this->_redirect("/mails/inbox?submenuId=ML4-SL2");
            exit;
        }
    }
    public function replymailAction()
    {
        $mail_params=$this->_request->getParams();
        if($mail_params['mailaction']=='reply' && $mail_params['ticket']!='')
        {
            $ticket_Identifier=$mail_params['ticket'];
            $identifier= $this->adminLogin->userId;
            $ticket=new Ep_Message_Ticket();
            if(($ticket_details=$ticket->getTicketDetails($ticket_Identifier,$identifier))!="NO")
            {
                $reply_messages= $ticket->getUserReplyMails($ticket_Identifier,$identifier);
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
                        /* if( $reply_messages[$i]['attachment']!='')
                        {
                           if(file_exists($this->attachment_path.$reply_messages[$i]['attachment']))
                          {
                            //$attachment_file=str_replace(APP_PATH_ROOT,"http://edit-place.oboulo.com/FO/",$this->attachment_path.$viewMessage[0]['attachment']);
                            //echo  $attachment_file;
                            $attachment_name=str_replace($reply_messages[$i]['messageId']."_",'',$reply_messages[$i]['attachment']);
                            $reply_messages[$i]['attachment_name']=$attachment_name;
                          }
                        }*/
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
                        $this->_view->Identifier= $message['userid'];
                    }
                }
                $this->_view->replyMessages= $reply_messages;
                //$this->_view->Identifier= $identifier;
                if($ticket_details[0]['username']=='')
                    $this->_view->send_contact_name=$ticket_details[0]['email'];
                else
                    $this->_view->send_contact_name=$ticket_details[0]['username'];
                $this->_view->object=$ticket_details[0]['Subject'];
                $this->_view->ticketid=$ticket_details[0]['ticketid'];
                $this->_view->messageId=$mail_params['reply_message'];
            }
            else
            {
                 $this->_redirect("/mails/inbox?submenuId=ML4-SL2");
            }
        }
        else
             $this->_redirect("/mails/inbox?submenuId=ML4-SL2");
        $this->_view->render("mails_replymail");
    }
    public function sendreplymailAction()
    {
        if($this->_request-> isPost())
        {
            $ticket_params=$this->_request->getParams();
            //print_r($ticket_params);exit;
            $identifier=$this->adminLogin->userId;
            $ticket_Identifier=$ticket_params['ticket_id'];
            /**added w.r.t message not be in inbox once bo replied**/
            $reply_message_id=$ticket_params['reply_message'];
            $reply=new Ep_Message_Message();
            $data_reply['bo_replied_staus']='yes';
            $data_reply['locked_user']=NULL;
            $reply->updateBoReplyStatus($reply_message_id, $data_reply);
            /**ENDED**/
            $ticket=new Ep_Message_Ticket();
            if(($ticket_details=$ticket->getUserTypeTicket($ticket_Identifier,$identifier))!="NO")
            {
                if($ticket_details[0]['usertype']=='recipient')
                    $update_ticket['status']='1';
                else
                    $update_ticket['status']='0';
                $update_ticket['updated_at']=date("Y-m-d H:i:s", time());
                $ticket->updateTicketStatus($ticket_Identifier,$update_ticket);
                try
                {
                    $message=new Ep_Message_Message();
                    $message->ticket_id=$ticket_Identifier;
                    $message->content=$this->utf8dec($ticket_params["mail_message"]);
                    if($ticket_details[0]['usertype']=='recipient')
                        $message->type='1' ;
                    else
                        $message->type='0' ;
                    $message->status='0';
                    $message->created_at=$ticket->created_at;
                    $message->approved='yes';
                    $message->auto_mail='no';
                    $message->bo_replied_user=$identifier;
                   /* if($_FILES['attachment']['name']!='')
                        $message->attachment=$message->getIdentifier()."_".$_FILES['attachment']['name'];*/
                     if($_FILES['attachment']['name'][0]!=NULL)
                     {
                        $file_attachemnts='';
                        $cnt=1;
                        foreach($_FILES['attachment']['name'] as $file)
                        {
                            $file_attachemnt[$cnt-1]=$message->getIdentifier()."_".$cnt."_".$file;
                            $file_attachemnts.= $message->getIdentifier()."_".$cnt."_".$file."|";
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
                        //$attachment->uploadAttachment($this->attachment_path,$_FILES['attachment'],$message->attachment);
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
                        $this->_redirect("/mails/sentmails?submenuId=ML4-SL3");
                    }
                }
                catch(Exception $e)
                {
                        echo $e->getMessage();
                }
            }
            else
                $this->_redirect("/mails/composemail?submenuId=ML4-SL1");
        }
        else
            $this->_redirect("/mails/composemail?submenuId=ML4-SL1");
    }
    public function classifymailAction()
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
                $this->_redirect("/mails/inbox?submenuId=ML4-SL2");
            }
        }
    }
    public function classifyticketAction()
    {
        $ticket_obj=new Ep_Message_Ticket();
        $user_identifier= $this->adminLogin->userId;
        $class_ticket= $ticket_obj->getClassifyTicket($user_identifier);
        //$inbox_messages=print_r($inbox_messages,true);
        if(is_array($class_ticket) && count($class_ticket)>0)
        {
            $i=0;
            foreach($class_ticket as $ticket)
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
                    $class_ticket[$i]['classify_user_name']=$name[0]['login']." - ".$name[0]['email'];
                else
                    $class_ticket[$i]['classify_user_name']=$name[0]['sendername'];
                $i++;
            }
			$this->_view->paginator = $class_ticket;
        }
        else
            $this->_view->ticket_classes="Vous n'avez aucun message";
        $this->_view->render("classify_tickets");
    }
	public function classifyboxAction()
    {
        $ticket_params=$this->_request->getParams();
        if($ticket_params['ticket']!='')
        {
            $ticket=new Ep_Message_Ticket();
            $user_identifier= $this->adminLogin->userId;
            $ticketId=$ticket_params['ticket'];
            $class_messages= $ticket->getUserClassifyBox($ticketId,$user_identifier);
            //$inbox_messages=print_r($inbox_messages,true);
            if(is_array($class_messages) && count($class_messages)>0)
            {
                $i=0;
                foreach($class_messages as $message)
                {
                    if(strlen($message['content']) > 255)
                    {
                        $class_messages[$i]['text_message']=stripslashes(substr($message['content'],0,254))."[...]";
                        $class_messages[$i]['read_more']=TRUE;
                    }
                    else{
                        $class_messages[$i]['text_message']=stripslashes($message['content']);
                        $class_messages[$i]['read_more']=FALSE;
                    }
                    $class_messages[$i]['sendername']=$ticket->getUserName($message['userid']);
                    $this->_view->Identifier=$message['userid'];
                    $i++;
                }
                $page = $this->_getParam('page',1);
                $paginator = Zend_Paginator::factory($class_messages);
                $paginator->setItemCountPerPage(10);
                $paginator->setCurrentPageNumber($page);
                //$this->_view->pagination=print_r($paginator->getPages(),true);
                $patterns='/&page=[\d{1,2}]/';
                $replace="";
                $this->_view->paginator = $paginator;
                $this->_view->pages = $paginator->getPages();
                $this->_view->pageURL=preg_replace($patterns, $replace,$_SERVER['REQUEST_URI']);
            }
            else
                $this->_view->ticket_classes="Vous n'avez aucun message";
            $this->_view->meta_title="Contributor-Mail class&eacute;s";
            $this->_view->render("classify_box");
        }
        else
             $this->_redirect("mails/classifyticket?submenuId=ML4-SL4");
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
                $this->_view->msgContent= utf8_encode(stripslashes($msg_details[0]['content']));
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
    ////fetching the message fo contacted visitors in popup////////////
    public function getcontactusmessageAction()
    {
        $contactus_obj = new EP_Message_ContactUs();
        $contactus_params=$this->_request->getParams();
        //print_r($mail_params);
            if(isset($contactus_params['messageId']))
            {
                //$contactus_messages= $contactus_obj->contactUsDetails();
                $msg_details=$contactus_obj->getContactUsMessage($contactus_params['messageId']);
                $this->_view->msgSubject= utf8_encode(strip_tags($msg_details[0]['msg_object']));
                $this->_view->message= utf8_encode(strip_tags($msg_details[0]['message']));
                $this->_view->recipient= $msg_details[0]['name'];
                $this->_view->email= $msg_details[0]['email'];
                $this->_view->msgId= $msg_details[0]['identifier'];
                $this->_view->pageId= $mail_params['page'];
                $this->_view->type= "contactusmailreply";
            }
        $this->_view->render("mails_emailacceptpopup");
    }
    /////////displays all contact detail who cantacted from front end//////////////
	public function contactsAction()
	{
		$contactus_obj = new Ep_Message_ContactUs();
        $user_id =$this->adminLogin->userId ;
        $user_type= $this->adminLogin->type ;
		$contact_messages= $contactus_obj->contactUsDetails();
		if($contact_messages !='NO')
        {
            $this->_view->paginator = $contact_messages;
        }
        else
            $this->_view->sent_messages="Vous n'avez aucun message";
		$this->_view->render("mails_contacts");
	}
   
	public function contactusAction()
	{
		$contactus_obj = new EP_Message_ContactUs();
        $user_id =$this->adminLogin->userId ;
        $user_type= $this->adminLogin->type ;
		$contactus_messages= $contactus_obj->contactUsDetails();
        $contactus_params=$this->_request->getParams();
       
        if(isset($contactus_params['contactusmsgId']))
        {
            $contusmsg->id=$contactus_params["contactusmsgId"] ;
            $data = array("status"=>'2');////////updating
            $query = "identifier= '".$contusmsg->id."'";
            $contactus_obj->updateContactUs($data,$query);
            $this->_redirect("mails/contactus?submenuId=ML4-SL9");
        }
		if(is_array($contactus_messages) && count($contactus_messages)>0)
        {
			$this->_view->paginator = $contactus_messages;
		}
        else
            $this->_view->sent_messages="Vous n'avez aucun message";
		$this->_view->render("mails_contactus");
	}
	
    ///////////sending mail as a reply to visitor who contacted site from front end and contactus page/////////
    public function replymailcontactusAction()
    {
        $contactus_obj = new EP_Message_ContactUs();
        $contactus_params=$this->_request->getParams();
        $msg_details=$contactus_obj->getContactUsMessage($contactus_params['messageId']);
        $msgSubject= $msg_details[0]['msg_object'];
        $message= $msg_details[0]['message'];
        $recipient= $msg_details[0]['name'];
        $email= $msg_details[0]['email'];
        $sent_date=$msg_details[0]['created_at'];
        $premsg = '<div style="width: 70%;">
           <table width="100%" cellspacing=5 cellpadding=5>
              <tr>
					<td colspan="2">--------Message d\'origine--------</td>
			 </tr>
             <tr>
					<td width="15%" align="right">De :</td><td width="80%" align="left">'.$email.'</td>
			 </tr>
			 <tr>
					<td width="15%" align="right">Envoy&eacute; :</td><td width="80%" align="left">'.date("d/m/Y h:i:s",strtotime($sent_date)).'</td>
			 </tr>
			 <tr>
					<td width="15%" align="right">&Agrave; :</td><td width="80%" align="left">contact@edit-place.com</td>
			 </tr>
			 <tr>
					<td width="15%" align="right">Objet :</td><td width="80%" align="left">'.$msgSubject.'</td>
			 </tr>
             <tr>
             <td width="15%" align="right" valign="top">&nbsp;</td><td width="80%" align="left">'.$message.'</td>
            </tr>
           </table>
        </div>';
        //print_r($contactus_params);
         $body = $contactus_params['contactusreplymsg']."<br><br><br>".$premsg;
         $body=preg_replace('/\t/','',$body);
         $mail = new Zend_Mail();
             $mail->addHeader('Reply-To','contact@edit-place.com');
              $mail->setBodyHTML($body)
                   ->setFrom('contact@edit-place.com')
                   ->addTo($contactus_params['replyemail'])
                   ->setSubject($contactus_params['contactusmsg_object']);
           if($mail->send())
           {
               $this->_helper->FlashMessenger(utf8_decode('la réplique est envoyée avec succès.'));
               $this->_redirect("/mails/contactus?submenuId=ML4-SL9");
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
            $status=$message->checkLockstatus($messageId,$usertype);
            if($status=='unlocked')
            {
                $data['locked_user']=$this->adminLogin->userId;
                $message->updateLockstatus($messageId,$data);
                $this->_redirect("/mails/inbox?submenuId=ML4-SL2");
            }
            else
                $this->_redirect("/mails/inbox?submenuId=ML4-SL2");
        }
        elseif($messageId && $change_status=='unlock')
        {
            $message=new Ep_Message_Message();
            $status=$message->checkLockstatus($messageId,$usertype);
            if($status!='unlocked')
            {
                $data['locked_user']=NULL;
                $message->updateLockstatus($messageId,$data);
                $this->_redirect("/mails/inbox?submenuId=ML4-SL2");
            }
            else
                $this->_redirect("/mails/inbox?submenuId=ML4-SL2");
        }
    }
    
	/////////to compose mail and send//////////////
    public function newsletterAction()
    { 
        $user_obj = new Ep_User_User();
        $userparams=$this->_request->getParams();
        $newsletterMailId = $this->configval["newletter_mailid"];
        $user_info= $user_obj->getUsersByGroup($userparams['selectgroup']);
        foreach($user_info as $key=>$value)
        {
            if($value['first_name']!="")
                $user_contacts[$value['identifier']]=strtoupper($value['first_name'])."(".$value['email'].")";
            else
                $user_contacts[$value['identifier']]=strtoupper($value['email']);
        }
        if($user_contacts)
            asort($user_contacts);
        /*if($user_contacts)
            array_unshift($user_contacts, "S&eacute;lectionner");*/
        $this->_view->user_contacts=$user_contacts;
        $this->_view->from_contact=$newsletterMailId;
          ////////////////////////////////////////////////////////////////////////
		$this->_view->render("mails_newsletter");
    }
	
    /////////to compose mail and send//////////////
    public function sendnewsletterAction()
    {
        ////////////////////////////////////////////////////////////////////////
		if($this->_request-> isPost())
        {
			$newsletter_obj = new EP_Message_Newsletter();
            $newsletterMsg_obj = new EP_Message_NewsletterMessage();
            $attachment=new Ep_Message_Attachment();
       		$newsletterparams=$this->_request->getParams();
     		$newsletter_obj->mail_from=$this->adminLogin->userId;
            $to_mysql_date = date('Y-m-d h:i:s', time());
            if($newsletterparams['mailnow'])
               echo   $mail_at=$to_mysql_date;
            else
               echo   '<br>'.$mail_at=date('Y-m-d h:i:s', strtotime(str_replace('/', '-', $newsletterparams['mail_time'])));
           /// print_r($newsletterparams);    exit;
            /////inserting the data into newsletterMessage table////////
            if($_FILES['attachment']['name'][0]!=NULL)
            {
                $file_attachemnts='';
                $cnt=1;
                foreach($_FILES['attachment']['name'] as $file)
                {
                    $file_attachemnt[$cnt-1]=$newsletterMsg_obj->getIdentifier()."_".$cnt."_".$file;
                    $file_attachemnts.= $newsletterMsg_obj->getIdentifier()."_".$cnt."_".$file."|";
                    $cnt++;
                }
                $file_attachemnts=substr($file_attachemnts,0,-1);
                $newsletterMsg_obj->attachment=$file_attachemnts;
                $newsletterMsg_obj->mail_from=$newsletterparams['newsletter_from'] ;
                $newsletterMsg_obj->subject=stripslashes($newsletterparams['msg_object']) ;
                $newsletterMsg_obj->message=nl2br(str_replace("é",'&eacute;',$newsletterparams['mail_message'])) ;
                ///////uploading the attachments on to the server BO/attachments file path  ////////
                $fileCount=0;
                foreach($_FILES['attachment']['tmp_name'] as $file)
                {
                    $attachFile['tmp_name']=$file;
                    $attachment->uploadAttachment($this->attachment_path_newsletter,$attachFile,$file_attachemnt[$fileCount]);
                    $fileCount++;
                }
                  /////////////////////////////////
                if($newsletterMsg_obj->insert())
                {
                    $mailusers = $newsletterparams['usercontacts'];
                    $noofusers = count($mailusers);
                    for($i=0; $i<$noofusers; $i++)
                    {
                        $newsletter_obj->mail_to=$mailusers[$i] ;
                        $newsletter_obj->mail_at=$mail_at;
                        $newsletter_obj->newsletter_message_id=$newsletterMsg_obj->getIdentifier() ;
                        $newsletter_obj->insert();
                    }
                    $this->_helper->FlashMessenger(utf8_decode("Message sent succès."));
                    $this->_redirect("/mails/newsletter?submenuId=ML4-SL10");
                }
            }
            else
            {
                $newsletterMsg_obj->mail_from=$newsletterparams['newsletter_from'] ;
                $newsletterMsg_obj->subject=stripslashes($newsletterparams['msg_object']) ;
                $newsletterMsg_obj->message=nl2br(str_replace("é",'&eacute;',$newsletterparams['mail_message'])) ;
                if($newsletterMsg_obj->insert())
                {
                    $mailusers = $newsletterparams['usercontacts'];
                    $noofusers = count($mailusers);
                    for($i=0; $i<$noofusers; $i++)
                    {
                        $newsletter_obj->mail_to=$mailusers[$i] ;
                        $newsletter_obj->mail_at=$mail_at;
                        $newsletter_obj->newsletter_message_id=$newsletterMsg_obj->getIdentifier() ;
                        $newsletter_obj->insert();
                    }
                    $this->_helper->FlashMessenger(utf8_decode("Message sent succès."));
                    $this->_redirect("/mails/newsletter?submenuId=ML4-SL10");
                }
            }
		}
       
    }
    ////Dispatching the  emails to all selected users in cron function ///////
    public function sendnewslettercronAction()
    {
        $newsletter_obj = new EP_Message_Newsletter();
        $usersmailids=$newsletter_obj->getnotsentnewsletter();
         $countMails = count($usersmailids);
        for($i=0; $i<$countMails; $i++)
        {
            $id = $usersmailids[$i]['newsletterId']; // newsletter table primary id
            $email_to = $usersmailids[$i]['email']; // To whom the email is to send
            $email_from = $usersmailids[$i]['mail_from']; // from whom the email is sent
            $email_subject = $usersmailids[$i]['subject']; // subject of email
            $email_message = $usersmailids[$i]['message']; // message
            $attachments = $usersmailids[$i]['attachment']; // message
            $files = explode("|", $attachments);
            $path = "/home/sites/site8/web/BO/attachments/";
            //////////////////////////////////////////////////
            if($attachments != NULL)
            {
                $ok = $this->mail_attachment($files, $path, $email_to, $email_from, $email_from, $email_from, $email_subject, $email_message);
                /*////////////////////////////////////////////////////
                for($x=0;$x<count($files);$x++){
                    $attachment[$x] = file_get_contents($path.$files[$x]);
                    $attachment_name[$x] = explode("_",$files[$x]);
                    $attachment_type[$x] = explode(".",$files[$x]);
                    $mail = new Zend_Mail();
                    $mail->addHeader('Reply-To',$email_from);
                    $mail->setType(Zend_Mime::MULTIPART_RELATED);
                    $mail->setBodyHtml($email_message)
                        ->setFrom($email_from)
                        ->addTo($email_to)
                        ->setSubject($email_subject);
                   // $at[$x] = $mail->createAttachment($attachment[$x]);
                    $at[$x] = new Zend_Mime_Part($attachment[$x]);
                    $at[$x]->type       = $attachment_type[$x][1];
                    $at[$x]->disposition = Zend_Mime::DISPOSITION_INLINE;
                    $at[$x]->encoding   = Zend_Mime::ENCODING_BASE64;
                    $at[$x]->filename   = $attachment_name[$x][2];
                    print_r($at);
                     $mail->addAttachment($at[$x]);
                }*/
                   //////////////////////////////////////////////////////
            }
            else
            {
                $mail = new Zend_Mail();
                $mail->addHeader('Reply-To',$email_from);
                $mail->setBodyHtml($email_message)
                    ->setFrom($email_from)
                    ->addTo($email_to)
                    ->setSubject($email_subject);
                $ok = $mail->send();
            }
            /////udate status newsletter table ///////
            $data = array("status"=>"sent");////////updating
            $query = "id= ".$id;
            $newsletter_obj->updateNewsletter($data,$query);
            //////////////////////////////////////////////////
          /*  $ok = @mail($email_to, $email_subject, $email_message, $headers);
            $query = "update Newsletter set status='sent' where id='".$id."'";*/
            //$result = mysql_query($query); // processing the query
        }
        if($ok) {
            echo "sent successfully";
        } else {
            die("Sorry but the email could not be sent. Please go back and try again!");
        }
    }
    public function mail_attachment($files, $path, $mailto, $from_mail, $from_name, $replyto, $subject, $message) {
        $uid = md5(uniqid(time()));
        $header = "From: ".$from_name." <".$from_mail.">\r\n";
        $header .= "Reply-To: ".$replyto."\r\n";
        $header .= "MIME-Version: 1.0\r\n";
        $header .= "Content-Type: multipart/mixed; boundary=\"".$uid."\"\r\n\r\n";
        $header .= "This is a multi-part message in MIME format.\r\n";
        $header .= "--".$uid."\r\n";
        $header .= "Content-type:text/html; charset=iso-8859-1\r\n";
        $header .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
        $header .= $message."\r\n\r\n";
        foreach ($files as $filename) {
            $file = $path.$filename;
            $name = basename($file);
            $file_size = filesize($file);
            $handle = fopen($file, "r");
            $content = fread($handle, $file_size);
            fclose($handle);
            $content = chunk_split(base64_encode($content));
            $file_name =  explode("_",$filename);
            $header .= "--".$uid."\r\n";
            $header .= "Content-Type: application/octet-stream; name=\"".$file_name[2]."\"\r\n"; // use different content types here
            $header .= "Content-Transfer-Encoding: base64\r\n";
            $header .= "Content-Disposition: attachment; filename=\"".$file_name[2]."\"\r\n\r\n";
            $header .= $content."\r\n\r\n";
        }
        $header .= "--".$uid."--";
        return mail($mailto, $subject, "", $header);
    }
}
