<?php
//
// Description
// -----------
// This function will check for competitors in the writing festivals
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_writingfestivals_wng_accountAdjudicationsProcess(&$ciniki, $tnid, &$request, $args) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'wng', 'private', 'videoProcess');

    $blocks = array();

    $settings = isset($request['site']['settings']) ? $request['site']['settings'] : array();
    $base_url = $request['ssl_domain_base_url'] . '/account/writingfestivaladjudications';
    $display = 'list';

    if( isset($_POST['submit']) && $_POST['submit'] == 'Back' ) {
        header("Location: {$request['ssl_domain_base_url']}/account/writingfestivaladjudications");
        return array('stat'=>'exit');
    }

    //
    // Load current festival
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'writingfestivals', 'private', 'loadCurrentFestival');
    $rc = ciniki_writingfestivals_loadCurrentFestival($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.395', 'msg'=>'', 'err'=>$rc['err']));
    }
    $festival = $rc['festival'];

    //
    // Load the festival details
    //
    $strsql = "SELECT detail_key, detail_value "
        . "FROM ciniki_writingfestival_settings "
        . "WHERE ciniki_writingfestival_settings.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND ciniki_writingfestival_settings.festival_id = '" . ciniki_core_dbQuote($ciniki, $festival['id']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQueryList2');
    $rc = ciniki_core_dbQueryList2($ciniki, $strsql, 'ciniki.writingfestivals', 'settings');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.396', 'msg'=>'Unable to load settings', 'err'=>$rc['err']));
    }
    if( isset($rc['settings']) ) {
        foreach($rc['settings'] as $k => $v) {
            $festival[$k] = $v;
        }
    }

    //
    // Load the adjudicator
    //
    $strsql = "SELECT id "  
        . "FROM ciniki_writingfestival_adjudicators "
        . "WHERE festival_id = '" . ciniki_core_dbQuote($ciniki, $festival['id']) . "' "
        . "AND customer_id = '" . ciniki_core_dbQuote($ciniki, $request['session']['customer']['id']) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.writingfestivals', 'adjudicator');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.397', 'msg'=>'Unable to load adjudicator', 'err'=>$rc['err']));
    }
    if( isset($rc['adjudicator']['id']) ) {
        $adjudicator = 'yes';
        $adjudicator_id = $rc['adjudicator']['id'];
    }

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
        . "regclass.flags AS reg_class_flags, "
        . "regclass.name AS reg_class_name "
        . "FROM ciniki_writingfestival_schedule_sections AS sections "
        . "INNER JOIN ciniki_writingfestival_schedule_divisions AS divisions ON ("
            . "sections.id = divisions.ssection_id " 
            . "AND divisions.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "INNER JOIN ciniki_writingfestival_schedule_timeslots AS timeslots ON ("
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
//            . "timeslots.id = registrations.timeslot_id "
            . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_writingfestival_comments AS comments ON ("
            . "registrations.id = comments.registration_id "
            . "AND comments.adjudicator_id = '" . ciniki_core_dbQuote($ciniki, $adjudicator_id) . "' "
            . "AND comments.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_writingfestival_classes AS regclass ON ("
            . "registrations.class_id = regclass.id "
            . "AND regclass.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE sections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND sections.festival_id = '" . ciniki_core_dbQuote($ciniki, $festival['id']) . "' "
        . "AND ("
            . "sections.adjudicator1_id = '" . ciniki_core_dbQuote($ciniki, $adjudicator_id) . "' "
            . "OR sections.adjudicator2_id = '" . ciniki_core_dbQuote($ciniki, $adjudicator_id) . "' "
            . "OR sections.adjudicator3_id = '" . ciniki_core_dbQuote($ciniki, $adjudicator_id) . "' "
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
            'fields'=>array('id'=>'reg_id', 'uuid'=>'reg_uuid', 'name'=>'display_name', 'public_name', 'title', 'pdf_filename',
                'reg_class_flags', 'class_name'=>'reg_class_name', 'comment_id', 'comments', 'grade', 'score', 
//                'placement',
                )),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $divisions = isset($rc['divisions']) ? $rc['divisions'] : array();


    /* Check for division and timeslot */
    if( isset($request['uri_split'][($request['cur_uri_pos']+3)]) 
        && isset($divisions[$request['uri_split'][($request['cur_uri_pos']+2)]]['timeslots'][$request['uri_split'][($request['cur_uri_pos']+3)]])
        ) {
        $timeslot = $divisions[$request['uri_split'][($request['cur_uri_pos']+2)]]['timeslots'][$request['uri_split'][($request['cur_uri_pos']+3)]];
        $display = 'timeslot';

        //
        // Check for form submit
        //
        if( isset($_POST['action']) && $_POST['action'] == 'submit' ) {
            //
            // Update the comments for the adjudicator and registrations
            //
            ciniki_core_loadMethod($ciniki, 'ciniki', 'writingfestivals', 'wng', 'adjudicatorCommentsUpdate');
            $rc = ciniki_writingfestivals_wng_adjudicatorCommentsUpdate($ciniki, $tnid, $request, array(
                'registrations' => $timeslot['registrations'],
                'adjudicator_id' => $adjudicator_id,
                ));
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.394', 'msg'=>'Unable to update comments', 'err'=>$rc['err']));
            }

            header("Location: {$request['ssl_domain_base_url']}/account/writingfestivaladjudications");
            $request['session']['account-writingfestivals-adjudications-saved'] = 'yes';
            return array('stat'=>'exit');
        }

        //
        // Check for download
        //
        if( isset($request['uri_split'][($request['cur_uri_pos']+5)]) 
            && isset($timeslot['registrations'][$request['uri_split'][($request['cur_uri_pos']+4)]])
//            && $request['uri_split'][($request['cur_uri_pos']+5)] == $timeslot['registrations'][$request['uri_split'][($request['cur_uri_pos']+4)]]['pdf_filename']
            ) {
            $registration = $timeslot['registrations'][$request['uri_split'][($request['cur_uri_pos']+4)]];
error_log("TEST");
            error_log(print_r($registration,true));
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
                $blocks[] = array(
                    'type' => 'msg',
                    'level' => 'error',
                    'content' => 'File not found',
                    );
                return array('stat'=>'ok', 'blocks'=>$blocks);
            }

            header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); 
            header("Last-Modified: " . gmdate("D,d M YH:i:s") . " GMT"); 
            header('Cache-Control: no-cache, must-revalidate');
            header('Pragma: no-cache');
            // Set mime header
            $finfo = finfo_open(FILEINFO_MIME);
            if( $finfo ) { 
                header('Content-Type: ' . finfo_file($finfo, $storage_filename)); 
            }
            // Open file in browser
            header('Content-Disposition: inline;filename="' . $registration['pdf_filename'] . '"');
            // Download file to filesystem
            header('Content-Length: ' . filesize($storage_filename));
            header('Cache-Control: max-age=0');

            $fp = fopen($storage_filename, 'rb');
            fpassthru($fp);

            return array('stat'=>'exit');
        }
    }


