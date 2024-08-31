<?php
//
// Description
// ===========
// This method will return all the information about an schedule division.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the schedule division is attached to.
// scheduledivision_id:          The ID of the schedule division to get the details for.
//
// Returns
// -------
//
function ciniki_writingfestivals_scheduleDivisionGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'festival_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Festival'),
        'scheduledivision_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Schedule Division'),
        'ssection_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Schedule Section'),
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
    $rc = ciniki_writingfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.writingfestivals.scheduleDivisionGet');
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

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');

    //
    // Return default for new Schedule Division
    //
    if( $args['scheduledivision_id'] == 0 ) {
        $scheduledivision = array('id'=>0,
            'festival_id'=>'',
            'ssection_id'=>(isset($args['ssection_id']) ? $args['ssection_id'] : 0),
            'name'=>'',
            'division_date'=>'',
            'address'=>'',
        );
    }

    //
    // Get the details for an existing Schedule Division
    //
    else {
        $strsql = "SELECT ciniki_writingfestival_schedule_divisions.id, "
            . "ciniki_writingfestival_schedule_divisions.festival_id, "
            . "ciniki_writingfestival_schedule_divisions.ssection_id, "
            . "ciniki_writingfestival_schedule_divisions.name, "
            . "ciniki_writingfestival_schedule_divisions.division_date, "
            . "ciniki_writingfestival_schedule_divisions.address "
            . "FROM ciniki_writingfestival_schedule_divisions "
            . "WHERE ciniki_writingfestival_schedule_divisions.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_writingfestival_schedule_divisions.id = '" . ciniki_core_dbQuote($ciniki, $args['scheduledivision_id']) . "' "
            . "";
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.writingfestivals', array(
            array('container'=>'scheduledivisions', 'fname'=>'id', 
                'fields'=>array('festival_id', 'ssection_id', 'name', 'division_date', 'address'),
                'utctotz'=>array('division_date'=>array('timezone'=>'UTC', 'format'=>$date_format)),                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.93', 'msg'=>'Schedule Division not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['scheduledivisions'][0]) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.94', 'msg'=>'Unable to find Schedule Division'));
        }
        $scheduledivision = $rc['scheduledivisions'][0];
    }

    $rsp = array('stat'=>'ok', 'scheduledivision'=>$scheduledivision);

    //
    // Get the list of sections
    //
    $strsql = "SELECT id, name "
        . "FROM ciniki_writingfestival_schedule_sections "
        . "WHERE ciniki_writingfestival_schedule_sections.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND ciniki_writingfestival_schedule_sections.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
        . "ORDER BY name "
        . "";
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.writingfestivals', array(
        array('container'=>'sections', 'fname'=>'id', 'fields'=>array('id', 'name')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.100', 'msg'=>'Schedule Division not found', 'err'=>$rc['err']));
    }
    if( isset($rc['sections']) ) {
        $rsp['schedulesections'] = $rc['sections'];
    }

    return $rsp;
}
?>
