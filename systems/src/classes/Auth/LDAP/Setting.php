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
use Riskpoint\Auth\AD\FullName as FullName;

class Setting extends \Object
{
    public $table = "system_ldap_settings";
    public $object_name = "ldap_settings";
    public $left_join = " LEFT JOIN system_ldap_security_list l ON t.security_id = l.id";
    public $other_selects = ", l.level_name as security";
    public $orderby = "";
    public $dir = "";
    public $view_array = array();
    public $enabled = false;
    public $fullname = "DisplayName";

    public function __construct()
    {
        parent::__construct();
        $this->select(1);
    }

    public function isEnabled()
    {
        return $this->enabled;
    }

    public function getAdldapConfig()
    {
        global $crypt;

        $use_tls = false;
        $use_ssl = false;
        switch ($this->security_id) {
            case 1:
                $use_tls = true;
                break;
            case 2:
                $use_ssl = true;
                break;
        }

        $config = array(
            'domain_controllers' => array($this->domain_controller),
            'base_dn' => $this->base_dn,
            'use_tls' => $use_tls,
            'use_ssl' => $use_ssl,
            'admin_username' => $this->username,
            'admin_password' => $crypt->str_decrypt($this->password),
        );
        if (!is_null($this->port) && !empty($this->port)) {
            $config['port'] = $this->port;
        }
        return $config;
    }

    public function update(array $additional = array())
    {
        global $crypt;
        if (isset($this->new_password) && !empty($this->new_password)) {
            $this->password = $crypt->str_encrypt($this->new_password);
            $_POST['ldap_settings']['password'] = $this->password;
            $additional['password'] = $this->password;
        }
        parent::update($additional);
    }

    public function print_details()
    {
        global $libhtml;
        $html = open_table();
        $html .= $libhtml->render_table_row('Enabled', tick_cross_image($this->enabled));
        $html .= $libhtml->render_table_row('Domain Controller', $this->domain_controller);
        $html .= $libhtml->render_table_row('Port', $this->port);
        $html .= $libhtml->render_table_row('Security', $this->security);
        $html .= $libhtml->render_table_row('Base DN', $this->base_dn);
        $html .= $libhtml->render_table_row('User Fullname Field', $this->fullname);
        $html .= $libhtml->render_table_row('Admin Username', $this->username);
        if (is_null($this->password) || empty($this->password)) {
            $html .= $libhtml->render_table_row('Admin Password', '[not set]');
        } else {
            $html .= $libhtml->render_table_row('Admin Password', '******');
        }
        $html .= close_table();
        return $html;
    }

    public function print_form()
    {
        global $cfg, $db, $libhtml;

        $html = $libhtml->form_start();

        if (!function_exists('ldap_set_option')) {
            $html .= '<div class="error">LDAP extension missing.</div>';
            return $html;
        }

        $html .= open_table();

        $boolean_array = array(
            (object) array(
                'id' => 0,
                'name' => 'No'
            ),
            (object) array(
                'id' => 1,
                'name' => 'Yes'
            )
        );

        $security_list = $db->select('*', 'system_ldap_security_list', array());

        if (!isset($this->replace_password)) {
            $this->replace_password = 0;
        }

        $html .= $libhtml->render_form_table_radio_selection(
            $this->object_name . "[enabled]",
            $this->enabled,
            'Enabled',
            "enabled",
            $boolean_array,
            "id",
            "name"
        );
        $html .= $libhtml->render_form_table_row(
            $this->object_name . "[domain_controller]",
            $this->domain_controller,
            'Domain Controller (IP)',
            "domain_controller",
            array('required' => true)
        );
        $html .= $libhtml->render_form_table_row(
            $this->object_name . "[port]",
            $this->port,
            'Port (if non-standard)',
            "port"
        );
        $html .= $libhtml->render_form_table_row_selection(
            $this->object_name . "[security_id]",
            $this->security_id,
            'Security',
            "security_id",
            $security_list,
            "id",
            "level_name"
        );
        $html .= $libhtml->render_form_table_row(
            $this->object_name . "[base_dn]",
            $this->base_dn,
            'Base DN',
            "base_dn",
            array('required' => true)
        );
        $html .= $libhtml->render_form_table_row_selection(
            $this->object_name."[fullname]",
            $this->fullname,
            "User Fullname Field",
            "fullname",
            FullName::getOptions(),
            "",
            "",
            array('allowed_empty'=>false)
        );
        $html .= $libhtml->render_form_table_row(
            $this->object_name . "[username]",
            $this->username,
            'Admin Username',
            "username",
            array('required' => true)
        );
        if (!empty($this->password)) {
            $html .= $libhtml->render_form_table_row_checkbox(
                $this->object_name . "[replace_password]",
                $this->replace_password,
                'Replace Password',
                "replace_password",
                array('self_submit'=>true)
            );
            if ($this->replace_password) {
                $html .= $libhtml->render_form_table_row_password(
                    $this->object_name . "[new_password]",
                    'Admin Password',
                    "new_password",
                    array('required' => true)
                );
            }
        } else {
            $html .= $libhtml->render_form_table_row_password(
                $this->object_name . "[new_password]",
                'Admin Password',
                "new_password",
                array('required' => true)
            );
        }

        $html .= close_table();
        return $html;
    }
}
