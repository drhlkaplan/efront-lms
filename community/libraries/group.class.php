<?php
/**
 * File for groups
 *
 * @package eFront
*/

//This file cannot be called directly, only included.
if (str_replace(DIRECTORY_SEPARATOR, "/", __FILE__) == $_SERVER['SCRIPT_FILENAME']) {
    exit;
}

/**
 * EfrontGroupException class
 *
 * This class extends Exception class and is used to issue errors regarding groups
 * @package eFront
 * @author Antonellis Panagiotis <antonellis@efront.gr>
 * @version 1.0
 */
class EfrontGroupException extends Exception
{
    const NO_ERROR          = 0;
    const GROUP_NOT_EXISTS  = 301;
    const INVALID_ID        = 302;
    const INVALID_USER      = 303;
}

/**
 * EfrontGroup class
 *
 * This class represents a group
 * @package eFront
 * @author Antonellis Panagiotis <antonelli@efront.gr>
 * @version 1.0
 */
class EfrontGroup
{
    /**
     * The group array.
     *
     * @since 1.0
     * @var array
     * @access protected
     */
    public $group = array();

    /**
     * The group users. Calling getUsers() initializes it; otherwise, it evaluates to false.
     *
     * @since 1.0
     * @var array
     * @access protected
     */
    protected $users = false;

    /*
     * The group lessons. Lessons correlated with this group can be either directly assigned to
     * all users of the group or be automatically assigned to every user joining the group
     */
    protected $lessons = false;
    
    /*
     * The group courses. Lessons correlated with this group can be either directly assigned to
     * all users of the group or be automatically assigned to every user joining the group
     */
    protected $courses = false;
    
    
    /**
     * Class constructor
     *
     * This function is used to instantiate the class. The instatiation is done
     * based on a group id. If an entry with this id is not found in the database,
     * an EfrontGroupException is thrown.
     * <br/>Example:
     * <code>
     * $group = new EfrontGroup(32);                     //32 is a group id
     * </code>
     *
     * @param int $group_id The group id or array with group's info array
     * @since 1.0
     * @access public
     */
    function __construct($group_id) {
        
        if (is_array($group_id)) {
            $group[0] = $group_id;
        } else {
	        if (!eF_checkParameter($group_id, 'id')) {
	            throw new EfrontGroupException(_INVALIDID, EfrontGroupException :: INVALID_ID);
	        }
            $group = eF_getTableData("groups", "*", "id = $group_id");
        }
        
        
        if (sizeof($group) == 0) {
            throw new EfrontGroupException(_GROUPDOESNOTEXIST, EfrontGroupException :: GROUP_NOT_EXISTS);
        } else {
            $this -> group    = $group[0];
        }
    }

    /**
     * Create group
     *
     * This function creates a new group. This involves creating
     * the database instance..
     * The function argument is an array with field values, corresponding to database
     * fields. All fields are optional, and if absent they are filled with default values,
     * but 'name' is strongly recommended to be defined
     * <br/>Example:
     * <code>
     * $fields = array('name' => 'Test group', 'description' => 'Description of the group');
     * try {
     *   $newGroup = EfrontGroup :: create($fields);                     //$newgroup is now a new group object
     * } catch (Exception $e) {
     *   echo $e -> getMessage();
     * }
     * </code><br/>
     * 
     * @param array $fields The new group characteristics
     * @return EfrontGroup the new group object
     * @since 1.0
     * @access public
     * @static
     */
    public static function create($fields) {
        //These are the mandatory fields. In case one of these is absent, fill it in with a default value
        !isset($fields['name'])           ? $fields['name']           = 'Default name'                                : null;
 
        $group_id = eF_insertTableData("groups", $fields);                                    //Insert the group to the database
        $newGroup = new EfrontGroup($group_id);
        return $newGroup;
    }

    /**
     * Delete group
     *
     * This function is used to delete an existing group. In order to do
     * this, it caclulates all the group dependendant elements, deletes them
     * and finally deletes the group itself.
     *
     * <br/>Example:
     * <code>
     * try {
     *   $group = new EfrontGroup(32);                     //32 is the group id
     *   $group -> delete();
     * } catch (Exception $e) {
     *   echo $e -> getMessage();
     * }
     * </code>
     *
     * @return boolean True if everything is ok
     * @since 1.0
     * @access public
     */
    public function delete() {
        ef_deleteTableData("users_to_groups", "groups_ID=".$this -> group['id']);
        eF_deleteTableData("groups", "id=".$this -> group['id']);
        return true;
    }

