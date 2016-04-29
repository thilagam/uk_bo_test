<?php

/**
 * statsController - The controller class for statistics main menu
 *
 * @author
 * @version
 */
 
class EbookersController extends Ep_Controller_Action
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
    // this will fecth the themes select option and category select options//
    public function managelistAction(){
        include_once ROOT_PATH.'BO/nlibrary/script/fileupload/script.php';
        $this->_view->uploadpath = ROOT_PATH.'BO/xlsx/';
        $ep_lang_array = $this->_arrayDb->loadArrayv2("EP_LANGUAGES", $this->_lang);
        $contrib_lang_array=$ep_lang_array;
        $this->_view->Contrib_langs = $contrib_lang_array;
        $ebookers_obj = new Ep_Ebookers_Managelist();
        $this->_view->render("ebookers_managelist");

    }
    //to validate theme name/category name/token name duplication using ajax//
    public function validateAction(){
        if($this->_request->getParam('type') == 'themes') {
            if (in_array(utf8_encode(( $this->_request->getParam('value') )), $_SESSION['theme_name']))
                echo 1;
            else
                echo 0;
        }
        elseif($this->_request->getParam('type') == 'category'){
            $cat_themes_id = explode(",",$this->_request->getParam('cat_themes_id'));
            for($i=0;$i<count($cat_themes_id);$i++) {
                $key = utf8_encode(($this->_request->getParam('value'))) . "###" . $cat_themes_id[$i];
                if (in_array($key, $_SESSION['category_name_and_themes_id'])) {
                    echo 1;break;
                }
                else
                    echo 0;
            }
        }
        elseif($this->_request->getParam('type') == 'tokens'){
            $tokens_category_id = explode(",",$this->_request->getParam('tokens_category_id'));
            for($i=0;$i<count($tokens_category_id);$i++) {
                $key = utf8_encode(($this->_request->getParam('value') )) . "###" . $tokens_category_id[$i] . "###" . $this->_request->getParam('tokens_themes_id');
                if (in_array($key, $_SESSION['token_name_category_id_themes_id'])){
                    echo 1;break;
                }
                else
                    echo 0;
            }
        }
        exit;
    }
    // to load the List of themes/categosy/tokens/sampletext (this function is called using AJAX)//
    //note this function will return json_encoded array of results//
    public function loadDatatableAction(){
        $ebookers_obj = new Ep_Ebookers_Managelist();
        if($this->_request->getParam('datatable') == 'themes') {
            $data = $ebookers_obj->loadThemes();
            $i=0;
            unset($_SESSION['theme_name']);//unset any already existing sessions//
            while($data[$i]){
                $_SESSION['theme_name'][$i] = ($data[$i]['theme_name']);//saved in session for later validate purpose//
                $data[$i]['action'] = '<button type="button" class="btn btn-info btn-lg" data-toggle="modal" data-target="#editModal" onclick="view_edit_list(\''.$data[$i]['theme_id'].'\',\'themes\');">Edit</button>&nbsp;<button class="btn btn-default btn-lg" onclick="delete_list(\''.$data[$i]['theme_id'].'\',\'themes\');">Delete</button>';
                $i++;
            }
        }
        elseif($this->_request->getParam('datatable') == 'category') {
            $data = $ebookers_obj->loadCategory();
            $i=0;
            unset($_SESSION['category_name_and_themes_id']);//unset any already existing sessions//
            while($data[$i]){
                $_SESSION['category_name_and_themes_id'][$i] = ($data[$i]['category_name'])."###".$data[$i]['themes_id'];//saved in session for later validate purpose//
                $data[$i]['action'] = '<button type="button" class="btn btn-info btn-lg" data-toggle="modal" data-target="#editModal" onclick="view_edit_list(\''.$data[$i]['cat_id'].'\',\'category\');">Edit</button>&nbsp;<button class="btn btn-default btn-lg" onclick="delete_list(\''.$data[$i]['cat_id'].'\',\'category\');">Delete</button>';
                $i++;
            }
        }
        elseif($this->_request->getParam('datatable') == 'tokens') {
            $data = $ebookers_obj->loadTokens();
            $i=0;
            unset($_SESSION['token_name_category_id_themes_id']);//unset any already existing sessions//
            while($data[$i]){
                $_SESSION['token_name_category_id_themes_id'][$i] = ($data[$i]['token_name'])."###".$data[$i]['category_id']."###".$data[$i]['themes_id'];
                $data[$i]['action'] = '<button type="button" class="btn btn-info btn-lg" data-toggle="modal" data-target="#editModal" onclick="view_edit_list(\''.$data[$i]['token_id'].'\',\'tokens\');">Edit</button>&nbsp;<button class="btn btn-default btn-lg" onclick="delete_list(\''.$data[$i]['token_id'].'\',\'tokens\');">Delete</button>';
                $i++;
            }
        }
        elseif($this->_request->getParam('datatable') == 'sample_text'){
            /*fetching the laungaes*/
            $ep_lang_array = $this->_arrayDb->loadArrayv2("EP_LANGUAGES", $this->_lang);
            $data = $ebookers_obj->loadSampletext();
            $temp = $data;
            $i=0;
            while($data[$i]){
                $data[$i]['action'] = '<button type="button" class="btn btn-info btn-lg" data-toggle="modal" data-target="#editModal" onclick="view_edit_list(\''.$data[$i]['sample_id'].'\',\'sample_text\');">Edit</button>&nbsp;<button class="btn btn-default btn-lg" onclick="delete_list(\''.$data[$i]['sample_id'].'\',\'sample_text\');">Delete</button>';
                $i++;
                /*$j=0;
                while (list($key, $value) = each($ep_lang_array)) {
                    if($key === $temp[$j]['language']){
                        $data[$i]['language'] = $value;
                    }
                    $j++;
                }*/
            }
        }

        $results = array(
            "sEcho" => 1,
            "iTotalRecords" => count($data),
            "iTotalDisplayRecords" => count($data),
            "aaData"=>$data);
        echo json_encode($results);exit;
    }
    public function importAction(){
        require_once APP_PATH_ROOT.'nlibrary/tools/PHPExcel.php';
        $objReader = PHPExcel_IOFactory::createReader('Excel2007');
        $objReader->setReadDataOnly(true);

        $objPHPExcel = $objReader->load("/home/sites/site5/web/FO/invoice/client/xls/sample.xlsx");
        $objWorksheet = $objPHPExcel->getActiveSheet();

        $highestRow = $objWorksheet->getHighestRow();
        $highestColumn = $objWorksheet->getHighestColumn();

        $highestColumnIndex = PHPExcel_Cell::columnIndexFromString($highestColumn);

        echo '<table border="1">' . "\n";
        for ($row = 1; $row <= $highestRow; ++$row) {
          echo '<tr>' . "\n";
          for ($col = 0; $col <= $highestColumnIndex; ++$col) {
            echo '<td>' . $objWorksheet->getCellByColumnAndRow($col, $row)->getValue() . '</td>' . "\n";
          }
          echo '</tr>' . "\n";
        }
        echo '</table>' . "\n";
        exit;
    }
    public function exportAction(){
        $ebookers_obj = new Ep_Ebookers_Managelist();
        $data = $ebookers_obj->loadManageList();
        {
            error_reporting(E_ALL);
            ini_set('display_errors', TRUE);
            ini_set('display_startup_errors', TRUE);
            date_default_timezone_set('Europe/London');
            require_once APP_PATH_ROOT.'nlibrary/tools/PHPExcel.php';
            require_once APP_PATH_ROOT.'nlibrary/tools/PHPExcel/Writer/Excel2007.php';
            $file_name = time()."excel_file.xlsx";
            $file = "/home/sites/site5/web/FO/invoice/client/xls/".$file_name;
            $objPHPExcel = new PHPExcel();
            $objPHPExcel->setActiveSheetIndex(0);
                $rowCount = 1;
                $styleArray = array(
                    'fill' => array(
                        'type' => PHPExcel_Style_Fill::FILL_SOLID,
                        'color' => array('rgb'=>'000000'),
                    ),
                    'font'  => array(
                        'bold'  => true,
                        'size'  => 14,
                        'color'  => array('rgb' => 'FFFFFF'),
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
                $objPHPExcel->getActiveSheet()->SetCellValue('A' . $rowCount, 'THEME ');
                $objPHPExcel->getActiveSheet()->getStyle('B1')->applyFromArray($styleArray);
                $objPHPExcel->getActiveSheet()->SetCellValue('B' . $rowCount, 'CATEGORY ');
                $objPHPExcel->getActiveSheet()->getStyle('C1')->applyFromArray($styleArray);
                $objPHPExcel->getActiveSheet()->SetCellValue('C' . $rowCount, 'TOKENS ');
                $objPHPExcel->getActiveSheet()->getStyle('D1')->applyFromArray($styleArray);
                $objPHPExcel->getActiveSheet()->SetCellValue('D' . $rowCount, 'TOKENS CODE ');
                foreach ($data as $result){
                    $rowCount++;
                    if($result['theme_name'] != $theme_name) {
                        $objPHPExcel->getActiveSheet()->SetCellValue('A' . $rowCount, $result['theme_name']);
                        $temprow=$rowCount;
                    }
                    else{
                        $objPHPExcel->getActiveSheet()->mergeCells('A'.$temprow.':A'.$rowCount);
                    }
                    if($result['category_name'] != $category_name) {
                        $objPHPExcel->getActiveSheet()->SetCellValue('B' . $rowCount, $result['category_name']);
                    }
                    $objPHPExcel->getActiveSheet()->SetCellValue('C' . $rowCount, $result['token_name']);
                    $objPHPExcel->getActiveSheet()->SetCellValue('C' . $rowCount, $result['token_code']);
                    $theme_name = $result['theme_name'];
                    $category_name = $result['category_name'];

                }

            /* for loop to resize all the width of cell*/
            foreach(range('A','D') as $columnID)
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
    // inserting of new themes/categosy/tokens/sampletext into the database //
    public function updateManagelistAction(){
        $ebookers_obj = new Ep_Ebookers_Managelist();
        //to call a functio to update themes//
        if($this->_request->getParam('submit') != ''){
            $ebookers_obj->updateManagelist($this->_request->getParams());
            exit;
        }
    }
    // deletion  of existing themes/categosy/tokens/sampletext into the database (NOTE:only the enum STATUS is changed to deleted)//
    public function deleteListAction(){
        $ebookers_obj = new Ep_Ebookers_Managelist();
        //to call a function to delete themes//
        $ebookers_obj->deleteList($this->_request->getParams());
        //unset($_REQUEST);
        exit;
    }
    // to fetch the selected theme/category/tokens/sampletext and display the result in a form for editing//
    public function viewEditListAction(){
        $ebookers_obj = new Ep_Ebookers_Managelist();
        //to call a functio to update themes//
        $data = $ebookers_obj->viewEditList($this->_request->getParams());

        if($this->_request->getParam('type') == 'themes') {
            $this->_view->data = $data;
        }
        elseif($this->_request->getParam('type') == 'category'){
            $result = $ebookers_obj->loadThemesEditOption();
            $options = '<option value="">Select</option>';
            $i=0;
            $themes_id = $data[$i]['themes_id'];
            while($result[$i]){
                if($result[$i]['theme_id'] == $themes_id)
                    $options .= '<option value="' . $result[$i]['theme_id'] . '" selected>' . utf8_encode($result[$i]['theme_name']) . '</option>';
                else
                    $options .= '<option value="' . $result[$i]['theme_id'] . '">' . utf8_encode($result[$i]['theme_name']) . '</option>';
                $i++;
            }
            $this->_view->themes_option = $options;
            $this->_view->data = $data;
        }
        elseif($this->_request->getParam('type') == 'tokens') {
            $result = $ebookers_obj->loadThemesEditOption();
            $options = '<option value="">Select</option>';
            $i=0;
            $themes_id = $data[$i]['themes_id'];
            while($result[$i]){
                if($result[$i]['theme_id'] == $themes_id)
                    $options .= '<option value="' . $result[$i]['theme_id'] . '" selected>' . utf8_encode($result[$i]['theme_name']) . '</option>';
                else
                    $options .= '<option value="' . $result[$i]['theme_id'] . '">' . utf8_encode($result[$i]['theme_name']) . '</option>';
                $i++;
            }
            $this->_view->themes_option = $options;
            $result = $ebookers_obj->loadCategoryEdit2Option($data);
            $options = '<option value="">Select</option>';
            $i=0;
            $category_id = $data[$i]['category_id'];
            while($result[$i]) {
                if($result[$i]['cat_id'] == $category_id)
                    $options .= '<option value="' . $result[$i]['cat_id'] . '" selected>' . utf8_encode($result[$i]['category_name']) . '</option>';
                else
                    $options .= '<option value="' . $result[$i]['cat_id'] . '">' . utf8_encode($result[$i]['category_name']) . '</option>';
                $i++;
            }
            $this->_view->category_option = $options;
            $this->_view->data = $data;
        }
        elseif($this->_request->getParam('type') == 'sample_text') {
            $result = $ebookers_obj->loadThemesEditOption();
            $options = '<option value="">Select</option>';
            $i=0;
            $themes_id = $data[$i]['themes_id'];
            while($result[$i]){
                if($result[$i]['theme_id'] == $themes_id)
                    $options .= '<option value="' . $result[$i]['theme_id'] . '" selected>' . utf8_encode($result[$i]['theme_name']) . '</option>';
                else
                    $options .= '<option value="' . $result[$i]['theme_id'] . '">' . utf8_encode($result[$i]['theme_name']) . '</option>';
                $i++;
            }
            $this->_view->themes_option = $options;
            $result = $ebookers_obj->loadCategoryEdit2Option($data);
            $options = '<option value="">Select</option>';
            $i=0;
            $category_id = $data[$i]['category_id'];
            while($result[$i]) {
                if($result[$i]['cat_id'] == $category_id)
                    $options .= '<option value="' . $result[$i]['cat_id'] . '" selected>' . utf8_encode($result[$i]['category_name']) . '</option>';
                else
                    $options .= '<option value="' . $result[$i]['cat_id'] . '">' . utf8_encode($result[$i]['category_name']) . '</option>';
                $i++;
            }
            $this->_view->category_option = $options;
            $result = $ebookers_obj->loadTokensEditOption($data);
            $options = '<option value="">Select</option>';
            $i=0;
            $token_id = $data[$i]['token_id'];
            while($result[$i]) {
                if($result[$i]['token_id'] == $token_id)
                    $options .= '<option value="' . $result[$i]['token_id'] . '" selected>' . utf8_encode($result[$i]['token_name']) . '</option>';
                else
                    $options .= '<option value="' . $result[$i]['token_id'] . '">' . utf8_encode($result[$i]['token_name']) . '</option>';
                $i++;
            }
            $this->_view->token_option = $options;
            $this->_view->data = $data;

        }
            //unset($_REQUEST);
        $ep_lang_array = $this->_arrayDb->loadArrayv2("EP_LANGUAGES", $this->_lang);
        $contrib_lang_array=$ep_lang_array;
        $this->_view->Contrib_langs = $contrib_lang_array;

        $this->_view->render("ebookers_formpopup");
    }
    //to update the selected theme/category/tokens/sampletext and update the respective row in database//
    public function editManagelistAction(){
        $ebookers_obj = new Ep_Ebookers_Managelist();
        //to call a functio to update themes//
        if($this->_request->getParam('submit') != ''){
            $ebookers_obj->editManagelist($this->_request->getParams());
            //unset($_REQUEST);
            exit;
        }
    }
    //to load the theme select option called using ajax(note:this function will construct a html option and send it to display) //
    public function loadThemesOptionAction(){
        $ebookers_obj = new Ep_Ebookers_Managelist();
        //to call a functio to update themes//
        $result =  $ebookers_obj->loadThemesOption($this->_request->getParams());
        $options = '<option value="">Select</option>';
        $i=0;
        $themes_id = $data[$i]['themes_id'];
        while($result[$i]){
            if($result[$i]['theme_id'] == $themes_id)
                $options .= '<option value="' . $result[$i]['theme_id'] . '" selected>' . utf8_encode($result[$i]['theme_name']) . '</option>';
            else
                $options .= '<option value="' . $result[$i]['theme_id'] . '">' . utf8_encode($result[$i]['theme_name']) . '</option>';
            $i++;
        }
        echo $options;
        //unset($_REQUEST);
        exit;
    }
    //to load the category select option called using ajax(note:this function will construct a html option and send it to display) //
    public function loadCategoryOptionAction(){
        $ebookers_obj = new Ep_Ebookers_Managelist();
        //to call a functio to update themes//
        $result =  $ebookers_obj->loadCategoryOption($this->_request->getParams());
        $options = '<option value="">Select</option>';
        $i=0;
        while($result[$i]) {
            $options .= '<option value="' . $result[$i]['cat_id'] . '">' . utf8_encode($result[$i]['category_name']) . '</option>';
            $i++;
        }
        echo $options;
        //unset($_REQUEST);
        exit;

    }
    public function loadTokensOptionAction(){
        $ebookers_obj = new Ep_Ebookers_Managelist();
        //to call a functio to update themes//
        $result =  $ebookers_obj->loadTokensOption($this->_request->getParams());
        $options = '<option value="">Select</option>';
        $i=0;
        $token_id = $data[$i]['token_id'];
        while($result[$i]) {
            if($result[$i]['token_id'] == $token_id)
                $options .= '<option value="' . $result[$i]['token_id'] . '" selected>' . utf8_encode($result[$i]['token_name']) . '</option>';
            else
                $options .= '<option value="' . $result[$i]['token_id'] . '">' . utf8_encode($result[$i]['token_name']) . '</option>';
            $i++;
        }
        echo $options;
        //unset($_REQUEST);
        exit;

    }
	
	function weeklyReportAction()
	{		
		ob_start();
		$reportParams=$this->_request->getParams();
		//print_r($reportParams);
		$type=$reportParams['type'];
		$raction=$reportParams['raction'];
		$language_selected=$reportParams['language'];
		if(!$language_selected)
			$language_selected='uk';
		
		$auth=FALSE;
		
		if($type=='html')
		{
			$auth=TRUE;			
		}	
		else{
			$auth=$this->authenticateEbooker();
		}
		
		
		if($auth)
		{
			$reportObj= new Ep_Ebookers_Report();
			$themeStencilsDetails=$reportObj->getThemeStencils($language_selected);
			if($themeStencilsDetails)
			{
				$total_stencils_count=0;
				$total_intergrated_count=0;
				$completion_percentage=0;
				$total_written_count=0;
				$total_proofreaded_count=0;
				$total_validated_count=0;

                $total_written_words=0;
                $total_proofreaded_words=0;
                $total_validated_words=0;
                $total_integrated_words=0;

				foreach($themeStencilsDetails as $index=>$theme)
				{
					//echo $theme['written_stencils'];exit;
                    $manual_count=$theme['manual_count'];
					$themeStencilsDetails[$index]['written_stencils_count']=$theme['written_stencils_count']+$manual_count;
					$themeStencilsDetails[$index]['proofread_stencils_count']=$theme['proofread_stencils_count']+$manual_count;
					$themeStencilsDetails[$index]['validated_stencils_count']=$theme['validated_stencils_count']+$manual_count;
					$themeStencilsDetails[$index]['integrated_count']=$theme['integrated_count']+$manual_count;
					
					
					
					$total_stencils_count+=$theme['stencils_count'];
					$total_written_count+=$themeStencilsDetails[$index]['written_stencils_count'];
					$total_proofreaded_count+=$themeStencilsDetails[$index]['proofread_stencils_count'];
					$total_validated_count+=$themeStencilsDetails[$index]['validated_stencils_count'];
					$total_intergrated_count+=$themeStencilsDetails[$index]['integrated_count'];

                    //words count
                    $total_written_words+=$theme['written_words'];
                    $total_proofreaded_words+=$theme['proofreaded_words'];
                    $total_validated_words+=$theme['validated_words'];
                    $total_integrated_words+=$theme['integrated_words'];
				}
				$completion_percentage=(($total_intergrated_count/$total_stencils_count)*100);
				
				$this->_view->themeStencilsDetails=$themeStencilsDetails;
				$this->_view->total_stencils_count=$total_stencils_count;
				$this->_view->completion_percentage=$completion_percentage;
				
				$this->_view->total_written_count=number_format($total_written_count,0,'.',' ');
				$this->_view->total_proofreaded_count=number_format($total_proofreaded_count,0,'.',' ');
				$this->_view->total_intergrated_count=number_format($total_intergrated_count,0,'.',' ');
				$this->_view->total_validated_count=number_format($total_validated_count,0,'.',' ');

                $this->_view->total_written_words=number_format($total_written_words,0,'.',' ');
                $this->_view->total_proofreaded_words=number_format($total_proofreaded_words,0,'.',' ');
                $this->_view->total_validated_words=number_format($total_validated_words,0,'.',' ');
                $this->_view->total_integrated_words=number_format($total_integrated_words,0,'.',' ');
				
			}
			
			//get languages in which stencils created
			$slanguages=$reportObj->getStencilsLanguages();
				
			$languagesArray=$this->_arrayDb->loadArrayv2("EP_LANGUAGES", $this->_lang);
			foreach($languagesArray  as $lang=>$name)
			{
				if(in_array($lang,$slanguages))
				{
					$languages[$lang]=$name;
				}
			}
			//print_r($languagesArray);exit;
			natsort($languages);
			//print_r($languages);
			
			$this->_view->languages=$languages;
			$this->_view->language_selected=$language_selected;
			
			//print_r($themeStencilsDetails);		
			$this->_view->type=$type;			
			$this->render('ebooker-weekly-report');
		}
		
		if($type=='html')
		{
			$downloadDir=APP_PATH_ROOT.'ebookers-weekly-report/'.date("Y-m-d").'/';			
			if(!is_dir($downloadDir))
			{   
				mkdir($downloadDir,0777);
				chmod($downloadDir,0777);
			}
			$file_name=date("dmY").'_'.$language_selected.'.html';
			//echo ob_get_contents();	
			$html_content=ob_get_contents();
			$doc = new DOMDocument();
			$doc->loadHTML($html_content);

			$selector = new DOMXPath($doc);
			foreach($selector->query('//a[contains(attribute::class, "dlCta")]') as $e ) {
				$e->parentNode->removeChild($e);
			}
			$html_content=$doc->saveHTML();			
			file_put_contents($downloadDir.$file_name, $html_content);	
			ob_end_clean();
		}	
	}
	function authenticateEbooker()
	{
		//unset($_SERVER['PHP_AUTH_USER']);
		$valid_passwords = array ("admin" => "admin");
		$valid_users = array_keys($valid_passwords);

		$user = $_SERVER['PHP_AUTH_USER'];
		$pass = $_SERVER['PHP_AUTH_PW'];

		$validated = (in_array($user, $valid_users)) && ($pass == $valid_passwords[$user]);

		if (!$validated) {
		  header('WWW-Authenticate: Basic realm="Login please"');
		  header('HTTP/1.0 401 Unauthorized');
		  die ("Not authorized");
		}
		else{
			return TRUE;
		}
	}
	
	/*download weekly report*/
	function downloadReportAction()
	{
		$downloadParams=$this->_request->getParams();
		$file_type=$downloadParams['type'];
		
		$language_selected=$downloadParams['language'];
		if(!$language_selected)
			$language_selected='uk';
		
		$language_name=$this->getCustomName("EP_LANGUAGES",$language_selected);
		
		$reportObj= new Ep_Ebookers_Report();
		$themeStencilsDetails=$reportObj->getThemeStencils($language_selected);
		if($themeStencilsDetails)
		{
			$html='<table width="100%">
						<tr>
							<td align="center" colspan="6" style="font-size:18px;"><b>ebookers.fr weekly report - '.$language_name.' - '.date("d/m/Y").'</b></td>
						</tr>
					</table>
					<table width="100%">
						<tr><td align="center" colspan="6"></td></tr>
					</table>
					';
			$html.='<table border="0" width="100%" cellspacing=0 cellpadding=3>';
			$html.='<tr>
						<th bgcolor="#ed5565" align="left" style="color:#FFFFFF;font-size:13px;" colspan="6"><b>Split per category</b></th>
					</tr>
					<tr>
					<td align="center" bgcolor="#a4aeb9" style="color:#FFFFFF;font-size:11px;"><b>Category</b></td>
					<td align="center" bgcolor="#a4aeb9" style="color:#FFFFFF;font-size:11px;"><b>Stencils</b></td>
					<td align="center" bgcolor="#a4aeb9" style="color:#FFFFFF;font-size:11px;"><b>Written</b></td>
					<td align="center" bgcolor="#a4aeb9" style="color:#FFFFFF;font-size:11px;"><b>Proof readed</b></td>
					<td align="center" bgcolor="#a4aeb9" style="color:#FFFFFF;font-size:11px;"><b>Validated</b></td>
					<td align="center" bgcolor="#a4aeb9" style="color:#FFFFFF;font-size:11px;"><b>Integrated</b></td>
				</tr>';
			
			foreach($themeStencilsDetails as $index=>$theme)
			{
				//echo $theme['written_stencils'];exit;
				$manual_count=$theme['manual_count'];
				$themeStencilsDetails[$index]['written_stencils_count']=$theme['written_stencils_count']+$manual_count;
				$themeStencilsDetails[$index]['proofread_stencils_count']=$theme['proofread_stencils_count']+$manual_count;
				$themeStencilsDetails[$index]['validated_stencils_count']=$theme['validated_stencils_count']+$manual_count;
				$themeStencilsDetails[$index]['integrated_count']=$theme['integrated_count']+$manual_count;
				
				if($index%2==0)
					$bgcolor=' bgcolor="#f9f9f9"';
				else
					$bgcolor='';
				
				$html.='<tr  style="font-size: 12px;">
							<td  '.$bgcolor.' align="center">'.$theme['theme_name'].'</td>				
							<td  '.$bgcolor.' align="center">'.$themeStencilsDetails[$index]['stencils_count'].'</td>
							<td  '.$bgcolor.' align="center">'.$themeStencilsDetails[$index]['written_stencils_count'].'</td>
							<td  '.$bgcolor.' align="center">'.$themeStencilsDetails[$index]['proofread_stencils_count'].'</td>
							<td  '.$bgcolor.' align="center">'.$themeStencilsDetails[$index]['validated_stencils_count'].'</td>	
							<td  '.$bgcolor.' align="center">'.$themeStencilsDetails[$index]['integrated_count'].'</td>				
						</tr>';
			}
			
			$html.='</table>';
			
			//echo $html;exit;
			
			$downloadDir=APP_PATH_ROOT.'ebookers-weekly-report/'.date("Y-m-d").'/';
			
			if(!is_dir($downloadDir))
			{   
				mkdir($downloadDir,0777);
				chmod($downloadDir,0777);
			}
			
			if($file_type=='pdf')
				$file_name="ebookers-weekly-report-".date("dmY").'_'.$language_selected.'.pdf';
			else if($file_type=='xlsx')
				$file_name="ebookers-weekly-report-".date("dmY").'_'.$language_selected.'.xlsx';
			
			
			$file_path=$downloadDir.$file_name;
			
			if($file_type=='pdf')
			{	
				require_once(APP_PATH_ROOT.'dompdf/dompdf_config.inc.php');
				if ( get_magic_quotes_gpc() )
					$html = stripslashes($html);
						
				//echo $html;exit;
				$dompdf = new DOMPDF();
				$dompdf->load_html( $html);
				$dompdf->set_paper("a4");
				$dompdf->render();
				//$dompdf->stream($file_name.".pdf");
				$pdf = $dompdf->output();
				file_put_contents($file_path, $pdf);
			}
			else if($file_type=='xlsx')
			{
				
				//echo $file_path;exit;
				convertHtmltableToXlsx($html,$file_path);
			}
			
			if(file_exists($file_path))
				$this->_redirect("/BO/download-ebooker-report.php?date=".date("Y-m-d")."&file_name=".$file_name);
            
            exit;
		}
		//echo $html;
	}
	function getCustomName($type,$name)
	{
		$categories_array = $this->_arrayDb->loadArrayv2($type, $this->_lang);
		return $categories_array[$name];
	}
	
    //added by naseer on 21-09-2015//
    public function deleteThemeLangaugeAction(){
        $ebookers_obj = new Ep_Ebookers_Managelist();
        //to call a function to delete themes//
        $ebookers_obj->deleteThemeLangauge($this->_request->getParams());
        //unset($_REQUEST);
        exit;
    }
    /* *** added on 16.12.2015 *** */
    // called as ajax function to delte category laung //
	 public function deleteCategoryLangaugeAction(){
        $ebookers_obj = new Ep_Ebookers_Managelist();
        //to call a function to delete themes//
        $ebookers_obj->deleteCategoryLangauge($this->_request->getParams());
        //unset($_REQUEST);
        exit;
    }

	

}