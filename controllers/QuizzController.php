<?php

require_once 'Zend/Controller/Action.php';

class QuizzController extends Ep_Controller_Action
{
    private $text_admin;
    
    public function init()
    {
        parent::init();
        $this->_view->lang = $this->_lang;
        $this->adminLogin = Zend_Registry::get('adminLogin');
        $this->sid = session_id();
        
        //$this->_view->dateformat = $this->getConfiguredval/ao("timeformat_bo");
        $this->_view->paginationlimit = $this->getConfiguredval("pagination_bo");
    }

    
	/******************************************************* Quizz creation *********************************************/
	/* Quizz creation step 1 */
    public function createquizzAction()
    {
        $params =   $this->_request->getParams();     
        $this->QZ_creation = Zend_Registry::get('QZ_creation');

        // && $params['mod']
        $categories=$this->_arrayDb->loadArrayv2("EP_ARTICLE_CATEGORY", $this->_lang) ;
        $this->_view->categories = $categories;
                
        if(isset($this->QZ_creation->qz_step1['quizztitle']))
        {
            $this->_view->quizztitle=$this->QZ_creation->qz_step1['quizztitle'];
            $this->_view->category=$this->QZ_creation->qz_step1['category'];
            $this->_view->status=$this->QZ_creation->qz_step1['status'];
            $this->_view->quest_count=$this->QZ_creation->qz_step1['quest_count'];
            $this->_view->correct_ans_count=$this->QZ_creation->qz_step1['correct_ans_count'];
            $this->_view->setuptime=$this->QZ_creation->qz_step1['setuptime'];
            $this->_view->get_modify=0;
        }
        
        $this->_view->render("create1_quizz");
    }    
	
	/* Quizz creation step 2 */
    public function create1quizzAction()
    {
        $this->QZ_creation = Zend_Registry::get('QZ_creation');
        $params=$this->_request->getParams();
        
        if($params['quizztitle']!="")
            $this->QZ_creation->qz_step1=$params;
        else
            $this->_redirect("/quizz/createquizz?submenuId=ML2-SL21");
        
        $this->_view->quest_count   =   $this->QZ_creation->qz_step1['quest_count'];
        $this->_view->questtitle   =   $this->QZ_creation->qz_step1['quizztitle'];
        $this->_view->render("create2_quizz") ;
    }  
	
	/* Saving quizz info */
    public function create2quizzAction()
    { 
        $this->QZ_creation = Zend_Registry::get('QZ_creation');
        $params=$this->_request->getParams();

        if($params['qn0']=="") :
            unset($this->QZ_creation->qz_step1) ;
            unset($this->QZ_creation->qz_step2) ;
            $this->_redirect("/quizz/create1quizz?submenuId=ML2-SL21");
        else :
            $this->QZ_creation->qz_step2=$params;
            $obj = new Ep_Quizz_Quizz() ;
            $obj->insertQuizz($this->QZ_creation->qz_step1, $this->QZ_creation->qz_step2, $this->adminLogin->userId) ;
            $this->_view->successMsg    =   "Quizz created succedfully" ;
        endif ;

        unset($this->QZ_creation->qz_step1) ;
        unset($this->QZ_creation->qz_step2) ;
        $this->_view->render("create3_quizz") ;
    } 
	
