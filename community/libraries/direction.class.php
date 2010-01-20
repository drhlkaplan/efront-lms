<?php
/**
 * File for directions
 *
 * @package eFront
*/

//This file cannot be called directly, only included.
if (str_replace(DIRECTORY_SEPARATOR, "/", __FILE__) == $_SERVER['SCRIPT_FILENAME']) {
    exit;
}

/**
 * Direction exceptions
 *
 * This class extends Exception to provide the exceptions related to directions
 * @package eFront
 * @since 3.5.0
 * 
 */
class EfrontDirectionException extends Exception
{
    /**
     * The direction requested does not exist
     * @since 3.5.0
     */    
    const DIRECTION_NOT_EXISTS = 1051;
    /**
     * The id provided is not valid, for example it is not a number or it is 0
     * @since 3.5.0
     */
    const INVALID_ID        = 1052;
    /**
     * The category is not empty
     * @since 3.5.4
     */
    const NOT_EMPTY_CATEGORY        = 1052;
    /**
     * An unspecific error
     * @since 3.5.0
     */
    const GENERAL_ERROR     = 1099;
}

/**
 * This class represents a direction in eFront
 * 
 * @package eFront
 * @since 3.5.0
 */
class EfrontDirection extends ArrayObject
{
    /**
     * The maximum length for direction names. After that, the names appear truncated
     */
    const MAXIMUM_NAME_LENGTH = 50;

    /**
     * Instantiate direction
     *
     * This function is the class constructor, which instantiates the
     * EfrontDirection object, based on the direction values
     * <br/>Example:
     * <code>
     * $direction_array = eF_getTableData("directions", "*", "id=4");
     * $direction = new EfrontDirection($direction_array[0]);
     * </code>
     *
     * @param array $array The direction values
     * @since 3.5.0
     * @access public
     */
    function __construct($direction) {
        if (!is_array($direction)) {
            if (!eF_checkParameter($direction, 'id')) {
                throw new EfrontLessonException(_INVALIDID.': '.$direction, EfrontDirectionException :: INVALID_ID);
            }
            $result = eF_getTableData("directions", "*", "id=".$direction);  
            if (sizeof($result) == 0) {
                throw new EfrontLessonException(_CATEGORYDOESNOTEXIST.': '.$direction, EfrontDirectionException :: DIRECTION_NOT_EXISTS);
            }
            $direction = $result[0];
        }
        parent :: __construct($direction);
    }

    /**
     * Persist changed values
     *
     * This function is used to persist the direction values
     * <br/>Example:
     * <code>
     * $direction_array = eF_getTableData("directions", "*", "id=4");
     * $direction = new EfrontDirection($direction_array[0]);
     * $direction['name'] = 'new name';
     * $direction -> persist();
     * </code>
     *
     * @since 3.5.0
     * @access public
     */
    function persist() {
        foreach (new EfrontAttributesOnlyFilterIterator($this -> getIterator()) as $key => $value) {
            $fields[$key] = $value;
        }
        eF_updateTableData("directions", $fields, "id=".$fields['id']);    
    }

    /**
     * Delete a direction
     * This function is used to delete the current direction
     * <br/>Example:
     * <code>
     * $direction_array = eF_getTableData("directions", "*", "id=4");
     * $direction = new EfrontDirection($direction_array[0]);
     * $direction -> delete();
     * </code>
     *
     * @since 3.5.0
     * @access public
     */
    function delete() {
        foreach (new EfrontAttributeFilterIterator(new RecursiveIteratorIterator(new RecursiveArrayIterator($this)), 'id') as $key => $value) {
            eF_deleteTableData("directions", "id=".$value);                                               //Delete Units from database
        }
    }

    /**
     * Get direction's lessons
     *
     * This function is used to get the lessons that belong
     * to this direction.
     * <br/>Example:
     * <code>
     * $lessons = $direction -> getLessons();
     * </code>
     *
     * @param boolean $returnObjects Whether to return EfrontLesson objects or a simple array
     * @param boolean $subDirections Whether to return subDirections lessons as well
     * @return array An array of lesson ids/names pairs or EfrontLesson objects
     * @since 3.5.0
     * @access public
     */
    function getLessons($returnObjects = false, $subDirections = false) {
        if (!$subDirections) {
            $result = eF_getTableData("lessons", "id, name", "directions_ID=".$this['id']);
        } else {
            $directions = new EfrontDirectionsTree();
            $children       = $directions -> getNodeChildren($this['id']);
            foreach (new EfrontAttributeFilterIterator(new RecursiveIteratorIterator(new RecursiveArrayIterator($children)), array('id')) as $value) {
                $siblings[] = $value;
            }
            $result = eF_getTableData("lessons", "id, name", "directions_ID in (".implode(",", $siblings).")");
        }
        $lessons = array();

        foreach ($result as $value) {
            $returnObjects ? $lessons[$value['id']] = new EfrontLesson($value['id']) : $lessons[$value['id']] = $value['name'];
        }

        return $lessons;
    }

    /**
     * Get direction's courses
     *
     * This function is used to get the courses that belong
     * to this direction.
     * <br/>Example:
     * <code>
     * $courses = $direction -> getCourses();
     * </code>
     *
     * @param boolean $returnObjects Whether to return EfrontCourse objects or a simple array
     * @param boolean $subDirections Whether to return subDirections lessons as well
     * @return array An array of course ids/names pairs or EfrontCourse objects
     * @since 3.5.0
     * @access public
     */
    function getCourses($returnObjects = false, $subDirections = false) {
        if (!$subDirections) {
            $result = eF_getTableData("courses", "id, name", "directions_ID=".$this['id']);
        } else {
            $directionsTree = new EfrontDirectionsTree();
            $children       = $directionsTree -> getNodeChildren($this['id']);
            foreach (new EfrontAttributeFilterIterator(new RecursiveIteratorIterator(new RecursiveArrayIterator($children)), array('id')) as $value) {
                $siblings[] = $value;
            }
            $result = eF_getTableData("courses", "id, name", "directions_ID in (".implode(",", $siblings).")");
        }
        $courses = array();

        foreach ($result as $value) {
            $returnObjects ? $courses[$value['id']] = new EfrontCourse($value['id']) : $courses[$value['id']] = $value['name'];
        }

        return $courses;
    }

