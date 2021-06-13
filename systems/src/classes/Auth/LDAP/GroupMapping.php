<?php

/*
 * This file is a part of Riskpoint Framework Software which is released under
 * MIT Open-Source license
 *
 * Riskpoint Framework Software License - MIT License
 *
 * Copyright (C) 2008 - 2015 Riskpoint Limited
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to
 * deal in the Software without restriction, including without limitation the
 * rights to use, copy, modify, merge, publish, distribute, sublicense, and/or
 * sell copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER
 * DEALINGS IN THE SOFTWARE.
 *
 */
namespace Riskpoint\Auth\LDAP;

class GroupMapping extends \Object
{
    protected $allow_pk_insert = true;
    public $table = "system_ldap_group_mappings";
    public $object_pk = "ldap_group";
    public $object_name = "ldap_group_mapping";
    public $left_join = "
        LEFT JOIN system_user_groups u ON u.id=t.system_group_id
    ";
    public $other_selects = "
        ,u.name as system_group
    ";
    public $view_array = array(
        'ldap_group'=>array("name"=>"AD/LDAP Group","column"=>"ldap_group"),
        'system_group'=>array("name"=>"System Group","column"=>"system_group"),
    );

    public function getSystemGroups()
    {
        $system_user_group = new \User_Group();
        return $system_user_group->_get();
    }

    public function getGroups(array $group_array = array())
    {
        $placeholders = array_fill(0, count($group_array), '?');
        $types = array_fill(0, count($group_array), 'varchar');
        $user_ldap_groups = $this->_get(
            array(
                'where' => array(
                    'WHERE ldap_group in (' . implode(', ', $placeholders) . ')',
                    $group_array,
                    $types
                )
            )
        );
        $user_groups = array();
        foreach ($user_ldap_groups as $group) {
            $user_groups[] = $group->system_group;
        }
        return $user_groups;
    }

    public function getGroupIDs(array $group_array = array())
    {
        $placeholders = array_fill(0, count($group_array), '?');
        $types = array_fill(0, count($group_array), 'varchar');
        $user_ldap_groups = $this->_get(
            array(
                'where' => array(
                    'WHERE ldap_group in (' . implode(', ', $placeholders) . ')',
                    $group_array,
                    $types
                )
            )
        );
        $user_groups = array();
        foreach ($user_ldap_groups as $group) {
            $user_groups[] = $group->system_group_id;
        }
        return $user_groups;
    }

    public function print_form()
    {
        global $cfg, $db, $libhtml;
        $html = $libhtml->form_start();
        $html .= open_table();

        $html .= $libhtml->render_form_table_row(
            $this->object_name."[ldap_group]",
            $this->ldap_group,
            "AD/LDAP Group",
            "ldap_group",
            array('required'=>true)
        );

        $selection = $this->getSystemGroups();
        $html .= $libhtml->render_form_table_row_selection(
            $this->object_name."[system_group_id]",
            $this->system_group_id,
            "System Group",
            "system_group_id",
            $selection,
            'id',
            'name',
            array('required'=>true)
        );

        $html .= close_table();

        return $html;
    }
}
