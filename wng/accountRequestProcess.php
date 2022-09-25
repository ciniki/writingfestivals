<?php
//
// Description
// -----------
// This function will process the account request from accountMenuItems
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_writingfestivals_wng_accountRequestProcess(&$ciniki, $tnid, &$request, $item) {

    if( !isset($item['ref']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.199', 'msg'=>'No reference specified'));
    }

    if( !isset($request['session']['customer']['id']) || $request['session']['customer']['id'] <= 0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.200', 'msg'=>'Must be logged in'));
    }

    if( $item['ref'] == 'ciniki.writingfestivals.registrations' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'writingfestivals', 'wng', 'accountRegistrationsProcess');
        return ciniki_writingfestivals_wng_accountRegistrationsProcess($ciniki, $tnid, $request, $item);
    } elseif( $item['ref'] == 'ciniki.writingfestivals.competitors' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'writingfestivals', 'wng', 'accountCompetitorsProcess');
        return ciniki_writingfestivals_wng_accountCompetitorsProcess($ciniki, $tnid, $request, $item);
    }
    

    return array('stat'=>'404', 'err'=>array('code'=>'ciniki.writingfestivals.201', 'msg'=>'Account page not found'));
}
?>
