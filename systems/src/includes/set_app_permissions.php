<?php
    require_once dirname(__FILE__).'/../config/global.php';
    require_once $cfg['source_root'] . "includes/common_plain_includes.php";

    if ($user1->logged_in) {

        $app = my_get("app");
        $p = my_get("p");
        $level = my_get("level");

        if (!empty($app) && !empty($p) && !empty($level)) {

            $selection = $db->select("id, name, path, image, tooltip, front_end_app","system_apps",array("WHERE active=1",array(),array()));

			foreach($selection as $app2) $apps[] = $app2;
            foreach($apps as $item) if ($item->name == $app) $s_app = $item;

            // front end and back end
            if (empty($s_app->front_end_app)) {

                $path = $cfg['source_root'] . $s_app->path;

            } else {

                $path = $cfg['website_source'];

            }

            if ($p=="enable") {
                $p=1;
                $change_text="Allow access to all files";
            } else {
                $p=0;
                $change_text="Deny access to all files";
            }

            // enable or disable
            if ($p == 1) {

                if ($dir = opendir($path)) {

					$dir_array = simple_get_files($path);

                    foreach($dir_array as $file) {

                        if(!is_dir($path.$file) && preg_match('/php/', extension($file))) {

                            // check if page exists in permissions table
							if(empty($s_app->front_end_app)){

								$page = $s_app->path . $file;
								$current_permission = $db->select(
									"p.page, p.front_end_app",
									"system_user_group_permissions per
									LEFT JOIN system_pages p ON p.id=per.page_id
									LEFT JOIN system_user_groups u ON u.id=per.group_id",
									array(
										"WHERE p.page = ? AND (p.front_end_app = 0 OR p.front_end_app IS NULL) AND per.group_id = ?",
										array('page' => $page, 'group_id' => $level),
										array('varchar', 'integer')
									)
								);
								$page_id = $db->select(
									"id",
									"system_pages",
									array("WHERE page = ? AND (front_end_app = 0 OR front_end_app IS NULL)", array('page' => $page),array('varchar'))
								);

							} else {

								$page = $file;
								$current_permission = $db->select(
									"p.page, p.front_end_app",
									"system_user_group_permissions per
									LEFT JOIN system_pages p ON p.id=per.page_id
									LEFT JOIN system_user_groups u ON u.id=per.group_id",
									array(
										"WHERE p.page = ? AND p.front_end_app = 1 AND per.group_id = ?",
										array('page' => $page, 'group_id' => $level),
										array('varchar', 'integer')
									)
								);
								$page_id = $db->select(
									"id",
									"system_pages",
									array("WHERE page = ? AND front_end_app =1", array('page' => $page),array('varchar'))
								);
							}

							// If file does not exist insert new table row and give Admin permission
                            if (empty($current_permission) && !empty($page_id[0]->id)) {
                                $db->insert("system_user_group_permissions",array('page_id'=>$page_id[0]->id, 'group_id'=>$level));
                            }

                        }

                    }

                    $db->insert(
						"system_log",
						array(
	                        'time' => date("Y-m-d H:i:s", time()),
	                        'user_id' => $user1->id,
	                        'object' => "User_Group",
	                        'action' => $change_text,
	                        'object_id' => $level,
	                        'comment' => $s_app->path,
	                    )
					);
                }

            } elseif ($p == 0) {

                if ($dir = opendir($path)) {

                    $dir_array = simple_get_files($path);

					foreach($dir_array as $file) {

					    if(!is_dir($path.$file) && preg_match('/php/', extension($file))) {

							// check if page exists in permissions table
							if(empty($s_app->front_end_app)){

								$page = $s_app->path . $file;
								$current_permission = $db->select(
									"p.page, p.front_end_app",
									"system_user_group_permissions per
									LEFT JOIN system_pages p ON p.id=per.page_id
									LEFT JOIN system_user_groups u ON u.id=per.group_id",
									array(
										"WHERE p.page = ? AND (p.front_end_app = 0 OR p.front_end_app IS NULL) AND per.group_id = ?",
										array('page' => $page, 'group_id' => $level),
										array('varchar', 'integer')
									)
								);
								$page_id = $db->select(
									"id",
									"system_pages",
									array("WHERE page = ? AND (front_end_app = 0 OR front_end_app IS NULL)", array('page' => $page),array('varchar'))
								);

							} else {

								$page = $file;
								$current_permission = $db->select(
									"p.page, p.front_end_app",
									"system_user_group_permissions per
									LEFT JOIN system_pages p ON p.id=per.page_id
									LEFT JOIN system_user_groups u ON u.id=per.group_id",
									array(
										"WHERE p.page = ? AND p.front_end_app = 1 AND per.group_id = ?",
										array('page' => $page, 'group_id' => $level),
										array('varchar', 'integer')
									)
								);
								$page_id = $db->select(
									"id",
									"system_pages",
									array("WHERE page = ? AND front_end_app =1", array('page' => $page),array('varchar'))
								);
							}

							//If file does not exist insert new table row and give Admin permission
							if (!empty($current_permission) && !empty($page_id[0]->id)) {
								$db->delete(
									"system_user_group_permissions",
									array(
										"WHERE page_id = ? AND group_id = ?",
										array('page' => $page_id[0]->id, 'group_id' => $level),
										array('varchar', 'integer')
									)
								);
							}

                        }

                    }

                    $db->insert(
						"system_log",
						array(
	                        'time' => date("Y-m-d H:i:s", time()),
	                        'user_id' => $user1->id,
	                        'object' => "User_Group",
	                        'action' => $change_text,
	                        'object_id' => $level,
	                        'comment' => $s_app->path,
                    	)
					);

                }

            }

        }

    }

    $db->close();

    header("Location: " . encrypt_url($cfg['root'] . "users.php?tab=pages&subtab=page_permissions&user_group_id=$level"));
    exit;
