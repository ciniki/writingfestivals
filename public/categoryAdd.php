<?php
//
// Description
// -----------
// This method will add a new category for the tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to add the Category to.
//
// Returns
// -------
//
function ciniki_writingfestivals_categoryAdd(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'festival_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Festival'),
        'section_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Section'),
        'name'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Name'),
        'permalink'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Permalink'),
        'sequence'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Order'),
        'primary_image_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Image'),
        'synopsis'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Synopsis'),
        'description'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Description'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'writingfestivals', 'private', 'checkAccess');
    $rc = ciniki_writingfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.writingfestivals.categoryAdd');
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
        . "FROM ciniki_writingfestival_categories "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND permalink = '" . ciniki_core_dbQuote($ciniki, $args['permalink']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.writingfestivals', 'item');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( $rc['num_rows'] > 0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.30', 'msg'=>'You already have a category with that name, please choose another.'));
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
    // Add the category to the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'ciniki.writingfestivals.category', $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.writingfestivals');
        return $rc;
    }
    $category_id = $rc['id'];

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
    ciniki_core_hookExec($ciniki, $args['tnid'], 'ciniki', 'web', 'indexObject', array('object'=>'ciniki.writingfestivals.category', 'object_id'=>$category_id));

    return array('stat'=>'ok', 'id'=>$category_id);
}
?>
