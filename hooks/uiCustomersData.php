<?php
//
// Description
// -----------
// This function will return the data for customer(s) to be displayed in the IFB display panel.
// The request might be for 1 individual, or multiple customer ids for a family.
//
// Arguments
// ---------
// ciniki:
// tnid:     The ID of the tenant to get writingfestivals for.
//
// Returns
// -------
//
function ciniki_writingfestivals_hooks_uiCustomersData($ciniki, $tnid, $args) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteIDs');

    //
    // Default response
    //
    $rsp = array('stat'=>'ok', 'tabs'=>array());

    //
    // Get the list of adjudications
    //
    $sections['ciniki.writingfestivals.adjudicators'] = array(
        'label' => 'Adjudications',
        'type' => 'simplegrid', 
        'num_cols' => 1,
        'headerValues' => array('Festival'),
        'cellClasses' => array(''),
        'noData' => 'No adjudications',
//            'editApp' => array('app'=>'ciniki.writingfestivals.sapos', 'args'=>array('registration_id'=>'d.id;', 'source'=>'\'\'')),
        'cellValues' => array(
            '0' => "d.name",
            ),
        'data' => array(),
        );
    $strsql = "SELECT adjudicators.id, "
        . "adjudicators.customer_id, "
        . "festivals.name "
        . "FROM ciniki_writingfestival_adjudicators AS adjudicators "
        . "LEFT JOIN ciniki_writingfestivals AS festivals ON ("
            . "adjudicators.festival_id = festivals.id "
            . "AND festivals.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE adjudicators.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    if( isset($args['customer_id']) ) {
        $strsql .= "AND adjudicators.customer_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' ";
    } elseif( isset($args['customer_ids']) && count($args['customer_ids']) > 0 ) {
        $strsql .= "AND adjudicators.customer_id IN (" . ciniki_core_dbQuoteIDs($ciniki, $args['customer_ids']) . ") ";
    } else {
        return array('stat'=>'ok');
    }
    $strsql .= "ORDER BY festivals.start_date DESC, festivals.name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.customers', array(
        array('container'=>'adjudicators', 'fname'=>'id', 
            'fields'=>array('id', 'customer_id', 'name'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $sections['ciniki.writingfestivals.adjudicators']['data'] = isset($rc['adjudicators']) ? $rc['adjudicators'] : array();

    //
    // Get the list of competitors
    //
    $sections['ciniki.writingfestivals.competitors'] = array(
        'label' => 'Competitors',
        'type' => 'simplegrid', 
        'num_cols' => 1,
        'headerValues' => array('Festival'),
        'cellClasses' => array(''),
        'noData' => 'No competitors',
//            'editApp' => array('app'=>'ciniki.writingfestivals.sapos', 'args'=>array('registration_id'=>'d.id;', 'source'=>'\'\'')),
        'cellValues' => array(
            '0' => "d.name",
            ),
        'data' => array(),
        );
    $strsql = "SELECT competitors.id, "
        . "competitors.billing_customer_id, "
        . "festivals.name "
        . "FROM ciniki_writingfestival_competitors AS competitors "
        . "LEFT JOIN ciniki_writingfestivals AS festivals ON ("
            . "competitors.festival_id = festivals.id "
            . "AND festivals.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE competitors.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    if( isset($args['customer_id']) ) {
        $strsql .= "AND competitors.billing_customer_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' ";
    } elseif( isset($args['customer_ids']) && count($args['customer_ids']) > 0 ) {
        $strsql .= "AND competitors.billing_customer_id IN (" . ciniki_core_dbQuoteIDs($ciniki, $args['customer_ids']) . ") ";
    } else {
        return array('stat'=>'ok');
    }
    $strsql .= "ORDER BY festivals.start_date DESC, festivals.name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.customers', array(
        array('container'=>'competitors', 'fname'=>'id', 
            'fields'=>array('id', 'billing_customer_id', 'name'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $sections['ciniki.writingfestivals.competitors']['data'] = isset($rc['competitors']) ? $rc['competitors'] : array();

    //
    // Get the list of customers
    //
    $sections['ciniki.writingfestivals.customers'] = array(
        'label' => 'Signups',
        'type' => 'simplegrid', 
        'num_cols' => 1,
        'headerValues' => array('Festival'),
        'cellClasses' => array(''),
        'noData' => 'No signups',
//            'editApp' => array('app'=>'ciniki.writingfestivals.sapos', 'args'=>array('registration_id'=>'d.id;', 'source'=>'\'\'')),
        'cellValues' => array(
            '0' => "d.name",
            ),
        'data' => array(),
        );
    $strsql = "SELECT customers.id, "
        . "customers.customer_id, "
        . "festivals.name "
        . "FROM ciniki_writingfestival_customers AS customers "
        . "LEFT JOIN ciniki_writingfestivals AS festivals ON ("
            . "customers.festival_id = festivals.id "
            . "AND festivals.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE customers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    if( isset($args['customer_id']) ) {
        $strsql .= "AND customers.customer_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' ";
    } elseif( isset($args['customer_ids']) && count($args['customer_ids']) > 0 ) {
        $strsql .= "AND customers.customer_id IN (" . ciniki_core_dbQuoteIDs($ciniki, $args['customer_ids']) . ") ";
    } else {
        return array('stat'=>'ok');
    }
    $strsql .= "ORDER BY festivals.start_date DESC, festivals.name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.customers', array(
        array('container'=>'customers', 'fname'=>'id', 
            'fields'=>array('id', 'customer_id', 'name'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $sections['ciniki.writingfestivals.customers']['data'] = isset($rc['customers']) ? $rc['customers'] : array();

    //
    // Get the list of registrations
    //
    $sections['ciniki.writingfestivals.registrations'] = array(
        'label' => 'Registrations',
        'type' => 'simplegrid', 
        'num_cols' => 1,
        'headerValues' => array('Festival'),
        'cellClasses' => array(''),
        'noData' => 'No registrations',
//            'editApp' => array('app'=>'ciniki.writingfestivals.sapos', 'args'=>array('registration_id'=>'d.id;', 'source'=>'\'\'')),
        'cellValues' => array(
            '0' => "d.name",
            ),
        'data' => array(),
        );
    $strsql = "SELECT registrations.id, "
        . "registrations.teacher_customer_id, "
        . "registrations.billing_customer_id, "
        . "festivals.name "
        . "FROM ciniki_writingfestival_registrations AS registrations "
        . "LEFT JOIN ciniki_writingfestivals AS festivals ON ("
            . "registrations.festival_id = festivals.id "
            . "AND festivals.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    if( isset($args['customer_id']) ) {
        $strsql .= "AND ("
            . "registrations.teacher_customer_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
            . "OR registrations.billing_customer_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
            . ") ";
    } elseif( isset($args['customer_ids']) && count($args['customer_ids']) > 0 ) {
        $strsql .= "AND ("
            . "registrations.teacher_customer_id IN (" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . ") "
            . "OR registrations.billing_customer_id IN (" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . ") "
            . ") ";
    } else {
        return array('stat'=>'ok');
    }
    $strsql .= "ORDER BY festivals.start_date DESC, festivals.name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.customers', array(
        array('container'=>'registrations', 'fname'=>'id', 
            'fields'=>array('id', 'teacher_customer_id', 'billing_customer_id', 'name'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $sections['ciniki.writingfestivals.registrations']['data'] = isset($rc['registrations']) ? $rc['registrations'] : array();

    //
    // Setup the tab
    //
    $rsp['tabs'][] = array(
        'id' => 'ciniki.writingfestivals.festivals',
        'label' => 'Writing',
        'sections' => $sections,
        );
    $sections = array();

    return $rsp;
}
?>
