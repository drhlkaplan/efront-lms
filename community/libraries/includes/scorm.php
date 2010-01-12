<?php
//This file cannot be called directly, only included.
if (str_replace(DIRECTORY_SEPARATOR, "/", __FILE__) == $_SERVER['SCRIPT_FILENAME']) {
    exit;
}


if (!isset($currentUser -> coreAccess['content']) || $currentUser -> coreAccess['content'] == 'change') {
    $options = array(array('image' => '16x16/scorm.png',  'title' => _SCORMTREE,   'link' => basename($_SERVER['PHP_SELF']).'?ctg=scorm',                'selected' => $_GET['scorm_review'] || $_GET['scorm_import'] || $_GET['scorm_export'] ? false : true),
    array('image' => '16x16/unit.png',   'title' => _SCORMREVIEW, 'link' => basename($_SERVER['PHP_SELF']).'?ctg=scorm&scorm_review=1', 'selected' => !$_GET['scorm_review'] ? false : true),
    array('image' => '16x16/import.png', 'title' => _SCORMIMPORT, 'link' => basename($_SERVER['PHP_SELF']).'?ctg=scorm&scorm_import=1', 'selected' => !$_GET['scorm_import'] ? false : true),
    array('image' => '16x16/export.png', 'title' => _SCORMEXPORT, 'link' => basename($_SERVER['PHP_SELF']).'?ctg=scorm&scorm_export=1', 'selected' => !$_GET['scorm_export'] ? false : true));
} else {
    $options = array(array('image' => '16x16/scorm.png',      'title' => _SCORMTREE,   'link' => basename($_SERVER['PHP_SELF']).'?ctg=scorm',                'selected' => $_GET['scorm_review'] || $_GET['scorm_import'] || $_GET['scorm_export'] ? false : true),
    array('image' => '16x16/unit.png', 'title' => _SCORMREVIEW, 'link' => basename($_SERVER['PHP_SELF']).'?ctg=scorm&scorm_review=1', 'selected' => !$_GET['scorm_review'] ? false : true));
}
$smarty -> assign("T_TABLE_OPTIONS", $options);
$currentContent = new EfrontContentTree($currentLesson);
if ($_GET['scorm_review']) {
    $iterator = new EfrontSCORMFilterIterator(new EfrontNodeFilterIterator(new RecursiveIteratorIterator(new RecursiveArrayIterator($currentContent -> tree), RecursiveIteratorIterator :: SELF_FIRST)));
    foreach ($iterator as $key => $value) {
        $scormContentIds[] = $key;
    }
    if (sizeof($scormContentIds)) {
        $result    = eF_getTableData("scorm_data, content, users", "scorm_data.*, content.name as content_name, users.name, users.surname", "scorm_data.users_LOGIN != '' and scorm_data.content_ID IN (".implode(",", $scormContentIds).") and content_ID=content.id and users.login=scorm_data.users_LOGIN");
        $scormData = $result;
    } else {
        $scormData = array();
    }
    foreach ($result as $value) {
        //$scormData[$value['users_LOGIN']] = $value;
    }

    //$smarty -> assign("T_SCORM_DATA", $scormData);
    if (isset($_GET['ajax']) && $_GET['ajax'] == 'scormUsersTable') {
        isset($_GET['limit']) && eF_checkParameter($_GET['limit'], 'uint') ? $limit = $_GET['limit'] : $limit = G_DEFAULT_TABLE_SIZE;

        if (isset($_GET['sort']) && eF_checkParameter($_GET['sort'], 'text')) {
            $sort = $_GET['sort'];
            isset($_GET['order']) && $_GET['order'] == 'desc' ? $order = 'desc' : $order = 'asc';
        } else {
            $sort = 'login';
        }
        $scormData = eF_multiSort($scormData, $sort, $order);
        $smarty -> assign("T_USERS_SIZE", sizeof($scormData));
        if (isset($_GET['filter'])) {
            $scormData = eF_filterData($scormData, $_GET['filter']);
        }
        if (isset($_GET['limit']) && eF_checkParameter($_GET['limit'], 'int')) {
            isset($_GET['offset']) && eF_checkParameter($_GET['offset'], 'int') ? $offset = $_GET['offset'] : $offset = 0;
            $scormData = array_slice($scormData, $offset, $limit);
        }
        $smarty -> assign("T_SCORM_DATA", $scormData);
        $smarty -> display('professor.tpl');
        exit;
    }

    foreach ($scormData as $value) {
        $scormIds[] = $value['id'];
    }

    if (isset($_GET['delete']) && in_array($_GET['delete'], $scormIds)) {
        eF_deleteTableData("scorm_data", "id=".$_GET['delete']);
        $user = EfrontUserFactory::factory($scormData[0]['users_LOGIN']);
        $user -> setSeenUnit($scormData[0]['content_ID'], $currentLesson, false);
        exit;
    }
} else if ($_GET['scorm_import']) {
    if (isset($currentUser -> coreAccess['content']) && $currentUser -> coreAccess['content'] != 'change') {
        eF_redirect("".basename($_SERVER['PHP_SELF'])."?ctg=control_panel&message=".urlencode(_UNAUTHORIZEDACCESS)."&message_type=failure");
    }

    try {
        $smarty -> assign("T_MAX_FILE_SIZE", FileSystemTree :: getUploadMaxSize());
        $maxUploads = 100;
        
        $form = new HTML_QuickForm("upload_scorm_form", "post", basename($_SERVER['PHP_SELF']).'?ctg=scorm&scorm_import=1', "", null, true);
        $form -> registerRule('checkParameter', 'callback', 'eF_checkParameter');                   //Register this rule for checking user input with our function, eF_checkParameter
        
		$form -> addElement('file', 'scorm_file[0]', _UPLOADTHESCORMFILEINZIPFORMAT);
		for ($i = 1; $i < $maxUploads; $i++) {
		    $form -> addElement('file', "scorm_file[$i]", null);
		}

		//$form -> addElement('file', 'scorm_file', _SCORMFILEINZIPFORMAT);
        $form -> setMaxFileSize(FileSystemTree :: getUploadMaxSize() * 1024);
        
        $form -> addElement('text', 'url_upload', _UPLOADFILEFROMURL, 'class = "inputText"');
        $form -> addElement('submit', 'submit_upload_scorm', _SUBMIT, 'class = "flatButton"');

        //@todo: url upload, if not exists, report a human-readable error!
        $timestamp  = time();
        
        if ($form -> isSubmitted() && $form -> validate()) {
            $urlUpload = $form -> exportValue('url_upload');

            $scormFiles = array();
            if ($urlUpload != "" ) {
                FileSystemTree :: checkFile($urlUpload);
                $urlArray    = explode("/", $urlUpload);
                $urlFile     = urldecode($urlArray[sizeof($urlArray) - 1]);
                
                if (!copy($urlUpload, $currentLesson -> getDirectory().$urlFile)) {
                    throw new Exception(_PROBLEMUPLOADINGFILE);
                } else {
                    $scormFiles[] = new EfrontFile($currentLesson -> getDirectory().$urlFile);
                }
            } else {
	            $filesystem = new FileSystemTree($currentLesson -> getDirectory(), true);
                
	            foreach ($_FILES['scorm_file']['name'] as $key => $value) {
	                if (!in_array($value, $scormFiles)) {        //This way we bypass duplicates
	                    try {
	                        $scormFiles[$value] = $filesystem -> uploadFile("scorm_file", $currentLesson -> getDirectory(), $key);
	                    } catch (EfrontFileException $e) {
	                        if ($e -> getCode() != UPLOAD_ERR_NO_FILE) {
	                            throw $e;
	                        }
	                    }
	                }
                }
	            
            }
//pr($scormFiles);exit;
            foreach ($scormFiles as $scormFile) {
	            /* Imports scorm package to database */           
	            $scormFolderName = EfrontFile :: encode(basename($scormFile['name'], '.zip'));
	            $scormPath       = $currentLesson -> getDirectory().$scormFolderName.'/';
	            is_dir($scormPath) OR mkdir($scormPath, 0755);
//pr($scormPath.$scormFile['name']);
//try {
	            $scormFile -> rename($scormPath.$scormFile['name'], true);
//} catch (Exception $e) {pr($e);throw $e;}
	            $fileList   = $scormFile  -> uncompress(false);
	            $scormFile -> delete();

	            $total_fields = array();
	            $resources    = array();
	
	            $manifestFile = new EfrontFile($scormPath.'imsmanifest.xml');
	            EfrontScorm :: import($currentLesson, $manifestFile, $scormFolderName);
            }
            eF_redirect("".basename($_SERVER['PHP_SELF'])."?ctg=scorm&message=".urlencode(_SUCCESSFULLYIMPORTEDSCORMFILE)."&message_type=success");
        }
        $renderer = new HTML_QuickForm_Renderer_ArraySmarty($smarty);
        $form -> accept($renderer);
        $smarty -> assign('T_UPLOAD_SCORM_FORM', $renderer -> toArray());
    } catch (Exception $e) {
        $smarty -> assign("T_EXCEPTION_TRACE", $e -> getTraceAsString());
        $message      = $e -> getMessage().' ('.$e -> getCode().') &nbsp;<a href = "javascript:void(0)" onclick = "eF_js_showDivPopup(\''._ERRORDETAILS.'\', 2, \'error_details\')">'._MOREINFO.'</a>';
        $message_type = failure;
    }
} else if ($_GET['scorm_export']) {
    if (isset($currentUser -> coreAccess['content']) && $currentUser -> coreAccess['content'] != 'change') {
        eF_redirect("".basename($_SERVER['PHP_SELF'])."?ctg=control_panel&message=".urlencode(_UNAUTHORIZEDACCESS)."&message_type=failure");
    }
    $form = new HTML_QuickForm("export_scorm_form", "post", basename($_SERVER['PHP_SELF']).'?ctg=scorm&scorm_export=1', "", null, true);
    $form -> registerRule('checkParameter', 'callback', 'eF_checkParameter');                   //Register this rule for checking user input with our function, eF_checkParameter
    $form -> addElement('submit', 'submit_export_scorm', _EXPORT, 'class = "flatButton"');
    if ($form -> isSubmitted() && $form -> validate()) {
        define ('SCORM_FOLDER', G_ROOTPATH."www/content/scorm_data");
        if (!is_dir(SCORM_FOLDER)) {
            mkdir(SCORM_FOLDER, 0755);
        }
        $scorm_filename = "scorm_lesson".$lessons_id.".zip";

        if (is_file(SCORM_FOLDER."/".$scorm_filename)) {
            unlink(SCORM_FOLDER."/".$scorm_filename);
        }

        $lessons_id = $currentLesson -> lesson['id'];

        try {
            $filesystem = new FileSystemTree($currentLesson -> getDirectory());
            foreach (new EfrontNodeFilterIterator(new RecursiveIteratorIterator($filesystem -> tree, RecursiveIteratorIterator :: SELF_FIRST)) as $key => $value) {
                ($value instanceOf EfrontDirectory) ? $filelist[] = preg_replace("#".$currentLesson -> getDirectory()."#", "", $key).'/' : $filelist[] = preg_replace("#".$currentLesson -> getDirectory()."#", "", $key);
            }

            $lesson_entries = eF_getTableData("content", "id,name,data", "lessons_ID=" . $lessons_id . " and ctg_type!='tests' and active=1");

            require_once("scorm_tools.php");
            create_manifest($lessons_id, $lesson_entries, $filelist, SCORM_FOLDER);

            $scormDirectory = new EfrontDirectory(SCORM_FOLDER  ."/lesson". $lessons_id."/");

            $compressedFile = $scormDirectory -> compress(false, false, true);
            $scormDirectory -> delete();

            $smarty -> assign("T_SCORM_EXPORT_FILE", $compressedFile);
            $smarty -> assign("T_MESSAGE", _SUCCESSFULLYEXPORTEDSCORMFILE);
            $smarty -> assign("T_MESSAGE_TYPE", "success");
        } catch (Exception $e) {
            $smarty -> assign("T_EXCEPTION_TRACE", $e -> getTraceAsString());
            $message      = _SOMEPROBLEMEMERGED.': '.$e -> getMessage().' ('.$e -> getCode().') &nbsp;<a href = "javascript:void(0)" onclick = "eF_js_showDivPopup(\''._ERRORDETAILS.'\', 2, \'error_details\')">'._MOREINFO.'</a>';
            $message_type = "failure";
        }
    }
    $renderer = new HTML_QuickForm_Renderer_ArraySmarty($smarty);
    $form -> accept($renderer);

    $smarty -> assign('T_EXPORT_SCORM_FORM', $renderer -> toArray());

} else {
/*    
    $iterator = new EfrontSCORMFilterIterator(new EfrontNodeFilterIterator(new RecursiveIteratorIterator(new RecursiveArrayIterator($currentContent -> tree), RecursiveIteratorIterator :: SELF_FIRST)));

    $iterator   -> rewind();
    $current    = $iterator -> current();
    $depth      = $iterator -> getDepth();
    $treeString = '';
    $count      = 0;                                //Counts the total number of nodes, used to signify whether the tree has content
    while ($iterator -> valid()) {
        $scormUnitIds[] = $current['id'];
        $iterator -> next();
        if (!isset($currentUser -> coreAccess['content']) || $currentUser -> coreAccess['content'] == 'change') {
            if ($current['ctg_type'] == 'scorm') {
                $toolsString = '<span><a href = "javascript:void(0)" onclick = "convertScorm(this, '.$current['id'].')"><img style = "vertical-align:middle" src = "images/16x16/scorm_to_test.png" title = "'._CONVERTTOSCORMTEST.'" alt = "'._CONVERTTOSCORMTEST.'" border = "0" /></a></span>';
            } else {
                $toolsString = '<span><a href = "javascript:void(0)" onclick = "convertScorm(this, '.$current['id'].')"><img style = "vertical-align:middle" src = "images/16x16/test_to_scorm.png" title = "'._CONVERTTOSCORMCONTENT.'" alt = "'._CONVERTTOSCORMCONTENT.'" border = "0" /></a></span>';
            }
        }
        $treeString  .= '
                    <li style = "white-space:nowrap;" class = "'.($current['ctg_type'] == 'scorm' ? 'scorm' : 'scorm_test').'" id = "node'.$current['id'].'" noDrag = "true" noRename = "true" noDelete = "true">
                        <a class = "treeLink" href = "javascript:void(0)" title = "'.$current['name'].'">'.$current['name']."</a>&nbsp;".$toolsString;

        $iterator -> getDepth() > $depth ? $treeString .= '<ul>' : $treeString .= '</li>';
        for ($i = $depth; $i > $iterator -> getDepth(); $i--) {
            $treeString .= '</ul></li>';
        }
        $current = $iterator -> current();
        $depth   = $iterator -> getDepth();
        $count++;
    }

    if (isset($_GET['set_type']) && isset($_GET['id']) && in_array($_GET['id'], $scormUnitIds)) {        //Set scorm content type through AJAX call
        $unit = new EfrontUnit($_GET['id']);
        $_GET['set_type'] == 'scorm' ? $unit['ctg_type'] = 'scorm' : $unit['ctg_type'] = 'scorm_test';
        $unit -> persist();
        exit;
    }
    //$smarty -> assign("T_SCORM_TREE", $currentContent -> toHTML($iterator, 'dhtmlContentTree', array('expand' => true)));
    $smarty -> assign("T_SCORM_TREE", $treeString);
*/    
    
    $iterator = new EfrontSCORMFilterIterator(new EfrontNodeFilterIterator(new RecursiveIteratorIterator(new RecursiveArrayIterator($currentContent -> tree), RecursiveIteratorIterator :: SELF_FIRST)));    //Default iterator excludes non-active units
    $smarty -> assign("T_SCORM_TREE", $currentContent -> toHTML($iterator));
}

//$scormOptions[] = array('text' => _SCORMEXPORT,       'image' => "32x32/export.png",         'href' => "scorm_export.php?lessons_ID=".$_SESSION['s_lessons_ID'], 'onClick' => "eF_js_showDivPopup('"._SCORMEXPORT."',     2)", 'target' => 'POPUP_FRAME');
//$scormOptions[] = array('text' => _SCORMIMPORT,       'image' => "32x32/import.png",         'href' => "scorm_import.php?lessons_ID=".$_SESSION['s_lessons_ID'], 'onClick' => "eF_js_showDivPopup('"._SCORMIMPORT."',     2)", 'target' => 'POPUP_FRAME');
//$scormOptions[] = array('text' => _REVIEWSCORMDATA,   'image' => "32x32/unit.png",   'href' => "scorm_review.php?lessons_ID=".$_SESSION['s_lessons_ID'], 'onClick' => "eF_js_showDivPopup('"._REVIEWSCORMDATA."', 2)", 'target' => 'POPUP_FRAME');


?>
