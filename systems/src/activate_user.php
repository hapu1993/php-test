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

$libhtml->standard_form(array(
    "class_name"=>"User",
    "type"=>"activate",
    "title"=>"Activate User"
));