    /**
     * Get group users
     *
     * This function returns an array with the group users. The array
     * has 2 sub-arrays, 'student' and 'professor', each of which holding
     * a list of user logins. If the optional $type is specified, then only
     * a one-dimensional array is returned, holding the group users of the
     * specified type.
     * <br/>Example:
     * <code>
     * try {
     *   $group     = new EfrontGroup(32);                     //32 is the group id
     *   $all_users  = $group -> getUsers();
     *   $professors = $group -> getUsers('professor');
     * } catch (Exception $e) {
     *   echo $e -> getMessage();
     * }
     * </code><br/>
     * One thing to note is that this function caches results throughout the life
     * cycle of the object. That is, if some other function updates the group users,
     * the function results will not be altered, unless $refresh is set to true
     *
     * @param string $type The group users type
     * @param boolean $refresh Whether to explicitly refresh the object cached data set
     * @return array A 2-dimensional array with group users per type, or a 1-dimensional array with group users of the specified type
     * @since 1.0
     * @access public
     */
    public function getUsers($type = false, $refresh = false) {
        if ($this -> users === false || $refresh) {                //Make a database query only if the variable is not initialized, or it is explicitly asked
            $this -> users = array('professor' => array(), 'student' => array());
            $result = eF_getTableData("users_to_groups ug, users u", "ug.*, u.user_type", "u.login=ug.users_LOGIN and groups_ID=".$this -> group['id']);
            foreach ($result as $value) {
                $this -> users[$value['user_type']][] = $value['users_LOGIN'];
            }
         }

        if ($type) {
            return $this -> users[$type];
        } else {
            return $this -> users;
        }
    }
    
    /**
     * Add users to group
     * 
     * This function is used to add users to the current group
     * <br>Example:
     * <code>
     * $group = new EfrontGroup(2);
     * $group -> addUsers(array('jdoe', 'doej'));
     * </code>
     *
     * @param mixed $users An array of user logins or EfrontUser objects, or a single login or EfrontUser object
     * @return boolean True if everything is ok
     * @since 3.5.2
     * @access public
     */
    public function addUsers($users) {
        if (!is_array($users)) {
            if ($users instanceof EfrontUser) {
                $users = $users -> user['login'];
            }
            $users = array($users);
        }        
        
        $allUsers = eF_getTableDataFlat("users", "login");
        
        // Optimization - get the lesson info extraciton out of the loop if you are to assign 
        // them to new group users
        if ($this -> group['assign_profile_to_new']) {
            $groupLessons = $this -> getLessons();    
               
            $lessonIds = array_keys($groupLessons);        
            /*
            $lessonTypes = array();
            foreach ($groupLessons as $lesson) {
                $lessonTypes[] = $lesson['user_type'];   
            } 
			*/

            $groupCourses = $this -> getCourses();       
            $courseIds = array_keys($groupCourses);
            /*        
            $courseTypes = array();
            foreach ($groupCourses as $course) {
                $courseTypes[] = $course['user_type'];   
            } 
			*/               
        }
        
        foreach ($users as $user) {
            if ($user instanceof EfrontUser) {
                $user = $user -> user['login'];
            }
            if (in_array($user, $allUsers['login'])) {
                $fields = array('groups_ID'   => $this -> group['id'],
                                'users_LOGIN' => $user);                
                $ok = eF_insertTableData("users_to_groups", $fields);

                if ($ok && $this -> group['assign_profile_to_new']) {
                    
                    $userObject = EfrontUserFactory::factory($user);                    
                    $fields = array();
                    
                    if ($userObject -> getType() != 'administrator') {
	                	// Update the user profile
	                	if ($this -> group['user_types_ID']) {
			                if ($this -> group['user_types_ID'] != 'student' && $this -> group['user_types_ID'] != 'professor') {
					            $basic_type = eF_getTableData("user_types", "basic_user_type", "id = '" . $this -> group['user_types_ID']. "'");
					            if (sizeof($basic_type)) {
					                $fields["user_type"] = $basic_type[0]['basic_user_type'];
					                $fields["user_types_ID"] = $this -> group['user_types_ID']; 
					            } else {
					                throw new EfrontGroupException(_INVALIDID, EfrontGroupException :: INVALID_ID);
					            }    
			                } else {
			                    // basic user type
				                $fields["user_type"] = $this -> group['user_types_ID'];
				                $fields["user_types_ID"] = 0; 
				            }
	                	}
                    }
                      
			        if ($this -> group['languages_NAME']) {	            
			            $fields["languages_NAME"] = $this -> group['languages_NAME'];
			        }
	        
		        	if ($this -> group['users_active']) {	            
			            $fields["active"] = ($this -> group['users_active'] == 1)? 1:0;
			        }	        

			        if (sizeof($fields)) {
			            eF_updateTableData("users", $fields, "login = '". $user ."'"); 
			        } 

			        // Add lessons - info acquired before entering the new user assignment loop
                    if ($userObject -> getType() != 'administrator') {
                        $userObject -> addLessons($lessonIds, $userObject -> getType(), 1);    //active lessons
                        $userObject -> addCourses($courseIds, $userObject -> getType(), 1);    // active courses
                    }
                    
                }
            }
        }
    }
    
    
    /**
     * Remove users from group
     * 
     * This function is used to remove users from the current group
     * <br>Example:
     * <code>
     * $group = new EfrontGroup(2);
     * $group -> removeUsers(array('jdoe', 'doej'));
     * </code>
     *
     * @param mixed $users An array of user logins or EfrontUser objects, or a single login or EfrontUser object
     * @return boolean True if everything is ok
     * @since 3.5.2
     * @access public
     */
    public function removeUsers($users) {
        if (!is_array($users)) {
            if ($users instanceof EfrontUser) {
                $users = $users -> user['login'];
            }
            $users = array($users);
        }        
        foreach ($users as $user) {
            if ($user instanceof EfrontUser) {
                $user = $user -> user['login'];
            }
            if (eF_checkParameter($user, 'login')) {
                eF_deleteTableData("users_to_groups", "users_LOGIN='".$user."' and groups_ID=".$this -> group['id']);
            }            
        }
        return true;
    }
    
