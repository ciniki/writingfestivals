<?php
//
// Description
// -----------
// This function will process the registrations page for online writing festival registrations.
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
function ciniki_writingfestivals_web_processRequestRegistrations(&$ciniki, $settings, $tnid, $args) {

    //
    // Check to make sure the module is enabled
    //
    if( !isset($ciniki['tenant']['modules']['ciniki.writingfestivals']) ) {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.writingfestivals.181', 'msg'=>"I'm sorry, the page you requested does not exist."));
    }

    //
    // Check there is a festival setup
    //
    if( !isset($args['festival_id']) || $args['festival_id'] <= 0 ) {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.writingfestivals.122', 'msg'=>"I'm sorry, the page you requested does not exist."));
    }

    //
    // This function does not build a page, just provides an array of blocks
    //
    $blocks = array();

    //
    // Load maps
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'writingfestivals', 'private', 'maps');
    $rc = ciniki_writingfestivals_maps($ciniki);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $maps = $rc['maps'];
    
    //
    // Check to make sure the customer is logged in, otherwise redirect to login page
    //
    if( !isset($ciniki['session']['customer']['id']) || $ciniki['session']['customer']['id'] == 0 ) {
        $redirect = $args['ssl_domain_base_url'];
        $join = '?';
        if( isset($_GET['r']) && $_GET['r'] != '' ) {
            $redirect .= $join . 'r=' . $_GET['r'];
            $join = '&';
        }
        if( isset($_GET['cl']) && $_GET['cl'] != '' ) {
            $redirect .= $join . 'cl=' . $_GET['cl'];
            $join = '&';
        }
        $blocks[] = array(
            'type' => 'login', 
            'section' => 'login',
            'register' => 'yes',
            'redirect' => $redirect,        // Redirect back to registrations page
            );
        return array('stat'=>'ok', 'blocks'=>$blocks);
    }

    //
    // Get the customer details
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'hooks', 'customerDetails');
    $rc = ciniki_customers_hooks_customerDetails($ciniki, $tnid, array(
        'customer_id' => $ciniki['session']['customer']['id'], 
        'addresses' => 'yes',
        'phones' => 'yes',
        'emails' => 'yes',
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.185', 'msg'=>'Internal Error', 'err'=>$rc['err']));
    }
    $customer = $rc['customer'];

    //
    // Check if customer is setup for the writing festival this year
    //
    $strsql = "SELECT id, ctype "
        . "FROM ciniki_writingfestival_customers "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
        . "AND customer_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['customer']['id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.writingfestivals', 'customer');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.123', 'msg'=>'Unable to load customer', 'err'=>$rc['err']));
    }
    $customer_type = 0;
    if( !isset($rc['customer']['ctype']) || $rc['customer']['ctype'] == 0 ) {
        //
        // Check if customer type was submitted
        //
        if( isset($_GET['ctype']) && in_array($_GET['ctype'], array(10,20,30)) ) {
            //
            // Add the customer to the writingfestival
            //
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
            $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.writingfestivals.customer', array(
                'festival_id' => $args['festival_id'],
                'customer_id' => $ciniki['session']['customer']['id'],
                'ctype' => $_GET['ctype'],
                ), 0x04);
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.124', 'msg'=>'Unable to add the customer', 'err'=>$rc['err']));
            }
            $customer_type = $_GET['ctype'];
        } 
        
        //
        // Ask the customer what type they are
        //
        else {
            $additional_args = '';
            if( isset($_GET['r']) && $_GET['r'] != '' ) {
                $additional_args .= '&r=' . $_GET['r'];
            }
            if( isset($_GET['cl']) && $_GET['cl'] != '' ) {
                $additional_args .= '&cl=' . $_GET['cl'];
            }
            $blocks[] = array('type'=>'content', 'content'=>'In order to better serve you, we need to know who you are.');
            $blocks[] = array('type'=>'decisionbuttons',
                'buttons'=>array(
                    array('label' => 'I am a Parent registering my Children',
                        'url' => $args['base_url'] . "?ctype=10" . $additional_args,
                        ),
/*                    array('label' => 'I am a Teacher registering my Students',
                        'url' => $args['base_url'] . "?ctype=20" . $additional_args,
                        ), */
                    array('label' => 'I am an Adult registering Myself',
                        'url' => $args['base_url'] . "?ctype=30" . $additional_args,
                        ),
                    ));
            return array('stat'=>'ok', 'blocks'=>$blocks);
        }
    } else {
        $customer_type = $rc['customer']['ctype'];
    }

    //
    // Setup language based on customer type
    //
    $s_competitor = '';
    $p_competitor = '';
    if( $customer_type == 10 ) {
        $s_competitor = 'Child';
        $p_competitor = 'Children';
    } elseif( $customer_type == 20 ) {
        $s_competitor = 'Student';
        $p_competitor = 'Students';
    } else {
        $s_competitor = 'Competitor';
        $p_competitor = 'Competitors';
    }

    //
    // Load the customers registrations
    //
    $strsql = "SELECT r.id, r.uuid, "
        . "r.teacher_customer_id, r.billing_customer_id, r.rtype, r.status, r.status AS status_text, "
        . "r.display_name, r.public_name, "
        . "r.competitor1_id, x.parent, r.competitor2_id, r.competitor3_id, r.competitor4_id, r.competitor5_id, "
        . "r.class_id, r.timeslot_id, r.title, r.word_count, r.fee, r.payment_type, r.notes, "
        . "c.code AS class_code, c.name AS class_name, c.flags AS class_flags "
        . "FROM ciniki_writingfestival_registrations AS r "
        . "LEFT JOIN ciniki_writingfestival_classes AS c ON ("
            . "r.class_id = c.id "
            . "AND c.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_writingfestival_competitors AS x ON ("
            . "r.competitor1_id = x.id "
            . "AND x.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE r.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
        . "AND r.billing_customer_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['customer']['id']) . "' "
        . "AND r.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "ORDER BY r.status, r.display_name, class_code, class_name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.writingfestivals', array(
        array('container'=>'registrations', 'fname'=>'id', 
            'fields'=>array('id', 'uuid', 'teacher_customer_id', 'billing_customer_id', 'rtype', 'status', 'status_text',
                'display_name', 'public_name', 'competitor1_id', 'parent', 'competitor2_id', 'competitor3_id', 
                'competitor4_id', 'competitor5_id', 'class_id', 'timeslot_id', 'title', 'word_count', 
                'fee', 'payment_type', 'notes',
                'class_code', 'class_name', 'class_flags'),
            'maps'=>array('status_text'=>$maps['registration']['status']),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.126', 'msg'=>'Unable to load registrations', 'err'=>$rc['err']));
    }
    $registrations = isset($rc['registrations']) ? $rc['registrations'] : array();
