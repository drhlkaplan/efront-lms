{*moduleGroups: The user groups list*}
    {capture name = "moduleGroups"}
    <tr><td class = "moduleCell">
                        {if $smarty.get.add_user_group || $smarty.get.edit_user_group}

           {capture name = "t_group_form"}
                                                            {$T_USERGROUPS_FORM_R.javascript}
                                                            <form {$T_USERGROUPS_FORM_R.attributes}>
                                                            {$T_USERGROUPS_FORM_R.hidden}
                                                            <table class = "formElements">
                                                                <tr><td class = "labelCell">{$T_USERGROUPS_FORM_R.name.label}:&nbsp;</td>
                                                                    <td>{$T_USERGROUPS_FORM_R.name.html}</td></tr>
                                                                {if $T_USERGROUPS_FORM_R.name.error}<tr><td></td><td class = "formError">{$T_USERGROUPS_FORM_R.name.error}</td></tr>{/if}
                                                                <tr><td class = "labelCell">{$T_USERGROUPS_FORM_R.description.label}:&nbsp;</td>
                                                                    <td>{$T_USERGROUPS_FORM_R.description.html}</td></tr>
                                                                {if $T_USERGROUPS_FORM_R.description.error}<tr><td></td><td class = "formError">{$T_USERGROUPS_FORM_R.description.error}</td></tr>{/if}
                                                                    <tr><td class = "labelCell">{$T_USERGROUPS_FORM_R.group_key.label}:&nbsp;</td>
                                                                        <td>
                                                                            <table><tr><td>{$T_USERGROUPS_FORM_R.group_key.html}</td>
                                                                                       <td><img src = "images/16x16/wizard.png" class = "ajaxHandle" alt = "{$smarty.const._AUTOMATICALLYGENERATEGROUPKEY}" title = "{$smarty.const._AUTOMATICALLYGENERATEGROUPKEY}" onclick = "$('group_key_id').value = '{$T_NEW_UNIQUE_KEY}';"/></td>
                                                                                       <td><img src = "images/16x16/help.png" style= "vertical-align:middle;border:0;" alt = "{$smarty.const._INFO}" title = "{$smarty.const._INFO}" onclick = "eF_js_showHideDiv(this, 'display_key_info', event)"><div id = 'display_key_info' onclick = "eF_js_showHideDiv(this, 'display_key_info', event)" class = "popUpInfoDiv" style = "padding:1em 1em 1em 1em;width:450px;position:absolute;z-index:100;display:none">{$smarty.const._UNIQUEGROUPKEYINFO}</div></td>
                                                                                    </tr>
                                                                            </table>
                                                                            </td></tr>
                                                                <tr><td>&nbsp;</td></tr>
                                                                <tr><td></td><td>{$T_USERGROUPS_FORM_R.submit_type.html}</td></tr>
                                                            </table>
                                                            </form>
                                            {/capture}
{capture name = "t_group_users_code"}
<!--ajax:usersTable-->
                                                    <table style = "width:100%" class = "sortedTable" size = "{$T_USERS_SIZE}" sortBy = "0" id = "usersTable" useAjax = "1" rowsPerPage = "{$smarty.const.G_DEFAULT_TABLE_SIZE}" url = "administrator.php?ctg=user_groups&edit_user_group={$smarty.get.edit_user_group}&">
                                                        <tr class = "topTitle">
                                                            <td class = "topTitle" name = "login">{$smarty.const._USER}</td>
                                                            <td class = "topTitle" name = "user_type">{$smarty.const._USERTYPE}</td>
                                                            <td class = "topTitle centerAlign" name = "in_group">{$smarty.const._CHECK}</td>
                                                        </tr>
                                        {foreach name = 'users_to_lessons_list' key = 'key' item = 'user' from = $T_GROUP_USERS}
                                                        <tr class = "defaultRowHeight {cycle values = "oddRowColor, evenRowColor"} {if !$user.active}deactivatedTableElement{/if}">
                                                            <td><a href = "administrator.php?ctg=users&edit_user={$user.login}" class = "editLink" {if ($user.pending == 1)}style="color:red;"{/if}><span id="column_{$user.login}" {if !$user.active}style="color:red;"{/if}>#filter:login-{$user.login}#</span></a></td>
                                                            <td>{$user.user_type}</td>
                                                            <td align = "center">
                                                        {if !isset($T_CURRENT_USER->coreAccess.users) || $T_CURRENT_USER->coreAccess.users == 'change'}
                                                                <input class = "inputCheckbox" type = "checkbox" id = "checked_{$user.login}" name = "checked_{$user.login}" onclick = "ajaxPost('{$user.login}', this, 'usersTable');" {if $user.in_group == 1}checked = "checked"{/if} />
                                                        {else}
                                                                {if $user.in_group == 1}<img src = "images/16x16/success.png" alt = "{$smarty.const._GROUPUSER}" title = "{$smarty.const._GROUPUSER}">{/if}
                                                        {/if}
                                                            </td>
                                                    </tr>
                                        {/foreach}
                                                </table>