    /**
     * Update group users 
     * 
     * This function is used to update ALL current group users
     * in terms of active/languages_NAME/user_type
     * <br>Example:
     * <code>
     * $group = new EfrontGroup(2);
     * $group -> group['active'] = 2; 	// deactivate users 
     * $group -> updateUsers();
     * $group -> persist();
     * </code>
     *
     * @param optionally a single user login might be set to update only this user's records
     * @return boolean True if everything is ok
     * @since 3.5.2
     * @access public
     */
    public function updateUsers($login = false) {

        if ($login) {
            $users = array($login);
        } else {
            $users = $this -> getUsers();
            $users = array_merge($users['professor'], $users['student']);
            
        }
        //pr($users);
        if (sizeof($users)) {

	        if ($this -> group['user_types_ID']) {

	            // If we have a custom user type
	            if ($this -> group['user_types_ID'] != 'student' && $this -> group['user_types_ID'] != 'professor') {
		        
		            $basic_type = eF_getTableData("user_types", "basic_user_type", "id = '" . $this -> group['user_types_ID'] . "'");
		            if (sizeof($basic_type)) {
		                $fields["user_type"] = $basic_type[0]['basic_user_type'];
		                $fields["user_types_ID"] = $this -> group['user_types_ID']; 
		            } 
	            } else {
	                // basic user type
	                $fields["user_type"] = $this -> group['user_types_ID'];
	                $fields["user_types_ID"] = 0; 
	            }
	        }
	        
	        if ($this -> group['languages_NAME']) {	            
	            $fields["languages_NAME"] = $this -> group['languages_NAME'];
	        }
	        
        	if ($this -> group['users_active']) {	            
	            $fields["active"] = ($this -> group['users_active'] == 1)? 1:0;
	        }	        
	            
	        // If at least one value greater than zero
	        if (sizeof($fields)) {
	            
	            return eF_updateTableData("users", $fields, "login IN ('". implode("','", $users) ."')"); 
	        }
        }
        return true;
    }
    
    
    /**
     * Get group lessons
     *
     * This function returns an array with the group lessons. Each record in 
     * the array holds the lesson id, lesson name and the type ('student' or 'professor')
     * assosiated with that lesson.
     * 
     * <br/>Example:
     * <code>
     * try {
     *   $group     = new EfrontGroup(32);                     //32 is the group id
     *   $group_lessons = $group -> getLessons();
     * } catch (Exception $e) {
     *   echo $e -> getMessage();
     * }
     * </code><br/>
     * One thing to note is that this function caches results throughout the life
     * cycle of the object. That is, if some other function updates the group users,
     * the function results will not be altered, unless $refresh is set to true
     *
     * @return array of lessons in the form [lessons_ID] => [lessons_ID, lessons_name, user_type, active]
     * @since 3.5.2
     * @access public
     */
    public function getLessons() {
        if ($this -> lessons === false) {                //Make a database query only if the variable is not initialized, or it is explicitly asked
            
            $result = eF_getTableData("lessons_to_groups lg, lessons l", "lg.*, l.name, l.active", "lg.lessons_ID = l.id and lg.groups_ID=".$this -> group['id']);
            $this -> lessons = array();
            foreach ($result as $value) {
                $this -> lessons[$value['lessons_ID']] = array("lessons_ID" => $value['lessons_ID'], "lessons_name" => $value['name'], "user_type" => $value['user_type'], "active" => $value['active']);
            }
         }

        return $this -> lessons;
    }
    
    
     /**
     * Add lessons to group
     * 
     * This function is used to add lessons to the current group
     * <br>Example:
     * <code>
     * $group = new EfrontGroup(2);
     * $group -> addLesson(3, 'professor'); // will add lesson with id 3 and role 'professor' to the group's lessons
     * </code>
     *
     * @param $lessons_ID and $user_type
     * @return boolean True if everything is ok
     * @since 3.5.2
     * @access public
     */
    public function addLesson($lessons_ID, $user_type = "student") {
        // Check if the lesson exists in the group's list    
        $lessons = $this -> getLessons();

        if (in_array($lessons_ID, array_keys($lessons))) {
            // If the lesson is already assigned check if you need
            // to update the user type for this lesson
            if ($lessons[$lessons_ID]['user_type'] != $user_type) {
                $ok = eF_updateTableData("lessons_to_groups", array("user_type" => $user_type), "lessons_ID = " . $lessons_ID);
                $this -> lessons[$lessons_ID]['user_type'] = $user_type;
                return $ok;
            }
        } else {
            $fields = array('lessons_ID' => $lessons_ID,
            				'user_type'  => $user_type,
            				'groups_ID'  => $this -> group['id']);
            
            if ($ok = eF_insertTableData("lessons_to_groups", $fields)) {           
                $newLesson = new EfrontLesson($lessons_ID);
                $this -> lessons[$lessons_ID] = array('lessons_ID' => $lessons_ID,'lessons_name'=> $newLesson -> lesson['name'], 'user_type'  => $user_type);
            }
            return $ok;
        } 
    
        // if control flow reaches here then the lesson was already assigned and with the same user_type
        return false;
    }
       
