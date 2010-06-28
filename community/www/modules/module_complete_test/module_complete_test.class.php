<?php

/**

 * Complete test class

 *

 * This class implements the Complete test eFront module

 * @version 0.1

 */
class module_complete_test extends EfrontModule
{
    public function getName() {
        return "Correct test";
    }
    public function getPermittedRoles() {
        return array("professor");
    }
    public function onInstall() {
        return true;
    }
    public function onUnInstall() {
        return true;
    }
    public function getModule() {
        return true;
    }

    public function isLessonModule() {
  return true;
 }

    public function getLessonSmartyTpl() {
        return $this -> getControlPanelSmartyTpl();
    }

    public function getSmartyTpl() {

     $smarty = $this -> getSmartyVar();
     $smarty -> assign("T_CURRENT_TEST_MODULE_BASEURL", $this -> moduleBaseUrl);

     $currentUser = $this -> getCurrentUser();
     if ($currentLesson = $this -> getCurrentLesson()) {
      $currentContent = new EfrontContentTree($currentLesson);
      if (isset($_GET['test']) && isset($_GET['login'])) {
       $currentUnit = new EfrontUnit($_GET['test']);
       $user = EfrontUserFactory::factory($_GET['login']);

       $test = new EfrontTest($currentUnit['id'], true);
       $status = $test -> getStatus($user);

       $form = new HTML_QuickForm("test_form", "post", $this -> moduleBaseUrl.'&login='.$_GET['login'].'&test='.$_GET['test'], "", null, true);
       switch ($status['status']) {
        case 'incomplete':
         if (!$testInstance = unserialize($status['completedTest']['test'])) {
          throw new EfrontTestException(_TESTCORRUPTEDASKRESETEXECUTION, EfrontTestException::CORRUPTED_TEST);
         }

         if ($testInstance -> time['pause'] && isset($_GET['resume'])) {
          $testInstance -> time['pause'] = 0;
          $testInstance -> time['resume'] = time();
          //unset($testInstance -> currentQuestion);
          $testInstance -> save();
         }
         $remainingTime = $testInstance -> options['duration'] - $testInstance -> time['spent'] - (time() - $testInstance -> time['resume']);

         $nocache = false;
         if ($form -> isSubmitted() || ($testInstance -> options['duration'] && $remainingTime < 0) || $status['status'] == 'incomplete') {
          $nocache = true;
         }
         $testInstance -> options['pause_test'] = 0;
         $testInstance -> options['onebyone'] = 0;
         $testInstance -> options['duration'] = 0;

         $testString = $testInstance -> toHTMLQuickForm($form, false, false, false, $nocache);
         $testString = $testInstance -> toHTML($testString, $remainingTime);

         break;
        case 'completed':case 'passed':case 'failed':case 'pending':

         if (!$testInstance = unserialize($status['completedTest']['test'])) {
          throw new EfrontTestException(_TESTCORRUPTEDASKRESETEXECUTION, EfrontTestException::CORRUPTED_TEST);
         }

         //$url          = basename($_SERVER['PHP_SELF']).'?ctg=content&view_unit='.$_GET['view_unit'];
         //($testInstance -> options['redoable'] = 1);
         $testString = $testInstance -> toHTMLQuickForm($form, false, true);
         $testString = $testInstance -> toHTMLSolved($testString);

         if (isset($_GET['test_analysis'])) {
          require_once 'charts/php-ofc-library/open-flash-chart.php';

          list($parentScores, $analysisCode) = $testInstance -> analyseTest();

          $smarty -> assign("T_CONTENT_ANALYSIS", $analysisCode);
          $smarty -> assign("T_TEST_DATA", $testInstance);

          $status = $testInstance -> getStatus($user -> user['login']);
          $smarty -> assign("T_TEST_STATUS", $status);

          if (isset($_GET['display_chart'])) {
           $url = $this -> moduleBaseUrl.'&login='.$user -> user['login'].'&test='.$currentUnit['id'].'&test_analysis=1&selected_unit='.$_GET['selected_unit'].'&show_chart=1&show_solved_test='.$_GET['show_solved_test'];
           echo $testInstance -> displayChart($url);
           exit;
          } elseif (isset($_GET['show_chart'])) {
           echo $testInstance -> calculateChart($parentScores);
           exit;
          }
         }

         break;
        default:
         if (isset($_GET['confirm'])) {
          $testInstance = $test -> start($user -> user['login']);
          eF_redirect("".$this -> moduleBaseUrl.'&login='.$_GET['login'].'&test='.$_GET['test']);
          exit;
         } else {
          $testInstance = $test;
          $test -> getQuestions(); //This way the test's questions are populated, and we will be needing this information
          $testInstance -> options['random_pool'] && $testInstance -> options['random_pool'] >= sizeof($testIn) ? $questionsNumber = $testInstance -> options['random_pool'] : $questionsNumber = sizeof($testInstance -> questions);
         }
         break;
       }

       if (isset($_GET['ajax'])) {
        $testInstance -> handleAjaxActions();
       }

       //Calculate total questions. If it's already set, then we are visiting an unsolved test, and the questions number is already calculated (and may be different that the $testInstance -> questions size)
       if (!isset($questionsNumber)) {
        $questionsNumber = sizeof($testInstance -> questions);
       }
       //$smarty -> assign("T_REMAINING_TIME", $remainingTime);
       $smarty -> assign("T_TEST_QUESTIONS_NUM", $questionsNumber);
       $smarty -> assign("T_TEST_DATA", $testInstance);
       $smarty -> assign("T_TEST", $testString);
       $smarty -> assign("T_TEST_STATUS", $status);

       if (!$status['status'] || ($status['status'] == 'incomplete' && $testInstance -> time['pause'])) { //If the user hasn't confirmed he wants to do the test, display confirmation buttons
        $smarty -> assign("T_SHOW_CONFIRMATION", true);
       } else { //The user confirmed he wants to do the test, so display it

        $form -> addElement('hidden', 'time_start', $timeStart); //This element holds the time the test started, so we know the remaining time even if the user left the system
        $form -> addElement('submit', 'submit_test', _SUBMITTEST, 'class = "flatButton" onclick = "if (typeof(checkedQuestions) != \'undefined\' && (unfinished = checkQuestions())) return confirm(\''._YOUHAVENOTCOMPLETEDTHEFOLLOWINGQUESTIONS.': \'+unfinished+\'. '._AREYOUSUREYOUWANTTOSUBMITTEST.'\');"');

        if ($form -> isSubmitted() && $form -> validate()) {
         $values = $form -> exportValues();

         $submitValues = $form -> getSubmitValues();

         foreach($testInstance -> questions as $id => $question) {
          $submitValues['question_time'][$id] || $submitValues['question_time'][$id] === 0 ? $question -> time = $submitValues['question_time'][$id] : null;
         }

         //Set the unit as "seen"
         $testInstance -> complete($values['question']);
         $user -> setSeenUnit($currentUnit, $currentLesson, 1);

         eF_redirect("".$this -> moduleBaseUrl.'&login='.$_GET['login'].'&test='.$_GET['test']);
         exit;
        }

        $renderer = new HTML_QuickForm_Renderer_ArraySmarty($smarty);
        $form -> accept($renderer);
        $smarty -> assign('T_TEST_FORM', $renderer -> toArray());
       }
      } else {

       $form = $this -> buildImportCsvForm();
       list($selectedTest, $uploadedFile) = $this -> handleImportCsvForm($form);
       $renderer = prepareFormRenderer($form);
       $smarty -> assign('T_UPLOAD_FORM', $renderer -> toArray());

       $lessonUsers = $currentLesson -> getUsers('student'); //Get all users that have this lesson

       $form = $this -> buildCorrelateDataForm($selectedTest, $uploadedFile);
       list($dateFormat, $userFormat) = $this -> handleCorrelateDataForm($form);
       $renderer = prepareFormRenderer($form);
       $smarty -> assign('T_CORRELATE_FORM', $renderer -> toArray());

       $form = $this -> buildMappingsForm($dateFormat, $userFormat);





       if (isset($_GET['ajax']) && $_GET['ajax'] == 'usersTable') {
        $testsIterator = new EfrontTestsFilterIterator(new EfrontVisitableFilterIterator(new EfrontNodeFilterIterator(new RecursiveIteratorIterator(new RecursiveArrayIterator($currentContent -> tree), RecursiveIteratorIterator :: SELF_FIRST))));
        foreach ($testsIterator as $key => $value) {
         $tests[$key] = $value['name'];
        }
        $select_units = & HTML_QuickForm :: createElement('select', 'tests');
        $select_units -> loadArray($tests);
        $smarty -> assign("T_TESTS_SELECT", $select_units -> toHTML());

        isset($_GET['limit']) && eF_checkParameter($_GET['limit'], 'uint') ? $limit = $_GET['limit'] : $limit = G_DEFAULT_TABLE_SIZE;

        if (isset($_GET['sort']) && eF_checkParameter($_GET['sort'], 'text')) {
         $sort = $_GET['sort'];
         isset($_GET['order']) && $_GET['order'] == 'desc' ? $order = 'desc' : $order = 'asc';
        } else {
         $sort = 'login';
        }
        $lessonUsers = eF_multiSort($lessonUsers, $sort, $order);
        $smarty -> assign("T_USERS_SIZE", sizeof($lessonUsers));
        if (isset($_GET['filter'])) {
         $lessonUsers = eF_filterData($lessonUsers, $_GET['filter']);
        }
        if (isset($_GET['limit']) && eF_checkParameter($_GET['limit'], 'int')) {
         isset($_GET['offset']) && eF_checkParameter($_GET['offset'], 'int') ? $offset = $_GET['offset'] : $offset = 0;
         $lessonUsers = array_slice($lessonUsers, $offset, $limit);
        }

        $smarty -> assign("T_ALL_USERS", $lessonUsers);
     $smarty -> assign("T_COMPLETETEST_BASELINK", $this -> moduleBaseLink);
        $smarty -> display($this -> moduleBaseDir . "module_complete_test.tpl");
        exit;
       }
      }
     }
     $smarty -> assign("T_COMPLETETEST_BASELINK", $this -> moduleBaseLink);
     $smarty -> assign("T_COMPLETETEST_BASEURL", $this -> moduleBaseUrl);
     return $this -> moduleBaseDir . "module_complete_test.tpl";

    }

