<?php

class Ward_Security_Groups_Permissions_Link extends Object {

    public $table = "ward_security_groups_permissions";
    public $left_join = "LEFT JOIN wards w ON t.ward_id = w.id
                         LEFT JOIN ward_security_groups g on g.id = t.group_id";
    public $other_selects = ",CONCAT_WS(' - ',w.hl7_id,w.name) as ward_name, g.name as group_name";
    public $orderby = "";
    public $dir = " ASC";

}
