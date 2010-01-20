<?php
//This file cannot be called directly, only included.
if (str_replace(DIRECTORY_SEPARATOR, "/", __FILE__) == $_SERVER['SCRIPT_FILENAME']) {
    exit;
}


$loadScripts[] = 'includes/groups';
    if (isset($currentUser -> coreAccess['users']) && $currentUser -> coreAccess['users'] == 'hidden') {
        eF_redirect("".basename($_SERVER['PHP_SELF'])."?ctg=control_panel&message=".urlencode(_UNAUTHORIZEDACCESS)."&message_type=failure");
    }
    if (isset($_GET['delete_user_group']) && eF_checkParameter($_GET['delete_user_group'], 'id')) {
        if (isset($currentUser -> coreAccess['users']) && $currentUser -> coreAccess['users'] != 'change') {
            eF_redirect("".basename($_SERVER['PHP_SELF'])."?ctg=control_panel&message=".urlencode(_UNAUTHORIZEDACCESS)."&message_type=failure");
        }
        try {
            $group = new EfrontGroup($_GET['delete_user_group']);
            $group -> delete();
        } catch (Exception $e) {
            $message      = $e -> getMessage();
            header("HTTP/1.0 500 ");
            echo urlencode($e -> getMessage()).' ('.$e -> getCode().')';
        }
        exit;
    } elseif (isset($_GET['deactivate_user_group']) && eF_checkParameter($_GET['deactivate_user_group'], 'id')) {
        if (isset($currentUser -> coreAccess['users']) && $currentUser -> coreAccess['users'] != 'change') {
            echo urlencode(_UNAUTHORIZEDACCESS);
            exit;
        }
        try {
            $group = new EfrontGroup($_GET['deactivate_user_group']);
            $group -> group['active'] = 0;
            $group -> persist();    
            echo "0";        
        } catch (Exception $e) {
            $message = $e -> getMessage();
            header("HTTP/1.0 500 ");
            echo urlencode($e -> getMessage()).' ('.$e -> getCode().')';
        }
        exit;
    } elseif (isset($_GET['activate_user_group']) && eF_checkParameter($_GET['activate_user_group'], 'id')) {
        if (isset($currentUser -> coreAccess['users']) && $currentUser -> coreAccess['users'] != 'change') {
            echo urlencode(_UNAUTHORIZEDACCESS);
            exit;
        }
        try {
            $group = new EfrontGroup($_GET['activate_user_group']);
            $group -> group['active'] = 1;
            $group -> persist();
            echo "1";
        } catch (Exception $e) {
            $message = $e -> getMessage();
            header("HTTP/1.0 500 ");
            echo urlencode($e -> getMessage()).' ('.$e -> getCode().')';
        }
        exit;
    } elseif (isset($_GET['add_user_group']) || ( isset($_GET['edit_user_group']) && eF_checkParameter($_GET['edit_user_group'], 'id')) ) {
        $loadScripts[] = 'scriptaculous/scriptaculous';
        $loadScripts[] = 'scriptaculous/effects';

        isset($_GET['add_user_group']) ? $postTarget = 'add_user_group=1' : $postTarget = "edit_user_group=".$_GET['edit_user_group'];
        $form = new HTML_QuickForm("add_group_form", "post", basename($_SERVER['PHP_SELF'])."?ctg=user_groups&$postTarget", "", null, true);
        $form -> registerRule('checkParameter', 'callback', 'eF_checkParameter');
        $form -> registerRule('checkNotExist', 'callback', 'eF_checkNotExist');

        $form -> addElement('text', 'name', _NAME, 'class = "inputText"');

        $form -> addElement('text', 'description', _DESCRIPTION, 'class = "inputText"');
        $form -> addRule('name', _THEFIELD.' '._TYPENAME.' '._ISMANDATORY, 'required', null, 'client');
        $form -> addRule('name', _INVALIDFIELDDATA, 'checkParameter', 'text');

        $form -> addElement('text', 'group_key', _UNIQUEGROUPKEY, 'class = "inputText" id="group_key_id"');
        $smarty -> assign("T_NEW_UNIQUE_KEY", md5(time()));    // timestamp guarantess uniqueness

        $form -> addElement('text', 'key_max_usage', _MAXGROUPKEYUSAGE, 'class = "inputText"');

        $form -> registerRule('onlydigits','regex','/^\d+/');
        $form -> addRule('key_max_usage',_INVALIDFIELDDATAFORFIELD.' "'._MAXGROUPKEYUSAGE,'onlydigits');
        $form -> addRule('key_max_usage', _INVALIDFIELDDATAFORFIELD.' "'._MAXGROUPKEYUSAGE, 'callback', create_function('$a', 'return ($a >= 0);'));


        $form -> addElement('select', 'group_status' , _GROUPUSERSTATUS, array("0" => _NOCOMMONGROUPUSERSTATUS, "1" => _ACTIVE, "2" => _NOTACTIVE),'class = "inputText"');

        if ($GLOBALS['configuration']['onelanguage']) {
            $form -> addElement('hidden', 'group_languages_NAME', $GLOBALS['configuration']['default_language']);
        } else {
            $form -> addElement('select', 'group_languages_NAME', _GROUPLANGUAGE, array_merge(array("0" => _NOCOMMONGROUPLANGUAGE) ,EfrontSystem :: getLanguages(true)));
        }

        $roles = EfrontLessonUser :: getLessonsRoles(true);
		$roles[0] = _NOCOMMONGROUPUSERTYPE;
		ksort($roles);
        $form -> addElement('select', 'group_usertype' , _GROUPUSERTYPE, $roles, 'class = "inputText"');
        $form -> addElement('advcheckbox', 'assign_to_all_new', _ASSIGNLESSONSTOALLNEWMEMBERS,  null, 'class = "inputCheckBox"', array(0, 1));
        $form -> addElement('advcheckbox', 'is_default', _ISTHEDEFAULTEFRONTSYSTEMGROUP,  null, 'class = "inputCheckBox"', array(0, 1));

        if (isset($_GET['edit_user_group'])) {
            try {
                $currentGroup = new EfrontGroup($_GET['edit_user_group']);
            } catch (Exception $e) {
                $message      = $e -> getMessage();
                $message_type = 'failure';
            }

            $form -> setDefaults(array('name' 					=> $currentGroup -> group['name'],
                                       'description' 			=> $currentGroup -> group['description'],
                                       'group_status'           => $currentGroup -> group['users_active'],
                                       'group_languages_NAME'   => $currentGroup -> group['languages_NAME'],
                                       'group_usertype'         => $currentGroup -> group['user_types_ID'],
                                       'assign_to_all_new'      => $currentGroup -> group['assign_profile_to_new'],
                                       'group_key'              => $currentGroup -> group['unique_key'],
                                       'is_default'             => $currentGroup -> group['is_default'],
                                       'key_max_usage'          => isset($currentGroup -> group['key_max_usage']) ? $currentGroup -> group['key_max_usage'] : 0));

            
            //$smarty -> assign("T_USERGROUP_NAME", $currentGroup -> group['name']);
            // To report the remaining key uses
            if ($currentGroup -> group['key_max_usage'] > 0) {
                $smarty -> assign("T_LIMITED_KEY_USES", (($currentGroup -> group['key_max_usage'] - $currentGroup -> group['key_current_usage'] > 0)?$currentGroup -> group['key_max_usage'] - $currentGroup -> group['key_current_usage']:0) . " / " .$currentGroup -> group['key_max_usage']);
            }
        }

        if (isset($currentUser -> coreAccess['users']) && $currentUser -> coreAccess['users'] != 'change') {
            $form -> freeze();
        } else {
            $form -> addElement('submit', 'submit_type', _SUBMIT, 'class = "flatButton"');

            if ($form -> isSubmitted() && $form -> validate()) {
                if (isset($_GET['edit_user_group'])) {
                    try {
                        $currentGroup -> group['name']        = $form -> exportValue('name');
                        $currentGroup -> group['description'] = $form -> exportValue('description');

                        //if ($currentGroup -> group['users_active']  != $form -> exportValue('group_status') || $currentGroup -> group['languages_NAME'] != $form -> exportValue('group_languages_NAME') || $currentGroup -> group['user_types_ID'] != $form -> exportValue('group_usertype')) {
                            $currentGroup -> group['users_active']            = $form -> exportValue('group_status');
                            $currentGroup -> group['languages_NAME']          = $form -> exportValue('group_languages_NAME');
                            $currentGroup -> group['user_types_ID']           = $form -> exportValue('group_usertype');

                            $currentGroup -> updateUsers();
                        //}

                        $currentGroup -> group['assign_profile_to_new']   = $form -> exportValue('assign_to_all_new');
                        $currentGroup -> group['unique_key']   = $form -> exportValue('group_key');
                        $currentGroup -> group['is_default']   = $form -> exportValue('is_default');
                        $currentGroup -> group['key_max_usage'] = $form -> exportValue('key_max_usage');
                        if ($currentGroup -> group['key_max_usage'] == 0) {
                            $currentGroup -> group['key_current_usage'] = 0;
                        }
                        $currentGroup -> persist();
                        eF_redirect("".basename($_SERVER['PHP_SELF'])."?ctg=user_groups&message=".urlencode(_SUCCESFULLYUPDATEDGROUP)."&message_type=success");
                    } catch (Exception $e){
                        $message      = _SOMEPROBLEMEMERGED;
                        $message_type = 'failure';
                    }
                } else {
                    $content['name']                    = $form -> exportValue('name');
                    $content['description']             = $form -> exportValue('description');
                    $content['users_active']            = $form -> exportValue('group_status');
                    $content['languages_NAME']          = $form -> exportValue('group_languages_NAME');
                    $content['user_types_ID']           = $form -> exportValue('group_usertype');
                    $content['assign_profile_to_new']   = $form -> exportValue('assign_to_all_new');
                    $content['unique_key']              = $form -> exportValue('group_key');
                    $content['is_default']              = $form -> exportValue('is_default');
                    $content['key_max_usage']           = $form -> exportValue('key_max_usage') ? $form -> exportValue('key_max_usage') : 0;
                    try {
                        $group = EfrontGroup::create($content);
                    } catch (Exception $e){
                        $message      = $e -> getMessage();;
                        $message_type = 'failure';
                    }
                    eF_redirect("".basename($_SERVER['PHP_SELF'])."?ctg=user_groups&edit_user_group=".$group -> group['id']."&tab=users&message=".urlencode(_SUCCESFULLYADDEDGROUP)."&message_type=success");
                }
            }
        }
        $renderer = new HTML_QuickForm_Renderer_ArraySmarty($smarty);

        $form -> setJsWarnings(_BEFOREJAVASCRIPTERROR, _AFTERJAVASCRIPTERROR);
        $form -> setRequiredNote(_REQUIREDNOTE);
        $form -> accept($renderer);
        $smarty -> assign('T_USERGROUPS_FORM_R', $renderer -> toArray());

        if (isset($_GET['edit_user_group'])) {
            $groupUsers = $currentGroup -> getUsers();
            $result     = eF_getTableData("users", "*");
            $users      = array();
            foreach ($result as $user) {
                $user['in_group'] = false;
                if (in_array($user['login'], $groupUsers[$user['user_type']])) {
                    $user['in_group']      = true;
                    $users[$user['login']] = $user;
                } else if ($user['active']) {
                    $users[$user['login']] = $user;
                }

            }

            if (isset($_GET['ajax']) && $_GET['ajax'] == "usersTable") {
                isset($_GET['limit']) && eF_checkParameter($_GET['limit'], 'uint') ? $limit = $_GET['limit'] : $limit = G_DEFAULT_TABLE_SIZE;

                if (isset($_GET['sort']) && eF_checkParameter($_GET['sort'], 'text')) {
                    $sort = $_GET['sort'];
                    isset($_GET['order']) && $_GET['order'] == 'desc' ? $order = 'desc' : $order = 'asc';
                } else {
                    $sort = 'login';
                }

                if (isset($_GET['filter'])) {
                    $users = eF_filterData($users, $_GET['filter']);
                }
                $users = eF_multiSort($users, $sort, $order);
                $smarty -> assign("T_USERS_SIZE", sizeof($users));

                if (isset($_GET['limit']) && eF_checkParameter($_GET['limit'], 'int')) {
                    isset($_GET['offset']) && eF_checkParameter($_GET['offset'], 'int') ? $offset = $_GET['offset'] : $offset = 0;
                    $users = array_slice($users, $offset, $limit);
                }

                $smarty -> assign("T_GROUP_USERS", $users);
                $smarty -> display('administrator.tpl');
                exit;
            }


            // Group lessons
            $groupLessons = $currentGroup -> getLessons();
            $result       = EfrontLesson::getStandAloneLessons(true);
            // roles already defined
            $smarty -> assign("T_ROLES_ARRAY", $roles);

            $lessons = array();
            foreach ($result as $value) {
                $lesson              = $value -> lesson;
                $lesson['in_group']  = false;
                $lesson['user_type'] = 'student';
                if (in_array($lesson['id'], array_keys($groupLessons))) {
                    $lesson['in_group']                  = true;
                    $lessons[$lesson['id']]              = $lesson;
                    $lessons[$lesson['id']]['user_type'] = $groupLessons[$lesson['id']]['user_type'];
                } else if ($lesson['active']) {
                    $lessons[$lesson['id']] = $lesson;
                }

            }

            // Group courses
            $groupCourses = $currentGroup -> getCourses();
            $result       = EfrontCourse::getCourses(true);
            $courses      = array();
            foreach ($result as $value) {
                $course = $value -> course;
                $course['in_group'] = false;
                $course['user_type'] = 'student';
                if (in_array($course['id'], array_keys($groupCourses))) {
                    $course['in_group']      = true;
                    $courses[$course['id']] = $course;
                    $courses[$course['id']]['user_type'] = $groupCourses[$course['id']]['user_type'];
                } else if ($course['active']) {
                    $courses[$course['id']] = $course;
                }
            }

            if (isset($_GET['postAjaxRequest'])) {
                if (isset($_GET['login']) && eF_checkParameter($_GET['login'], 'login')) {
                    if ($users[$_GET['login']]['in_group']) {
                        $currentGroup -> removeUsers($_GET['login']);
                        //eF_deleteTableData("users_to_groups", "users_LOGIN='".$_GET['login']."' and groups_ID=".$_GET['edit_user_group']);
                        echo "Deleted user ".$_GET['login']." from group";
                    } else {
                        $currentGroup -> addUsers($_GET['login']);
                        echo "Added user ".$_GET['login']." to group";
                    }
                } else if (isset($_GET['addAll']) && $_GET['table'] == "usersTable") {
                        // the $users variable is defined before the if condition
                        isset($_GET['filter']) ? $users = eF_filterData($users, $_GET['filter']) : null;

                        foreach ($users as $user) {
                            if (!$user['in_group']) {
                                $currentGroup -> addUsers($user['login']);
                                //$fields_insert = array("users_LOGIN" =>  $user['login'],
                                //                       "groups_ID"   =>  $_GET['edit_user_group']);
                                //eF_insertTableData("users_to_groups", $fields_insert);
                                echo "Added user ".$user['login']." to group";
                            }
                        }
                } else if (isset($_GET['removeAll']) && $_GET['table'] == "usersTable") {
                    //isset($_GET['filter']) ? $users = eF_filterData($users, $_GET['filter']) : null;
                    eF_deleteTableData("users_to_groups", "groups_ID=".$_GET['edit_user_group']);
                    echo "All users where deleted from group";

                } else if (isset($_GET['lessons_ID']) && eF_checkParameter($_GET['lessons_ID'], 'id')) {
                    if ($_GET['insert'] == "1") {
                        $currentGroup -> addLesson($_GET['lessons_ID']);
                    } else {
                        $currentGroup -> removeLessons($_GET['lessons_ID']);
                    }

                } else if (isset($_GET['addAll']) && $_GET['table'] == "lessonsTable") {
                    isset($_GET['filter']) ? $lessons = eF_filterData($lessons, $_GET['filter']) : null;
                    foreach ($lessons as $lesson) {
                        if (!$lesson['in_group']) {
                            $currentGroup -> addLesson($lesson['id'], 'student');
                            echo "Added lesson ".$lesson['id']." to group";
                        }
                    }
                } else if (isset($_GET['removeAll']) && $_GET['table'] == "lessonsTable") {
                    //isset($_GET['filter']) ? $lessons = eF_filterData($lessons, $_GET['filter']) : null;
                    eF_deleteTableData("lessons_to_groups", "groups_ID=".$_GET['edit_user_group']);
                    echo "All lessons where deleted from group";
                } else if (isset($_GET['courses_ID']) && eF_checkParameter($_GET['courses_ID'], 'id')) {
                    if ($_GET['insert'] == 1) {
                        $currentGroup -> addCourse($_GET['courses_ID']);
                    } else {
                        $currentGroup -> removeCourses($_GET['courses_ID']);
                    }

                } else if (isset($_GET['addAll']) && $_GET['table'] == "coursesTable") {
                    isset($_GET['filter']) ? $courses = eF_filterData($courses, $_GET['filter']) : null;
                    foreach ($courses as $course) {
                        if (!$course['in_group']) {
                            $currentGroup -> addCourse($course['id'], 'student');
                            echo "Added course ".$course['id']." to group";
                        }
                    }
                } else if (isset($_GET['removeAll']) && $_GET['table'] == "coursesTable") {
                    //isset($_GET['filter']) ? $lessons = eF_filterData($lessons, $_GET['filter']) : null;
                    eF_deleteTableData("courses_to_groups", "groups_ID=".$_GET['edit_user_group']);
                    echo "All lessons where deleted from group";
                } else if (isset($_GET['assign_to_all_users'])) {
                    try {
                        $groupUsers = $currentGroup -> getUsers();
                        $groupUsers = array_merge($groupUsers['professor'], $groupUsers['student']);
                        $groupLessons = $currentGroup -> getLessons();
                        $groupCourses = $currentGroup -> getCourses();

                        $lessonIds = array_keys($groupLessons);
                        $courseIds = array_keys($groupCourses);
                        foreach ($groupUsers as $user) {
                            $user = EfrontUserFactory :: factory($user);
                            if ($user -> getType() != 'administrator') {
                                if ($_GET['assign_to_all_users'] == "lessons") {
                                    $user -> addLessons($lessonIds, $user -> getType(), 1);    //active lessons
                                } else {
                                    $user -> addCourses($courseIds, $user -> getType(), 1);    //active courses
                                }
                            }
                        }
                    } catch (Exception $e) {
                        $message      = $e -> getMessage();
                        header("HTTP/1.0 500 ");
                        echo urlencode($e -> getMessage()).' ('.$e -> getCode().')';
                    }

                }

                exit;
            }

            // Ajax get group lessonsTable content
            if (isset($_GET['ajax']) && $_GET['ajax'] == "lessonsTable") {
                isset($_GET['limit']) && eF_checkParameter($_GET['limit'], 'uint') ? $limit = $_GET['limit'] : $limit = G_DEFAULT_TABLE_SIZE;

                if (isset($_GET['sort']) && eF_checkParameter($_GET['sort'], 'text')) {
                    $sort = $_GET['sort'];
                    isset($_GET['order']) && $_GET['order'] == 'desc' ? $order = 'desc' : $order = 'asc';
                } else {
                    $sort = 'name';
                }

                if (isset($_GET['filter'])) {
                    $lessons = eF_filterData($lessons, $_GET['filter']);
                }
                $lessons = eF_multiSort($lessons, $sort, $order);
                $smarty -> assign("T_LESSONS_SIZE", sizeof($lessons));

                if (isset($_GET['limit']) && eF_checkParameter($_GET['limit'], 'int')) {
                    isset($_GET['offset']) && eF_checkParameter($_GET['offset'], 'int') ? $offset = $_GET['offset'] : $offset = 0;
                    $lessons = array_slice($lessons, $offset, $limit);
                }

                $smarty -> assign("T_GROUP_LESSONS", $lessons);
                $smarty -> display('administrator.tpl');
                exit;
            }



            // Ajax get group coursesTable content
            if (isset($_GET['ajax']) && $_GET['ajax'] == "coursesTable") {
                isset($_GET['limit']) && eF_checkParameter($_GET['limit'], 'uint') ? $limit = $_GET['limit'] : $limit = G_DEFAULT_TABLE_SIZE;

                if (isset($_GET['sort']) && eF_checkParameter($_GET['sort'], 'text')) {
                    $sort = $_GET['sort'];
                    isset($_GET['order']) && $_GET['order'] == 'desc' ? $order = 'desc' : $order = 'asc';
                } else {
                    $sort = 'name';
                }

                if (isset($_GET['filter'])) {
                    $courses = eF_filterData($courses, $_GET['filter']);
                }
                $courses = eF_multiSort($courses, $sort, $order);
                $smarty -> assign("T_COURSES_SIZE", sizeof($courses));

                if (isset($_GET['limit']) && eF_checkParameter($_GET['limit'], 'int')) {
                    isset($_GET['offset']) && eF_checkParameter($_GET['offset'], 'int') ? $offset = $_GET['offset'] : $offset = 0;
                    $courses = array_slice($courses, $offset, $limit);
                }

                $smarty -> assign("T_GROUP_COURSES", $courses);
                $smarty -> display('administrator.tpl');
                exit;
            }




        }

    } else {
        $result = eF_getTableData("groups g LEFT OUTER JOIN users_to_groups ug ON g.id=ug.groups_ID", "g.*, count(ug.groups_ID) as num_users", "", "", "id");
        $smarty -> assign("T_USERGROUPS", $result);
    }
    
?>