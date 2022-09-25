<?php
//
// Description
// -----------
// This function will check for competitors in the writing festivals
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_writingfestivals_wng_accountCustomerTypeProcess(&$ciniki, $tnid, &$request, $args) {


    //
    // Customer types are NOT used for writing festival. This function is left as a placeholder incase required in future.
    //


    return array('stat'=>'ok', 'customer_type'=>10);



    $blocks = array();

    $settings = isset($request['site']['settings']) ? $request['site']['settings'] : array();

    //
    // Get the type of customer
    //
    $strsql = "SELECT id, ctype "
        . "FROM ciniki_writingfestival_customers "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival']['id']) . "' "
        . "AND customer_id = '" . ciniki_core_dbQuote($ciniki, $request['session']['customer']['id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.writingfestivals', 'customer');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.346', 'msg'=>'Unable to load customer', 'err'=>$rc['err']));
    }
    $ctype_id = isset($rc['customer']['id']) ? $rc['customer']['id'] : 0;
    $customer_type = isset($rc['customer']['ctype']) ? $rc['customer']['ctype'] : 0;

    $additional_args = '';
    if( isset($_GET['add']) && $_GET['add'] != '' ) {
        $additional_args .= '&add=' . $_GET['add'];
    }
    if( isset($_GET['r']) && $_GET['r'] != '' ) {
        $additional_args .= '&r=' . $_GET['r'];
    }
    if( isset($_GET['cl']) && $_GET['cl'] != '' ) {
        $additional_args .= '&cl=' . $_GET['cl'];
    }

    //
    // Check for the customer type and if not ask for it
    //
    if( $customer_type == 0 || isset($_GET['changetype']) ) {
        //
        // Check if customer type was submitted
        //
        if( isset($_GET['ctype']) && in_array($_GET['ctype'], array(10,20,30)) ) {
            //
            // Add the customer to the writingfestival
            //
            if( $ctype_id > 0 ) {
                ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
                $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.writingfestivals.customer', $ctype_id, array(
                    'ctype' => $_GET['ctype'],
                    ), 0x04);
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.361', 'msg'=>'Unable to update the customer', 'err'=>$rc['err']));
                }
                if( $additional_args != '' ) {
                    header("Location: {$args['base_url']}?{$additional_args}");
                } else {
                    header("Location: {$args['base_url']}");
                }
                return array('stat'=>'exit');

            } else {
                ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
                $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.writingfestivals.customer', array(
                    'festival_id' => $args['festival']['id'],
                    'customer_id' => $request['session']['customer']['id'],
                    'ctype' => $_GET['ctype'],
                    ), 0x04);
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.373', 'msg'=>'Unable to add the customer', 'err'=>$rc['err']));
                }
                if( $additional_args != '' ) {
                    header("Location: {$args['base_url']}?{$additional_args}");
                } else {
                    header("Location: {$args['base_url']}");
                }
                return array('stat'=>'exit');
            }
        } 
        
        //
        // Ask the customer what type they are
        //
        else {
            if( isset($_GET['changetype']) ) {
                $additional_args .= '&changetype';
            }
            $blocks[] = array(
                'type' => 'text', 
                'class' => 'aligncenter',
                'content' => 'In order to better serve you, we need to know who you are.',
                );
            $blocks[] = array(
                'type' => 'buttons',
                'class' => 'aligncenter decisionbuttons width-30',
                'list' => array(
                    array(
                        'text' => 'I am a Parent registering my Children',
                        'url' => "{$args['base_url']}?ctype=10" . $additional_args,
                        ),
                    array(
                        'text' => 'I am a Teacher registering my Students',
                        'url' => "{$args['base_url']}?ctype=20" . $additional_args,
                        ),
                    array(
                        'text' => 'I am an Adult registering Myself',
                        'url' => "{$args['base_url']}?ctype=30" . $additional_args,
                        ),
                    ));
            return array('stat'=>'ok', 'blocks'=>$blocks, 'stop'=>'yes');
        }
    }

    $rsp = array('stat'=>'ok', 'customer_type'=>$customer_type);

    //
    // Setup the block to show them what they are current registered as and allow them to switch
    //
    if( $customer_type == 10 ) {
        $rsp['switch_block'] = array(
            'type' => 'text',
            'class' => 'limit-width limit-width-60 aligncenter',
            'content' => "<br/>You are registering competitors as a parent.<br/><br/><a class='button' href='{$args['base_url']}?changetype'>Change Registration Type</a>"
            );
    } elseif( $customer_type == 20 ) {
        $rsp['switch_block'] = array(
            'type' => 'text',
            'class' => 'limit-width limit-width-60 aligncenter',
            'content' => "<br/>You are registering competitors as a teacher.<br/><br/><a class='button' href='{$args['base_url']}?changetype'>Change Registration Type</a>"
            );
    } elseif( $customer_type == 30 ) {
        $rsp['switch_block'] = array(
            'type' => 'text',
            'class' => 'limit-width limit-width-60 aligncenter',
            'content' => "<br/>You are registering yourself as a competitor.<br/><br/><a class='button' href='{$args['base_url']}?changetype'>Change Registration Type</a>"
            );
    }

    return $rsp;
}
?>
