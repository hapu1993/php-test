<?php
/**
 * This file is part of the Riskpoint Framework Software.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package Riskpoint/Core
 * @subpackage Core
 * @license http://opensource.org/licenses/MIT MIT
 */

require_once dirname(__FILE__).'/config/global.php';
require_once $cfg['source_root'] . "includes/common_form_includes.php";

$id = my_get("user_id");
$u = new User;

if ($user1->id==$id) {
    $html .= '<div class="no_data">You cannot deactivate your own account.
    please ask anther admin-level user to do this for you.</div>';

    $html .= $libhtml->render_submit_button("deactivate", "Update", array('show_action'=>false));
} else {
    $u->select($id);
    $u->set_post(my_post('user'));
    $html .= $u->print_deactivate_form();
}

$libhtml->render_form($html);