    public function getCenterLinkInfo() {
        $optionArray = array('title' => _COMPLETE_TEST_CORRECTTEST,
                             'image' => $this -> moduleBaseLink.'images/tests.png',
                             'link' => $this -> moduleBaseUrl);
        $centerLinkInfo = $optionArray;

        return $centerLinkInfo;
    }

    public function getLessonCenterLinkInfo() {
        $currentUser = $this -> getCurrentUser();
        if ($currentUser -> getType() == 'professor') {
            return $this -> getCenterLinkInfo();
        }
    }

 public function getNavigationLinks() {

        $currentUser = $this -> getCurrentUser();
  $currentLesson = $this -> getCurrentLesson();

        $basicNavArray = array (array ('title' => _MYLESSONS, 'onclick' => "location='".$currentUser -> getRole($currentLesson).".php?ctg=lessons';top.sideframe.hideAllLessonSpecific();"),
        array ('title' => $currentLesson -> lesson['name'], 'link' => $currentUser -> getRole($this -> getCurrentLesson()) . ".php?ctg=control_panel"),
                 array ('title' => _COMPLETE_TEST_CORRECTTEST, 'link' => $this -> moduleBaseUrl));

        return $basicNavArray;

    }

    private function buildImportCsvForm() {
     $currentContent = new EfrontContentTree($this -> getCurrentLesson());
     $testsIterator = new EfrontTestsFilterIterator(new EfrontVisitableFilterIterator(new EfrontNodeFilterIterator(new RecursiveIteratorIterator(new RecursiveArrayIterator($currentContent -> tree), RecursiveIteratorIterator :: SELF_FIRST))));
     foreach ($testsIterator as $key => $value) {
      $tests[$key] = $value['name'];
     }
     $form = new HTML_QuickForm("upload_form", "post", $this -> moduleBaseUrl.'&tab=import', "", null, true);
     $form -> addElement('file', 'upload_file', _UPLOADFILE);
     $form -> addElement('select', 'select_test', _TEST, $tests);
     $form -> addElement('submit', 'submit', _SUBMIT, 'class = "flatButton"');

     return $form;
    }