    /**
     * Remove lessons from group
     * 
     * This function is used to remove lessons from the current group
     * <br>Example:
     * <code>
     * $group = new EfrontGroup(2);
     * $group -> removeLessons(array(21, 32));	// remove lessons with ids 21 and 32
     * </code>
     *
     * @param mixed $users An array of lesson ids or EfrontLesson objects, or a single id or EfrontLesson object
     * @return boolean True if everything is ok
     * @since 3.5.2
     * @access public
     */
    public function removeLessons($lessons) {
        if (!is_array($lessons)) {
            if ($lessons instanceof EfrontLesson) {
                $lessons = $lessons -> lesson['id'];
            }
            $lessons = array($lessons);
        }        
        foreach ($lessons as $lesson) {
            if ($lesson instanceof EfrontLesson) {
                $lesson = $lesson -> lesson['id'];
            }
            if (eF_checkParameter($lesson, 'id')) {
                eF_deleteTableData("lessons_to_groups", "lessons_ID ='".$lesson."' and groups_ID=".$this -> group['id']);
            }            
        }
        return true;
    }


    /**
     * Get group courses
     *
     * This function returns an array with the group courses. Each record in 
     * the array holds the course id, course name and the type ('student' or 'professor')
     * assosiated with that course.
     * 
     * <br/>Example:
     * <code>
     * try {
     *   $group     = new EfrontGroup(32);                     //32 is the group id
     *   $group_courses = $group -> getCourses();
     * } catch (Exception $e) {
     *   echo $e -> getMessage();
     * }
     * </code><br/>
     * One thing to note is that this function caches results throughout the life
     * cycle of the object. That is, if some other function updates the group users,
     * the function results will not be altered, unless $refresh is set to true
     *
     * @param boolean $returnObjects whether to return EfrontCourse objects
     * @param boolean $refresh whether to refresh data
     * @return array of courses in the form [courses_ID] => [courses_ID, courses_name, user_type]
     * @since 3.5.2
     * @access public
     */
    public function getCourses($returnObjects = false, $refresh = false) {
        if ($this -> courses === false || $refresh) {                //Make a database query only if the variable is not initialized, or it is explicitly asked
            
            $result = eF_getTableData("courses_to_groups lg, courses l", "lg.*, l.*", "lg.courses_ID = l.id and lg.groups_ID=".$this -> group['id']);
            $this -> courses = array();
            foreach ($result as $value) {
            	if ($returnObjects) {
                	$this -> courses[$value['courses_ID']] = new EfrontCourse($value);
            	} else {
                	$this -> courses[$value['courses_ID']] = array("courses_ID" => $value['courses_ID'], "courses_name" => $value['courses_name'], "user_type" => $value['user_type']);
            	}
            }
         }

        return $this -> courses;
    }
    
    
     /**
     * Add courses to group
     * 
     * This function is used to add courses to the current group
     * <br>Example:
     * <code>
     * $group = new EfrontGroup(2);
     * $group -> addCourse(3, 'professor'); // will add course with id 3 and role 'professor' to the group's courses
     * </code>
     *
     * @param $courses_ID and $user_type
     * @return boolean True if everything is ok
     * @since 3.5.2
     * @access public
     */
    public function addCourse($courses_ID, $user_type = "student") {
        // Check if the course exists in the group's list
        $courses = $this -> getCourses();
        if (in_array($courses_ID, array_keys($courses))) {
            
            // If the course is already assigned check if you need
            // to update the user type for this course
            if ($courses[$courses_ID]['user_type'] != $user_type) {
                
                $ok = eF_updateTableData("courses_to_groups", array("user_type" => $user_type), "courses_ID = " . $courses_ID);
                $this -> courses[$courses_ID]['user_type'] = $user_type;
                return $ok;
            }
        } else {
            $fields = array('courses_ID' => $courses_ID,
            				'user_type'  => $user_type,
            				'groups_ID'  => $this -> group['id']);
            
            if ($ok = eF_insertTableData("courses_to_groups", $fields)) {           
                $newCourse = new EfrontCourse($courses_ID);
                $this -> courses[$courses_ID] = array('courses_ID' => $courses_ID,'courses_name'=> $newCourse -> course['name'], 'user_type'  => $user_type);
            }
            return $ok;
        } 
    
        // if control flow reaches here then the course was already assigned and with the same user_type
        return false;
    }
       
