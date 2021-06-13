<?php

class Solr_Owncloud_Search extends Solr {

    public $search_term = "";

    /*
     table owncloud.oc_share
        share_type = 1 for share with group.
        share_with = RiskpointSystem - need to determine how alan/alasdair/boris/matt/nik resolve as no entries in owncloud.oc_users/owncloud.oc_group_user
        may need to get permissions for reading.

        share_type = 0 for share with user
        share_with = username

        file_source = int e.g. 34 for /projects
        uid_owner = username e.g. admin - use for assembing real path

     table owncloud.oc_filecache
        fileid - maps to owncloud.oc_share.file_source
        path e.g. files/projects maps to storage location e.g. admin/files/projects

     currently all users are in the RiskpointSystem group - no mapping of users at all

     */


    /**
     * Returns the search form for searching.
     *
     * @uses Libhtml::form_start(), Libhtml::render_form_table_row(), Libhtml::render_form_table_row_hidden(), Libhtml::render_actions(), Libhtml::render_button(), Libhtml::form_end()
     *
     * @return string of html.
     */

    function print_search_form() {
        global $libhtml, $my_get;
        $html  = $libhtml->form_start();
        $html .= open_table("600px","","action_form");

        $html .= $libhtml->render_form_table_row("search_term", my_request('search_term', ''), "Search String", "search_term");

        $html .= close_table();
        $html .= $libhtml->render_form_table_row_hidden("search", "Search");
        $html .= $libhtml->render_form_table_row_hidden("move_to_get", true);

        $html .= $libhtml->render_actions(
            array(
                $libhtml->render_button("search_button", "Search"),
            ),
            array(
                "show_prompt"=>false,
                "show_cancel"=>false,
                "pause"=>false,
            )
        );

        $html .= $libhtml->form_end();
        return $html;
    }

    /**
     * Returns the number of files indexed.
     *
     * @param string $query query string to search on.
     *
     * @uses Solr_Owncloud::_get_query_with_shares(), Solr::_search()
     *
     * @return array of search results
     */
    function count($query='*') {

        $q_string = "&fl=resourcename";
        $query_with_shares = $this->_get_query_with_shares($query);
        return parent::count($query_with_shares);
    }


    /**
     * searches the indexes for a given search temp.
     *
     * @param string $query query string to search on.
     * @param int $rows pagination size.
     * @param int $start pagination offset.
     *
     * @uses Solr_Owncloud::_get_query_with_shares(), Solr::_search()
     *
     * @return array of search results
     */
    function search($search_term, $rows=10, $start=0) {
        $query_with_shares = $this->_get_query_with_shares($search_term);
        return parent::search($query_with_shares, $rows, $start);
    }


    /**
     * Returns the search term taking into account the user shares.
     *
     * @uses DB2::select()
     *
     * @return string
     */
    private function _get_query_with_shares($query) {
        global $cfg, $user1;

        if (!isset($cfg['owncloud_db']) || empty($cfg['owncloud_db'])) throw new Exception("riskpoint config variable owncloud_db is not set.");
        if (!isset($cfg['owncloud_group']) || empty($cfg['owncloud_group'])) throw new Exception("riskpoint config variable owncloud_group is not set.");
        if (!isset($cfg['owncloud_filesystem']) || empty($cfg['owncloud_filesystem'])) throw new Exception("riskpoint config variable owncloud_filesystem is not set.");

        $query_array = array();
        $query_array[] = "(text:$query AND resourcename_text:\"".$cfg['owncloud_filesystem'].$user1->username."/\")";
        // Add shared locations
        $owncloud_db=new DB2($cfg['dbhost'], $cfg['dbuser'], $cfg['dbpass'], $cfg['owncloud_db']);
        $selection=$owncloud_db->select('s.uid_owner, f.path', 'oc_filecache f LEFT JOIN oc_share s ON s.file_source=f.fileid', array('WHERE s.share_type=? AND s.share_with=?', array(1, $cfg['owncloud_group']), array('integer', 'varchar')));

        foreach ($selection as $share) {
            $query_array[] = "OR (text:$query AND resourcename_text:\"".$cfg['owncloud_filesystem'].$share->uid_owner."/".$share->path."/\")";
        }

        return implode(' ', $query_array);
    }

}
