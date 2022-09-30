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
function ciniki_writingfestivals_wng_syllabusProcess(&$ciniki, $tnid, &$request, $section) {

    if( !isset($ciniki['tenant']['modules']['ciniki.writingfestivals']) ) {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.writingfestivals.210', 'msg'=>"I'm sorry, the page you requested does not exist."));
    }

    //
    // Make sure a valid section was passed
    //
    if( !isset($section['ref']) || !isset($section['settings']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.230', 'msg'=>"No festival specified"));
    }
    $s = $section['settings'];
    $blocks = array();

    //
    // Make sure a festival was specified
    //
    if( !isset($s['festival-id']) || $s['festival-id'] == '' || $s['festival-id'] == 0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.212', 'msg'=>"No festival specified"));
    }

    //
    // Check for syllabus section requested
    //
    if( isset($request['uri_split'][($request['cur_uri_pos']+1)])
        && $request['uri_split'][($request['cur_uri_pos']+1)] != '' 
        ) {
        $request['cur_uri_pos']++;
        ciniki_core_loadMethod($ciniki, 'ciniki', 'writingfestivals', 'wng', 'syllabusSectionProcess');
        return ciniki_writingfestivals_wng_syllabusSectionProcess($ciniki, $tnid, $request, $section);
    }

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
    // Get the list of sections
    //
    $strsql = "SELECT sections.id, "
        . "sections.permalink, "
        . "sections.name, "
        . "sections.primary_image_id, "
        . "sections.synopsis "
        . "FROM ciniki_writingfestival_sections AS sections "
        . "WHERE sections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND sections.festival_id = '" . ciniki_core_dbQuote($ciniki, $s['festival-id']) . "' "
        . "AND (sections.flags&0x01) = 0 "
        . "ORDER BY sections.sequence, sections.name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.writingfestivals', array(
        array('container'=>'sections', 'fname'=>'id', 
            'fields'=>array('id', 'permalink', 'title'=>'name', 'image-id'=>'primary_image_id', 'synopsis'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.213', 'msg'=>'Unable to load syllabus', 'err'=>$rc['err']));
    }
    $sections = isset($rc['sections']) ? $rc['sections'] : array();

    //
    // Add the title block
    //
    $blocks[] = array(
        'type' => 'title', 
        'title' => isset($s['title']) ? $s['title'] : 'Syllabus',
        );

    //
    // Display as trading cards
    //
    if( isset($s['layout']) && $s['layout'] == 'tradingcards' ) {
        foreach($sections as $sid => $section) {
            $sections[$sid]['url'] = ($request['page']['path'] != '/' ? $request['page']['path'] : '') . '/' . $section['permalink'];
            $sections[$sid]['button-class'] = isset($s['button-class']) && $s['button-class'] != '' ? $s['button-class'] : 'button';
            $sections[$sid]['button-1-text'] = 'View Syllabus';
            $sections[$sid]['button-1-url'] = ($request['page']['path'] != '/' ? $request['page']['path'] : '') . '/' . $section['permalink'];
        }
        $blocks[] = array(
            'type' => 'tradingcards',
            'image-format' => (isset($s['image-format']) && $s['image-format'] == 'padded' ? 'padded' : 'cropped'),
            'items' => $sections,
            );
    } 

    //
    // Default to gallery
    //
    else {
        foreach($sections as $sid => $section) {
            $sections[$sid]['url'] = $request['page']['path'] . '/' . $section['permalink'];
            $sections[$sid]['image-ratio'] = '4-3';
            $sections[$sid]['title-position'] = 'overlay-bottomhalf';
            $sections[$sid]['url'] = $request['page']['path'] . '/' . $section['permalink'];
        }
        $blocks[] = array(
            'type' => 'imagebuttons',
            'items' => $sections,
            );
    }

    return array('stat'=>'ok', 'blocks'=>$blocks);
}
?>
