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
namespace Riskpoint\Auth;

use Riskpoint\Auth\AD\FullName as FullName;
use Riskpoint\Auth\AD\UserAccountControl as UserAccountControl;

class LDAP
{
    protected $ad = null;
    protected $provider = null;
    protected $displayName = null;

    public function __construct()
    {
        global $db;
        $ad = new \Adldap\Adldap();
        $this->ad = $ad;  // only used to expose extended error message;
        $settings = new \Riskpoint\Auth\LDAP\Setting;
        $config = $settings->getAdldapConfig();
        $this->displayName = $settings->fullname;
        try {
            $ad->addProvider($config);
            unset($config);
            $this->provider = $ad->connect();
        } catch (\Adldap\Auth\BindException $e) {
            unset($config);
            $message = $e->getMessage();
            error_log('[' . $db->database . "] Caught LDAP Bind Exception: $message");
            $extended_error = $this->getExtendedErrorMessage();
            if (!is_null($extended_error)) {
                error_log('[' . $db->database . "] Extended LDAP error: $extended_error");
            }
        } catch (\Exception $e) {
            unset($config);
            $message = $e->getMessage();
            error_log('[' . $db->database . "] Caught LDAP Generic Exception: $message");
            $extended_error = $this->getExtendedErrorMessage();
            if (!is_null($extended_error)) {
                error_log('[' . $db->database . "] Extended LDAP error: $extended_error");
            }
        }
    }

    public function getExtendedErrorMessage()
    {
        $defaultProvider = $this->ad->getDefaultProvider();
        if (!is_null($defaultProvider)) {
            $defaultProviderConnectionObject = $defaultProvider->getConnection();
            if (!is_null($defaultProviderConnectionObject)) {
                if (ldap_get_option($defaultProviderConnectionObject->getConnection(), \LDAP_OPT_DIAGNOSTIC_MESSAGE, $extended_error)) {
                    return $extended_error;
                }
            }
        }
        return null;
    }

    public function isProviderAvailable()
    {
        if (is_null($this->provider)) {
            return false;
        }
        return true;
    }

    public function authenticate($username, $password)
    {
        global $db;
        try {
            $username = $this->assembleUser($username);
            if ($this->provider->auth()->attempt($username, $password, true)) {
                return true;
            } else {
                return false;
            }
        } catch (\Adldap\Auth\UsernameRequiredException $e) {
            $message = $e->getMessage();
            error_log('[' . $db->database . "] Caught LDAP UsernameRequiredException: $message");
        } catch (\Adldap\Auth\PasswordRequiredException $e) {
            $message = $e->getMessage();
            error_log('[' . $db->database . "] Caught LDAP PasswordRequiredException: $message");
        }
        return false;
    }

    protected function getDomain()
    {
        try {
            $search = $this->provider->search();
            $results = $search->select('name')->Where(
                array(
                    array('objectCategory', '=', 'domain')
                )
            )->firstOrFail();
            return $results->getAttribute('name')[0];
        } catch (\Exception $e) {
            $message = $e->getMessage();
            error_log('[' . $db->database . "] Caught LDAP Excpetion: $message");
        }
    }

    protected function assembleUser($username)
    {
        // Just return unmodified as Taz/Manchester are not returning the domain
        // May need investigation at another time.
        return $username;
        if (strpos($username, '@') === false && strpos($username, '\\') === false) {
            $domain = $this->getDomain();
            return $domain . '\\' . $username;
        }
        return $username;
    }

    public function getUser($username, $selects = array('*', 'objectguid', 'msDS-PrincipalName'))
    {
        global $db;
        try {
            if ($this->isProviderAvailable() === false) {
                throw new \Exception('[' . $db->database . "] No AD/LDAP Provider available.");
            }

            $username = $this->assembleUser($username);
            $username_parts = array();
            $username_parts = explode('\\', $username);
            if (count($username_parts) != 1) {
                $username = $username_parts[1];
            }

            $search = $this->provider->search();
            $results = $search->select($selects)->orWhere(
                array(
                    array('sAMAccountName', '=', $username)
                )
            )->orWhere(
                array(
                    array('userPrincipalName', '=', $username)
                )
            )->firstOrFail();

            $msds_principalname = $results->getAttribute('msds-principalname')[0];
            $username = implode('\\', $username_parts);
            if (count($username_parts) != 1
                && strtolower($username) != strtolower($msds_principalname)
            ) {
                throw new \Exception("User matched but not on msds-principalname $username, $msds_principalname");
            }
            return $results;
        } catch (\Exception $e) {
            $message = $e->getMessage();
            error_log('[' . $db->database . "] Caught LDAP Excpetion: $message");
        }
    }

    public function getUserId($results)
    {
        return \Adldap\Utilities::binaryGuidToString($results->objectguid[0]);
    }