<!--/ajax:usersTable-->
{/capture}
{capture name = "t_group_lessons_code"}
                                     <div class = "headerTools">
                                         <span>
                                             <img src = "images/16x16/users.png" title = "{$smarty.const._ASSIGNLESSONSTOGROUPUSERS}" alt = "{$smarty.const._ASSIGNLESSONSTOGROUPUSERS}">
                                             <a href = "javascript:void(0)" onclick = "assignToGroupUsers(Element.extend(this).previous(), 'lessons')" title = "{$smarty.const._ASSIGNLESSONSTOGROUPUSERS}" >{$smarty.const._ASSIGNLESSONSTOGROUPUSERS}</a>
                                         </span>
                                     </div>
<!--ajax:lessonsTable-->
                                                <table style = "width:100%" class = "sortedTable" size = "{$T_LESSONS_SIZE}" sortBy = "0" id = "lessonsTable" useAjax = "1" rowsPerPage = "{$smarty.const.G_DEFAULT_TABLE_SIZE}" url = "administrator.php?ctg=user_groups&edit_user_group={$smarty.get.edit_user_group}&">
                                                   <tr class = "topTitle">
                                                        <td name = "name" class = "topTitle">{$smarty.const._NAME}</td>
                                                        <td name = "directions_ID">{$smarty.const._PARENTDIRECTIONS}</td>
                                                     <td name = "price" class = "topTitle centerAlign">{$smarty.const._PRICE}</td>
                             {if !isset($T_CURRENT_USER->coreAccess.users) || $T_CURRENT_USER->coreAccess.users == 'change'}
                                                        <td name = "partof" class = "topTitle centerAlign">{$smarty.const._CHECK}</td>
                                {/if}
                                                    </tr>
                                {foreach name = 'users_to_lessons_list' key = 'key' item = 'lesson' from = $T_GROUP_LESSONS}
                                                    <tr class = "defaultRowHeight {cycle values = "oddRowColor, evenRowColor"} {if !$lesson.active}deactivatedTableElement{/if}">
                                                        <td>{$lesson.name}</td>
                                                        <td>{$lesson.direction_name}</td>
                                                        <td class = "centerAlign">{if $course.price == 0}{$smarty.const._FREECOURSE}{else}{$course.price_string}{/if}</td>
                                 {if (!isset($T_CURRENT_USER->coreAccess.users) || $T_CURRENT_USER->coreAccess.users == 'change')}
                                                     <td class = "centerAlign">
                                                         <input class = "inputCheckBox" type = "checkbox" id = "lesson_{$lesson.id}" name = "lesson_{$lesson.id}" onclick ="ajaxPost('{$lesson.id}', this, 'lessonsTable');" {if $lesson.in_group}checked{/if}>
                                                     </td>
                                 {/if}
                                                    </tr>
                                {foreachelse}
                                                    <tr class = "defaultRowHeight oddRowColor"><td class = "emptyCategory" colspan = "6">{$smarty.const._NODATAFOUND}</td></tr>
                                {/foreach}
                                                </table>