    /**
     * Create direction
     *
     * This function is used to create a new direction
     * <br/>Example:
     * <code>
     * $fields = array('name' => 'new direction');
     * EfrontDirection :: createDirection($fields);
     * </code>
     *
     * @param array $fields The new direction's fields
     * @return EfrontDirection The new direction
     * @since 3.5.0
     * @access public
     * @static
     */
    public static function createDirection($fields = array()) {
        !isset($fields['name']) ? $fields['name'] = 'Default direction' : null;

        $newId     = eF_insertTableData("directions", $fields);
        $result    = eF_getTableData("directions", "*", "id=".$newId);                                            //We perform an extra step/query for retrieving data, sinve this way we make sure that the array fields will be in correct order (forst id, then name, etc)
        $direction = new EfrontDirection($result[0]);

        return $direction;
    }
    
    /**
     * Delete category (statically)
     *
     * This function is used to delete an existing category. 
     * This function is the same as EfrontDirection :: delete(), 
     * except that it is called statically
     * <br/>Example:
     * <code>
     * try {
     *   EfrontDirection :: delete(32);                     //32 is the category id
     * } catch (Exception $e) {
     *   echo $e -> getMessage();
     * }
     * </code>
     *
     * @param mixed $category The category id or a category object
     * @return boolean True if everything is ok
     * @since 3.5.0
     * @access public
     * @static
     */
    public static function deleteDirection($category) {
        if (!($category instanceof EfrontDirection)) {
            $category = new EfrontDirection($category);
        }
        return $category -> delete();
    }
    
}


/**
 * This class represents the directions tree and extends EfrontTree class 
 * @package eFront
 * @since 3.5.0 
 */
class EfrontDirectionsTree extends EfrontTree
{
    /**
     * Initialize tree
     *
     * This function is used to initialize the directions tree
     * <br/>Example:
     * <code>
     * $directionsTree = new EfrontDirectionsTree();
     * </code>
     *
     * @since 3.5.0
     * @access public
     */
    function __construct() {
        $this -> reset();
    }

    /**
     * Reset/initialize directions tree
     *
     * This function is used to initialize or reset the directions tree
     * <br/>Example:
     * <code>
     * $directionsTree = new EfrontDirectionsTree();
     * $directionsTree -> reset();
     * </code>
     *
     * @since 3.5.0
     * @access public
     */
    public function reset() {
        $directions = eF_getTableData("directions", "*", "", "name");

        if (sizeof($directions) == 0) {
            $this -> tree = new RecursiveArrayIterator(array());
            return;
        }

        foreach ($directions as $node) {                //Assign previous direction ids as keys to the previousNodes array, which will be used for sorting afterwards
            $nodes[$node['id']] = new EfrontDirection($node);        //We convert arrays to array objects, which is best for manipulating data through iterators
        }

        $rejected = array();
        $tree     = $nodes;
        $count    = 0;                                                                          //$count is used to prevent infinite loops
        while (sizeof($tree) > 1 && $count++ < 1000) {                                       //We will merge all branches under the main tree branch, the 0 node, so its size will become 1
            foreach ($nodes as $key => $value) {
                if ($value['parent_direction_ID'] == 0 || in_array($value['parent_direction_ID'], array_keys($nodes))) {        //If the unit parent is in the $nodes array keys - which are the unit ids- or it is 0, then it is  valid
                    $parentNodes[$value['parent_direction_ID']][]      = $value;               //Find which nodes have children and assign them to $parentNodes
                    $tree[$value['parent_direction_ID']][$value['id']] = array();              //We create the "slots" where the node's children will be inserted. This way, the ordering will not be lost
                } else {
                    $rejected = $rejected + array($value['id'] => $value);                   //Append units with invalid parents to $rejected list
                    unset($nodes[$key]);                                                     //Remove the invalid unit from the units array, as well as from the parentUnits, in case a n entry for it was created earlier
                    unset($parentNodes[$value['parent_direction_ID']]);
                }
            }
            if (isset($parentNodes)) {                                                       //If the unit was rejected, there won't be a $parentNodes array
                $leafNodes = array_diff(array_keys($nodes), array_keys($parentNodes));       //Now, it's easy to see which nodes are leaf nodes, just by subtracting $parentNodes from the whole set
                foreach ($leafNodes as $leaf) {
                    $parent_id = $nodes[$leaf]['parent_direction_ID'];                         //Get the leaf's parent
                    $tree[$parent_id][$leaf] = $tree[$leaf];                                 //Append the leaf to its parent's tree branch
                    unset($tree[$leaf]);                                                     //Remove the leaf from the main tree branch
                    unset($nodes[$leaf]);                                                    //Remove the leaf from the nodes set
                }
                unset($parentNodes);                                                         //Reset $parentNodes; new ones will be calculated at the next loop
            }
        }
        if (sizeof($tree) > 0 && !isset($tree[0])) {                                         //This is a special case, where only one node exists in the tree
            $tree = array($tree);
        }
        foreach ($tree as $key => $value) {
            if ($key != 0) {
                $rejected[$key] = $value;
            }
        }
        
        if (sizeof($rejected) > 0) {                                            //Append rejected nodes to the end of the tree array, updating their parent/previous information
            foreach ($rejected as $key => $value) {
                eF_updateTableData("directions", array("parent_direction_ID" => 0), "id=".$key);
                $value['parent_direction_ID'] = 0;
                $tree[0][] = $value;
            }
        }

        $this -> tree = new RecursiveArrayIterator($tree[0]);
    }

