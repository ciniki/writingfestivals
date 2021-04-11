<?php
//
// Description
// ===========
//
// Arguments
// ---------
// api_key:
// auth_token:
//
// Returns
// -------
//
function ciniki_writingfestivals_scheduleTimeslotCommentsUpdate($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'festival_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Festival'),
        'timeslot_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Schedule Time Slot'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'writingfestivals', 'private', 'checkAccess');
    $rc = ciniki_writingfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.writingfestivals.scheduleTimeslotCommentsUpdate');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
    $intl_currency = $rc['settings']['intl-default-currency'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');

    //
    // Get the adjudicators for the timeslot
    //
    $strsql = "SELECT adjudicators.id, adjudicators.customer_id "
        . "FROM ciniki_writingfestival_schedule_timeslots AS timeslots "
        . "LEFT JOIN ciniki_writingfestival_schedule_divisions AS divisions ON ("
            . "timeslots.sdivision_id = divisions.id "
            . "AND divisions.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "LEFT JOIN ciniki_writingfestival_schedule_sections AS sections ON ("
            . "divisions.ssection_id = sections.id "
            . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "LEFT JOIN ciniki_writingfestival_adjudicators AS adjudicators ON ("
            . "(sections.adjudicator1_id = adjudicators.id "
                . "OR sections.adjudicator2_id = adjudicators.id "
                . "OR sections.adjudicator3_id = adjudicators.id "
                . ") "
            . "AND adjudicators.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE timeslots.id = '" . ciniki_core_dbQuote($ciniki, $args['timeslot_id']) . "' "
        . "AND timeslots.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.writingfestivals', array(
        array('container'=>'adjudicators', 'fname'=>'id', 
            'fields'=>array('id', 'customer_id'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.166', 'msg'=>'Unable to load adjudicators', 'err'=>$rc['err']));
    }
    $adjudicators = isset($rc['adjudicators']) ? $rc['adjudicators'] : array();

    //
    // Get the registrations for the timeslot
    //
    $strsql = "SELECT "
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
        . "timeslots.description, "
        . "registrations.id AS reg_id, "
        . "registrations.uuid AS reg_uuid, "
        . "registrations.display_name, "
        . "registrations.public_name, "
        . "registrations.title, "
        . "registrations.pdf_filename, "
        . "IFNULL(comments.adjudicator_id, 0) AS adjudicator_id, "
        . "IFNULL(comments.id, 0) AS comment_id, "
        . "IFNULL(comments.comments, '') AS comments, "
        . "IFNULL(comments.grade, '') AS grade, "
        . "IFNULL(comments.score, '') AS score, "
        . "regclass.name AS reg_class_name "
        . "FROM ciniki_writingfestival_schedule_timeslots AS timeslots "
        . "LEFT JOIN ciniki_writingfestival_classes AS class1 ON ("
            . "timeslots.class1_id = class1.id " 
            . "AND class1.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "LEFT JOIN ciniki_writingfestival_classes AS class2 ON ("
            . "timeslots.class3_id = class2.id " 
            . "AND class2.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "LEFT JOIN ciniki_writingfestival_classes AS class3 ON ("
            . "timeslots.class3_id = class3.id " 
            . "AND class3.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "LEFT JOIN ciniki_writingfestival_registrations AS registrations ON ("
            . "(timeslots.class1_id = registrations.class_id "  
                . "OR timeslots.class2_id = registrations.class_id "
                . "OR timeslots.class3_id = registrations.class_id "
                . ") "
            . "AND ((timeslots.flags&0x01) = 0 OR timeslots.id = registrations.timeslot_id) "
            . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "LEFT JOIN ciniki_writingfestival_comments AS comments ON ("
            . "registrations.id = comments.registration_id "
            . "AND comments.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "LEFT JOIN ciniki_writingfestival_classes AS regclass ON ("
            . "registrations.class_id = regclass.id "
            . "AND regclass.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE timeslots.id = '" . ciniki_core_dbQuote($ciniki, $args['timeslot_id']) . "' "
            . "AND timeslots.class1_id > 0 "
            . "AND timeslots.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "ORDER BY slot_time, registrations.display_name, comments.adjudicator_id "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.writingfestivals', array(
        array('container'=>'timeslots', 'fname'=>'timeslot_id', 
            'fields'=>array('id'=>'timeslot_id', 'permalink'=>'timeslot_uuid', 'name'=>'timeslot_name', 'time'=>'slot_time_text', 
                'class1_id', 'class2_id', 'class3_id', 'description', 'class1_name', 'class2_name', 'class3_name',
                )),
        array('container'=>'registrations', 'fname'=>'reg_id', 
            'fields'=>array('id'=>'reg_id', 'uuid'=>'reg_uuid', 'public_name', 'title', 
                'pdf_filename', 'reg_class_name',
                )),
        array('container'=>'comments', 'fname'=>'adjudicator_id', 
            'fields'=>array('id'=>'adjudicator_id', 'comment_id', 'comments', 'grade', 'score')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $timeslot = isset($rc['timeslots'][$args['timeslot_id']]) ? $rc['timeslots'][$args['timeslot_id']] : array();

    if( isset($timeslot['registrations']) ) {
        foreach($timeslot['registrations'] as $rid => $registration) {
            foreach($adjudicators as $aid => $adjudicator) {
                
                //
                // Check if comments exist
                //
                if( isset($registration['comments'][$adjudicator['id']]) ) {
                    $existing_comments = $registration['comments'][$adjudicator['id']];
                    $update_args = array();
                    if( isset($ciniki['request']['args']['comments_' . $registration['id'] . '_' . $adjudicator['id']])
                        && $ciniki['request']['args']['comments_' . $registration['id'] . '_' . $adjudicator['id']] != $existing_comments['comments'] 
                        ) {
                        $update_args['comments'] = $ciniki['request']['args']['comments_' . $registration['id'] . '_' . $adjudicator['id']];
                    }
                    if( isset($ciniki['request']['args']['grade_' . $registration['id'] . '_' . $adjudicator['id']])
                        && $ciniki['request']['args']['grade_' . $registration['id'] . '_' . $adjudicator['id']] != $existing_comments['grade'] 
                        ) {
                        $update_args['grade'] = $ciniki['request']['args']['grade_' . $registration['id'] . '_' . $adjudicator['id']];
                    }
                    if( isset($ciniki['request']['args']['score_' . $registration['id'] . '_' . $adjudicator['id']])
                        && $ciniki['request']['args']['score_' . $registration['id'] . '_' . $adjudicator['id']] != $existing_comments['score'] 
                        ) {
                        $update_args['score'] = $ciniki['request']['args']['score_' . $registration['id'] . '_' . $adjudicator['id']];
                    }
                    if( count($update_args) > 0 ) {
                        //
                        // Update the comments for the adjudicator
                        //
                        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
                        $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.writingfestivals.comment', $existing_comments['comment_id'], $update_args, 0x04);
                        if( $rc['stat'] != 'ok' ) {
                            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.167', 'msg'=>'Unable to update the comment'));
                        }
                    }
                }
                //
                // Add comments if not exist
                //
                else {
                    $update_args = array();
                    if( (isset($ciniki['request']['args']['comments_' . $registration['id'] . '_' . $adjudicator['id']])
                        && $ciniki['request']['args']['comments_' . $registration['id'] . '_' . $adjudicator['id']] != '')
                        ) {
                        $update_args['comments'] = $ciniki['request']['args']['comments_' . $registration['id'] . '_' . $adjudicator['id']];
                    }
                    if( (isset($ciniki['request']['args']['grade_' . $registration['id'] . '_' . $adjudicator['id']])
                        && $ciniki['request']['args']['grade_' . $registration['id'] . '_' . $adjudicator['id']] != '')
                        ) {
                        $update_args['grade'] = $ciniki['request']['args']['grade_' . $registration['id'] . '_' . $adjudicator['id']];
                    }
                    if( (isset($ciniki['request']['args']['score_' . $registration['id'] . '_' . $adjudicator['id']])
                        && $ciniki['request']['args']['score_' . $registration['id'] . '_' . $adjudicator['id']] != '')
                        ) {
                        $update_args['score'] = $ciniki['request']['args']['score_' . $registration['id'] . '_' . $adjudicator['id']];
                    }
                    if( count($update_args) > 0 ) {
                        //
                        // Add the comments for the adjudicator
                        //
                        $update_args['registration_id'] = $registration['id'];
                        $update_args['adjudicator_id'] = $adjudicator['id'];
                        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
                        $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'ciniki.writingfestivals.comment', $update_args, 0x04);
                        if( $rc['stat'] != 'ok' ) {
                            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.168', 'msg'=>'Unable to add the comment'));
                        }
                    }
                }
            }
        }
    }

    return array('stat'=>'ok');
}
?>
