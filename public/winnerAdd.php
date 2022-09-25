<?php
//
// Description
// -----------
// This method will add a new winner for the tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to add the Winner to.
//
// Returns
// -------
//
function ciniki_writingfestivals_winnerAdd(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'festival_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Festival'),
        'category'=>array('required'=>'yes', 'blank'=>'no', 'trim'=>'yes', 'name'=>'Category'),
        'award'=>array('required'=>'yes', 'blank'=>'no', 'trim'=>'yes', 'name'=>'Award'),
        'sequence'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Order'),
        'title'=>array('required'=>'no', 'blank'=>'yes', 'trim'=>'yes', 'name'=>'Title'),
        'author'=>array('required'=>'no', 'blank'=>'yes', 'trim'=>'yes', 'name'=>'Author'),
        'image_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Image'),
        'synopsis'=>array('required'=>'no', 'blank'=>'yes', 'trim'=>'yes', 'name'=>'Synopsis'),
        'intro'=>array('required'=>'no', 'blank'=>'yes', 'trim'=>'yes', 'name'=>'Introduction'),
        'content'=>array('required'=>'no', 'blank'=>'yes', 'trim'=>'yes', 'name'=>'Content'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'writingfestivals', 'private', 'checkAccess');
    $rc = ciniki_writingfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.writingfestivals.winnerAdd');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Setup permalink
    //
    if( !isset($args['permalink']) || $args['permalink'] == '' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'makePermalink');
        $permalink = '';
        if( isset($args['category']) && $args['category'] != '' ) {
            $permalink .= ($permalink != '' ? '-' : '') . $args['category'];
        }
        if( isset($args['award']) && $args['award'] != '' ) {
            $permalink .= ($permalink != '' ? '-' : '') . $args['award'];
        }
        if( isset($args['title']) && $args['title'] != '' ) {
            $permalink .= ($permalink != '' ? '-' : '') . $args['title'];
        }
        if( isset($args['author']) && $args['author'] != '' ) {
            $permalink .= ($permalink != '' ? '-' : '') . $args['author'];
        }
        $args['permalink'] = ciniki_core_makePermalink($ciniki, $permalink);
    }

    //
    // Make sure the permalink is unique
    //
    $strsql = "SELECT id, permalink "
        . "FROM ciniki_writingfestival_winners "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
        . "AND permalink = '" . ciniki_core_dbQuote($ciniki, $args['permalink']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.writingfestivals', 'item');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( $rc['num_rows'] > 0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.220', 'msg'=>'You already have a winner with that award, title and author, please choose another.'));
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
    // Add the winner to the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'ciniki.writingfestivals.winner', $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.writingfestivals');
        return $rc;
    }
    $winner_id = $rc['id'];

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
    ciniki_core_hookExec($ciniki, $args['tnid'], 'ciniki', 'web', 'indexObject', array('object'=>'ciniki.writingfestivals.winner', 'object_id'=>$winner_id));

    return array('stat'=>'ok', 'id'=>$winner_id);
}
?>