    /**
     * Experimental function for merging lessons and courses to the main tree
     *
     */
    public function reset2() {
        $directions = eF_getTableData("directions", "*", "", "name");
        
        $result  = eF_getTableData("lessons", "*");
        $lessons = array();
        foreach ($result as $value) {
            $lessons[$value['directions_ID']][] = new EfrontLesson($value);
        }
        $result  = eF_getTableData("courses", "*");
        $courses = array();
        foreach ($result as $value) {
            $courses[$value['directions_ID']][] = new EfrontCourse($value);
        }
        
        if (sizeof($directions) == 0) {
            $this -> tree = new RecursiveArrayIterator(array());
            return;
        }

        foreach ($directions as $node) {                //Assign previous direction ids as keys to the previousNodes array, which will be used for sorting afterwards
            $nodes[$node['id']] = new EfrontDirection($node);        //We convert arrays to array objects, which is best for manipulating data through iterators
            $nodes[$node['id']]['lessons'] = $lessons[$node['id']];
            $nodes[$node['id']]['courses'] = $lessons[$node['id']];
        }

        $rejected = array();
        $tree     = $nodes;
        $count    = 0;                                                                          //$count is used to prevent infinite loops
        while (sizeof($tree) > 1 && $count++ < 1000) {                                       //We will merge all branches under the main tree branch, the 0 node, so its size will become 1
            foreach ($nodes as $key => $value) {
                if ($value['parent_direction_ID'] == 0 || in_array($value['parent_direction_ID'], array_keys($nodes))) {        //If the unit parent is in the $nodes array keys - which are the unit ids- or it is 0, then it is  valid
                    $parentNodes[$value['parent_direction_ID']][]      = $value;               //Find which nodes have children and assign them to $parentNodes
                    $tree[$value['parent_direction_ID']][$value['id']] = array();              //We create the "slots" where the node's children will be inserted. This way, the ordering will not be lost
                } else {
                    $rejected = $rejected + array($value['id'] => $value);                   //Append units with invalid parents to $rejected list
                    unset($nodes[$key]);                                                     //Remove the invalid unit from the units array, as well as from the parentUnits, in case a n entry for it was created earlier
                    unset($parentNodes[$value['parent_direction_ID']]);
                }
            }
            if (isset($parentNodes)) {                                                       //If the unit was rejected, there won't be a $parentNodes array
                $leafNodes = array_diff(array_keys($nodes), array_keys($parentNodes));       //Now, it's easy to see which nodes are leaf nodes, just by subtracting $parentNodes from the whole set
                foreach ($leafNodes as $leaf) {
                    $parent_id = $nodes[$leaf]['parent_direction_ID'];                         //Get the leaf's parent
                    $tree[$parent_id][$leaf] = $tree[$leaf];                                 //Append the leaf to its parent's tree branch
                    unset($tree[$leaf]);                                                     //Remove the leaf from the main tree branch
                    unset($nodes[$leaf]);                                                    //Remove the leaf from the nodes set
                }
                unset($parentNodes);                                                         //Reset $parentNodes; new ones will be calculated at the next loop
            }
        }
        if (sizeof($tree) > 0 && !isset($tree[0])) {                                         //This is a special case, where only one node exists in the tree
            $tree = array($tree);
        }
        foreach ($tree as $key => $value) {
            if ($key != 0) {
                $rejected[$key] = $value;
            }
        }
        
        if (sizeof($rejected) > 0) {                                            //Append rejected nodes to the end of the tree array, updating their parent/previous information
            foreach ($rejected as $key => $value) {
                eF_updateTableData("directions", array("parent_direction_ID" => 0), "id=".$key);
                $value['parent_direction_ID'] = 0;
                $tree[0][] = $value;
            }
        }

        $this -> tree = new RecursiveArrayIterator($tree[0]);
    }    
    
    /**
     * Insert node to the tree
     *
     * @param mixed $node
     * @param mixed $parentNode
     * @param mixed $previousNode
     * @since 3.5.0
     * @access public
     */
    public function insertNode($node, $parentNode = false, $previousNode = false) {}

    /**
     * Remove node from tree
     *
     * @param mixed $node
     * @since 3.5.0
     * @access public
     */
    public function removeNode($node) {



    }
    
