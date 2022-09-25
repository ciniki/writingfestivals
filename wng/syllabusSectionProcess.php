<?php
//
// Description
// -----------
// This function will process a wng request for the blog module.
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_writingfestivals_wng_syllabusSectionProcess(&$ciniki, $tnid, &$request, $section) {

    if( !isset($ciniki['tenant']['modules']['ciniki.writingfestivals']) ) {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.writingfestivals.214', 'msg'=>"I'm sorry, the page you requested does not exist."));
    }

    //
    // Make sure a valid section was passed
    //
    if( !isset($section['ref']) || !isset($section['settings']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.215', 'msg'=>"No festival specified"));
    }
    $s = $section['settings'];
    $blocks = array();


    //
    // Make sure a festival was specified
    //
    if( !isset($s['festival-id']) || $s['festival-id'] == '' || $s['festival-id'] == 0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.247', 'msg'=>"No festival specified"));
    }

    //
    // Load the tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];

    //
    // Check for syllabus section requested
    //
    if( !isset($request['uri_split'][$request['cur_uri_pos']])
        || $request['uri_split'][$request['cur_uri_pos']] == '' 
        ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.216', 'msg'=>"No syllabus specified"));
    }

    $section_permalink = $request['uri_split'][$request['cur_uri_pos']];
    $base_url = $request['base_url'] . $request['page']['path'];

    //
    // Check for image format
    //
    $thumbnail_format = 'square-cropped';
    $thumbnail_padding_color = '#ffffff';
    if( isset($s['thumbnail-format']) && $s['thumbnail-format'] == 'square-padded' ) {
        $thumbnail_format = $s['thumbnail-format'];
        if( isset($s['thumbnail-padding-color']) && $s['thumbnail-padding-color'] != '' ) {
            $thumbnail_padding_color = $s['thumbnail-padding-color'];
        } 
    }
    
    //
    // Get the writing festival details
    //
    $dt = new DateTime('now', new DateTimezone('UTC'));
    $strsql = "SELECT id, name, flags, "
        . "earlybird_date, "
        . "live_date, "
        . "virtual_date "
//        . "IFNULL(DATEDIFF(earlybird_date, '" . ciniki_core_dbQuote($ciniki, $dt->format('Y-m-d')) . "'), -1) AS earlybird, "
//        . "IFNULL(DATEDIFF(virtual_date, '" . ciniki_core_dbQuote($ciniki, $dt->format('Y-m-d')) . "'), -1) AS virtual "
        . "FROM ciniki_writingfestivals "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND id = '" . ciniki_core_dbQuote($ciniki, $s['festival-id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.writingfestivals', 'festival');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['festival']) ) {
        $festival = $rc['festival'];
        $earlybird_dt = new DateTime($rc['festival']['earlybird_date'], new DateTimezone('UTC'));
        $live_dt = new DateTime($rc['festival']['live_date'], new DateTimezone('UTC'));
        $virtual_dt = new DateTime($rc['festival']['virtual_date'], new DateTimezone('UTC'));
        $festival['earlybird'] = ($earlybird_dt > $dt ? 'yes' : 'no');
        $festival['live'] = ($live_dt > $dt ? 'yes' : 'no');
        $festival['virtual'] = ($virtual_dt > $dt ? 'yes' : 'no');
    }

    //
    // Get the section details
    //
    $strsql = "SELECT sections.id, "
        . "sections.permalink, "
        . "sections.name, "
        . "sections.primary_image_id, "
        . "sections.synopsis, "
        . "sections.description "
        . "FROM ciniki_writingfestival_sections AS sections "
        . "WHERE sections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND sections.festival_id = '" . ciniki_core_dbQuote($ciniki, $s['festival-id']) . "' "
        . "AND sections.permalink = '" . ciniki_core_dbQuote($ciniki, $section_permalink) . "' "
        . "ORDER BY sections.sequence, sections.name "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.writingfestivals', 'section');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.217', 'msg'=>'Unable to load section', 'err'=>$rc['err']));
    }
    if( !isset($rc['section']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.218', 'msg'=>'Unable to find requested section'));
    }
    $section = $rc['section'];
  
    if( isset($section['description']) && $section['description'] != '' ) {
        $blocks[] = array(
            'type' => 'text',
            'title' => (isset($s['title']) ? $s['title'] : 'Syllabus') . ' - ' . $section['name'],
            'content' => $section['description'],
            );
    } else {
        $blocks[] = array(
            'type' => 'title', 
            'title' => (isset($s['title']) ? $s['title'] : 'Syllabus') . ' - ' . $section['name'],
            );
    }

    //
    // Load the syllabus for the section
    //
    $strsql = "SELECT classes.id, "
        . "classes.uuid, "
        . "classes.festival_id, "
        . "classes.category_id, "
        . "categories.id AS category_id, "
        . "categories.name AS category_name, "
        . "categories.primary_image_id AS category_image_id, "
        . "categories.synopsis AS category_synopsis, "
        . "categories.description AS category_description, "
        . "classes.code, "
        . "classes.name, "
        . "classes.permalink, "
        . "classes.sequence, "
        . "classes.flags, "
        . "earlybird_fee, "
        . "fee, "
        . "virtual_fee "
        . "FROM ciniki_writingfestival_categories AS categories "
        . "INNER JOIN ciniki_writingfestival_classes AS classes ON ("
            . "categories.id = classes.category_id "
            . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE categories.section_id = '" . ciniki_core_dbQuote($ciniki, $section['id']) . "' "
        . "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "ORDER BY categories.sequence, categories.name, classes.sequence, classes.name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.writingfestivals', array(
        array('container'=>'categories', 'fname'=>'category_id', 
            'fields'=>array('name'=>'category_name', 'image_id'=>'category_image_id', 'synopsis'=>'category_synopsis', 'description'=>'category_description')),
        array('container'=>'classes', 'fname'=>'id', 
            'fields'=>array('id', 'uuid', 'festival_id', 'category_id', 'code', 'name', 'permalink', 'sequence', 'flags', 
                'earlybird_fee', 'fee', 'virtual_fee')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['categories']) ) {
        $categories = $rc['categories'];
        foreach($categories as $category) {
            $blocks[] = array(
                'type' => 'text', 
                'title' => $category['name'], 
                'content' => ($category['description'] != '' ? $category['description'] : ($category['synopsis'] != '' ? $category['synopsis'] : ' ')),
                );
            if( isset($category['classes']) && count($category['classes']) > 0 ) {
                //
                // Process the classes to determine which fee to show
                //
                foreach($category['classes'] as $cid => $class) {
                    if( $festival['live'] == 'yes' ) {
                        if( isset($festival['earlybird']) && $festival['earlybird'] == 'yes' && $class['earlybird_fee'] > 0 ) {
                            $category['classes'][$cid]['live_fee'] = '$' . number_format($class['earlybird_fee'], 2);
                        } elseif( isset($festival['live']) && $festival['live'] == 'yes' && $class['fee'] > 0 ) {
                            $category['classes'][$cid]['live_fee'] = '$' . number_format($class['fee'], 2);
                        } else {
                            $category['classes'][$cid]['live_fee'] = 'n/a';
                        }
                    } else {
                        $category['classes'][$cid]['live_fee'] = 'Registration Closed';
                    }
                    if( ($festival['flags']&0x04) == 0x04 ) {
                        if( $festival['virtual'] == 'yes' && $class['virtual_fee'] > 0 ) {
                            $category['classes'][$cid]['virtual_fee'] = '$' . number_format($class['virtual_fee'], 2);
                        } elseif( $festival['virtual'] == 'yes' ) {
                            $category['classes'][$cid]['virtual_fee'] = 'n/a';
                        } else {
                            $category['classes'][$cid]['virtual_fee'] = 'Registration Closed';
                        }
                    }
                    $category['classes'][$cid]['fullname'] = $class['code'] . ' - ' . $class['name'];
                    if( ($festival['flags']&0x01) == 0x01 
                        && ($festival['live'] == 'yes' || $festival['virtual'] == 'yes') 
                        ) {
                        $category['classes'][$cid]['register'] = "<a class='button' href='{$request['ssl_domain_base_url']}/account/writingfestivalregistrations?add=yes&cl=" . $class['uuid'] . "'>Register</a>";
                    }
                }
                //
                // Check if online registrations enabled, and online registrations enabled for this class
                //
                if( ($festival['flags']&0x06) == 0x06 ) {   // Virtual option & Virtual Pricing
                    $blocks[] = array(
                        'type' => 'table', 
                        'section' => 'classes', 
                        'headers' => ($festival['flags']&0x04) == 0x04 ? 'yes' : 'no',
                        'class' => 'fold-at-40',
                        'columns' => array(
                            array('label'=>'Class', 'fold-label'=>'Class', 'field'=>'fullname', 'class'=>''),
    //                            array('label'=>'Course', 'field'=>'name', 'class'=>''),
                            array('label'=>'Live', 'fold-label'=>(($festival['flags']&0x04) == 0x04 ? 'Live' : ''), 'field'=>'live_fee', 'class'=>'aligncenter'),
                            array('label'=>'Virtual', 'fold-label'=>'Virtual', 'field'=>'virtual_fee', 'class'=>'aligncenter'),
                            array('label'=>'', 'field'=>'register', 'class'=>'alignright buttons'),
                            ),
                        'rows' => $category['classes'],
                        );
                } else {
                    $blocks[] = array(
                        'type' => 'table', 
                        'section' => 'classes', 
                        'headers' => 'no',
                        'class' => 'fold-at-40',
                        'columns' => array(
                            array('label'=>'Class', 'fold-label'=>'Class', 'field'=>'fullname', 'class'=>''),
                            array('label'=>'Live', 'fold-label'=>'Fee', 'field'=>'live_fee', 'class'=>'aligncenter'),
                            array('label'=>'', 'field'=>'register', 'class'=>'alignright buttons'),
                            ),
                        'rows' => $category['classes'],
                        );
                }
            }
        }
    }


    return array('stat'=>'ok', 'blocks'=>$blocks);
}
?>
