<?php
/**
 * BnpController - The controller class for Bnp main menu
 *
 * @author
 * @version
 */
class BnpController extends Ep_Controller_Action
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
        $enp_obj = new Ep_Bnp_Managelist();
        $this->_view->region_options = $enp_obj->fecthRegionOption();
        $this->_view->city_options = $enp_obj->fecthCityOption();
        $this->_view->render("bnp_managelist");
    }
    //to validate theme name/category name/token name duplication using ajax//
    public function validateAction(){
        if($_REQUEST['type'] == 'region') {
            if (in_array(utf8_encode(($_REQUEST['value'])), $_SESSION['region_name']))
                echo 1;
            else
                echo 0;
        }
        elseif($_REQUEST['type'] == 'city'){
            $city_region_id = explode(",",$_REQUEST['city_region_id']);
            for($i=0;$i<count($city_region_id);$i++) {
                $key = utf8_encode(($_REQUEST['value'])) . "###" . $city_region_id[$i];
                if (in_array($key, $_SESSION['city_name_and_region_id'])) {
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
        $bnp_obj = new Ep_Bnp_Managelist();
        if($this->_request->getParam('datatable') == 'region') {
            $data = $bnp_obj->loadRegion();
            $i=0;
            unset($_SESSION['region_name']);//unset any already existing sessions//
            while($data[$i]){
                $_SESSION['region_name'][$i] = ($data[$i]['region_name']);//saved in session for later validate purpose//
                $data[$i]['action'] = '<button type="button" class="btn btn-info btn-lg" data-toggle="modal" data-target="#editModal" onclick="view_edit_list(\''.$data[$i]['region_id'].'\',\'region\');">Edit</button>&nbsp;<button class="btn btn-default btn-lg" onclick="delete_list(\''.$data[$i]['region_id'].'\',\'region\');">Delete</button>';
                $i++;
            }
        }
        elseif($this->_request->getParam('datatable') == 'city') {
            $data = $bnp_obj->loadCity();
            $i=0;
            unset($_SESSION['city_name_and_region_id']);//unset any already existing sessions//
            while($data[$i]){
                $_SESSION['city_name_and_region_id'][$i] = ($data[$i]['city_name'])."###".$data[$i]['region_id'];//saved in session for later validate purpose//
                $data[$i]['action'] = '<button type="button" class="btn btn-info btn-lg" data-toggle="modal" data-target="#editModal" onclick="view_edit_list(\''.$data[$i]['city_id'].'\',\'city\');">Edit</button>&nbsp;<button class="btn btn-default btn-lg" onclick="delete_list(\''.$data[$i]['city_id'].'\',\'city\');">Delete</button>';
                $i++;
            }
        }
        elseif($this->_request->getParam('datatable') == 'sample_text'){
            $data = $bnp_obj->loadSampletext();
            $i=0;
            while($data[$i]){
                $data[$i]['action'] = '<button type="button" class="btn btn-info btn-lg" data-toggle="modal" data-target="#editModal" onclick="view_edit_list(\''.$data[$i]['sample_id'].'\',\'sample_text\');">Edit</button>&nbsp;<button class="btn btn-default btn-lg" onclick="delete_list(\''.$data[$i]['sample_id'].'\',\'sample_text\');">Delete</button>';
                $i++;
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
        $bnp_obj = new Ep_Bnp_Managelist();
        $data = $bnp_obj->loadManageList();
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
    // inserting of new city/region/sampletext into the database //
    public function updateManagelistAction(){
        $bnp_obj = new Ep_bnp_Managelist();
        //to call a functio to update themes//
        if($this->_request->getParam('submit') != ''){
            $bnp_obj->updateManagelist($_REQUEST);
            unset($_REQUEST);
            exit;
        }
    }
    // deletion  of existing themes/categosy/tokens/sampletext into the database (NOTE:only the enum STATUS is changed to deleted)//
    public function deleteListAction(){
        $bnp_obj = new Ep_Bnp_Managelist();
        //to call a function to delete themes//
        $bnp_obj->deleteList($_REQUEST);
        unset($_REQUEST);
        exit;
    }
    // to fetch the selected theme/category/tokens/sampletext and display the result in a form for editing//
    public function viewEditListAction(){
        $bnp_obj = new Ep_Bnp_Managelist();
        //to call a functio to update themes//
        $this->_view->data = $bnp_obj->viewEditList($_REQUEST);
        unset($_REQUEST);
        $this->_view->render("bnp_formpopup");
    }
    //to update the selected theme/category/tokens/sampletext and update the respective row in database//
    public function editManagelistAction(){
        $bnp_obj = new Ep_Bnp_Managelist();
        //to call a functio to update themes//
        if($this->_request->getParam('submit') != ''){
            $bnp_obj->editManagelist($_REQUEST);
            unset($_REQUEST);
            exit;
        }
    }
    //to load the theme select option called using ajax(note:this function will construct a html option and send it to display) //
    public function loadRegionOptionAction(){
        $bnp_obj = new Ep_Bnp_Managelist();
        //to call a functio to update themes//
        echo $bnp_obj->loadRegionOption($_REQUEST);
        unset($_REQUEST);
        exit;

    }
    //to load the category select option called using ajax(note:this function will construct a html option and send it to display) //
    public function loadCityOptionAction(){
        $bnp_obj = new Ep_Bnp_Managelist();
        //to call a functio to update themes//
        echo $bnp_obj->loadCityOption($_REQUEST);
        unset($_REQUEST);
        exit;

    }
}
