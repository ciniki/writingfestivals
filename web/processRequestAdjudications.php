<?php
//
// Description
// -----------
// This function will process the adjudications page for an adjudicator.
//
// Arguments
// ---------
// ciniki:
// settings:        The web settings structure.
// tnid:     The ID of the tenant to get writing festival request for.
//
// args:            The possible arguments for posts
//
//
// Returns
// -------
//
function ciniki_writingfestivals_web_processRequestAdjudications(&$ciniki, $settings, $tnid, $args) {

    //
    // Check to make sure the module is enabled
    //
    if( !isset($ciniki['tenant']['modules']['ciniki.writingfestivals']) ) {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.writingfestivals.121', 'msg'=>"I'm sorry, the page you requested does not exist."));
    }

    //
    // Check there is a festival setup
    //
    if( !isset($args['festival_id']) || $args['festival_id'] <= 0 ) {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.writingfestivals.182', 'msg'=>"I'm sorry, the page you requested does not exist."));
    }

    //
    // This function does not build a page, just provides an array of blocks
    //
    $blocks = array();

    //
    // Load maps
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'writingfestivals', 'private', 'maps');
    $rc = ciniki_writingfestivals_maps($ciniki);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $maps = $rc['maps'];
    
    //
    // Check to make sure the customer is logged in, otherwise redirect to login page
    //
    if( !isset($ciniki['session']['customer']['id']) || $ciniki['session']['customer']['id'] == 0 ) {
        $redirect = $args['ssl_domain_base_url'];
        $join = '?';
        if( isset($_GET['r']) && $_GET['r'] != '' ) {
            $redirect .= $join . 'r=' . $_GET['r'];
            $join = '&';
        }
        if( isset($_GET['cl']) && $_GET['cl'] != '' ) {
            $redirect .= $join . 'cl=' . $_GET['cl'];
            $join = '&';
        }
        $blocks[] = array(
            'type' => 'login', 
            'section' => 'login',
            'register' => 'yes',
            'redirect' => $redirect,        // Redirect back to registrations page
            );
        return array('stat'=>'ok', 'blocks'=>$blocks);
    }

    //
    // Get the customer details
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'hooks', 'customerDetails');
    $rc = ciniki_customers_hooks_customerDetails($ciniki, $tnid, array(
        'customer_id' => $ciniki['session']['customer']['id'], 
        'addresses' => 'yes',
        'phones' => 'yes',
        'emails' => 'yes',
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.128', 'msg'=>'Internal Error', 'err'=>$rc['err']));
    }
    $customer = $rc['customer'];

    //
    // Load the schedule sections, divisions, timeslots, classes, registrations
    //
    $strsql = "SELECT sections.id AS section_id, "
        . "sections.name AS section_name, "
        . "sections.adjudicator1_id, "
        . "sections.adjudicator2_id, "
        . "sections.adjudicator3_id, "
        . "divisions.id AS division_id, "
        . "divisions.uuid AS division_uuid, "
        . "divisions.name AS division_name, "
        . "divisions.address, "
        . "DATE_FORMAT(divisions.division_date, '%W, %M %D, %Y') AS division_date_text, "
        . "timeslots.id AS timeslot_id, "
        . "timeslots.uuid AS timeslot_uuid, "
        . "IF(timeslots.name='', IFNULL(class1.name, ''), timeslots.name) AS timeslot_name, "
        . "TIME_FORMAT(timeslots.slot_time, '%l:%i %p') AS slot_time_text, "
        . "timeslots.class1_id, "
        . "timeslots.class2_id, "
        . "timeslots.class3_id, "
        . "IFNULL(class1.name, '') AS class1_name, "
        . "IFNULL(class2.name, '') AS class2_name, "
        . "IFNULL(class3.name, '') AS class3_name, "
//        . "timeslots.name AS timeslot_name, "
        . "timeslots.description, "
        . "registrations.id AS reg_id, "
        . "registrations.uuid AS reg_uuid, "
        . "registrations.display_name, "
        . "registrations.public_name, "
        . "registrations.title, "
        . "registrations.pdf_filename, "
        . "IFNULL(comments.id, 0) AS comment_id, "
        . "IFNULL(comments.comments, '') AS comments, "
        . "IFNULL(comments.grade, '') AS grade, "
        . "IFNULL(comments.score, '') AS score, "
        . "regclass.name AS reg_class_name "
        . "FROM ciniki_writingfestival_schedule_sections AS sections "
        . "LEFT JOIN ciniki_writingfestival_schedule_divisions AS divisions ON ("
            . "sections.id = divisions.ssection_id " 
            . "AND divisions.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_writingfestival_schedule_timeslots AS timeslots ON ("
            . "divisions.id = timeslots.sdivision_id " 
            . "AND timeslots.class1_id > 0 "
            . "AND timeslots.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_writingfestival_classes AS class1 ON ("
            . "timeslots.class1_id = class1.id " 
            . "AND class1.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_writingfestival_classes AS class2 ON ("
            . "timeslots.class3_id = class2.id " 
            . "AND class2.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_writingfestival_classes AS class3 ON ("
            . "timeslots.class3_id = class3.id " 
            . "AND class3.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_writingfestival_registrations AS registrations ON ("
            . "(timeslots.class1_id = registrations.class_id "  
                . "OR timeslots.class2_id = registrations.class_id "
                . "OR timeslots.class3_id = registrations.class_id "
                . ") "
            . "AND ((timeslots.flags&0x01) = 0 OR timeslots.id = registrations.timeslot_id) "
            . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_writingfestival_comments AS comments ON ("
            . "registrations.id = comments.registration_id "
            . "AND comments.adjudicator_id = '" . ciniki_core_dbQuote($ciniki, $args['adjudicator_id']) . "' "
            . "AND comments.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_writingfestival_classes AS regclass ON ("
            . "registrations.class_id = regclass.id "
            . "AND regclass.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE sections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND sections.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
        . "AND ("
            . "sections.adjudicator1_id = '" . ciniki_core_dbQuote($ciniki, $args['adjudicator_id']) . "' "
            . "OR sections.adjudicator2_id = '" . ciniki_core_dbQuote($ciniki, $args['adjudicator_id']) . "' "
            . "OR sections.adjudicator3_id = '" . ciniki_core_dbQuote($ciniki, $args['adjudicator_id']) . "' "
            . ") "
        . "";
    $strsql .= "ORDER BY section_name, divisions.division_date, division_id, slot_time, registrations.display_name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.writingfestivals', array(
        array('container'=>'divisions', 'fname'=>'division_uuid', 
            'fields'=>array('id'=>'division_id', 'uuid'=>'division_uuid', 
                'name'=>'division_name', 'date'=>'division_date_text', 'address',
                )),
        array('container'=>'timeslots', 'fname'=>'timeslot_uuid', 
            'fields'=>array('id'=>'timeslot_id', 'permalink'=>'timeslot_uuid', 'name'=>'timeslot_name', 'time'=>'slot_time_text', 
                'class1_id', 'class2_id', 'class3_id', 'description', 'class1_name', 'class2_name', 'class3_name',
                )),
        array('container'=>'registrations', 'fname'=>'reg_uuid', 
            'fields'=>array('id'=>'reg_id', 'uuid'=>'reg_uuid', 'name'=>'display_name', 'public_name', 'title', 
                'pdf_filename', 'class_name'=>'reg_class_name', 'comment_id', 'comments', 'grade', 'score')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $divisions = isset($rc['divisions']) ? $rc['divisions'] : array();


    $display = 'list';
    $base_url = $args['base_url'];
    if( isset($args['uri_split'][1]) && isset($divisions[$args['uri_split'][0]]['timeslots'][$args['uri_split'][1]]) ) {
        $division_uuid = $args['uri_split'][0];
        $timeslot_uuid = $args['uri_split'][1];
        $base_url .= '/' . $args['uri_split'][0] . '/' . $args['uri_split'][1]; 
            
        $display = 'timeslot';
        $timeslot = $divisions[$division_uuid]['timeslots'][$timeslot_uuid];

        if( isset($args['uri_split'][3]) && $args['uri_split'][3] == 'writing' && isset($timeslot['registrations'][$args['uri_split'][2]]) ) {
            $registration = $timeslot['registrations'][$args['uri_split'][2]];
            //
            // Get the tenant storage directory
            //
            ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'hooks', 'storageDir');
            $rc = ciniki_tenants_hooks_storageDir($ciniki, $tnid, array());
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            $storage_filename = $rc['storage_dir'] . '/ciniki.writingfestivals/files/' 
                . $registration['uuid'][0] . '/' . $registration['uuid'] . '_writing';
            if( !file_exists($storage_filename) ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.173', 'msg'=>'File does not exist'));
            }

            header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); 
            header("Last-Modified: " . gmdate("D,d M YH:i:s") . " GMT"); 
            header('Cache-Control: no-cache, must-revalidate');
            header('Pragma: no-cache');
            // Set mime header
            $finfo = finfo_open(FILEINFO_MIME);
            if( $finfo ) { header('Content-Type: ' . finfo_file($finfo, $storage_filename)); }
            // Open file in browser
            header('Content-Disposition: inline;filename="' . $registration['pdf_filename'] . '"');
            // Download file to filesystem
            //header('Content-Disposition: attachment;filename="' . $registration['pdf_filename'] . '"');
            header('Content-Length: ' . filesize($storage_filename));
            header('Cache-Control: max-age=0');

            $fp = fopen($storage_filename, 'rb');
            fpassthru($fp);
            exit;    
        }
    }

    if( $display == 'timeslot' ) {
        //
        // Check if form submitted
        //
        $content = '<form method="POST" class="wide">';
        $content .= '<input type="hidden" name="action" value="update">';
        foreach($timeslot['registrations'] as $registration) {
            //
            // Check if comments submitted for this registration
            //
            if( isset($_POST['action']) && $_POST['action'] == 'update' ) {
                $comments_updated = 'yes';
                $update_args = array();
                if( isset($_POST[$registration['id'] . '-comments'])
                    && $_POST[$registration['id'] . '-comments'] != $registration['comments'] 
                    ) {
                    $update_args['comments'] = $_POST[$registration['id'] . '-comments'];
                }
                if( isset($_POST[$registration['id'] . '-grade']) 
                    && $_POST[$registration['id'] . '-grade'] != $registration['grade'] 
                    ) {
                    $update_args['grade'] = $_POST[$registration['id'] . '-grade'];
                }
                if( isset($_POST[$registration['id'] . '-score']) 
                    && $_POST[$registration['id'] . '-score'] != $registration['score'] 
                    ) {
                    $update_args['score'] = $_POST[$registration['id'] . '-score'];
                }
                if( count($update_args) > 0 ) {
                    if( $registration['comment_id'] > 0 ) {
                        //
                        // Add the comment
                        //
                        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
                        $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.writingfestivals.comment', $registration['comment_id'], $update_args, 0x04);
                        if( $rc['stat'] != 'ok' ) {
                            $comments_updated = 'error';
                        }

                    } else {
                        $update_args['registration_id'] = $registration['id'];
                        $update_args['adjudicator_id'] = $args['adjudicator_id'];
                        //
                        // Add the comment
                        //
                        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
                        $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.writingfestivals.comment', $update_args, 0x04);
                        if( $rc['stat'] != 'ok' ) {
                            $comments_updated = 'error';
                        }
                    }
                }
            }

            $content .= '<div class="registration wide">'
                . '<b>Class: </b>' . $registration['class_name'] . '<br/>'
//                . '<b>Participant: </b>' . $registration['name'] . '<br/>'
                . ($registration['title'] != '' ? '<b>Title: </b>' . $registration['title'] . '<br/>' : '')
                . '<b>PDF: </b><a target="_blank" href="' . $base_url . '/' . $registration['uuid'] . '/writing">' . $registration['pdf_filename'] . '</a><br/>'
                . '';
            $content .= '<br/><b>Comments:</b>'
                . '<textarea name="' . $registration['id'] . '-comments" class="large">' . $registration['comments'] . '</textarea>'
                . '<br/>'
                . '<div class="adjudications-grade">'
                . '<b>Grade: </b> <input class="small text" type="text" name="' . $registration['id'] . '-grade" value="' . $registration['grade'] . '"/><br/>'
                . '</div>'
                . '<div class="adjudications-score">'
                . '<b>Score: </b> <input class="small text" type="text" name="' . $registration['id'] . '-score" value="' . $registration['score'] . '"/><br/>'
                . '</div>'
                . '</div>';
        }
        $content .= '<div class="submit wide">'
            . '<button class="submit" onclick="window.open(\'' . $args['base_url'] . '\');">Cancel</button> '
            . '<input class="submit" type="submit" value="Save"/>'
            . '</div>';
        $content .= '</form>'; 

        //
        // Check if this was a save
        //
        if( isset($comments_updated) && $comments_updated == 'yes' ) {
            header("Location: " . $args['base_url']);
            exit;
        }
        if( isset($comments_updated) && $comments_updated == 'error' ) {
            $blocks[] = array('type'=>'formmessage', 'level'=>'error', 'message'=>'Unable to save the comments, please try again or contact us for help');
        }
        $blocks[] = array('type'=>'content', 'class'=>'wide', 'html'=>$content);
    }

    if( $display == 'list' ) {
        //
        // Setup the open button and status
        //
        foreach($divisions as $division) {
            foreach($division['timeslots'] as $tid => $timeslot) {
                $num_completed = 0;
                foreach($timeslot['registrations'] as $rid => $registration) {
                    if( $registration['comments'] != '' && $registration['grade'] != '' && $registration['score'] != '' ) {
                        $num_completed++;
                    }
                }
                $division['timeslots'][$tid]['status'] = $num_completed . ' of ' . count($timeslot['registrations']);
                $division['timeslots'][$tid]['actions'] = "<a class='button' href='{$args['base_url']}/{$division['uuid']}/{$timeslot['permalink']}'>Open</a>";
            }
            $blocks[] = array('type'=>'table', 'title'=>$division['name'], 'section'=>'classes', 
                'columns'=>array(
                    array('label'=>'Name', 'field'=>'name', 'class'=>''),
                    array('label'=>'Completed', 'field'=>'status', 'class'=>'aligncenter'),
                    array('label'=>'', 'field'=>'actions', 'class'=>'aligncenter'),
                    ),
                'rows'=>$division['timeslots'],
                );
        }
    }

    return array('stat'=>'ok', 'blocks'=>$blocks);
}
?>
