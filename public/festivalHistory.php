<?php
//
// Description
// -----------
// This method will return the list of actions that were applied to an element of an festival.
// This method is typically used by the UI to display a list of changes that have occured
// on an element through time. This information can be used to revert elements to a previous value.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant to get the details for.
// festival_id:          The ID of the festival to get the history for.
// field:                   The field to get the history for.
//
// Returns
// -------
// <history>
// <action user_id="2" date="May 12, 2012 10:54 PM" value="Festival Name" age="2 months" user_display_name="Andrew" />
// ...
// </history>
//
function ciniki_writingfestivals_festivalHistory($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'festival_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Festival'),
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
    $rc = ciniki_writingfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.writingfestivals.festivalHistory');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    if( $args['field'] == 'start_date' || $args['field'] == 'end_date' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbGetModuleHistoryReformat');
        return ciniki_core_dbGetModuleHistoryReformat($ciniki, 'ciniki.writingfestival', 'ciniki_writingfestival_history', $args['tnid'], 'ciniki_writingfestivals', $args['festival_id'], $args['field'], 'date');
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbGetModuleHistory');
    return ciniki_core_dbGetModuleHistory($ciniki, 'ciniki.writingfestivals', 'ciniki_writingfestival_history', $args['tnid'], 'ciniki_writingfestivals', $args['festival_id'], $args['field']);
}
?>
