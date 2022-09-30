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
function ciniki_writingfestivals_wng_winnersProcess(&$ciniki, $tnid, &$request, $section) {

    if( !isset($ciniki['tenant']['modules']['ciniki.writingfestivals']) ) {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.writingfestivals.204', 'msg'=>"I'm sorry, the page you requested does not exist."));
    }

    //
    // Make sure a valid section was passed
    //
    if( !isset($section['ref']) || !isset($section['settings']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.205', 'msg'=>"No festival specified"));
    }
    $s = $section['settings'];
    $blocks = array();

    //
    // Make sure a festival was specified
    //
    if( !isset($s['festival-id']) || $s['festival-id'] == '' || $s['festival-id'] == 0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.206', 'msg'=>"No festival specified"));
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
    // Get the list of winners for the festival
    //
    $strsql = "SELECT winners.id, "
        . "winners.permalink, "
        . "winners.category, "
        . "winners.award, "
        . "winners.title, "
        . "winners.author, "
        . "winners.permalink, "
        . "winners.synopsis "
        . "FROM ciniki_writingfestival_winners AS winners "
        . "WHERE winners.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND winners.festival_id = '" . ciniki_core_dbQuote($ciniki, $s['festival-id']) . "' "
        . "ORDER BY category, sequence, award, title, author "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.writingfestivals', array(
        array('container'=>'winners', 'fname'=>'permalink', 
            'fields'=>array(
                'id', 'permalink', 'category', 'award', 'title', 'author', 'permalink', 'synopsis'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.219', 'msg'=>'Unable to load winners', 'err'=>$rc['err']));
    }
    $winners = isset($rc['winners']) ? $rc['winners'] : array();

    //
    // Check for syllabus section requested
    //
    if( isset($request['uri_split'][($request['cur_uri_pos']+1)])
        && $request['uri_split'][($request['cur_uri_pos']+1)] != '' 
        && isset($winners[$request['uri_split'][($request['cur_uri_pos']+1)]])
        ) {
        $request['cur_uri_pos']++;
        ciniki_core_loadMethod($ciniki, 'ciniki', 'writingfestivals', 'wng', 'winnerProcess');
        return ciniki_writingfestivals_wng_winnerProcess($ciniki, $tnid, $request, $section);
    }

    //
    // Add the title block
    //
    $blocks[] = array(
        'type' => 'title', 
        'title' => isset($s['title']) ? $s['title'] : 'Winners',
        );

    if( count($winners) > 0 ) {
        foreach($winners as $wid => $winner) {
            $winners[$wid]['meta'] = $winner['title'] . ' - ' . $winner['author'];
            $winners[$wid]['title'] = $winner['category'] . ' - ' . $winner['award'];
            $winners[$wid]['url'] = $request['page']['path'] . '/' . $winner['permalink'];
            $winners[$wid]['button-1-text'] = 'Read More';
            $winners[$wid]['button-1-url'] = $winners[$wid]['url'];
        }
        $blocks[] = array(
            'type' => 'tradingcards',
            'items' => $winners,
            );
/*        $blocks[] = array(
            'type' => 'table',
            'headers' => 'yes', 
            'columns' => array(
                array('label'=>'Category', 'field'=>'category', 'class'=>''),
                array('label'=>'Award', 'field'=>'award', 'class'=>''),
                array('label'=>'Title', 'field'=>'title', 'class'=>''),
                array('label'=>'Author', 'field'=>'author', 'class'=>''),
                ),
            'rows' => $winners
            ); */
    } else {
        $blocks[] = array(
            'type' => 'text', 
            'content' => 'No winners for this festival',
            );
    }

    return array('stat'=>'ok', 'blocks'=>$blocks);
}
?>
