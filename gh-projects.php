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

define('GHPROJECTS_CACHE', 'gh-projects-cache');
define('GHPROJECTS_EXPIRE', 5 * MINUTE_IN_SECONDS);

// Only makes sense to use in WP
if ( !defined('WPINC') ) { die; }

/**
 * Output an unordered HTML list of github projects for a given user
 *
 * The format to use is:
 *    [ghprojects user="username_here"]
 * Output includes pushed time and repo description.
 *
 * @param array $atts from shortcode:
 *  user        => GitHub user to fetch projects for
 *  nofork      => whether forked repos should not be shown
 *  clear_cache => Force clear the WP transient cache
 *
 * @return string html list of projects
 */
function ghprojects_func( $atts ) {
    $a = shortcode_atts( array(
        'user'          => '',
        'nofork'        => '',
        'clear_cache'   => 0,
    ), $atts );

    if ($a['user'] === '') {
        error_log('No github user provided!');
        return; // we need a username to try and get
    }

    $user = urlencode($a['user']);
    $all_projects = _get_github_projects($user, $a['clear_cache']);

    if ($all_projects === false) {
        error_log("Unable to retrieve GitHub projects for $user!");
        return;
    }

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

/**
 * Fetch a GitHub users public projects
 *
 * @param string $user GitHub user whose projects we want
 * @param boolean $clear_cache Force a clear of the WP transient cache
 *
 * @return array of projects
 */
function _get_github_projects($user, $clear_cache) {
    // Currently we assume a cache hit will be for the correct user
    $cache = get_transient(GHPROJECTS_CACHE);

    if (($cache === false) || $clear_cache) {
        error_log('cache miss');

        $context = stream_context_create( array(
            'http' => array(
                'method'    => "GET",
                'header'    => "Accept: application/vnd.github.v3+json\r\n"
                             . "User-Agent: $user\r\n"
            )
        ) );

        // Open the file using the HTTP headers set above
        // User-Agent is a must for GitHub to respond
        $projects_json = file_get_contents(
            "https://api.github.com/users/" . $user . "/repos",
            false,
            $context
        );

        set_transient(GHPROJECTS_CACHE, $projects_json, GHPROJECTS_EXPIRE);

    } else {
        error_log('cache hit');
        $projects_json = $cache;

    }

    // Order by the 'pushed' time
    $all_projects = json_decode($projects_json);
    usort($all_projects, "cmp");
    return $all_projects;
}

// Return the newest repo in terms of recent pushed commit
function cmp($a, $b) {
    return strcmp($b->pushed_at, $a->pushed_at);
}