/*    $blocks[] = array(
        'type' => 'html',
        'html' => '<pre>' . print_r($divisions, true) . '</pre>',
        ); */


    //
    // Prepare any errors
    //
    $form_errors = '';
    if( isset($errors) && count($errors) > 0 ) {
        foreach($errors as $err) {
            $form_errors .= ($form_errors != '' ? '<br/>' : '') . $err['msg'];
        }
    }

    if( $form_errors != '' ) { 
        $blocks[] = array(
            'type' => 'msg',
            'level' => 'error',
            'content' => $form_errors,
            );
    }
    
    if( isset($request['session']['account-writingfestivals-adjudications-saved']) ){
        $blocks[] = array(
            'type' => 'msg',
            'level' => 'success',
            'content' => 'Comments saved',
            );
        unset($request['session']['account-writingfestivals-adjudications-saved']);
    }

    if( $display == 'timeslot' ) {
        $sections = array();
        foreach($timeslot['registrations'] as $registration) {
            $section = array(    
                'id' => 'section-' . $registration['id'],
                'label' => $registration['name'] . ' - ' . $registration['class_name'],
                'fields' => array(),
                );
            $num_titles = 1;
            if( ($registration['reg_class_flags']&0x4000) == 0x4000 ) {
                $num_titles = 3;
            } elseif( ($registration['reg_class_flags']&0x1000) == 0x1000 ) {
                $num_titles = 2;
            }
            if( $registration["title"] != '' ) {
                $section['fields']["title"] = array(
                    'id' => "title",
                    'ftype' => 'content',
//                        'ftype' => 'text',
                    'label' => 'Title',
                    'editable' => 'no',
                    'size' => 'medium',
                    'description' => $registration["title"],
                    );
                if( $registration["pdf_filename"] != '' ) {
                    $download_url = "{$request['ssl_domain_base_url']}/account/writingfestivaladjudications"
                        . '/' . $request['uri_split'][($request['cur_uri_pos']+2)]
                        . '/' . $request['uri_split'][($request['cur_uri_pos']+3)]
                        . '/' . $registration['uuid']
                        . '/' . $registration['pdf_filename'] . '.pdf'
                        . "";
                    $section['fields']["pdf_filename"] = array(
                        'id' => "pdf_filename",
                        'ftype' => 'button',
                        'label' => 'PDF File',
                        'size' => 'medium',
                        'target' => '_blank',
                        'href' => $download_url,
                        'value' => $registration["pdf_filename"],
                        );
                } else {
                    $section['fields']["pdf_filename"] = array(
                        'id' => "pdf_filename",
                        'ftype' => 'content',
                        'label' => 'PDF File',
                        'size' => 'medium',
                        'description' => "No file provided",
                        );
                }
            }
            $section['fields']["{$registration['id']}-comments"] = array(
                'id' => "{$registration['id']}-comments",
                'ftype' => 'textarea',
                'label' => 'Comments',
                'onkeyup' => 'fieldUpdated()',
                'size' => 'large',
                'value' => $registration['comments'],
                );
            $section['fields']["{$registration['id']}-score"] = array(
                'id' => "{$registration['id']}-score",
                'ftype' => 'text',
                'onkeyup' => 'fieldUpdated()',
                'size' => 'small',
                'label' => 'Mark',
                'value' => $registration['score'],
                );
            if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.writingfestivals', 0x04) ) {
/*                $section['fields']["{$registration['id']}-placement"] = array(
                    'id' => "{$registration['id']}-placement",
                    'ftype' => 'text',
                    'onkeyup' => 'fieldUpdated()',
                    'size' => 'small',
                    'label' => 'Placement',
                    'value' => $registration['placement'],
                    ); */
            }
            $sections[$registration['id']] = $section;
        }
        $sections['submit'] = array(
            'id' => 'submit',
            'class' => 'buttons',
            'label' => '',
            'fields' => array(
                'timeslot_id' => array(
                    'id' => 'timeslot_id',
                    'ftype' => 'hidden',
                    'value' => $timeslot['id'],
                    ),
                'customer_id' => array(
                    'id' => 'customer_id',
                    'ftype' => 'hidden',
                    'value' => $request['session']['customer']['id'],
                    ),
                'cancel' => array(
                    'id' => 'cancel',
                    'ftype' => 'cancel',
                    'label' => 'Back',
                    ),
                'submit' => array(
                    'id' => 'submit',
                    'ftype' => 'submit',
                    'label' => 'Save',
                    ),
                ),
            );
        $js = ""
            . "var fSaveTimer=null;"
            . "function fieldUpdated(){"
                . "if(fSaveTimer==null){"
                    . "fSaveTimer=setTimeout(fSave, 10000);"
                . "}"
            . "}"
            . "function fSave(){"
                . "clearTimeout(fSaveTimer);"
                . "fSaveTimer=null;"
                . "C.form.qSave();"
            . "}"
            . "";
        $blocks[] = array(
            'type' => 'form',
            'id' => 'adjudication-form',
            'title' => $timeslot['name'],
            'class' => 'limit-width limit-width-60 writingfestival-adjudications',
            'form-sections' => $sections,
            'js' => $js,
            'api-save-url' => $request['api_url'] . '/ciniki/writingfestivals/adjudicationsSave',
            'last-saved-msg' => '',
            'api-args' => array(
                ),
            );
