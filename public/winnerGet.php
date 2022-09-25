<?php
//
// Description
// ===========
// This method will return all the information about an winner.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the winner is attached to.
// winner_id:          The ID of the winner to get the details for.
//
// Returns
// -------
//
function ciniki_writingfestivals_winnerGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'winner_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Winner'),
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
    $rc = ciniki_writingfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.writingfestivals.winnerGet');
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

    //
    // Return default for new Winner
    //
    if( $args['winner_id'] == 0 ) {
        $winner = array('id'=>0,
            'festival_id'=>'',
            'category'=>'',
            'award'=>'',
            'sequence'=>'1',
            'title'=>'',
            'author'=>'',
            'image_id'=>'',
            'synopsis'=>'',
            'intro'=>'',
            'content'=>'',
        );
    }

    //
    // Get the details for an existing Winner
    //
    else {
        $strsql = "SELECT ciniki_writingfestival_winners.id, "
            . "ciniki_writingfestival_winners.festival_id, "
            . "ciniki_writingfestival_winners.category, "
            . "ciniki_writingfestival_winners.award, "
            . "ciniki_writingfestival_winners.sequence, "
            . "ciniki_writingfestival_winners.title, "
            . "ciniki_writingfestival_winners.author, "
            . "ciniki_writingfestival_winners.image_id, "
            . "ciniki_writingfestival_winners.synopsis, "
            . "ciniki_writingfestival_winners.intro, "
            . "ciniki_writingfestival_winners.content "
            . "FROM ciniki_writingfestival_winners "
            . "WHERE ciniki_writingfestival_winners.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_writingfestival_winners.id = '" . ciniki_core_dbQuote($ciniki, $args['winner_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.writingfestivals', array(
            array('container'=>'winners', 'fname'=>'id', 
                'fields'=>array('festival_id', 'category', 'award', 'sequence', 'title', 'author', 'image_id', 'synopsis', 'intro', 'content'),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.320', 'msg'=>'Winner not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['winners'][0]) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.321', 'msg'=>'Unable to find Winner'));
        }
        $winner = $rc['winners'][0];
    }

    return array('stat'=>'ok', 'winner'=>$winner);
}
?>
