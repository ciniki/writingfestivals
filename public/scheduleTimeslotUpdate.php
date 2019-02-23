<?php
//
// Description
// ===========
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_writingfestivals_scheduleTimeslotUpdate(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'scheduletimeslot_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Schedule Time Slot'),
        'festival_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Festival'),
        'sdivision_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Division'),
        'slot_time'=>array('required'=>'no', 'blank'=>'no', 'type'=>'time', 'name'=>'Time'),
        'class1_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Class 1'),
        'class2_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Class 2'),
        'class3_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Class 3'),
        'name'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Name'),
        'description'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Description'),
        'flags'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Options'),
        'registrations'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'idlist', 'name'=>'Registrations'),
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
    $rc = ciniki_writingfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.writingfestivals.scheduleTimeslotUpdate');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    
    //
    // Get the current list of registrations
    //
    $strsql = "SELECT registrations.id "
        . "FROM ciniki_writingfestival_registrations AS registrations "
        . "WHERE registrations.timeslot_id = '" . ciniki_core_dbQuote($ciniki, $args['scheduletimeslot_id']) . "' "
        . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQueryList');
    $rc = ciniki_core_dbQueryList($ciniki, $strsql, 'ciniki.writingfestivals', 'registrations', 'id');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $registrations = (isset($rc['registrations']) ? $rc['registrations'] : array());

    //
    // Start transaction
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.writingfestivals');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Update the Schedule Time Slot in the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.writingfestivals.scheduletimeslot', $args['scheduletimeslot_id'], $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.writingfestivals');
        return $rc;
    }

    //
    // Add any registrations
    //
    if( isset($args['registrations']) ) {
        foreach($args['registrations'] as $reg_id) {
            if( !in_array($reg_id, $registrations) ) {
                $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.writingfestivals.registration', $reg_id, array('timeslot_id'=>$args['scheduletimeslot_id']), 0x04);
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
            }
        }
    }

    //
    // Remove any registrations
    //
    if( isset($args['registrations']) && isset($registrations) ) {
        foreach($registrations as $reg_id) {
            if( !in_array($reg_id, $args['registrations']) ) {
                $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.writingfestivals.registration', $reg_id, array('timeslot_id'=>0), 0x04);
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
            }
        }
    }

    //
    // Commit the transaction
    //
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.writingfestivals');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Update the last_change date in the tenant modules
    // Ignore the result, as we don't want to stop user updates if this fails.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'updateModuleChangeDate');
    ciniki_tenants_updateModuleChangeDate($ciniki, $args['tnid'], 'ciniki', 'writingfestivals');

    //
    // Update the web index if enabled
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'hookExec');
    ciniki_core_hookExec($ciniki, $args['tnid'], 'ciniki', 'web', 'indexObject', array('object'=>'ciniki.writingfestivals.scheduleTimeslot', 'object_id'=>$args['scheduletimeslot_id']));

    return array('stat'=>'ok');
}
?>
