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
function ciniki_writingfestivals_winnerUpdate(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'winner_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Winner'),
        'festival_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>''),
        'category'=>array('required'=>'no', 'blank'=>'no', 'trim'=>'yes', 'name'=>'Category'),
        'award'=>array('required'=>'no', 'blank'=>'no', 'trim'=>'yes', 'name'=>'Award'),
        'sequence'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Order'),
        'title'=>array('required'=>'no', 'blank'=>'yes', 'trim'=>'yes', 'name'=>'Title'),
        'author'=>array('required'=>'no', 'blank'=>'yes', 'trim'=>'yes', 'name'=>'Author'),
        'image_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Image'),
        'synopsis'=>array('required'=>'no', 'blank'=>'yes', 'trim'=>'yes', 'name'=>'Synopsis'),
        'intro'=>array('required'=>'no', 'blank'=>'yes', 'trim'=>'yes', 'name'=>'Introduction'),
        'content'=>array('required'=>'no', 'blank'=>'yes', 'trim'=>'yes', 'name'=>'Content'),
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
    $rc = ciniki_writingfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.writingfestivals.winnerUpdate');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load the existing winner
    //
    $strsql = "SELECT id, category, award, sequence, title, author, permalink "
        . "FROM ciniki_writingfestival_winners "
        . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['winner_id']) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.writingfestivals', 'winner');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.227', 'msg'=>'Unable to load winner', 'err'=>$rc['err']));
    }
    if( !isset($rc['winner']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.228', 'msg'=>'Unable to find requested winner'));
    }
    $winner = $rc['winner'];

    //
    // Setup permalink
    //
    if( isset($args['category']) || isset($args['award']) || isset($args['title']) || isset($args['author']) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'makePermalink');
        $permalink = '';
        if( isset($args['category']) ) {
            if( $args['category'] != '' ) {
                $permalink .= ($permalink != '' ? '-' : '') . $args['category'];
            }
        } elseif( $winner['category'] != '' ) {
                $permalink .= ($permalink != '' ? '-' : '') . $winner['category'];
        }
        if( isset($args['award']) ) {
            if( $args['award'] != '' ) {
                $permalink .= ($permalink != '' ? '-' : '') . $args['award'];
            }
        } elseif( $winner['award'] != '' ) {
                $permalink .= ($permalink != '' ? '-' : '') . $winner['award'];
        }
        if( isset($args['title']) ) {
            if( $args['title'] != '' ) {
                $permalink .= ($permalink != '' ? '-' : '') . $args['title'];
            }
        } elseif( $winner['title'] != '' ) {
                $permalink .= ($permalink != '' ? '-' : '') . $winner['title'];
        }
        if( isset($args['author']) ) {
            if( $args['author'] != '' ) {
                $permalink .= ($permalink != '' ? '-' : '') . $args['author'];
            }
        } elseif( $winner['author'] != '' ) {
                $permalink .= ($permalink != '' ? '-' : '') . $winner['author'];
        }
        $args['permalink'] = ciniki_core_makePermalink($ciniki, $permalink);

        //
        // Make sure the permalink is unique
        //
        $strsql = "SELECT id, permalink "
            . "FROM ciniki_writingfestival_winners "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
            . "AND id <> '" . ciniki_core_dbQuote($ciniki, $args['winner_id']) . "' "
            . "AND permalink = '" . ciniki_core_dbQuote($ciniki, $args['permalink']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.writingfestivals', 'item');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( $rc['num_rows'] > 0 ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.220', 'msg'=>'You already have a winner with that award, title and author, please choose another.'));
        }
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
    // Update the Winner in the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.writingfestivals.winner', $args['winner_id'], $args, 0x04);
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
    ciniki_core_hookExec($ciniki, $args['tnid'], 'ciniki', 'web', 'indexObject', array('object'=>'ciniki.writingfestivals.winner', 'object_id'=>$args['winner_id']));

    return array('stat'=>'ok');
}
?>
