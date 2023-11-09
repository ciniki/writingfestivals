<?php
//
// Description
// -----------
// This function will process api requests for wng.
//
// Arguments
// ---------
// ciniki:
// tnid:     The ID of the tenant to get sapos request for.
//
// args:            The possible arguments for posts
//
//
// Returns
// -------
//
function ciniki_writingfestivals_wng_api(&$ciniki, $tnid, &$request) {

    //
    // Check to make sure the module is enabled
    //
    if( !isset($ciniki['tenant']['modules']['ciniki.writingfestivals']) ) {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.writingfestivals.526', 'msg'=>"I'm sorry, the page you requested does not exist."));
    }

    //
    // Check to make sure logged in (also available to anonymous users of forms)
    //
    if( !isset($request['session']['customer']['id']) || $request['session']['customer']['id'] < 1 ) {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.writingfestivals.430', 'msg'=>"I'm sorry, the you are not authorized."));
    }

    //
    // saveSubmission - Save the form submission
    //
    if( isset($request['uri_split'][$request['cur_uri_pos']]) 
        && $request['uri_split'][$request['cur_uri_pos']] == 'adjudicationsSave' 
        ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'writingfestivals', 'wng', 'apiAdjudicationsSave');
        return ciniki_writingfestivals_wng_apiAdjudicationsSave($ciniki, $tnid, $request);
    }

    return array('stat'=>'ok');
}
?>
