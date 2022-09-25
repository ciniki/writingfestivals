<?php
//
// Description
// -----------
// This method will add a new festival for the tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to add the Festival to.
//
// Returns
// -------
// <rsp stat="ok" id="42">
//
function ciniki_writingfestivals_festivalAdd(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'name'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Name'),
        'permalink'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Permalink'),
        'start_date'=>array('required'=>'yes', 'blank'=>'no', 'type'=>'date', 'name'=>'Start'),
        'end_date'=>array('required'=>'yes', 'blank'=>'no', 'type'=>'date', 'name'=>'End'),
        'status'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Status'),
        'flags'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Options'),
        'earlybird_date'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'datetimetoutc', 'name'=>'Earlybird End Date'),
        'live_date'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'datetimetoutc', 'name'=>'Live End Date'),
        'virtual_date'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'datetimetoutc', 'name'=>'Virtual End Date'),
        'primary_image_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Primary Image'),
        'description'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Description'),
        'document_logo_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Document Header Logo'),
        'document_header_msg'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Document Header Message'),
        'document_footer_msg'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Document Footer Message'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'writingfestivals', 'private', 'checkAccess');
    $rc = ciniki_writingfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.writingfestivals.festivalAdd');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Setup permalink
    //
    if( !isset($args['permalink']) || $args['permalink'] == '' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'makePermalink');
        $args['permalink'] = ciniki_core_makePermalink($ciniki, $args['name']);
    }

    //
    // Make sure the permalink is unique
    //
    $strsql = "SELECT id, name, permalink "
        . "FROM ciniki_writingfestivals "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND permalink = '" . ciniki_core_dbQuote($ciniki, $args['permalink']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.writingfestivals', 'item');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( $rc['num_rows'] > 0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.4', 'msg'=>'You already have a festival with that name, please choose another.'));
    }

    //
    // Start transaction
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.writingfestivals');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Add the festival to the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'ciniki.writingfestivals.festival', $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.writingfestivals');
        return $rc;
    }
    $festival_id = $rc['id'];

    //
    // Update the Festival settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'writingfestivals', 'private', 'settingsUpdate');
    $rc = ciniki_writingfestivals_settingsUpdate($ciniki, $args['tnid'], $festival_id, $ciniki['request']['args']);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.writingfestivals');
        return $rc;
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
    ciniki_core_hookExec($ciniki, $args['tnid'], 'ciniki', 'web', 'indexObject', array('object'=>'ciniki.writingfestivals.festival', 'object_id'=>$festival_id));

    return array('stat'=>'ok', 'id'=>$festival_id);
}
?>