//        $blocks[] = array(
//            'type' => 'html',
//            'html' => '<pre>' . print_r($timeslot, true) . '</pre>',
//            );

    } else {
        //
        // Setup the open button and status
        //
        foreach($divisions as $division) {
            if( isset($division['timeslots']) && count($division['timeslots']) > 0 ) {
                foreach($division['timeslots'] as $tid => $timeslot) {
                    $num_completed = 0;
                    if( isset($timeslot['registrations']) ) {
                        foreach($timeslot['registrations'] as $rid => $registration) {
                            if( $registration['comments'] != '' && $registration['score'] != '' ) {
                                $num_completed++;
                            }
                        }
                    } else {
                        $timeslot['registrations'] = array();
                    }
                    $division['timeslots'][$tid]['status'] = $num_completed . ' of ' . count($timeslot['registrations']);
                    $division['timeslots'][$tid]['actions'] = "<a class='button' href='{$base_url}/{$division['uuid']}/{$timeslot['permalink']}'>Open</a>";
                }

                $blocks[] = array(
                    'type' => 'table', 
                    'title' => $division['name'], 
                    'section' => 'writingfestival-adjudications limit-width limit-width-60', 
                    'columns' => array(
                        array('label'=>'Name', 'field'=>'name', 'class'=>''),
                        array('label'=>'Completed', 'field'=>'status', 'class'=>'aligncenter'),
                        array('label'=>'', 'field'=>'actions', 'class'=>'aligncenter'),
                        ),
                    'rows'=>$division['timeslots'],
                    );
            }
        }
    }

    return array('stat'=>'ok', 'blocks'=>$blocks);
}
?>