    /**
     * Print an HTML representation of the directions tree
     *
     * This function is used to print an HTML representation of the HTML tree
     * <br/>Example:
     * <code>
     * $directionsTree -> toHTML();                         //Print directions tree
     * </code>
     * Possible options are:
     * - lessons_link			//a value of '#user_type#' inside the url will be replaced with the user type
     * - courses_link			//a value of '#user_type#' inside the url will be replaced with the user typed
     * - tooltip		 
     * - search					//display the search box (true/false)
     * - tree_tools				//Whether to display the top div with tree tools, show/hide and search (true/false)
     * - url					//A url to search ajax functions for. defaults to current url
     * - collapse				//Whether to start with categories collapsed
     * - buy_link				//Whether to display "buy" (add to cart) links
     *  
     * @param RecursiveIteratorIterator $iterator An optional custom iterator
     * @param array $lessons An array of EfrontLesson Objects
     * @param array $courses An array of EfrontCourse Objects
     * @param array $userInfo Optional information for the user accessing the tree
     * @param array $options display options for the tree
     * @return string The HTML version of the tree
     * @since 3.5.0
     * @access public
     */
    public function toHTML($iterator = false, $lessons = false, $courses = false, $userInfo = array(), $options = array()) {

        //!isset($options['show_cart'])   ? $options['show_cart']   = false : null;
        //!isset($options['information']) ? $options['information'] = false : null;
        !isset($options['lessons_link']) ? $options['lessons_link'] = false : null;
        !isset($options['courses_link']) ? $options['courses_link'] = false : null;
        !isset($options['tooltip'])      ? $options['tooltip']      = true  : null;
        !isset($options['search'])       ? $options['search']       = false : null;
        !isset($options['catalog'])      ? $options['catalog']      = false : null;
        !isset($options['tree_tools'])   ? $options['tree_tools']   = true  : null;
        !isset($options['url'])          ? $options['url']          = $_SERVER['REQUEST_URI'] : null;    //Pay attention since REQUEST_URI is empty if accessing index.php with the url http://localhost/
        
        if (!$iterator) {
            $iterator = new EfrontNodeFilterIterator(new RecursiveIteratorIterator(new RecursiveArrayIterator($this -> tree), RecursiveIteratorIterator :: SELF_FIRST));
        }
//		isset($options['catalog']) ? $catalogString    = " AND show_catalog = 1"  : $catalogString = "";
        if ($lessons === false) {                                                    //If a lessons list is not specified, get all active lessons
            $result = eF_getTableData("lessons", "*", "active=1", "name");                   //Get all lessons at once, thus avoiding looping queries
            foreach ($result as $value) {
                $lessons[$value['id']] = new EfrontLesson($value);                   //Create an array of EfrontLesson objects
            }
        }

        $directionsLessons = array();
        foreach ($lessons as $id => $lesson) {            
            if (!$lesson -> lesson['active'] ||  ($options['catalog'] && !$lesson -> lesson['show_catalog'])) {                                      //Remove inactive lessons
                unset($lessons[$id]);
            } elseif (!$lesson -> lesson['course_only']) {                           //Lessons in courses will be handled by the course's display method, so remove them from the list
                $directionsLessons[$lesson -> lesson['directions_ID']][] = $id;      //Create an intermediate array that maps lessons to directions
            }
        }
        
        if ($courses === false) {                                                   //If a courses list is not specified, get all active courses
            $result = eF_getTableData("courses", "*", "active=1", "name");                  //Get all courses at once, thus avoiding looping queries
            foreach ($result as $value) {
                $courses[$value['id']] = new EfrontCourse($value);                  //Create an array of EfrontCourse objects
            }
        }
        $directionsCourses = array();
        foreach ($courses as $id => $course) {
            if (!$course -> course['active'] || ($options['catalog'] && !$course -> course['show_catalog'])) {                                     //Remove inactive courses
                unset($courses[$id]);
            } else {
                $directionsCourses[$course -> course['directions_ID']][] = $id;     //Create an intermediate array that maps courses to directions
            }
        }
        $roles     = EfrontLessonUser :: getLessonsRoles();
        $roleNames = EfrontLessonUser :: getLessonsRoles(true);

        //We need to calculate which directions will be displayed. We will keep only directions that have lessons or courses and their parents. In order to do so, we traverse the directions tree and set the 'hasNodes' attribute to the nodes that will be kept
        foreach ($iterator as $key => $value) {
            if (isset($directionsLessons[$value['id']]) || isset($directionsCourses[$value['id']])) {
                $count = $iterator -> getDepth();
                $value['hasNodes'] = true;
                isset($directionsLessons[$value['id']]) ? $value['lessons'] = $directionsLessons[$value['id']] : null;        //Assign lessons ids to the direction
                isset($directionsCourses[$value['id']]) ? $value['courses'] = $directionsCourses[$value['id']] : null;        //Assign courses ids to the direction
                while ($count) {
                    $node = $iterator -> getSubIterator($count--);
                    $node['hasNodes'] = true;                        //Mark "keep" all the parents of the node
                }
            }
        }

        $iterator = new EfrontNodeFilterIterator($iterator, array('hasNodes' => true));    //Filter in only tree nodes that have the 'hasNodes' attribute

        $iterator   -> rewind();
        $current    = $iterator -> current();
        $treeString = '';            			
        if ($options['tree_tools']) {
            $treeString = '            			
        				<div style = "padding-top:8px;padding-bottom:8px">
        					'.($options['search'] ? '<span style = "float:right;"><span style = "vertical-align:middle">'._SEARCH.': <input type = "text" style = "vertical-align:middle" onKeyPress = "if (event.keyCode == 13) {filterTree(this)}"></span></span>' : '').'
        					<a href = "javascript:void(0)" onclick = "showAll()" >'._SHOWALL.'</a> / <a href = "javascript:void(0)" onclick = "hideAll()">'._HIDEALL.'</a>
        				</div>';
        }
        $treeString .= '            			
        				<div id = "directions_tree">';
/*
        if ($options['credits_link']) {        				
            $treeString .= '            			
            			<table class = "directionsTable" id = "direction_credit">
                            <tr class = "lessonsList" >
                            	<td class = "listPadding" style = "width:1px;"></td>
                            	<td class = "listIcon">
                                    <img name = "default_visible_image" src = "images/32x32/generic.png" class = "visible">
                                </td>
                                <td class = "listName"><div>Credits</div></td>
                                <td>
                                        <img class = "buyLesson" src = "images/16x16/shopping_basket_add.png" alt = "'._BUY.'" title = "'._BUY.'" onclick = "addToCart(this, 1, \'credit\')">
                                        <span class = "buyLesson" onclick = "addToCart(this, 1, \'credit\'))">euro 1 / '._CREDIT.'</span>                    
                                </td>
                            </tr>
        				</table>';
        }
*/
        if (isset($options['collapse']) && $options['collapse'] == 2) { 
			$display 			= '';
			$display_lessons 	= 'style = "display:none"';
		} elseif (isset($options['collapse']) && $options['collapse'] == 1) {
			$display 			= 'style = "display:none"';
			$display_lessons 	= 'style = "display:none"';
		} else {
			$display 			= '';
			$display_lessons 	= '';
		}
        
        while ($iterator -> valid()) {
            $children = array();                    //The $children array is used so that when collapsing a direction, all its children disappear as well
            foreach (new EfrontNodeFilterIterator(new ArrayIterator($this -> getNodeChildren($current), RecursiveIteratorIterator :: SELF_FIRST)) as $key => $value) {
                $children[] = $key;
            }

            $treeString .= '
                        <table class = "directionsTable" id = "direction_'.$current['id'].'" '.($iterator -> getDepth() >= 1 ? $display : '').'>
                            <tr class = "lessonsList">
                            	<td class = "listPadding" style = "width:'.(20 * $iterator -> getDepth() + 1).'px;"></td>
                            	<td class = "listToggle">
                            		<img id = "subtree_img'.$current['id'].'" class = "visible" src = "images/16x16/navigate_down.png" alt = "'._CLICKTOTOGGLE.'" title = "'._CLICKTOTOGGLE.'" onclick = "showHideDirections(this, \''.implode(",", $children).'\', \''.$current['id'].'\', (this.hasClassName(\'visible\')) ? \'hide\' : \'show\');">
                            	</td>
                            	<td class = "listIcon">
                                    <img src = "images/32x32/categories.png" >
                                    <span style = "display:none" id = "subtree_children_'.$current['id'].'">'.implode(",", $children).'</span>
                                </td>
                                <td><span class = "listName">'.$current['name'].'</span></td>
                            </tr>';

            if (sizeof($current['lessons']) > 0) {
                $treeString .= '
                            <tr id = "subtree'.$current['id'].'" name = "default_visible" '.$display_lessons.'>
                            	<td></td>
                                <td class = "lessonsList_nocolor">&nbsp;</td>
                                <td colspan = "2">
                                    <table width = "100%">';
                
                foreach ($current -> offsetGet('lessons') as $lessonId) {
                    if (!empty($userInfo)) {                        
                        $roleBasicType = $roles[$userInfo['lessons'][$lessonId]['user_type']];        //The basic type of the user's role in the lesson
                    } else {
                        $roleBasicType = null;
                    }              
                    $toolsString   = '
                                    	<tr class = "directionEntry">';
                    if ($userInfo['lessons'][$lessonId]) {                       
                        if ($roles[$userInfo['lessons'][$lessonId]['user_type']] == 'student' && ($userInfo['lessons'][$lessonId]['completed'] || $userInfo['lessons'][$lessonId]['lesson_passed'])) {                    //Show the "completed" mark
                            $userInfo['lessons'][$lessonId]['completed'] ? $icon = 'success' : $icon = 'semi_success';
                            $treeString .= '
							                <td style = "width:50px;padding-bottom:2px;">
                                                <span class = "progressNumber" style = "width:50px;">&nbsp;</span>
                                                <span class = "progressBar" style = "width:50px;text-align:center"><img src = "images/16x16/'.$icon.'.png" alt = "'._LESSONCOMPLETE.'" title = "'._LESSONCOMPLETE.'" style = "vertical-align:middle" /></span>
                                                &nbsp;&nbsp;
                                            </td>'; 
							                         
                        } elseif ($roles[$userInfo['lessons'][$lessonId]['user_type']] == 'student') {                                                                //Show the progress bar
                            $treeString .= '
                                            <td style = "width:50px;padding-bottom:2px;">
                                                <span class = "progressNumber" style = "width:50px;">'.$userInfo['lessons'][$lessonId]['overall_progress'].'%</span>
                                                <span class = "progressBar" style = "width:'.($userInfo['lessons'][$lessonId]['overall_progress'] / 2).'px;">&nbsp;</span>
                                                &nbsp;&nbsp;
                                            </td>';                            
                        } else {
                            $treeString .= '<td style = "width:1px;padding-bottom:2px;"></td>';
                        }
                    }
                    
                    $treeString .= '
                                        <td>';
                    if (isset($options['buy_link']) && $options['buy_link'] && !$lessons[$lessonId] -> lesson['has_lesson'] && !$lessons[$lessonId] -> lesson['reached_max_users'] && $_SESSION['s_type'] != 'administrator') {					    
                        $lessons[$lessonId] -> lesson['price'] ? $priceString = formatPrice($lessons[$lessonId] -> lesson['price'], array($lessons[$lessonId] -> options['recurring'], $lessons[$lessonId] -> options['recurring_duration']), true) : $priceString = '';                    
                    	$treeString .= '
                    						<span class = "buyLesson">                    
	                                        	<span>'.$priceString.'</span>
	                                        	<img class = "ajaxHandle" src = "images/16x16/shopping_basket_add.png" alt = "'._ADDTOCART.'" title = "'._ADDTOCART.'" onclick = "addToCart(this, '.$lessonId.', \'lesson\')">
	                                        </span>';
					}
					$treeString .= '&nbsp;';
                    if (!isset($userInfo['lessons'][$lessonId]['from_timestamp']) || $userInfo['lessons'][$lessonId]['from_timestamp']) {    //from_timestamp in user status means that the user's status in the lesson is not 'pending'
                        $classNames = array();
                        $lessonLink = $options['lessons_link'];
                        if ($userInfo['lessons'][$lessonId]['user_type'] && $roles[$userInfo['lessons'][$lessonId]['user_type']] == 'student' && (($lessons[$lessonId] -> lesson['from_timestamp'] && $lessons[$lessonId] -> lesson['from_timestamp'] > time()) || ($lessons[$lessonId] -> lesson['to_timestamp'] && $lessons[$lessonId] -> lesson['to_timestamp'] < time()))) { //here, from_timestamp and to_timestamp refer to the lesson periods
                            $lessonLink = false;
                            $classNames[] = 'inactiveLink';
                        } 
                        
                        if ($options['tooltip']) {
                            $treeString .= '<a href = "'.($lessonLink ? str_replace("#user_type#", $roleBasicType, $lessonLink).$lessons[$lessonId] -> lesson['id'] : 'javascript:void(0)').'" class = "info '.implode(" ", $classNames).'" onmouseover = "updateInformation(this, '.$lessonId.', \'lesson\')">'.$lessons[$lessonId] -> lesson['name'].'
                            						<img class = "tooltip" border = "0" src = "images/others/tooltip_arrow.gif"/>
                            						<span class = "tooltipSpan"></span>
                            					</a>';
                        } else {
                            $lessonLink ? $treeString .= '<a href = "'.str_replace("#user_type#", $roleBasicType, $lessonLink).$lessons[$lessonId] -> lesson['id'].'">'.$lessons[$lessonId] -> lesson['name'].'</a>' : $treeString .= $lessons[$lessonId] -> lesson['name'];
                        }
                    } else {
                        $treeString .= '<a href = "javascript:void(0)" class = "inactiveLink" title = "'._CONFIRMATIONPEDINGFROMADMIN.'">'.$lessons[$lessonId] -> lesson['name'].'</a>';
                    }
                    
                    $treeString .= ($userInfo['lessons'][$lessonId]['different_role'] ? '&nbsp;<span class = "courseRole">('.$roleNames[$userInfo['lessons'][$lessonId]['user_type']].')</span>' : '').'
                    			   '.(!is_null($userInfo['lessons'][$lessonId]['remaining']) && $roles[$userInfo['lessons'][$lessonId]['user_type']] == 'student' ? '<span class = "">('.eF_convertIntervalToTime($userInfo['lessons'][$lessonId]['remaining'], true).' '.mb_strtolower(_REMAINING).')</span>' : '').'
                                        '.$toolsString.'
                                        </td>
                                	</tr>';									
                }
                    $treeString .= '
                                    </table>
                                </td></tr>';
            }
            
            if (isset($current['courses']) && sizeof($current['courses']) > 0) {
                $treeString .= '
                            <tr id = "subtree'.$current['id'].'" name = "default_visible" '.$display.'> 
                            	<td></td>
                                <td class = "lessonsList_nocolor">&nbsp;</td>
                                <td colspan = "2">';
                foreach ($current -> offsetGet('courses') as $courseId) {
                    $treeString .= $courses[$courseId] -> toHTML($userInfo, $options);
                }
                $treeString .= '
                                </td>
                            </tr>';
            }
            $treeString .= '
                        </table>';

            $iterator -> next();
            $current = $iterator -> current();
        }
        
        $treeString .= "
        			</div>
                        <script>
                        	function showAll() {
                        		$$('tr').each(function (tr) 	  {tr.id.match(/subtree/) ? tr.show() : null;});
                           		$$('table').each(function (table) {table.id.match(/direction_/) ? table.show() : null;});
                           		$$('img').each(function (img) {!img.hasClassName('visible') ? img.addClassName('visible') : null;});
                        	}
                        	function hideAll() {
                        		$$('tr').each(function (tr) 	  {tr.id.match(/subtree/) ? tr.hide() : null;});
                           		//$$('table').each(function (table) {table.id.match(/direction_/) ? table.hide() : null;});
                           		$$('img').each(function (img) {img.hasClassName('visible') ? img.removeClassName('visible') : null;});
                        	}
                        	
                            function showHideDirections(el, ids, id, mode) {       
                            	Element.extend(el);		//IE intialization
                                if (mode == 'show') {
                            		el.up().up().nextSiblings().each(function(s) {s.show()});
                                    if (ids) {
                                        ids.split(',').each(function (s) { showHideDirections($('subtree_img'+id), $('subtree_children_'+s) ? $('subtree_children_'+s).innerHTML : '', s, 'show') });
                                        ids.split(',').each(function (s) { obj = $('direction_'+s); obj ? obj.show() : '';});
    								}
    								setImageSrc(el, 16, 'navigate_down');
    								$('subtree_img'+id) ? $('subtree_img'+id).addClassName('visible') : '';
    							} else {
                            		el.up().up().nextSiblings().each(function(s) {s.hide()});
                                    if (ids) {
                                        ids.split(',').each(function (s) { showHideDirections($('subtree_img'+id), $('subtree_children_'+s) ? $('subtree_children_'+s).innerHTML : '', s, 'hide') });
                                        ids.split(',').each(function (s) { obj = $('direction_'+s); obj ? obj.hide() : ''});
    								}
    								setImageSrc(el, 16, 'navigate_up.png');
    								$('subtree_img'+id) ? $('subtree_img'+id).removeClassName('visible') : '';
    							}
    						}
    						function showHideCourses(el, course) {
    							if (el.hasClassName('visible')) {
    								course.hide();
    								setImageSrc(el, 16, 'navigate_up.png');
    								el.removeClassName('visible');
    							} else {
    								course.show();
    								setImageSrc(el, 16, 'navigate_down');
    								el.addClassName('visible');
    							}
    						}
    						function updateInformation(el, id, type) {
    							Element.extend(el);
    							type == 'lesson' ? url = 'ask_information.php?lessons_ID='+id : url = 'ask_information.php?courses_ID='+id;
    							el.select('span').each(function (s) {    								
    								if (s.hasClassName('tooltipSpan') && s.empty()) {
    									s.setStyle({height:'50px'}).insert(new Element('span').addClassName('progress').setStyle({margin:'auto',background:'url(\"images/others/progress1.gif\")'}));
    									new Ajax.Request(url, {
                                            method:'get',
                                            asynchronous:true,
                                            onSuccess: function (transport) {
                                            	s.setStyle({height:'auto'}).update(transport.responseText);
    										}
    									});
    								}
   								});
    						}
    						function filterTree(el) {
    							Element.extend(el);
    							//$$('tr.directionEntry').each(function (s) {if(s.innerHTML.stripTags().toLowerCase().match(el.value.toLowerCase())) {s.show()} else {s.hide()}});
    							var url = '".$options['url']."';
    							url.match(/\?/) ? url = url+'&' : url = url + '?';
    							el.addClassName('loadingImg').setStyle({background:'url(\"images/others/progress1.gif\") center right no-repeat'});
    							new Ajax.Request(url+'filter='+el.value, {
                                    method:'get',
                                    asynchronous:true,
                                    onSuccess: function (transport) {
                                    	$('directions_tree').innerHTML = transport.responseText;
                                    	el.removeClassName('loadingImg').setStyle({background:''});
									}
								});
    						}
                            </script>";

        return $treeString;
    }
    
