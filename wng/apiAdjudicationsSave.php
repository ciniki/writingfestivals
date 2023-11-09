<?php
//
// Description
// -----------
// Auto save the adjudications form
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_writingfestivals_wng_apiAdjudicationsSave(&$ciniki, $tnid, $request) {
    
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');

    //
    // Make sure customer is logged in
    //
    if( !isset($request['session']['customer']['id']) || $request['session']['customer']['id'] <= 0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.431', 'msg'=>'Not signed in'));
    }

    //
    // Make sure timeslot specified
    //
    if( !isset($_POST['f-timeslot_id']) || $_POST['f-timeslot_id'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.432', 'msg'=>'No timeslot specified'));
    }

    $customer_id = 0;
    if( isset($request['session']['customer']['id']) && $request['session']['customer']['id'] > 0 ) {
        $customer_id = $request['session']['customer']['id'];
    }

    //
    // Make sure same customer submitted as session
    //
    if( !isset($_POST['f-customer_id']) || $_POST['f-customer_id'] != $customer_id ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.433', 'msg'=>'Not signed in'));
    }

    //
    // Load the tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    $dt = new DateTime('now', new DateTimezone($intl_timezone));

    //
    // Load current festival
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'writingfestivals', 'private', 'loadCurrentFestival');
    $rc = ciniki_writingfestivals_loadCurrentFestival($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.434', 'msg'=>'', 'err'=>$rc['err']));
    }
    $festival = $rc['festival'];

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
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.435', 'msg'=>'Unable to load adjudicator', 'err'=>$rc['err']));
    }
    if( isset($rc['adjudicator']['id']) ) {
        $adjudicator = 'yes';
        $adjudicator_id = $rc['adjudicator']['id'];
    } else {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.436', 'msg'=>'Invalid adjudicator'));
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
        . "IFNULL(comments.id, 0) AS comment_id, "
        . "IFNULL(comments.comments, '') AS comments, "
        . "IFNULL(comments.grade, '') AS grade, "
        . "IFNULL(comments.score, '') AS score, "
        . "regclass.name AS reg_class_name "
        . "FROM ciniki_writingfestival_schedule_sections AS sections "
        . "INNER JOIN ciniki_writingfestival_schedule_divisions AS divisions ON ("
            . "sections.id = divisions.ssection_id " 
            . "AND divisions.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "INNER JOIN ciniki_writingfestival_schedule_timeslots AS timeslots ON ("
            . "divisions.id = timeslots.sdivision_id " 
            . "AND timeslots.id = '" . ciniki_core_dbQuote($ciniki, $_POST['f-timeslot_id']) . "' "
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
        . "ORDER BY section_name, divisions.division_date, division_id, slot_time, registrations.display_name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.writingfestivals', array(
/*        array('container'=>'divisions', 'fname'=>'division_uuid', 
            'fields'=>array('id'=>'division_id', 'uuid'=>'division_uuid', 
                'name'=>'division_name', 'date'=>'division_date_text', 'address',
                )),
        array('container'=>'timeslots', 'fname'=>'timeslot_uuid', 
            'fields'=>array('id'=>'timeslot_id', 'permalink'=>'timeslot_uuid', 'name'=>'timeslot_name', 'time'=>'slot_time_text', 
                'class1_id', 'class2_id', 'class3_id', 'description', 'class1_name', 'class2_name', 'class3_name',
                )), */
        array('container'=>'registrations', 'fname'=>'reg_uuid', 
            'fields'=>array('id'=>'reg_id', 'uuid'=>'reg_uuid', 'name'=>'display_name', 'public_name', 'title',
                'class_name'=>'reg_class_name', 'comment_id', 'comments', 'grade', 'score')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $registrations = isset($rc['registrations']) ? $rc['registrations'] : array();

    //
    // Update the comments for the adjudicator and registrations
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'writingfestivals', 'wng', 'adjudicatorCommentsUpdate');
    $rc = ciniki_writingfestivals_wng_adjudicatorCommentsUpdate($ciniki, $tnid, $request, array(
        'registrations' => $registrations,
        'adjudicator_id' => $adjudicator_id,
        'autosave' => 'yes',
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.452', 'msg'=>'Unable to update comments', 'err'=>$rc['err']));
    }

    return array('stat'=>'ok', 'last_saved'=>$dt->format('M j, Y g:i A'));
}
?>