<!--/ajax:lessonsTable-->
{/capture}
{capture name = "t_group_courses_code"}
                                     <div class = "headerTools">
                                         <span>
                                             <img src = "images/16x16/users.png" title = "{$smarty.const._ASSIGNCOURSESTOGROUPUSERS}" alt = "{$smarty.const._ASSIGNCOURSESTOGROUPUSERS}">
                                             <a href = "javascript:void(0)" onclick = "assignToGroupUsers(Element.extend(this.previous()), 'courses')" title = "{$smarty.const._ASSIGNCOURSESTOGROUPUSERS}" >{$smarty.const._ASSIGNCOURSESTOGROUPUSERS}</a>
                                         </span>
                                     </div>
<!--ajax:coursesTable-->
                                                <table style = "width:100%" class = "sortedTable" size = "{$T_COURSES_SIZE}" sortBy = "0" id = "coursesTable" useAjax = "1" rowsPerPage = "{$smarty.const.G_DEFAULT_TABLE_SIZE}" url = "administrator.php?ctg=user_groups&edit_user_group={$smarty.get.edit_user_group}&">
                                                <tr class = "topTitle">
                                                     <td name = "name" class = "topTitle">{$smarty.const._NAME}</td>
                                                     <td name = "directions_ID">{$smarty.const._PARENTDIRECTIONS}</td>
                                                     <td name = "price" class = "topTitle centerAlign">{$smarty.const._PRICE}</td>
                             {if $smarty.session.s_type == "administrator" && (!isset($T_CURRENT_USER->coreAccess.users) || $T_CURRENT_USER->coreAccess.users == 'change')}
                                                     <td name = "partof" class = "topTitle centerAlign">{$smarty.const._CHECK}</td>
                             {/if}
                                                 </tr>
                             {foreach name = 'users_to_courses_list' key = 'key' item = 'course' from = $T_GROUP_COURSES}
                                                 <tr class = "defaultRowHeight {cycle values = "oddRowColor, evenRowColor"} {if !$course.active}deactivatedTableElement{/if}">
                                                     <td>{$course.name}</td>
                                                     <td>{$course.direction_name}</td>
                                                     <td class = "centerAlign">{if $course.price == 0}{$smarty.const._FREECOURSE}{else}{$course.price_string}{/if}</td>
                                 {if (!isset($T_CURRENT_USER->coreAccess.users) || $T_CURRENT_USER->coreAccess.users == 'change')}
                                                     <td class = "centerAlign">
                                                         <input class = "inputCheckBox" type = "checkbox" id = "course_{$course.id}" name = "course_{$course.id}" onclick ="ajaxPost('{$course.id}', this, 'coursesTable');" {if $course.in_group}checked{/if}>
                                                     </td>
                                 {/if}
                                                 </tr>
                             {foreachelse}
                                                 <tr class = "defaultRowHeight oddRowColor"><td class = "emptyCategory" colspan = "6">{$smarty.const._NODATAFOUND}</td></tr>
                             {/foreach}
                                                </table>