	/******************************************************* Quizz modify *********************************************************/
    /* Quizz modification step 1 */
    public function modifyquizzAction()
    {
        $obj  =    new Ep_Quizz_Quizz() ;
        $params =   $this->_request->getParams();
        if(!$params['id'])
            $this->_redirect("/quizz/listallquizz?submenuId=ML2-SL22");
        
        $this->QZ_creation = Zend_Registry::get('QZ_modify');

        $categories=$this->_arrayDb->loadArrayv2("EP_ARTICLE_CATEGORY", $this->_lang) ;
        $this->_view->categories = $categories;
        
        if($params['mod']) :
            $this->_view->quizztitle=$this->QZ_creation->qz_step1['quizztitle'];
            $this->_view->category=$this->QZ_creation->qz_step1['category'];
            $this->_view->status=$this->QZ_creation->qz_step1['status'];
            $this->_view->quest_count=$this->QZ_creation->qz_step1['quest_count'];
            $this->_view->correct_ans_count=$this->QZ_creation->qz_step1['correct_ans_count'];
            $this->_view->setuptime=$this->QZ_creation->qz_step1['setuptime'];
        else :
            $obj  =    new Ep_Quizz_Quizz() ;
            $quizz =   $obj->Getquizz($params['id']) ;
            
            $this->_view->quizztitle=$quizz['title'];
            $this->_view->category=$quizz['category'];
            $this->_view->status=$quizz['status'];
            $this->_view->quest_count=$quizz['quest_count'];
            $this->_view->correct_ans_count=$quizz['correct_ans_count'];
            $this->_view->setuptime=$quizz['setuptime'];
        endif ;
            
		//QuizzParticipations
		$Quizparticipations=$obj->GetParticipantsList($params['id']) ;
		$this->_view->participants=count($Quizparticipations);
        $this->_view->render("modify1_quizz");
    }    
	
	    /* Quizz modification step 2 */
    public function modify1quizzAction()
    {
        $params=$this->_request->getParams();
        if(!$params['id'])
            $this->_redirect("/quizz/listallquizz?submenuId=ML2-SL22");
        
        $obj  =    new Ep_Quizz_Quizz() ;
        $quizzinfo =   $obj->Getquizzinfo($params['id']) ;
        $questinfoCount =   sizeof($quizzinfo) ;
        
        if(($questinfoCount > 0) && ($params['quizztitle']!="")) :
            
            $this->QZ_creation = Zend_Registry::get('QZ_modify');
            
            $this->QZ_creation->qz_step1    =   $params;
            $this->_view->quest_count   =   $this->QZ_creation->qz_step1['quest_count'];
            $this->_view->questinfoCount   =   $questinfoCount/4;
            $this->_view->quest_count_step1   =   (($questinfoCount/4) > $this->QZ_creation->qz_step1['quest_count']) ? ($questinfoCount/4) : $this->QZ_creation->qz_step1['quest_count'] ;
            
            $quizz  =   array() ;
            for($j=0; $j<$questinfoCount; $j++) :            
                if( ($j+4)%4 == 0 ) {
                    $quizz['qn'.(intval(($j+4)/4)-1)] = ( $quizzinfo[$j]['question'] ) ;
                    $quizz['qnid'.(intval(($j+4)/4)-1)] = ( $quizzinfo[$j]['id'] ) ;
                    $quizz['r_an'.(intval(($j+4)/4)-1)] = ( $quizzinfo[$j]['ans_id'] ) ;
                }
                $quizz['an'.(($j+4)%4).(intval(($j+4)/4)-1)] = ( $quizzinfo[$j]['text'] ) ;
                $quizz['ansid'.(($j+4)%4).(intval(($j+4)/4)-1)] = ( $quizzinfo[$j]['aid'] ) ;
            endfor ;
            
            $this->_view->quizz   =   $quizz ;
            $this->_view->questtitle   =   $this->QZ_creation->qz_step1['quizztitle'];
            $this->_view->render("modify2_quizz") ;

        else :
             $this->_redirect("/quizz/listallquizz?submenuId=ML2-SL22");
        endif ;
    }
	
	/* Updating quizz info */
    public function modify2quizzAction()
    {
        $this->QZ_creation = Zend_Registry::get('QZ_modify');
        $params=$this->_request->getParams();

        if($params['qn0']=="" || !$params['id']) :
            unset($this->QZ_creation->qz_step1) ;
            unset($this->QZ_creation->qz_step2) ;
            $this->_redirect("/quizz/listallquizz?submenuId=ML2-SL22");
        else :
            $this->QZ_creation->qz_step2=$params;
            $obj = new Ep_Quizz_Quizz() ;
            $obj->updateQuizz($params['id'], $this->QZ_creation->qz_step1, $this->QZ_creation->qz_step2, $this->adminLogin->userId) ;
            $this->_view->successMsg    =   "Quizz a &#233;t&#233; mis &#224; jour." ;
        endif ;
            
        unset($this->QZ_creation->qz_step1) ;
        unset($this->QZ_creation->qz_step2) ;
        $this->_view->render("create3_quizz") ;
    } 
	