    /**
     * Remove courses from group
     * 
     * This function is used to remove courses from the current group
     * <br>Example:
     * <code>
     * $group = new EfrontGroup(2);
     * $group -> removeCourses(array(21, 32));	// remove courses with ids 21 and 32
     * </code>
     *
     * @param mixed $users An array of course ids or EfrontCourse objects, or a single id or EfrontCourse object
     * @return boolean True if everything is ok
     * @since 3.5.2
     * @access public
     */
    public function removeCourses($courses) {
        if (!is_array($courses)) {
            if ($courses instanceof EfrontCourse) {
                $courses = $courses -> course['id'];
            }
            $courses = array($courses);
        }        
        foreach ($courses as $course) {
            if ($course instanceof EfrontCourse) {
                $course = $course -> course['id'];
            }
            if (eF_checkParameter($course, 'id')) {
                eF_deleteTableData("courses_to_groups", "courses_ID ='".$course."' and groups_ID=".$this -> group['id']);
            }            
        }
        return true;
    }
    
    
    public function setData($fields, $persist = false) {
        if (sizeof($fields['name']) > 0)
            $this -> group['name'] = $fields['name'];
        if (sizeof($fields['description']) > 0)
            $this -> group['description'] = $fields['description'];
        if (sizeof($fields['active']) > 0)
            $this -> group['active'] = $fields['active'];
        if ($persist) {
            $this -> persist();
        }
    }
    
    /**
     * Persist group values
     * 
     * This function is used to persist any changes made to the current 
     * group.
     * <br/>Example:
     * <code>
     * $group = new EfrontGroup(3);										//Instantiate group with id 3
     * $group -> group['name'] = 'new name';							//Change a group's value
     * $group -> persist();												//Store changes values to the database
     * </code>
     * 
     * @return boolean True if everything is ok
     * @since 3.5.0
     * @access public
     */
    public function persist() {
        // Remove the current default group
        if ($this -> group['is_default']) {
            eF_updateTableData("groups", array("is_default" => 0), "");
        }
        
        $ok = eF_updateTableData("groups", $this -> group, "id=".$this -> group['id']);
        return $ok;
    }

