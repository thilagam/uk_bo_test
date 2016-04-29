<?php
class SearchtestController extends Ep_Controller_Action
{
public function init(){
		
		parent::init();
        $this->_view->lang = $this->_lang;
        $this->adminLogin  = Zend_Registry::get('adminLogin');
        $this->sid         = session_id();
        $this->configval=$this->getConfiguredval(); 		
        
        $ep_lang_array = $this->_arrayDb->loadArrayv2("EP_LANGUAGES", $this->_lang);
		$this->_view->ep_lang=$ep_lang_array;
}

public function profileeditAction(){
	    
		 $uid=$_REQUEST['user_id'];
		 $userdetails  =   array(
							'email'	        => $_REQUEST['user_email']);
							
			 //$userdetails['email'] = $_REQUEST['user_email'];		
							
		 $contributordetails  =   array(
							'twitter_id'	=> $_REQUEST['twiter'],
							'facebook_id'	=> $_REQUEST['facebook'],
		 				    'website'       => $_REQUEST['website']);
												
		$stObj=new Ep_Searchtest_Search();
		$us_details=$stObj->edit_user($uid,$userdetails);
		$con_details=$stObj->edit_contributor($uid,$contributordetails);
		$profile_details=$stObj->get_profile($uid);
		
			if($profile_details){
				foreach($profile_details as $profiledata){ 
				$pd .= '<div><input type="hidden" id="uid" name="uid" value=" '. $profiledata['identifier'] . ' " /></div>';
				
				$pd .= '<div><label>Email Address<input type="text" id="uemail" name="uemail" value=" ' . $profiledata['email'] . ' " /></label></div>';
				$pd .= '<div><label>Password<input type="password" id="upassword" name="upassword" value=" ' . $profiledata['password'] . ' " readonly="readonly"/></label></div>';
				
				$pd .= '<div><label>DOB<input type="text" id="udob" name="udob" value="  ' . $profiledata['twitter_id'] . ' " /></label></div>';
				
				$pd .= '<div><label>Twitter Id<input type="text" id="utid" name="utid" value="  ' . $profiledata['twitter_id'] . ' " /></label></div>';
				
				$pd .= '<div><label>Facebook Id<input type="text" id="utfb" name="utfb" value="  ' . $profiledata['facebook_id'] . ' " /></label></div>';
				
				$pd .= '<div><label>Website<input type="text" id="uwebsite" name="uwebsite" value="  ' . $profiledata['website'] . ' " /></label></div>';
				}
				echo $pd;
			}
} 

public function profileviewAction(){
	
	$stObj=new Ep_Searchtest_Search();
	$profile_details=$stObj->get_profile($this->_request->getParam('id'));
	//print_r($profile_details);
	
	        $this->_view->profile=$profile_details;
			//$this->_view->searchResult_count=count($result);
        	$this->_view->render("profile_view");
			/*foreach($profile_details as $profiledata){ 
			$pd = '<div><input type="hidden" id="uid" name="uid" value=" '. $profiledata['identifier'] . ' " /></div>'; }
			echo $pd;*/
}
 
 public function profiledetailsAction(){
	 
		$stObj=new Ep_Searchtest_Search();
		$profile_details=$stObj->get_profile($this->_request->getParam('id'));
			if($profile_details){
				foreach($profile_details as $profiledata){ 
				$pd .= '<div><input type="hidden" id="uid" name="uid" value=" '. $profiledata['identifier'] . ' " /></div>';
				
				$pd .= '<div><label>Email Address<input type="text" id="uemail" name="uemail" value=" ' . $profiledata['email'] . ' " /></label></div>';
				$pd .= '<div><label>Password<input type="password" id="upassword" name="upassword" value=" ' . $profiledata['password'] . ' " readonly="readonly"/></label></div>';
				
				$pd .= '<div><label>DOB<input type="text" id="udob" name="udob" value="  ' . $profiledata['twitter_id'] . ' " /></label></div>';
				
				$pd .= '<div><label>Twitter Id<input type="text" id="utid" name="utid" value="  ' . $profiledata['twitter_id'] . ' " /></label></div>';
				
				$pd .= '<div><label>Facebook Id<input type="text" id="utfb" name="utfb" value="  ' . $profiledata['facebook_id'] . ' " /></label></div>';
				
				$pd .= '<div><label>Website<input type="text" id="uwebsite" name="uwebsite" value="  ' . $profiledata['website'] . ' " /></label></div>';
				}
				echo $pd;
			}
} 
		
public function checkAction(){
		
		if($_POST) {
	
        	$stObj=new Ep_Searchtest_Search();
			$result=$stObj->serachresult($this->_request->getParams());
        
			$this->_view->searchResult=$result;
			$this->_view->searchResult_count=count($result);
        	$this->_view->render("searchresult_view");
        }
        else{
        	$this->_view->render("searchtest_view");
    	}
}

}
?>