//    $teacher_ids = array();
    $parents = array();
    foreach($registrations as $rid => $reg) {
        $registrations[$rid]['fee_display'] = '$' . number_format($reg['fee'], 2);
//        $teacher_ids[] = $reg['teacher_customer_id'];
        if( $reg['status'] == 5 ) {
            $registrations[$rid]['edit-url'] = $args['base_url'] . '?r=' . $reg['uuid'];
        }
        if( $reg['parent'] != '' ) {
            if( !isset($parents[$reg['parent']]) ) {
                $parents[$reg['parent']] = array(
                    'name' => $reg['parent'],
                    'num_registrations' => 0,
                    'total_fees' => 0,
                    );
            }
            $parents[$reg['parent']]['num_registrations'] += 1;
            $parents[$reg['parent']]['total_fees'] += $reg['fee'];
        }
    }

    //
    // Load the competitors
    //
    $strsql = "SELECT c.id, c.uuid, "
        . "c.name, c.flags, c.parent, c.address, c.city, c.province, c.postal, "
        . "c.phone_home, c.phone_cell, c.email, c.age, c.notes, "
        . "IF((c.flags&0x01)=0x01,'signed','') AS waiver_signed "
        . "FROM ciniki_writingfestival_competitors AS c "
        . "WHERE c.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
        . "AND c.billing_customer_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['customer']['id']) . "' "
        . "AND c.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.writingfestivals', array(
        array('container'=>'competitors', 'fname'=>'id', 
            'fields'=>array('id', 'uuid', 'name', 'flags', 'parent', 'address', 'city', 'province', 'postal', 
                'phone_home', 'phone_cell', 'email', 'age', 'notes', 'waiver_signed'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.183', 'msg'=>'Unable to load competitors', 'err'=>$rc['err']));
    }
    $competitors = isset($rc['competitors']) ? $rc['competitors'] : array();

    //
    // Load the teachers that this customer has access to, based 
    // on their registrations
    //
    /*
    $teachers = array();
    if( count($teacher_ids) > 0 ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteIDs');
        $strsql = "SELECT id, display_name "
            . "FROM ciniki_customers "
            . "WHERE id IN (" . ciniki_core_dbQuoteIDs($ciniki, $teacher_ids) . ") "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.writingfestivals', array(
            array('container'=>'teachers', 'fname'=>'id', 'fields'=>array('id', 'name'=>'display_name')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.144', 'msg'=>'Unable to load teachers', 'err'=>$rc['err']));
        }
        if( isset($rc['teachers']) ) {
            $teachers = $rc['teachers'];
        }
    } */

    //
    // Load the classes
    //
    $strsql = "SELECT s.id AS section_id, "
        . "s.name AS section_name, "
        . "ca.name AS category_name, "
        . "cl.id AS class_id, "
        . "cl.uuid AS class_uuid, "
        . "cl.code AS class_code, "
        . "cl.name AS class_name, "
        . "cl.flags AS class_flags, ";
    if( isset($args['earlybird']) && $args['earlybird'] >= 0 ) {
        $strsql .= "cl.earlybird_fee AS class_fee ";
    } else {
        $strsql .= "cl.fee AS class_fee ";
    }
    $strsql .= "FROM ciniki_writingfestival_sections AS s "
        . "LEFT JOIN ciniki_writingfestival_categories AS ca ON ("
            . "s.id = ca.section_id "
            . "AND ca.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_writingfestival_classes AS cl ON ("
            . "ca.id = cl.category_id "
            . "AND cl.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE s.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
        . "AND s.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "ORDER BY s.sequence, s.name, ca.sequence, ca.name, cl.sequence, cl.name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.writingfestivals', array(
        array('container'=>'sections', 'fname'=>'section_id', 'fields'=>array('id'=>'section_id', 'name'=>'section_name')),
        array('container'=>'classes', 'fname'=>'class_id', 'fields'=>array('id'=>'class_id', 'uuid'=>'class_uuid',
            'category_name'=>'category_name', 'code'=>'class_code', 'name'=>'class_name', 'flags'=>'class_flags', 'fee'=>'class_fee')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.80', 'msg'=>'Unable to load ', 'err'=>$rc['err']));
    }
    $sections = isset($rc['sections']) ? $rc['sections'] : array();

    $class = null;
    if( isset($_POST['section']) && $_POST['section'] > 0 && isset($_POST["section-{$_POST['section']}-class"]) ) {
        $class = $sections[$_POST['section']]['classes'][$_POST["section-{$_POST['section']}-class"]];
    } elseif( isset($_GET['cl']) && $_GET['cl'] > 0 ) {
        foreach($sections as $sid => $section) {
            if( isset($section['classes']) ) {
                foreach($section['classes'] as $section_class) {
                    if( $_GET['cl'] == $section_class['uuid'] ) {
                        $class = $section_class;
                    }
                }
            }
        }
    }

    //
    // Check if download PDF selected
    //
    if( isset($_POST['action']) && $_POST['action'] == 'Download Printable PDF' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'writingfestivals', 'templates', 'teacherRegistrationsPDF');
        $rc = ciniki_writingfestivals_templates_teacherRegistrationsPDF($ciniki, $tnid, array(
            'festival_id' => $args['festival_id'],
            'billing_customer_id' => $ciniki['session']['customer']['id'],
            'registrations' => $registrations,
            'competitors' => $competitors,
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.149', 'msg'=>'Unable to generate PDF', 'err'=>$rc['err']));
        }
        if( isset($rc['pdf']) ) {
            $rc['pdf']->Output($rc['filename'], 'D');
            exit;
        }
    }

    //
    // Check if sapos cart should be created and loaded with unpaid items
    //
    if( isset($_POST['action']) && $_POST['action'] == 'Add to Cart' ) {
 
        //
        // Open or create a shopping cart
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'web', 'cartCreate');
        $rc = ciniki_sapos_web_cartCreate($ciniki, $settings, $tnid, array());
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.130', 'msg'=>'Unable to create cart.', 'err'=>$rc['err']));
        }
        $cart_id = $rc['sapos_id']; 

        //
        // Add items to cart
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
        ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'web', 'cartItemAdd');
        $line_number = 1;
        foreach($registrations as $reg) {
            //
            // Check if in a previous cart and remove
            //
            if( $reg['status'] == 5 ) {
                $rc = ciniki_sapos_web_cartItemAdd($ciniki, $settings, $tnid, array(
                    'line_number' => $line_number,
                    'flags' => 0x08,        // Registration item, quantity must be zero.
                    'object' => 'ciniki.writingfestivals.registration',
                    'object_id' => $reg['id'],
                    'price_id' => 0,
                    'code' => $reg['class_code'],
                    'description' => $reg['class_name'],
                    'quantity' => 1,
                    'shipped_quantity' => 0,
                    'unit_amount' => $reg['fee'],
                    'unit_discount_amount' => 0,
                    'unit_discount_percentage' => 0,
                    'taxtype_id' => 0,
                    'notes' => $reg['display_name'] . ($reg['title'] != '' ? ' - ' . $reg['title'] : ''),
                    ));
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.133', 'msg'=>'Unable to add registration to cart.', 'err'=>$rc['err']));
                }
                $line_number++;
                $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.writingfestivals.registration', $reg['id'], array(
                    'invoice_id'=>$cart_id,
                    'status'=>6,
                    ));
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.134', 'msg'=>'Unable to update registration', 'err'=>$rc['err']));
                }
            }
        }

        //
        // Redirect to the shopping cart
        //
        header('Location: ' . $ciniki['request']['ssl_domain_base_url'] . '/cart');
        exit;
    }

    //
    // Decide what should be displayed
    //
    $display = 'registration-list';
    if( ($args['festival_flags']&0x01) == 0x01 && isset($_GET['r']) && $_GET['r'] != '' ) {
        $display = 'registration-form';
        $registration_id = 0;
        $err_msg = '';
        //
        // Check for an update
        //
        if( $_GET['r'] != 'new' ) {
            foreach($registrations as $registration) {
                if( $registration['uuid'] == $_GET['r'] ) {
                    $registration_id = $registration['id'];

                    //
                    // Check if delete registration
                    //
                    if( isset($_POST['delete']) && $_POST['delete'] == "Delete" ) {
                        $blocks[] = array('type'=>'formmessage', 'level' => 'error',
                            'message' => 'Are you sure you want to delete this registration? Please click the Confirm button below.',
                            );
                        break;
                    }
                    elseif( isset($_POST['delete']) && $_POST['delete'] == "Confirm" ) {
                        if( $registration['status'] != 5 ) {
                            $blocks[] = array('type'=>'formmessage', 'level' => 'error',
                                'message' => 'This registration cannot be deleted online. Please contact us for help.',
                                );
                            unset($_POST['delete']);
                        } else {
                            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');
                            $rc = ciniki_core_objectDelete($ciniki, $tnid, 'ciniki.writingfestivals.registration', $registration_id, $registration['uuid'], 0x04);
                            if( $rc['stat'] != 'ok' ) {
                                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.81', 'msg'=>"I'm sorry, we were unable to remove that registration. You will need to contact us to have it removed.", 'err'=>$rc['err']));
                            }
                            header('Location: ' . $args['base_url']);
                            exit;
                        }
                        break;
                    }

                    //
                    // Check for updates
                    //
                    $update_args = array();
                    if( isset($class) && $class != null ) {
                        if( $class['id'] != $registration['class_id'] ) {
                            $update_args['class_id'] = $class['id'];
                        }
                        
                        //
                        // Check if for new competitors
                        //
                        for($i = 1; $i <= 3; $i++) {
                            $field = "competitor{$i}_id";
                            //
                            // Check if competitor2 or 3 is allowed, if not should be set to zero in the database.
                            //
                            if( ($i == 2 && ($class['flags']&0x10) == 0) || ($i == 3 && ($class['flags']&0x20) == 0) ) {
                                if( $registration[$field] != 0 ) {
                                    $update_args[$field] = 0;
                                }
                                //
                                // Skip to next field, don't do any more processing
                                continue;
                            }
                            if( isset($_POST[$field]) ) {
                                if( $_POST[$field] == 'new' ) {
                                    $required = array();
                                    $required['name'] = 'You must enter a competitors name';
                                    if( $customer_type == 10 || $customer_type == 20 ) {
                                        $required['parent'] = 'You must enter the parents name';
                                    }
                                    $required['city'] = 'You must enter an city';
                                    $required['email'] = 'You must enter an email address';
                                    $required['age'] = 'You must enter this competitors age';
                                    $required['waiver_signed'] = 'You must agree to "' . $args['settings']['waiver-msg'] . '"';
                                    $err_msg = '';
                                    foreach($required as $rf => $msg) {
                                        if( !isset($_POST[$rf . $i]) || trim($_POST[$rf . $i]) == '' ) {
                                            $err_msg = $msg;
                                            break;
                                        }
                                    }
                                    //
                                    // Add new competitor
                                    //
                                    if( $err_msg == '' ) {
//                                        $blocks[] = array('type'=>'formmessage', 'level'=>'error', 'message'=>$err_msg);
//                                    } else {
                                        $new_competitor = array(
                                            'festival_id' => $args['festival_id'],
                                            'billing_customer_id' => $ciniki['session']['customer']['id'],
                                            'name' => $_POST["name{$i}"],
                                            'parent' => (isset($_POST["parent{$i}"]) ? $_POST["parent{$i}"] : ''),
                                            'address' => (isset($_POST["address{$i}"]) ? $_POST["address{$i}"] : ''),
                                            'city' => (isset($_POST["city{$i}"]) ? $_POST["city{$i}"] : ''),
                                            'province' => (isset($_POST["province{$i}"]) ? $_POST["province{$i}"] : ''),
                                            'postal' => (isset($_POST["postal{$i}"]) ? $_POST["postal{$i}"] : ''),
                                            'phone_home' => (isset($_POST["phone_home{$i}"]) ? $_POST["phone_home{$i}"] : ''),
                                            'phone_cell' => (isset($_POST["phone_cell{$i}"]) ? $_POST["phone_cell{$i}"] : ''),
                                            'email' => (isset($_POST["email{$i}"]) ? $_POST["email{$i}"] : ''),
                                            'age' => (isset($_POST["age{$i}"]) ? $_POST["age{$i}"] : ''),
                                            'notes' => (isset($_POST["notes{$i}"]) ? $_POST["notes{$i}"] : ''),
                                            );
                                        if( isset($_POST["waiver_signed{$i}"]) && $_POST["waiver_signed{$i}"] == 'signed' ) {
                                            $new_competitor['flags'] = 0x01;
                                        } else {
                                            $new_competitor['flags'] = 0;
                                        }
                                        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
                                        $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.writingfestivals.competitor', $new_competitor, 0x04);
                                        if( $rc['stat'] != 'ok' ) {   
                                            $err_msg = "Unable to add new {$s_competitor}. Please try again, or contact us for help.";
                                            error_log(print_r($rc['err'], true));
                                        } else {
                                            $update_args[$field] = $rc['id'];
                                            $competitors[$rc['id']] = $new_competitor;
                                        }
                                    }
                                } elseif( $_POST[$field] != $registration[$field] ) {
                                    $update_args[$field] = $_POST[$field];
                                }
                            }
                        }
                    }
/*                    if( isset($_POST['teacher_customer_id']) ) {
                        if( $_POST['teacher_customer_id'] == 'new' ) {
                            ciniki_core_loadMethod($ciniki, 'ciniki', 'writingfestivals', 'web', 'teacherCreate');
                            $rc = ciniki_writingfestivals_web_teacherCreate($ciniki, $settings, $tnid, $_POST);
                            if( $rc['stat'] == 'error' ) {
                                $err_msg = $rc['err']['msg'];
                            } elseif( $rc['stat'] != 'ok' ) {
                                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.188', 'msg'=>'Unable to update registration', 'err'=>$rc['err']));
                            }
                            if( $err_msg != '' ) {
                                $update_args['teacher_customer_id'] = $rc['teacher_customer_id'];
                            }
                        } elseif( $_POST['teacher_customer_id'] != $registration['teacher_customer_id'] ) {
                            $update_args['teacher_customer_id'] = $_POST['teacher_customer_id'];
                        }
                    } */
                    foreach(['title', 'word_count'] as $field) {
                        if( isset($_POST[$field]) && $_POST[$field] != $registration[$field] ) {
                            $update_args[$field] = $_POST[$field];
                        }
                    }
                    if( $err_msg == '' && count($update_args) > 0 ) {
                        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
                        $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.writingfestivals.registration',
                            $registration_id, $update_args, 0x04);
                        if( $rc['stat'] != 'ok' ) {
                            $err_msg = "We had an error saving the registration. Please try again, or contact us for help.";
                        } else {
                            //
                            // Update the display_name for the registration
                            //
                            ciniki_core_loadMethod($ciniki, 'ciniki', 'writingfestivals', 'private', 'registrationNameUpdate');
                            $rc = ciniki_writingfestivals_registrationNameUpdate($ciniki, $tnid, $registration_id);
                            if( $rc['stat'] != 'ok' ) {
                                error_log('Unable to update registration name');
                            }
                            if( !isset($_POST['ceditid']) || $_POST['ceditid'] == '' ) {
                                header('Location: ' . $args['base_url']);
                                exit;
                            }
                        }
                    } elseif( $err_msg == '' && isset($_POST['save']) ) {
                        //
                        // Update the display_name for the registration
                        //
                        ciniki_core_loadMethod($ciniki, 'ciniki', 'writingfestivals', 'private', 'registrationNameUpdate');
                        $rc = ciniki_writingfestivals_registrationNameUpdate($ciniki, $tnid, $registration_id);
                        if( $rc['stat'] != 'ok' ) {
                            error_log('Unable to update registration name');
                        }
                        if( !isset($_POST['ceditid']) || $_POST['ceditid'] == '' ) {
                            header('Location: ' . $args['base_url']);
                            exit;
                        }
                    }
                }
            }
        } 
        //
        // Check if a new registration has been submitted
        //
        elseif( isset($_POST['section']) && isset($_POST['title']) ) {
            //
            // Check required fields were submitted
            //
            $required = array();
            if( $class == null ) {
                $err_msg = "You must pick a class to register for.";
            } elseif( !isset($_POST['competitor1_id']) || $_POST['competitor1_id'] == '' || $_POST['competitor1_id'] == '0' ) {
                $err_msg = "You must choose a {$s_competitor} or add a new {$s_competitor}.";
            } elseif( isset($_POST['competitor1_id']) && $_POST['competitor1_id'] == 'new'
                && (!isset($_POST['name1']) || $_POST['name1'] == '' )
                ) {
                $err_msg = "You must enter a competitors name";
            } elseif( isset($_POST['competitor1_id']) && $_POST['competitor1_id'] == 'new'
                && (!isset($_POST['phone_home1']) || $_POST['phone_home1'] == '' )
                && (!isset($_POST['phone_cell1']) || $_POST['phone_cell1'] == '' )
                ) {
                $err_msg = "You must enter a phone number for the first child";
            } elseif( isset($_POST['competitor2_id']) && $_POST['competitor2_id'] == 'new'
                && (!isset($_POST['phone_home2']) || $_POST['phone_home2'] == '' )
                && (!isset($_POST['phone_cell2']) || $_POST['phone_cell2'] == '' )
                ) {
                $err_msg = "You must enter a phone number for the second child";
            } elseif( isset($_POST['competitor3_id']) && $_POST['competitor3_id'] == 'new'
                && (!isset($_POST['phone_home3']) || $_POST['phone_home3'] == '' )
                && (!isset($_POST['phone_cell3']) || $_POST['phone_cell3'] == '' )
                ) {
                $err_msg = "You must enter a phone number for the third child";
            } /*elseif( isset($_POST['teacher_customer_id']) && $_POST['teacher_customer_id'] == 'new'
                && (!isset($_POST['teacher_name']) || $_POST['teacher_name'] == '' )
                ) {
                $err_msg = "You must enter a name for the teacher";
            } elseif( isset($_POST['teacher_customer_id']) && $_POST['teacher_customer_id'] == 'new'
                && (!isset($_POST['teacher_email']) || $_POST['teacher_email'] == '' )
                ) {
                $err_msg = "You must enter an email address for the teacher";
            } elseif( $customer_type != 20 && (!isset($_POST['teacher_customer_id']) || $_POST['teacher_customer_id'] == '' || $_POST['teacher_customer_id'] == '0') ) {
                $err_msg = "You must choose specify a teacher.";
            } */

            if( isset($_POST['competitor1_id']) && $_POST['competitor1_id'] == 'new' ) {
                $required['name1'] = 'You must enter a competitors name';
                if( $customer_type == 10 || $customer_type == 20 ) {
                    $required['parent1'] = 'You must enter the parents name';
                }
                $required['city1'] = 'You must enter an city';
                $required['email1'] = 'You must enter an email address';
                $required['age1'] = 'You must enter this competitors age';
                $required['waiver_signed1'] = 'You must agree to "' . $args['settings']['waiver-msg'] . '"';
            }
            if( isset($_POST['competitor2_id']) && $_POST['competitor2_id'] == 'new' ) {
                $required['name2'] = 'You must enter a competitors name';
                if( $customer_type == 10 || $customer_type == 20 ) {
                    $required['parent2'] = 'You must enter the parents name';
                }
                $required['city2'] = 'You must enter an city';
                $required['email2'] = 'You must enter an email address';
                $required['age2'] = 'You must enter this competitors age';
                $required['waiver_signed2'] = 'You must agree to "' . $args['settings']['waiver-msg'] . '"';
            }
            if( isset($_POST['competitor3_id']) && $_POST['competitor3_id'] == 'new' ) {
                $required['name3'] = 'You must enter a competitors name';
                if( $customer_type == 10 || $customer_type == 20 ) {
                    $required['parent3'] = 'You must enter the parents name';
                }
                $required['city3'] = 'You must enter an city';
                $required['email3'] = 'You must enter an email address';
                $required['age3'] = 'You must enter this competitors age';
                $required['waiver_signed3'] = 'You must agree to "' . $args['settings']['waiver-msg'] . '"';
            }

            foreach($required as $rf => $msg) {
                if( isset($_POST['competitor1_id']) && $_POST['competitor1_id'] == 'new' 
                    && (!isset($_POST[$rf]) || trim($_POST[$rf]) == '' ) 
                    ) {
                    $err_msg = $msg;
                    break;
                }
            }

            //
            // Check for new competitors
            //
            $registration = array(
                'festival_id' => $args['festival_id'],
//                'teacher_customer_id' => ($customer_type == 20 ? $ciniki['session']['customer']['id'] : (isset($_POST['teacher_customer_id']) ? $_POST['teacher_customer_id'] : 0)),
                'billing_customer_id' => $ciniki['session']['customer']['id'],
                'rtype' => 30,
                'status' => 5,
                'invoice_id' => 0,
                'display_name' => '',
                'public_name' => '',
                'competitor1_id' => (isset($_POST['competitor1_id']) ? $_POST['competitor1_id'] : 0),
                'competitor2_id' => (isset($_POST['competitor2_id']) ? $_POST['competitor2_id'] : 0),
                'competitor3_id' => (isset($_POST['competitor3_id']) ? $_POST['competitor3_id'] : 0),
                'competitor4_id' => (isset($_POST['competitor4_id']) ? $_POST['competitor4_id'] : 0),
                'competitor5_id' => (isset($_POST['competitor5_id']) ? $_POST['competitor5_id'] : 0),
                'class_id' => $class['id'],
                'timeslot_id' => 0,
                'title' => (isset($_POST['title']) ? $_POST['title'] : ''),
                'word_count' => (isset($_POST['word_count']) ? $_POST['word_count'] : ''),
                'fee' => $class['fee'],
                'payment_type' => 0,
                'notes' => (isset($_POST['notes']) ? $_POST['notes'] : ''),
                );
            if( ($class['flags']&0x20) == 0x20 ) {
                $registration['rtype'] = 60;
            } elseif( ($class['flags']&0x10) == 0x10 ) {
                $registration['rtype'] = 50;
            }

            if( $err_msg == '' ) {
                for($i = 1; $i <= 3; $i++) {
                    $field = "competitor{$i}_id";
                    //
                    // Skip competitor2, 3 if not required for class
                    //
                    if( ($i == 2 && ($class['flags']&0x10) == 0) || ($i == 3 && ($class['flags']&0x20) == 0) ) {
                        continue;
                    }
                    if( isset($_POST[$field]) ) {
                        if( $_POST[$field] == 'new' ) {
                            $required = array();
                            $required['name'] = 'You must enter a competitors name';
                            if( $customer_type == 10 || $customer_type == 20 ) {
                                $required['parent'] = 'You must enter the parents name';
                            }
                            $required['city'] = 'You must enter an city';
                            $required['email'] = 'You must enter an email address';
                            $required['age'] = 'You must enter this competitors age';
                            $required['waiver_signed'] = 'You must agree to "' . $args['settings']['waiver-msg'] . '"';
                            $err_msg = '';
                            foreach($required as $rf => $msg) {
                                if( !isset($_POST[$rf . $i]) || trim($_POST[$rf . $i]) == '' ) {
                                    $err_msg = $msg;
                                    break;
                                }
                            }
                            //
                            // Add new competitor
                            //
                            if( $err_msg != '' ) {
                                $blocks[] = array('type'=>'formmessage', 'level'=>'error', 'message'=>$err_msg);
                            } else {
                                $new_competitor = array(
                                    'festival_id' => $args['festival_id'],
                                    'billing_customer_id' => $ciniki['session']['customer']['id'],
                                    'name' => $_POST["name{$i}"],
                                    'parent' => (isset($_POST["parent{$i}"]) ? $_POST["parent{$i}"] : ''),
                                    'address' => (isset($_POST["address{$i}"]) ? $_POST["address{$i}"] : ''),
                                    'city' => (isset($_POST["city{$i}"]) ? $_POST["city{$i}"] : ''),
                                    'province' => (isset($_POST["province{$i}"]) ? $_POST["province{$i}"] : ''),
                                    'postal' => (isset($_POST["postal{$i}"]) ? $_POST["postal{$i}"] : ''),
                                    'phone_home' => (isset($_POST["phone_home{$i}"]) ? $_POST["phone_home{$i}"] : ''),
                                    'phone_cell' => (isset($_POST["phone_cell{$i}"]) ? $_POST["phone_cell{$i}"] : ''),
                                    'email' => (isset($_POST["email{$i}"]) ? $_POST["email{$i}"] : ''),
                                    'age' => (isset($_POST["age{$i}"]) ? $_POST["age{$i}"] : ''),
                                    'notes' => (isset($_POST["notes{$i}"]) ? $_POST["notes{$i}"] : ''),
                                    );
                                if( isset($_POST["waiver_signed{$i}"]) && $_POST["waiver_signed{$i}"] == 'signed' ) {
                                    $new_competitor['flags'] = 0x01;
                                } else {
                                    $new_competitor['flags'] = 0;
                                }
                                ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
                                $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.writingfestivals.competitor', $new_competitor, 0x04);
                                if( $rc['stat'] != 'ok' ) {   
                                    $err_msg = "Unable to add new {$s_competitor}. Please try again, or contact us for help.";
                                    error_log(print_r($rc['err'], true));
                                } else {
                                    $registration[$field] = $rc['id'];
                                }
                            }
                        }
                    }
                }
            }
            //
            // FIXME: Check if there's a teacher to add
            //
/*            if( $err_msg == '' && isset($_POST['teacher_customer_id']) ) {
                if( $_POST['teacher_customer_id'] == 'new' ) {
                    ciniki_core_loadMethod($ciniki, 'ciniki', 'writingfestivals', 'web', 'teacherCreate');
                    $rc = ciniki_writingfestivals_web_teacherCreate($ciniki, $settings, $tnid, $_POST);
                    if( $rc['stat'] == 'error' ) {
                        $err_msg = $rc['err']['msg'];
                    } elseif( $rc['stat'] != 'ok' ) {
                        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.145', 'msg'=>'Unable to update registration', 'err'=>$rc['err']));
                    }
                    $registration['teacher_customer_id'] = $rc['teacher_customer_id'];
                } elseif( $_POST['teacher_customer_id'] != $registration['teacher_customer_id'] ) {
                    $registration['teacher_customer_id'] = $_POST['teacher_customer_id'];
                }
            } */

            //
            // Add the registration
            //
            if( $err_msg == '' ) {  
                //
                // Get the UUID so it can be used later
                //
                ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUUID');
                $rc = ciniki_core_dbUUID($ciniki, 'ciniki.writingfestivals');
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.129', 'msg'=>'', 'err'=>$rc['err']));
                }
                $registration['uuid'] = $rc['uuid'];

                //
                // Add the regisrations
                //
                ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
                $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.writingfestivals.registration', $registration, 0x04);
                if( $rc['stat'] != 'ok' ) {   
                    $err_msg = "I'm sorry, we had a problem saving your registration. Please try again or contact us for help.";
                    error_log(print_r($rc['err'], true));
                } else {
                    //
                    // Update the display_name for the registration
                    //
                    ciniki_core_loadMethod($ciniki, 'ciniki', 'writingfestivals', 'private', 'registrationNameUpdate');
                    $rc = ciniki_writingfestivals_registrationNameUpdate($ciniki, $tnid, $rc['id']);
                    if( $rc['stat'] != 'ok' ) {
                        error_log('Unable to update registration name');
                    }

                    //
                    // The registration was added, now redirect to the registration list
                    //
                    $args['r'] = $registration['uuid'];
                    if( !isset($_POST['ceditid']) || $_POST['ceditid'] == '' ) {
                        header("Location: " . $args['base_url']);
                        exit;
                    }
                }
            }
        }

        //
        // Check if error produced
        //
        if( $err_msg != '' ) {
            $blocks[] = array('type'=>'formmessage', 'level'=>'error', 'message'=>$err_msg);
        }
    }

    //
    // Check if a register link was followed
    //
    if( ($args['festival_flags']&0x01) == 0x01 && isset($_GET['cl']) && $_GET['cl'] != '' ) {
        $display = 'registration-form';
    } 

    //
    // Check if a competitor edit has been requested. The registration form will have been saved above
    // so any changes to title, word_count will be saved before editing the competitor details.
    //
    if( isset($_POST['ceditid']) && $_POST['ceditid'] != '' ) {
        $display = 'competitor-form';
        $competitor_id = 0;
        //
        // Setup required fields
        //
        $required = array();
        $required['name'] = 'You must enter a competitors name';
        if( $customer_type == 10 || $customer_type == 20 ) {
            $required['parent'] = 'You must enter the parents name';
        }
        $required['city'] = 'You must enter an city';
        $required['email'] = 'You must enter an email address';
        $required['age'] = 'You must enter this competitors age';
        $required['waiver_signed'] = 'You must agree to "' . $args['settings']['waiver-msg'] . '"';
        $err_msg = '';
        if( $_POST['ceditid'] != 'new' ) {
            foreach($competitors as $competitor) {
                if( $competitor['uuid'] == $_POST['ceditid'] ) {
                    $competitor_id = $competitor['id'];

                    //
                    // Check for updates
                    //
                    if( isset($_POST['save']) && $_POST['save'] == 'Save' ) {
                        $update_args = array();
                        if( isset($_POST["waiver_signed"]) && $_POST["waiver_signed"] == 'signed' && ($competitor['flags']&0x01) == 0 ) {
                            $update_args['flags'] = $competitor['flags'] | 0x01;
                        } elseif( !isset($_POST['waiver_signed']) ) {
                            $err_msg = $required['waiver_signed'];
                        }
                        foreach(['name', 'parent', 'address', 'city', 'postal', 'phone_home', 'phone_cell', 'email', 'age', 'notes'] as $field) {
                            if( isset($_POST[$field]) && $_POST[$field] != $competitor[$field] ) {
                                if( isset($required[$field]) && trim($_POST[$field]) == '' ) {
                                    $err_msg = $required[$field];
                                    break;
                                } else {
                                    $update_args[$field] = $_POST[$field];
                                }
                            } elseif( isset($required[$field]) && trim($competitor[$field]) == '' ) {
                                $err_msg = $required[$field];
                                break;
                            }
                        }
                        if( $err_msg != '' ) {
                            $blocks[] = array('type'=>'formmessage', 'level'=>'error', 'message'=>$err_msg);
                        } elseif( $err_msg == '' && count($update_args) > 0 ) {
                            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
                            $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.writingfestivals.competitor',
                                $competitor_id, $update_args, 0x04);
                            if( $rc['stat'] != 'ok' ) {
                                $err_msg = "We had an error saving the changes. Please try again, or contact us for help.";
                            } else {
                                //
                                // Update the display_name for the registration
                                //
                                ciniki_core_loadMethod($ciniki, 'ciniki', 'writingfestivals', 'private', 'competitorUpdateNames');
                                $rc = ciniki_writingfestivals_competitorUpdateNames($ciniki, $tnid, $args['festival_id'], $competitor_id);
                                if( $rc['stat'] != 'ok' ) {
                                    error_log('Unable to update competitor name');
                                }
                                if( isset($_POST['r']) && $_POST['r'] != '' ) {
                                    header('Location: ' . $args['base_url'] . '?r=' . $_POST['r']);
                                } elseif( isset($args['r']) && $args['r'] != '' ) {
                                    header('Location: ' . $args['base_url'] . '?r=' . $args['r']);
                                } else {
                                    header('Location: ' . $args['base_url']);
                                }
                                exit;
                            }
                        } elseif( isset($_POST['save']) ) {
                            if( isset($_POST['r']) && $_POST['r'] != '' ) {
                                header('Location: ' . $args['base_url'] . '?r=' . $_POST['r']);
                            } elseif( isset($args['r']) && $args['r'] != '' ) {
                                header('Location: ' . $args['base_url'] . '?r=' . $args['r']);
                            } else {
                                header('Location: ' . $args['base_url']);
                            }
                            exit;
                        }
                    }
                }
            }
        }
    }

    //
    // Display the list of registrations
    //
    if( $display == 'registration-list' ) {
        $block = array('type'=>'foldingtable',
            'section' => 'registration-list',
            'headers' => 'yes',
            'folded-labels' => 'yes',
            'editable' => 'no',
            'columns' => array( 
                array('label'=>$p_competitor, 'field'=>'display_name', 'class'=>''),
                array('label'=>'Class', 'field'=>'class_code', 'fold'=>'yes', 'class'=>''),
                array('label'=>'Title', 'field'=>'title', 'fold'=>'yes', 'class'=>''),
                array('label'=>'Fee', 'field'=>'fee_display', 'fold'=>'yes', 'class'=>''),
                array('label'=>'Status', 'field'=>'status_text', 'fold'=>'yes', 'class'=>''),
                ),
            'rows' => $registrations,
            );
        if( ($args['festival_flags']&0x01) == 0x01 ) {
            $block['editable'] = 'yes';
            $block['add'] = array('label' => '+ Add Registration', 'url' => $args['base_url'] . '?r=new');
        }
        $blocks[] = $block;

        //
        // Get the total of unpaid registrations
        //
        $total = 0;
        foreach($registrations as $reg) {
            if( $reg['status'] == 5 ) {
                $total = bcadd($total, $reg['fee'], 6);
            }
        }
        if( $total > 0 ) {
            $content = "<p class='wide'>You have $" . number_format($total, 2) . " in unpaid registrations. "
                . "When you are ready to pay, click Add to Cart button below and checkout."
                . "<br/><b>Please Note: Once added to your cart you will no longer be able to make changes to the registrations.</b></p>"
//                . "<p class='wide alignright'><button class='button' href='" . $args['base_url'] . "?checkout'>Checkout</button></p>"
                . "<form class='wide' method='POST'>"
//                    . "<input type='hidden' name='action' value='checkout'>"
                    . "<div class='submit alignright wide'>"
                    . "<input class='submit' type='submit' name='action' value='Add to Cart' />"
                . "</div></form>"
                . "";
            $blocks[] = array('type'=>'content', 'html'=>$content);
        }

        //
        // If a teacher, provide list of parents and how much each owes
        //
        if( $customer_type == 20 && count($parents) > 0 ) {
            foreach($parents as $pid => $parent) {
                $parents[$pid]['total_fees_display'] = '$' . number_format($parent['total_fees'], 2);
            }
            $blocks[] = array('type'=>'foldingtable',
                'section' => 'registration-list',
                'headers' => 'yes',
                'folded-labels' => 'yes',
                'label-width' => '75%',
                'value-width' => '25%',
                'editable' => 'no',
                'columns' => array( 
                    array('label'=>'Parent', 'field'=>'name', 'class'=>''),
                    array('label'=>'# of Registrations', 'field'=>'num_registrations', 'fold'=>'yes', 'class'=>''),
                    array('label'=>'Total Fees', 'field'=>'total_fees_display', 'fold'=>'yes', 'class'=>''),
                    ),
                'rows' => $parents,
                );
            $content = ""
                . "<form class='wide' method='POST'>"
                    . "<div class='submit alignright wide'>"
                    . "<input class='submit' type='submit' name='action' value='Download Printable PDF' />"
                . "</div></form>"
                . "";
            $blocks[] = array('type'=>'content', 'html'=>$content);
        }
    }
    
    //
    // Display the registration form for new registration or edit registration
    //
    if( $display == 'registration-form' ) {
        $form = "<form id='registration-form' class='registration-form medium' action='' method='POST'>";
        if( isset($registrations[$registration_id]) ) {
            $registration = $registrations[$registration_id];
        } else {
            $registration = array(
                'status' => 0,
                'teacher_customer_id' => 0,
                'class_id' => 0,
                'competitor1_id' => 0,
                'competitor2_id' => 0,
                'competitor3_id' => 0,
                'title' => '',
                'word_count' => '',
                );
        }

        //
        // The basic structure of the registration form
        //
        $form_sections = array(
            'cedit' => array(
                'label' => '',
                'visible' => 'no',
                'type' => 'hidden',
                'fields' => array(
                    'ceditid' => array('type' => 'hidden', 'value' => ''),
                    ),
                ),
            'class' => array(
                'label' => 'Class',
                'fields' => array(
                    'section' => array('type'=>'select', 
                        'label'=>array('title'=>'Section', 'class'=>'hidden'),
                        'size' => 'small', 
                        'options'=>array(),
                        ),
                    // More dropdown fields added here for each section of classes
                    ),
                ),
            'competitor1' => array(
                'label' => $s_competitor,
                'visible' => 'yes',
                'fields' => array(),
                ),
            'competitor2' => array(
                'label' => $s_competitor . ' 2',
                'visible' => 'no',
                'fields' => array(),
                ),
            'competitor3' => array(
                'label' => $s_competitor . ' 3',
                'visible' => 'no',
                'fields' => array(),
                ),
/*            'teacher' => array(
                'label' => 'Teacher',
                'visible' => 'no',
                'fields' => array(),
                ), */
            'performance' => array(
                'label' => 'Performing',
                'fields' => array(
                    'title' => array('type'=>'text', 
                        'label' => array('title'=>'Title'), 
                        'size' => 'large',
                        'value' => (isset($_POST['title']) ? $_POST['title'] : $registration['title'])),
                    'word_count' => array('type'=>'text', 
                        'label' => array('title'=>'Word Count'), 
                        'size' => 'small',
                        'value' => (isset($_POST['word_count']) ? $_POST['word_count'] : $registration['word_count'])),
                    ),
                ),
            ); 

        for($i = 1; $i <= 3; $i++) {
            //
            // Prefill the address, parent from customer details
            //
            $competitor = array( 
                'name' => '',
                'parent' => '',
                'address' => '',
                'city' => '',
                'province' => '',
                'postal' => '', 
                'phone_home' => '', 
                'phone_cell' => '', 
                'email' => '', 
                'age' => '', 
                'notes' => '', 
                'waiver_signed' => 'no', 
                );
            if( $customer_type == 10 ) {
                $competitor['parent'] = $customer['first'] . ' ' . $customer['last'];
            } elseif( $customer_type == 30 ) {
                $competitor['name'] = $customer['first'] . ' ' . $customer['last'];
                $competitor['parent'] = '';
            }
            if( $customer_type == 10 || $customer_type == 30 ) {
                //
                // Load the default values for parent/adult
                //
                if( isset($customer['addresses']) ) {
                    foreach($customer['addresses'] as $address) {
                        if( ($address['address']['flags']&0x07) > 0 ) {
                            $competitor['address'] = $address['address']['address1'];
                            $competitor['city'] = $address['address']['city'];
                            $competitor['province'] = $address['address']['province'];
                            $competitor['postal'] = $address['address']['postal'];
                            break;
                        }
                    }
                }
                if( isset($customer['phones']) ) {
                    foreach($customer['phones'] as $phone) {
                        if( $phone['phone_label'] == 'Home' ) {
                            $competitor['phone_home'] = $phone['phone_number'];
                        }
                        if( $phone['phone_label'] == 'Cell' ) {
                            $competitor['phone_cell'] = $phone['phone_number'];
                        }
                    }
                }
                if( isset($customer['emails'][0]['email']['address']) ) {
                    $competitor['email'] = $customer['emails'][0]['email']['address'];
                }
            }
            $competitor = array(
                'name' => (isset($_POST["name{$i}"]) ? $_POST["name{$i}"] : $competitor['name']),
                'parent' => (isset($_POST["parent{$i}"]) ? $_POST["parent{$i}"] : $competitor['parent']),
                'address' => (isset($_POST["address{$i}"]) ? $_POST["address{$i}"] :$competitor['address']), 
                'city' => (isset($_POST["city{$i}"]) ? $_POST["city{$i}"] :$competitor['city']), 
                'province' => (isset($_POST["province{$i}"]) ? $_POST["province{$i}"] :$competitor['province']), 
                'postal' => (isset($_POST["postal{$i}"]) ? $_POST["postal{$i}"] :$competitor['postal']), 
                'phone_home' => (isset($_POST["phone_home{$i}"]) ? $_POST["phone_home{$i}"] :$competitor['phone_home']), 
                'phone_cell' => (isset($_POST["phone_cell{$i}"]) ? $_POST["phone_cell{$i}"] :$competitor['phone_cell']), 
                'email' => (isset($_POST["email{$i}"]) ? $_POST["email{$i}"] : $competitor['email']), 
                'age' => (isset($_POST["age{$i}"]) ? $_POST["age{$i}"] :$competitor['age']), 
                'notes' => (isset($_POST["notes{$i}"]) ? $_POST["notes{$i}"] :$competitor['notes']), 
                'waiver_signed' => (isset($_POST["waiver_signed{$i}"]) ? $_POST["waiver_signed{$i}"] :$competitor['waiver_signed']), 
                );
//            if( isset($competitors[$registration["competitor{$i}_id"]]) ) {
//                $competitor = $competitors[$registration["competitor{$i}_id"]];
//            }

            if( $customer_type == 10 || $customer_type == 20 ) {
                $form_sections["competitor{$i}"]['fields'] = array(
                    "competitor{$i}_id" => array('type'=>'select', 
                        'label' => array('title'=>$s_competitor, 'class'=>'hidden'),
                        'size' => 'full', 
                        'options' => array(),
                        'details' => array(),
                        ),
                    "name{$i}"=>array('type'=>'text', 'visible'=>'no', 'label'=>array('title'=>'Name'), 'size'=>'medium', 'value'=>$competitor['name']),
                    "parent{$i}"=>array('type'=>'text', 'visible'=>'no', 'label'=>array('title'=>'Parent'), 'size'=>'medium', 'value'=>$competitor['parent']),
                    "address{$i}"=>array('type'=>'text', 'visible'=>'no', 'label'=>array('title'=>'Address'), 'size'=>'full', 'value'=>$competitor['address']),
                    "city{$i}"=>array('type'=>'text', 'visible'=>'no', 'label'=>array('title'=>'City'), 'size'=>'medium', 'value'=>$competitor['city']),
                    "province{$i}"=>array('type'=>'text', 'visible'=>'no', 'label'=>array('title'=>'Province'), 'size'=>'small', 'value'=>$competitor['province']),
                    "postal{$i}"=>array('type'=>'text', 'visible'=>'no', 'label'=>array('title'=>'Postal'), 'size'=>'small', 'value'=>$competitor['postal']),
                    "phone_home{$i}"=>array('type'=>'text', 'visible'=>'no', 'label'=>array('title'=>'Home Phone'), 'size'=>'small', 'value'=>$competitor['phone_home']),
                    "phone_cell{$i}"=>array('type'=>'text', 'visible'=>'no', 'label'=>array('title'=>'Cell Phone'), 'size'=>'small', 'value'=>$competitor['phone_cell']),
                    "email{$i}"=>array('type'=>'text', 'visible'=>'no', 'label'=>array('title'=>'Email'), 'size'=>'medium', 'value'=>$competitor['email']),
                    "age{$i}"=>array('type'=>'text', 'visible'=>'no', 'label'=>array(
                        'title'=>'Age' . ($args['settings']['age-restriction-msg'] != '' ? ' ' . $args['settings']['age-restriction-msg'] : '')),
                        'size'=>'small', 'value'=>$competitor['age']),
                    "notes{$i}"=>array('type'=>'text', 'visible'=>'no', 'label'=>array('title'=>'Notes'), 'size'=>'full', 'value'=>$competitor['notes']),
                    "waiver_signed{$i}"=>array('type'=>'checkbox', 'visible'=>'no', 'label'=>array('title'=>$args['settings']['waiver-title']), 'size'=>'full', 'checked_value'=>'signed', 'value'=>$competitor['waiver_signed'], 'msg'=>$args['settings']['waiver-msg']),
                    );
            } else {
                $form_sections["competitor{$i}"]['fields'] = array(
                    "competitor{$i}_id" => array('type'=>'select', 
                        'label' => array('title'=>$s_competitor, 'class'=>'hidden'),
                        'size' => 'full', 
                        'options' => array(),
                        'details' => array(),
                        ),
                    "name{$i}"=>array('type'=>'text', 'visible'=>'no', 'label'=>array('title'=>'Name'), 'size'=>'full', 'value'=>$competitor['name']),
                    "address{$i}"=>array('type'=>'text', 'visible'=>'no', 'label'=>array('title'=>'Address'), 'size'=>'full', 'value'=>$competitor['address']),
                    "city{$i}"=>array('type'=>'text', 'visible'=>'no', 'label'=>array('title'=>'City'), 'size'=>'medium', 'value'=>$competitor['city']),
                    "province{$i}"=>array('type'=>'text', 'visible'=>'no', 'label'=>array('title'=>'Province'), 'size'=>'small', 'value'=>$competitor['province']),
                    "postal{$i}"=>array('type'=>'text', 'visible'=>'no', 'label'=>array('title'=>'Postal'), 'size'=>'small', 'value'=>$competitor['postal']),
                    "phone_home{$i}"=>array('type'=>'text', 'visible'=>'no', 'label'=>array('title'=>'Home Phone'), 'size'=>'small', 'value'=>$competitor['phone_home']),
                    "phone_cell{$i}"=>array('type'=>'text', 'visible'=>'no', 'label'=>array('title'=>'Cell Phone'), 'size'=>'small', 'value'=>$competitor['phone_cell']),
                    "email{$i}"=>array('type'=>'text', 'visible'=>'no', 'label'=>array('title'=>'Email'), 'size'=>'medium', 'value'=>$competitor['email']),
                    "age{$i}"=>array('type'=>'text', 'visible'=>'no', 'label'=>array(
                        'title'=>'Age' . ($args['settings']['age-restriction-msg'] != '' ? ' ' . $args['settings']['age-restriction-msg'] : '')),
                        'size'=>'small', 'value'=>$competitor['age']),
                    "notes{$i}"=>array('type'=>'text', 'visible'=>'no', 'label'=>array('title'=>'Notes'), 'size'=>'full', 'value'=>$competitor['notes']),
                    "waiver_signed{$i}"=>array('type'=>'checkbox', 'visible'=>'no', 'label'=>array('title'=>$args['settings']['waiver-title']), 'size'=>'full', 'checked_value'=>'signed', 'value'=>$competitor['waiver_signed'], 'msg'=>$args['settings']['waiver-msg']),
                    );
            }
        }
        
        //
        // Setup the teacher field
        //
/*        $teacher = array( 
            'name' => '',
            'email' => '',
            'phone' => '',
            );
        $teacher = array(
            'name' => (isset($_POST["teacher_name"]) ? $_POST["teacher_name"] : $teacher['name']),
            'email' => (isset($_POST["teacher_email"]) ? $_POST["teacher_email"] : $teacher['email']),
            'phone' => (isset($_POST["teacher_phone"]) ? $_POST["teacher_phone"] :$teacher['phone']), 
            ); 
        if( $customer_type == 10 || $customer_type == 30 ) {
            $form_sections["teacher"]['fields'] = array(
                "teacher_customer_id" => array('type'=>'select', 
                    'label' => array('title'=>'Teacher', 'class'=>'hidden'),
                    'size' => 'full', 
                    'options' => array(),
                    'details' => array(),
                    ),
                "teacher_name"=>array('type'=>'text', 'visible'=>'no', 'label'=>array('title'=>'Name'), 'size'=>'full', 'value'=>$teacher['name']),
                "teacher_email"=>array('type'=>'text', 'visible'=>'no', 'label'=>array('title'=>'Email'), 'size'=>'medium', 'value'=>$teacher['email']),
                "teacher_phone"=>array('type'=>'text', 'visible'=>'no', 'label'=>array('title'=>'Phone'), 'size'=>'medium', 'value'=>$teacher['phone']),
                );
            $form_sections['teacher']['visible'] = 'yes';
        } else {
            $form_sections['teacher']['visible'] = 'no';
        }
        */

        //
        // Build the drop down lists for section and classes
        //
        $section_ids = array();
        $selected_section_id = 0;
        $selected_flags = 0;
        $class_flags = array();
        foreach($sections as $section) {
            //
            // Set the default section
            //
            if( $selected_section_id == 0 ) {
                $selected_section_id = $section['id'];
            }
            $section_ids[] = $section['id'];
            //
            // Add the section classes array
            //
            $class_field = "section-{$section['id']}-class";
            $form_sections['class']['fields'][$class_field] = array(
                'type' => 'select', 
                'label' => 'Class',
                'visible' => 'no',
                'size' => 'large', 
                'options' => array(),
                );

            foreach($section['classes'] as $class) {
                $class_flags[$class['id']] = $class['flags'];
                if( $class['id'] == $registration['class_id'] 
                    || (isset($_GET['cl']) && $_GET['cl'] == $class['uuid']) 
                    ) {
                    $selected_section_id = $section['id'];
                    $selected_flags = $class['flags'];
                    $form_sections['class']['fields'][$class_field]['options'][] = array(
                        'value' => $class['id'],
                        'selected' => 'yes',
                        'label' => $class['code'] . ' - ' . $class['name'],
                        );
                    $form_sections['class']['fields'][$class_field]['visible'] = 'yes';
                    if( ($class['flags']&0x10) == 0x10 ) {
                        $form_sections['competitor2']['visible'] = 'yes';
                    }
                    if( ($class['flags']&0x20) == 0x20 ) {
                        $form_sections['competitor3']['visible'] = 'yes';
                    }
                } else {
                    $form_sections['class']['fields'][$class_field]['options'][] = array(
                        'value' => $class['id'],
                        'label' => $class['code'] . ' - ' . $class['name'],
                        );
                }
            }
            //
            // Add the section option
            //
            $form_sections['class']['fields']['section']['options'][] = array(
                'value' => $section['id'],
                'selected' => ($selected_section_id == $section['id'] ? 'yes' : 'no'),
                'visible' => 'yes',
                'label' => $section['name'],
                'labelclass' => 'hidden',
                );
        }
        $form_sections['class']['fields']["section-{$selected_section_id}-class"]['visible'] = 'yes';

        //
        // Competitors
        //
        $curdetails = '';
        for($i = 1; $i <= 3; $i++) {
            $form_sections["competitor{$i}"]['fields']["competitor{$i}_id"]['options'][] = array('value'=>'', 'label'=>'');
            foreach($competitors as $competitor) {
                $form_sections["competitor{$i}"]['fields']["competitor{$i}_id"]['options'][] = array(
                    'value' => $competitor['id'],
                    'selected' => ($registration["competitor{$i}_id"] == $competitor['id'] ? 'yes' : 'no'),
                    'label' => $competitor['name'],
                    );
                if( $registration["competitor{$i}_id"] == $competitor['id'] ) {
                    $curdetails .= "competitor{$i}_id:'{$competitor['id']}',";
                }
                //
                // The extra details to be displayed below the drop down
                //
                $address = $competitor['address'];
                $address .= $competitor['city'] != '' ? ($address != '' ? ', ' : '') . $competitor['city'] : '';
                $address .= $competitor['province'] != '' ? ($address != '' ? ', ' : '') . $competitor['province'] : '';
                $address .= $competitor['postal'] != '' ? ($address != '' ? ', ' : '') . $competitor['postal'] : '';
                if( $customer_type == 10 || $customer_type == 20 ) {
                    $form_sections["competitor{$i}"]['fields']["competitor{$i}_id"]['details'][$competitor['id']] = array(
                        array('label'=>'Parent', 'value'=>$competitor['parent'], 'size'=>'full'),
                        array('label'=>'Address', 'value'=>$address, 'size'=>'full'),
                        array('label'=>'Home Phone', 'value'=>$competitor['phone_home'], 'size'=>'full'),
                        array('label'=>'Cell Phone', 'value'=>$competitor['phone_cell'], 'size'=>'full'),
                        array('label'=>'Email', 'value'=>$competitor['email'], 'size'=>'full'),
                        array('label'=>'Age', 'value'=>$competitor['age'], 'size'=>'full'),
    //                    array('type'=>'button', 'label'=>'Edit ' . $s_competitor, 'url'=>$args['base_url'] . "?r=" . $registration['uuid'] . "&c=" . $competitor['uuid']),
                        array('type'=>'button', 'label'=>'Edit ' . $s_competitor, 'url'=>'javascript: editComp("' . $competitor['uuid'] . '");'),
                        );
                } else {
                    $form_sections["competitor{$i}"]['fields']["competitor{$i}_id"]['details'][$competitor['id']] = array(
                        array('label'=>'Address', 'value'=>$address, 'size'=>'full'),
                        array('label'=>'Home Phone', 'value'=>$competitor['phone_home'], 'size'=>'full'),
                        array('label'=>'Cell Phone', 'value'=>$competitor['phone_cell'], 'size'=>'full'),
                        array('label'=>'Email', 'value'=>$competitor['email'], 'size'=>'full'),
                        array('label'=>'Age', 'value'=>$competitor['age'], 'size'=>'full'),
    //                    array('type'=>'button', 'label'=>'Edit ' . $s_competitor, 'url'=>$args['base_url'] . "?r=" . $registration['uuid'] . "&c=" . $competitor['uuid']),
                        array('type'=>'button', 'label'=>'Edit ' . $s_competitor, 'url'=>'javascript: editComp("' . $competitor['uuid'] . '");'),
                        );
                }
            }
            if( $customer_type == 10 || $customer_type == 20 ) {
                $form_sections["competitor{$i}"]['fields']["competitor{$i}_id"]['options'][] = array(
                    'value'=>'new', 
                    'selected'=>(isset($_POST["competitor{$i}_id"]) && $_POST["competitor{$i}_id"] == 'new' ? 'yes' : 'no'),
                    'label'=>'Add ' . $s_competitor,
                    );
            } else {
                $form_sections["competitor{$i}"]['fields']["competitor{$i}_id"]['options'][] = array(
                    'value'=>'new', 
                    'selected'=>(isset($_POST["competitor{$i}_id"]) && $_POST["competitor{$i}_id"] == 'new' ? 'yes' : 'no'),
                    'label'=>'Add Competitor',
                    );
            }
        }

        //
        // Teacher
        //
/*        if( $customer_type == 10 || $customer_type == 30 ) {
            $form_sections['teacher']['fields']['teacher_customer_id']['options'][] = array('value'=>'', 'label'=>'');
            foreach($teachers as $teacher) {
                $form_sections['teacher']['fields']['teacher_customer_id']['options'][] = array(
                    'value' => $teacher['id'],
                    'selected' => ($registration['teacher_customer_id'] == $teacher['id'] ? 'yes' : 'no'),
                    'label' => $teacher['name'],
                    );
                if( $registration['teacher_customer_id'] == $teacher['id'] ) {
                    $curdetails .= "teacher_customer_id:'{$teacher['id']}',";
                }
            }
            $form_sections['teacher']['fields']['teacher_customer_id']['options'][] = array(
                'value'=>'new', 
                'selected'=>(isset($_POST['teacher_customer_id']) && $_POST['teacher_customer_id'] == 'new' ? 'yes' : 'no'),
                'label'=>'Add Teacher',
                );
        } */


        $buttons = array(
            'save' => array('type'=>'submit', 'label'=>'Save'),
            );
        if( $registration['status'] == 5 && !isset($_POST['delete']) ) {
            $buttons['delete'] = array('type'=>'submit', 'class'=>'delete', 'label'=>'Delete');
        } elseif( $registration['status'] == 5 && isset($_POST['delete']) && $_POST['delete'] == 'Delete' ) {
            $buttons['delete'] = array('type'=>'submit', 'class'=>'delete', 'label'=>'Confirm');
        }

        if( $customer_type == 10 || $customer_type == 20 ) {
            $cfields = "'name','parent','address','city','province','postal','phone_home','phone_cell','email','age','notes','waiver_signed'";
        } else {
            $cfields = "'name','address','city','province','postal','phone_home','phone_cell','email','age','notes','waiver_signed'";
        }

        $blocks[] = array('type' => 'registrationform', 
            'id' => 'mrf',
            'action' => '',
            'method' => 'POST',
            'sections' => $form_sections,
            'onchange' => 'regFormUpdate()',
            'buttons' => $buttons,
            'javascript' => ""
                . "var cflags = " . json_encode($class_flags) . ";"
                . "var sids = [" . implode(',', $section_ids) . "];"
                . "var cfields = [{$cfields}];"
//                . "var tfields = ['teacher_name', 'teacher_email', 'teacher_phone'];"
                . "var curdetails = {{$curdetails}};"
                . "function regFormUpdate() {"
                    //
                    // Check if competitor should be added
                    //
                    . "var s=document.getElementById('section').value;"
                    . "var c=0;"
                    . "for(var i in sids){"
                        . "var e=document.getElementById('section-' + sids[i] + '-class-wrap');"
                        . "if(s==sids[i]){"
                            . "var v=document.getElementById('section-' + sids[i] + '-class');"
                            . "c=v.value;"
                            . "e.classList.contains('hidden') ? e.classList.remove('hidden') : '';"
                        . "}else{"
                            . "e.classList.contains('hidden') ? '' : e.classList.add('hidden');"
                        . "}"
                    . "}"
                    //
                    // Check how many competitors there should be
                    //
                    . "if(cflags[c] != null){"
                        . "var e=document.getElementById('competitor2');"
                        . "if( (cflags[c]&0x10) == 0x10 ) {"
                            . "e.classList.contains('hidden') ? e.classList.remove('hidden') : '';"
                        . "}else{"
                            . "e.classList.contains('hidden') ? '' : e.classList.add('hidden');"
                        . "}"
                        . "var e=document.getElementById('competitor3');"
                        . "if( (cflags[c]&0x20) == 0x20 ) {"
                            . "e.classList.contains('hidden') ? e.classList.remove('hidden') : '';"
                        . "}else{"
                            . "e.classList.contains('hidden') ? '' : e.classList.add('hidden');"
                        . "}"
                    . "}"
                    . "var f=document.getElementById('mrf');"
                    . "var c1=document.getElementById('competitor1_id');"
                    . "var c2=document.getElementById('competitor2_id');"
                    . "var c3=document.getElementById('competitor3_id');"
                    . "if(c1.value=='new'){"
                        . "showCompetitorAdd(1);"
                        . "hideCompetitorDetails(1);"
                    . "}else{"
                        . "hideCompetitorAdd(1);"
                        . "if(c1.value>0){"
                            . "showCompetitorDetails(1,c1.value);"
                        . "}else{"
                            . "hideCompetitorDetails(1);"
                        . "}"
                    . "}"
                    . "if(c2.value=='new'){"
                        . "showCompetitorAdd(2);"
                        . "hideCompetitorDetails(2);"
                    . "}else{"
                        . "hideCompetitorAdd(2);"
                        . "if(c2.value>0){"
                            . "showCompetitorDetails(2,c2.value);"
                        . "}"
                    . "}"
                    . "if(c3.value=='new'){"
                        . "showCompetitorAdd(3);"
                        . "hideCompetitorDetails(3);"
                    . "}else{"
                        . "hideCompetitorAdd(3);"
                        . "if(c3.value>0){"
                            . "showCompetitorDetails(3,c3.value);"
                        . "}"
                    . "}"
/*                    . "var t=document.getElementById('teacher_customer_id');"
                    . "if(t.value=='new') {"
                        . "for(var i in tfields){"
                            . "var e=document.getElementById(tfields[i] + '-wrap');"
                            . "e.classList.contains('hidden') ? e.classList.remove('hidden') : '';"
                        . "}"
                    . "}else{"
                        . "for(var i in tfields){"
                            . "var e=document.getElementById(tfields[i] + '-wrap');"
                            . "e.classList.contains('hidden') ? '' : e.classList.add('hidden');"
                        . "}" 
                    . "}" */
                . "};"
                . "function showCompetitorAdd(n){"
                    . "for(var i in cfields){"
                        . "var e=document.getElementById(cfields[i] + n + '-wrap');"
                        . "e.classList.contains('hidden') ? e.classList.remove('hidden') : '';"
                    . "}"
                . "};"
                . "function hideCompetitorAdd(n){"
                    . "for(var i in cfields){"
                        . "var e=document.getElementById(cfields[i] + n + '-wrap');"
                        . "e.classList.contains('hidden') ? '' : e.classList.add('hidden');"
                    . "}"
                . "};"
                . "function hideCompetitorDetails(n){"
                    . "if( curdetails['competitor'+n+'_id'] != null ) {"
                        . "var e=document.getElementById('competitor' + n + '_id-details-' + curdetails['competitor'+n+'_id']);"
                        . "if(e!=null){"
                            . "e.classList.contains('hidden') ? '' : e.classList.add('hidden');"
                        . "}"
                    . "}"
                . "}"
                . "function showCompetitorDetails(n,i){"
                    . "hideCompetitorDetails(n);"
                    . "curdetails['competitor'+n+'_id'] = i;"
                    . "var e=document.getElementById('competitor' + n + '_id-details-' + i);"
                    . "if(e!=null){"
                        . "e.classList.contains('hidden') ? e.classList.remove('hidden') : '';"
                    . "}"
                . "}"
/*                . "function showTeacher(){"
                    . "var e=document.getElementById(cfields[i] + n + '-wrap');"
                    . "e.classList.contains('hidden') ? e.classList.remove('hidden') : '';"
                . "};" */
                . "function hideCompetitorAdd(n){"
                    . "for(var i in cfields){"
                        . "var e=document.getElementById(cfields[i] + n + '-wrap');"
                        . "e.classList.contains('hidden') ? '' : e.classList.add('hidden');"
                    . "}"
                . "};"
                . "function editComp(n){"
                    . "var e=document.getElementById('ceditid');"
                    . "e.value=n;"
                    . "document.getElementById('mrf').submit();"
                . "}"
                . "window.onload = regFormUpdate;",
            );
    }

    //
    // Display the competitor form to add/edit a competitors details.
    //
    if( $display == 'competitor-form' ) {
        $competitor = $competitors[$competitor_id];
        $form_sections = array(
            'cedit' => array(
                'label' => '',
                'visible' => 'no',
                'type' => 'hidden',
                'fields' => array(
                    'ceditid' => array('type' => 'hidden', 'value' => $_POST['ceditid']),
                    'r' => array('type' => 'hidden', 'value' => (isset($registration['uuid']) ? $registration['uuid'] : $_GET['r'])),
                    ),
                ),
            'competitor' => array(
                'label' => $s_competitor,
                'visible' => 'yes',
                'fields' => array(
                    'name'=>array('type'=>'text',
                        'label'=>array('title'=>'Name'), 'size'=>'medium', 'value'=>$competitor['name']),
                    'parent'=>array('type'=>'text',
                        'label'=>array('title'=>'Parent'), 'size'=>'medium', 'value'=>$competitor['parent']),
                    'address'=>array('type'=>'text',
                        'label'=>array('title'=>'Address'), 'size'=>'full', 'value'=>$competitor['address']),
                    'city'=>array('type'=>'text',
                        'label'=>array('title'=>'City'), 'size'=>'medium', 'value'=>$competitor['city']),
                    'province'=>array('type'=>'text',
                        'label'=>array('title'=>'Province'), 'size'=>'small', 'value'=>$competitor['province']),
                    'postal'=>array('type'=>'text',
                        'label'=>array('title'=>'Postal'), 'size'=>'small', 'value'=>$competitor['postal']),
                    'phone_home'=>array('type'=>'text',
                        'label'=>array('title'=>'Home Phone'), 'size'=>'small', 'value'=>$competitor['phone_home']),
                    'phone_cell'=>array('type'=>'text',
                        'label'=>array('title'=>'Cell Phone'), 'size'=>'small', 'value'=>$competitor['phone_cell']),
                    'email'=>array('type'=>'text',
                        'label'=>array('title'=>'Email'), 'size'=>'medium', 'value'=>$competitor['email']),
                    'age'=>array('type'=>'text', 'label'=>array(
                        'title'=>'Age' . ($args['settings']['age-restriction-msg'] != '' ? ' ' . $args['settings']['age-restriction-msg'] : '')),
                        'size'=>'small', 'value'=>$competitor['age']),
                    'notes'=>array('type'=>'text',
                        'label'=>array('title'=>'Notes'), 'size'=>'full', 'value'=>$competitor['notes']),
                    'waiver_signed'=>array('type'=>'checkbox',
                        'label'=>array('title'=>$args['settings']['waiver-title']), 'size'=>'full', 'checked_value'=>'signed', 'value'=>$competitor['waiver_signed'], 'msg'=>$args['settings']['waiver-msg']),
                    ),
                ),
            ); 
        $blocks[] = array('type' => 'registrationform', 
            'id' => 'mcf',
            'action' => '',
            'method' => 'POST',
            'sections' => $form_sections,
            'buttons' => array(
                'save' => array('type'=>'submit', 'label'=>'Save'),
                ),
            'javascript' => ""
            );
    }

    return array('stat'=>'ok', 'blocks'=>$blocks);
}
?>