    public function getUserArray($username)
    {
        global $db;
        $nice_results = array();
        try {
            $results = $this->getUser($username);
            if (!is_a($results, '\Adldap\Models\User')) {
                throw new \Exception('[' . $db->database . '] Not a user object');
            }
            $active = UserAccountControl::isActive($results->getUserAccountControl());
            $expired = UserAccountControl::hasPasswordExpired($results->getUserAccountControl());
            $email = $results->mail[0];
            if (empty($email)) {
                $email = sprintf('%s@localhost', $results->getCommonName());
            }
            $nice_results = array(
                'username' => $username,
                'commonName' => $results->getCommonName(),
                'firstName' => $results->getFirstName(),
                'lastName' => $results->getLastName(),
                'distinguishedName' => $results->getDistinguishedName(),
                'displayName' => $results->getDisplayName(),
                'name' => $results->name[0],
                'configuredFullName' => FullName::get($results, $this->displayName),
                'userPrincipalName' => $results->getUserPrincipalName(),
                'email' => $email,
                'guid' => $this->getUserId($results),
                'groups' => $results->memberof,
                'useraccountcontrol' => $results->getUserAccountControl(),
                'active' => $active,
                'expired' => $expired,
                'msds-principalname' => $results->getAttribute('msds-principalname')[0],
            );
        } catch (\Exception $e) {
            error_log('[' . $db->database . "] AD/LDAP user $username not found");
        }

        return $nice_results;
    }

    public function show($username)
    {
        global $libhtml;

        $html = '';
        $group_mappings = new \Riskpoint\Auth\LDAP\GroupMapping();
        $user = $this->getUserArray($username);

        if (empty($user)) {
            $html .= '<div class="hint">No user found</div>';
        } else {
            $html = open_table("800px", "User Details");
            $html .= $libhtml->render_table_row('Distinguished Name', $user['distinguishedName']);
            $html .= $libhtml->render_table_row('Common Name', $user['commonName']);
            $html .= $libhtml->render_table_row('First Name', $user['firstName']);
            $html .= $libhtml->render_table_row('Last Name', $user['lastName']);
            $html .= $libhtml->render_table_row('Name', $user['name']);
            $html .= $libhtml->render_table_row('Display Name', $user['displayName']);
            $html .= $libhtml->render_table_row('Configured Full Name', $user['configuredFullName']);
            $html .= $libhtml->render_table_row('User Principal Name', $user['userPrincipalName']);
            $html .= $libhtml->render_table_row('Email Address', $user['email']);
            $html .= $libhtml->render_table_row('GUID', $user['guid']);
            $html .= $libhtml->render_table_row('Active', tick_cross_image($user['active']));
            $html .= $libhtml->render_table_row('Expired', tick_cross_image($user['expired']));
            $html .= $libhtml->render_table_row('User Logon Name', $user['msds-principalname']);
            if (is_array($user['groups'])) {
                $html .= table_separator('800px', 'AD/LDAP User Groups');
                foreach ($user['groups'] as $group) {
                    $html .= $libhtml->render_table_row('User Group', $group);
                }
                $html .= table_separator('800px', 'System User Groups');
                $groups = $group_mappings->getGroups($user['groups']);
                foreach ($groups as $group) {
                    $html .= $libhtml->render_table_row('User Group', $group);
                }
            }
            $html .= close_table();
        }
        return $html;
    }

    protected function updateNativeUserGroups($user, array $ldap_groups = array())
    {
        $user_group_ids = array();
        if (!empty($user->group_ids)) {
            $user_group_ids = array_fill_keys(explode(',', $user->group_ids), 0);
        }
        foreach ($user_group_ids as $group_id => $access) {
            // depermission existing user groups by default to allow depermissioning via AD/LDAP
            $user_group_ids[$group_id] = 0;
        }
        if (!empty($ldap_groups)) {
            $group_mappings = new \Riskpoint\Auth\LDAP\GroupMapping();
            $group_ids = $group_mappings->getGroupIDs($ldap_groups);
            foreach ($group_ids as $group_id) {
                $user_group_ids[$group_id] = 1;
            }

            $user->user_group_ids = $user_group_ids;
            $user->set_user_groups();
        }
    }

    public function upsertSystemUser($ldap_user)
    {
        global $db;
        if (!empty($ldap_user)) {
            $user = new \User();
            $existing_user = $user->_get(array('where' => array('WHERE password = ?', array('LDAP Account ' . $ldap_user['guid']), array('varchar'))));
            if (!empty($existing_user)) {
                array2object($existing_user, $user);
            } else {
                $user->password = 'LDAP Account ' . $ldap_user['guid'];
            }
            $user->username = $ldap_user['msds-principalname'];
            $user->fullname = $ldap_user['configuredFullName'];
            $user->email = $ldap_user['email'];
            $user->active = $ldap_user['active'];
            if ($ldap_user['active'] === true) {
                $user->active = !$ldap_user['expired'];
            }

            if (!empty($existing_user)) {
                $db->update(
                    'system_users',
                    array(
                        'username' => $user->username,
                        'password' => $user->password,
                        'fullname' => $user->fullname,
                        'email'    => $user->email,
                        'active'   => $user->active
                    ),
                    array(
                        'WHERE id = ?',
                        array('id' => $user->id),
                        array('integer')
                    )
                );
            } else {
                $user_id = null;
                $user_id = $db->insert(
                    'system_users',
                    array(
                        'username'  => $user->username,
                        'password'  => $user->password,
                        'fullname'  => $user->fullname,
                        'email'     => $user->email,
                        'active'    => $user->active,
                        'auth_type' => 'AD/LDAP',
                        'created_time' => date('Y-m-d H:i:s')
                    )
                );

                if (is_null($user_id) || empty($user_id)) {
                    throw new \Exception('[' . $db->database . "] Unable to add AD/LDAP user: " . $user->username);
                }
                $existing_user = $user->_get(array('where' => array('WHERE id = ?', array($user_id), array('integer'))));
                array2object($existing_user, $user);
            }

            $this->updateNativeUserGroups($user, $ldap_user['groups']);
        }
    }
}
