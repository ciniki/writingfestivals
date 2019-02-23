<?php
//
// Description
// ===========
// This method will return all the information about an schedule time slot.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the schedule time slot is attached to.
// scheduletimeslot_id:          The ID of the schedule time slot to get the details for.
//
// Returns
// -------
//
function ciniki_writingfestivals_scheduleTimeslotGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'festival_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Festival'),
        'scheduletimeslot_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Schedule Time Slot'),
        'sdivision_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Schedule Division'),
        'class1_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Class'),
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
    $rc = ciniki_writingfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.writingfestivals.scheduleTimeslotGet');
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
    // Return default for new Schedule Time Slot
    //
    if( $args['scheduletimeslot_id'] == 0 ) {
        $scheduletimeslot = array('id'=>0,
            'festival_id'=>'',
            'sdivision_id'=>(isset($args['sdivision_id']) ? $args['sdivision_id'] : 0),
            'slot_time'=>'',
            'class1_id'=>(isset($args['class1_id']) ? $args['class1_id'] : 0),
            'name'=>'',
            'description'=>'',
        );
    }

    //
    // Get the details for an existing Schedule Time Slot
    //
    else {
        $strsql = "SELECT timeslots.id, "
            . "timeslots.festival_id, "
            . "timeslots.sdivision_id, "
            . "TIME_FORMAT(timeslots.slot_time, '%h:%i %p') AS slot_time, "
            . "timeslots.class1_id, "
            . "timeslots.class2_id, "
            . "timeslots.class3_id, "
            . "timeslots.flags, "
            . "timeslots.name, "
            . "timeslots.description, "
            . "IFNULL(registrations.id, '') AS registrations "
            . "FROM ciniki_writingfestival_schedule_timeslots AS timeslots "
            . "LEFT JOIN ciniki_writingfestival_registrations AS registrations ON ("
                . "timeslots.id = registrations.timeslot_id "
                . "AND timeslots.festival_id = registrations.festival_id "
                . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "WHERE timeslots.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND timeslots.id = '" . ciniki_core_dbQuote($ciniki, $args['scheduletimeslot_id']) . "' "
            . "";
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.writingfestivals', array(
            array('container'=>'scheduletimeslot', 'fname'=>'id', 
                'fields'=>array('festival_id', 'sdivision_id', 'slot_time', 'class1_id', 'class2_id', 'class3_id', 'flags', 'name', 'description', 'registrations'),
                'idlists'=>array('registrations'),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.98', 'msg'=>'Schedule Time Slot not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['scheduletimeslot'][0]) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.99', 'msg'=>'Unable to find Schedule Time Slot'));
        }
        $scheduletimeslot = $rc['scheduletimeslot'][0];
    }

    $rsp = array('stat'=>'ok', 'scheduletimeslot'=>$scheduletimeslot);

    //
    // Get the list of divisions
    //
    $strsql = "SELECT divisions.id, CONCAT_WS(' - ', sections.name, divisions.name) AS name "
        . "FROM ciniki_writingfestival_schedule_sections AS sections, ciniki_writingfestival_schedule_divisions AS divisions "
        . "WHERE sections.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND sections.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
        . "AND sections.id = divisions.ssection_id "
        . "AND divisions.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND divisions.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
        . "ORDER BY sections.name, divisions.name "
        . "";
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.writingfestivals', array(
        array('container'=>'divisions', 'fname'=>'id', 'fields'=>array('id', 'name')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.118', 'msg'=>'Schedule Division not found', 'err'=>$rc['err']));
    }
    if( isset($rc['divisions']) ) {
        $rsp['scheduledivisions'] = $rc['divisions'];
    }

    //
    // Get the list of classes
    //
    $strsql = "SELECT classes.id, "
        . "CONCAT_WS(' - ', classes.code, classes.name) AS name, "
        . "FORMAT(classes.fee, 2) AS fee, "
        . "registrations.id AS registration_id, "
        . "registrations.display_name, "
        . "IFNULL(TIME_FORMAT(rtimeslots.slot_time, '%h:%i %p'), '') AS regtime "
//        . "COUNT(registrations.id) AS num_registrations "
        . "FROM ciniki_writingfestival_sections AS sections "
        . "LEFT JOIN ciniki_writingfestival_categories AS categories ON ("
            . "sections.id = categories.section_id "
            . "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "LEFT JOIN ciniki_writingfestival_classes AS classes ON ("
            . "categories.id = classes.category_id "
            . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "LEFT JOIN ciniki_writingfestival_registrations AS registrations ON ("
            . "classes.id = registrations.class_id "
            . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "LEFT JOIN ciniki_writingfestival_schedule_timeslots AS rtimeslots ON ("
            . "registrations.timeslot_id = rtimeslots.id "
            . "AND rtimeslots.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE sections.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
        . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
//        . "GROUP BY classes.id "
//        . "ORDER BY num_registrations DESC, sections.name, classes.code "
        . "ORDER BY classes.id, sections.name, classes.code "
        . "";
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.writingfestivals', array(
        array('container'=>'classes', 'fname'=>'id', 'fields'=>array('id', 'name', 'fee')),
        array('container'=>'registrations', 'fname'=>'registration_id', 'fields'=>array('id'=>'registration_id', 'name'=>'display_name', 'time'=>'regtime')),
        ));
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }
    if( isset($rc['classes']) ) {
        $rsp['classes'] = $rc['classes'];
        foreach($rsp['classes'] as $cid => $class) {
            $rsp['classes'][$cid]['num_registrations'] = (isset($class['registrations']) ? count($class['registrations']) : 0);
            if( $rsp['classes'][$cid]['num_registrations'] > 0 ) {
                $rsp['classes'][$cid]['name'] .= ' (' . $rsp['classes'][$cid]['num_registrations'] . ')';
            }
            if( isset($class['registrations']) ) {
                foreach($class['registrations'] as $rid => $reg) {
                    if( $reg['time'] != '' ) {
                        $rsp['classes'][$cid]['registrations'][$rid]['name'] .= ' (' . $reg['time'] . ')';
                    }
                }
            }
        }
        usort($rsp['classes'], function($a, $b) {
            if( $a['num_registrations'] == $b['num_registrations'] ) {
                return strcasecmp($a['name'], $b['name']);
            }
            return ($a['num_registrations'] > $b['num_registrations'] ? -1 : 1);
        });
    }

    return $rsp;
}
?>