    private function handleImportCsvForm(&$form) {
     $currentUser = $this -> getCurrentUser();
     $smarty = $this -> getSmartyVar();
     if ($form -> isSubmitted() && $form -> validate()) {
      $values = $form -> exportValues();
      if (!is_dir($currentUser -> user['directory']."/temp")) {
       mkdir($currentUser -> user['directory']."/temp", 0755);
      }
      $filesystem = new FileSystemTree($currentUser -> user['directory']."/temp");
      $uploadedFile = $filesystem -> uploadFile('upload_file');

      if (($handle = fopen($uploadedFile['path'], "r")) !== FALSE) {
       while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        $parsedContents[] = $data;
       }
       fclose($handle);
      }

      $selectedTest = new EfrontTest($values['select_test'], true);
      $smarty -> assign("T_TEST_QUESTIONS", $selectedTest -> getQuestions());
      $smarty -> assign("T_COMPLETED_TEST_PARSED_CONTENTS", array_slice($parsedContents, 0, 10));
     }

     return array($selectedTest, $uploadedFile);
    }

    private function buildCorrelateDataForm($selectedTest, $uploadedFile) {
     if (isset($selectedTest)) {
      $selectedTestId = $selectedTest -> test['id'];
     } elseif ($_GET['test_id']) {
      $selectedTest = new EfrontTest($_GET['test_id']);
     }
     $form = new HTML_QuickForm("correlate_form", "post", $this -> moduleBaseUrl.'&tab=import&test_id='.$selectedTestId, "", null, true);

     $form -> addElement('hidden', 'uploaded_file');
     $form -> addElement('hidden', 'test_id');
     $form -> addElement('hidden', 'start_data_row', '', 'id = "start_data_row"');
     $form -> addElement('hidden', 'date_source_hidden', '', 'id = "date_source_hidden"');
     $form -> addElement('hidden', 'user_source_hidden', '', 'id = "user_source_hidden"');
     $form -> addElement('hidden', 'score_source_hidden', '', 'id = "score_source_hidden"');
     if (isset($selectedTest)) {
      $form -> setDefaults(array('test_id' => $selectedTest -> test['id'], 'uploaded_file' => $uploadedFile['path']));
      foreach($selectedTest -> getQuestions() as $key => $value) {
       $form -> addElement('hidden', $key.'_answer_source_hidden', '', 'id = "'.$key.'_answer_source_hidden"');
       $form -> addElement('hidden', $key.'_score_source_hidden', '', 'id = "'.$key.'_score_source_hidden"');
      }
     }
     $form -> addElement('submit', 'submit', _SUBMIT, 'class = "flatButton"');

     return $form;
    }

    private function handleCorrelateDataForm(&$form) {
     if ($form -> isSubmitted() && $form -> validate()) {
      $formValues = $form -> exportValues();
      $selectedTest = new EfrontTest($formValues['test_id']);
      $uploadedFile = new EfrontFile($formValues['uploaded_file']);
      if (($handle = fopen($uploadedFile['path'], "r")) !== FALSE) {
       while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        $parsedContents[] = $data;
       }
       fclose($handle);
      }
      $parsedContents = array_slice($parsedContents, $formValues['start_data_row']);

      $dateColumn = $formValues['date_source_hidden'];
      $userColumn = $formValues['user_source_hidden'];
      $scoreColumn = $formValues['score_source_hidden'];
      foreach ($parsedContents as $key => $value) {
       if ($value[$userColumn] && $value[$dateColumn]) { //Meaning a valid row
        $testDates[] = $value[$dateColumn];
        $testUsers[] = $value[$userColumn];
        $testScores[] = $value[$scoreColumn];
       } else {
        unset($parsedContents[$key]);
       }
      }

      $dateFormat = $this -> analyzeContentsToFindDateFormat($testDates);
      $userFormat = $this -> analyzeContentsToFindUserFormat($testUsers, $allUsers);
      return (array($dateFormat, $userFormat));
/*    		

    		//$scoreFormat = $this -> analyzeContentsToFindScoreFormat($testScores);

    		foreach($selectedTest -> getQuestions() as $id => $question) {

    			if ($question['type'] == 'multiple_one' || $question['type'] == 'multiple_many') {

    				foreach ($parsedContents as $value) {

    					$questionAnswers[] = $value[$formValues[$id.'_answer_source_hidden']];

    					$questionScores[]  = $value[$formValues[$id.'_score_source_hidden']];

    					

    					//pr("results for question $id: ".$questionAnswer.' - '.$questionScore);

    				}

    			}

    		}

    		if (max($questionScores) == 1) {

    			$questionScoreFormat = 1;

    		} elseif (max($questionScores) == 100) {

    			$questionScoreFormat = 100;

    		} else {

    			$questionScoreFormat = false;

    		}

    		pr($questionAnswers);

    		pr($dateFormat);

    		pr($userFormat);

    		pr($questionScoreFormat);

*/
      //pr($scoreFormat);
     }
    }
    private function buildMappingsForm($selectedTest, $uploadedFile) {
     //$dateFormat
    }
    private function importCSVContents($parsedContents) {
      foreach ($parsedContents as $value) {
       $login = '';
       $userNameInCSV = $value[$userColumn];
       if ($userFormat == 'login') {
        $userLogin = $userNameInCSV;
       } elseif ($userFormat == 'surname, name') {
        $userLogin = array_search($userNameInCSV, $surnameName);
       } elseif ($userFormat == 'name, surname') {
        $userLogin = array_search($userNameInCSV, $nameSurname);
       }
       if ($userLogin && in_array($userLogin, array_keys($lessonUsers))) {
        $login = $userLogin;
       } else if ($userLogin) {
        $existingUsersButNotInLesson[] = $userLogin;
       } else {
        $notFoundUsers[] = $userNameInCSV;
       }
       /*

    			 $user = $value[$userColumn];

    			 $testInstance = $selectedTest -> start($user -> user['login']);

    			 $testInstance -> complete($values['question']);

    			 $user  -> setSeenUnit($currentUnit, $currentLesson, 1);

    			 */
      }
      pr($existingUsersButNotInLesson);
      pr($notFoundUsers);
      exit;
      pr($testDates);
      pr($parsedContents);
      pr($values);exit;
    }
 private function analyzeContentsToFindUserFormat($testUsers, $allUsers) {
  $allUsers = eF_getTableData("users", "login, name, surname");
  foreach ($allUsers as $value) {
   $surnameName[$value['login']] = $value['surname'].', '.$value['name'];
   $nameSurname[$value['login']] = $value['name'].', '.$value['surname'];
   $logins[$value['login']] = $value['login'];
  }
  $count = 0;
  $userFormat = false;
  while (!$userFormat && $testUsers[$count]) {
   $value = $testUsers[$count++];
   if (in_array($value, $logins)) {
    $userFormat = 'login';
   } elseif (in_array($value, $surnameName)) {
    $userFormat = 'surname, name';
   } elseif (in_array($value, $nameSurname)) {
    $userFormat = 'name, surname';
   } else {
    $userFormat = false;
   }
  }
  return $userFormat;
 }
 private function analyzeContentsToFindScoreFormat($testScores) {
  pr($testScores);
 }
    private function analyzeContentsToFindDateFormat($testDates) {
     $count = 0;
     $dateFormat = false;
     while (!$dateFormat && $testDates[$count]) {
      $value = $testDates[$count++];
      $parts = explode("/", $value);
      if (sizeof($parts) == 1) {
       $dateFormat = 'timestamp';
      } elseif ($parts[0] > 1000) {
       $dateFormat = 'YYYY/MM/DD';
      } elseif ($parts[0] > 12) {
       $dateFormat = 'DD/MM/YYYY';
      } elseif ($parts[1] > 12) {
       $dateFormat = 'MM/DD/YYYY';
      }
     }
     return $dateFormat;
    }
    private function importCsvFile() {
    }
    private function parseCSVContents() {
    }
}
?>
