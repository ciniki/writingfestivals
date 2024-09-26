<?php
//
// Description
// -----------
// This function will remove the files for a registration and then remove the registration
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_writingfestivals_registrationDelete(&$ciniki, $tnid, $reg_id) {

    //
    // Get the tenant storage directory
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'hooks', 'storageDir');
    $rc = ciniki_tenants_hooks_storageDir($ciniki, $tnid, array());
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $tenant_storage_dir = $rc['storage_dir'];

    //
    // Load the registration details
    //
    $strsql = "SELECT registrations.id, "
        . "registrations.uuid, "
        . "registrations.pdf_filename "
        . "FROM ciniki_writingfestival_registrations AS registrations "
        . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $reg_id) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.writingfestivals', 'registration');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.236', 'msg'=>'Unable to load registration', 'err'=>$rc['err']));
    }
    if( !isset($rc['registration']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.236', 'msg'=>'Unable to find requested registration'));
    }
    $reg = $rc['registration'];
    
    //
    // Remove the files
    //
    for($i = 1; $i <= 8; $i++) {
        if( $reg["pdf_filename"] != '' ) {
            $filename = "{$tenant_storage_dir}/ciniki.writingfestivals/files/{$reg['uuid'][0]}/{$reg['uuid']}_writing";
            if( file_exists($filename) ) {
                unlink($filename);
            }
        }
    }

    //
    // Remove the registration
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');
    $rc = ciniki_core_objectDelete($ciniki, $tnid, 'ciniki.writingfestivals.registration', $reg['id'], $reg['uuid'], 0x04);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.237', 'msg'=>'Unable to remove registration', 'err'=>$rc['err']));
    }

    return array('stat'=>'ok');
}
?>
