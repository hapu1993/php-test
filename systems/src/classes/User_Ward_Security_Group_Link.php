<?php

class User_Ward_Security_Group_Link extends Object {

    public $table = "user_ward_security_groups_links";
    public $left_join = "LEFT JOIN system_users u ON t.user_id = u.id
                         LEFT JOIN ward_security_groups g on g.id = t.group_id";
    public $other_selects = ",g.name, u.fullname, u.username";
    public $orderby = "";
    public $dir = " ASC";

}