	/******************************************************** Quizz duplicate ***************************************************/
    /* Quizz duplication step 1 */
    public function duplicatequizzAction()
    {
        $params =   $this->_request->getParams();
        if(!$params['id'])
            $this->_redirect("/quizz/listallquizz?submenuId=ML2-SL22");
        
        $this->QZ_creation = Zend_Registry::get('QZ_duplication');

        $categories=$this->_arrayDb->loadArrayv2("EP_ARTICLE_CATEGORY", $this->_lang) ;
        $this->_view->categories = $categories;
        
        if($params['mod']) :
            $this->_view->quizztitle=$this->QZ_creation->qz_step1['quizztitle'];
            $this->_view->category=$this->QZ_creation->qz_step1['category'];
            $this->_view->status=$this->QZ_creation->qz_step1['status'];
            $this->_view->quest_count=$this->QZ_creation->qz_step1['quest_count'];
            $this->_view->correct_ans_count=$this->QZ_creation->qz_step1['correct_ans_count'];
            $this->_view->setuptime=$this->QZ_creation->qz_step1['setuptime'];
        else :
            $obj  =    new Ep_Quizz_Quizz() ;
            $quizz =   $obj->Getquizz($params['id']) ;
            
            $this->_view->quizztitle=$quizz['title'];
            $this->_view->category=$quizz['category'];
            $this->_view->status=$quizz['status'];
            $this->_view->quest_count=$quizz['quest_count'];
            $this->_view->correct_ans_count=$quizz['correct_ans_count'];
            $this->_view->setuptime=$quizz['setuptime'];
        endif ;
            
        $this->_view->render("duplicate1_quizz");
    }    
    
    /* Quizz duplication step 2 */
    public function duplicate1quizzAction()
    {
        $params=$this->_request->getParams();
        if(!$params['id'])
            $this->_redirect("/quizz/listallquizz?submenuId=ML2-SL22");
        
        $obj  =    new Ep_Quizz_Quizz() ;
        $quizzinfo =   $obj->Getquizzinfo($params['id']) ;
        $questinfoCount =   sizeof($quizzinfo) ;
        
        if(($questinfoCount > 0) && ($params['quizztitle']!="")) :
            
            $this->QZ_creation = Zend_Registry::get('QZ_duplication');
            
            $this->QZ_creation->qz_step1    =   $params;
            $this->_view->quest_count   =   $this->QZ_creation->qz_step1['quest_count'];
            $this->_view->questinfoCount   =   $questinfoCount/4;
            $this->_view->quest_count_step1   =   (($questinfoCount/4) > $this->QZ_creation->qz_step1['quest_count']) ? ($questinfoCount/4) : $this->QZ_creation->qz_step1['quest_count'] ;
            
            $quizz  =   array() ;
            for($j=0; $j<$questinfoCount; $j++) :            
                if( ($j+4)%4 == 0 ) {
                    $quizz['qn'.(intval(($j+4)/4)-1)] = ( $quizzinfo[$j]['question'] ) ;
                    $quizz['qnid'.(intval(($j+4)/4)-1)] = ( $quizzinfo[$j]['id'] ) ;
                    $quizz['r_an'.(intval(($j+4)/4)-1)] = ( $quizzinfo[$j]['ans_id'] ) ;
                }
                $quizz['an'.(($j+4)%4).(intval(($j+4)/4)-1)] = ( $quizzinfo[$j]['text'] ) ;
                $quizz['ansid'.(($j+4)%4).(intval(($j+4)/4)-1)] = ( $quizzinfo[$j]['aid'] ) ;
            endfor ;
            
            $this->_view->quizz   =   $quizz ;
            $this->_view->questtitle   =   $this->QZ_creation->qz_step1['quizztitle'];
            $this->_view->render("duplicate2_quizz") ;

        else :
             $this->_redirect("/quizz/listallquizz?submenuId=ML2-SL22");
        endif ;
    }  
    
