<?php
//
// Description
// -----------
// This function will create or find an existing teacher.
//
// Arguments
// ---------
// ciniki:
// settings:        The web settings structure.
// tnid:     The ID of the tenant to get writing festival request for.
//
// args:            The possible arguments for posts
//
//
// Returns
// -------
//
function ciniki_writingfestivals_web_teacherCreate(&$ciniki, $settings, $tnid, $args) {

    //
    // Check to make sure the module is enabled
    //
    if( !isset($ciniki['tenant']['modules']['ciniki.writingfestivals']) ) {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.writingfestivals.154', 'msg'=>"I'm sorry, the page you requested does not exist."));
    }

    if( !isset($_POST['teacher_name']) 
        || ($_POST['teacher_name'] == '' && isset($_POST['teacher_email']) && $_POST['teacher_email'] == '')
        ) {
        return array('stat'=>'ok', 'teacher_customer_id'=>0);
    }

    //
    // Check if the teacher_name is found
    //
    if( isset($_POST['teacher_name']) && $_POST['teacher_name'] != '' ) {
        $strsql = "SELECT id, display_name "
            . "FROM ciniki_customers "
            . "WHERE (display_name = '" . ciniki_core_dbQuote($ciniki, $_POST['teacher_name']) . "' "
                . "OR company = '" . ciniki_core_dbQuote($ciniki, $_POST['teacher_name']) . "' "
                . ") "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.writingfestivals', 'item');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.189', 'msg'=>'Unable to load item', 'err'=>$rc['err']));
        }
        if( isset($rc['item']) ) {
            return array('stat'=>'ok', 'teacher_customer_id'=>$rc['item']['id']);
        }
    }

    //
    // Check if teacher email is found
    //
    if( isset($_POST['teacher_email']) && $_POST['teacher_email'] != '' ) {
        $strsql = "SELECT e.id, e.customer_id "
            . "FROM ciniki_customer_emails AS e "
            . "INNER JOIN ciniki_customers AS c ON ("
                . "e.customer_id = c.id "
                . "AND c.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . "AND c.status < 60 "
                . ") "
            . "WHERE e.email = '" . ciniki_core_dbQuote($ciniki, $_POST['teacher_email']) . "' "
            . "AND e.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.writingfestivals', 'item');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.190', 'msg'=>'Unable to load item', 'err'=>$rc['err']));
        }
        if( isset($rc['item']) ) {
            return array('stat'=>'ok', 'teacher_customer_id'=>$rc['item']['customer_id']);
        }
    }

    //
    // Check that both name and email were specified
    //
    if( $_POST['teacher_name'] == '' && isset($_POST['teacher_email']) && $_POST['teacher_email'] != '' ) {
        return array('stat'=>'error', 'err'=>array('code'=>'ciniki.writingfestivals.191', 'msg'=>"You must specifiy the Teacher's Name"));
    }

    //
    // Create the teacher
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'web', 'customerAdd');
    $rc = ciniki_customers_web_customerAdd($ciniki, $tnid, array(
        'name'=>$_POST['teacher_name'],
        'email_address'=>$_POST['teacher_email'],
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'error', 'err'=>array('code'=>'ciniki.writingfestivals.146', 'msg'=>"We had a problem saving the theacher, please try again or contact us for help."));
    }

    return array('stat'=>'ok', 'teacher_customer_id'=>$rc['id']);
}
?>
