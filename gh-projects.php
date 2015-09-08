<?php
/*
Plugin Name: GH Projects
Plugin URI:  http://github.com/mfalkus/gh-projects
Description: Using a shortcode, grab your public repos and descriptions from github
Version:     1.0
Author:      Martin Falkus
Author URI:  http://falkus.co
License:     GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

// Only makes sense to use in WP
if ( !defined('WPINC') ) {
    die;
}

// Return the newest repo in terms of recent pushed commit
function cmp($a, $b) {
    return strcmp($b->pushed_at, $a->pushed_at);
}

// [ghprojects user="username_here"]
function ghprojects_func( $atts ) {
    $a = shortcode_atts( array(
        'user'      => '',
        'nofork'    => '',
    ), $atts );

    if ($a['user'] === '') {
        error_log('No github user provided!');
        return; // we need a username to try and get
    }

    $user = urlencode($a['user']);

    // Create a stream
    $opts = array(
        'http' => array(
            'method'    => "GET",
            'header'    => "Accept: application/vnd.github.v3+json\r\n"
                         . "User-Agent: $user\r\n"
        )
    );

    $context = stream_context_create($opts);

    // Open the file using the HTTP headers set above
    $projects_json = file_get_contents(
        "https://api.github.com/users/" . $user . "/repos",
        false,
        $context
    );

    $all_projects = json_decode($projects_json);

    // Order by the 'pushed' time
    usort($all_projects, "cmp");

    $output = '<ul class="project-list">';
    foreach ($all_projects as $project) {
        if ($project->fork && $a['nofork'] !== '') {
            continue;
        }

        $output .= '<li class="project-item">'
            . '<h3 class="project-title"><a href="' . $project->html_url . '">'
                . $project->name
            . '</a> '
            . '<small class="date">'
                . date('d M Y', strtotime($project->pushed_at))
            . '</small>'
            . '</h3>'
            . '<p>' . $project->description . '</p>';
    }
    $output .= '</ul>';
    
    return $output;
}
add_shortcode( 'ghprojects', 'ghprojects_func' );
