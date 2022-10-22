<?php
//
// Description
// -----------
// Load the current festival
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_writingfestivals_loadCurrentFestival(&$ciniki, $tnid) {

    //
    // Get the current festival
    //
    $strsql = "SELECT id, "
        . "name, "
        . "flags, "
        . "earlybird_date, "
        . "live_date, "
        . "virtual_date "
        . "FROM ciniki_writingfestivals "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND status = 30 "        // Current
        . "ORDER BY start_date DESC "
        . "LIMIT 1 "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.writingfestivals', 'festival');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.259', 'msg'=>'Unable to load festival', 'err'=>$rc['err']));
    }
    if( !isset($rc['festival']) ) {
        // No festivals published, no items to return
        return array('stat'=>'ok', 'items'=>array());
    }
    $festival = $rc['festival'];

    //
    // Determine which dates are still open for the festival
    //
    $now = new DateTime('now', new DateTimezone('UTC'));
    $earlybird_dt = new DateTime($festival['earlybird_date'], new DateTimezone('UTC'));
    $live_dt = new DateTime($festival['live_date'], new DateTimezone('UTC'));
    $virtual_dt = new DateTime($festival['virtual_date'], new DateTimezone('UTC'));
    $festival['earlybird'] = (($festival['flags']&0x01) == 0x01 && $earlybird_dt > $now ? 'yes' : 'no');
    $festival['live'] = (($festival['flags']&0x01) == 0x01 && $live_dt > $now ? 'yes' : 'no');
    $festival['virtual'] = (($festival['flags']&0x03) == 0x03 && $virtual_dt > $now ? 'yes' : 'no');

    return array('stat'=>'ok', 'festival'=>$festival);
}
?>