    /* Saving quizz info */
    public function duplicate2quizzAction()
    { 
        $this->QZ_creation = Zend_Registry::get('QZ_duplication');
        $params=$this->_request->getParams();

        if($params['qn0']=="") :
            unset($this->QZ_creation->qz_step1) ;
            unset($this->QZ_creation->qz_step2) ;
            $this->_redirect("/quizz/create1quizz?submenuId=ML2-SL22");
        else :
            $this->QZ_creation->qz_step2=$params;
            $obj = new Ep_Quizz_Quizz() ;
            $obj->insertQuizz($this->QZ_creation->qz_step1, $this->QZ_creation->qz_step2, $this->adminLogin->userId) ;
            $this->_view->successMsg    =   "Quizz cr&eacute;&eacute; avec succ&egrave;s" ;
        endif ;

        unset($this->QZ_creation->qz_step1) ;
        unset($this->QZ_creation->qz_step2) ;
        $this->_view->render("create3_quizz") ;
    }

    /* Listing all quizzes */
    public function listallquizzAction()
    {
        $categories=$this->_arrayDb->loadArrayv2("EP_ARTICLE_CATEGORY", $this->_lang) ;
        $this->_view->categories = $categories;
        
        if($_REQUEST['search_button']!="")
        {
            $quizztitle = $_GET['quizztitle'];
            $category = $_GET['category'];
            $status = $_GET['status'];
            $start_date = $_GET['start_date'];
            $end_date = $_GET['end_date'];

            $this->_view->quizztitle=$quizztitle;
            $this->_view->category=$category;
            $this->_view->status=$status;
            $this->_view->start_date=$start_date;
            $this->_view->end_date=$end_date;
            
            if($quizztitle != '')
                $condition[] = "q.title LIKE '".$quizztitle."%'" ;
            if($category!='' && $category!='all')
                $condition[] = "q.category='".$category."'" ;
            if($status!='')
                $condition[] = "q.status='".$status."'" ;
           
            if($start_date!='' && $end_date!='')
            {
                $start_date = date('Y-m-d', strtotime($creation_date)) ;
                $end_date = date('Y-m-d', strtotime($end_date)) ;
                $condition[] = " q.creation_date >= '".$start_date."' AND q.creation_date <= '".$end_date."'" ;
            }
            
            $condtn = ( $condition ? implode(' AND ', $condition) : '' ) ;
        }
        else
        {
			if($this->adminLogin->type!="superadmin")	
				$condtn = " q.status=1";
			else
				$condtn = "";
		}
        
        $quizzlistobj = new Ep_Quizz_Quizz() ;
        $quizzlist =  $quizzlistobj->GetquizzList($condtn) ;
       
        $userobj = new Ep_User_UserPlus() ;
        foreach ($quizzlist as $key => $value) {
            $username = $userobj->getUsersName($value['created_by']) ;
            $quizzlist[$key]['login'] = $username[0]['login'] ;
			$quizzlist[$key]['linkcount']=$quizzlistobj->CheckQuizzLinked($quizzlist[$key]['id']);
        }
        
        $i = 0;
        while ($i < sizeof($quizzlist)) {
            $quizzlist[$i]['category'] = $categories[$quizzlist[$i]['category']] ;
            $i++ ;
        }  
        
        if($this->adminLogin->userId == '110823103540627' ||  $this->adminLogin->userId == '111113163826982') :
            $this->_view->editao = 1 ;
        else :
            $this->_view->editao = 0 ;
        endif ;
       
	    $this->_view->usertype = $this->adminLogin->type;
        $this->_view->quizzlist = $quizzlist ;
        $this->_view->render("listquizzes") ;
    }
    