    /* Return an array to be inputed as the contents of a select item or
     * an HTML select object with directions->courses->lessons
     *  
     * This function is used to create a select with directions, lessons and courses
     * categorized properly under a select item
     * 
     * The values of the returned array of HTML select are different but always start
     * with the type of educational entity, i.e. "direction_", "course_" and "lesson_" 
     * and finish with the id of that entity "_<id>". The inbetween parts differ
     * 
     * The categorization display is the following
     * direction D
     * - subdirection SuBD
     * -- course C1 in SubD 
     * ---- lesson in C1
     * ---- lesson in C1
     * - course C2 in D 
     * -- lesson in C2
     * -- lesson in C2
     * - lesson in D
     * 
     * <br/>Example:
     * <code>
     * $directionsTree -> toSelect();                         //Print directions tree
     * </code>
     * 
     * @param boolean $returnClassedHTML return the HTML select object rather than the array - different colors denote different educational entities
     * @param boolean $includeSkillGaps the skill gap test questions will be included
     * @param boolean $showQuestions defines whether to show the number of questions of each lesson
     * @param RecursiveIteratorIterator $iterator An optional custom iterator
     * @param array $lessons An array of EfrontLesson Objects
     * @param array $courses An array of EfrontCourse Objects
     * @return array to be used or string for The HTML version of the tree 
     * @since 3.5.2
     * @access public
     */
    public function toSelect($returnClassedHTML = false, $includeSkillGaps = false, $showQuestions = false, $iterator = false,  $lessons = false, $courses = false) {
        
        if (!$iterator) {
            $iterator = new EfrontNodeFilterIterator(new RecursiveIteratorIterator(new RecursiveArrayIterator($this -> tree), RecursiveIteratorIterator :: SELF_FIRST));
        }

        if ($lessons === false) {                                                    //If a lessons list is not specified, get all active lessons
            $result = eF_getTableData("lessons", "*", "active=1");                   //Get all lessons at once, thus avoiding looping queries
            foreach ($result as $value) {
                $lessons[$value['id']] = new EfrontLesson($value);                   //Create an array of EfrontLesson objects
            }
        }

        $directionsLessons = array();
        foreach ($lessons as $id => $lesson) {
            if (!$lesson -> lesson['active']) {                                      //Remove inactive lessons
                unset($lessons[$id]);
            } elseif (!$lesson -> lesson['course_only']) {                           //Lessons in courses will be handled by the course's display method, so remove them from the list
                $directionsLessons[$lesson -> lesson['directions_ID']][] = $id;      //Create an intermediate array that maps lessons to directions
            }
        }
        
        if ($courses === false) {                                                   //If a courses list is not specified, get all active courses
            $result = eF_getTableData("courses", "*", "active=1");                  //Get all courses at once, thus avoiding looping queries
            foreach ($result as $value) {
                $courses[$value['id']] = new EfrontCourse($value);                  //Create an array of EfrontCourse objects
            }
        }
        $directionsCourses = array();
        foreach ($courses as $id => $course) {
            if (!$course -> course['active']) {                                     //Remove inactive courses
                unset($courses[$id]);
            } else {
                $directionsCourses[$course -> course['directions_ID']][] = $id;     //Create an intermediate array that maps courses to directions
            }
        }

        //We need to calculate which directions will be displayed. We will keep only directions that have lessons or courses and their parents. In order to do so, we traverse the directions tree and set the 'hasNodes' attribute to the nodes that will be kept
        foreach ($iterator as $key => $value) {
            if (isset($directionsLessons[$value['id']]) || isset($directionsCourses[$value['id']])) {
                $count = $iterator -> getDepth();
                $value['hasNodes'] = true;
                isset($directionsLessons[$value['id']]) ? $value['lessons'] = $directionsLessons[$value['id']] : null;        //Assign lessons ids to the direction
                isset($directionsCourses[$value['id']]) ? $value['courses'] = $directionsCourses[$value['id']] : null;        //Assign courses ids to the direction
                while ($count) {
                    $node = $iterator -> getSubIterator($count--);
                    $node['hasNodes'] = true;                        //Mark "keep" all the parents of the node
                }
            }
        }

        $iterator = new EfrontNodeFilterIterator($iterator, array('hasNodes' => true));    //Filter in only tree nodes that have the 'hasNodes' attribute

        $iterator   -> rewind();
        $current    = $iterator -> current();
       // pr($current);
        $current_level_father = 0;
        $treeArray = array();
        if ($includeSkillGaps) {
            $treeArray['lesson_0'] = _SKILLGAPTESTS;   
        }
        $offset = "";
        while ($iterator -> valid()) {
            $children = array();                    //The $children array is used so that when collapsing a direction, all its children disappear as well
            foreach (new EfrontNodeFilterIterator(new ArrayIterator($this -> getNodeChildren($current), RecursiveIteratorIterator :: SELF_FIRST)) as $key => $value) {
                $children[] = $key;
            }
            
            if ($offset != "") {
                $treeArray['direction_' . $current['id']] = $offset . " " . $current['name'];
            } else {
                $treeArray['direction_' . $current['id']] = $current['name'];
            }
            if (sizeof($current['lessons']) > 0) {
                
                foreach ($current -> offsetGet('lessons') as $lessonId) {    
                    $treeArray['lesson_' . $current['id']. '_' . $lessonId] = $offset . "- ". $lessons[$lessonId] -> lesson['name'];
                }
            }        
                    
            if (sizeof($current['courses']) > 0) {
                foreach ($current -> offsetGet('courses') as $courseId) {
                    $coursesArray = $courses[$courseId] -> toSelect();
                    $first = 1;
                    foreach ($coursesArray as $courseId => $courseName) {
                        // The first result is the name of the course - the rest lesson names
                        // We need this distinction to have different keys (starting with course_ or lesson_ correctly 
                        if ($first) {
                            $treeArray['course_' . $current['id']. '_' . $courseId . "_" . $courseId] = $offset . "-". $courseName;
                            $first = 0;
                        } else {
                            $treeArray['lesson_' . $current['id']. '_' . $courseId . "_" . $courseId] = $offset . "-". $courseName;
                        }
                    } 
                }
            }

            $iterator -> next();
            $current = $iterator -> current();
           // $current
            if ($current['parent_direction_ID'] != $current_level_father) {
                $offset .= "-";
                $current_level_father = $current['parent_direction_ID'];
            }
            
        }
        
  
          
        if ($returnClassedHTML) {
            $htmlString = "<select id= 'educational_criteria_row' name ='educational_criteria_row' onchange='createQuestionsSelect(this)' mySelectedIndex = '0'>";
            
            
            if ($showQuestions) {
                $result = eF_getTableData("questions", "lessons_ID, count(lessons_ID) as quests", "type <> 'raw_text'", "", "lessons_ID");
                $lessonQuestions = array();
                foreach ($result as $lesson) {
                    if ($lesson['quests'] > 0) {
                        $lessonQuestions[$lesson['lessons_ID']] = $lesson['quests']; 
                    }
                }

                
            }
            
            foreach($treeArray as $key => $value) {
                
                $extras = " ";
                $htmlString .= "<option";
                if (strpos($key, "direction_") === 0) {
                    $htmlString .= " value = 'direction". strrchr($key,"_") . "' style='background-color:maroon; color:white'";
                    if ($showQuestions) {
                        
                        $startcounting = 0;
                        $questions_sum = 0;

                        foreach($treeArray as $keyInner => $valueInner) {
                            if ($keyInner == $key) {
                                $startcounting = 1;        
                            }
                            if ($startcounting) {
                                if ($keyInner != $key && strpos($keyInner, "direction_") === 0) {
                                    break;    // you reached the next direction
                                } else if (strpos($keyInner, "lesson_") === 0) {    //only lessons have questions
                                    $lessonId = substr(strrchr($keyInner,"_"),1);
                                    if ($lessonQuestions[$lessonId]) {
                                        $questions_sum += $lessonQuestions[$lessonId];
                                    }
                                }
                                
                            }
                        }
                        $extras = " (" . $questions_sum .")";
                        
                    }
                } else if (strpos($key, "course_") === 0) {
                    $htmlString .= " value = 'course". strrchr($key,"_") . "' style='background-color:green; color:white'";
                    if ($showQuestions) {
                        
                        $startcounting = 0;
                        $questions_sum = 0;

                        foreach($treeArray as $keyInner => $valueInner) {
                            if ($keyInner == $key) {
                                $startcounting = 1;        
                            }
                            if ($startcounting) {
                                if ($keyInner != $key && (strpos($keyInner, "course_") === 0 || strpos($keyInner, "direction_") === 0)) {
                                    break;    // you reached the next direction
                                } else if (strpos($keyInner, "lesson_") === 0) {    //only lessons have questions
                                    $lessonId = substr(strrchr($keyInner,"_"),1);
                                    if ($lessonQuestions[$lessonId]) {
                                        $questions_sum += $lessonQuestions[$lessonId];
                                    }
                                }
                                
                            }
                        }
                        $extras = " (" . $questions_sum .")";
                        
                    }                    
                } else {
                    $htmlString .= " value = 'lesson". strrchr($key,"_") . "' ";
                    if ($showQuestions) {
                        $lessonId = substr(strrchr($key,"_"),1);
                        
                        if ($showQuestions) {
                            if ($lessonQuestions[$lessonId]) {
                                $extras .= "(" . $lessonQuestions[$lessonId]. ")";
                            } else {
                                $extras .= "(0)";         
                            }
                        }
                    }
                    
                }
                $htmlString .= ">" . $value . $extras . "</option>";
            }
            $htmlString .= "</select>";
            
            // If no lessons or anything is found, then an empty select or array should be returned
            return $htmlString;
    
        }
       
        return $treeArray;
    }
    
