<?php
    require_once dirname(__FILE__).'/../config/global.php';
    require_once $cfg['source_root'] . "includes/common_ajax_includes.php";

    if ($user1->logged_in) {

        $tags = my_post("data");
        $selected = my_post("selected");

        // different queries depending if there there are any tags inserted
        if (!empty($tags)) {
            $count = count($tags);

            $selection = $db->select("i.*, (SELECT GROUP_CONCAT(t.tag SEPARATOR ', ') FROM system_objects_tags it INNER JOIN system_tags t ON t.id = it.tag_id WHERE it.object_id = i.id AND it.related_object_name = 'lib_image') AS tags",
                 "cms_lib_images i",
                array("WHERE i.access_public", array(), array()),
                array('joins' => "INNER JOIN (SELECT it.object_id, COUNT(*) AS total FROM system_objects_tags it WHERE it.tag_id IN (".implode(",", $tags).") GROUP BY it.object_id HAVING count(*)=".$count.") xi ON xi.object_id = i.id")
            );

        } else {
            $selection = $db->select("i.*, (SELECT GROUP_CONCAT(t.tag SEPARATOR ', ') FROM system_objects_tags it INNER JOIN system_tags t ON t.id = it.tag_id WHERE it.object_id = i.id AND it.related_object_name = 'lib_image') as tags",
                "cms_lib_images i",
                array("WHERE i.access_public", array(), array()));

        }

        // format the output
        if (!empty($selection)) {

            $html = '';
            foreach($selection as $image){

                $sclass = (!empty($selected) && $image->id == $selected) ? 'selected' : '';
                $imgdesc = (!empty($image->description)) ? $image->description  . "<br/>" : "";
                $imgat = (!empty($image->tags)) ? '<b>Tags:</b> <i style=\'color:#666;\'>'.$image->tags.'</i>' : '';
                $imgtitle = $imgdesc . $imgat;

                $html .= '<a class="img tooltip '.$sclass.'" href="#" title="'.$imgtitle.'" data-lid="'.$image->id.'">
                    <span class="rm"><i class="fa fa-times"></i></span>
                    <img src="'. phpThumb_URL(array(
                        "src"=>$cfg['secure_dir'] . $image->image,
                        "w"=>60,
                        "h"=>60,
                        "zc"=>1
                        )) . '" alt="'.str_replace(substr(basename($image->image), 0, 14), '', basename($image->image)). '"/>
                </a>';
            }

        } else {
            if (empty($tags)) $html = '<span class="msg">The images library is empty</span>';
            else $html = '<span class="msg">There are no images that match the selected tags</span>';

        }

        echo $html;

    }

    $db->close();