    /* View quizz info */
    public function viewquizzAction()
    {
        $params=$this->_request->getParams();
        $obj = new Ep_Quizz_Quizz() ;
        $datas = $obj->viewQuizz($params['id']);
        foreach($datas as $qz)
        {
            $qns[$qz['quest_id']] = $qz['question'];
            $ans[$qz['quest_id']][] = array('ans_id'=>$qz['ans_id'], 'r_ans_id'=>$qz['r_ans_id'], 'option'=>$qz['options']); 
        }
        //echo '<pre>';print_r($qns);print_r($ans);exit;

        $this->_view->quizz_title=$datas[0]['title'];
        $this->_view->qns=$qns;
        $this->_view->ans=$ans;
        $this->_view->nums=array('k','i','ii','iii','iv','v','vi','vii','viii');
        $this->_view->page_title="Edit-place Admin : View quizz";
        $this->_view->render("view_quizz") ;
    }
    
    /* Delete quizz */
    public function delquizzAction()
    {
        $params=$this->_request->getParams() ;
        $obj = new Ep_Quizz_Quizz() ;
        $obj->delQuizz($params['id']) ;
    }
    
    /* Delete question */
    public function delquestionAction()
    {
        $params=$this->_request->getParams() ;
        $obj = new Ep_Quizz_Quizz() ;
        $obj->delQuestion($params['id']) ;
    }
    
    /* Listing participants */
    public function listparticipantsAction()
    {
        $params=$this->_request->getParams() ;
        $obj = new Ep_Quizz_Quizz() ;
		if($params['ao'])
			$participantslist  =   $obj->GetParticipantsList($params['id'],$params['ao']) ;
		else
			$participantslist  =   $obj->GetParticipantsList($params['id']) ;
        $this->_view->participantslist =   $participantslist ;
        $this->_view->render("listparticipants") ;
    }
	
	public function deletequizzAction()
	{
		$qid=$_REQUEST['quiz'];
		$quizzobj = new Ep_Quizz_Quizz();
		
		$quizzobj->deleteQuizz($qid);
		echo 'deleted';
		exit;
		
	}	
	
		/*active or inactive the quizz*/
    public function changequizzstatusAction()
    {
        $params=$this->_request->getParams();
        $quizzobj = new Ep_Quizz_Quizz();
        $data = array("status"=>$params['status']);
        $query = "id= '".$params['quizz_id']."'";
        $quizzobj->updateQuizzStatus($data,$query);

    }	
	
	public function quizzlinkedaoAction()
	{
		$params=$this->_request->getParams();
		
		$quizzobj = new Ep_Quizz_Quizz(); 
		$deliveries=$quizzobj->getQuizzLinkedAO($params['quizz']);
		
		$AOarray=array();$ao=0;
		$RecruitArray=array();$recruit=0;
			
			if(count($deliveries)>0)
			{
				foreach ($deliveries as $ditem)
				{
					if($ditem['missiontest']=='yes' && $ditem['test_article']=='yes')
					{
						$RecruitArray[$recruit]['quizz']=$params['quizz'];
						$RecruitArray[$recruit]['id']=$ditem['id'];
						$RecruitArray[$recruit]['title']=$ditem['title'];
						$RecruitArray[$recruit]['type']=$ditem['AOtype'];
						$RecruitArray[$recruit]['created_at']=$ditem['created_at'];
						$RecruitArray[$recruit]['participants']=$ditem['Qparticipants'];
						$recruit++;
					}
					else
					{
						$AOarray[$ao]['quizz']=$params['quizz'];
						$AOarray[$ao]['id']=$ditem['id'];
						$AOarray[$ao]['title']=$ditem['title'];
						$AOarray[$ao]['type']=$ditem['AOtype'];
						$AOarray[$ao]['created_at']=$ditem['created_at'];
						$AOarray[$ao]['participants']=$ditem['Qparticipants'];
						$ao++;
					}
				}
			}
		
		$this->_view->RecruitArray =   $RecruitArray ;
		$this->_view->AOarray =   $AOarray ;
		$this->_view->render("quizz_quizzlinkedao");
	
	}
	
	
}