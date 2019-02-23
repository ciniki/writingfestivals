<?php
//
// Description
// -----------
// This function will update the festival settings from the supplier array.
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// festival_id:     The ID of the festival
// args:            The array to search for the settings in.
// 
// Returns
// ---------
// 
function ciniki_writingfestivals_settingsUpdate(&$ciniki, $tnid, $festival_id, $args) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');

    //
    // Get the current settings
    //
    $strsql = "SELECT id, uuid, detail_key, detail_value "
        . "FROM ciniki_writingfestival_settings "
        . "WHERE festival_id = '" . ciniki_core_dbQuote($ciniki, $festival_id) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.writingfestivals', array(
        array('container'=>'settings', 'fname'=>'detail_key', 'fields'=>array('id', 'uuid', 'detail_key', 'detail_value')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $settings = array();
    if( isset($rc['settings']) ) {
        $settings = $rc['settings'];
    }

    //
    // Check for any settings and add/update
    //
    $valid_settings = array(
        'age-restriction-msg',
        'waiver-title',
        'waiver-msg',
        );
    foreach($valid_settings as $field) {
        if( isset($args[$field]) ) {
            if( isset($settings[$field]['detail_value']) && $settings[$field]['detail_value'] != $args[$field] ) {
                //
                // Update the setting
                //
                $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.writingfestivals.setting', $settings[$field]['id'],
                    array('detail_value'=>$args[$field]), 
                    0x04);
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
            } else if( !isset($settings[$field]) ) {
                //
                // Add the setting
                //
                $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'ciniki.writingfestivals.setting', 
                    array('festival_id'=>$festival_id, 'detail_key'=>$field, 'detail_value'=>$args[$field]), 
                    0x04);
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
            }
        }
    }

    return array('stat'=>'ok');
}
?>