   /**
     * Adds a user to the default group
     * 
     * This function adds a user to the default group, assigning to him the lessons
     * of that group.
     * 
     * <br/>Example:
     * <code>
     * $userObject = EfrontUserFactory::factory('joe'); 	// create user object joe
     * EfrontGroup :: addToDefaultGroup($userObject);							
     * </code>
     * 
     * @param $user InstanceOf EfrontUser 
     * @return true if everything ok
     * @since 3.5.2
     * @access public
     */        
   public static function addToDefaultGroup($user) {
       if (!($user instanceof EfrontUser)) {
       		$login = $user['login'];
       } else {
       		$login = $user -> user['login'];
       }
   	
       // Get the default eFront group
       $default_group = eF_getTableData("groups", "*", "is_default = 1 AND active = 1");
      // pr($default_group);
       if (sizeof($default_group)) {
           $default_group = $default_group[0];
           $group = new EfrontGroup($default_group);
           
           $group -> addUsers($login);
           $group -> updateUsers($login);
           
           // Get updated version of user
		   $updatedUser = EfrontUserFactory::factory($login);
       
           // Assign default group type
           $groupLessons = $group -> getLessons();
           $lessonIds = array_keys($groupLessons);
           $user -> addLessons($lessonIds, $updatedUser -> user['user_types_ID'],1);
           
           $groupCourses = $group -> getCourses();
           $courseIds = array_keys($groupCourses);                          
           $user -> addCourses($courseIds, $updatedUser -> user['user_types_ID'],1);     

       } // otherwise no default group has been defined
       return true;
   }

   /**
     * Returns the existing groups
     * 
     * This function returns the existing groups
     * group.
     * <br/>Example:
     * <code>
     * $groups -> EfrontGroup :: getGroups();							
     * </code>
     * 
     * @param boolean Flat to indicate whether to return group objects or not 
     * @param boolean Flag to indicate whether to return disabled groups
     * @return array An array of groups. Each element is the group array
     * @since 3.5.0
     * @access public
     */    
    public static function getGroups($returnObjects = false, $returnDisabled = false){
        $groups = array();
        if ($returnDisabled){
            $data = ef_getTableData("groups", "id, name");
        }
        else{
            $data = ef_getTableData("groups", "id, name", "active = 1");
        }
        if ($returnObjects){
            foreach ($data as $group_info){
                $group = new EfrontGroup($group_info['id']);
                $groups[$group_info['id']] = $group;
            }
        }
        else{
            foreach ($data as $group_info){
                $groups[$group_info['id']] = $group_info;
            }
        }
        return $groups;
    }
    
   /**
     * Returns the lessons that are associated with the group's users (NOT necessarily with the group)
     * 
     * <br/>Example:
     * <code>
     * $group = new EfrontGroup(2);
     * $group->getLessonGroupUsers();							
     * </code>
     * 
     * @param boolean Flat to indicate whether to return lesson objects or not 
     * @param boolean Flag to indicate whether to return disabled lessons
     * @return array An array of lessons. Each element is the lesson array
     * @since 3.6.0
     * @access public
     */    
    public function getLessonGroupUsers($returnObjects = false, $returnDisabled = false){
        $lessons = array();
        if ($returnDisabled){
            $data = eF_getTableData("lessons JOIN users_to_lessons ON lessons.id = users_to_lessons.lessons_ID JOIN users_to_groups ON users_to_groups.users_LOGIN = users_to_lessons.users_LOGIN", "lessons.*, count(users_to_lessons.users_LOGIN) as group_users_count", "groups_ID = ". $this -> group['id'], "", "lessons_ID");
        } else{
            $data = eF_getTableData("lessons JOIN users_to_lessons ON lessons.id = users_to_lessons.lessons_ID JOIN users_to_groups ON users_to_groups.users_LOGIN = users_to_lessons.users_LOGIN", "lessons.*, count(users_to_lessons.users_LOGIN) as group_users_count", "lessons.active = 1 AND groups_ID = ". $this -> group['id'], "", "lessons_ID");
        }
        if ($returnObjects){
            foreach ($data as $lesson_info){
                $lesson = new EfrontLesson($lesson_info['id']);
                $lessons[$lesson_info['id']] = $lesson;
            }
        }
        else{
            foreach ($data as $lesson_info){
                $lessons[] = $lesson_info;
            }
        }
        return $lessons;
    }    
    
}




?>