    /**
     * Print paths string
     *
     * This function is used to print the paths to the each direction
     * based on its ancestors.
     * <br/>Example:
     * <code>
     * $paths = $directionsTree -> toPathString();  //$paths is an array with direction ids as keys, and paths as values, for example 'Direction 1 -> Directions 1.1 -> Direction 1.1.1'
     * </code>
     *
     * @param boolean $$includeLeaf Whether leaf direction will be included to the path string
     * @return array The direction paths
     * @since 3.5.0
     * @access public
     */
    public function toPathString($includeLeaf = true, $onlyActive = false) {
        if ($onlyActive) {
            $iterator = new EfrontNodeFilterIterator(new RecursiveIteratorIterator(new RecursiveArrayIterator($this -> tree), RecursiveIteratorIterator :: SELF_FIRST), array('active' => 1));
        } else {
            $iterator = new EfrontNodeFilterIterator(new RecursiveIteratorIterator(new RecursiveArrayIterator($this -> tree), RecursiveIteratorIterator :: SELF_FIRST));
        }
        foreach ($iterator as $id => $value) {
            $values = array();
            foreach ($this -> getNodeAncestors($id) as $direction) {
                $values[] = $direction['name'];
            }
            if (!$includeLeaf) {
                unset($values[0]);
            }
            $parentsString[$id] = implode('&nbsp;&rarr;&nbsp;', array_reverse($values));
        }

        return $parentsString;
    }

}


?>