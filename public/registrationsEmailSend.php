<?php
//
// Description
// -----------
// This method will return the excel export of registrations.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to get Registration for.
//
// Returns
// -------
//
function ciniki_writingfestivals_registrationsEmailSend(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'festival_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Festival'),
        'teacher_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Teacher'),
        'subject'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Subject'),
        'message'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Message'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'writingfestivals', 'private', 'checkAccess');
    $rc = ciniki_writingfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.writingfestivals.registrationsEmailSend');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    $strsql = "SELECT registrations.id, "
        . "registrations.festival_id, "
        . "sections.id AS section_id, "
        . "registrations.teacher_customer_id, "
        . "teachers.display_name AS teacher_name, "
        . "registrations.billing_customer_id, "
        . "registrations.rtype, "
        . "registrations.rtype AS rtype_text, "
        . "registrations.status, "
        . "registrations.status AS status_text, "
        . "registrations.display_name, "
        . "registrations.class_id, "
        . "classes.code AS class_code, "
        . "classes.name AS class_name, "
        . "registrations.title, "
        . "registrations.word_count, "
        . "FORMAT(registrations.fee, 2) AS fee, "
        . "registrations.payment_type "
        . "FROM ciniki_writingfestival_registrations AS registrations "
        . "LEFT JOIN ciniki_customers AS teachers ON ("
            . "registrations.teacher_customer_id = teachers.id "
            . "AND teachers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "LEFT JOIN ciniki_writingfestival_classes AS classes ON ("
            . "registrations.class_id = classes.id "
            . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "LEFT JOIN ciniki_writingfestival_categories AS categories ON ("
            . "classes.category_id = categories.id "
            . "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "LEFT JOIN ciniki_writingfestival_sections AS sections ON ("
            . "categories.section_id = sections.id "
            . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE registrations.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
        . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND registrations.teacher_customer_id = '" . ciniki_core_dbQuote($ciniki, $args['teacher_id']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.writingfestivals', array(
        array('container'=>'registrations', 'fname'=>'id', 
            'fields'=>array('id', 'festival_id', 'teacher_name', 'display_name', 
                'class_id', 'class_code', 'class_name', 'title', 'word_count', 'fee', 'payment_type'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $html = '';
    $text = '';
    if( isset($rc['registrations']) ) {
        $festival['registrations'] = $rc['registrations'];
        $total = 0;
        $html = "<table cellpadding=5 cellspacing=0>";
        $html .= "<tr><th>Class</th><th>Competitor</th><th>Title</th><th>Time</th></tr>";
        foreach($festival['registrations'] as $iid => $registration) {
            $html .= '<tr><td>' . $registration['class_code'] . '</td><td>' . $registration['display_name'] . '</td><td>' . $registration['title'] . '</td><td>' . $registration['word_count'] . "</td></tr>\n";
            $text .= $registration['class_code'] 
                . ' - ' . $registration['display_name'] 
                . ($registration['title'] != '' ? ' - ' . $registration['title'] : '')
                . ($registration['word_count'] != '' ? ' - ' . $registration['word_count'] : '')
                . "\n";
        }
        $html .= "</table>";
    } else {
        $festival['registrations'] = array();
    }

    $html_message = $args['message'] . "<br/><br/>" . $html;
    $text_message = $args['message'] . "\n\n" . $text;

    //
    // Lookup the teacher info
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'hooks', 'customerDetails');
    $rc = ciniki_customers_hooks_customerDetails($ciniki, $args['tnid'], 
        array('customer_id'=>$args['teacher_id'], 'phones'=>'no', 'emails'=>'yes', 'addresses'=>'no', 'subscriptions'=>'no'));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['customer']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.102', 'msg'=>'No teacher found, we are unable to send the email.'));
    }
    $customer = $rc['customer'];

    //
    // if customer is set
    //
    if( !isset($customer['emails'][0]['email']['address']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.103', 'msg'=>"The teacher doesn't have an email address, we are unable to send the email."));
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'mail', 'hooks', 'addMessage');
    $rc = ciniki_mail_hooks_addMessage($ciniki, $args['tnid'], array(
        'customer_id'=>$args['teacher_id'],
        'customer_name'=>(isset($customer['display_name'])?$customer['display_name']:''),
        'customer_email'=>$customer['emails'][0]['email']['address'],
        'subject'=>$args['subject'],
        'html_content'=>$html_message,
        'text_content'=>$text_message,
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $ciniki['emailqueue'][] = array('mail_id'=>$rc['id'], 'tnid'=>$args['tnid']);

    return array('stat'=>'ok');
}
?>
