<?php
//
// Description
// -----------
// This method will return the list of actions that were applied to an element of an registration.
// This method is typically used by the UI to display a list of changes that have occured
// on an element through time. This information can be used to revert elements to a previous value.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant to get the details for.
// registration_id:          The ID of the registration to get the history for.
// field:                   The field to get the history for.
//
// Returns
// -------
//
function ciniki_writingfestivals_registrationHistory($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'registration_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Registration'),
        'field'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'field'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner, or sys admin
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'writingfestivals', 'private', 'checkAccess');
    $rc = ciniki_writingfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.writingfestivals.registrationHistory');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    if( $args['field'] == 'fee' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbGetModuleHistoryReformat');
        return ciniki_core_dbGetModuleHistoryReformat($ciniki, 'ciniki.writingfestivals', 'ciniki_writingfestival_history', $args['tnid'], 'ciniki_writingfestival_registrations', $args['registration_id'], $args['field'], 'currency');
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbGetModuleHistory');
    return ciniki_core_dbGetModuleHistory($ciniki, 'ciniki.writingfestivals', 'ciniki_writingfestival_history', $args['tnid'], 'ciniki_writingfestival_registrations', $args['registration_id'], $args['field']);
}
?>