<!--/ajax:coursesTable-->
{/capture}
                                            {capture name='t_new_group_code'}
                                            <div class = "tabber">
            {eF_template_printBlock tabber = "groups" title=$smarty.const._GROUPOPTIONS data=$smarty.capture.t_group_form image='32x32/generic.png'}
                             {if $smarty.get.edit_user_group}
                              <script>var editGroup = '{$smarty.get.edit_user_group}';</script>
            {eF_template_printBlock tabber = "users" title=$smarty.const._GROUPUSERS data=$smarty.capture.t_group_users_code image='32x32/users.png'}
            {eF_template_printBlock tabber = "lessons" title=$smarty.const._GROUPLESSONS data=$smarty.capture.t_group_lessons_code image='32x32/lessons.png'}
            {eF_template_printBlock tabber = "courses" title=$smarty.const._GROUPCOURSES data=$smarty.capture.t_group_courses_code image='32x32/courses.png'}
           {/if}
           </div>
                                        {/capture}
                            {if $smarty.get.add_user_group}
                                    {eF_template_printBlock title = $smarty.const._NEWGROUP data = $smarty.capture.t_new_group_code image = '32x32/users.png'}
                            {else}
                                    {eF_template_printBlock title = "`$smarty.const._OPTIONSFORGROUP` <span class = 'innerTableName'>&quot;`$T_USERGROUPS_FORM_R.name.value`&quot;</span>" data = $smarty.capture.t_new_group_code image = '32x32/users.png'}
                            {/if}
                        {else}
                            {capture name = 't_groups_code'}
                            <script>var activate = '{$smarty.const._ACTIVATE}';var deactivate = '{$smarty.const._DEACTIVATE}';</script>
                                        {if !isset($T_CURRENT_USER->coreAccess.users) || $T_CURRENT_USER->coreAccess.users == 'change'}
                                   <div class = "headerTools">
                                       <span>
                                           <img src = "images/16x16/add.png" title = "{$smarty.const._NEWGROUP}" alt = "{$smarty.const._NEWGROUP}">
                                           <a href = "administrator.php?ctg=user_groups&add_user_group=1" title = "{$smarty.const._NEWGROUP}" >{$smarty.const._NEWGROUP}</a>
                                       </span>
                                   </div>
                                   {assign var = "change_groups" value = 1}
                                        {/if}
                                                    <table width = "100%" class = "sortedTable" sortBy = "0">
                                                        <tr class = "topTitle">
                                                            <td class = "topTitle">{$smarty.const._NAME}</td>
                                                            <td class = "topTitle">{$smarty.const._DESCRIPTION}</td>
                                                            <td class = "topTitle centerAlign">{$smarty.const._USERS}</td>
                                                            <td class = "topTitle centerAlign">{$smarty.const._ACTIVE2}</td>
                                                        {if $change_groups}
                                                            <td class = "topTitle centerAlign noSort">{$smarty.const._OPERATIONS}</td>
                                                        {/if}
                                                        </tr>
                                                {foreach name = 'group_list' key = 'key' item = 'group' from = $T_USERGROUPS}
                                                        <tr id="row_{$group.id}" class = "{cycle values = "oddRowColor, evenRowColor"} {if !$group.active}deactivatedTableElement{/if}">
                                                            <td><a href = "administrator.php?ctg=user_groups&edit_user_group={$group.id}" class = "editLink">
                                                             <span id="column_{$group.id}" {if !$group.active}style="color:red"{/if}>
                                                              {$group.name}
                                                             </span></a></td>
                                                            <td>{$group.description}</td>
                                                            <td class = "centerAlign">{$group.num_users}</td>
                                                            <td class = "centerAlign">
                                                                {if $group.active == 1}
                                                                 <img class = "ajaxHandle" src = "images/16x16/trafficlight_green.png" alt = "{$smarty.const._DEACTIVATE}" title = "{$smarty.const._DEACTIVATE}" {if $change_groups}onclick = "activateGroup(this, '{$group.id}')"{/if}>
                                                                {else}
                                                                    <img class = "ajaxHandle" src = "images/16x16/trafficlight_red.png" alt = "{$smarty.const._ACTIVATE}" title = "{$smarty.const._ACTIVATE}" {if $change_groups}onclick = "activateGroup(this, '{$group.id}')"{/if}>
                                                                {/if}
                                                            </td>
                                                        {if $change_groups}
                                                            <td class = "centerAlign">
                                                                    <a href = "administrator.php?ctg=user_groups&edit_user_group={$group.id}" ><img border = "0" src = "images/16x16/edit.png" title = "{$smarty.const._EDIT}" alt = "{$smarty.const._EDIT}" /></a>
                                                                    <img class = "ajaxHandle" border = "0" src = "images/16x16/error_delete.png" title = "{$smarty.const._DELETE}" alt = "{$smarty.const._DELETE}" onclick = "if (confirm('{$smarty.const._AREYOUSUREYOUWANTTODELETEGROUP}')) deleteGroup(this, '{$group.id}');"/>
                                                            </td>
                                                        {/if}
                                                        </tr>
                                {foreachelse}
                                                    <tr class = "defaultRowHeight oddRowColor"><td class = "emptyCategory" colspan = "100%">{$smarty.const._NODATAFOUND}</td></tr>
                                {/foreach}
                                                    </table>
                            {/capture}
                            {eF_template_printBlock title = $smarty.const._UPDATEGROUPS data = $smarty.capture.t_groups_code image = '32x32/users.png'}
                        {/if}
    </td></tr>
        {/capture}