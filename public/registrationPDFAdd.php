<?php
//
// Description
// ===========
// This method will add a new file to the files table.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant to add the file to.
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_writingfestivals_registrationPDFAdd(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'registration_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Registration'),
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
    $rc = ciniki_writingfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.writingfestivals.registrationPDFAdd'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Get the details of the registration
    //
    $strsql = "SELECT registrations.id, "
        . "registrations.uuid, "
        . "registrations.festival_id, "
        . "registrations.pdf_filename "
        . "FROM ciniki_writingfestival_registrations AS registrations "
        . "WHERE registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND registrations.id = '" . ciniki_core_dbQuote($ciniki, $args['registration_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.writingfestivals', 'registration');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.164', 'msg'=>'Unable to load registration', 'err'=>$rc['err']));
    }
    if( !isset($rc['registration']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.165', 'msg'=>'Unable to find requested registration'));
    }
    $registration = $rc['registration'];
    
    //
    // Get the tenant storage directory
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'hooks', 'storageDir');
    $rc = ciniki_tenants_hooks_storageDir($ciniki, $args['tnid'], array());
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $tenant_storage_dir = $rc['storage_dir'];

    //
    // Check to see if an image was uploaded
    //
    if( isset($_FILES['uploadfile']['error']) && $_FILES['uploadfile']['error'] == UPLOAD_ERR_INI_SIZE ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.52', 'msg'=>'Upload failed, file too large.'));
    }
    // FIXME: Add other checkes for $_FILES['uploadfile']['error']

    //
    // Make sure a file was submitted
    //
    if( !isset($_FILES) || !isset($_FILES['uploadfile']) || $_FILES['uploadfile']['tmp_name'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.53', 'msg'=>'No file specified.'));
    }

    $args['pdf_filename'] = $_FILES['uploadfile']['name'];
    $args['extension'] = preg_replace('/^.*\.([a-zA-Z]+)$/', '$1', $args['pdf_filename']);

    //
    // Check the extension is a PDF, currently only accept PDF files
    //
    if( $args['extension'] != 'pdf' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.172', 'msg'=>'The file must be a PDF file.'));
    }
   
    //
    // Move the file to ciniki-storage
    //
    $storage_filename = $tenant_storage_dir . '/ciniki.writingfestivals/files/' 
        . $registration['uuid'][0] . '/' . $registration['uuid'] . '_writing';
    if( !is_dir(dirname($storage_filename)) ) {
        if( !mkdir(dirname($storage_filename), 0700, true) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.177', 'msg'=>'Unable to add file'));
        }
    }

    if( !rename($_FILES['uploadfile']['tmp_name'], $storage_filename) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.178', 'msg'=>'Unable to add file'));
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
    // Update the Registration in the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.writingfestivals.registration', $args['registration_id'], $args, 0x04);
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
    // Return the registration
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'writingfestivals', 'public', 'registrationGet');
    return ciniki_writingfestivals_registrationGet($ciniki);
}
?>
