<?php
//
// Description
// -----------
// This function will return the list of options for the module that can be set for the website.
//
// Arguments
// ---------
// ciniki:
// settings:        The web settings structure.
// tnid:     The ID of the tenant to get writingfestivals for.
//
// args:            The possible arguments for posts
//
//
// Returns
// -------
//
function ciniki_writingfestivals_hooks_webOptions(&$ciniki, $tnid, $args) {

    //
    // Check to make sure the module is enabled
    //
    if( !isset($ciniki['tenant']['modules']['ciniki.writingfestivals']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.2', 'msg'=>"I'm sorry, the page you requested does not exist."));
    }

    //
    // Get the settings from the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDetailsQueryDash');
    $rc = ciniki_core_dbDetailsQueryDash($ciniki, 'ciniki_web_settings', 'tnid', $tnid, 'ciniki.web', 'settings', 'page-writingfestivals');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['settings']) ) {
        $settings = array();
    } else {
        $settings = $rc['settings'];
    }


    $options = array();
    $pages['ciniki.writingfestivals.current'] = array('name'=>'Writing Festivals', 'options'=>$options);

    return array('stat'=>'ok', 'pages'=>$pages);
}
?>
